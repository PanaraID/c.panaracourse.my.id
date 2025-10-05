<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;
use App\Http\Controllers\Admin\LogDashboardController;

// Route::get('/', function () {
//     return view('welcome');
// })->name('home');

Volt::route('/', 'pages.home')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Volt::route('/dashboard', 'pages.dashboard')->name('dashboard');
    Route::redirect('/dashboard', '/chat')->name('dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');

    // Chat routes
    Route::middleware(['role:admin|member', 'log.access:chat_access'])->group(function () {
        Volt::route('/chat', 'chat.index')->name('chat.index');
        Volt::route('/chat/{chat:slug}', 'chat.show')
            ->middleware(['permission:view-chat', 'chat.access:member', 'log.access:chat_room_access'])
            ->name('chat.show');
        
        // Admin or chat owner only routes
        Route::middleware(['chat.access:owner-or-admin', 'permission:manage-chat-members', 'log.access:chat_management'])->group(function () {
            Volt::route('/chat/{chat:slug}/manage', 'chat.manage')->name('chat.manage');
        });
        
        // Chat creation route (admin only)
        Route::middleware(['role:admin', 'permission:create-chat'])->group(function () {
            // Chat creation handled via Livewire component in chat.index
        });
    });

    // Notifications (accessible by all authenticated users)
    Volt::route('/notifications', 'notifications.index')->name('notifications.index');
    
    // Frontend Logs Admin Dashboard (add appropriate middleware for admin access)
    Route::prefix('admin/logs')->name('admin.logs.')->group(function () {
        Route::get('/', [LogDashboardController::class, 'index'])->name('dashboard');
        Route::get('/logs', [LogDashboardController::class, 'logs'])->name('logs');
        Route::get('/logs/{log}', [LogDashboardController::class, 'show'])->name('show');
        Route::get('/errors', [LogDashboardController::class, 'errors'])->name('errors');
        Route::get('/performance', [LogDashboardController::class, 'performance'])->name('performance');
        Route::get('/export', [LogDashboardController::class, 'export'])->name('export');
    });
});

require __DIR__.'/auth.php';
