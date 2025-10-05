# Livewire Error Fix Documentation

## Problem
Error: `Uncaught TypeError: can't access property "call", window.Livewire.find(...) is undefined`

## Root Cause
The error occurred because JavaScript code was trying to call Livewire methods (`@this.call()`, `@this.set()`) before Livewire was fully initialized, causing `@this` to be undefined.

## Solution Applied

### 1. Safety Helper Functions
Added helper functions to safely call Livewire methods:

```javascript
// Safe Livewire call helper
function safeLivewireCall(method, ...args) {
    if (window.Livewire && @this && typeof @this[method] === 'function') {
        try {
            return @this[method](...args);
        } catch (error) {
            console.error('Livewire call error:', error);
            if (window.logger) {
                window.logger.error('Livewire call failed', {
                    method, args, error: error.message, component: 'component-name'
                });
            }
        }
    } else {
        console.warn('Livewire not ready for method:', method);
        if (window.logger) {
            window.logger.warn('Livewire not ready', {
                method, livewireExists: !!window.Livewire, thisExists: !!@this
            });
        }
        return null;
    }
}
```

### 2. Initialization Waiting
Added `waitForLivewire()` function to wait for proper initialization:

```javascript
function waitForLivewire(callback, timeout = 5000) {
    const startTime = Date.now();
    const checkInterval = setInterval(() => {
        if (window.Livewire && @this && typeof @this.call === 'function') {
            clearInterval(checkInterval);
            callback();
        } else if (Date.now() - startTime > timeout) {
            clearInterval(checkInterval);
            console.warn('Livewire initialization timeout');
        }
    }, 100);
}
```

### 3. Event Listener Protection
Wrapped Livewire event listeners in `livewire:initialized` event:

```javascript
document.addEventListener('livewire:initialized', () => {
    Livewire.on('show-initial-notifications-delayed', () => {
        safeLivewireCall('call', 'showInitialNotifications');
    });
});
```

### 4. Files Modified
- `/resources/views/livewire/components/notification-manager.blade.php`
- `/resources/views/livewire/chat/show.blade.php`

### 5. Integration with Frontend Logger
All Livewire errors are now logged through the frontend logging system for monitoring and debugging.

## Testing
```bash
npm run build
```

## Benefits
1. **Error Prevention**: No more undefined @this errors
2. **Graceful Degradation**: Code continues to work even if Livewire fails to load
3. **Better Debugging**: Comprehensive error logging
4. **Production Safety**: Robust error handling for production environments
5. **Monitoring**: Integration with frontend logging system

## Usage
Replace direct `@this.call()` and `@this.set()` calls with:
```javascript
// Instead of: @this.call('methodName', arg1, arg2)
safeLivewireCall('call', 'methodName', arg1, arg2);

// Instead of: @this.set('property', value)
safeLivewireSet('property', value);
```

This fix ensures stable operation and provides visibility into any Livewire-related issues through the logging system.