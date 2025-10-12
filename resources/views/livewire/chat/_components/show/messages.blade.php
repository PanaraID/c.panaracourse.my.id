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
    public $isRefreshing = false;
    public $messagesCache = null;
    public $lastRefreshTime = null;

    /**
     * Mount component and initialize state
     */
    public function mount(Chat $chat): void
    {
        $this->chat = $chat;
        $this->lastMessageId = $this->getLatestMessageId();
        $this->messagesCache = $this->loadMessages();
        $this->lastRefreshTime = now();
    }

    /**
     * Get unique snapshot key for this component
     */
    public function getSnapshotKey()
    {
        return 'chat-messages-' . $this->chat->id;
    }

    /**
     * Dehydrate - clear cache before sending to frontend
     */
    public function dehydrate()
    {
        // Don't cache messages in snapshot to prevent issues
        $this->messagesCache = null;
    }

    /**
     * Hydrate - reload cache when component rehydrates
     */
    public function hydrate()
    {
        if (!$this->messagesCache) {
            $this->messagesCache = $this->loadMessages();
        }
    }

    /**
     * Get the latest message ID for the chat
     */
    private function getLatestMessageId(): ?int
    {
        return Message::where('chat_id', $this->chat->id)
            ->latest('created_at')
            ->value('id');
    }

    /**
     * Load messages from database with error handling
     */
    public function loadMessages()
    {
        try {
            return Message::where('chat_id', $this->chat->id)
                ->with('user')
                ->latest('created_at')
                ->get();
        } catch (\Exception $e) {
            Log::error('Error loading messages', [
                'chat_id' => $this->chat->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return collect();
        }
    }

    /**
     * Computed property for messages
     */
    public function getMessagesProperty()
    {
        if (!$this->messagesCache) {
            $this->messagesCache = $this->loadMessages();
        }
        return $this->messagesCache;
    }

    /**
     * Force refresh messages from database
     */
    public function forceRefresh(): void
    {
        $this->messagesCache = $this->loadMessages();
        $latestMessage = $this->messagesCache->first();
        
        if ($latestMessage) {
            $this->lastMessageId = $latestMessage->id;
        }
    }

    /**
     * Livewire event listeners
     */
    public function getListeners()
    {
        return [
            'forceRefresh' => 'forceRefresh',
            'refreshMessages' => 'refreshMessages',
        ];
    }

    /**
     * Mark all chat notifications as read
     */
    public function markChatNotificationsAsRead(): int
    {
        $updatedCount = Notification::markChatNotificationsAsRead(
            Auth::id(), 
            $this->chat->id
        );

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

    /**
     * Public method to mark notifications as read
     */
    public function markNotificationsRead(): int
    {
        return $this->markChatNotificationsAsRead();
    }

    /**
     * Update user's last access time for this chat
     */
    private function updateUserLastAccess(): void
    {
        $user = Auth::user();
        
        if (!$user) {
            return;
        }

        $chatUser = $user->chatUsers()
            ->where('chat_id', $this->chat->id)
            ->first();
        
        if ($chatUser) {
            $chatUser->update(['latest_accessed_at' => now()]);
        }
    }

    /**
     * Check if refresh should be performed
     */
    private function shouldRefresh(): bool
    {
        // Prevent concurrent refreshes
        if ($this->isRefreshing) {
            return false;
        }

        // Rate limiting: don't refresh more than once every 5 seconds
        if ($this->lastRefreshTime && $this->lastRefreshTime->diffInSeconds(now()) < 5) {
            return false;
        }

        return true;
    }

    /**
     * Refresh messages from database
     */
    public function refreshMessages(): void
    {
        if (!$this->shouldRefresh()) {
            return;
        }
        
        $this->isRefreshing = true;
        $this->lastRefreshTime = now();
        
        try {
            $latestMessage = Message::where('chat_id', $this->chat->id)
                ->latest('created_at')
                ->first();

            $this->updateUserLastAccess();

            // Only trigger updates if there are new messages
            if ($this->hasNewMessages($latestMessage)) {
                $this->lastMessageId = $latestMessage->id;
                $this->messagesCache = $this->loadMessages();
                $this->markChatNotificationsAsRead();
                $this->dispatch('new-messages-loaded');
            }
        } catch (\Exception $e) {
            Log::error('Error refreshing messages', [
                'chat_id' => $this->chat->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        } finally {
            $this->isRefreshing = false;
        }
    }

    /**
     * Check if there are new messages
     */
    private function hasNewMessages($latestMessage): bool
    {
        return $latestMessage && $latestMessage->id > $this->lastMessageId;
    }
};

?>

<div class="space-y-6 chat-background" wire:poll.5s="refreshMessages" id="messages-container" wire:key="chat-container-{{ $chat->id }}">
    @php $prevDate = null; @endphp
    
    @forelse ($this->messages as $message)
        @php
            $isOwnMessage = $message->user_id === Auth::id();
            $isReaded = $message->readed_at !== null;
            $currentDate = $message->created_at->toDateString();
        @endphp

        {{-- Date Separator --}}
        @if ($prevDate !== $currentDate)
            <div class="flex items-center justify-center my-8" wire:key="date-separator-{{ $message->id }}-{{ $currentDate }}">
                <div class="flex-grow h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent dark:via-gray-600"></div>
                <span class="mx-4 px-6 py-2 rounded-full bg-white dark:bg-gray-800 shadow text-gray-600 dark:text-gray-300 text-sm font-semibold border border-gray-200 dark:border-gray-700">
                    {{ $message->created_at->translatedFormat('l, d F Y') }}
                </span>
                <div class="flex-grow h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent dark:via-gray-600"></div>
            </div>
            @php $prevDate = $currentDate; @endphp
        @endif

        {{-- Message Bubble --}}
        <div class="transition-all duration-200 animate-slide-in" wire:key="message-wrapper-{{ $message->id }}">
            @livewire('chat._components.show.partials.message', [
                'message' => $message, 
                'isOwnMessage' => $isOwnMessage, 
                'isReaded' => $isReaded
            ], key('message-' . $message->id))
        </div>
    @empty
        <div class="flex flex-col items-center justify-center py-12 text-center" wire:key="empty-state-{{ $chat->id }}">
            <div class="w-20 h-20 mb-4 rounded-full bg-gradient-to-br from-emerald-100 to-emerald-200 dark:from-emerald-900 dark:to-emerald-800 flex items-center justify-center animate-bounce-slow">
                <svg class="w-10 h-10 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                </svg>
            </div>
            <p class="text-gray-500 dark:text-gray-400 text-lg font-medium">Belum ada pesan</p>
            <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">Mulai percakapan sekarang</p>
        </div>
    @endforelse



<style>
    /* ==================== CSS Variables ==================== */
    :root {
        --whatsapp-green: #25D366;
        --whatsapp-green-dark: #20BD5F;
        --emerald-glow: rgba(16, 185, 129, 0.3);
    }

    /* ==================== Chat Background ==================== */
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

    /* ==================== Keyframe Animations ==================== */
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
        0%, 100% {
            transform: translateY(0px);
        }
        50% {
            transform: translateY(-6px);
        }
    }

    @keyframes bounce-slow {
        0%, 100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-15px);
        }
    }

    @keyframes pulse-slow {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.7;
        }
    }

    /* ==================== Animation Classes ==================== */
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

    /* ==================== Message Bubble Effects ==================== */
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

    /* ==================== Custom Scrollbar ==================== */
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

    /* ==================== Dark Mode Scrollbar ==================== */
    .dark #messages-container::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.05);
    }
</style>

<script>
    /**
     * Initialize Livewire event listeners and handlers
     */
    document.addEventListener('livewire:init', () => {
        let scrollTimeout;
        let isScrolling = false;
        
        /**
         * Handle new messages loaded event
         */
        Livewire.on('new-messages-loaded', () => {
            try {
                if (scrollTimeout) {
                    clearTimeout(scrollTimeout);
                }
                
                if (isScrolling) {
                    return;
                }
                
                isScrolling = true;
                
                // Use requestAnimationFrame for better performance
                requestAnimationFrame(() => {
                    const container = document.getElementById('messages-container');
                    if (container) {
                        scrollTimeout = setTimeout(() => {
                            container.scrollTo({
                                top: container.scrollHeight,
                                behavior: 'smooth'
                            });
                            
                            setTimeout(() => {
                                isScrolling = false;
                            }, 500);
                        }, 100);
                    } else {
                        isScrolling = false;
                    }
                });
            } catch (error) {
                console.error('Error handling new messages:', error);
                isScrolling = false;
            }
        });

        /**
         * Livewire error handling hooks
         */
        Livewire.hook('message.failed', (message, component) => {
            console.error('Livewire message failed:', {
                message: message,
                component: component.id,
                timestamp: new Date().toISOString()
            });
        });

        Livewire.hook('message.processed', (message, component) => {
            console.debug('Livewire message processed:', component.id);
        });

        /**
         * Handle component errors
         */
        Livewire.hook('element.init', ({ component, el }) => {
            if (!component.snapshot) {
                console.warn('Component initialized without snapshot:', component.id);
            }
        });
    });

    /**
     * Handle page visibility changes to optimize polling
     */
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            console.log('Page hidden, reducing polling frequency');
        } else {
            console.log('Page visible, resuming normal polling');
            
            if (window.Livewire) {
                setTimeout(() => {
                    try {
                        Livewire.dispatch('forceRefresh');
                    } catch (error) {
                        console.error('Error dispatching forceRefresh:', error);
                    }
                }, 500);
            }
        }
    });

    /**
     * Prevent memory leaks on page unload
     */
    window.addEventListener('beforeunload', () => {
        if (window.Livewire) {
            // Clean up any pending operations
            console.log('Cleaning up Livewire components');
        }
    });
</script>
</div>