@props(['title' => null, 'class' => ''])

<section {{ $attributes->merge(['class' => "rounded-2xl border border-gray-200 bg-white p-6 shadow-sm {$class}"]) }}>
    @if($title)
        <h2 class="font-semibold text-gray-900">{{ $title }}</h2>
        <div class="mt-5">{{ $slot }}</div>
    @else
        {{ $slot }}
    @endif
</section>
