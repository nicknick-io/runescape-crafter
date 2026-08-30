<?php

use App\Models\Item;
use App\Models\Recipe;
use App\Services\CraftingCostService;
use Illuminate\Validation\ValidationException;

function rsItem(int $rsId, string $name, int $price): Item
{
    return Item::create(['rs_id' => $rsId, 'name' => $name, 'tradeable' => true, 'value' => 0, 'ge_price' => $price, 'ge_updated_at' => now()]);
}

it('expands unlimited crafting dependencies and calculates leaf material cost', function () {
    $copper = rsItem(1, 'Copper ore', 12);
    $tin = rsItem(2, 'Tin ore', 8);
    $bar = rsItem(3, 'Bronze bar', 100);
    $sword = rsItem(4, 'Bronze sword', 500);
    $barRecipe = Recipe::create(['output_item_id' => $bar->id, 'output_quantity' => 1, 'skill' => 'Smithing', 'level_required' => 1, 'xp' => 6]);
    $barRecipe->ingredients()->createMany([['item_id' => $copper->id, 'quantity' => 1], ['item_id' => $tin->id, 'quantity' => 1]]);
    $swordRecipe = Recipe::create(['output_item_id' => $sword->id, 'output_quantity' => 1, 'skill' => 'Smithing', 'level_required' => 1, 'xp' => 12]);
    $swordRecipe->ingredients()->create(['item_id' => $bar->id, 'quantity' => 2]);

    $calculation = app(CraftingCostService::class)->calculate($swordRecipe);

    expect($calculation['materials'][$copper->id]['quantity'])->toBe(2.0)
        ->and($calculation['materials'][$tin->id]['quantity'])->toBe(2.0)
        ->and($calculation['material_cost'])->toBe(40.0)
        ->and($calculation['profit'])->toBe(460.0)
        ->and($calculation['gp_per_xp'])->toBe(460 / 12);
});

it('does not include the output item itself as a material when no ingredient tree exists', function () {
    $bar = rsItem(30, 'Bronze bar', 1927);
    $barRecipe = Recipe::create(['output_item_id' => $bar->id, 'output_quantity' => 1, 'skill' => 'Smithing', 'level_required' => 1, 'xp' => 15]);

    $calculation = app(CraftingCostService::class)->calculate($barRecipe);

    expect($calculation['materials'])->toBe([])
        ->and($calculation['material_cost'])->toBe(0.0)
        ->and($calculation['output_value'])->toBe(1927.0)
        ->and($calculation['profit'])->toBe(1927.0);
});

it('rejects ingredients that close a circular dependency', function () {
    $a = rsItem(10, 'A', 1); $b = rsItem(11, 'B', 1); $c = rsItem(12, 'C', 1);
    $aRecipe = Recipe::create(['output_item_id' => $a->id, 'skill' => 'Crafting']); $aRecipe->ingredients()->create(['item_id' => $b->id, 'quantity' => 1]);
    $bRecipe = Recipe::create(['output_item_id' => $b->id, 'skill' => 'Crafting']); $bRecipe->ingredients()->create(['item_id' => $c->id, 'quantity' => 1]);

    expect(fn () => app(CraftingCostService::class)->validateRecipeIngredients($c->id, [$a->id]))->toThrow(ValidationException::class);
});
