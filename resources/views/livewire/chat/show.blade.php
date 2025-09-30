<?php

use function Livewire\Volt\{computed, state, on, mount};
use App\Models\Chat;
use App\Models\Message;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Validate;

new class extends \Livewire\Volt\Component {
    public ?Chat $chat = null;
    public string $newMessage = '';
    public int $lastMessageId = 0;

    public function messages()
    {
        if (!$this->chat) {
            return collect();
        }

        $messages = $this->chat->messages()->with('user')->latest()->take(50)->get()->values();

        // Update last message ID to track new messages
        if ($messages->isNotEmpty()) {
            $this->lastMessageId = $messages->last()->id;
        }

        return $messages;
    }

    public function mount(Chat $chat)
    {
        // Check if user is member of this chat
        if (!$chat->members->contains(Auth::user()) && !Auth::user()->hasRole('admin')) {
            abort(403, 'Anda tidak memiliki akses ke chat ini.');
        }

        $this->chat = $chat;

        // Set initial last message ID
        $lastMessage = $this->chat->messages()->latest()->first();
        $this->lastMessageId = $lastMessage ? $lastMessage->id : 0;

        // Mark all chat-related notifications as read
        $this->markChatNotificationsAsRead();

        Log::info('User accessed chat', [
            'chat_id' => $chat->id,
            'chat_title' => $chat->title,
            'user_name' => Auth::user()->name,
            'user_id' => Auth::id(),
        ]);
    }

    public function markChatNotificationsAsRead()
    {
        // Mark all unread notifications related to this chat as read
        $updatedCount = Notification::markChatNotificationsAsRead(Auth::id(), $this->chat->id);

        if ($updatedCount > 0) {
            Log::info('Chat notifications marked as read', [
                'chat_id' => $this->chat->id,
                'user_id' => Auth::id(),
                'notifications_count' => $updatedCount,
            ]);

            // Dispatch event to refresh notification components
            $this->dispatch('notifications-updated');
        }

        return $updatedCount;
    }

    public function sendMessage()
    {
        $this->newMessage = trim($this->newMessage);
        if (strlen($this->newMessage) < 1) {
            $this->addError('newMessage', 'Pesan harus terdiri dari minimal 1 karakter.');
            return;
        }
        if (strlen($this->newMessage) > 1000) {
            $this->addError('newMessage', 'Pesan tidak boleh lebih dari 1000 karakter.');
            return;
        }

        $message = Message::create([
            'chat_id' => $this->chat->id,
            'user_id' => Auth::id(),
            'content' => $this->newMessage,
        ]);

        Log::info('Message sent', [
            'message_id' => $message->id,
            'chat_id' => $this->chat->id,
            'chat_title' => $this->chat->title,
            'user_name' => Auth::user()->name,
            'user_id' => Auth::id(),
            'content_length' => strlen($this->newMessage),
        ]);

        $this->reset('newMessage');

        // Update last message ID
        $this->lastMessageId = $message->id;

        // Dispatch browser notification
        $this->dispatch('new-message-sent', [
            'chat_title' => $this->chat->title,
            'user_name' => Auth::user()->name,
            'message' => \Str::limit($message->content, 50),
        ]);
    }

    public function deleteMessage($messageId)
    {
        $message = Message::findOrFail($messageId);

        // Only sender or admin can delete
        if ($message->user_id === Auth::id() || Auth::user()->hasRole('admin')) {
            Log::info('Message deleted', [
                'message_id' => $message->id,
                'chat_id' => $this->chat->id,
                'deleted_by' => Auth::user()->name,
                'user_id' => Auth::id(),
            ]);

            $message->delete();
        }
    }

    public function refreshMessages()
    {
        // Check if there are new messages
        $latestMessage = $this->chat->messages()->latest()->first();
        
        if ($latestMessage && $latestMessage->id > $this->lastMessageId) {
            // There are new messages, refresh the component
            $this->lastMessageId = $latestMessage->id;
            
            // Mark new chat notifications as read since user is actively viewing
            $this->markChatNotificationsAsRead();
            
            // Dispatch event to scroll to bottom
            $this->dispatch('new-messages-loaded');
        }
    }

    public function markNotificationsRead()
    {
        // Public method that can be called from JavaScript
        $this->markChatNotificationsAsRead();
    }

    #[\Livewire\Attributes\On('message-received')]
    public function onMessageReceived()
    {
        // The computed property will automatically refresh
    }
};

?>
<div>
    <div class="flex flex-col h-screen bg-gray-50">
        <!-- Chat Header -->
        <div class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">{{ $chat->title }}</h1>
                    <p class="text-sm text-gray-600">
                        {{ $chat->members->count() }} anggota
                        @if ($chat->description)
                            • {{ $chat->description }}
                        @endif
                    </p>
                </div>
                <div class="flex items-center space-x-4">
                    @if (Auth::user()->hasRole('admin') || $chat->created_by === Auth::id())
                        <a href="{{ route('chat.manage', $chat->slug) }}" class="text-gray-600 hover:text-gray-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                                </path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </a>
                    @endif
                    <a href="{{ route('chat.index') }}" class="text-gray-600 hover:text-gray-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </div>

        <!-- Messages Container -->
        <div class="flex-1 overflow-y-auto px-6 py-4 space-y-4" wire:poll.3s="refreshMessages">
            @forelse($this->messages() as $message)
                <div class="flex {{ $message->user_id === Auth::id() ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-xs lg:max-w-md">
                        <div
                            class="flex items-start space-x-2 {{ $message->user_id === Auth::id() ? 'flex-row-reverse space-x-reverse' : '' }}">
                            <!-- Avatar -->
                            <div class="flex-shrink-0">
                                <div
                                    class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center text-xs font-medium text-gray-700">
                                    {{ $message->user->initials() }}
                                </div>
                            </div>

                            <!-- Message Bubble -->
                            <div class="relative">
                                <div
                                    class="{{ $message->user_id === Auth::id() ? 'bg-blue-600 text-white' : 'bg-white border border-gray-200 text-gray-900' }} px-4 py-2 rounded-lg shadow-sm">
                                    <div
                                        class="text-xs {{ $message->user_id === Auth::id() ? 'text-blue-100' : 'text-gray-500' }} mb-1">
                                        {{ $message->user->name }}
                                        @if ($message->is_edited)
                                            <span class="italic">(edited)</span>
                                        @endif
                                    </div>
                                    <div class="text-sm">{{ $message->content }}</div>
                                    <div
                                        class="text-xs {{ $message->user_id === Auth::id() ? 'text-blue-100' : 'text-gray-400' }} mt-1">
                                        {{ $message->created_at->format('H:i') }}
                                    </div>
                                </div>

                                <!-- Message Actions -->
                                @if ($message->user_id === Auth::id() || Auth::user()->hasRole('admin'))
                                    <div
                                        class="absolute -top-2 {{ $message->user_id === Auth::id() ? '-left-8' : '-right-8' }} opacity-0 hover:opacity-100 transition-opacity">
                                        <button wire:click="deleteMessage({{ $message->id }})"
                                            wire:confirm="Apakah Anda yakin ingin menghapus pesan ini?"
                                            class="w-6 h-6 bg-red-100 hover:bg-red-200 text-red-600 rounded-full flex items-center justify-center text-xs">
                                            ×
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-12">
                    <div class="w-16 h-16 mx-auto mb-4 text-gray-300">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Belum ada pesan</h3>
                    <p class="text-gray-500">Mulai percakapan dengan mengirim pesan pertama!</p>
                </div>
            @endforelse
        </div>

        <!-- Message Input -->
        @can('send-message')
            <div class="bg-white border-t border-gray-200 px-6 py-4">
                <form wire:submit="sendMessage" class="flex space-x-4">
                    <div class="flex-1">
                        <input wire:model="newMessage" type="text" placeholder="Ketik pesan..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            maxlength="1000">
                        @error('newMessage')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>
                    <button type="submit"
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                    </button>
                </form>
            </div>
        @endcan
    </div>

    <script>
        let chatId = {{ $chat->id }};
        window.currentUserId = window.currentUserId || {{ Auth::id() }};
        let autoScroll = true;

        function scrollToBottom() {
            const container = document.querySelector('.overflow-y-auto');
            if (container && autoScroll) {
                container.scrollTop = container.scrollHeight;
            }
        }

        function markNotificationsAsRead() {
            // Call Livewire method to mark notifications as read
            @this.call('markNotificationsRead');
        }

        // Auto scroll to bottom when new messages arrive
        document.addEventListener('livewire:updated', () => {
            setTimeout(scrollToBottom, 100);
        });

        // Handle browser notifications and events
        document.addEventListener('livewire:init', () => {
            Livewire.on('new-message-sent', (data) => {
                if ('Notification' in window && Notification.permission === 'granted') {
                    new Notification(`Ada pesan dari ${data.user_name}`, {
                        body: `Di {{ $chat->title }}: ${data.message}`,
                        icon: '/favicon.ico',
                        tag: 'new-message-' + Date.now(),
                        badge: '/favicon.ico',
                        requireInteraction: false,
                        silent: false
                    });
                }
                autoScroll = true;
                setTimeout(scrollToBottom, 100);
            });

            Livewire.on('new-messages-loaded', () => {
                setTimeout(scrollToBottom, 100);
            });
        });

        // Track scroll position to determine auto-scroll behavior
        document.addEventListener('DOMContentLoaded', () => {
            const container = document.querySelector('.overflow-y-auto');
            if (container) {
                container.addEventListener('scroll', () => {
                    const isAtBottom = container.scrollTop + container.clientHeight >= container.scrollHeight - 10;
                    autoScroll = isAtBottom;
                });
                
                // Initial scroll to bottom
                setTimeout(scrollToBottom, 200);
            }
        });

        // Mark notifications as read when user becomes active or page becomes visible
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                // Page became visible, mark notifications as read
                markNotificationsAsRead();
            }
        });

        window.addEventListener('focus', () => {
            // Window got focus, mark notifications as read
            markNotificationsAsRead();
        });

        // Mark notifications as read when user clicks anywhere in the chat
        document.addEventListener('click', () => {
            markNotificationsAsRead();
        });

        // Mark notifications as read when user scrolls (showing engagement)
        let scrollTimeout;
        document.addEventListener('scroll', () => {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                markNotificationsAsRead();
            }, 1000); // Wait 1 second after scrolling stops
        });

        // Request notification permission
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
    </script>
</div>
