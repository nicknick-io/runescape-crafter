@props(['label', 'value', 'positive' => null])
<div class="rounded-xl bg-gray-100 p-3"><p class="text-xs text-gray-600">{{ $label }}</p><p @class(['mt-1 font-semibold text-gray-900', 'text-emerald-600' => $positive === true, 'text-rose-600' => $positive === false])>{{ $value }}</p></div>
