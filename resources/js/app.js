import './bootstrap';
import './pwa-install';

// Global message handling for chat (without real-time broadcasting)
window.currentUserId = null;

// Placeholder functions for compatibility (no broadcasting)
window.handleNewMessage = function(chatId, callback) {
    // No broadcasting implementation - messages will be updated via page refresh or manual refresh
    console.log('Real-time messaging disabled. Please refresh the page to see new messages.');
};

// Function to leave a chat channel (placeholder)
window.leaveChatChannel = function(chatId) {
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
    
    // Auto hide after 10 seconds
    setTimeout(() => {
        if (updateNotification.parentNode) {
            updateNotification.parentNode.removeChild(updateNotification);
        }
    }, 10000);
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
    if (permission === 'granted') {
        console.log('✓ Notification permission granted.');

        function showNotification() {
            fetch('/api/notifications', {
                headers: {
                    'Accept': 'application/json',
                    "Content-Type": "application/json",
                    'Authorization': 'Bearer ' + '2f1eecdc6eefcd560d6cb8b7297d7015484fee7dd30fb6829d1c80c629430a6c'
                }
            })
            .then(response => {
                console.log('✓ Fetching notifications from API');
                console.log('User token:', localStorage.getItem('user_token'));
                console.log('Response URL:', response.url);
                console.log('Response type:', response.type);
                console.log('Headers:', [...response.headers]);
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                console.log('Response body:', response.body);
                console.log('Response ok:', response.ok);
                console.log('Response ' + response.type);
                return response.json(); // Parse the response as JSON
            })
            .then(data => {
                console.log('✓ Fetched notifications:', data);
            })
            .catch(error => {
                console.error('✗ Failed to fetch notifications:', error);
            });
        }

        showNotification();
    } else {
        alert('⚠️ Izin notifikasi ditolak. Silakan aktifkan notifikasi untuk pengalaman terbaik.');
    }
});