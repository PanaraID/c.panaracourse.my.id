<?php

use function Livewire\Volt\{computed, state, on, mount};
use App\Models\Chat;
use App\Models\Message;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Validate;
use Illuminate\Support\Str;

new
#[\Livewire\Attributes\Layout('layouts.base')]
class extends \Livewire\Volt\Component {
    public ?Chat $chat = null;
    public string $newMessage = '';
    public int $lastMessageId = 0;

    public function messages()
    {
        if (!$this->chat) {
            return collect();
        }

        $messages = $this->chat->messages()->with('user')->latest()->get()->values();

        if ($messages->isNotEmpty()) {
            $this->lastMessageId = $messages->last()->id;
        }

        return $messages;
    }

    public function mount(Chat $chat)
    {
        if (!$chat->members->contains(Auth::user()) && !Auth::user()->hasRole('admin')) {
            abort(403, 'Anda tidak memiliki akses ke chat ini.');
        }

        $this->chat = $chat;

        $lastMessage = $this->chat->messages()->latest()->first();
        $this->lastMessageId = $lastMessage ? $lastMessage->id : 0;

        Log::info('User accessed chat', [
            'chat_id' => $chat->id,
            'chat_title' => $chat->title,
            'user_name' => Auth::user()->name,
            'user_id' => Auth::id(),
        ]);
    }

    public function markChatNotificationsAsRead()
    {
        $updatedCount = Notification::markChatNotificationsAsRead(Auth::id(), $this->chat->id);

        if ($updatedCount > 0) {
            Log::info('Chat notifications marked as read', [
                'chat_id' => $this->chat->id,
                'user_id' => Auth::id(),
                'notifications_count' => $updatedCount,
            ]);

            $this->dispatch('notifications-updated');
        }

        return $updatedCount;
    }

    public function refreshMessages()
    {
        $latestMessage = $this->chat->messages()->latest()->first();

        if ($latestMessage && $latestMessage->id > $this->lastMessageId) {
            $this->lastMessageId = $latestMessage->id;
            $this->markChatNotificationsAsRead();
            $this->dispatch('new-messages-loaded');
        }
    }

    public function markNotificationsRead()
    {
        $this->markChatNotificationsAsRead();
    }

    #[\Livewire\Attributes\On('message-received')]
    public function onMessageReceived()
    {
        // The computed property will automatically refresh
    }
};

?>

<div class="flex flex-col bg-gradient-to-b from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-950 h-screen overflow-hidden shadow-2xl">
    @livewire('chat._components.show.header', ['chat' => $chat])

    <div class="flex-1 overflow-y-auto px-4 h-full sm:px-6 py-4 bg-gray-200 dark:bg-gray-900 transition duration-300" wire:poll.2s="refreshMessages" id="messages-container">

        
        @if ($chat->messages()->count() == 0)
            @livewire('chat._components.show.empty_chat')
        @else
            @livewire('chat._components.show.messages', ['chat' => $chat])
        @endif

        {{-- Custom Utility Classes for Animation (Tambahkan ini di bagian CSS Anda) --}}
        <style>
            @keyframes fadeIn {
                from {
                    opacity: 0;
                }

                to {
                    opacity: 1;
                }
            }

            @keyframes slideInRight {
                from {
                    opacity: 0;
                    transform: translateX(10px);
                }

                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }

            @keyframes slideInLeft {
                from {
                    opacity: 0;
                    transform: translateX(-10px);
                }

                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }

            @keyframes pulseSlow {

                0%,
                100% {
                    opacity: 1;
                }

                50% {
                    opacity: 0.8;
                }
            }

            .animate-fade-in {
                animation: fadeIn 0.5s ease-out forwards;
            }

            .animate-slide-in-right {
                animation: slideInRight 0.3s ease-out forwards;
            }

            .animate-slide-in-left {
                animation: slideInLeft 0.3s ease-out forwards;
            }

            .animate-pulse-slow {
                animation: pulseSlow 2.5s infinite ease-in-out;
            }
        </style>
    </div>

    <!-- Message Input Footer -->
    @can('send-message')
        @livewire('chat._components.show.send_button', ['chat' => $chat])
    @endcan

    <style>
        /* Enhanced Variables */
        :root {
            --whatsapp-green: #25D366;
            --whatsapp-green-dark: #20BD5F;
            --emerald-glow: rgba(16, 185, 129, 0.3);
        }

        /* Chat Background Pattern */
        .chat-background {
            background: linear-gradient(to bottom, #f9fafb, #f3f4f6);
            background-image:
                radial-gradient(circle at 20% 50%, rgba(16, 185, 129, 0.03) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(52, 211, 153, 0.03) 0%, transparent 50%),
                url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%2310b981' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        .dark .chat-background {
            background: linear-gradient(to bottom, #111827, #0f172a);
            background-image:
                radial-gradient(circle at 20% 50%, rgba(16, 185, 129, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(52, 211, 153, 0.05) 0%, transparent 50%),
                url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%2310b981' fill-opacity='0.02'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        /* Enhanced Animations */
        @keyframes slide-in {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes fade-in-slow {
            from {
                opacity: 0;
                transform: scale(0.9);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-6px);
            }
        }

        @keyframes bounce-slow {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-15px);
            }
        }

        @keyframes pulse-slow {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.7;
            }
        }

        .animate-slide-in {
            animation: slide-in 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .animate-fade-in-slow {
            animation: fade-in-slow 0.8s ease-out;
        }

        .animate-float {
            animation: float 3s ease-in-out infinite;
        }

        .animate-bounce-slow {
            animation: bounce-slow 2s ease-in-out infinite;
        }

        .animate-pulse-slow {
            animation: pulse-slow 2s ease-in-out infinite;
        }

        /* Message Bubble Effects */
        .message-bubble {
            backdrop-filter: blur(10px);
            position: relative;
            z-index: 1;
        }

        .message-bubble::before {
            content: '';
            position: absolute;
            inset: -2px;
            border-radius: inherit;
            padding: 2px;
            background: linear-gradient(135deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .message-bubble:hover::before {
            opacity: 1;
        }

        /* Custom Scrollbar */
        #messages-container::-webkit-scrollbar {
            width: 8px;
        }

        #messages-container::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
            border-radius: 10px;
        }

        #messages-container::-webkit-scrollbar-thumb {
            background: linear-gradient(to bottom, #10b981, #059669);
            border-radius: 10px;
            border: 2px solid transparent;
            background-clip: padding-box;
        }

        #messages-container::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(to bottom, #059669, #047857);
            background-clip: padding-box;
        }

        #message-input::-webkit-scrollbar {
            width: 4px;
        }

        #message-input::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 2px;
        }

        .dark #message-input::-webkit-scrollbar-thumb {
            background: #4b5563;
        }

        /* Input Placeholder */
        #message-input:empty:before {
            content: attr(data-placeholder);
            color: #9ca3af;
            pointer-events: none;
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-weight: 500;
        }

        .dark #message-input:empty:before {
            color: #6b7280;
        }

        #message-input:focus {
            outline: none;
        }

        /* Send Button Animation */
        #send-btn {
            position: relative;
            overflow: hidden;
        }

        #send-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        #send-btn:active::before {
            width: 300px;
            height: 300px;
        }
    </style>

    <script>
        let chatId = {{ $chat->id }};
        window.currentUserId = window.currentUserId || {{ Auth::id() }};
        let autoScroll = true;
        let messageInput;

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
                            component: 'chat-show'
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
                        component: 'chat-show'
                    });
                }
                return null;
            }
        }

        // Safe Livewire set helper
        function safeLivewireSet(property, value) {
            if (window.Livewire && @this && typeof @this.set === 'function') {
                try {
                    return @this.set(property, value);
                } catch (error) {
                    console.error('Livewire set error:', error);
                    if (window.logger) {
                        window.logger.error('Livewire set failed', {
                            property,
                            value,
                            error: error.message,
                            component: 'chat-show'
                        });
                    }
                }
            } else {
                console.warn('Livewire not ready for set:', property);
                if (window.logger) {
                    window.logger.warn('Livewire not ready for set', {
                        property,
                        livewireExists: !!window.Livewire,
                        thisExists: !!@this,
                        component: 'chat-show'
                    });
                }
                return null;
            }
        }

        function scrollToBottom() {
            const container = document.getElementById('messages-container');
            if (container && autoScroll) {
                container.scrollTo({
                    top: container.scrollHeight,
                    behavior: 'smooth'
                });
            }
        }

        function markNotificationsAsRead() {
            safeLivewireCall('call', 'markNotificationsRead');
        }

        function updateHiddenInput() {
            if (messageInput) {
                const text = messageInput.innerText.trim();
                document.getElementById('hidden-message').value = text;
                safeLivewireSet('newMessage', text);
            }
        }

        // Initialize Message Input
        document.addEventListener('DOMContentLoaded', () => {
            messageInput = document.getElementById('message-input');

            if (messageInput) {
                // Handle input changes
                messageInput.addEventListener('input', function() {
                    updateHiddenInput();

                    // Show/hide placeholder
                    if (this.innerText.trim() === '') {
                        this.classList.add('empty');
                    } else {
                        this.classList.remove('empty');
                    }

                    // Auto-resize
                    this.style.height = 'auto';
                    this.style.height = Math.min(this.scrollHeight, 128) + 'px';
                });

                // Handle paste - strip formatting
                messageInput.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const text = (e.clipboardData || window.clipboardData).getData('text/plain');
                    document.execCommand('insertText', false, text);
                });

                // Handle Enter key
                // messageInput.addEventListener('keydown', function(e) {
                //     if (e.key === 'Enter' && !e.shiftKey) {
                //         e.preventDefault();
                //         if (this.innerText.trim() !== '') {
                //             document.getElementById('send-btn').click();
                //         }
                //     }
                // });
            }

            const container = document.getElementById('messages-container');
            if (container) {
                container.addEventListener('scroll', () => {
                    const isAtBottom = container.scrollTop + container.clientHeight >= container
                        .scrollHeight - 10;
                    autoScroll = isAtBottom;
                });

                setTimeout(scrollToBottom, 200);
            }
        });

        // Auto scroll to bottom when new messages arrive
        document.addEventListener('livewire:updated', () => {
            setTimeout(scrollToBottom, 100);
        });

        // Handle browser notifications and events
        document.addEventListener('livewire:init', () => {
            Livewire.on('new-messages-loaded', () => {
                setTimeout(scrollToBottom, 100);
            });

            // Clear editor after message sent
            Livewire.on('message-sent', () => {
                if (messageInput) {
                    messageInput.innerText = '';
                    messageInput.style.height = 'auto';
                    messageInput.classList.add('empty');
                    updateHiddenInput();
                    messageInput.focus();
                }
            });
        });

        // Mark notifications as read on various user interactions
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                markNotificationsAsRead();
            }
        });

        window.addEventListener('focus', () => {
            markNotificationsAsRead();
        });

        document.addEventListener('click', () => {
            markNotificationsAsRead();
        });

        let scrollTimeout;
        document.addEventListener('scroll', () => {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                markNotificationsAsRead();
            }, 1000);
        });

        // Request notification permission
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
    </script>
</div>
