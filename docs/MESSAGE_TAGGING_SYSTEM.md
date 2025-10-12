unculkan # Sistem Tag Peserta di Chat

## Overview
Fitur ini memungkinkan pengguna untuk menandai (tag) peserta lain dalam pesan chat. Ketika seseorang di-tag, mereka akan mendapat notifikasi dan dapat melihat pesan tersebut dengan mudah.

## Struktur Database

### Tabel `message_tags`
```sql
- id (primary key)
- message_id (foreign key ke messages)
- tagged_user_id (foreign key ke users - user yang di-tag)
- tagged_by_user_id (foreign key ke users - user yang menandai)
- is_read (boolean - status apakah tag sudah dibaca)
- created_at, updated_at (timestamps)
```

## Model Relations

### MessageTag Model
- `message()` - belongs to Message
- `taggedUser()` - belongs to User (yang di-tag)
- `taggedByUser()` - belongs to User (yang menandai)

### Message Model
- `tags()` - has many MessageTag
- `taggedUsers()` - belongs to many User (through message_tags pivot)

### User Model
- `messageTags()` - has many MessageTag (sebagai tagged user)
- `unreadMessageTagsCount()` - method untuk hitung tag belum dibaca
- `recentUnreadMessageTags()` - method untuk ambil tag terbaru belum dibaca

## Fitur UI

### 1. Form Input Pesan (send_message.blade.php)
- **Tombol "Tag Orang"**: Membuka modal untuk memilih peserta yang akan di-tag
- **Modal Pemilihan**: Menampilkan daftar peserta chat dengan checkbox
- **Preview Tag**: Menampilkan peserta yang dipilih dengan tombol hapus
- **Integrasi dengan Kirim Pesan**: Tag otomatis tersimpan saat pesan dikirim

### 2. Header Chat (header.blade.php)
- **Notifikasi Badge**: Menampilkan jumlah tag belum dibaca (hanya muncul jika ada)
- **Modal Daftar Tag**: Menampilkan semua tag yang diterima user di chat tersebut
- **Aksi Cepat**: 
  - "Tandai Dibaca" - mark tag as read
  - "Lihat Pesan" - scroll ke pesan yang dimaksud dengan highlight

## Cara Penggunaan

### Menandai Peserta:
1. Klik tombol "Tag Orang" di atas form input pesan
2. Pilih peserta yang ingin di-tag dari modal
3. Ketik pesan seperti biasa
4. Klik "Kirim" - tag otomatis tersimpan

### Melihat Tag yang Diterima:
1. Jika ada tag baru, badge dengan angka akan muncul di header
2. Klik badge untuk membuka modal daftar tag
3. Klik "Lihat Pesan" untuk navigasi ke pesan yang dimaksud
4. Klik "Tandai Dibaca" untuk menandai tag sebagai sudah dibaca

## Fitur Keamanan
- Hanya peserta chat yang bisa di-tag
- User tidak bisa tag diri sendiri
- Validasi foreign key constraint di database level
- Unique constraint untuk mencegah duplicate tag di pesan yang sama

## Performance Optimizations
- Index pada kolom yang sering di-query (tagged_user_id, is_read)
- Eager loading untuk relasi (message.chat, taggedByUser)
- Limit query untuk recent tags
- Caching property untuk unread tags count

## JavaScript Integration
- `scrollToMessage(messageId)` - function untuk scroll ke pesan tertentu
- Highlight animation untuk pesan yang di-navigate
- Auto-hide highlight setelah 3 detik

## CSS Animations
- Pulse animation untuk badge notifikasi
- Hover effects untuk tombol dan modal items
- Smooth scroll dan highlight transitions
- Responsive design untuk mobile

## API Endpoints
Fitur ini menggunakan Livewire component, jadi tidak ada REST API endpoints terpisah. Semua interaksi melalui:
- `openTagModal()` - buka modal tag
- `toggleTagUser(userId)` - toggle selection user
- `sendMessage()` - kirim pesan dengan tags
- `markTagAsRead(tagId)` - mark tag sebagai dibaca

## Testing
Untuk test fitur ini:
1. Buat beberapa user dan tambahkan ke chat
2. Login dengan user pertama, tag user lain dalam pesan
3. Login dengan user yang di-tag, cek notifikasi di header
4. Test navigasi ke pesan dan mark as read

## Future Enhancements
- Push notifications untuk tag baru
- Email notifications
- Tag multiple users dengan @mention syntax
- Search dalam tagged messages
- Tag analytics dan reporting