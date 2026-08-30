<?php

namespace App\Livewire\Admin;

use App\Models\Item;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class IngredientSelectModal extends Component
{
    public ?int $selectingFor = null;

    public bool $open = false;

    public string $search = '';

    public function mount(): void
    {
        $this->open = false;
    }

    #[\Livewire\Attributes\On('open-ingredient-modal')]
    public function openModal(int $selectingFor): void
    {
        $this->selectingFor = $selectingFor;
        $this->search = '';
        $this->open = true;
    }

    public function selectItem(int $itemId): void
    {
        $this->dispatch('ingredient-selected', selectingFor: $this->selectingFor, itemId: $itemId);
        $this->open = false;
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function render()
    {
        $items = Item::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('rs_id', $this->search))
            ->orderBy('name')
            ->limit(30)
            ->get();

        return view('livewire.admin.ingredient-select-modal', ['items' => $items]);
    }
}
