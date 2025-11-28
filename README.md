<p align="center">
  <img src="public/logo.png" width="200" alt="Panara Course Logo">
</p>

<h1 align="center">Komunitas Panara Course</h1>

<p align="center">
  Platform komunikasi real-time berbasis web untuk komunitas Panara Course dengan fitur Progressive Web App (PWA).
</p>

<p align="center">
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-12.x-red.svg" alt="Laravel Version"></a>
  <a href="https://livewire.laravel.com"><img src="https://img.shields.io/badge/Livewire-3.x-blue.svg" alt="Livewire Version"></a>
  <a href="https://www.php.net"><img src="https://img.shields.io/badge/PHP-8.2+-purple.svg" alt="PHP Version"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-green.svg" alt="License"></a>
</p>

---

## 📋 Tentang Aplikasi

Komunitas Panara Course adalah platform chat real-time yang dibangun khusus untuk memfasilitasi komunikasi antar anggota komunitas. Aplikasi ini dilengkapi dengan berbagai fitur modern seperti PWA, notifikasi real-time, sistem tagging, dan manajemen pengguna berbasis role.

### ✨ Fitur Utama

- 💬 **Real-time Chat** - Komunikasi instant menggunakan Livewire
- 🏷️ **Message Tagging** - Tandai anggota dalam pesan untuk notifikasi langsung
- 🔔 **Real-time Notifications** - Notifikasi browser dan in-app secara real-time
- 📱 **Progressive Web App (PWA)** - Installable seperti aplikasi native
- 👥 **Role & Permission Management** - Sistem role (Admin/Member) dengan Spatie Permission
- 🔐 **Authentication** - Login, register, dan reset password dengan Laravel Fortify
- 📊 **Activity Logging** - Pencatatan aktivitas pengguna untuk audit
- 🌐 **Offline Support** - Service Worker untuk pengalaman offline terbatas
- 🎨 **Modern UI** - Interface responsif dengan Tailwind CSS dan Livewire Flux
- 📈 **Admin Dashboard** - Manajemen pengguna, chat, dan monitoring sistem

## 🚀 Teknologi Stack

### Backend
- **Laravel 12** - PHP Framework
- **Livewire 3** - Reactive Components
- **Laravel Fortify** - Authentication
- **Laravel Sanctum** - API Token Management
- **Laravel Horizon** - Queue Monitoring
- **Spatie Laravel Permission** - Role & Permission Management
- **Spatie Laravel Backup** - Database & File Backup

### Frontend
- **Livewire Flux** - UI Components
- **Tailwind CSS 4** - Styling
- **Alpine.js** - JavaScript Interactivity
- **Vite** - Asset Bundling

### Infrastructure
- **SQLite** - Database
- **Redis** (Optional) - Cache & Broadcasting
- **Web Push** - Browser Notifications
- **Service Worker** - PWA & Offline Support

## 📦 Instalasi

### Persyaratan Sistem

- PHP >= 8.2
- Composer
- Node.js >= 18.x
- NPM atau Yarn
- SQLite atau MySQL/PostgreSQL
- Redis (opsional, untuk broadcasting)

### Langkah Instalasi

1. **Clone Repository**
   ```bash
   git clone https://github.com/PanaraID/c.panaracourse.my.id.git
   cd c.panaracourse.my.id
   ```

2. **Install Dependencies**
   ```bash
   # Install PHP dependencies
   composer install

   # Install JavaScript dependencies
   npm install
   ```

3. **Setup Environment**
   ```bash
   # Copy file environment
   cp .env.example .env

   # Generate application key
   php artisan key:generate
   ```

4. **Setup Database**
   ```bash
   # Buat file database SQLite (jika menggunakan SQLite)
   touch database/database.sqlite

   # Jalankan migrasi
   php artisan migrate

   # Seed database dengan data awal
   php artisan db:seed
   ```

5. **Build Assets**
   ```bash
   npm run build
   ```

6. **Setup Storage Link**
   ```bash
   php artisan storage:link
   ```

## 🔧 Konfigurasi

### Environment Variables

Edit file `.env` sesuai kebutuhan:

```env
APP_NAME="Komunitas Panara Course"
APP_ENV=production
APP_URL=https://c.panaracourse.my.id

# Database (SQLite default)
DB_CONNECTION=sqlite

# Queue (untuk notifikasi)
QUEUE_CONNECTION=database

# Broadcasting (untuk real-time features)
BROADCAST_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Web Push Notifications
VAPID_PUBLIC_KEY=your_public_key
VAPID_PRIVATE_KEY=your_private_key
```

### Web Push Notifications Setup

Generate VAPID keys untuk push notifications:

```bash
npm install web-push -g
web-push generate-vapid-keys [--json]
```

## 🏃 Menjalankan Aplikasi

### Development Mode

Gunakan script composer untuk menjalankan semua service sekaligus:

```bash
composer dev
```

Script ini akan menjalankan:
- Web server (http://localhost:8000)
- Queue worker
- Log viewer (Pail)
- Vite dev server

### Production Mode

```bash
# Build assets untuk production
npm run build

# Jalankan horizon untuk queue monitoring
php artisan horizon

# Setup web server (Nginx/Apache) untuk serve aplikasi
```

## 📱 Fitur PWA

### Install sebagai Aplikasi

**Di Smartphone (Android/iOS):**
1. Buka website di browser
2. Tap tombol "Install App" atau "Add to Home Screen"
3. Aplikasi akan muncul di home screen

**Di Desktop:**
1. Buka website di Chrome/Edge
2. Klik ikon install di address bar
3. Klik "Install"

### Service Worker

Service worker otomatis aktif dan meng-cache:
- Static assets (CSS, JS, images)
- Halaman offline
- API responses (terbatas)

## 👥 Sistem Role & Permission

### Default Roles

- **Admin** - Full access ke semua fitur
  - Manage users
  - Create/delete chats
  - Manage chat members
  - View logs & analytics

- **Member** - Access terbatas
  - Join assigned chats
  - Send/receive messages
  - Tag other members
  - Receive notifications

### Permissions

- `create-chat` - Membuat chat baru
- `manage-chat-members` - Mengelola anggota chat
- `delete-chat` - Menghapus chat
- `view-logs` - Melihat activity logs
- `manage-users` - Mengelola pengguna

## 🔔 Sistem Notifikasi

### Jenis Notifikasi

1. **New Message** - Notifikasi pesan baru di chat
2. **Message Tag** - Notifikasi saat di-tag dalam pesan
3. **Chat Invitation** - Notifikasi undangan ke chat baru
4. **System Notification** - Notifikasi sistem

### Cara Kerja

- Real-time dengan Livewire polling (5 detik)
- Browser push notifications (jika diizinkan)
- In-app notification badge & dropdown
- Email notification (opsional)

## 🏷️ Sistem Tagging

### Cara Menggunakan

1. Klik tombol "Tag Orang" saat menulis pesan
2. Pilih anggota yang ingin di-tag
3. Kirim pesan
4. Anggota yang di-tag akan menerima notifikasi

### Tracking Tags

- Badge notifikasi menampilkan jumlah tag belum dibaca
- Klik badge untuk melihat semua tag
- Klik "Lihat Pesan" untuk scroll ke pesan dengan highlight
- Tandai sebagai dibaca setelah melihat

## 📊 Logging & Monitoring

### Frontend Logging

Sistem logging aktivitas frontend untuk debugging:

```javascript
window.logActivity('event_type', { data: 'value' });
```

Logs dapat dilihat di:
- Browser console (development)
- Database `frontend_logs` table
- Admin panel (jika diimplementasikan)

### Backend Logging

- Laravel Log Viewer untuk monitoring logs
- Activity logs untuk audit trail
- Queue monitoring dengan Horizon

## 🧪 Testing

Aplikasi menggunakan Pest PHP untuk testing:

```bash
# Run all tests
php artisan test

# atau
composer test

# Run specific test file
php artisan test tests/Feature/ChatTest.php

# Run with coverage
php artisan test --coverage
```

## 📚 Dokumentasi Lengkap

Dokumentasi teknis tersedia di folder `docs/`:

- [PWA Implementation](docs/PWA_IMPLEMENTATION.md)
- [Message Tagging System](docs/MESSAGE_TAGGING_SYSTEM.md)
- [Real-time Notifications](docs/REALTIME_NOTIFICATIONS.md)
- [Frontend Logging System](docs/FRONTEND_LOGGING_SYSTEM.md)
- [Authentication UI Redesign](docs/AUTH_UI_REDESIGN.md)
- [Middleware Documentation](docs/MIDDLEWARE.md)
- [Sanctum Token Authentication](docs/SANCTUM_TOKEN_AUTHENTICATION.md)

## 🔒 Security

### Keamanan Aplikasi

- CSRF Protection (Laravel default)
- XSS Prevention dengan Blade escaping
- SQL Injection prevention dengan Eloquent ORM
- Rate limiting untuk API endpoints
- Secure password hashing dengan bcrypt
- Token-based authentication dengan Sanctum

### Melaporkan Vulnerability

Jika menemukan security vulnerability, mohon laporkan ke:
- Email: security@panaracourse.my.id
- Atau buat private security advisory di GitHub

## 🛠️ Deployment

### Deployment ke Production

1. **Build Assets**
   ```bash
   npm run build
   ```

2. **Optimize Laravel**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan optimize
   ```

3. **Setup Queue & Scheduler**
   ```bash
   # Tambahkan ke crontab
   * * * * * cd /path-to-app && php artisan schedule:run >> /dev/null 2>&1

   # Setup supervisor untuk queue worker
   php artisan queue:work --daemon
   ```

4. **Setup Web Server (Nginx Example)**
   ```nginx
   server {
       listen 80;
       server_name c.panaracourse.my.id;
       root /path-to-app/public;

       add_header X-Frame-Options "SAMEORIGIN";
       add_header X-Content-Type-Options "nosniff";

       index index.php;

       charset utf-8;

       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }

       location ~ \.php$ {
           fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
           fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
           include fastcgi_params;
       }

       location ~ /\.(?!well-known).* {
           deny all;
       }
   }
   ```

## 🤝 Contributing

Kontribusi sangat diterima! Silakan:

1. Fork repository
2. Buat branch fitur (`git checkout -b feature/AmazingFeature`)
3. Commit perubahan (`git commit -m 'Add some AmazingFeature'`)
4. Push ke branch (`git push origin feature/AmazingFeature`)
5. Buat Pull Request

## 📄 License

Aplikasi ini dilisensikan di bawah [MIT License](LICENSE).

## 👨‍💻 Developer

Dikembangkan dengan ❤️ oleh [Panara Course Team](https://github.com/PanaraID)

## 📞 Kontak & Support

- Website: [https://c.panaracourse.my.id](https://c.panaracourse.my.id)
- Email: support@panaracourse.my.id
- GitHub Issues: [Report Bug/Request Feature](https://github.com/PanaraID/c.panaracourse.my.id/issues)

---

<p align="center">
  Made with ❤️ for Panara Course Community
</p>
