/**
 * Advanced Debug Console for Frontend Logging
 * Provides interactive debugging interface with filtering, search, and export capabilities
 */

class DebugConsole {
    constructor(logger) {
        this.logger = logger;
        this.isVisible = false;
        this.logs = [];
        this.filteredLogs = [];
        this.filters = {
            level: 'all',
            search: '',
            startDate: '',
            endDate: '',
            userId: '',
            type: 'all'
        };
        
        this.init();
    }

    init() {
        this.createConsoleHTML();
        this.setupEventListeners();
        this.setupKeyboardShortcuts();
        
        // Listen for new logs
        window.addEventListener('frontendLog', (event) => {
            this.addLog(event.detail);
        });
        
        // Load existing logs from localStorage
        this.loadExistingLogs();
    }

    createConsoleHTML() {
        const consoleHTML = `
            <div id="debug-console" class="debug-console hidden">
                <div class="console-header">
                    <div class="console-title">
                        <h3>🔍 Frontend Debug Console</h3>
                        <div class="console-stats">
                            <span class="log-count">0 logs</span>
                            <span class="session-id">Session: ${this.logger.sessionId.slice(-8)}</span>
                        </div>
                    </div>
                    <div class="console-controls">
                        <button id="clear-logs" class="btn btn-sm">Clear</button>
                        <button id="export-logs" class="btn btn-sm">Export</button>
                        <button id="toggle-console" class="btn btn-sm">Minimize</button>
                        <button id="close-console" class="btn btn-sm">×</button>
                    </div>
                </div>
                
                <div class="console-filters">
                    <div class="filter-row">
                        <select id="level-filter" class="filter-select">
                            <option value="all">All Levels</option>
                            <option value="debug">Debug</option>
                            <option value="info">Info</option>
                            <option value="warn">Warn</option>
                            <option value="error">Error</option>
                        </select>
                        
                        <select id="type-filter" class="filter-select">
                            <option value="all">All Types</option>
                            <option value="js_error">JS Errors</option>
                            <option value="ajax_request">AJAX</option>
                            <option value="user_action">User Actions</option>
                            <option value="performance">Performance</option>
                            <option value="page_view">Page Views</option>
                        </select>
                        
                        <input type="search" id="search-filter" placeholder="Search logs..." class="filter-input">
                    </div>
                    
                    <div class="filter-row">
                        <input type="datetime-local" id="start-date-filter" class="filter-input">
                        <input type="datetime-local" id="end-date-filter" class="filter-input">
                        <input type="text" id="user-filter" placeholder="User ID" class="filter-input">
                        <button id="reset-filters" class="btn btn-sm">Reset</button>
                    </div>
                </div>
                
                <div class="console-content">
                    <div id="log-container" class="log-container"></div>
                </div>
                
                <div class="console-footer">
                    <div class="console-info">
                        <span>Ctrl+Shift+L to toggle | Ctrl+Shift+C to clear | Ctrl+Shift+E to export</span>
                    </div>
                </div>
            </div>
        `;

        // Create and inject CSS
        const style = document.createElement('style');
        style.textContent = this.getConsoleCSS();
        document.head.appendChild(style);

        // Inject HTML
        document.body.insertAdjacentHTML('beforeend', consoleHTML);
        this.consoleElement = document.getElementById('debug-console');
    }

    getConsoleCSS() {
        return `
            .debug-console {
                position: fixed;
                top: 20px;
                right: 20px;
                width: 800px;
                height: 600px;
                background: #1e1e1e;
                border: 1px solid #333;
                border-radius: 8px;
                font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
                font-size: 12px;
                color: #fff;
                z-index: 999999;
                display: flex;
                flex-direction: column;
                box-shadow: 0 10px 30px rgba(0,0,0,0.5);
                resize: both;
                overflow: hidden;
                min-width: 400px;
                min-height: 300px;
            }

            .debug-console.hidden {
                display: none;
            }

            .debug-console.minimized {
                height: 40px;
                overflow: hidden;
            }

            .console-header {
                background: #2d2d2d;
                padding: 8px 12px;
                border-bottom: 1px solid #333;
                display: flex;
                justify-content: space-between;
                align-items: center;
                user-select: none;
                cursor: move;
            }

            .console-title h3 {
                margin: 0;
                font-size: 14px;
                color: #61dafb;
            }

            .console-stats {
                display: flex;
                gap: 15px;
                font-size: 11px;
                color: #888;
            }

            .console-controls {
                display: flex;
                gap: 5px;
            }

            .btn {
                background: #404040;
                border: 1px solid #555;
                color: #fff;
                padding: 4px 8px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 11px;
            }

            .btn:hover {
                background: #505050;
            }

            .btn-sm {
                padding: 2px 6px;
                font-size: 10px;
            }

            .console-filters {
                background: #252525;
                padding: 8px 12px;
                border-bottom: 1px solid #333;
            }

            .filter-row {
                display: flex;
                gap: 8px;
                align-items: center;
                margin-bottom: 5px;
            }

            .filter-row:last-child {
                margin-bottom: 0;
            }

            .filter-select, .filter-input {
                background: #1e1e1e;
                border: 1px solid #444;
                color: #fff;
                padding: 4px 8px;
                border-radius: 3px;
                font-size: 11px;
            }

            .filter-select {
                min-width: 100px;
            }

            .filter-input {
                flex: 1;
                min-width: 80px;
            }

            .console-content {
                flex: 1;
                overflow: hidden;
                display: flex;
                flex-direction: column;
            }

            .log-container {
                flex: 1;
                overflow-y: auto;
                padding: 0;
            }

            .log-entry {
                padding: 6px 12px;
                border-bottom: 1px solid #2a2a2a;
                font-size: 11px;
                line-height: 1.4;
                cursor: pointer;
                word-wrap: break-word;
            }

            .log-entry:hover {
                background: rgba(255,255,255,0.05);
            }

            .log-entry.expanded {
                background: rgba(255,255,255,0.1);
            }

            .log-entry.level-debug {
                border-left: 3px solid #8B949E;
            }

            .log-entry.level-info {
                border-left: 3px solid #2196F3;
            }

            .log-entry.level-warn {
                border-left: 3px solid #FF9800;
                background: rgba(255, 152, 0, 0.05);
            }

            .log-entry.level-error {
                border-left: 3px solid #F44336;
                background: rgba(244, 67, 54, 0.05);
            }

            .log-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin-bottom: 4px;
            }

            .log-basic {
                flex: 1;
            }

            .log-timestamp {
                color: #888;
                font-size: 10px;
                white-space: nowrap;
                margin-left: 10px;
            }

            .log-level {
                display: inline-block;
                padding: 1px 4px;
                border-radius: 2px;
                font-size: 9px;
                font-weight: bold;
                text-transform: uppercase;
                margin-right: 6px;
                min-width: 40px;
                text-align: center;
            }

            .log-level.debug { background: #8B949E; color: #000; }
            .log-level.info { background: #2196F3; color: #fff; }
            .log-level.warn { background: #FF9800; color: #000; }
            .log-level.error { background: #F44336; color: #fff; }

            .log-message {
                color: #fff;
                margin-bottom: 2px;
            }

            .log-details {
                display: none;
                margin-top: 8px;
                padding: 8px;
                background: rgba(0,0,0,0.3);
                border-radius: 4px;
                font-size: 10px;
            }

            .log-entry.expanded .log-details {
                display: block;
            }

            .log-data {
                color: #98C379;
                white-space: pre-wrap;
                margin-bottom: 6px;
            }

            .log-context {
                color: #E06C75;
            }

            .log-stack {
                color: #D19A66;
                margin-top: 6px;
                white-space: pre-wrap;
                font-size: 9px;
            }

            .console-footer {
                background: #2d2d2d;
                padding: 4px 12px;
                border-top: 1px solid #333;
                font-size: 10px;
                color: #888;
            }

            .log-container::-webkit-scrollbar {
                width: 8px;
            }

            .log-container::-webkit-scrollbar-track {
                background: #1e1e1e;
            }

            .log-container::-webkit-scrollbar-thumb {
                background: #444;
                border-radius: 4px;
            }

            .log-container::-webkit-scrollbar-thumb:hover {
                background: #555;
            }

            @media (max-width: 1024px) {
                .debug-console {
                    width: 90vw;
                    height: 70vh;
                }
            }
        `;
    }

    setupEventListeners() {
        // Console controls
        document.getElementById('clear-logs').addEventListener('click', () => this.clearLogs());
        document.getElementById('export-logs').addEventListener('click', () => this.exportLogs());
        document.getElementById('toggle-console').addEventListener('click', () => this.toggleMinimized());
        document.getElementById('close-console').addEventListener('click', () => this.hide());

        // Filters
        document.getElementById('level-filter').addEventListener('change', (e) => {
            this.filters.level = e.target.value;
            this.applyFilters();
        });

        document.getElementById('type-filter').addEventListener('change', (e) => {
            this.filters.type = e.target.value;
            this.applyFilters();
        });

        document.getElementById('search-filter').addEventListener('input', (e) => {
            this.filters.search = e.target.value;
            this.applyFilters();
        });

        document.getElementById('start-date-filter').addEventListener('change', (e) => {
            this.filters.startDate = e.target.value;
            this.applyFilters();
        });

        document.getElementById('end-date-filter').addEventListener('change', (e) => {
            this.filters.endDate = e.target.value;
            this.applyFilters();
        });

        document.getElementById('user-filter').addEventListener('input', (e) => {
            this.filters.userId = e.target.value;
            this.applyFilters();
        });

        document.getElementById('reset-filters').addEventListener('click', () => this.resetFilters());

        // Make console draggable
        this.makeConsoleResizable();
    }

    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.shiftKey) {
                switch (e.key) {
                    case 'L':
                        e.preventDefault();
                        this.toggle();
                        break;
                    case 'C':
                        e.preventDefault();
                        if (this.isVisible) this.clearLogs();
                        break;
                    case 'E':
                        e.preventDefault();
                        if (this.isVisible) this.exportLogs();
                        break;
                }
            }
        });
    }

    makeConsoleResizable() {
        const header = this.consoleElement.querySelector('.console-header');
        let isDragging = false;
        let dragOffset = { x: 0, y: 0 };

        header.addEventListener('mousedown', (e) => {
            isDragging = true;
            dragOffset.x = e.clientX - this.consoleElement.offsetLeft;
            dragOffset.y = e.clientY - this.consoleElement.offsetTop;
            document.addEventListener('mousemove', handleDrag);
            document.addEventListener('mouseup', stopDrag);
        });

        const handleDrag = (e) => {
            if (!isDragging) return;
            this.consoleElement.style.left = (e.clientX - dragOffset.x) + 'px';
            this.consoleElement.style.top = (e.clientY - dragOffset.y) + 'px';
            this.consoleElement.style.right = 'auto';
        };

        const stopDrag = () => {
            isDragging = false;
            document.removeEventListener('mousemove', handleDrag);
            document.removeEventListener('mouseup', stopDrag);
        };
    }

    loadExistingLogs() {
        const existingLogs = this.logger.getLocalLogs();
        this.logs = existingLogs;
        this.applyFilters();
    }

    addLog(log) {
        this.logs.push(log);
        if (this.logs.length > 5000) {
            this.logs = this.logs.slice(-4000); // Keep last 4000 logs
        }
        this.applyFilters();
        this.updateStats();
    }

    applyFilters() {
        this.filteredLogs = this.logs.filter(log => {
            // Level filter
            if (this.filters.level !== 'all' && log.level !== this.filters.level) {
                return false;
            }

            // Type filter
            if (this.filters.type !== 'all' && log.context.type !== this.filters.type) {
                return false;
            }

            // Search filter
            if (this.filters.search) {
                const searchTerm = this.filters.search.toLowerCase();
                const searchText = (log.message + JSON.stringify(log.data)).toLowerCase();
                if (!searchText.includes(searchTerm)) {
                    return false;
                }
            }

            // Date filters
            if (this.filters.startDate) {
                const logDate = new Date(log.timestamp);
                const startDate = new Date(this.filters.startDate);
                if (logDate < startDate) return false;
            }

            if (this.filters.endDate) {
                const logDate = new Date(log.timestamp);
                const endDate = new Date(this.filters.endDate);
                if (logDate > endDate) return false;
            }

            // User filter
            if (this.filters.userId && log.context.userId !== this.filters.userId) {
                return false;
            }

            return true;
        });

        this.renderLogs();
        this.updateStats();
    }

    renderLogs() {
        const container = document.getElementById('log-container');
        container.innerHTML = '';

        this.filteredLogs.slice(-500).forEach(log => {
            const logElement = this.createLogElement(log);
            container.appendChild(logElement);
        });

        // Auto-scroll to bottom
        container.scrollTop = container.scrollHeight;
    }

    createLogElement(log) {
        const element = document.createElement('div');
        element.className = `log-entry level-${log.level}`;
        
        const timestamp = new Date(log.timestamp).toLocaleTimeString();
        const contextInfo = log.context.type ? `[${log.context.type}]` : '';
        
        element.innerHTML = `
            <div class="log-header">
                <div class="log-basic">
                    <span class="log-level ${log.level}">${log.level}</span>
                    <span class="log-message">${this.escapeHtml(log.message)} ${contextInfo}</span>
                </div>
                <span class="log-timestamp">${timestamp}</span>
            </div>
            <div class="log-details">
                ${Object.keys(log.data).length > 0 ? `<div class="log-data">${this.escapeHtml(JSON.stringify(log.data, null, 2))}</div>` : ''}
                <div class="log-context">Context: ${this.escapeHtml(JSON.stringify(log.context, null, 2))}</div>
                ${log.stackTrace ? `<div class="log-stack">Stack Trace:\n${this.escapeHtml(log.stackTrace)}</div>` : ''}
            </div>
        `;

        element.addEventListener('click', () => {
            element.classList.toggle('expanded');
        });

        return element;
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    updateStats() {
        const countElement = document.querySelector('.log-count');
        if (countElement) {
            countElement.textContent = `${this.filteredLogs.length} / ${this.logs.length} logs`;
        }
    }

    resetFilters() {
        this.filters = {
            level: 'all',
            search: '',
            startDate: '',
            endDate: '',
            userId: '',
            type: 'all'
        };

        // Reset UI
        document.getElementById('level-filter').value = 'all';
        document.getElementById('type-filter').value = 'all';
        document.getElementById('search-filter').value = '';
        document.getElementById('start-date-filter').value = '';
        document.getElementById('end-date-filter').value = '';
        document.getElementById('user-filter').value = '';

        this.applyFilters();
    }

    clearLogs() {
        this.logs = [];
        this.filteredLogs = [];
        this.logger.clearLocalLogs();
        this.renderLogs();
        this.updateStats();
    }

    exportLogs() {
        const format = prompt('Export format (json/csv):', 'json');
        if (!format) return;

        const data = this.logger.exportLogs(format);
        const blob = new Blob([data], { 
            type: format === 'json' ? 'application/json' : 'text/csv' 
        });
        
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `frontend-logs-${new Date().toISOString().split('T')[0]}.${format}`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    toggle() {
        if (this.isVisible) {
            this.hide();
        } else {
            this.show();
        }
    }

    show() {
        this.consoleElement.classList.remove('hidden');
        this.isVisible = true;
        this.loadExistingLogs();
    }

    hide() {
        this.consoleElement.classList.add('hidden');
        this.isVisible = false;
    }

    toggleMinimized() {
        this.consoleElement.classList.toggle('minimized');
    }
}

// Initialize debug console when logger is available
if (window.logger) {
    window.debugConsole = new DebugConsole(window.logger);
} else {
    window.addEventListener('load', () => {
        if (window.logger) {
            window.debugConsole = new DebugConsole(window.logger);
        }
    });
}

// Export for module systems
export { DebugConsole };