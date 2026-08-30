<?php

namespace App\Livewire\Admin;

use App\Models\Recipe;
use Livewire\Component;
use Livewire\WithPagination;

class RecipeReviewQueue extends Component
{
    use WithPagination;

    public array $selectedRecipes = [];
    public bool $showDeleteConfirm = false;
    public ?int $deleteRecipeId = null;

    public function rejectRecipe(int $recipeId): void
    {
        Recipe::find($recipeId)?->delete();
        session()->flash('status', 'Recipe rejected and deleted.');
    }

    public function approveRecipe(int $recipeId): void
    {
        Recipe::find($recipeId)?->update(['needs_review' => false]);
        session()->flash('status', 'Recipe approved.');
    }

    public function approveSelected(): void
    {
        if (empty($this->selectedRecipes)) {
            session()->flash('error', 'No recipes selected.');
            return;
        }

        Recipe::whereIn('id', $this->selectedRecipes)->update(['needs_review' => false]);
        $this->selectedRecipes = [];
        session()->flash('status', 'Selected recipes approved.');
    }

    public function deleteSelected(): void
    {
        if (empty($this->selectedRecipes)) {
            session()->flash('error', 'No recipes selected.');
            return;
        }

        Recipe::whereIn('id', $this->selectedRecipes)->delete();
        $this->selectedRecipes = [];
        session()->flash('status', 'Selected recipes deleted.');
    }

    public function render()
    {
        $recipes = Recipe::where('needs_review', true)
            ->with('outputItem', 'ingredients.item')
            ->paginate(10);

        return view('livewire.admin.recipe-review-queue', [
            'recipes' => $recipes,
        ])->layout('components.public-layout', ['title' => 'Recipe Review Queue']);
    }
}
