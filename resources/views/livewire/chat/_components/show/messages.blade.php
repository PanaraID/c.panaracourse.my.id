<?php

use Livewire\Volt\Component;
use App\Models\Chat;
use App\Models\Message;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection; // Import Collection untuk tipe yang lebih jelas

new class extends Component {
    // Properti utama
    public Chat $chat;
    public ?int $lastMessageId = null; // ID pesan terbaru yang dimuat
    public bool $isRefreshing = false;
    public ?Collection $messagesCache = null; 
    public ?\Illuminate\Support\Carbon $lastRefreshTime = null;
    public bool $isUserAtBottom = true;
    
    // Properti Pagination Scroll
    public int $perPage = 50; // Ditingkatkan untuk pengalaman yang lebih baik
    public bool $hasMoreMessages = true;
    public bool $isLoadingMore = false;
    // ID pesan PALING LAMA yang saat ini dimuat (di bagian atas tampilan)
    public ?int $oldestLoadedMessageId = null; 

    /**
     * Mount component and initialize state
     */
    public function mount(Chat $chat): void
    {
        $this->chat = $chat;
        
        // 1. Muat batch pesan terbaru
        $this->messagesCache = $this->loadLatestMessages();
        
        // 2. Set ID pesan terakhir dan ID pesan paling lama yang dimuat
        if ($this->messagesCache->isNotEmpty()) {
            // lastMessageId (ID pesan terbaru)
            $this->lastMessageId = $this->messagesCache->last()->id;
            // oldestLoadedMessageId (ID pesan paling lama yang sudah dimuat)
            $this->oldestLoadedMessageId = $this->messagesCache->first()->id;
        } else {
            // Jika tidak ada pesan, coba dapatkan ID pesan terakhir dari DB (jika ada)
            $this->lastMessageId = $this->getLatestMessageId(); 
        }

        $this->lastRefreshTime = now();
        
        // 3. Periksa apakah ada pesan yang lebih lama
        $this->checkHasMoreMessages();
        
        // Optional: Tandai notifikasi sebagai terbaca
        $this->markChatNotificationsAsRead();
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
        // Hapus cache agar tidak tersimpan di snapshot
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
        return Message::where('chat_id', $this->chat->id)->latest('created_at')->value('id');
    }

    /**
     * Load initial/latest batch of messages
     */
    private function loadLatestMessages(): Collection
    {
        try {
            // Kueri pesan terbaru
            $messages = Message::where('chat_id', $this->chat->id)
                          ->with(['user', 'taggedUsers'])
                          ->latest('created_at')
                          ->limit($this->perPage)
                          ->get()
                          ->reverse(); // Reverse untuk urutan oldest-to-newest
            
            return $messages;
        } catch (\Exception $e) {
            Log::error('Error loading latest messages', [
                'chat_id' => $this->chat->id,
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * Load messages (digunakan oleh Hydrate untuk memuat ulang pesan yang sudah ada)
     */
    public function loadMessages(): Collection
    {
        // Jika tidak ada oldestLoadedMessageId (misal setelah forceRefresh tanpa pesan), muat yang terbaru
        if (!$this->oldestLoadedMessageId) {
            return $this->loadLatestMessages();
        }
        
        try {
            // Hitung berapa banyak pesan yang harus dimuat ulang
            $totalMessagesLoaded = Message::where('chat_id', $this->chat->id)
                ->where('id', '>=', $this->oldestLoadedMessageId) // Memuat semua pesan >= oldest
                ->count();
                
            $messages = Message::where('chat_id', $this->chat->id)
                ->with(['user', 'taggedUsers'])
                ->latest('created_at')
                ->limit($totalMessagesLoaded > 0 ? $totalMessagesLoaded : $this->perPage)
                ->get()
                ->reverse(); 

            return $messages;
            
        } catch (\Exception $e) {
            Log::error('Error re-loading messages', [
                'chat_id' => $this->chat->id,
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * Load more older messages (saat scroll ke atas)
     */
    public function loadMoreMessages(): void
    {
        if ($this->isLoadingMore || !$this->hasMoreMessages || !$this->oldestLoadedMessageId) {
            $this->isLoadingMore = false; 
            return;
        }

        $this->isLoadingMore = true;

        try {
            // ID pesan PALING LAMA yang saat ini ditampilkan
            $currentOldestId = $this->oldestLoadedMessageId;
            
            // Kueri pesan yang ID-nya lebih kecil (lebih lama) dari ID pesan paling lama yang sudah dimuat
            $olderMessages = Message::where('chat_id', $this->chat->id)
                ->where('id', '<', $currentOldestId)
                ->with(['user', 'taggedUsers'])
                ->latest('created_at') 
                ->limit($this->perPage)
                ->get()
                ->reverse(); 

            if ($olderMessages->isEmpty()) {
                $this->hasMoreMessages = false;
            } else {
                // Prepend (gabungkan di depan) pesan lama ke cache yang ada
                $this->messagesCache = $olderMessages->concat($this->messagesCache);
                
                // Perbarui ID pesan paling lama yang dimuat
                $this->oldestLoadedMessageId = $olderMessages->first()->id;
                
                // Periksa apakah masih ada pesan yang lebih lama
                $this->checkHasMoreMessages();
                
                // Dispatch event untuk mempertahankan posisi scroll
                $this->dispatch('older-messages-loaded', ['oldestMessageId' => $currentOldestId]);
            }
        } catch (\Exception $e) {
            Log::error('Error loading more messages', [
                'chat_id' => $this->chat->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
        } finally {
            $this->isLoadingMore = false;
        }
    }

    /**
     * Check if there are more messages to load
     */
    private function checkHasMoreMessages(): void
    {
        if (!$this->oldestLoadedMessageId) {
            // Jika tidak ada pesan yang dimuat, anggap ada pesan jika ada pesan di DB.
            $this->hasMoreMessages = Message::where('chat_id', $this->chat->id)->exists();
            return;
        }

        // Hitung pesan yang lebih lama dari oldestLoadedMessageId
        $count = Message::where('chat_id', $this->chat->id)
            ->where('id', '<', $this->oldestLoadedMessageId)
            ->count();

        $this->hasMoreMessages = $count > 0;
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
        // Muat batch pesan terbaru
        $this->messagesCache = $this->loadLatestMessages();
        
        $latestMessage = $this->messagesCache->last();

        if ($latestMessage) {
            $this->lastMessageId = $latestMessage->id;
        }
        
        // Atur ulang oldestLoadedMessageId ke ID pesan paling lama di batch baru
        $this->oldestLoadedMessageId = $this->messagesCache->first()->id ?? null;
        
        $this->checkHasMoreMessages();
        
        // Mark as read after refresh
        $this->markChatNotificationsAsRead();
    }

    /**
     * Livewire event listeners
     */
    public function getListeners()
    {
        return [
            'forceRefresh' => 'forceRefresh',
            'refreshMessages' => 'refreshMessages',
            'loadMoreMessages' => 'loadMoreMessages',
        ];
    }

    /**
     * Mark all chat notifications as read
     */
    public function markChatNotificationsAsRead(): int
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

        $chatUser = $user->chatUsers()->where('chat_id', $this->chat->id)->first();

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
     * Refresh messages from database (only check for new messages)
     */
    public function refreshMessages(): void
    {
        if (!$this->shouldRefresh()) {
            return;
        }

        $this->isRefreshing = true;
        $this->lastRefreshTime = now();

        try {
            $latestMessage = Message::where('chat_id', $this->chat->id)->latest('created_at')->first();

            $this->updateUserLastAccess();

            // Hanya picu update jika ada pesan baru
            if ($this->hasNewMessages($latestMessage)) {
                // Muat hanya pesan baru
                $newMessages = Message::where('chat_id', $this->chat->id)
                    ->where('id', '>', $this->lastMessageId)
                    ->with(['user', 'taggedUsers'])
                    ->orderBy('created_at', 'asc')
                    ->get();

                if ($newMessages->isNotEmpty()) {
                    $this->lastMessageId = $latestMessage->id;
                    $this->messagesCache = $this->messagesCache->concat($newMessages);
                    $this->markChatNotificationsAsRead();
                    
                    $this->dispatch('new-messages-loaded');
                }
            }
        } catch (\Exception $e) {
            Log::error('Error refreshing messages', [
                'chat_id' => $this->chat->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
        // Hanya pertimbangkan pesan baru yang BUKAN dikirim oleh pengguna saat ini
        return $latestMessage
            && $latestMessage->id > $this->lastMessageId
            && $latestMessage->user_id !== Auth::id();
    }

    /**
     * Set user scroll position status
     */
    public function setUserAtBottom($isAtBottom): void
    {
        $this->isUserAtBottom = $isAtBottom;
    }
};

?>

<div class="space-y-6 chat-background" wire:poll.3s="refreshMessages" id="messages-container"
    wire:key="chat-container-{{ $chat->id }}">

    {{-- Loading More Indicator --}}
    @if($hasMoreMessages)
        <div class="flex justify-center py-4" id="load-more-trigger">
            <div wire:loading.remove wire:target="loadMoreMessages">
                <button 
                    wire:click="loadMoreMessages"
                    class="px-4 py-2 text-sm font-medium text-emerald-600 dark:text-emerald-400 bg-white dark:bg-gray-800 rounded-full shadow-md hover:shadow-lg transition-all duration-200 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-50 dark:hover:bg-gray-700">
                    <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                    </svg>
                    Muat pesan sebelumnya
                </button>
            </div>
            <div wire:loading wire:target="loadMoreMessages" class="flex items-center space-x-2 text-emerald-600 dark:text-emerald-400">
                <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm font-medium">Memuat...</span>
            </div>
        </div>
    @endif

    @php $prevDate = null; @endphp
    @forelse ($this->messages as $message)
        @php
            $isOwnMessage = $message->user_id === Auth::id();
            // Anda mungkin perlu memastikan kolom 'readed_at' ada atau menggunakan logika read receipt lain
            $isReaded = $message->readed_at !== null; 
            $currentDate = $message->created_at->toDateString();
        @endphp

        {{-- Date Separator --}}
        @if ($prevDate !== $currentDate)
            <div class="flex items-center justify-center my-8"
                wire:key="date-separator-{{ $message->id }}-{{ $currentDate }}">
                <div
                    class="flex-grow h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent dark:via-gray-600">
                </div>
                <span
                    class="mx-4 px-6 py-2 rounded-full bg-white dark:bg-gray-800 shadow text-gray-600 dark:text-gray-300 text-sm font-semibold border border-gray-200 dark:border-gray-700">
                    {{ $message->created_at->translatedFormat('l, d F Y') }}
                </span>
                <div
                    class="flex-grow h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent dark:via-gray-600">
                </div>
            </div>
            @php $prevDate = $currentDate; @endphp
        @endif

        {{-- Message Bubble --}}
        <div class="transition-all duration-200 animate-slide-in" 
             wire:key="message-wrapper-{{ $message->id }}"
             data-message-id="{{ $message->id }}"
             data-is-readed="{{ $isReaded ? 'true' : 'false' }}">
            @livewire(
                'chat._components.show.partials.message',
                [
                    'message' => $message,
                    'isOwnMessage' => $isOwnMessage,
                    'isReaded' => $isReaded,
                ],
                key('message-' . $message->id)
            )
        </div>
    @empty
        <div class="flex flex-col items-center justify-center py-12 text-center"
            wire:key="empty-state-{{ $chat->id }}">
            <div
                class="w-20 h-20 mb-4 rounded-full bg-gradient-to-br from-emerald-100 to-emerald-200 dark:from-emerald-900 dark:to-emerald-800 flex items-center justify-center animate-bounce-slow">
                <svg class="w-10 h-10 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                    </path>
                </svg>
            </div>
            <p class="text-gray-500 dark:text-gray-400 text-lg font-medium">Belum ada pesan</p>
            <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">Mulai percakapan sekarang</p>
        </div>
    @endforelse

    <style>
        /* ... (CSS tetap sama) ... */
        :root {
            --whatsapp-green: #25D366;
            --whatsapp-green-dark: #20BD5F;
            --emerald-glow: rgba(16, 185, 129, 0.3);
        }

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

        @keyframes bounce-slow {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-15px);
            }
        }

        .animate-slide-in {
            animation: slide-in 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .animate-bounce-slow {
            animation: bounce-slow 2s ease-in-out infinite;
        }

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

        .dark #messages-container::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
        }
    </style>

    <script>
        document.addEventListener('livewire:init', () => {
            let scrollTimeout;
            // Variabel untuk menyimpan tinggi scroll sebelum memuat pesan lama
            let previousScrollHeight = 0; 

            window.scrollToBottom = function() {
                const container = document.getElementById('messages-container');
                if (container) {
                    container.scrollTo({
                        top: container.scrollHeight,
                        behavior: 'smooth'
                    });
                }
            };

            function isUserAtBottom() {
                const container = document.getElementById('messages-container');
                if (!container) return true;
                
                // Threshold 50px
                const threshold = 50; 
                return (container.scrollTop + container.clientHeight >= container.scrollHeight - threshold);
            }

            function isUserAtTop() {
                const container = document.getElementById('messages-container');
                if (!container) return false;
                // Batas 150px untuk memicu pemuatan lebih awal saat scroll ke atas
                return container.scrollTop <= 150; 
            }

            function updateScrollPosition() {
                const atBottom = isUserAtBottom();
                @this.setUserAtBottom(atBottom);
            }

            const container = document.getElementById('messages-container');
            if (container) {
                // Track scroll position for lazy loading
                container.addEventListener('scroll', function() {
                    clearTimeout(scrollTimeout);
                    scrollTimeout = setTimeout(() => {
                        updateScrollPosition();
                        
                        // Auto-load more messages when near top
                        if (isUserAtTop() && @this.hasMoreMessages && !@this.isLoadingMore) {
                            // **Penting:** Catat tinggi scroll sebelum memuat pesan lama
                            previousScrollHeight = container.scrollHeight;
                            @this.loadMoreMessages();
                        }
                    }, 100); // Debounce
                });

                updateScrollPosition();
            }

            // Handle new messages loaded
            Livewire.on('new-messages-loaded', () => {
                setTimeout(() => {
                    if (isUserAtBottom()) {
                        scrollToBottom();
                    }
                    // Opsi lain: tampilkan tombol "Pesan Baru" jika tidak di bawah
                }, 100);
            });

            // Handle older messages loaded - maintain scroll position
            Livewire.on('older-messages-loaded', (data) => {
                setTimeout(() => {
                    const container = document.getElementById('messages-container');
                    if (container) {
                        // Cari pesan yang ID-nya dikirim (pesan paling atas sebelum batch baru)
                        const oldTopMessage = container.querySelector(`[data-message-id="${data.oldestMessageId}"]`);
                        
                        if (oldTopMessage) {
                            // Scroll ke elemen lama untuk mempertahankan posisi
                            oldTopMessage.scrollIntoView({ block: 'start' });
                            // Tambahan kecil (misalnya 5px) untuk kenyamanan
                            container.scrollTop -= 5; 
                        } else {
                            // Fallback: Pertahankan posisi relatif berdasarkan selisih tinggi scroll
                            const newScrollHeight = container.scrollHeight;
                            const scrollDiff = newScrollHeight - previousScrollHeight;
                            if (scrollDiff > 0) {
                               container.scrollTop += scrollDiff;
                            }
                        }
                    }
                }, 50); // Set timeout singkat untuk menunggu DOM diperbarui
            });

            Livewire.hook('message.failed', (message, component) => {
                console.error('Livewire message failed:', {
                    message: message,
                    component: component.id,
                    timestamp: new Date().toISOString()
                });
            });
        });

        // Initial scroll setup (menggulir ke bawah atau ke pesan yang belum dibaca)
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                const container = document.getElementById('messages-container');
                if (container) {
                    const firstUnreadElement = container.querySelector('[data-is-readed="false"]');

                    if (firstUnreadElement) {
                        firstUnreadElement.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                    } else {
                        scrollToBottom();
                    }
                }
            }, 200);
        });

        // Force refresh saat tab kembali aktif
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && window.Livewire) {
                setTimeout(() => {
                    try {
                        Livewire.dispatch('forceRefresh');
                    } catch (error) {
                        console.error('Error dispatching forceRefresh:', error);
                    }
                }, 500);
            }
        });
    </script>

    {{-- Script untuk auto scroll yang lebih modern (menggantikan logika lama) --}}
    <script>
        document.addEventListener('livewire:initialized', () => {
            const container = document.getElementById('messages-container');
            if (container) {
                // Gunakan MutationObserver untuk memantau penambahan pesan baru ke DOM
                const observer = new MutationObserver((mutationsList, observer) => {
                    for(const mutation of mutationsList) {
                        if (mutation.type === 'childList') {
                            // Periksa apakah ada pesan baru yang ditambahkan (kecuali pemuatan lama)
                            if (mutation.addedNodes.length > 0) {
                                
                                // Jika pengguna berada di bagian bawah, auto-scroll.
                                const threshold = container.scrollHeight * 0.005;
                                const isAtBottom = container.scrollTop + container.clientHeight >= container.scrollHeight - threshold;
                                
                                if (isAtBottom) {
                                    container.scrollTo({
                                        top: container.scrollHeight,
                                        behavior: 'smooth'
                                    });
                                } 
                                // Di sini Anda bisa menambahkan logika untuk menampilkan "Pesan Baru" jika tidak di bawah
                            }
                        }
                    }
                });
                
                // Mulai observasi pada container dengan konfigurasi:
                // childList: true (mengamati penambahan/penghapusan elemen anak langsung)
                // subtree: true (mengamati semua keturunan dari elemen target)
                observer.observe(container, {
                    childList: true,
                    subtree: true
                });
            }
        });
    </script>
</div>