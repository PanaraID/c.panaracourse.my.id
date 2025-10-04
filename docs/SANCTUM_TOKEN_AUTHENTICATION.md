# Dokumentasi Token Sanctum untuk Autentikasi API

## Perubahan yang Telah Dilakukan

### 1. Login Component (`resources/views/livewire/auth/login.blade.php`)
- **Menyimpan token ke localStorage**: Setelah login berhasil, token Sanctum disimpan ke `localStorage` untuk digunakan oleh JavaScript
- **Menyimpan token ke cookie**: Token juga disimpan ke HTTP-only cookie untuk keamanan tambahan
- **Flash session**: Token diteruskan ke frontend melalui session flash

### 2. JavaScript Application (`resources/js/app.js`)
- **Dynamic token retrieval**: Fungsi `showNotification()` sekarang mengambil token dari `localStorage`
- **Error handling**: Menangani kasus ketika token tidak ditemukan
- **Improved logging**: Log yang lebih informatif untuk debugging

### 3. Logout Action (`app/Livewire/Actions/Logout.php`)
- **Token revocation**: Menghapus semua token user saat logout
- **Cookie cleanup**: Menghapus cookie sanctum_token
- **localStorage cleanup**: Flash session untuk membersihkan token dari localStorage

### 4. API Routes (`routes/api.php`)
- **Enhanced response**: Endpoint `/api/notifications` sekarang mengembalikan informasi user juga
- **Proper authentication**: Menggunakan middleware `auth:sanctum`

### 5. Auth Layout (`resources/views/components/layouts/auth/split.blade.php`)
- **Token cleanup script**: Script JavaScript untuk membersihkan token saat logout

## Cara Penggunaan

### Login
1. User melakukan login melalui form
2. Token Sanctum dibuat dan disimpan ke:
   - localStorage (untuk akses JavaScript)
   - HTTP-only cookie (untuk keamanan)
3. Token digunakan untuk API calls

### API Calls
```javascript
const userToken = localStorage.getItem('user_token');
fetch('/api/notifications', {
    headers: {
        'Authorization': 'Bearer ' + userToken
    }
})
```

### Logout
1. Semua token user dihapus dari database
2. Cookie sanctum_token dihapus
3. localStorage token dihapus via JavaScript

## Testing
- Halaman test tersedia di: `/token-test.html`
- Test endpoint API: `/api/notifications`
- Periksa localStorage di Developer Tools

## Security Notes
- Token disimpan di localStorage untuk kemudahan akses JavaScript
- Token juga disimpan di HTTP-only cookie untuk keamanan tambahan
- Token memiliki ekspirasi 30 hari
- Semua token dihapus saat logout