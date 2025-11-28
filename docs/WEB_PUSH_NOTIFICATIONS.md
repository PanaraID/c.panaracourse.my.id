# Web Push Notifications Implementation

## 📋 Overview

Sistem Web Push Notifications telah diimplementasikan untuk mengirimkan notifikasi real-time ke browser pengguna, bahkan ketika aplikasi tidak sedang dibuka. Sistem ini menggunakan Web Push API dengan VAPID (Voluntary Application Server Identification).

## ✨ Fitur

- 🔔 **Push Notifications** - Notifikasi langsung ke browser
- 📱 **Multi-Device Support** - Satu user bisa menerima notifikasi di beberapa device
- 🔄 **Auto Subscription Management** - Subscription otomatis tersimpan dan dikelola
- 🗑️ **Auto Cleanup** - Subscription expired otomatis dihapus
- 🎯 **Targeted Notifications** - Kirim notifikasi ke specific user
- 📊 **Message Preview** - Preview konten pesan dalam notifikasi
- 🔗 **Deep Linking** - Klik notifikasi langsung ke halaman chat

## 🏗️ Struktur Implementasi

### Backend Components

#### 1. Database Migration
```php
// database/migrations/2025_11_28_215504_create_push_subscriptions_table.php
Schema::create('push_subscriptions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('endpoint', 500);
    $table->string('public_key')->nullable();
    $table->string('auth_token')->nullable();
    $table->text('subscription_data'); // Full JSON subscription data
    $table->timestamps();
    $table->unique(['user_id', 'endpoint']);
});
```

#### 2. Model: PushSubscription
**File:** `app/Models/PushSubscription.php`

**Methods:**
- `updateOrCreateSubscription($userId, $subscriptionData)` - Create or update subscription
- `toWebPushSubscription()` - Convert to WebPush library format
- `sendPushNotification($title, $body, $data)` - Send notification to this subscription

#### 3. Model: User (Extended)
**File:** `app/Models/User.php`

**New Methods:**
- `pushSubscriptions()` - Relation to PushSubscription
- `sendPushNotification($title, $body, $data)` - Send to all user's devices

#### 4. API Controller
**File:** `app/Http/Controllers/Api/PushSubscriptionController.php`

**Endpoints:**
- `GET /api/push/public-key` - Get VAPID public key
- `POST /api/push/subscribe` - Save subscription (requires auth)
- `POST /api/push/unsubscribe` - Remove subscription (requires auth)

#### 5. Event Listener
**File:** `app/Listeners/SendMessageNotification.php`

Automatically triggered when `MessageSent` event fires:
1. Creates in-app notification
2. Sends push notification to all recipients' devices
3. Includes chat URL for deep linking

### Frontend Components

#### 1. Service Worker (sw.js)
**File:** `public/sw.js`

**Push Event Handler:**
- Receives push notification from server
- Parses notification payload
- Shows browser notification with custom data
- Handles notification click for deep linking

**Key Features:**
```javascript
// Parse push data
self.addEventListener('push', event => {
    const data = event.data.json();
    // Show notification with title, body, icon, actions
});

// Handle notification clicks
self.addEventListener('notificationclick', event => {
    // Open or focus app window at specific URL
});
```

#### 2. Application JavaScript (app.js)
**File:** `resources/js/app.js`

**Functions:**
- `requestNotificationPermission()` - Request user permission
- `subscribeToPushNotifications()` - Create subscription
- `sendSubscriptionToServer()` - Save to backend
- `urlBase64ToUint8Array()` - Convert VAPID key

**Flow:**
1. Request notification permission
2. Get VAPID public key from server
3. Subscribe using Push Manager
4. Send subscription data to backend
5. Backend stores in database

## 🔧 Configuration

### VAPID Keys Setup

**File:** `config/services.php`
```php
'vapid' => [
    'subject' => env('VAPID_SUBJECT', env('APP_URL')),
    'public_key' => env('VAPID_PUBLIC_KEY'),
    'private_key' => env('VAPID_PRIVATE_KEY'),
],
```

**Environment Variables (.env):**
```env
VAPID_SUBJECT=https://c.panaracourse.my.id
VAPID_PUBLIC_KEY=BCuyibSSOEa2kZN_576JLcQDr12BUAQjhavSziBBJuUkCbysAfCdPfjIoUHoIBeGxgD6BO2hb9YpjN_mm5nlglE
VAPID_PRIVATE_KEY=eJ7szp49X_YzXQ8Xr7Cx169IEaL79qMvp_VIEy7aKo4
```

> **Note:** VAPID keys sudah di-generate. Untuk generate keys baru, gunakan online VAPID generator atau library `web-push`.

## 🚀 Cara Kerja

### Flow Diagram

```
User Login → Request Permission → Subscribe to Push
                                         ↓
                              Save Subscription to DB
                                         ↓
New Message Sent → MessageSent Event → SendMessageNotification Listener
                                         ↓
                              Create Notification Record
                                         ↓
                         Send Push to User's Devices
                                         ↓
                         Service Worker Receives Push
                                         ↓
                         Show Browser Notification
                                         ↓
                    User Clicks → Open Chat Page
```

### Detailed Steps

1. **User Authentication**
   - User logs in and grants notification permission
   - Frontend requests subscription from Push Manager
   - Subscription sent to backend API

2. **Subscription Storage**
   - Backend validates and stores subscription in `push_subscriptions` table
   - One user can have multiple subscriptions (multi-device)

3. **Message Sending**
   - User sends message in chat
   - `MessageSent` event triggered
   - `SendMessageNotification` listener handles event

4. **Notification Creation**
   - In-app notification created in database
   - Push notification sent to all recipients via WebPush library
   - Invalid/expired subscriptions auto-deleted

5. **Browser Reception**
   - Service Worker receives push event
   - Notification displayed with custom data
   - Click handler opens specific chat page

## 📱 User Experience

### Permission Request
```javascript
// Automatic on first visit (after login)
Notification.requestPermission()
```

### Notification Display
- **Title:** "Pesan baru dari [Sender Name]"
- **Body:** Message preview (100 chars)
- **Icon:** App icon (192x192)
- **Badge:** App badge (72x72)
- **Actions:** "Buka" and "Tutup"

### Notification Click
- **Action "Buka"** or **Click Body:** Opens chat page
- **Action "Tutup":** Dismisses notification

## 🔒 Security

### Authentication
- All API endpoints require Sanctum authentication
- Subscription tied to authenticated user ID

### Validation
- Endpoint, keys, and auth token validated
- Duplicate subscriptions prevented via unique constraint

### Auto Cleanup
- Expired subscriptions (404/410 errors) auto-deleted
- Prevents database bloat

## 🧪 Testing

### Manual Testing

1. **Test Subscription:**
```javascript
// In browser console (after login)
navigator.serviceWorker.ready.then(reg => {
    reg.pushManager.getSubscription().then(sub => {
        console.log('Current subscription:', sub);
    });
});
```

2. **Test Notification:**
- Send message in chat
- Check browser notification appears
- Click notification, verify redirect to chat

3. **Test Multi-Device:**
- Login on multiple browsers/devices
- Send message
- Verify all devices receive notification

### API Testing

```bash
# Get public key
curl https://c.panaracourse.my.id/api/push/public-key

# Subscribe (with auth token)
curl -X POST https://c.panaracourse.my.id/api/push/subscribe \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "endpoint": "https://fcm.googleapis.com/fcm/send/...",
    "keys": {
      "p256dh": "...",
      "auth": "..."
    }
  }'
```

## 📊 Database Schema

### Table: push_subscriptions

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| user_id | bigint | Foreign key to users |
| endpoint | varchar(500) | Push service endpoint |
| public_key | varchar | P256DH key |
| auth_token | varchar | Auth secret |
| subscription_data | text | Full JSON subscription |
| created_at | timestamp | Creation time |
| updated_at | timestamp | Last update |

**Indexes:**
- Primary: `id`
- Foreign: `user_id` → `users.id` (cascade delete)
- Unique: `(user_id, endpoint)`

## 🔍 Troubleshooting

### Notification Not Appearing

**Check:**
1. Browser notification permission granted?
2. Service Worker registered?
3. Subscription saved to server?
4. Push subscription still valid?

```javascript
// Debug checklist
console.log('Permission:', Notification.permission);
console.log('SW:', navigator.serviceWorker.controller);
navigator.serviceWorker.ready.then(reg => {
    reg.pushManager.getSubscription().then(sub => {
        console.log('Subscription:', sub);
    });
});
```

### Subscription Failed

**Possible causes:**
- Invalid VAPID keys
- Service Worker not active
- Browser doesn't support Push API
- Network error

### Push Not Received

**Check backend:**
```bash
# Check logs
tail -f storage/logs/laravel.log | grep "Push notification"

# Check subscriptions in database
php artisan tinker
>>> App\Models\PushSubscription::count()
>>> App\Models\PushSubscription::where('user_id', 1)->get()
```

## 📚 References

- [Web Push API](https://developer.mozilla.org/en-US/docs/Web/API/Push_API)
- [VAPID Protocol](https://tools.ietf.org/html/rfc8292)
- [Minishlink WebPush Library](https://github.com/web-push-libs/web-push-php)
- [Service Worker API](https://developer.mozilla.org/en-US/docs/Web/API/Service_Worker_API)

## ⚙️ Advanced Usage

### Send Custom Notification

```php
use App\Models\User;

$user = User::find(1);
$user->sendPushNotification(
    'Custom Title',
    'Custom message body',
    [
        'url' => '/custom-page',
        'custom_data' => 'value'
    ]
);
```

### Send to Specific Subscription

```php
use App\Models\PushSubscription;

$subscription = PushSubscription::find(1);
$subscription->sendPushNotification(
    'Title',
    'Body',
    ['key' => 'value']
);
```

### Cleanup Expired Subscriptions

```php
// Manually trigger cleanup
php artisan tinker
>>> App\Models\User::find(1)->sendPushNotification('Test', 'Test');
// Invalid subscriptions will be auto-deleted during send
```

## 🎯 Future Improvements

- [ ] Notification preferences per user
- [ ] Notification categories (messages, mentions, system)
- [ ] Silent push for background sync
- [ ] Rich notifications with images
- [ ] Notification history/archive
- [ ] Admin panel for broadcast notifications
- [ ] A/B testing for notification content

---

**Last Updated:** November 28, 2025  
**Version:** 1.0  
**Maintainer:** Panara Course Team
