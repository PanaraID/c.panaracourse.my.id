{{--
    Button Component
    
    Komponen tombol yang dapat digunakan kembali dengan berbagai styling options.
    
    @props
    - $variant (string): Tipe button - default: 'primary' (primary|secondary|danger|success)
    - $size (string): Ukuran - default: 'md' (sm|md|lg)
    - $disabled (boolean): Apakah button disabled
    - $loading (boolean): Tampilkan loading state
    - $icon (string): Opsional icon SVG
    - $fullWidth (boolean): Penuhi lebar container
    - $wireClick (string): Wire click action
--}}

@props([
    'variant' => 'primary',
    'size' => 'md',
    'disabled' => false,
    'loading' => false,
    'icon' => null,
    'fullWidth' => false,
    'wireClick' => null,
])

@php
$variantClasses = [
    'primary' => 'bg-blue-600 hover:bg-blue-700 text-white',
    'secondary' => 'bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-gray-100',
    'danger' => 'bg-red-600 hover:bg-red-700 text-white',
    'success' => 'bg-green-600 hover:bg-green-700 text-white',
    'ghost' => 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
];

$sizeClasses = [
    'sm' => 'px-3 py-1.5 text-sm',
    'md' => 'px-4 py-2 text-sm',
    'lg' => 'px-6 py-3 text-base',
];
@endphp

<button 
    type="button"
    @if ($wireClick) wire:click="{{ $wireClick }}" @endif
    @if ($disabled || $loading) disabled @endif
    class="inline-flex items-center justify-center font-medium rounded-md transition-colors
    {{ $variantClasses[$variant] ?? $variantClasses['primary'] }}
    {{ $sizeClasses[$size] ?? $sizeClasses['md'] }}
    {{ $fullWidth ? 'w-full' : '' }}
    {{ ($disabled || $loading) ? 'opacity-50 cursor-not-allowed' : '' }}">
    
    @if ($loading)
        <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    @elseif ($icon)
        <span class="mr-2">{{ $icon }}</span>
    @endif

    {{ $slot }}
</button>
