{{--
    Empty State Component
    
    Menampilkan pesan ketika tidak ada data.
    
    @props
    - $title (string): Judul pesan
    - $message (string): Deskripsi pesan
    - $icon (string): SVG icon
    - $action (string): Action text jika ada
    - $wireClick (string): Wire click action
--}}

@props([
    'title' => 'Tidak ada data',
    'message' => 'Data yang Anda cari tidak ditemukan.',
    'icon' => null,
    'action' => null,
    'wireClick' => null,
])

<div class="text-center py-12">
    @if ($icon)
        <div class="w-24 h-24 mx-auto mb-4 text-gray-300 dark:text-gray-700">
            {!! $icon !!}
        </div>
    @else
        <div class="w-24 h-24 mx-auto mb-4 text-gray-300 dark:text-gray-700">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
            </svg>
        </div>
    @endif

    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">{{ $title }}</h3>
    <p class="text-gray-500 dark:text-gray-400 mb-6">{{ $message }}</p>

    @if ($action && $wireClick)
        <button 
            wire:click="{{ $wireClick }}"
            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors">
            {{ $action }}
        </button>
    @endif
</div>
