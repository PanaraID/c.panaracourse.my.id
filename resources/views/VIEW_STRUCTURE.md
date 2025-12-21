{{--
    View Structure Documentation
    
    Dokumentasi lengkap struktur view yang telah dirapihkan dan dimodularkan.
    Setiap view memiliki deskripsi jelas tentang fungsinya.
--}}

# View Structure Documentation

## Ringkasan Refactoring

Seluruh views telah dirapihkan menjadi struktur yang lebih modular dengan penambahan dokumentasi di setiap file. 

### Komponen Reusable yang Dibuat

1. **Dialog/Modal** (`x-modals.dialog`)
   - Untuk dialog dan modal yang dapat dikonfigurasi
   - Mendukung berbagai ukuran dan action

2. **Form Input** (`x-forms.input-field`)
   - Input field dengan validasi otomatis
   - Support text, email, password, textarea

3. **Card** (`x-cards.card`)
   - Card component dengan optional border dan shadow
   - Mendukung title, subtitle, dan hoverable effect

4. **Button** (`x-buttons.button`)
   - Button component dengan berbagai variant
   - Support loading state dan icon

5. **Empty State** (`x-states.empty-state`)
   - Tampilan ketika tidak ada data
   - Customizable title, message, dan icon

---

## Directory Structure

```
resources/views/
├── components/
│   ├── modals/
│   │   └── dialog.blade.php              ✨ Komponen baru
│   ├── forms/
│   │   └── input-field.blade.php         ✨ Komponen baru
│   ├── cards/
│   │   └── card.blade.php                ✨ Komponen baru
│   ├── buttons/
│   │   └── button.blade.php              ✨ Komponen baru
│   ├── states/
│   │   └── empty-state.blade.php         ✨ Komponen baru
│   ├── layouts/                          📁 Existing layouts
│   └── settings/                         📁 Existing settings
│
├── livewire/
│   ├── auth/                             📁 Authentication pages (dengan dokumentasi ✅)
│   │   ├── login.blade.php               📝 Login - Autentikasi dengan email & password
│   │   ├── register.blade.php            📝 Register - Registrasi user baru
│   │   ├── forgot-password.blade.php     📝 Forgot Password - Reset password request
│   │   ├── reset-password.blade.php      📝 Reset Password - Confirm new password
│   │   ├── verify-email.blade.php        📝 Verify Email - Email verification
│   │   ├── confirm-password.blade.php    📝 Confirm Password - Konfirmasi untuk area sensitif
│   │   └── two-factor-challenge.blade.php 📝 2FA Challenge - TOTP/Recovery codes
│   │
│   ├── chat/                             📁 Chat feature pages (dengan dokumentasi ✅)
│   │   ├── index.blade.php               📝 Chat Index - Daftar semua chat rooms (refactored ✨)
│   │   ├── show.blade.php                📝 Chat Show - Detail chat & pesan-pesan (documented ✅)
│   │   ├── manage.blade.php              📝 Chat Manage - Kelola anggota chat (documented ✅)
│   │   └── _components/show/             📁 Sub-komponen chat
│   │       ├── header.blade.php
│   │       ├── messages.blade.php
│   │       ├── empty_chat.blade.php
│   │       ├── send_message.blade.php
│   │       └── partials/
│   │
│   ├── settings/                         📁 User settings pages (dengan dokumentasi ✅)
│   │   ├── profile.blade.php             📝 Profile - Update nama & email (documented ✅)
│   │   ├── password.blade.php            📝 Password - Ubah password (documented ✅)
│   │   ├── appearance.blade.php          📝 Appearance - Light/Dark/System mode (documented ✅)
│   │   ├── two-factor.blade.php          📝 2FA Setup - Enable/Disable 2FA (documented ✅)
│   │   ├── delete-user-form.blade.php    📝 Delete Account - Permanent deletion (documented ✅)
│   │   └── two-factor/
│   │       └── recovery-codes.blade.php
│   │
│   ├── notifications/                    📁 Notification pages (dengan dokumentasi ✅)
│   │   ├── index.blade.php               📝 Notifications - Semua notifikasi dengan pagination (documented ✅)
│   │   ├── dropdown.blade.php            📝 Notification Dropdown - 10 notifikasi terbaru (documented ✅)
│   │   └── ...
│   │
│   ├── pages/                            📁 General pages (dengan dokumentasi ✅)
│   │   ├── home.blade.php                📝 Home - Welcome page (documented ✅)
│   │   └── dashboard.blade.php           📝 Dashboard - Main dashboard (documented ✅)
│   │
│   └── components/                       📁 Livewire components
│       └── ...
│
└── ...
```

---

## Dokumentasi Setiap View

### Authentication Views (✅ Documented)

| File | Deskripsi |
|------|-----------|
| `login.blade.php` | Autentikasi user dengan email dan password, mendukung rate limiting |
| `register.blade.php` | Registrasi user baru dengan validasi email dan password |
| `forgot-password.blade.php` | Request reset password link via email |
| `reset-password.blade.php` | Reset password dengan token dari email (berlaku 60 menit) |
| `verify-email.blade.php` | Verifikasi email setelah registrasi |
| `confirm-password.blade.php` | Konfirmasi password untuk akses area sensitif |
| `two-factor-challenge.blade.php` | Verifikasi 2FA dengan TOTP atau recovery codes |

### Chat Views (✅ Documented)

| File | Deskripsi |
|------|-----------|
| `index.blade.php` | Daftar chat rooms dengan filtering (admin vs member), create, join, delete |
| `show.blade.php` | Detail chat dengan messages, header, dan input pesan |
| `manage.blade.php` | Kelola anggota chat, tambah/hapus member, edit info chat |

### Settings Views (✅ Documented)

| File | Deskripsi |
|------|-----------|
| `profile.blade.php` | Update profil (nama & email) dengan validasi unik |
| `password.blade.php` | Ubah password dengan validasi password lama |
| `appearance.blade.php` | Atur tampilan (Light/Dark/System mode) |
| `two-factor.blade.php` | Setup & manage 2FA dengan TOTP |
| `delete-user-form.blade.php` | Delete akun secara permanen dengan konfirmasi password |

### Pages Views (✅ Documented)

| File | Deskripsi |
|------|-----------|
| `home.blade.php` | Welcome page untuk authenticated users |
| `dashboard.blade.php` | Main dashboard dengan overview fitur |

### Notification Views (✅ Documented)

| File | Deskripsi |
|------|-----------|
| `index.blade.php` | Semua notifikasi dengan pagination (20 per halaman) |
| `dropdown.blade.php` | Dropdown notifikasi terbaru (10 notifikasi) |

---

## Fitur Refactoring

### ✨ Komponen Reusable
- Dialog/Modal yang flexible dan configurable
- Form input field dengan validasi terintegrasi
- Card component untuk layout fleksibel
- Button component dengan berbagai variant
- Empty state component untuk no-data scenario

### 📝 Dokumentasi
- Setiap file view memiliki deskripsi di atas (Blade comment)
- Setiap method memiliki dokumentasi PHPDoc
- File COMPONENTS.md untuk panduan komponen
- File ini untuk struktur keseluruhan

### 🎨 Styling
- Konsisten menggunakan Tailwind CSS v4
- Mendukung dark mode dengan `dark:` prefix
- Mobile responsive dengan breakpoint yang tepat

### 🔒 Security & Best Practices
- Validasi authorization di setiap page (mount)
- Logging untuk audit trail
- Wire model binding untuk reactive updates
- Proper error handling dan validation

---

## Cara Menggunakan Komponen

### 1. Dialog/Modal
```blade
<x-modals.dialog 
    :show="$showModal"
    title="Buat Chat Baru"
    closeAction="$set('showModal', false)"
    submitAction="createChat"
    submitColor="blue"
    size="md">
    <x-forms.input-field label="Judul" name="title" model="title" required />
</x-modals.dialog>
```

### 2. Card
```blade
<x-cards.card title="Chat Title" hoverable>
    <p>Konten kartu</p>
</x-cards.card>
```

### 3. Button
```blade
<x-buttons.button variant="primary" wireClick="save">
    Simpan
</x-buttons.button>
```

### 4. Empty State
```blade
<x-states.empty-state 
    title="Tidak ada chat"
    message="Bergabunglah atau buat chat baru"
/>
```

---

## Testing

Semua file views telah diperiksa syntax-nya dengan `php -l` dan tidak ada error.

```bash
# Clear view cache
php artisan view:clear

# Syntax check (sudah dilakukan)
php -l resources/views/livewire/**/*.blade.php
```

---

## Catatan Penting

1. **Backward Compatibility:** Semua perubahan kompatibel dengan kode yang ada
2. **No Breaking Changes:** Tidak ada fitur yang dihapus atau diubah behavior-nya
3. **Easy Maintenance:** Struktur yang modular memudahkan maintenance dan penambahan fitur
4. **Reusability:** Komponen dapat digunakan di berbagai tempat untuk consistency

---

## Next Steps (Opsional)

1. Extract sub-components untuk messages, header, send-message ke komponen terpisah
2. Buat reusable form component untuk complex forms
3. Buat loading skeleton component
4. Buat alert/toast component untuk feedback
5. Extract repeated styles ke utility classes custom

