<?php

namespace App\Livewire\Admin;

use App\Models\Item;
use App\Models\Recipe;
use App\Services\CraftingCostService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class RecipeBuilder extends Component
{
    public string $itemSearch = '';
    public ?int $outputItemId = null;
    public string $skill = 'Crafting';
    public int $outputQuantity = 1;
    public int $levelRequired = 1;
    public float $xp = 0;
    public ?int $editingIngredientIndex = null;
    public int $selectingFor = -1; // -1 for output, >= 0 for ingredient index
    
    /** @var array<int, array{item_id: int|null, quantity: float}> */ 
    public array $ingredients = [['item_id' => null, 'quantity' => 1]];
    
    public function addIngredient(): void 
    { 
        $this->ingredients[] = ['item_id' => null, 'quantity' => 1]; 
    }
    
    public function removeIngredient(int $index): void 
    { 
        unset($this->ingredients[$index]); 
        $this->ingredients = array_values($this->ingredients); 
    }
    
    public function openIngredientModal(int $index): void
    {
        $this->selectingFor = $index;
        $this->dispatch('open-ingredient-modal', selectingFor: $index);
    }

    public function openOutputItemModal(): void
    {
        $this->selectingFor = -1;
        $this->dispatch('open-ingredient-modal', selectingFor: -1);
    }
    
    #[On('ingredient-selected')]
    public function selectIngredient(int $selectingFor, int $itemId): void
    {
        if ($selectingFor === -1) {
            $this->outputItemId = $itemId;
        } elseif (isset($this->ingredients[$selectingFor])) {
            $this->ingredients[$selectingFor]['item_id'] = $itemId;
        }
    }
    
    public function updateQuantity(int $index, float $quantity): void
    {
        if (isset($this->ingredients[$index])) {
            $this->ingredients[$index]['quantity'] = max(0.001, $quantity);
        }
    }
    
    public function save(CraftingCostService $costs): void
    {
        $this->validate([
            'outputItemId' => ['required', 'integer', Rule::exists('items', 'id')],
            'skill' => ['required', 'string', 'max:255'],
            'outputQuantity' => ['required', 'integer', 'min:1'],
            'levelRequired' => ['required', 'integer', 'min:1'],
            'xp' => ['required', 'numeric', 'min:0'],
            'ingredients' => ['required', 'array', 'min:1'],
            'ingredients.*.item_id' => ['required', 'integer', Rule::exists('items', 'id')],
            'ingredients.*.quantity' => ['required', 'numeric', 'gt:0']
        ]);
        
        $ids = array_map(fn (array $ingredient) => (int) $ingredient['item_id'], $this->ingredients);
        $costs->validateRecipeIngredients($this->outputItemId, $ids);
        
        $recipe = Recipe::updateOrCreate(
            ['output_item_id' => $this->outputItemId],
            ['output_quantity' => $this->outputQuantity, 'skill' => $this->skill, 'level_required' => $this->levelRequired, 'xp' => $this->xp]
        );
        
        $recipe->ingredients()->delete();
        $recipe->ingredients()->createMany($this->ingredients);
        session()->flash('status', 'Recipe saved.');
    }
    
    public function render()
    {
        $allItems = Item::orderBy('name')->get();
        $searchResults = Item::query()
            ->when($this->itemSearch, fn ($q) => $q->where('name', 'like', "%{$this->itemSearch}%")->orWhere('rs_id', $this->itemSearch))
            ->orderBy('name')
            ->limit(20)
            ->get();
        
        $outputItem = $this->outputItemId ? Item::find($this->outputItemId) : null;
        $ingredientItems = Item::whereIn('id', array_filter(array_column($this->ingredients, 'item_id')))->get()->keyBy('id');

        return view('livewire.admin.recipe-builder', [
            'items' => $allItems,
            'searchResults' => $searchResults,
            'outputItem' => $outputItem,
            'ingredientItems' => $ingredientItems
        ])->layout('components.public-layout', ['title' => 'Recipe builder — RS3 Crafter']);
    }
}

