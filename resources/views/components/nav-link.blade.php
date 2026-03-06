@props(['active' => false, 'icon' => null])

@php
$classes = ($active ?? false)
            ? 'flex items-center gap-3 px-4 py-3 bg-indigo-800 text-white rounded-lg transition-colors'
            : 'flex items-center gap-3 px-4 py-3 text-indigo-100 hover:bg-indigo-800/50 hover:text-white rounded-lg transition-colors';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    @if($icon)
        {{-- You can use Heroicons or any SVG library here --}}
        <x-dynamic-component :component="'heroicon-o-' . $icon" class="w-5 h-5" />
    @endif
    
    <span class="font-medium">{{ $slot }}</span>
</a>