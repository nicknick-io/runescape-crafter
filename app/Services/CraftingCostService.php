<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class CraftingCostService
{
    /** @return array{tree: array, materials: array<int, array{item: array{id:int,name:string,image_url:?string,ge_price:float}, quantity: float}>, material_cost: float, output_value: float, profit: float, profit_margin: float, gp_per_xp: float|null, xp_cost: float|null} */
    public function calculate(Recipe $recipe): array
    {
        $recipe->loadMissing('outputItem', 'ingredients.item');
        $cacheKey = "crafting-cost:v2:{$recipe->id}:{$recipe->updated_at?->timestamp}:{$recipe->outputItem->ge_updated_at?->timestamp}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($recipe): array {
            $recipes = $this->loadGraph($recipe);
            $tree = $this->buildTree($recipe->outputItem, (float) $recipe->output_quantity, $recipes, []);
            $materials = $this->flattenTree($tree);
            $materialCost = collect($materials)->sum(fn (array $material) => $material['quantity'] * $material['item']['ge_price']);
            $outputValue = (float) $recipe->output_quantity * ((float) ($recipe->outputItem->ge_price ?? 0));
            $profit = $outputValue - $materialCost;
            $xp = (float) $recipe->xp;

            return ['tree' => $tree, 'materials' => $materials, 'material_cost' => $materialCost, 'output_value' => $outputValue, 'profit' => $profit, 'profit_margin' => $materialCost > 0 ? $profit / $materialCost * 100 : 0, 'gp_per_xp' => $xp > 0 ? $profit / $xp : null, 'xp_cost' => $xp > 0 ? $materialCost / $xp : null];
        });
    }

    /** @param array<int, int> $ingredientItemIds */
    public function validateRecipeIngredients(int $outputItemId, array $ingredientItemIds): void
    {
        if (in_array($outputItemId, $ingredientItemIds, true)) {
            throw ValidationException::withMessages(['ingredients' => 'An item cannot be an ingredient of its own recipe.']);
        }
        if (count($ingredientItemIds) !== count(array_unique($ingredientItemIds))) {
            throw ValidationException::withMessages(['ingredients' => 'A recipe cannot contain duplicate ingredients.']);
        }
        $recipes = Recipe::with('ingredients.item', 'outputItem')->get()->keyBy('output_item_id');
        foreach ($ingredientItemIds as $ingredientItemId) {
            if ($this->pathExists($ingredientItemId, $outputItemId, $recipes, [])) {
                throw ValidationException::withMessages(['ingredients' => 'This ingredient would create a circular crafting recipe.']);
            }
        }
    }

    /** @return Collection<int, Recipe> */
    private function loadGraph(Recipe $root): Collection
    {
        $recipes = collect([$root->load('ingredients.item', 'outputItem')])->keyBy('output_item_id');
        $pending = $root->ingredients->pluck('item_id')->all();
        while ($pending !== []) {
            $next = Recipe::with('ingredients.item', 'outputItem')->whereIn('output_item_id', $pending)->get();
            $new = $next->reject(fn (Recipe $recipe) => $recipes->has($recipe->output_item_id));
            $recipes = $recipes->merge($new->keyBy('output_item_id'));
            $pending = $new->flatMap(fn (Recipe $recipe) => $recipe->ingredients->pluck('item_id'))->unique()->values()->all();
        }
        return $recipes;
    }

    /** @param Collection<int, Recipe> $recipes @param array<int, bool> $path */
    private function buildTree(Item $item, float $quantity, Collection $recipes, array $path): array
    {
        if (isset($path[$item->id])) {
            throw ValidationException::withMessages(['recipe' => 'Circular recipe dependency detected.']);
        }
        $recipe = $recipes->get($item->id);
        if (! $recipe) return ['item' => $this->serializeItem($item), 'quantity' => $quantity, 'children' => []];
        $path[$item->id] = true;
        $multiplier = $quantity / $recipe->output_quantity;
        return ['item' => $this->serializeItem($item), 'quantity' => $quantity, 'children' => $recipe->ingredients->map(fn (RecipeIngredient $ingredient) => $this->buildTree($ingredient->item, (float) $ingredient->quantity * $multiplier, $recipes, $path))->all()];
    }

    /** @return array<int, array{item: array{id:int,name:string,image_url:?string,ge_price:float}, quantity: float}> */
    public function flattenTree(array $tree): array
    {
        $materials = [];
        $walk = function (array $node) use (&$walk, &$materials): void {
            if ($node['children'] === []) { $materials[$node['item']['id']] ??= ['item' => $node['item'], 'quantity' => 0.0]; $materials[$node['item']['id']]['quantity'] += $node['quantity']; return; }
            foreach ($node['children'] as $child) $walk($child);
        };
        foreach ($tree['children'] as $child) $walk($child);
        return $materials;
    }

    /** @return array{id:int,name:string,image_url:?string,ge_price:float} */
    private function serializeItem(Item $item): array
    {
        return ['id' => $item->id, 'name' => $item->name, 'image_url' => $item->image_url, 'ge_price' => (float) ($item->ge_price ?? 0)];
    }

    /** @param Collection<int, Recipe> $recipes @param array<int, bool> $visited */
    private function pathExists(int $current, int $target, Collection $recipes, array $visited): bool
    {
        if ($current === $target) return true;
        if (isset($visited[$current]) || ! $recipes->has($current)) return false;
        $visited[$current] = true;
        return $recipes->get($current)->ingredients->contains(fn (RecipeIngredient $ingredient) => $this->pathExists($ingredient->item_id, $target, $recipes, $visited));
    }
}
