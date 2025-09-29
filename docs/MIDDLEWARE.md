# Middleware Documentation

## Overview
Sistem aplikasi chat menggunakan beberapa custom middleware untuk security, access control, logging, dan rate limiting.

## Registered Middleware

### 1. RoleMiddleware (`role`)
**Purpose:** Mengontrol akses berdasarkan role user (admin/member)  
**Usage:** `middleware(['role:admin|member'])`  
**Parameters:**
- `admin|member` - OR condition (user harus memiliki salah satu role)
- `admin,member` - AND condition (user harus memiliki semua role)
- `admin` - Single role check

**Examples:**
```php
// User harus admin ATAU member
Route::middleware(['role:admin|member'])->group(...);

// User harus admin DAN member (jarang digunakan)
Route::middleware(['role:admin,member'])->group(...);

// User harus admin saja
Route::middleware(['role:admin'])->group(...);
```

### 2. PermissionMiddleware (`permission`)
**Purpose:** Mengontrol akses berdasarkan permission specific  
**Usage:** `middleware(['permission:create-chat|edit-chat'])`  
**Parameters:** Similar to role middleware (OR/AND conditions)

**Available Permissions:**
- `create-chat` - Membuat chat baru
- `edit-chat` - Edit informasi chat
- `delete-chat` - Hapus chat
- `manage-chat-members` - Kelola member chat
- `send-message` - Kirim pesan
- `delete-message` - Hapus pesan
- `view-chat` - Melihat chat
- `manage-users` - Kelola user

### 3. ChatOwnerMiddleware (`chat.access`)
**Purpose:** Mengontrol akses berdasarkan ownership atau membership chat  
**Usage:** `middleware(['chat.access:member'])`  
**Parameters:**
- `member` (default) - User harus member chat atau admin
- `owner` - User harus pemilik chat
- `owner-or-admin` - User harus pemilik chat atau admin

**Examples:**
```php
// Hanya member chat atau admin yang bisa akses
Route::middleware(['chat.access:member'])->group(...);

// Hanya owner chat yang bisa akses
Route::middleware(['chat.access:owner'])->group(...);

// Owner atau admin yang bisa akses
Route::middleware(['chat.access:owner-or-admin'])->group(...);
```

### 4. LogAccessMiddleware (`log.access`)
**Purpose:** Mencatat semua akses ke route untuk audit trail  
**Usage:** `middleware(['log.access:chat_access'])`  
**Parameters:** Context string untuk log (optional)

**Log Information:**
- User ID dan nama
- Route name dan URL
- HTTP method
- IP address dan User Agent
- Route parameters
- Status code response

### 5. ChatRateLimitMiddleware (`chat.rate_limit`)
**Purpose:** Membatasi jumlah request per menit per user  
**Usage:** `middleware(['chat.rate_limit:60'])`  
**Parameters:** Max attempts per minute (default: 60)

**Rate Limits by Route:**
- Chat browsing: 100 requests/minute
- Chat management: 30 requests/minute  
- Chat creation: 10 requests/minute

## Route Protection Examples

### Chat Index (List all chats)
```php
Route::middleware([
    'role:admin|member',           // Admin atau member
    'chat.rate_limit:100',         // Max 100 req/min
    'log.access:chat_access'       // Log akses
])
```

### Chat Room (View specific chat)
```php
Route::middleware([
    'permission:view-chat',        // Harus punya permission
    'chat.access:member',          // Harus member chat
    'log.access:chat_room_access'  // Log akses room
])
```

### Chat Management (Admin panel)
```php
Route::middleware([
    'chat.access:owner-or-admin',     // Owner atau admin
    'permission:manage-chat-members', // Permission khusus
    'chat.rate_limit:30',            // Rate limit ketat
    'log.access:chat_management'     // Log management
])
```

## Security Features

1. **Multi-layer Protection:** Setiap route dilindungi beberapa middleware
2. **Granular Permissions:** Permission level yang detail
3. **Rate Limiting:** Mencegah spam dan abuse
4. **Audit Logging:** Semua akses dicatat untuk security audit
5. **Owner-based Access:** Kontrol akses berdasarkan ownership

## Error Responses

- **403 Forbidden:** User tidak punya role/permission
- **404 Not Found:** Chat tidak ditemukan
- **429 Too Many Requests:** Rate limit exceeded

## Best Practices

1. Selalu gunakan middleware yang sesuai untuk setiap route
2. Kombinasikan role + permission untuk security berlapis
3. Set rate limit yang reasonable untuk setiap jenis aksi
4. Gunakan logging untuk audit dan debugging
5. Test semua middleware dengan user berbeda role
