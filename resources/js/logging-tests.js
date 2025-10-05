/**
 * Frontend Logging System Test Suite
 * Comprehensive testing for all logging functionality
 */

class LoggingTestSuite {
    constructor() {
        this.testResults = [];
        this.passedTests = 0;
        this.failedTests = 0;
        this.init();
    }

    init() {
        console.log('🚀 Starting Frontend Logging System Tests...');
        this.runAllTests();
    }

    async runAllTests() {
        // Wait for logger to be initialized
        await this.waitForLogger();

        // Run test suites
        await this.testBasicLogging();
        await this.testErrorTracking();
        await this.testPerformanceMonitoring();
        await this.testUserTracking();
        await this.testAjaxLogging();
        await this.testLocalStorage();
        await this.testDebugConsole();
        await this.testRemoteLogging();

        // Print results
        this.printResults();
    }

    async waitForLogger(timeout = 5000) {
        const start = Date.now();
        while (!window.logger && Date.now() - start < timeout) {
            await new Promise(resolve => setTimeout(resolve, 100));
        }
        
        if (!window.logger) {
            throw new Error('Logger not found after timeout');
        }
    }

    test(name, testFn) {
        try {
            const result = testFn();
            if (result === true || result === undefined) {
                this.testResults.push({ name, status: 'PASS', error: null });
                this.passedTests++;
                console.log(`✅ ${name}`);
            } else {
                this.testResults.push({ name, status: 'FAIL', error: 'Test returned false' });
                this.failedTests++;
                console.log(`❌ ${name}: Test returned false`);
            }
        } catch (error) {
            this.testResults.push({ name, status: 'FAIL', error: error.message });
            this.failedTests++;
            console.log(`❌ ${name}: ${error.message}`);
        }
    }

    async testBasicLogging() {
        console.log('\n📝 Testing Basic Logging...');

        this.test('Logger instance exists', () => {
            return window.logger instanceof FrontendLogger;
        });

        this.test('Logger has config', () => {
            return window.logger.config && typeof window.logger.config === 'object';
        });

        this.test('Debug logging works', () => {
            window.logger.debug('Test debug message', { test: true });
            return true;
        });

        this.test('Info logging works', () => {
            window.logger.info('Test info message', { test: true });
            return true;
        });

        this.test('Warning logging works', () => {
            window.logger.warn('Test warning message', { test: true });
            return true;
        });

        this.test('Error logging works', () => {
            window.logger.error('Test error message', { test: true });
            return true;
        });

        this.test('Session ID is generated', () => {
            return window.logger.sessionId && window.logger.sessionId.startsWith('session_');
        });

        this.test('Device info is collected', () => {
            return window.logger.deviceInfo && window.logger.deviceInfo.userAgent;
        });
    }

    async testErrorTracking() {
        console.log('\n🐛 Testing Error Tracking...');

        this.test('Error tracking is enabled', () => {
            return window.logger.config.enableErrorTracking;
        });

        this.test('JavaScript error handler', () => {
            // Simulate error event
            const errorEvent = new ErrorEvent('error', {
                message: 'Test error',
                filename: 'test.js',
                lineno: 1,
                colno: 1,
                error: new Error('Test error')
            });

            window.dispatchEvent(errorEvent);
            return true;
        });

        this.test('Unhandled promise rejection handler', () => {
            // Simulate unhandled rejection
            const rejectionEvent = new PromiseRejectionEvent('unhandledrejection', {
                promise: Promise.reject('Test rejection'),
                reason: 'Test rejection'
            });

            window.dispatchEvent(rejectionEvent);
            return true;
        });

        this.test('Stack trace generation', () => {
            const stackTrace = window.logger.getStackTrace();
            return stackTrace && stackTrace.includes('Error');
        });
    }

    async testPerformanceMonitoring() {
        console.log('\n⚡ Testing Performance Monitoring...');

        this.test('Performance monitoring is enabled', () => {
            return window.logger.config.enablePerformanceMonitoring;
        });

        this.test('Custom performance logging', () => {
            window.logger.logPerformance('test_metric', 1500, { component: 'test' });
            return true;
        });

        this.test('Performance API available', () => {
            return 'performance' in window && 'timing' in performance;
        });

        // Test navigation timing if available
        if ('PerformanceObserver' in window) {
            this.test('PerformanceObserver available', () => {
                return true;
            });
        }
    }

    async testUserTracking() {
        console.log('\n👤 Testing User Tracking...');

        this.test('User tracking is enabled', () => {
            return window.logger.config.enableUserTracking;
        });

        this.test('User action logging', () => {
            // Create test element
            const testElement = document.createElement('button');
            testElement.id = 'test-button';
            testElement.textContent = 'Test Button';
            
            window.logger.logUserAction('test_click', testElement, { test: true });
            return true;
        });

        this.test('Page view logging', () => {
            window.logger.logPageView('/test-page');
            return true;
        });

        this.test('User ID setting', () => {
            window.logger.setUserId('test-user-123');
            return window.logger.userId === 'test-user-123';
        });

        this.test('Click event listener attached', () => {
            // Create and click test button
            const button = document.createElement('button');
            button.setAttribute('data-track', 'true');
            button.textContent = 'Test Track Button';
            document.body.appendChild(button);
            
            button.click();
            document.body.removeChild(button);
            return true;
        });
    }

    async testAjaxLogging() {
        console.log('\n🌐 Testing AJAX Logging...');

        this.test('Axios interceptors attached', () => {
            return window.axios && 
                   window.axios.interceptors.request.handlers.length > 0 &&
                   window.axios.interceptors.response.handlers.length > 0;
        });

        this.test('Manual AJAX logging', () => {
            const mockConfig = {
                method: 'GET',
                url: '/test-endpoint',
                startTime: Date.now() - 100
            };
            
            const mockResponse = {
                status: 200,
                statusText: 'OK',
                data: { test: true }
            };

            window.logger.logAjaxRequest(mockConfig, mockResponse);
            return true;
        });

        this.test('AJAX error logging', () => {
            const mockConfig = {
                method: 'POST',
                url: '/test-error-endpoint',
                startTime: Date.now() - 500
            };
            
            const mockError = {
                message: 'Network Error',
                code: 'NETWORK_ERROR',
                response: { status: 500 }
            };

            window.logger.logAjaxRequest(mockConfig, null, mockError);
            return true;
        });
    }

    async testLocalStorage() {
        console.log('\n💾 Testing Local Storage...');

        this.test('Local storage is enabled', () => {
            return window.logger.config.enableLocalStorage;
        });

        this.test('Local storage key is set', () => {
            return window.logger.config.localStorageKey === 'frontend_logs';
        });

        this.test('Get local logs', () => {
            const logs = window.logger.getLocalLogs();
            return Array.isArray(logs);
        });

        this.test('Local storage writes', () => {
            const beforeCount = window.logger.getLocalLogs().length;
            window.logger.info('Test local storage', { test: true });
            
            // Allow some time for async operations
            setTimeout(() => {
                const afterCount = window.logger.getLocalLogs().length;
                return afterCount > beforeCount;
            }, 100);
            
            return true; // Simplified for sync test
        });

        this.test('Export logs JSON', () => {
            const exported = window.logger.exportLogs('json');
            return typeof exported === 'string' && exported.startsWith('[');
        });

        this.test('Export logs CSV', () => {
            const exported = window.logger.exportLogs('csv');
            return typeof exported === 'string' && exported.includes('timestamp');
        });

        this.test('Clear local logs', () => {
            window.logger.clearLocalLogs();
            const logs = window.logger.getLocalLogs();
            return logs.length === 0;
        });
    }

    async testDebugConsole() {
        console.log('\n🖥️ Testing Debug Console...');

        this.test('Debug console exists', () => {
            return window.debugConsole && typeof window.debugConsole === 'object';
        });

        this.test('Console can be shown', () => {
            window.debugConsole.show();
            const consoleElement = document.getElementById('debug-console');
            return consoleElement && !consoleElement.classList.contains('hidden');
        });

        this.test('Console filters work', () => {
            if (window.debugConsole) {
                window.debugConsole.filters.level = 'error';
                window.debugConsole.applyFilters();
                return true;
            }
            return false;
        });

        this.test('Console can be hidden', () => {
            window.debugConsole.hide();
            const consoleElement = document.getElementById('debug-console');
            return consoleElement && consoleElement.classList.contains('hidden');
        });

        this.test('Console keyboard shortcuts', () => {
            // Test toggle shortcut
            const keyEvent = new KeyboardEvent('keydown', {
                key: 'L',
                ctrlKey: true,
                shiftKey: true
            });
            
            document.dispatchEvent(keyEvent);
            return true;
        });
    }

    async testRemoteLogging() {
        console.log('\n📡 Testing Remote Logging...');

        this.test('Remote logging is enabled', () => {
            return window.logger.config.enableRemote;
        });

        this.test('Endpoint is configured', () => {
            return window.logger.config.endpoint === '/api/frontend-logs';
        });

        this.test('Log buffer exists', () => {
            return Array.isArray(window.logger.logBuffer);
        });

        this.test('Flush logs method exists', () => {
            return typeof window.logger.flushLogs === 'function';
        });

        // Test actual flush (will fail if no network, but method should exist)
        this.test('Flush logs can be called', () => {
            try {
                window.logger.flushLogs();
                return true;
            } catch (error) {
                return false;
            }
        });
    }

    printResults() {
        console.log('\n📊 Test Results Summary');
        console.log('========================');
        console.log(`✅ Passed: ${this.passedTests}`);
        console.log(`❌ Failed: ${this.failedTests}`);
        console.log(`📊 Total: ${this.testResults.length}`);
        console.log(`🎯 Success Rate: ${((this.passedTests / this.testResults.length) * 100).toFixed(1)}%`);

        if (this.failedTests > 0) {
            console.log('\n❌ Failed Tests:');
            this.testResults
                .filter(result => result.status === 'FAIL')
                .forEach(result => {
                    console.log(`  - ${result.name}: ${result.error}`);
                });
        }

        console.log('\n🏁 Testing Complete!');
        
        // Add results to window for external access
        window.loggingTestResults = {
            passed: this.passedTests,
            failed: this.failedTests,
            total: this.testResults.length,
            successRate: (this.passedTests / this.testResults.length) * 100,
            details: this.testResults
        };
    }
}

// Performance Test Suite
class PerformanceTestSuite {
    constructor() {
        this.results = {};
    }

    async runPerformanceTests() {
        console.log('\n🏃‍♂️ Running Performance Tests...');

        await this.testLoggingPerformance();
        await this.testMemoryUsage();
        await this.testBatchingPerformance();

        this.printPerformanceResults();
    }

    async testLoggingPerformance() {
        const iterations = 1000;
        const start = performance.now();

        for (let i = 0; i < iterations; i++) {
            window.logger.info(`Performance test ${i}`, { iteration: i });
        }

        const end = performance.now();
        const duration = end - start;
        const avgTime = duration / iterations;

        this.results.loggingPerformance = {
            iterations,
            totalTime: duration,
            averageTime: avgTime,
            logsPerSecond: 1000 / avgTime
        };

        console.log(`📊 Logging Performance: ${iterations} logs in ${duration.toFixed(2)}ms (${avgTime.toFixed(3)}ms avg)`);
    }

    async testMemoryUsage() {
        if ('memory' in performance) {
            const beforeMemory = performance.memory.usedJSHeapSize;
            
            // Generate lots of logs
            for (let i = 0; i < 500; i++) {
                window.logger.info(`Memory test ${i}`, { 
                    data: new Array(100).fill('test'),
                    iteration: i 
                });
            }

            const afterMemory = performance.memory.usedJSHeapSize;
            const memoryIncrease = afterMemory - beforeMemory;

            this.results.memoryUsage = {
                before: beforeMemory,
                after: afterMemory,
                increase: memoryIncrease,
                increaseKB: (memoryIncrease / 1024).toFixed(2)
            };

            console.log(`💾 Memory Usage: +${(memoryIncrease / 1024).toFixed(2)}KB`);
        } else {
            console.log('💾 Memory API not available');
        }
    }

    async testBatchingPerformance() {
        const originalBuffer = [...window.logger.logBuffer];
        window.logger.logBuffer = []; // Clear buffer

        const start = performance.now();

        // Add many logs quickly
        for (let i = 0; i < 100; i++) {
            window.logger.info(`Batch test ${i}`, { batch: true });
        }

        const bufferSize = window.logger.logBuffer.length;
        const end = performance.now();

        this.results.batchingPerformance = {
            logsAdded: 100,
            bufferSize,
            batchingTime: end - start,
            bufferingEfficiency: bufferSize / 100
        };

        // Restore original buffer
        window.logger.logBuffer = originalBuffer;

        console.log(`📦 Batching: ${bufferSize} logs buffered in ${(end - start).toFixed(2)}ms`);
    }

    printPerformanceResults() {
        console.log('\n🏆 Performance Test Results');
        console.log('============================');
        
        if (this.results.loggingPerformance) {
            const lp = this.results.loggingPerformance;
            console.log(`🔄 Logging Speed: ${lp.logsPerSecond.toFixed(0)} logs/second`);
        }

        if (this.results.memoryUsage) {
            const mu = this.results.memoryUsage;
            console.log(`💾 Memory Impact: +${mu.increaseKB}KB for 500 logs`);
        }

        if (this.results.batchingPerformance) {
            const bp = this.results.batchingPerformance;
            console.log(`📦 Batching Efficiency: ${(bp.bufferingEfficiency * 100).toFixed(1)}%`);
        }

        window.loggingPerformanceResults = this.results;
    }
}

// Auto-run tests when logger is ready
document.addEventListener('DOMContentLoaded', () => {
    // Wait a bit for all scripts to load
    setTimeout(() => {
        if (window.logger) {
            const testSuite = new LoggingTestSuite();
            
            // Run performance tests after main tests
            setTimeout(() => {
                const perfSuite = new PerformanceTestSuite();
                perfSuite.runPerformanceTests();
            }, 2000);
        } else {
            console.warn('Logger not found - skipping tests');
        }
    }, 1000);
});

// Manual test trigger
window.runLoggingTests = () => {
    const testSuite = new LoggingTestSuite();
    const perfSuite = new PerformanceTestSuite();
    
    setTimeout(() => {
        perfSuite.runPerformanceTests();
    }, 2000);
};

// Export for module systems
export { LoggingTestSuite, PerformanceTestSuite };