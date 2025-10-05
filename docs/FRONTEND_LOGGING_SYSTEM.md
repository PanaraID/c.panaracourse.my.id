# Frontend Logging System Documentation

## Overview

Sistem logging frontend yang komprehensif untuk aplikasi PanaraID Course Platform. Sistem ini menyediakan logging yang detail untuk JavaScript errors, AJAX requests, user interactions, performance metrics, dan debugging information.

## Features

### 🔍 **Comprehensive Logging**
- **Error Tracking**: JavaScript errors, unhandled promise rejections, resource loading errors
- **Performance Monitoring**: Page load times, AJAX response times, navigation metrics
- **User Activity Tracking**: Clicks, form submissions, page views, navigation
- **AJAX Monitoring**: Automatic request/response logging with timing
- **Console Integration**: Interactive debug console dengan filtering dan export

### 📊 **Advanced Analytics**
- Real-time log dashboard
- Error analysis dan trending
- Performance metrics
- User behavior analytics
- Export capabilities (JSON/CSV)

### 🛠 **Developer Tools**
- Interactive debug console (`Ctrl+Shift+L`)
- Local storage untuk offline logging
- Filtering dan search capabilities
- Stack trace capturing
- Session tracking

## Installation & Setup

### 1. Database Migration

Jalankan migration untuk membuat tabel `frontend_logs`:

```bash
php artisan migrate
```

### 2. Frontend Integration

File logger sudah terintegrasi dalam `resources/js/app.js`. Sistem akan otomatis aktif setelah build:

```bash
npm run build
# atau untuk development
npm run dev
```

### 3. Configuration

Sistem logger dapat dikonfigurasi melalui JavaScript:

```javascript
// Konfigurasi default sudah optimal, tapi bisa disesuaikan
window.logger = new FrontendLogger({
    level: 'info',              // debug, info, warn, error, silent
    enableConsole: true,        // Log ke browser console
    enableRemote: true,         // Kirim ke server
    enableLocalStorage: true,   // Simpan di localStorage
    enableErrorTracking: true,  // Track JavaScript errors
    enablePerformanceMonitoring: true,
    enableUserTracking: true
});
```

## Usage

### Basic Logging

```javascript
// Berbagai level logging
window.logger.debug('Debug information', { variable: value });
window.logger.info('Informational message', { data: 'example' });
window.logger.warn('Warning message', { warning: 'details' });
window.logger.error('Error occurred', { error: errorObject });
```

### Specialized Logging

```javascript
// User actions
window.logger.logUserAction('button_click', element, { feature: 'checkout' });

// Page views
window.logger.logPageView('/dashboard');

// Performance metrics
window.logger.logPerformance('custom_metric', 1500, { component: 'chat' });

// AJAX requests (automatic dengan axios interceptor)
// Manual logging:
window.logger.logAjaxRequest(config, response, error);
```

### Debug Console

Tekan `Ctrl+Shift+L` untuk membuka debug console yang interaktif:

- **Real-time log viewing**
- **Filtering** berdasarkan level, type, date, user
- **Search** dalam message dan data
- **Export** ke JSON atau CSV
- **Stack trace** untuk errors
- **Session tracking**

### Keyboard Shortcuts

- `Ctrl+Shift+L` - Toggle debug console
- `Ctrl+Shift+C` - Clear logs (ketika console terbuka)
- `Ctrl+Shift+E` - Export logs (ketika console terbuka)

## Admin Dashboard

### Akses Dashboard

Dashboard admin dapat diakses di:
- **Main Dashboard**: `/admin/logs`
- **Detailed Logs**: `/admin/logs/logs`
- **Error Analysis**: `/admin/logs/errors`
- **Performance**: `/admin/logs/performance`

### Features Dashboard

1. **Overview Dashboard**
   - Statistics cards (total logs, errors, warnings, users)
   - Daily log volume chart
   - Log levels distribution
   - Log types distribution
   - Recent logs table

2. **Detailed Logs View**
   - Comprehensive filtering (level, type, user, date range)
   - Search functionality
   - Pagination
   - Export to CSV
   - Expandable log details
   - Stack trace viewing

3. **Error Analysis**
   - Error statistics
   - Top errors by frequency
   - Errors by page
   - Error trends over time

4. **Performance Monitoring**
   - Page load time statistics
   - AJAX response time metrics
   - Performance trends

## API Endpoints

### Store Logs (Frontend → Backend)

```
POST /api/frontend-logs
```

**Request Body:**
```json
{
  "logs": [
    {
      "id": "unique_log_id",
      "timestamp": "2024-01-01T10:00:00Z",
      "level": "error",
      "message": "JavaScript error occurred",
      "data": { "error": "details" },
      "context": {
        "type": "js_error",
        "url": "https://example.com/page",
        "sessionId": "session_123",
        "userId": "user_456"
      },
      "stackTrace": "Error stack trace..."
    }
  ],
  "session": {
    "sessionId": "session_123",
    "userId": "user_456",
    "deviceInfo": { ... }
  }
}
```

### Get Logs (Admin Dashboard)

```
GET /api/frontend-logs
Authorization: Bearer {token}
```

**Query Parameters:**
- `level` - Filter by log level
- `type` - Filter by log type
- `user_id` - Filter by user
- `start_date` - Start date filter
- `end_date` - End date filter
- `search` - Search in messages
- `per_page` - Pagination size
- `sort_by` - Sort column
- `sort_order` - Sort direction

### Get Statistics

```
GET /api/frontend-logs/stats
Authorization: Bearer {token}
```

### Cleanup Old Logs

```
DELETE /api/frontend-logs/cleanup
Authorization: Bearer {token}
Content-Type: application/json

{
  "days": 30
}
```

## Log Types

### Automatic Log Types

- `js_error` - JavaScript errors
- `promise_rejection` - Unhandled promise rejections
- `resource_error` - Resource loading errors
- `ajax_request` - AJAX requests
- `ajax_error` - AJAX errors
- `user_action` - User interactions
- `page_view` - Page views
- `performance` - Performance metrics

### Custom Log Types

Anda dapat menambahkan custom log types:

```javascript
window.logger.info('Custom event', { data: 'value' }, { type: 'custom_event' });
```

## Configuration Options

### Logger Configuration

```javascript
const config = {
    // Logging level
    level: 'info',                    // debug|info|warn|error|silent
    
    // Output options
    enableConsole: true,              // Browser console
    enableRemote: true,               // Send to server
    enableLocalStorage: true,         // Local storage
    
    // Remote settings
    endpoint: '/api/frontend-logs',   // API endpoint
    
    // Local storage settings
    maxLocalLogs: 1000,              // Max logs in localStorage
    localStorageKey: 'frontend_logs', // Storage key
    
    // Feature toggles
    enablePerformanceMonitoring: true,
    enableUserTracking: true,
    enableErrorTracking: true,
    
    // App info
    appName: 'PanaraID Course',
    version: '1.0.0',
    environment: 'production'        // development|production
};
```

### Environment Variables

Tambahkan ke `.env`:

```env
# Logging configuration
LOG_LEVEL=debug
LOG_DAILY_DAYS=14

# Frontend logging (optional)
FRONTEND_LOG_LEVEL=info
FRONTEND_LOG_ENDPOINT=/api/frontend-logs
```

## Performance Considerations

### Client-Side

- **Batching**: Logs dikirim dalam batch setiap 10 detik
- **Local Storage**: Maximum 1000 logs disimpan locally
- **Memory Management**: Old logs otomatis dihapus
- **Compression**: Large data structures di-compress

### Server-Side

- **Database Indexing**: Index pada kolom sering diquery
- **Cleanup**: Old logs dapat dihapus otomatis
- **Rate Limiting**: Pertimbangkan rate limiting untuk endpoint
- **Storage**: Gunakan daily rotation untuk log files

## Security Considerations

### Data Sanitization

- PII (Personally Identifiable Information) tidak disimpan
- Password dan sensitive data di-filter
- Stack traces di-sanitize untuk production

### Access Control

- Admin dashboard memerlukan authentication
- API endpoints menggunakan Sanctum authentication
- Rate limiting pada log submission

### Privacy

- User consent untuk tracking (implementasi sesuai kebutuhan)
- Data retention policies
- GDPR compliance considerations

## Troubleshooting

### Common Issues

1. **Logs tidak muncul di dashboard**
   - Periksa network tab untuk request failures
   - Pastikan authentication token valid
   - Check console untuk JavaScript errors

2. **Console debug tidak muncul**
   - Tekan `Ctrl+Shift+L`
   - Pastikan logger sudah initialize
   - Check browser compatibility

3. **Performance impact**
   - Sesuaikan `level` config untuk production
   - Disable unnecessary tracking features
   - Monitor memory usage

### Debug Mode

Untuk debugging, set level ke 'debug':

```javascript
window.logger.config.level = 'debug';
window.logger.debug('Debug mode enabled');
```

## Best Practices

### Development

- Gunakan `debug` level untuk development
- Aktifkan debug console untuk testing
- Test error scenarios secara manual

### Production

- Set level ke `warn` atau `error` untuk production
- Monitor dashboard secara rutin
- Setup alerts untuk critical errors
- Regular cleanup old logs

### Monitoring

- Monitor error trends
- Track performance regressions
- Analyze user behavior patterns
- Set up alerting untuk error spikes

## Examples

### Complete Implementation Example

```javascript
// Inisialisasi dengan custom config
window.logger = new FrontendLogger({
    level: 'info',
    appName: 'My App',
    version: '2.0.0',
    environment: 'production'
});

// Set user ID setelah login
window.logger.setUserId(userId);

// Custom logging
window.logger.info('User completed onboarding', {
    steps_completed: 5,
    time_taken: 120,
    user_type: 'premium'
}, {
    type: 'onboarding_complete'
});

// Error handling dengan context
try {
    // Some operation
} catch (error) {
    window.logger.error('Operation failed', {
        operation: 'data_sync',
        error_code: error.code,
        retry_count: 3
    }, {
        type: 'operation_error'
    });
}
```

### Testing Scenarios

```javascript
// Test error logging
window.logger.error('Test error', { test: true });

// Test performance logging
window.logger.logPerformance('test_metric', 1000);

// Test user action
window.logger.logUserAction('test_click', null, { test: true });

// Simulate JavaScript error
throw new Error('Test error for logging');

// Test AJAX error (if using axios)
axios.get('/non-existent-endpoint').catch(() => {
    console.log('AJAX error logged automatically');
});
```

## Changelog

### Version 1.0.0
- Initial implementation
- Basic logging functionality
- Debug console
- Admin dashboard
- Performance monitoring
- Error tracking
- User activity logging

---

## Support

Untuk pertanyaan atau issues terkait frontend logging system:

1. Check dokumentasi ini terlebih dahulu
2. Periksa console browser untuk error messages
3. Test dengan debug console (`Ctrl+Shift+L`)
4. Review admin dashboard untuk patterns

## Contributing

Ketika menambah fitur baru:

1. Update dokumentasi ini
2. Tambahkan tests
3. Update changelog
4. Pertimbangkan backward compatibility