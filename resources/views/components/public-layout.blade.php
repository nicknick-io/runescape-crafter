<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'RS3 Crafter' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased">
    <div class="border-b border-gray-200 bg-white"><div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4"><a wire:navigate href="{{ route('home') }}" class="text-xl font-bold tracking-tight text-amber-600">RS3 Crafter</a><div class="flex items-center gap-3">@auth<a wire:navigate href="{{ route('admin.recipes') }}" class="rounded-lg px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-100 hover:text-amber-600">Recipe builder</a>@endauth<span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs text-emerald-700">Local GE intelligence</span></div></div></div>
    <main class="mx-auto max-w-7xl px-5 py-8">{{ $slot }}</main>
    @livewireScripts
</body>
</html>
