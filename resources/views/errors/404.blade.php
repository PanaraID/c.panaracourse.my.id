<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>404 Tidak Ditemukan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-blue-100 flex items-center justify-center min-h-screen">
    <div class="bg-white rounded-2xl shadow-2xl p-10 max-w-md w-full text-center border border-blue-100">
        <div class="flex justify-center mb-6">
            <svg class="w-20 h-20 text-blue-600 drop-shadow-lg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 64 64">
                <circle cx="32" cy="32" r="30" stroke="currentColor" stroke-width="4" fill="#eff6ff"/>
                <text x="32" y="44" text-anchor="middle" font-size="32" fill="currentColor" font-family="Arial, sans-serif" font-weight="bold">404</text>
            </svg>
        </div>
        <h1 class="text-4xl font-extrabold text-blue-700 mb-2 drop-shadow">Halaman Tidak Ditemukan</h1>
        <p class="text-lg text-gray-600 mb-8">Maaf, halaman yang Anda cari tidak tersedia atau telah dipindahkan.</p>
        <a href="{{ url('/') }}" class="inline-block px-8 py-3 bg-blue-600 text-white font-bold rounded-xl shadow-lg hover:bg-blue-700 transition duration-200 ease-in-out">
            Kembali ke Beranda
        </a>
    </div>
</body>
</html>
