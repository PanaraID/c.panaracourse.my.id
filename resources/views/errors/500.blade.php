<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>500 Kesalahan Server</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gradient-to-br from-red-50 via-white to-red-100 flex items-center justify-center min-h-screen">
    <div class="bg-white rounded-2xl shadow-2xl p-10 max-w-md w-full text-center border border-red-100">
        <div class="flex justify-center mb-6">
            <svg class="w-20 h-20 text-red-600 drop-shadow-lg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 64 64">
                <circle cx="32" cy="32" r="30" stroke="currentColor" stroke-width="4" fill="#fee2e2"/>
                <text x="32" y="44" text-anchor="middle" font-size="32" fill="currentColor" font-family="Arial, sans-serif" font-weight="bold">500</text>
            </svg>
        </div>
        <h1 class="text-4xl font-extrabold text-red-700 mb-2 drop-shadow">Kesalahan Server</h1>
        <p class="text-lg text-gray-600 mb-8">Maaf, terjadi kesalahan pada server kami. Silakan coba beberapa saat lagi.</p>
        <a href="{{ url('/') }}" class="inline-block px-8 py-3 bg-red-600 text-white font-bold rounded-xl shadow-lg hover:bg-red-700 transition duration-200 ease-in-out">
            Kembali ke Beranda
        </a>
    </div>
</body>
</html>
