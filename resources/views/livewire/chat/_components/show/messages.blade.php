<?php

use Livewire\Volt\Component;
use App\Models\Chat;
use App\Models\Message;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

new class extends Component {
    public $chat;
    public $lastMessageId;
    public $refreshKey = 0; // Add refresh key to force re-render
    public $isRefreshing = false; // Add lock to prevent concurrent refreshes

    public function mount(Chat $chat)
    {
        $this->chat = $chat;
        $this->lastMessageId = Message::where('chat_id', $chat->id)
            ->orderBy('created_at', 'desc')
            ->first()?->id ?? null;
    }

    public function messages()
    {
        try {
            // Query messages directly to avoid any relationship caching issues
            $messages = Message::where('chat_id', $this->chat->id)
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->get();
            
            // Only log in debug mode to avoid log spam
            if (config('app.debug')) {
                Log::debug('Messages retrieved', [
                    'chat_id' => $this->chat->id,
                    'message_count' => $messages->count(),
                    'user_id' => Auth::id()
                ]);
            }
            
            return $messages;
        } catch (\Exception $e) {
            Log::error('Error retrieving messages', [
                'chat_id' => $this->chat->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            return collect(); // Return empty collection on error
        }
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

    public function markNotificationsRead()
    {
        $this->markChatNotificationsAsRead();
    }

    public function refreshMessages()
    {
        // Prevent concurrent refreshes
        if ($this->isRefreshing) {
            return;
        }
        
        $this->isRefreshing = true;
        
        try {
            $latestMessage = Message::where('chat_id', $this->chat->id)
                ->orderBy('created_at', 'desc')
                ->first();
            $user = Auth::user();

            // Safely update user's last access time
            if ($user) {
                $chatUser = $user->chatUsers()->where('chat_id', $this->chat->id)->first();
                if ($chatUser) {
                    $chatUser->latest_accessed_at = now();
                    $chatUser->save();
                }
            }

            // Only trigger updates if there are new messages
            if ($latestMessage && $latestMessage->id > $this->lastMessageId) {
                $this->lastMessageId = $latestMessage->id;
                $this->refreshKey++; // Force component re-render
                $this->markChatNotificationsAsRead();
                $this->dispatch('new-messages-loaded');
            }
        } catch (\Exception $e) {
            Log::error('Error refreshing messages', [
                'chat_id' => $this->chat->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
        } finally {
            $this->isRefreshing = false;
        }
    }
};

?>

<div class="space-y-6" wire:poll.5s="refreshMessages" wire:key="messages-{{ $refreshKey }}" id="messages-container">
    @php $prevDate = null; @endphp
    @foreach ($this->messages() as $message)
        @php
            $isOwnMessage = $message->user_id === Auth::id();
            $isReaded = $message->readed_at !== null;
            $currentDate = $message->created_at->toDateString();
        @endphp

        @if ($prevDate !== $currentDate)
            <div class="flex items-center justify-center my-8">
                <div class="flex-grow h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent"></div>
                <span class="mx-4 px-6 py-2 rounded-full bg-white shadow text-gray-600 text-sm font-semibold border border-gray-200">
                    {{ \Carbon\Carbon::parse($message->created_at)->translatedFormat('l, d F Y') }}
                </span>
                <div class="flex-grow h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent"></div>
            </div>
            @php $prevDate = $currentDate; @endphp
        @endif

        <div class="transition-all duration-200">
            @livewire('chat._components.show.partials.message', ['message' => $message, 'isOwnMessage' => $isOwnMessage, 'isReaded' => $isReaded])
        </div>
    @endforeach


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
        document.addEventListener('livewire:init', () => {
            // Listen for new messages event
            Livewire.on('new-messages-loaded', () => {
                // Scroll to bottom when new messages are loaded
                const container = document.getElementById('messages-container');
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            });
        });
    </script>
</div>
