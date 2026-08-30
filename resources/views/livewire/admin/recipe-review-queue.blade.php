<div>
    <div class="max-w-6xl">
        <h1 class="text-3xl font-bold text-gray-900">Recipe Review Queue</h1>
        <p class="mt-2 text-gray-600">Review and approve AI-generated recipes before they go live.</p>

        @if(session('status'))
            <p class="mt-4 rounded-xl bg-emerald-50 p-3 text-emerald-700">{{ session('status') }}</p>
        @endif
        @if(session('error'))
            <p class="mt-4 rounded-xl bg-rose-50 p-3 text-rose-700">{{ session('error') }}</p>
        @endif

        @if($recipes->count() > 0)
            <div class="mt-6 flex gap-2">
                @if(!empty($selectedRecipes))
                    <button 
                        wire:click="approveSelected" 
                        class="rounded-lg bg-emerald-600 px-4 py-2 font-medium text-white hover:bg-emerald-700"
                    >
                        ✓ Approve {{ count($selectedRecipes) }} selected
                    </button>
                    <button 
                        wire:click="deleteSelected" 
                        class="rounded-lg bg-rose-600 px-4 py-2 font-medium text-white hover:bg-rose-700"
                    >
                        🗑 Delete {{ count($selectedRecipes) }} selected
                    </button>
                @endif
            </div>

            <div class="mt-6 space-y-4">
                @foreach($recipes as $recipe)
                    <div class="rounded-2xl border border-gray-200 bg-white p-6">
                        <div class="flex items-start gap-4">
                            <!-- Checkbox -->
                            <input 
                                type="checkbox" 
                                wire:model="selectedRecipes" 
                                value="{{ $recipe->id }}"
                                class="mt-2"
                            />

                            <!-- Recipe Info -->
                            <div class="flex-1">
                                <div class="flex items-center gap-3">
                                    @if($recipe->outputItem)
                                        <img src="{{ $recipe->outputItem->image_url }}" alt="" class="size-12 object-contain">
                                    @endif
                                    <div>
                                        <h3 class="font-semibold text-gray-900">
                                            {{ $recipe->outputItem?->name ?? 'Unknown Item' }}
                                        </h3>
                                        <p class="text-sm text-gray-600">
                                            {{ $recipe->skill }} • Level {{ $recipe->level_required }} • {{ $recipe->xp }} XP
                                        </p>
                                    </div>
                                </div>

                                <!-- Ingredients -->
                                @if($recipe->ingredients->count() > 0)
                                    <div class="mt-4">
                                        <p class="text-xs font-medium uppercase text-gray-500">Ingredients</p>
                                        <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                            @foreach($recipe->ingredients as $ingredient)
                                                <div class="flex items-center gap-2 rounded-lg bg-gray-50 p-2">
                                                    @if($ingredient->item)
                                                        <img src="{{ $ingredient->item->image_url }}" alt="" class="size-6 object-contain">
                                                        <span class="text-sm text-gray-700">
                                                            <span class="font-medium">{{ $ingredient->item->name }}</span>
                                                            <span class="text-gray-500">×{{ $ingredient->quantity }}</span>
                                                        </span>
                                                    @else
                                                        <span class="text-sm text-gray-500">Unknown item</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-2">
                                <a 
                                    href="/admin/recipes/{{ $recipe->id }}/edit"
                                    class="rounded-lg bg-blue-100 px-3 py-2 text-sm font-medium text-blue-700 hover:bg-blue-200"
                                >
                                    ✏ Edit
                                </a>
                                <button 
                                    wire:click="approveRecipe({{ $recipe->id }})" 
                                    class="rounded-lg bg-emerald-100 px-3 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-200"
                                >
                                    ✓ Approve
                                </button>
                                <button 
                                    wire:click="rejectRecipe({{ $recipe->id }})" 
                                    class="rounded-lg bg-rose-100 px-3 py-2 text-sm font-medium text-rose-700 hover:bg-rose-200"
                                    onclick="return confirm('Delete this recipe?')"
                                >
                                    🗑 Reject
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $recipes->links() }}
            </div>
        @else
            <div class="mt-8 rounded-2xl border-2 border-dashed border-gray-300 bg-gray-50 p-12 text-center">
                <p class="text-gray-600">✨ No recipes pending review!</p>
                <p class="mt-2 text-sm text-gray-500">Generate recipes from the wiki and they'll appear here for approval.</p>
            </div>
        @endif
    </div>
</div>
