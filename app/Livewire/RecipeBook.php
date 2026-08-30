<?php

namespace App\Livewire;

use App\Models\Recipe;
use App\Services\CraftingCostService;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;

class RecipeBook extends Component
{
    #[Url] public string $search = '';
    #[Url] public string $skill = '';
    #[Url] public string $members = '';
    #[Url] public string $minXp = '';
    #[Url] public string $maxXp = '';
    #[Url] public string $minProfit = '';
    #[Url] public string $maxProfit = '';
    public bool $profitableOnly = false;

    public function render(CraftingCostService $costs)
    {
        $recipes = Recipe::query()->with('outputItem', 'ingredients.item')->when($this->search, fn (Builder $query) => $query->whereHas('outputItem', fn (Builder $items) => $items->where('name', 'like', "%{$this->search}%")->orWhere('rs_id', $this->search)))->when($this->skill, fn (Builder $query) => $query->where('skill', $this->skill))->when($this->members !== '', fn (Builder $query) => $query->whereHas('outputItem', fn (Builder $items) => $items->where('members', $this->members === 'members')))->when($this->minXp !== '', fn (Builder $query) => $query->where('xp', '>=', $this->minXp))->when($this->maxXp !== '', fn (Builder $query) => $query->where('xp', '<=', $this->maxXp))->latest()->get();
        $cards = $recipes->map(fn (Recipe $recipe) => ['recipe' => $recipe, 'calculation' => $costs->calculate($recipe)])->filter(fn (array $card) => (! $this->profitableOnly || $card['calculation']['profit'] > 0) && ($this->minProfit === '' || $card['calculation']['profit'] >= (float) $this->minProfit) && ($this->maxProfit === '' || $card['calculation']['profit'] <= (float) $this->maxProfit));
        return view('livewire.recipe-book', ['cards' => $cards, 'skills' => Recipe::query()->distinct()->orderBy('skill')->pluck('skill')])
            ->layout('components.public-layout', ['title' => 'RS3 Crafter — Crafting profit']);
    }
}
