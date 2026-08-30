<div>
    <a wire:navigate href="{{ route('home') }}" class="text-sm text-amber-600 hover:text-amber-700">← All recipes</a>
    <div class="mt-5 grid gap-6 lg:grid-cols-[1.2fr_.8fr]">
        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="flex items-center gap-5">
                <img src="{{ $recipe->outputItem->image_url }}" alt="" class="size-24 object-contain" />
                <div>
                    <p class="text-sm text-amber-600">{{ $recipe->skill }} · Level {{ $recipe->level_required }}</p>
                    <h1 class="text-3xl font-bold text-gray-900">{{ $recipe->outputItem->name }}</h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Produces {{ $recipe->output_quantity }} · {{ number_format((float) $recipe->xp, 1) }} XP
                    </p>
                </div>
            </div>
            <div class="mt-7 grid grid-cols-2 gap-3 sm:grid-cols-3">
                <x-stat-card label="GE output" :value="number_format($calculation['output_value']).' gp'" /><x-stat-card
                    label="Materials"
                    :value="number_format($calculation['material_cost']).' gp'"
                /><x-stat-card
                    label="Profit"
                    :value="number_format($calculation['profit']).' gp'"
                    :positive="$calculation['profit'] >= 0"
                /><x-stat-card
                    label="Margin"
                    :value="number_format($calculation['profit_margin'], 1).'%'"
                /><x-stat-card
                    label="GP / XP"
                    :value="$calculation['gp_per_xp'] === null ? '—' : number_format($calculation['gp_per_xp'], 1)"
                /><x-stat-card
                    label="Price updated"
                    :value="$recipe->outputItem->ge_updated_at?->diffForHumans() ?? 'Not synced'"
                />
            </div>
        </section>
        <aside class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="font-semibold text-gray-900">Materials</h2>
            <div class="mt-4 space-y-3">
                @foreach($directMaterials as $material)
                    <div class="flex items-center justify-between border-b border-gray-200 pb-3">
                        <div class="flex items-center gap-3">
                            <img src="{{ $material['item']['image_url'] }}" alt="" class="size-8 object-contain" /><span
                                class="text-gray-900"
                            >{{ $material['item']['name'] }}</span
                            >
                        </div>
                        <span class="text-sm text-gray-600"
                        ><strong>{{ number_format($material['quantity']) }}</strong> × <strong>{{ number_format($material['item']['ge_price'])
                        }} gp</strong></span
                        >
                    </div>
                @endforeach
            </div>
        </aside>
    </div>
    <section class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <button
            wire:click="$toggle('treeOpen')"
            class="flex w-full items-center justify-between text-left font-semibold text-gray-900"
        >
            Crafting tree <span class="text-amber-600">{{ $treeOpen ? '−' : '+' }}</span></button
        >@if($treeOpen)
            <div class="mt-5"><x-crafting-tree :node="$calculation['tree']" /></div>
        @endif
    </section>
</div>
