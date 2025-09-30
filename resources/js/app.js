import './bootstrap';

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Configure Echo for real-time broadcasting
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
    wsHost: import.meta.env.VITE_PUSHER_HOST ? import.meta.env.VITE_PUSHER_HOST : `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusherapp.com`,
    wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
    wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});

// Global message handling for chat
window.currentUserId = null;

window.handleNewMessage = function(chatId, callback) {
    if (window.Echo) {
        window.Echo.private(`chat.${chatId}`)
            .listen('.message.sent', (e) => {
                callback(e.message);
            });
    }
};

// Function to leave a chat channel
window.leaveChatChannel = function(chatId) {
    if (window.Echo) {
        window.Echo.leaveChannel(`private-chat.${chatId}`);
    }
};