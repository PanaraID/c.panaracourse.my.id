{{--
    Reusable Components Documentation
    
    File ini mendokumentasikan semua komponen reusable yang tersedia.
    Komponen-komponen ini dirancang untuk konsistensi dan kemudahan maintenance.
--}}

# Reusable Components Guide

## Dialog/Modal Component
**Path:** `components.modals.dialog`

Komponen modal yang dapat digunakan kembali untuk berbagai keperluan dialog/modal.

**Properties:**
- `$show` (boolean): Kontrol visibility modal - default: false
- `$title` (string): Judul modal
- `$size` (string): Ukuran modal (sm|md|lg|xl) - default: md
- `$closeAction` (string): Action untuk close modal
- `$submitAction` (string): Action untuk submit
- `$submitText` (string): Teks tombol submit - default: 'Simpan'
- `$submitColor` (string): Warna tombol submit (blue|green|red) - default: 'blue'
- `$closeText` (string): Teks tombol close - default: 'Batal'

**Usage:**
```blade
<x-modals.dialog 
    :show="$showModal"
    title="Buat Chat Baru"
    closeAction="$set('showModal', false)"
    submitAction="createChat"
    submitText="Buat Chat"
    submitColor="blue"
    size="md">
    <!-- Form content here -->
</x-modals.dialog>
```

---

## Form Input Field Component
**Path:** `components.forms.input-field`

Komponen input field dengan validasi otomatis dan dukungan berbagai tipe input.

**Properties:**
- `$label` (string): Label untuk input
- `$name` (string): Nama attribute form
- `$model` (string): Wire model untuk binding
- `$type` (string): Tipe input (text|email|password|textarea) - default: 'text'
- `$placeholder` (string): Placeholder text
- `$required` (boolean): Apakah field wajib diisi
- `$disabled` (boolean): Apakah field disabled
- `$rows` (integer): Jumlah baris (untuk textarea) - default: 3
- `$hint` (string): Hint text di bawah input

**Usage:**
```blade
<x-forms.input-field 
    label="Judul Chat"
    name="title"
    model="title"
    type="text"
    placeholder="Masukkan judul chat"
    required
/>

<x-forms.input-field 
    label="Deskripsi"
    name="description"
    model="description"
    type="textarea"
    rows="4"
    hint="Jelaskan tujuan chat ini"
/>
```

---

## Card Component
**Path:** `components.cards.card`

Komponen kartu dengan berbagai styling options.

**Properties:**
- `$title` (string): Judul kartu
- `$subtitle` (string): Subtitle kartu
- `$padding` (string): Padding - default: 'p-6'
- `$hasBorder` (boolean): Tambah border - default: true
- `$shadow` (string): Tipe shadow (none|sm|md|lg) - default: 'sm'
- `$hoverable` (boolean): Efek hover - default: false

**Usage:**
```blade
<x-cards.card title="Chat Title" subtitle="Created by John" hoverable shadow="md">
    <p>Card content here</p>
</x-cards.card>
```

---

## Button Component
**Path:** `components.buttons.button`

Komponen tombol dengan berbagai variant dan size.

**Properties:**
- `$variant` (string): Tipe button (primary|secondary|danger|success|ghost) - default: 'primary'
- `$size` (string): Ukuran (sm|md|lg) - default: 'md'
- `$disabled` (boolean): Apakah button disabled
- `$loading` (boolean): Tampilkan loading state
- `$icon` (string): Opsional icon SVG
- `$fullWidth` (boolean): Penuhi lebar container
- `$wireClick` (string): Wire click action

**Usage:**
```blade
<x-buttons.button 
    variant="primary" 
    size="md"
    wireClick="submitForm">
    <svg class="w-4 h-4 mr-2"><!-- icon --></svg>
    Submit
</x-buttons.button>

<x-buttons.button 
    variant="danger" 
    size="sm"
    wireClick="deleteItem">
    Hapus
</x-buttons.button>
```

---

## Empty State Component
**Path:** `components.states.empty-state`

Komponen untuk menampilkan pesan ketika tidak ada data.

**Properties:**
- `$title` (string): Judul pesan
- `$message` (string): Deskripsi pesan
- `$icon` (string): SVG icon (HTML)
- `$action` (string): Action text jika ada
- `$wireClick` (string): Wire click action

**Usage:**
```blade
<x-states.empty-state 
    title="Belum ada chat"
    message="Bergabunglah dengan chat yang sudah ada atau buat baru."
    icon='<svg><!-- icon --></svg>'
    action="Buat Chat"
    wireClick="$set('showCreateModal', true)"
/>
```

---

## Best Practices

1. **Konsistensi Styling:** Gunakan komponen yang sudah ada daripada membuat styling manual
2. **Props Documentation:** Selalu dokumentasikan props pada file komponen
3. **Dark Mode:** Semua komponen sudah mendukung dark mode dengan `dark:` classes
4. **Accessibility:** Gunakan semantic HTML dan proper aria labels
5. **Reusability:** Sebelum membuat komponen baru, periksa apakah sudah ada yang mirip

## Directory Structure

```
resources/views/
├── components/
│   ├── modals/
│   │   └── dialog.blade.php
│   ├── forms/
│   │   └── input-field.blade.php
│   ├── cards/
│   │   └── card.blade.php
│   ├── buttons/
│   │   └── button.blade.php
│   └── states/
│       └── empty-state.blade.php
├── livewire/
│   ├── auth/          (Authentication pages)
│   ├── chat/          (Chat feature pages)
│   ├── settings/      (User settings pages)
│   ├── notifications/ (Notification pages)
│   ├── pages/         (General pages)
│   └── components/    (Livewire sub-components)
└── ...
```
