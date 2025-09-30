// PWA Install Prompt Handler
let deferredPrompt;

// Mendengarkan event beforeinstallprompt
window.addEventListener('beforeinstallprompt', (e) => {
    console.log('PWA install prompt available');
    // Mencegah mini-infobar muncul otomatis
    e.preventDefault();
    // Simpan event untuk digunakan nanti
    deferredPrompt = e;
    // Tampilkan tombol install jika ada
    showInstallButton();
});

// Fungsi untuk menampilkan tombol install
function showInstallButton() {
    const installButton = document.getElementById('pwa-install-button');
    if (installButton) {
        installButton.style.display = 'block';
        installButton.addEventListener('click', installPWA);
    }
}

// Fungsi untuk menginstall PWA
async function installPWA() {
    if (!deferredPrompt) {
        console.log('Install prompt not available');
        return;
    }

    // Tampilkan install prompt
    deferredPrompt.prompt();
    
    // Tunggu user choice
    const { outcome } = await deferredPrompt.userChoice;
    console.log(`User ${outcome} the install prompt`);
    
    // Reset deferredPrompt
    deferredPrompt = null;
    
    // Sembunyikan tombol install
    const installButton = document.getElementById('pwa-install-button');
    if (installButton) {
        installButton.style.display = 'none';
    }
}

// Mendengarkan event appinstalled
window.addEventListener('appinstalled', (evt) => {
    console.log('PWA was installed');
    // Sembunyikan tombol install
    const installButton = document.getElementById('pwa-install-button');
    if (installButton) {
        installButton.style.display = 'none';
    }
});

// Export fungsi untuk digunakan di tempat lain
window.installPWA = installPWA;
