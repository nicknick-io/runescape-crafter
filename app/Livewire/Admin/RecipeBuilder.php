<?php

namespace App\Livewire\Admin;

use App\Models\Item;
use App\Models\Recipe;
use App\Services\CraftingCostService;
use App\Services\WikiScraperService;
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
    public string $wikiUrl = '';
    public bool $isGenerating = false;
    public ?string $generationError = null;
    public ?int $toolId = null;
    public ?string $toolName = null;
    
    /** @var array<int, array{item_id: int|null, quantity: float}> */ 
    public array $ingredients = [['item_id' => null, 'quantity' => 1]];
    
    public function addIngredient(): void 
    { 
        $this->ingredients[] = ['item_id' => null, 'quantity' => 1]; 
    }
    
    public function generateFromWiki(WikiScraperService $scraper): void
    {
        try {
            $this->validate(['wikiUrl' => ['required', 'url']]);
            $this->isGenerating = true;
            $this->generationError = null;
            
            // Scrape the wiki page
            $data = $scraper->scrapeRecipe($this->wikiUrl);
            
            if ($data['confidence_score'] < 0.3) {
                $this->generationError = 'Could not reliably extract recipe data from this page. Confidence too low.';
                $this->isGenerating = false;
                return;
            }
            
            // Populate form fields with extracted data
            $this->outputItemId = $data['output_item_id'];
            $this->skill = $data['skill'] ?? 'Crafting';
            $this->levelRequired = (int) ($data['level_required'] ?? 1);
            $this->xp = (float) ($data['xp'] ?? 0);
            $this->outputQuantity = (int) ($data['output_quantity'] ?? 1);
            $this->toolId = $data['tool_id'] ?? null;
            $this->toolName = $data['tool_name'] ?? null;
            $this->wikiUrl = $data['wiki_url'] ?? $this->wikiUrl;
            
            // Populate ingredients
            $this->ingredients = array_map(fn ($ing) => [
                'item_id' => $ing['item_id'],
                'quantity' => (float) ($ing['quantity'] ?? 1),
            ], $data['ingredients'] ?? []);
            
            if (empty($this->ingredients)) {
                $this->ingredients = [['item_id' => null, 'quantity' => 1]];
            }
            
            session()->flash('status', 'Recipe generated from wiki! Please review and fact-check before saving.');
        } catch (\Throwable $e) {
            $this->generationError = 'Failed to generate recipe: ' . $e->getMessage();
        } finally {
            $this->isGenerating = false;
        }
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
        
        // Determine if this was generated from wiki
        $isGenerated = !empty($this->wikiUrl);
        
        $recipe = Recipe::updateOrCreate(
            ['output_item_id' => $this->outputItemId],
            [
                'output_quantity' => $this->outputQuantity,
                'skill' => $this->skill,
                'level_required' => $this->levelRequired,
                'xp' => $this->xp,
                'wiki_url' => $this->wikiUrl ?: null,
                'tool_id' => $this->toolId,
                'needs_review' => $isGenerated, // Mark AI-generated recipes as needs_review
            ]
        );
        
        $recipe->ingredients()->delete();
        $recipe->ingredients()->createMany($this->ingredients);
        
        // Clear wiki URL and reset form
        $this->wikiUrl = '';
        $this->toolId = null;
        $this->toolName = null;
        $this->itemSearch = '';
        $this->outputItemId = null;
        $this->skill = 'Crafting';
        $this->outputQuantity = 1;
        $this->levelRequired = 1;
        $this->xp = 0;
        $this->ingredients = [['item_id' => null, 'quantity' => 1]];
        
        session()->flash('status', $isGenerated ? 'Recipe saved as draft - it will appear in the review queue.' : 'Recipe saved.');
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

