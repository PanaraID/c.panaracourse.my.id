# Testing Script untuk Fitur Tag

## Untuk menguji fitur tag yang telah dibuat:

### 1. Setup Test Data
Pastikan Anda memiliki:
- Minimal 2 user dalam sistem
- Chat dengan kedua user sebagai member
- Akses ke kedua akun untuk testing

### 2. Test Scenario 1: Mengirim Pesan dengan Tag

**Sebagai User A:**
1. Buka chat
2. Klik tombol "Tag Orang" (biru-ungu)
3. Pilih User B dari modal
4. Ketik pesan: "Halo @UserB, ini pesan test untuk fitur tag!"
5. Klik "Kirim"

**Expected Result:**
- Pesan terkirim dengan informasi tag di bawah konten pesan
- Terlihat: "Menandai: UserB" dengan badge biru

### 3. Test Scenario 2: Menerima Notifikasi Tag

**Sebagai User B:**
1. Reload halaman atau buka chat
2. Cek header chat

**Expected Result:**
- Badge merah dengan angka "1" muncul di header
- Badge berisi icon tag dengan counter

### 4. Test Scenario 3: Melihat Daftar Tag

**Sebagai User B:**
1. Klik badge notifikasi tag di header
2. Modal terbuka

**Expected Result:**
- Modal menampilkan daftar tag dari User A
- Berisi: nama pengirim, preview pesan, timestamp
- Ada tombol "Tandai Dibaca" dan "Lihat Pesan"

### 5. Test Scenario 4: Navigasi ke Pesan

**Sebagai User B:**
1. Di modal tag, klik "Lihat Pesan"

**Expected Result:**
- Modal tertutup
- Page scroll ke pesan yang dimaksud
- Pesan ter-highlight dengan efek animasi
- Highlight hilang setelah 3 detik

### 6. Test Scenario 5: Mark as Read

**Sebagai User B:**
1. Di modal tag, klik "Tandai Dibaca"

**Expected Result:**
- Badge counter berkurang
- Tag tidak muncul lagi di unread list
- Jika tidak ada tag lagi, badge hilang total

### 7. Test Scenario 6: Multiple Tags

**Sebagai User A:**
1. Tag multiple users dalam satu pesan
2. Pilih User B dan User C
3. Kirim pesan

**Expected Result:**
- Pesan menampilkan: "Menandai: UserB, UserC"
- Kedua user mendapat notifikasi terpisah

### 8. Test Scenario 7: Tag Diri Sendiri (Negative Test)

**Sebagai User A:**
1. Coba buka modal tag
2. Cek apakah nama sendiri muncul dalam list

**Expected Result:**
- Nama sendiri TIDAK muncul dalam daftar
- Hanya member lain yang bisa di-tag

### 9. Database Verification

Cek tabel `message_tags`:
```sql
SELECT 
    mt.id,
    m.content as message_content,
    tagged.name as tagged_user,
    tagger.name as tagged_by_user,
    mt.is_read,
    mt.created_at
FROM message_tags mt
JOIN messages m ON mt.message_id = m.id
JOIN users tagged ON mt.tagged_user_id = tagged.id
JOIN users tagger ON mt.tagged_by_user_id = tagger.id
ORDER BY mt.created_at DESC;
```

### 10. Performance Test

**Test dengan banyak tag:**
1. Buat pesan dengan 5+ tagged users
2. Cek loading time modal
3. Verify query performance

**Expected:**
- Modal load dalam <500ms
- Tidak ada N+1 query issues
- Smooth scroll animation

### Troubleshooting

**Jika badge tidak muncul:**
- Cek apakah user di-tag sudah refresh page
- Verify relasi model sudah benar
- Cek log Laravel untuk error

**Jika scroll to message tidak bekerja:**
- Verify data-message-id ada di DOM
- Cek JavaScript console untuk error
- Pastikan message container ID benar

**Jika tag tidak tersimpan:**
- Cek foreign key constraints
- Verify validation tidak block save
- Check Laravel log

### Features Checklist

✅ Tag button dengan counter  
✅ Modal pemilihan user  
✅ Preview tagged users  
✅ Save tags ke database  
✅ Badge notifikasi di header  
✅ Modal daftar tag received  
✅ Mark as read functionality  
✅ Scroll to message navigation  
✅ Highlight animation  
✅ Tampilan tag info di chat  
✅ Responsive design  
✅ Dark mode support  