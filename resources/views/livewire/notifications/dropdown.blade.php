<?php

use function Livewire\Volt\{computed, state, on, mount};
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

state(['showDropdown' => false]);

$notifications = computed(function () {
    return Auth::user()->notifications()
        ->latest()
        ->take(10)
        ->get();
});

$unreadCount = computed(function () {
    return Auth::user()->unreadNotificationsCount();
});

$markAsRead = function ($notificationId) {
    $notification = Notification::where('user_id', Auth::id())
        ->where('id', $notificationId)
        ->first();
    
    if ($notification && !$notification->isRead()) {
        $notification->markAsRead();
    }
};

$markAllAsRead = function () {
    Auth::user()->notifications()->unread()->update(['read_at' => now()]);
};

?>

<div class="relative" x-data="{ open: @entangle('showDropdown') }">
    <!-- Notification Bell -->
    <button 
        @click="open = !open"
        class="relative p-2 text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-full"
    >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
        </svg>
        
        <!-- Badge -->
        @if($this->unreadCount > 0)
            <span class="absolute top-0 right-0 transform translate-x-1/2 -translate-y-1/2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                {{ $this->unreadCount > 99 ? '99+' : $this->unreadCount }}
            </span>
        @endif
    </button>

    <!-- Notification Dropdown -->
    <div 
        x-show="open" 
        @click.away="open = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 z-50"
    >
        <!-- Header -->
        <div class="px-4 py-3 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h3 class="text-sm font-medium text-gray-900">Notifikasi</h3>
                @if($this->unreadCount > 0)
                    <button 
                        wire:click="markAllAsRead"
                        class="text-xs text-blue-600 hover:text-blue-800"
                    >
                        Tandai semua dibaca
                    </button>
                @endif
            </div>
        </div>

        <!-- Notifications List -->
        <div class="max-h-96 overflow-y-auto">
            @forelse($this->notifications as $notification)
                <div 
                    class="{{ !$notification->isRead() ? 'bg-blue-50' : '' }} px-4 py-3 border-b border-gray-100 hover:bg-gray-50 cursor-pointer"
                    wire:click="markAsRead({{ $notification->id }})"
                >
                    <div class="flex justify-between items-start">
                        <div class="flex-1 min-w-0">
                            <h4 class="text-sm font-medium text-gray-900 truncate">
                                {{ $notification->title }}
                            </h4>
                            <p class="text-sm text-gray-600 mt-1">
                                {{ $notification->message }}
                            </p>
                            <p class="text-xs text-gray-400 mt-1">
                                {{ $notification->created_at->diffForHumans() }}
                            </p>
                        </div>
                        
                        @if(!$notification->isRead())
                            <div class="ml-2 flex-shrink-0">
                                <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                            </div>
                        @endif
                    </div>
                    
                    @if($notification->related_chat_id)
                        <div class="mt-2">
                            <a 
                                href="{{ route('chat.show', $notification->relatedChat->slug) }}"
                                class="inline-flex items-center text-xs text-blue-600 hover:text-blue-800"
                                @click="open = false"
                            >
                                Buka Chat
                                <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                </svg>
                            </a>
                        </div>
                    @endif
                </div>
            @empty
                <div class="px-4 py-8 text-center">
                    <div class="w-12 h-12 mx-auto mb-3 text-gray-300">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                    </div>
                    <p class="text-sm text-gray-500">Belum ada notifikasi</p>
                </div>
            @endforelse
        </div>

        @if($this->notifications->count() > 0)
            <div class="px-4 py-3 border-t border-gray-200">
                <a 
                    href="{{ route('notifications.index') }}" 
                    class="block text-center text-sm text-blue-600 hover:text-blue-800"
                    @click="open = false"
                >
                    Lihat semua notifikasi
                </a>
            </div>
        @endif
    </div>
</div>

<script>
    // Request notification permission on page load
    document.addEventListener('DOMContentLoaded', function() {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
    });

    // Auto-refresh notifications every 30 seconds
    setInterval(() => {
        if (document.visibilityState === 'visible') {
            Livewire.find(@this.getId()).call('$refresh');
        }
    }, 30000);
</script>
