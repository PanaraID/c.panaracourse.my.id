{{--
    Dashboard Page
    
    Halaman dashboard untuk user authenticated.
    Menampilkan welcome message dan overview fitur utama aplikasi.
    Tempat terpusat untuk navigasi ke fitur-fitur lain.
--}}

<?php

use Livewire\Attributes\{Layout};
use Livewire\Volt\Component;

new
#[Layout('components.layouts.app')]
class extends Component {
    /**
     * Log dashboard page visit
     */
    public function mount()
    {
        logger()->info('Home page visited');
    }
};

?>

<div class="flex flex-col items-center justify-center min-h-[60vh] px-4 py-12">
    <h1 class="text-4xl md:text-5xl font-bold mb-3 text-green-700 dark:text-green-300 text-center drop-shadow">
        Komunitas Panara Course
    </h1>
    <p class="text-lg md:text-xl text-gray-700 dark:text-gray-300 mb-6 max-w-xl text-center">
        Pusat kendali Tentor Super Panara Course.
    </p>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5 max-w-xl w-full">
        <h2 class="text-xl font-semibold text-green-600 dark:text-green-300 mb-2">Fitur Utama</h2>
        <ul class="list-disc list-inside text-gray-700 dark:text-gray-300 space-y-1 mb-3">
            <li>Dapat berkomunikasi dengan tim</li>
            <li>Fitur notifikasi pesan</li>
        </ul>
    </div>
</div>