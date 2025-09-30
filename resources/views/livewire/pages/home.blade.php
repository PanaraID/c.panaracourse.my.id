<?php

use Livewire\Attributes\{Layout};
use Livewire\Volt\Component;

new
#[Layout('layouts.base')]
class extends Component {
    public function mount()
    {
        logger()->info('Home page visited');
    }
};

?>
<div class="flex flex-col items-center justify-center py-12 px-4">
    <h1 class="text-5xl font-extrabold mb-4 text-blue-700 dark:text-blue-300 drop-shadow-lg">
        Selamat Datang di Platform Komunikasi
    </h1>
    <p class="text-xl text-gray-700 dark:text-gray-300 mb-8 max-w-2xl text-center">
        Platform ini dirancang untuk memudahkan komunikasi, berbagi pengalaman, dan meningkatkan kualitas bersama.
    </p>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-8 max-w-2xl w-full">
        <h2 class="text-2xl font-semibold text-blue-600 dark:text-blue-300 mb-2">Tentang Platform Ini</h2>
        <p class="text-gray-600 dark:text-gray-400 mb-4">
            Bergabunglah dengan komunitas yang aktif berdiskusi, berbagi strategi, dan saling mendukung dalam menghadapi berbagai tantangan.
        </p>
        <ul class="list-disc list-inside text-gray-700 dark:text-gray-300 space-y-1">
            <li>Forum diskusi</li>
            <li>Grup belajar dan kolaborasi</li>
        </ul>
    </div>
    <a href="{{ route('login') }}" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-8 rounded-full shadow transition duration-200">
        Gabung Sekarang
    </a>
</div>
