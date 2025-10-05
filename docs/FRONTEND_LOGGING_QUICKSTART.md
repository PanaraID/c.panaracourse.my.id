# Frontend Logging System - Quick Start Guide

## 🚀 Quick Setup

### 1. Jalankan Migration
```bash
php artisan migrate
```

### 2. Build Assets
```bash
npm run build
```

### 3. Test Logger
Buka browser console dan jalankan:
```javascript
// Test basic logging
window.logger.info('Hello from logger!', { test: true });

// Open debug console
// Tekan Ctrl+Shift+L

// Run automated tests
window.runLoggingTests();
```

## 🎯 Key Features

### ✅ Error Tracking
- JavaScript errors otomatis ter-capture
- Stack traces lengkap
- Unhandled promise rejections
- Resource loading errors

### ✅ Performance Monitoring
- Page load times
- AJAX response times
- Custom metrics
- Navigation timing

### ✅ User Activity
- Click tracking
- Form submissions
- Page views
- Custom user actions

### ✅ Debug Console
- Real-time log viewing
- Advanced filtering
- Export capabilities
- Keyboard shortcuts

### ✅ Admin Dashboard
- Comprehensive analytics
- Error analysis
- Performance insights
- CSV export

## 🛠 Usage Examples

### Basic Logging
```javascript
// Different log levels
window.logger.debug('Debug info', { variable: value });
window.logger.info('User logged in', { userId: 123 });
window.logger.warn('API slow response', { duration: 3000 });
window.logger.error('Payment failed', { error: errorObj });
```

### User Actions
```javascript
// Track user interactions
window.logger.logUserAction('button_click', element, { 
    feature: 'checkout',
    step: 'payment'
});
```

### Performance
```javascript
// Custom performance metrics
window.logger.logPerformance('component_render', 150, {
    component: 'UserDashboard'
});
```

### Page Views
```javascript
// Track page navigation
window.logger.logPageView('/dashboard');
```

## 🎮 Debug Console

**Keyboard Shortcuts:**
- `Ctrl+Shift+L` - Toggle console
- `Ctrl+Shift+C` - Clear logs
- `Ctrl+Shift+E` - Export logs

**Features:**
- Real-time filtering
- Search functionality
- Stack trace viewing
- Session tracking
- Export to JSON/CSV

## 📊 Admin Dashboard

**Access URLs:**
- Dashboard: `/admin/logs`
- Detailed Logs: `/admin/logs/logs`
- Error Analysis: `/admin/logs/errors`
- Performance: `/admin/logs/performance`

**Features:**
- Real-time statistics
- Interactive charts
- Advanced filtering
- Bulk export
- Error trending

## 🔧 Configuration

### Logger Config
```javascript
window.logger = new FrontendLogger({
    level: 'info',                    // debug|info|warn|error
    enableConsole: true,              // Browser console
    enableRemote: true,               // Send to server
    enableLocalStorage: true,         // Local storage
    enableErrorTracking: true,        // Auto error capture
    enablePerformanceMonitoring: true,
    enableUserTracking: true,
    appName: 'Your App Name',
    version: '1.0.0'
});
```

### Set User Context
```javascript
// Set user ID after login
window.logger.setUserId(userId);
```

## 🧪 Testing

### Automated Tests
```javascript
// Run full test suite
window.runLoggingTests();

// Check results
console.log(window.loggingTestResults);
```

### Manual Testing
```javascript
// Test error logging
throw new Error('Test error');

// Test AJAX logging
axios.get('/test-endpoint');

// Test user action
document.querySelector('button').click();

// Test performance
window.logger.logPerformance('test', 1000);
```

## 📈 Monitoring

### Key Metrics to Watch
- Error rate trends
- Performance regressions
- User engagement patterns
- Browser/platform distribution

### Alerts Setup
Consider setting up alerts for:
- Error spike (>50 errors/hour)
- Performance degradation (>3s load time)
- High memory usage
- Failed API requests

## 🔒 Security Notes

- PII data automatically filtered
- Stack traces sanitized in production
- Authentication required for admin access
- Rate limiting recommended

## 🎯 Best Practices

### Development
- Use `debug` level
- Enable all features
- Test error scenarios
- Monitor console output

### Production
- Use `warn` or `error` level
- Disable debug features
- Monitor dashboard regularly
- Clean up old logs

### Performance
- Batch log submissions
- Limit local storage
- Monitor memory usage
- Use appropriate log levels

## 🆘 Troubleshooting

### Logger Not Working
1. Check browser console for errors
2. Verify assets are built (`npm run build`)
3. Ensure migration ran successfully
4. Test with `window.logger.info('test')`

### Debug Console Not Showing
1. Press `Ctrl+Shift+L`
2. Check if logger is initialized
3. Verify no JavaScript errors
4. Try refreshing page

### Dashboard Empty
1. Generate some logs first
2. Check network tab for API errors
3. Verify authentication
4. Check database for logs

### Performance Issues
1. Reduce log level in production
2. Disable unnecessary features
3. Monitor memory usage
4. Check batch size settings

## 📞 Support

Common solutions:
- Check browser console first
- Use debug console for diagnostics
- Review documentation
- Test with automated test suite

---

**Happy Logging! 🎉**

Sistem logging frontend sekarang siap digunakan. Monitor aplikasi Anda dengan confidence!