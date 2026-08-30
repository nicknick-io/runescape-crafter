<div>
    @if($open)
        <!-- Modal Overlay -->
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" wire:click="close">
            <!-- Modal Content -->
            <div class="mx-4 w-full max-w-lg rounded-2xl bg-white shadow-xl" wire:click.stop>
                <div class="border-b border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900">Select ingredient</h2>
                </div>
                <div class="p-6">
                    <input wire:model.live="search" debounce="300ms" placeholder="Search items..." class="mb-4 w-full rounded-xl border-gray-300 bg-gray-50 px-3 py-2 text-gray-900">
                    <div class="max-h-96 space-y-2 overflow-y-auto">
                        @forelse($items as $item)
                            <button type="button" wire:click="selectItem({{ $item->id }})" class="flex w-full items-center gap-3 rounded-lg border border-gray-200 bg-white p-3 text-left transition hover:bg-amber-50">
                                <img src="{{ $item->image_url }}" alt="" class="size-10 object-contain">
                                <div class="flex-1 min-w-0">
                                    <p class="truncate font-medium text-gray-900">{{ $item->name }}</p>
                                    <p class="text-sm text-gray-600">RS #{{ $item->rs_id }} · {{ number_format($item->ge_price ?? 0) }} gp</p>
                                </div>
                            </button>
                        @empty
                            <p class="py-8 text-center text-gray-600">No items found</p>
                        @endforelse
                    </div>
                </div>
                <div class="border-t border-gray-200 p-4 text-right">
                    <button type="button" wire:click="close" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-900 transition hover:bg-gray-200">Close</button>
                </div>
            </div>
        </div>
    @endif
</div>
