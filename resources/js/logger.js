/**
 * Professional Frontend Logging System
 * Provides comprehensive logging, error tracking, performance monitoring, and debugging capabilities
 */

class FrontendLogger {
    constructor(config = {}) {
        this.config = {
            // Logging levels
            level: config.level || 'info', // debug, info, warn, error, silent
            enableConsole: config.enableConsole !== false,
            enableRemote: config.enableRemote !== false,
            enableLocalStorage: config.enableLocalStorage !== false,
            
            // Remote logging
            endpoint: config.endpoint || '/api/frontend-logs',
            
            // Local storage settings
            maxLocalLogs: config.maxLocalLogs || 1000,
            localStorageKey: config.localStorageKey || 'frontend_logs',
            
            // Performance monitoring
            enablePerformanceMonitoring: config.enablePerformanceMonitoring !== false,
            
            // User tracking
            enableUserTracking: config.enableUserTracking !== false,
            
            // Error tracking
            enableErrorTracking: config.enableErrorTracking !== false,
            
            // Application info
            appName: config.appName || 'PanaraID Course',
            version: config.version || '1.0.0',
            environment: config.environment || 'production'
        };

        this.levels = {
            debug: 0,
            info: 1,
            warn: 2,
            error: 3,
            silent: 4
        };

        this.logBuffer = [];
        this.sessionId = this.generateSessionId();
        this.userId = null;
        this.deviceInfo = this.getDeviceInfo();
        
        this.init();
    }

    init() {
        if (this.config.enableErrorTracking) {
            this.setupErrorTracking();
        }
        
        if (this.config.enablePerformanceMonitoring) {
            this.setupPerformanceMonitoring();
        }
        
        if (this.config.enableUserTracking) {
            this.setupUserTracking();
        }

        // Setup periodic log flushing
        setInterval(() => this.flushLogs(), 10000); // Flush every 10 seconds
        
        // Flush logs before page unload
        window.addEventListener('beforeunload', () => this.flushLogs());
        
        this.info('FrontendLogger initialized', { config: this.config });
    }

    generateSessionId() {
        return 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    getDeviceInfo() {
        return {
            userAgent: navigator.userAgent,
            platform: navigator.platform,
            language: navigator.language,
            cookieEnabled: navigator.cookieEnabled,
            onLine: navigator.onLine,
            screen: {
                width: screen.width,
                height: screen.height,
                colorDepth: screen.colorDepth
            },
            viewport: {
                width: window.innerWidth,
                height: window.innerHeight
            },
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            timestamp: new Date().toISOString()
        };
    }

    setUserId(userId) {
        this.userId = userId;
        this.info('User ID set', { userId });
    }

    shouldLog(level) {
        return this.levels[level] >= this.levels[this.config.level];
    }

    createLogEntry(level, message, data = {}, context = {}) {
        const timestamp = new Date().toISOString();
        const entry = {
            id: this.generateLogId(),
            timestamp,
            level,
            message,
            data,
            context: {
                ...context,
                sessionId: this.sessionId,
                userId: this.userId,
                url: window.location.href,
                userAgent: navigator.userAgent,
                appName: this.config.appName,
                version: this.config.version,
                environment: this.config.environment
            },
            stackTrace: level === 'error' ? this.getStackTrace() : null
        };

        return entry;
    }

    generateLogId() {
        return Date.now().toString(36) + Math.random().toString(36).substr(2, 5);
    }

    getStackTrace() {
        try {
            throw new Error();
        } catch (e) {
            return e.stack;
        }
    }

    log(level, message, data = {}, context = {}) {
        if (!this.shouldLog(level)) return;

        const entry = this.createLogEntry(level, message, data, context);

        // Console logging
        if (this.config.enableConsole) {
            this.logToConsole(entry);
        }

        // Add to buffer for remote logging
        if (this.config.enableRemote) {
            this.logBuffer.push(entry);
        }

        // Local storage logging
        if (this.config.enableLocalStorage) {
            this.logToLocalStorage(entry);
        }

        // Trigger custom event for external listeners
        window.dispatchEvent(new CustomEvent('frontendLog', { detail: entry }));
    }

    logToConsole(entry) {
        const { level, timestamp, message, data } = entry;
        const prefix = `[${timestamp}] [${level.toUpperCase()}] [${this.config.appName}]`;
        
        switch (level) {
            case 'debug':
                console.debug(prefix, message, data);
                break;
            case 'info':
                console.info(prefix, message, data);
                break;
            case 'warn':
                console.warn(prefix, message, data);
                break;
            case 'error':
                console.error(prefix, message, data, entry.stackTrace);
                break;
            default:
                console.log(prefix, message, data);
        }
    }

    logToLocalStorage(entry) {
        try {
            const existingLogs = JSON.parse(localStorage.getItem(this.config.localStorageKey) || '[]');
            existingLogs.push(entry);
            
            // Keep only the last N logs
            if (existingLogs.length > this.config.maxLocalLogs) {
                existingLogs.splice(0, existingLogs.length - this.config.maxLocalLogs);
            }
            
            localStorage.setItem(this.config.localStorageKey, JSON.stringify(existingLogs));
        } catch (e) {
            console.warn('Failed to store log in localStorage:', e);
        }
    }

    // Logging methods for different levels
    debug(message, data = {}, context = {}) {
        this.log('debug', message, data, context);
    }

    info(message, data = {}, context = {}) {
        this.log('info', message, data, context);
    }

    warn(message, data = {}, context = {}) {
        this.log('warn', message, data, context);
    }

    error(message, data = {}, context = {}) {
        this.log('error', message, data, context);
    }

    // Specialized logging methods
    logAjaxRequest(config, response = null, error = null) {
        const logData = {
            method: config.method,
            url: config.url,
            headers: config.headers,
            data: config.data,
            response: response ? {
                status: response.status,
                statusText: response.statusText,
                headers: response.headers,
                data: response.data
            } : null,
            error: error ? {
                message: error.message,
                code: error.code,
                response: error.response
            } : null,
            duration: Date.now() - (config.startTime || Date.now())
        };

        if (error) {
            this.error('AJAX Request Failed', logData, { type: 'ajax_error' });
        } else {
            this.info('AJAX Request Completed', logData, { type: 'ajax_request' });
        }
    }

    logUserAction(action, element = null, data = {}) {
        const actionData = {
            action,
            element: element ? {
                tagName: element.tagName,
                id: element.id,
                className: element.className,
                textContent: element.textContent?.substring(0, 100)
            } : null,
            ...data
        };

        this.info('User Action', actionData, { type: 'user_action' });
    }

    logPageView(page = window.location.pathname) {
        const pageData = {
            page,
            referrer: document.referrer,
            title: document.title,
            loadTime: performance.timing.loadEventEnd - performance.timing.navigationStart
        };

        this.info('Page View', pageData, { type: 'page_view' });
    }

    logPerformance(metric, value, context = {}) {
        this.info('Performance Metric', { metric, value }, { type: 'performance', ...context });
    }

    // Error tracking setup
    setupErrorTracking() {
        // Global error handler
        window.addEventListener('error', (event) => {
            this.error('JavaScript Error', {
                message: event.message,
                filename: event.filename,
                lineno: event.lineno,
                colno: event.colno,
                error: event.error ? {
                    name: event.error.name,
                    message: event.error.message,
                    stack: event.error.stack
                } : null
            }, { type: 'js_error' });
        });

        // Unhandled promise rejection handler
        window.addEventListener('unhandledrejection', (event) => {
            this.error('Unhandled Promise Rejection', {
                reason: event.reason,
                promise: event.promise
            }, { type: 'promise_rejection' });
        });

        // Resource loading errors
        window.addEventListener('error', (event) => {
            if (event.target !== window) {
                this.error('Resource Loading Error', {
                    element: event.target.tagName,
                    source: event.target.src || event.target.href,
                    type: event.target.type
                }, { type: 'resource_error' });
            }
        }, true);
    }

    // Performance monitoring setup
    setupPerformanceMonitoring() {
        // Page load performance
        window.addEventListener('load', () => {
            setTimeout(() => {
                const perfData = performance.timing;
                const metrics = {
                    dns: perfData.domainLookupEnd - perfData.domainLookupStart,
                    connect: perfData.connectEnd - perfData.connectStart,
                    request: perfData.responseStart - perfData.requestStart,
                    response: perfData.responseEnd - perfData.responseStart,
                    dom: perfData.domContentLoadedEventEnd - perfData.domContentLoadedEventStart,
                    load: perfData.loadEventEnd - perfData.loadEventStart,
                    total: perfData.loadEventEnd - perfData.navigationStart
                };

                this.logPerformance('page_load', metrics);
            }, 0);
        });

        // Navigation performance (for SPAs)
        if ('PerformanceObserver' in window) {
            const observer = new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    if (entry.entryType === 'navigation') {
                        this.logPerformance('navigation', {
                            type: entry.type,
                            duration: entry.duration,
                            domContentLoaded: entry.domContentLoadedEventEnd - entry.domContentLoadedEventStart,
                            loadComplete: entry.loadEventEnd - entry.loadEventStart
                        });
                    }
                }
            });
            observer.observe({ entryTypes: ['navigation'] });
        }
    }

    // User tracking setup
    setupUserTracking() {
        // Click tracking
        document.addEventListener('click', (event) => {
            if (event.target.matches('a, button, [data-track]')) {
                this.logUserAction('click', event.target);
            }
        });

        // Form submission tracking
        document.addEventListener('submit', (event) => {
            this.logUserAction('form_submit', event.target, {
                formId: event.target.id,
                formAction: event.target.action,
                formMethod: event.target.method
            });
        });

        // Page visibility changes
        document.addEventListener('visibilitychange', () => {
            this.logUserAction('visibility_change', null, {
                hidden: document.hidden
            });
        });
    }

    // Flush logs to remote server
    async flushLogs() {
        if (!this.config.enableRemote || this.logBuffer.length === 0) return;

        const logsToSend = [...this.logBuffer];
        this.logBuffer = [];

        try {
            await axios.post(this.config.endpoint, {
                logs: logsToSend,
                session: {
                    sessionId: this.sessionId,
                    userId: this.userId,
                    deviceInfo: this.deviceInfo
                }
            });
        } catch (error) {
            // If sending fails, add logs back to buffer
            this.logBuffer.unshift(...logsToSend);
            console.warn('Failed to send logs to server:', error);
        }
    }

    // Get logs from local storage
    getLocalLogs() {
        try {
            return JSON.parse(localStorage.getItem(this.config.localStorageKey) || '[]');
        } catch (e) {
            return [];
        }
    }

    // Clear local logs
    clearLocalLogs() {
        localStorage.removeItem(this.config.localStorageKey);
        this.info('Local logs cleared');
    }

    // Export logs
    exportLogs(format = 'json') {
        const logs = this.getLocalLogs();
        
        if (format === 'json') {
            return JSON.stringify(logs, null, 2);
        } else if (format === 'csv') {
            return this.convertToCSV(logs);
        }
        
        return logs;
    }

    convertToCSV(logs) {
        if (logs.length === 0) return '';
        
        const headers = ['timestamp', 'level', 'message', 'url', 'userId', 'sessionId'];
        const csvContent = [
            headers.join(','),
            ...logs.map(log => [
                log.timestamp,
                log.level,
                `"${log.message.replace(/"/g, '""')}"`,
                log.context.url,
                log.context.userId || '',
                log.context.sessionId
            ].join(','))
        ].join('\n');
        
        return csvContent;
    }

    // Debug console for development
    createDebugConsole() {
        if (this.config.environment !== 'development') return;

        const console = document.createElement('div');
        console.id = 'frontend-logger-console';
        console.style.cssText = `
            position: fixed;
            bottom: 0;
            right: 0;
            width: 400px;
            height: 300px;
            background: rgba(0, 0, 0, 0.9);
            color: white;
            font-family: monospace;
            font-size: 12px;
            padding: 10px;
            overflow-y: auto;
            z-index: 10000;
            border: 1px solid #333;
            display: none;
        `;

        document.body.appendChild(console);

        // Toggle console with Ctrl+Shift+L
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.shiftKey && e.key === 'L') {
                console.style.display = console.style.display === 'none' ? 'block' : 'none';
            }
        });

        // Listen for logs and display in console
        window.addEventListener('frontendLog', (event) => {
            const log = event.detail;
            const logElement = document.createElement('div');
            logElement.style.cssText = `
                margin-bottom: 5px;
                padding: 5px;
                border-left: 3px solid ${this.getLevelColor(log.level)};
            `;
            logElement.textContent = `[${log.timestamp}] [${log.level.toUpperCase()}] ${log.message}`;
            console.appendChild(logElement);
            console.scrollTop = console.scrollHeight;
        });
    }

    getLevelColor(level) {
        const colors = {
            debug: '#8B949E',
            info: '#2196F3',
            warn: '#FF9800',
            error: '#F44336'
        };
        return colors[level] || '#2196F3';
    }
}

// Create global logger instance
window.logger = new FrontendLogger({
    level: 'info',
    environment: document.querySelector('meta[name="app-env"]')?.content || 'production',
    appName: 'PanaraID Course Platform',
    version: '1.0.0'
});

// Setup AJAX interceptor for automatic logging
if (window.axios) {
    // Request interceptor
    window.axios.interceptors.request.use(
        (config) => {
            config.startTime = Date.now();
            window.logger.debug('AJAX Request Started', {
                method: config.method,
                url: config.url
            });
            return config;
        },
        (error) => {
            window.logger.error('AJAX Request Configuration Error', { error: error.message });
            return Promise.reject(error);
        }
    );

    // Response interceptor
    window.axios.interceptors.response.use(
        (response) => {
            window.logger.logAjaxRequest(response.config, response);
            return response;
        },
        (error) => {
            window.logger.logAjaxRequest(error.config, null, error);
            return Promise.reject(error);
        }
    );
}

// Log initial page load
document.addEventListener('DOMContentLoaded', () => {
    window.logger.logPageView();
    
    // Set user ID if available
    const userIdMeta = document.querySelector('meta[name="user-id"]');
    if (userIdMeta) {
        window.logger.setUserId(userIdMeta.content);
    }
});

// Export for module systems
export { FrontendLogger };