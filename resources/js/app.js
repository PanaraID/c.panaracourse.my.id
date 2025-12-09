import './bootstrap';
import './pwa-install';

// Penanganan pesan global untuk chat (tanpa siaran real-time)
window.currentUserId = null;

// --- Registrasi Service Worker PWA ---
function daftarkanServiceWorker() {
    if (!('serviceWorker' in navigator)) return;

    window.addEventListener('load', async () => {
        try {
            const registrasi = await navigator.serviceWorker.register('/sw.js');
            console.log('[PWA] Service Worker berhasil didaftarkan:', registrasi.scope);

            registrasi.addEventListener('updatefound', () => {
                const pekerjaBaru = registrasi.installing;
                pekerjaBaru.addEventListener('statechange', () => {
                    if (pekerjaBaru.state === 'installed' && navigator.serviceWorker.controller) {
                        console.log('[PWA] Pembaruan tersedia');
                        tampilkanNotifikasiUpdate();
                    }
                });
            });
        } catch (error) {
            console.error('[PWA] Gagal mendaftarkan Service Worker:', error.message);
        }
    });
}

// --- Prompt Instalasi PWA ---
let promptTertunda = null;
let tombolInstal = null;

function siapkanPromptInstalasi() {
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        promptTertunda = e;
        console.log('[PWA] Prompt instalasi tersedia');
        tampilkanTombolInstal();
    });

    window.addEventListener('appinstalled', () => {
        console.log('[PWA] Aplikasi berhasil diinstal');
        sembunyikanTombolInstal();
    });
}

function tampilkanTombolInstal() {
    if (window.location.pathname.startsWith('/chat')) return;
    if (tombolInstal || !document.body) return;

    tombolInstal = document.createElement('button');
    tombolInstal.innerHTML = `
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
            <polyline points="7,10 12,15 17,10"/>
            <line x1="12" y1="15" x2="12" y2="3"/>
        </svg>
        Instal Aplikasi
    `;
    tombolInstal.className = 'pwa-install-btn';
    tombolInstal.style.cssText = `
        position: fixed; bottom: 20px; right: 20px; background: #000; color: white;
        border: none; border-radius: 50px; padding: 12px 20px; font-size: 14px; font-weight: 500;
        cursor: pointer; box-shadow: 0 4px 12px rgba(0,0,0,0.3); display: flex; align-items: center;
        gap: 8px; z-index: 1000; transition: all 0.3s ease; transform: translateY(100px); opacity: 0;
    `;
    tombolInstal.addEventListener('click', instalPWA);
    document.body.appendChild(tombolInstal);

    setTimeout(() => {
        tombolInstal.style.transform = 'translateY(0)';
        tombolInstal.style.opacity = '1';
    }, 100);
}

async function instalPWA() {
    if (!promptTertunda) return;
    try {
        const hasil = await promptTertunda.prompt();
        if (hasil.outcome === 'accepted') {
            console.log('[PWA] Pengguna menerima instalasi');
            sembunyikanTombolInstal();
        } else {
            console.log('[PWA] Pengguna menolak instalasi');
        }
        promptTertunda = null;
    } catch (error) {
        console.error('[PWA] Kesalahan instalasi:', error.message);
    }
}

function sembunyikanTombolInstal() {
    if (!tombolInstal) return;
    tombolInstal.style.transform = 'translateY(100px)';
    tombolInstal.style.opacity = '0';
    setTimeout(() => {
        if (tombolInstal.parentNode) tombolInstal.parentNode.removeChild(tombolInstal);
        tombolInstal = null;
    }, 300);
}

// --- Notifikasi Pembaruan ---
function tampilkanNotifikasiUpdate() {
    const notifikasiUpdate = document.createElement('div');
    notifikasiUpdate.innerHTML = `
        <div style="padding:16px; background:#000; color:white; position:fixed; top:20px; right:20px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.3); z-index:1001; max-width:300px;">
            <p style="margin:0 0 12px 0; font-size:14px;">Terdapat pembaruan untuk aplikasi ini</p>
            <button id="update-now-btn" style="background:white; color:black; border:none; padding:8px 16px; border-radius:4px; cursor:pointer; font-size:12px; font-weight:500;">Perbarui Sekarang</button>
            <button id="update-later-btn" style="background:transparent; color:white; border:1px solid rgba(255,255,255,0.3); padding:8px 16px; border-radius:4px; cursor:pointer; font-size:12px; margin-left:8px;">Nanti Saja</button>
        </div>
    `;
    document.body.appendChild(notifikasiUpdate);

    notifikasiUpdate.querySelector('#update-now-btn').onclick = () => {
        console.log('[PWA] Pengguna memilih perbarui sekarang');
        window.location.reload();
    };
    notifikasiUpdate.querySelector('#update-later-btn').onclick = () => {
        console.log('[PWA] Pengguna menunda pembaruan');
        notifikasiUpdate.remove();
    };

    setTimeout(() => notifikasiUpdate.remove(), 3000);
}

// --- Status Online/Offline ---
function siapkanListenerOnlineOffline() {
    window.addEventListener('online', () => console.log('[Jaringan] Anda terhubung'));
    window.addEventListener('offline', () => console.log('[Jaringan] Anda sedang offline'));
}

// --- Penanganan Notifikasi ---
async function mintaIzinNotifikasi() {
    try {
        const izin = await Notification.requestPermission();
        if (izin !== 'granted') {
            alert('⚠️ Izin notifikasi ditolak. Silakan aktifkan notifikasi untuk pengalaman terbaik.');
            console.warn('[Notifikasi] Izin ditolak');
            return false;
        }
        console.log('[Notifikasi] Izin diberikan');
        
        // Berlangganan push notification
        await langgananPushNotification();
        
        return true;
    } catch (error) {
        console.error('[Notifikasi] Kesalahan izin:', error.message);
        return false;
    }
}
mintaIzinNotifikasi();

async function langgananPushNotification() {
    try {
        const registrasi = await navigator.serviceWorker.ready;
        
        // Cek langganan yang sudah ada
        let langganan = await registrasi.pushManager.getSubscription();
        
        if (!langganan) {
            // Ambil VAPID public key dari server
            const response = await fetch('/api/push/public-key');
            const { publicKey } = await response.json();
            
            // Buat langganan baru
            langganan = await registrasi.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: ubahBase64KeUint8Array(publicKey)
            });
            
            console.log('[Push] Langganan baru dibuat:', langganan);
        } else {
            console.log('[Push] Langganan sudah ada:', langganan);
        }
        
        // Kirim langganan ke server
        await kirimLanggananKeServer(langganan);
        
    } catch (error) {
        console.error('[Push] Gagal berlangganan:', error.message);
    }
}

async function kirimLanggananKeServer(langganan) {
    try {
        const token = ambilToken();
        if (!token) {
            console.warn('[Push] Tidak ada token auth, langganan tidak dikirim');
            return;
        }
        
        const response = await fetch('/api/push/subscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + token
            },
            body: JSON.stringify(langganan.toJSON())
        });
        
        if (response.ok) {
            const data = await response.json();
            console.log('[Push] Langganan berhasil disimpan di server:', data);
        } else {
            console.error('[Push] Gagal menyimpan langganan:', response.status);
        }
    } catch (error) {
        console.error('[Push] Gagal mengirim langganan ke server:', error.message);
    }
}

// Fungsi bantu untuk mengubah VAPID key
function ubahBase64KeUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/\-/g, '+')
        .replace(/_/g, '/');
    
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

function ambilToken() {
    const { userId } = document.body.dataset || {};
    if (!userId) {
        alert('⚠️ Ada kesalahan fatal. Silakan login ulang.');
        console.error('[Auth] Token pengguna tidak ditemukan');
        return null;
    }
    return userId;
}

// --- Inisialisasi ---
console.log('[Aplikasi] Inisialisasi app.js');
daftarkanServiceWorker();
siapkanPromptInstalasi();
siapkanListenerOnlineOffline();
