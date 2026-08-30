<?php

namespace App\Livewire;

use App\Models\Recipe;
use App\Services\CraftingCostService;
use Livewire\Component;

class RecipeShow extends Component
{
    public Recipe $recipe;
    public bool $treeOpen = true;
    public function mount(Recipe $recipe): void { $this->recipe = $recipe; }
    public function render(CraftingCostService $costs)
    {
        $this->recipe->loadMissing('ingredients.item', 'outputItem');
        $directMaterials = $this->recipe->ingredients->map(fn ($ing) => [
            'item' => [
                'id' => $ing->item->id,
                'name' => $ing->item->name,
                'image_url' => $ing->item->image_url,
                'ge_price' => (float) ($ing->item->ge_price ?? 0)
            ],
            'quantity' => (float) $ing->quantity,
        ])->all();
        
        $directMaterialCost = collect($directMaterials)->sum(fn (array $m) => $m['quantity'] * $m['item']['ge_price']);
        $outputValue = (float) $this->recipe->output_quantity * ((float) ($this->recipe->outputItem->ge_price ?? 0));
        $profit = $outputValue - $directMaterialCost;
        
        $calculation = $costs->calculate($this->recipe);
        $calculation['material_cost'] = $directMaterialCost;
        $calculation['profit'] = $profit;
        $calculation['profit_margin'] = $directMaterialCost > 0 ? $profit / $directMaterialCost * 100 : 0;
        $calculation['gp_per_xp'] = ((float) $this->recipe->xp) > 0 ? $profit / ((float) $this->recipe->xp) : null;

        return view('livewire.recipe-show', ['calculation' => $calculation, 'directMaterials' => $directMaterials])
            ->layout('components.public-layout', ['title' => $this->recipe->outputItem->name.' — RS3 Crafter']);
    }
}
