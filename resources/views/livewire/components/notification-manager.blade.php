<?php

use function Livewire\Volt\{computed, state, on, mount};
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

state(['hasShownInitialNotifications' => false]);

$unreadNotifications = computed(function () {
    return Auth::user()->notifications()->unread()->latest()->take(5)->get();
});

$showInitialNotifications = function () {
    if ($this->hasShownInitialNotifications) {
        return;
    }

    $unreadNotifications = $this->unreadNotifications;

    if ($unreadNotifications->count() > 0) {
        $this->dispatch('show-unread-notifications', [
            'notifications' => $unreadNotifications->toArray(),
        ]);
    }

    $this->hasShownInitialNotifications = true;
};

/**
 * Menandai semua notifikasi yang belum dibaca sebagai telah dibaca.
 * Ini adalah fungsi yang hilang dan menyebabkan Livewire\Exceptions\MethodNotFoundException.
 */
$markNotificationsRead = function () {
    // Mark all unread notifications as read
    $updatedCount = Auth::user()->notifications()->unread()->update(['read_at' => now()]);
    
    // Log the action for tracking
    if (function_exists('logger')) {
        logger()->info('Notifications marked as read', [
            'user_id' => Auth::id(),
            'updated_count' => $updatedCount
        ]);
    }
    
    // Refresh the computed property
    $this->hasShownInitialNotifications = false;
    
    // Dispatch event to update UI if needed
    $this->dispatch('notifications-marked-as-read', ['count' => $updatedCount]);
};

mount(function () {
    // Show initial notifications after a short delay
    $this->dispatch('show-initial-notifications-delayed');
});

?>

<div>
    <script>
        window.currentUserId = window.currentUserId || {{ Auth::id() }};
        let hasRequestedPermission = false;

        // Safe Livewire call helper
        function safeLivewireCall(method, ...args) {
            if (window.Livewire && @this && typeof @this[method] === 'function') {
                try {
                    return @this[method](...args);
                } catch (error) {
                    console.error('Livewire call error:', error);
                    if (window.logger) {
                        window.logger.error('Livewire call failed', {
                            method,
                            args,
                            error: error.message,
                            component: 'notification-manager'
                        });
                    }
                }
            } else {
                console.warn('Livewire not ready for method:', method);
                if (window.logger) {
                    window.logger.warn('Livewire not ready', {
                        method,
                        livewireExists: !!window.Livewire,
                        thisExists: !!@this,
                        component: 'notification-manager'
                    });
                }
                return null;
            }
        }

        // Wait for Livewire to be fully initialized
        function waitForLivewire(callback, timeout = 5000) {
            const startTime = Date.now();
            const checkInterval = setInterval(() => {
                if (window.Livewire && @this && typeof @this.call === 'function') {
                    clearInterval(checkInterval);
                    callback();
                } else if (Date.now() - startTime > timeout) {
                    clearInterval(checkInterval);
                    console.warn('Livewire initialization timeout');
                }
            }, 100);
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Request notification permission and show initial notifications
            document.addEventListener('livewire:init', () => {
                // Request permission first
                if ('Notification' in window && Notification.permission === 'default') {
                    Notification.requestPermission().then(function(permission) {
                        if (permission === 'granted') {
                            // Show initial notifications after permission granted
                            waitForLivewire(() => {
                                safeLivewireCall('call', 'showInitialNotifications');
                            });
                        }
                    });
                } else if (Notification.permission === 'granted') {
                    // Already have permission, show notifications
                    waitForLivewire(() => {
                        safeLivewireCall('call', 'showInitialNotifications');
                    });
                }

                // Listen for event to show initial notifications
                document.addEventListener('livewire:initialized', () => {
                    Livewire.on('show-initial-notifications-delayed', () => {
                        setTimeout(() => {
                            safeLivewireCall('call', 'showInitialNotifications');
                        }, 2000);
                    });

                    // Listen for unread notifications to display
                    Livewire.on('show-unread-notifications', (data) => {
                        if (window.logger) {
                            window.logger.info('Received unread notifications', {
                                count: data.notifications?.length || 0,
                                component: 'notification-manager'
                            });
                        }
                        
                        if ('Notification' in window && Notification.permission === 'granted') {
                            const notifications = data.notifications || [];

                            notifications.forEach((notification, index) => {
                                setTimeout(() => {
                                    const notificationData = notification
                                        .data || {};

                                    // Format browser notification
                                    let browserTitle = notification.title;
                                    let browserBody = notification.message;

                                    if (notification.type ===
                                        'new_message' && notificationData
                                        .sender_name && notificationData
                                        .message_content) {
                                        browserTitle =
                                            `Ada pesan dari ${notificationData.sender_name}`;
                                        browserBody =
                                            `${notificationData.chat_title ? 'Di ' + notificationData.chat_title + ': ' : ''}${notificationData.message_content}`;
                                    }

                                    new Notification(browserTitle, {
                                        body: browserBody,
                                        icon: '/logo.png',
                                        tag: 'unread-notification-' +
                                            notification.id,
                                        badge: '/logo.png',
                                        requireInteraction: true, // Keep notification visible until user interacts
                                        silent: false
                                    });

                                    // Log notification display
                                    if (window.logger) {
                                        window.logger.info('Browser notification shown', {
                                            notification_id: notification.id,
                                            title: browserTitle,
                                            type: notification.type
                                        });
                                    }
                                }, index *
                                1000); // Stagger notifications by 1 second each
                            });
                        }
                    });
                });
            });
        });
    </script>
</div>