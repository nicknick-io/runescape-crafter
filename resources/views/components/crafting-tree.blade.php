@props(['node'])

<div class="border-l border-gray-300 pl-4">
    <div class="flex items-center gap-2 py-2">
        <img src="{{ $node['item']['image_url'] }}" alt="" class="size-7 object-contain">
        <span class="text-gray-900">{{ $node['item']['name'] }}</span>
        <span class="text-sm text-gray-600">× {{ number_format($node['quantity']) }}</span>
    </div>
    @foreach($node['children'] as $child)
        <x-crafting-tree :node="$child" />
    @endforeach
</div>
