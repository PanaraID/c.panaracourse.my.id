<?php

use function Livewire\Volt\{computed, state, on, mount};
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

state(['lastNotificationId' => 0]);

$notifications = computed(function () {
    $notifications = Auth::user()->notifications()->latest()->paginate(20);
    
    // Update last notification ID to track new notifications
    if ($notifications->isNotEmpty()) {
        $this->lastNotificationId = $notifications->first()->id;
    }
    
    return $notifications;
});

$markAsRead = function ($notificationId) {
    $notification = Notification::where('user_id', Auth::id())->where('id', $notificationId)->first();

    if ($notification && !$notification->isRead()) {
        $notification->markAsRead();
    }
};

$markAllAsRead = function () {
    Auth::user()
        ->notifications()
        ->unread()
        ->update(['read_at' => now()]);
    session()->flash('success', 'Semua notifikasi telah ditandai sebagai dibaca.');
};

$refreshNotifications = function () {
    // Check if there are new notifications
    $latestNotification = Auth::user()->notifications()->latest()->first();
    
    if ($latestNotification && $latestNotification->id > $this->lastNotificationId) {
        // There are new notifications, refresh the component
        $this->lastNotificationId = $latestNotification->id;
        
        // Dispatch event for new notification
        $this->dispatch('new-notification-received');
    }
};

$onNotificationsUpdated = function () {
    // Force refresh when notifications are updated (e.g., marked as read)
    // This will re-compute all computed properties
};

on(['notifications-updated' => $onNotificationsUpdated]);

mount(function () {
    // Set initial last notification ID
    $lastNotification = Auth::user()->notifications()->latest()->first();
    $this->lastNotificationId = $lastNotification ? $lastNotification->id : 0;
});

?>

<div wire:poll.5s="refreshNotifications">

    <div class="max-w-4xl mx-auto p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Notifikasi</h1>
            @if (Auth::user()->unreadNotificationsCount() > 0)
                <button wire:click="markAllAsRead"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors">
                    Tandai Semua Dibaca
                </button>
            @endif
        </div>

        @if (session()->has('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif

        <!-- Notifications List -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
            @forelse($this->notifications as $notification)
                <div class="{{ !$notification->isRead() ? 'bg-blue-50' : '' }} px-6 py-4 border-b border-gray-100 last:border-b-0"
                    wire:key="notification-{{ $notification->id }}">
                    <div class="flex justify-between items-start">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center space-x-2 mb-2">
                                <h3 class="text-sm font-medium text-gray-900">
                                    {{ $notification->title }}
                                </h3>
                                @if (!$notification->isRead())
                                    <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                @endif
                            </div>

                            <p class="text-sm text-gray-600 mb-2">
                                {{ $notification->message }}
                            </p>

                            <div class="flex items-center space-x-4 text-xs text-gray-400">
                                <span>{{ $notification->created_at->format('d M Y, H:i') }}</span>
                                <span>•</span>
                                <span>{{ $notification->created_at->diffForHumans() }}</span>
                            </div>
                        </div>

                        <div class="ml-4 flex items-center space-x-2">
                            @if ($notification->related_chat_id)
                                <a href="{{ route('chat.show', $notification->relatedChat->slug) }}"
                                    class="inline-flex items-center px-3 py-1 bg-blue-100 hover:bg-blue-200 text-blue-800 text-xs font-medium rounded-full transition-colors">
                                    Buka Chat
                                </a>
                            @endif

                            @if (!$notification->isRead())
                                <button wire:click="markAsRead({{ $notification->id }})"
                                    class="text-blue-600 hover:text-blue-800 text-xs">
                                    Tandai Dibaca
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-6 py-12 text-center">
                    <div class="w-16 h-16 mx-auto mb-4 text-gray-300">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Belum ada notifikasi</h3>
                    <p class="text-gray-500">Notifikasi akan muncul ketika ada aktivitas baru di chat.</p>
                </div>
            @endforelse
        </div>

        @if ($this->notifications->hasPages())
            <div class="mt-6">
                {{ $this->notifications->links() }}
            </div>
        @endif
    </div>

    <script>
        window.currentUserId = window.currentUserId || {{ Auth::id() }};

        // Handle notification events (without real-time broadcasting)
        document.addEventListener('livewire:init', () => {
            Livewire.on('new-notification-received', () => {
                // Show toast notification for new notification
                if (typeof toastr !== 'undefined') {
                    toastr.info('Notifikasi baru diterima!');
                }
            });

            // Note: Real-time notifications disabled. 
            // Notifications will be updated when the page is refreshed or manually refreshed.
            console.log('Real-time notifications disabled. Please refresh the page to see new notifications.');
        });

        // Request notification permission on page load
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
    </script>
</div>
