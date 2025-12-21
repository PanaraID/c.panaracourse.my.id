{{--
    Form Input Field Component
    
    Komponen input field yang dapat digunakan kembali dengan validasi otomatis
    dan dukungan untuk berbagai tipe input.
    
    @props
    - $label (string): Label untuk input
    - $name (string): Nama attribute form
    - $model (string): Wire model untuk binding
    - $type (string): Tipe input - default: 'text'
    - $placeholder (string): Placeholder text
    - $required (boolean): Apakah field wajib diisi
    - $disabled (boolean): Apakah field disabled
    - $rows (integer): Jumlah baris (untuk textarea) - default: 3
    - $hint (string): Hint text di bawah input
--}}

@props([
    'label' => '',
    'name' => '',
    'model' => '',
    'type' => 'text',
    'placeholder' => '',
    'required' => false,
    'disabled' => false,
    'rows' => 3,
    'hint' => '',
])

<div>
    @if ($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
            {{ $label }}
            @if ($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    @if ($type === 'textarea')
        <textarea 
            @if ($model) wire:model="{{ $model }}" @endif
            id="{{ $name }}"
            name="{{ $name }}"
            rows="{{ $rows }}"
            placeholder="{{ $placeholder }}"
            @if ($disabled) disabled @endif
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-900 dark:text-gray-100 transition-colors
            @error($name) border-red-500 @enderror">
        </textarea>
    @else
        <input 
            type="{{ $type }}"
            @if ($model) wire:model="{{ $model }}" @endif
            id="{{ $name }}"
            name="{{ $name }}"
            placeholder="{{ $placeholder }}"
            @if ($disabled) disabled @endif
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-900 dark:text-gray-100 transition-colors
            @error($name) border-red-500 @enderror">
    @endif

    @if ($hint)
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="text-red-500 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>
