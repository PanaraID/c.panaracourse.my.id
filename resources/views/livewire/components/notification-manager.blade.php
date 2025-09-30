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
            'notifications' => $unreadNotifications->toArray()
        ]);
    }
    
    $this->hasShownInitialNotifications = true;
};

mount(function () {
    // Show initial notifications after a short delay
    $this->dispatch('show-initial-notifications-delayed');
});

?>

<div>
    <!-- Hidden component for handling initial notifications -->
    <script>
        window.currentUserId = window.currentUserId || {{ Auth::id() }};
        let hasRequestedPermission = false;

        // Request notification permission and show initial notifications
        document.addEventListener('livewire:init', () => {
            // Request permission first
            if ('Notification' in window && Notification.permission === 'default') {
                Notification.requestPermission().then(function (permission) {
                    if (permission === 'granted') {
                        // Show initial notifications after permission granted
                        setTimeout(() => {
                            @this.call('showInitialNotifications');
                        }, 1000);
                    }
                });
            } else if (Notification.permission === 'granted') {
                // Already have permission, show notifications
                setTimeout(() => {
                    @this.call('showInitialNotifications');
                }, 1000);
            }

            // Listen for event to show initial notifications
            Livewire.on('show-initial-notifications-delayed', () => {
                setTimeout(() => {
                    @this.call('showInitialNotifications');
                }, 2000);
            });

            // Listen for unread notifications to display
            Livewire.on('show-unread-notifications', (data) => {
                if ('Notification' in window && Notification.permission === 'granted') {
                    const notifications = data.notifications || [];
                    
                    notifications.forEach((notification, index) => {
                        setTimeout(() => {
                            const notificationData = notification.data || {};
                            
                            // Format browser notification
                            let browserTitle = notification.title;
                            let browserBody = notification.message;
                            
                            if (notification.type === 'new_message' && notificationData.sender_name && notificationData.message_content) {
                                browserTitle = `Ada pesan dari ${notificationData.sender_name}`;
                                browserBody = `${notificationData.chat_title ? 'Di ' + notificationData.chat_title + ': ' : ''}${notificationData.message_content}`;
                            }
                            
                            new Notification(browserTitle, {
                                body: browserBody,
                                icon: '/favicon.ico',
                                tag: 'unread-notification-' + notification.id,
                                badge: '/favicon.ico',
                                requireInteraction: true, // Keep notification visible until user interacts
                                silent: false
                            });
                        }, index * 1000); // Stagger notifications by 1 second each
                    });
                }
            });
        });
    </script>
</div>
