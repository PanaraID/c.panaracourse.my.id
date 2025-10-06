import './bootstrap';
import './pwa-install';

// Global message handling for chat (without real-time broadcasting)
window.currentUserId = null;

// --- PWA Service Worker Registration ---
function registerServiceWorker() {
    if (!('serviceWorker' in navigator)) return;

    window.addEventListener('load', async () => {
        try {
            const registration = await navigator.serviceWorker.register('/sw.js');
            console.log('✓ Service Worker registered:', registration.scope);

            registration.addEventListener('updatefound', () => {
                const newWorker = registration.installing;
                newWorker.addEventListener('statechange', () => {
                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                        showUpdateAvailable();
                    }
                });
            });
        } catch (error) {
            console.error('✗ Service Worker registration failed:', error);
        }
    });
}

// --- PWA Install Prompt ---
let deferredPrompt = null;
let installButton = null;

function setupInstallPrompt() {
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        showInstallButton();
    });

    window.addEventListener('appinstalled', () => {
        console.log('✓ PWA was installed');
        hideInstallButton();
    });
}

function showInstallButton() {
    if (window.location.pathname.startsWith('/chat')) return;
    if (installButton || !document.body) return;

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
        position: fixed; bottom: 20px; right: 20px; background: #000; color: white;
        border: none; border-radius: 50px; padding: 12px 20px; font-size: 14px; font-weight: 500;
        cursor: pointer; box-shadow: 0 4px 12px rgba(0,0,0,0.3); display: flex; align-items: center;
        gap: 8px; z-index: 1000; transition: all 0.3s ease; transform: translateY(100px); opacity: 0;
    `;
    installButton.addEventListener('click', installPWA);
    document.body.appendChild(installButton);

    setTimeout(() => {
        installButton.style.transform = 'translateY(0)';
        installButton.style.opacity = '1';
    }, 100);
}

async function installPWA() {
    if (!deferredPrompt) return;
    try {
        const result = await deferredPrompt.prompt();
        if (result.outcome === 'accepted') hideInstallButton();
        deferredPrompt = null;
    } catch (error) {
        console.error('✗ PWA install error:', error);
    }
}

function hideInstallButton() {
    if (!installButton) return;
    installButton.style.transform = 'translateY(100px)';
    installButton.style.opacity = '0';
    setTimeout(() => {
        if (installButton.parentNode) installButton.parentNode.removeChild(installButton);
        installButton = null;
    }, 300);
}

// --- Update Notification ---
function showUpdateAvailable() {
    const updateNotification = document.createElement('div');
    updateNotification.innerHTML = `
        <div style="padding:16px; background:#000; color:white; position:fixed; top:20px; right:20px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.3); z-index:1001; max-width:300px;">
            <p style="margin:0 0 12px 0; font-size:14px;">Update tersedia untuk aplikasi ini</p>
            <button id="update-now-btn" style="background:white; color:black; border:none; padding:8px 16px; border-radius:4px; cursor:pointer; font-size:12px; font-weight:500;">Update Sekarang</button>
            <button id="update-later-btn" style="background:transparent; color:white; border:1px solid rgba(255,255,255,0.3); padding:8px 16px; border-radius:4px; cursor:pointer; font-size:12px; margin-left:8px;">Nanti</button>
        </div>
    `;
    document.body.appendChild(updateNotification);

    updateNotification.querySelector('#update-now-btn').onclick = () => window.location.reload();
    updateNotification.querySelector('#update-later-btn').onclick = () => updateNotification.remove();

    setTimeout(() => updateNotification.remove(), 3000);
}

// --- Online/Offline Status ---
function setupOnlineOfflineListeners() {
    window.addEventListener('online', () => console.log('✓ Back online'));
    window.addEventListener('offline', () => console.log('✗ Gone offline'));
}

// --- Notification Handling ---
async function requestNotificationPermission() {
    try {
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            alert('⚠️ Izin notifikasi ditolak. Silakan aktifkan notifikasi untuk pengalaman terbaik.');
            return false;
        }
        console.log('✓ Notification permission granted.');
        return true;
    } catch (error) {
        console.error('✗ Notification permission error:', error);
        return false;
    }
}

function getToken() {
    let token = localStorage.getItem('user_token');
    if (!token) {
        token = "{{ auth()->user()->id }}";
        console.log(token);
        alert('⚠️ Ada kesalahan fatal. Silakan login ulang.');
        return null;
    }
    return token;
}

async function fetchNotifications() {
    const token = getToken();
    if (!token) return;
    try {
        const res = await fetch('/api/notifications', {
            headers: { 'Accept': 'application/json', 'Authorization': 'Bearer ' + token }
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();
        const notif = data.notification;
        if (notif) {
            new Notification('Hai, kamu punya notifikasi baru!', {
                body: notif.message,
                icon: '/favicon.ico',
                badge: '/favicon.ico',
            });
        }
    } catch (err) {
        console.error('✗ Notification fetch error:', err);
        if (err.message.includes('401')) localStorage.removeItem('user_token');
    }
}

async function setupNotifications() {
    if (await requestNotificationPermission()) {
        setInterval(fetchNotifications, 3000);
    }
}

// --- Init ---
registerServiceWorker();
setupInstallPrompt();
setupOnlineOfflineListeners();
setupNotifications();
