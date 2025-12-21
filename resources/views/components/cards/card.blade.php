{{--
    Card Component
    
    Komponen kartu yang dapat digunakan kembali dengan berbagai styling options.
    
    @props
    - $title (string): Judul kartu
    - $subtitle (string): Subtitle kartu
    - $padding (string): Padding - default: 'p-6'
    - $hasBorder (boolean): Tambah border - default: true
    - $shadow (string): Tipe shadow - default: 'sm'
    - $hoverable (boolean): Efek hover - default: false
--}}

@props([
    'title' => '',
    'subtitle' => '',
    'padding' => 'p-6',
    'hasBorder' => true,
    'shadow' => 'sm',
    'hoverable' => false,
])

@php
$shadowClasses = [
    'none' => '',
    'sm' => 'shadow-sm',
    'md' => 'shadow',
    'lg' => 'shadow-lg',
];
@endphp

<div class="bg-white dark:bg-gray-800 rounded-lg {{ $hasBorder ? 'border border-gray-200 dark:border-gray-700' : '' }} {{ $shadowClasses[$shadow] ?? $shadowClasses['sm'] }} {{ $hoverable ? 'hover:shadow-md transition-shadow' : '' }} transition-colors">
    @if ($title)
        <div class="{{ $padding }}">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $title }}</h3>
            @if ($subtitle)
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $subtitle }}</p>
            @endif
        </div>
        <div class="border-t border-gray-200 dark:border-gray-700"></div>
    @endif

    <div class="{{ $title ? $padding : $padding }}">
        {{ $slot }}
    </div>
</div>
