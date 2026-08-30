<div>
    <div class="max-w-4xl"><h1 class="text-3xl font-bold text-gray-900">Recipe builder</h1><p class="mt-2 text-gray-600">Build a safe crafting dependency graph from locally imported items.</p>@if(session('status'))<p class="mt-4 rounded-xl bg-emerald-50 p-3 text-emerald-700">{{ session('status') }}</p>@endif

    <form wire:submit="save" class="mt-8 space-y-8">
        <!-- Recipe Info Section -->
        <div class="rounded-2xl border border-gray-200 bg-white p-6">
            <h2 class="mb-4 font-semibold text-gray-900">Recipe details</h2>
            <div class="grid gap-4 sm:grid-cols-4">
                <label class="block"><span class="text-sm font-medium text-gray-700">Skill</span><input wire:model="skill" class="mt-1 w-full rounded-xl border-gray-300 bg-gray-50 px-3 py-2 text-gray-900"></label>
                <label class="block"><span class="text-sm font-medium text-gray-700">Output qty</span><input wire:model="outputQuantity" type="number" min="1" class="mt-1 w-full rounded-xl border-gray-300 bg-gray-50 px-3 py-2 text-gray-900"></label>
                <label class="block"><span class="text-sm font-medium text-gray-700">Level</span><input wire:model="levelRequired" type="number" min="1" class="mt-1 w-full rounded-xl border-gray-300 bg-gray-50 px-3 py-2 text-gray-900"></label>
                <label class="block"><span class="text-sm font-medium text-gray-700">XP</span><input wire:model="xp" type="number" min="0" step=".01" class="mt-1 w-full rounded-xl border-gray-300 bg-gray-50 px-3 py-2 text-gray-900"></label>
            </div>
        </div>

        <!-- Visual Tree Section -->
        <div class="rounded-2xl border border-gray-200 bg-white p-6">
            <h2 class="mb-6 font-semibold text-gray-900">Recipe tree</h2>
            
            <!-- Input/Output Tree -->
            <div class="space-y-8">
                <!-- Output -->
                <div class="flex flex-col items-center">
                    @if($outputItem)
                        <button type="button" wire:click="$set('outputItemId', null)" class="group flex flex-col items-center gap-2 rounded-xl border-2 border-amber-500 bg-amber-50 p-4 transition hover:bg-amber-100">
                            <img src="{{ $outputItem->image_url }}" alt="" class="size-20 object-contain">
                            <span class="font-semibold text-gray-900">{{ $outputItem->name }}</span>
                            <span class="text-xs text-amber-600">OUTPUT (Click to change)</span>
                        </button>
                    @else
                        <button type="button" wire:click="openOutputItemModal" class="flex flex-col items-center gap-2 rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 p-8 text-center transition hover:border-amber-500 hover:bg-amber-50">
                            <span class="text-sm font-medium text-gray-600">Click to select output item</span>
                        </button>
                    @endif
                </div>

                <!-- Lines to Inputs -->
                @if($ingredients && count($ingredients) > 0)
                    <div class="flex justify-center">
                        <div class="h-8 w-1 bg-gray-300"></div>
                    </div>

                    <!-- Inputs Grid -->
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-{{ min(count($ingredients), 4) }}">
                        @foreach($ingredients as $index => $ingredient)
                            <div class="flex flex-col items-center gap-3">
                                @if(isset($ingredientItems[$ingredient['item_id']]) && $ingredientItems[$ingredient['item_id']])
                                    @php $item = $ingredientItems[$ingredient['item_id']]; @endphp
                                    <div class="flex w-full flex-col items-center gap-2 rounded-xl border border-gray-300 bg-gray-50 p-3">
                                        <img src="{{ $item->image_url }}" alt="" class="size-16 object-contain">
                                        <div class="text-center">
                                            <p class="truncate text-sm font-medium text-gray-900">{{ $item->name }}</p>
                                            <input wire:change="updateQuantity({{ $index }}, $event.target.value)" value="{{ $ingredient['quantity'] }}" type="number" min="0.001" step="0.001" class="mt-1 w-20 rounded-lg border-gray-300 bg-white px-2 py-1 text-center text-sm text-gray-900">
                                        </div>
                                        <div class="flex gap-2">
                                            <button type="button" wire:click="openIngredientModal({{ $index }})" class="text-xs text-amber-600 hover:text-amber-700">Change</button>
                                            <button type="button" wire:click="removeIngredient({{ $index }})" class="text-xs text-rose-600 hover:text-rose-700">Remove</button>
                                        </div>
                                    </div>
                                @else
                                    <button type="button" wire:click="openIngredientModal({{ $index }})" class="flex h-40 w-full flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 transition hover:border-amber-500 hover:bg-amber-50">
                                        <span class="text-sm font-medium text-gray-600">Select ingredient</span>
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                <!-- Add Ingredient Button -->
                <div class="flex justify-center">
                    <button type="button" wire:click="addIngredient" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">+ Add ingredient</button>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex justify-end">
            <button type="submit" class="rounded-xl bg-amber-600 px-6 py-2 font-semibold text-white transition hover:bg-amber-700">Save recipe</button>
        </div>
    </form>

    <!-- Modal -->
    <livewire:admin.ingredient-select-modal :index="$editingIngredientIndex" />
    </div>
</div>
