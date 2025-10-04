import './bootstrap';
import './pwa-install';

// Global message handling for chat (without real-time broadcasting)
window.currentUserId = null;

// Placeholder functions for compatibility (no broadcasting)
window.handleNewMessage = function (chatId, callback) {
    // No broadcasting implementation - messages will be updated via page refresh or manual refresh
    console.log('Real-time messaging disabled. Please refresh the page to see new messages.');
};

// Function to leave a chat channel (placeholder)
window.leaveChatChannel = function (chatId) {
    // No broadcasting implementation
    console.log('Real-time messaging disabled.');
};

// PWA Service Worker Registration
if ('serviceWorker' in navigator) {
    window.addEventListener('load', async () => {
        try {
            const registration = await navigator.serviceWorker.register('/sw.js');
            console.log('✓ Service Worker registered successfully:', registration.scope);

            // Check for updates
            registration.addEventListener('updatefound', () => {
                const newWorker = registration.installing;
                newWorker.addEventListener('statechange', () => {
                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                        // New content is available
                        showUpdateAvailable();
                    }
                });
            });
        } catch (error) {
            console.log('✗ Service Worker registration failed:', error);
        }
    });
}

// PWA Install Prompt
let deferredPrompt;
let installButton = null;

window.addEventListener('beforeinstallprompt', (e) => {
    console.log('✓ PWA install prompt available');
    e.preventDefault();
    deferredPrompt = e;
    showInstallButton();
});

// Show install button
function showInstallButton() {
    // Jika pada route chat maka jangan tampilkan tombol install
    if (window.location.pathname.startsWith('/chat')) {
        return;
    }

    // Create install button if it doesn't exist
    if (!installButton && document.body) {
        installButton = document.createElement('button');
        installButton.innerHTML = `
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="7,10 12,15 17,10"/>
                <line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
            Install App
        `;
        installButton.className = 'pwa-install-btn';
        installButton.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #000;
            color: white;
            border: none;
            border-radius: 50px;
            padding: 12px 20px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            gap: 8px;
            z-index: 1000;
            transition: all 0.3s ease;
            transform: translateY(100px);
            opacity: 0;
        `;

        installButton.addEventListener('click', installPWA);
        document.body.appendChild(installButton);

        // Animate in
        setTimeout(() => {
            installButton.style.transform = 'translateY(0)';
            installButton.style.opacity = '1';
        }, 100);
    }
}

// Install PWA function
async function installPWA() {
    if (!deferredPrompt) return;

    try {
        const result = await deferredPrompt.prompt();
        console.log('✓ PWA install prompt result:', result.outcome);

        if (result.outcome === 'accepted') {
            hideInstallButton();
        }

        deferredPrompt = null;
    } catch (error) {
        console.log('✗ PWA install error:', error);
    }
}

// Hide install button
function hideInstallButton() {
    if (installButton) {
        installButton.style.transform = 'translateY(100px)';
        installButton.style.opacity = '0';
        setTimeout(() => {
            if (installButton && installButton.parentNode) {
                installButton.parentNode.removeChild(installButton);
                installButton = null;
            }
        }, 300);
    }
}

// Hide install button when app is installed
window.addEventListener('appinstalled', () => {
    console.log('✓ PWA was installed');
    hideInstallButton();
});

// Show update available notification
function showUpdateAvailable() {
    // Create update notification
    const updateNotification = document.createElement('div');
    updateNotification.innerHTML = `
        <div style="padding: 16px; background: #000; color: white; position: fixed; top: 20px; right: 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); z-index: 1001; max-width: 300px;">
            <p style="margin: 0 0 12px 0; font-size: 14px;">Update tersedia untuk aplikasi ini</p>
            <button onclick="window.location.reload()" style="background: white; color: black; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 500;">
                Update Sekarang
            </button>
            <button onclick="this.closest('div').remove()" style="background: transparent; color: white; border: 1px solid rgba(255,255,255,0.3); padding: 8px 16px; border-radius: 4px; cursor: pointer; font-size: 12px; margin-left: 8px;">
                Nanti
            </button>
        </div>
    `;

    document.body.appendChild(updateNotification);

    // Auto hide after 3 seconds
    setTimeout(() => {
        if (updateNotification.parentNode) {
            updateNotification.parentNode.removeChild(updateNotification);
        }
    }, 3000);
}

// Online/Offline status
window.addEventListener('online', () => {
    console.log('✓ Back online');
    // Optionally show a notification
});

window.addEventListener('offline', () => {
    console.log('✗ Gone offline');
    // Optionally show a notification
});

Notification.requestPermission().then(permission => {
    if (permission !== 'granted') {
        alert('⚠️ Izin notifikasi ditolak. Silakan aktifkan notifikasi untuk pengalaman terbaik.');
        return;
    }
    console.log('✓ Notification permission granted.');

    async function getToken() {
        let token = localStorage.getItem('user_token');
        if (!token) {
            try {
                const res = await fetch('/api/user/token', {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin'
                });
                if (!res.ok) throw new Error('Failed to get token');
                const data = await res.json();
                token = data.token;
                localStorage.setItem('user_token', token);
            } catch (err) {
                console.error('✗ Token fetch error:', err);
                return null;
            }
        }
        return token;
    }

    async function fetchNotifications() {
        const token = await getToken();
        if (!token) return;
        try {
            const res = await fetch('/api/notifications', {
                headers: { 'Accept': 'application/json', 'Authorization': 'Bearer ' + token }
            });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const data = await res.json();
            console.log('✓ Notification:', data);
            const notif = data.notification;
            if (notif) {
                console.log('✓ Notification shown:', notif);
            }
        } catch (err) {
            console.error('✗ Notification fetch error:', err);
            if (err.message.includes('401')) localStorage.removeItem('user_token');
        }
    }

    setInterval(fetchNotifications, 3000);
});
