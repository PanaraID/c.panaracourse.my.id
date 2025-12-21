{{--
    Dialog/Modal Component
    
    Komponen modal yang dapat digunakan kembali untuk berbagai keperluan.
    Mendukung backdrop, sizing, dan custom slots.
    
    @props
    - $show (boolean): Kontrol visibility modal
    - $title (string): Judul modal
    - $size (string): Ukuran modal (sm|md|lg|xl) - default: md
    - $closeAction (string): Action untuk close modal
    - $submitAction (string): Action untuk submit
    - $submitText (string): Teks tombol submit - default: 'Simpan'
    - $submitColor (string): Warna tombol submit - default: 'blue'
    - $closeText (string): Teks tombol close - default: 'Batal'
--}}

@props([
    'show' => false,
    'title' => '',
    'size' => 'md',
    'closeAction' => null,
    'submitAction' => null,
    'submitText' => 'Simpan',
    'submitColor' => 'blue',
    'closeText' => 'Batal',
])

@php
$sizeClasses = [
    'sm' => 'max-w-sm',
    'md' => 'max-w-md',
    'lg' => 'max-w-lg',
    'xl' => 'max-w-xl',
];

$bgColorClasses = [
    'blue' => 'bg-blue-600 hover:bg-blue-700',
    'green' => 'bg-green-600 hover:bg-green-700',
    'red' => 'bg-red-600 hover:bg-red-700',
];
@endphp

@if ($show)
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-80 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full {{ $sizeClasses[$size] ?? $sizeClasses['md'] }} shadow-xl">
            {{-- Header --}}
            @if ($title)
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $title }}</h3>
                    @if ($closeAction)
                        <button 
                            wire:click="{{ $closeAction }}"
                            class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    @endif
                </div>
            @endif

            {{-- Content --}}
            <div class="mb-6">
                {{ $slot }}
            </div>

            {{-- Footer --}}
            <div class="flex justify-end space-x-3">
                @if ($closeAction)
                    <button 
                        type="button" 
                        wire:click="{{ $closeAction }}"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-md transition-colors">
                        {{ $closeText }}
                    </button>
                @endif

                @if ($submitAction)
                    <button 
                        type="submit"
                        wire:click="{{ $submitAction }}"
                        class="px-4 py-2 text-sm font-medium text-white {{ $bgColorClasses[$submitColor] ?? $bgColorClasses['blue'] }} rounded-md transition-colors">
                        {{ $submitText }}
                    </button>
                @endif
            </div>
        </div>
    </div>
@endif
