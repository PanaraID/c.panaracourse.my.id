<?php

use Livewire\Volt\Component;
use App\Models\Chat;
use App\Models\Message;
use App\Models\MessageTag;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

new class extends Component {
    /**
     * Chat yang sedang aktif.
     */
    public Chat $chat;

    /**
     * Isi pesan baru.
     */
    public string $newMessage = '';

    /**
     * ID pesan terakhir (opsional).
     */
    public ?int $lastMessageId = null;

    /**
     * Array ID user yang akan di-tag.
     */
    public array $taggedUsers = [];

    /**
     * Status modal tag user.
     */
    public bool $showTagModal = false;

    /**
     * Mount component dan set chat aktif.
     */
    public function mount(Chat $chat): void
    {
        $this->chat = $chat;
    }

    /**
     * Buka modal untuk tag user.
     */
    public function openTagModal(): void
    {
        $this->showTagModal = true;
    }

    /**
     * Tutup modal tag user.
     */
    public function closeTagModal(): void
    {
        $this->showTagModal = false;
    }

    /**
     * Toggle tag user.
     */
    public function toggleTagUser(int $userId): void
    {
        if (in_array($userId, $this->taggedUsers)) {
            $this->taggedUsers = array_values(array_filter($this->taggedUsers, fn($id) => $id !== $userId));
        } else {
            $this->taggedUsers[] = $userId;
        }
    }

    /**
     * Get chat members yang bisa di-tag.
     */
    public function getChatMembersProperty()
    {
        return $this->chat->members()->where('users.id', '!=', Auth::id())->select('users.id', 'users.name', 'users.email')->get();
    }

    /**
     * Kirim pesan ke database.
     */
    public function sendMessage(): void
    {
        // 1️⃣ Sanitasi awal
        $this->newMessage = trim($this->newMessage);

        if (Str::length($this->newMessage) < 1) {
            $this->addError('newMessage', 'Pesan tidak boleh kosong.');
            return;
        }

        // 2️⃣ Validasi isi pesan
        $this->validate([
            'newMessage' => [
                'required',
                'string',
                'min:1',
                'max:5000',
                function ($attribute, $value, $fail) {
                    if (preg_match('/(\+62|62|0)?[\s\-]?\d{2,4}[\s\-]?\d{2,4}[\s\-]?\d{2,5}/', $value)) {
                        $fail('Pesan tidak boleh mengandung nomor telepon.');
                    }
                },
            ],
        ]);

        // 3️⃣ Simpan pesan
        $content = $this->newMessage;

        try {
            $message = Message::create([
                'chat_id' => $this->chat->id,
                'user_id' => Auth::id(),
                'content' => $content,
            ]);

            // 4️⃣ Log aktivitas
            Log::info('Message sent successfully', [
                'message_id' => $message->id,
                'chat_id' => $this->chat->id,
                'user_name' => Auth::user()->name ?? 'Unknown',
                'content_length' => Str::length($content),
            ]);

            // 5️⃣ Update state
            $this->lastMessageId = $message->id;

            // 5.5️⃣ Simpan tags jika ada
            if (!empty($this->taggedUsers)) {
                foreach ($this->taggedUsers as $userId) {
                    MessageTag::create([
                        'message_id' => $message->id,
                        'tagged_user_id' => $userId,
                        'tagged_by_user_id' => Auth::id(),
                        'is_read' => false,
                    ]);
                }
            }

            // 6️⃣ Dispatch events ke frontend
            $this->dispatch('new-message-sent', chatTitle: $this->chat->title, userName: Auth::user()->name ?? 'Pengguna', messageSnippet: Str::limit(strip_tags($message->content), 50));

            $this->dispatch('message-sent');

            // 7️⃣ Reset input form
            $this->reset(['newMessage', 'taggedUsers']);
            $this->showTagModal = false;
        } catch (\Exception $e) {
            // 8️⃣ Penanganan error
            Log::error('Failed to send message', [
                'chat_id' => $this->chat->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->addError('newMessage', 'Terjadi kesalahan sistem saat mengirim pesan. Silakan coba lagi.');
        }
    }
};
?>

<!-- ======================== -->
<!-- 💬 FORM INPUT PESAN -->
<!-- ======================== -->

<div
    class="sticky bottom-0 z-10 bg-slate-400 dark:bg-gray-900/90 backdrop-blur-xl border-t border-gray-200 shadow-xl px-4 sm:px-6 py-4">

    <!-- ======================== -->
    <!-- 🏷️ TOMBOL TAG -->
    <!-- ======================== -->
    <div class="mb-3 flex items-center justify-between">
        @if (count($taggedUsers) > 0)
            <div class="flex flex-wrap gap-1">
                @foreach ($this->chatMembers as $member)
                    @if (in_array($member->id, $taggedUsers))
                        <span
                            class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 
                                   rounded-full text-xs font-medium flex items-center gap-1">
                            {{ $member->name }}
                            <button type="button" wire:click="toggleTagUser({{ $member->id }})"
                                class="text-blue-600 hover:text-blue-800 ml-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </span>
                    @endif
                @endforeach
            </div>
        @endif
    </div>

    <section>
        <form wire:submit="sendMessage" class="flex items-end gap-3">
            <!-- 📝 Input Pesan -->
            <div class="flex-1 relative flex items-center gap-2">
                <div id="message-input-{{ $chat->id }}" wire:ignore contenteditable="true"
                    data-placeholder="Ketik pesan..."
                    class="w-full max-h-36 overflow-y-auto px-6 py-4
                        bg-gradient-to-br from-gray-100 via-gray-50 to-gray-200
                        dark:from-gray-800 dark:via-gray-900 dark:to-gray-700
                        rounded-3xl text-gray-900 dark:text-gray-100 text-[15px]
                        shadow-lg ring-1 ring-gray-200 dark:ring-gray-700
                        border-2 border-transparent focus:border-emerald-500
                        focus:ring-4 focus:ring-emerald-500/20 focus:outline-none
                        transition-all duration-300 transform-gpu
                        placeholder:text-gray-400 dark:placeholder:text-gray-500
                        @error('newMessage') border-red-500 ring-4 ring-red-500/20 dark:bg-red-900/10 @enderror"
                    style="min-height: 52px; line-height: 1.6;"
                    x-data="{}"
                    x-init="$nextTick(() => window.initializeMessageInput($el, '{{ $chat->id }}'))"></div>
                <input type="hidden" wire:model="newMessage" id="hidden-message-{{ $chat->id }}">

                <!-- 🏷️ Tombol Tag -->
                <button type="button" wire:click="openTagModal"
                    class="flex items-center gap-2 px-3 py-2 rounded-2xl text-sm font-medium
                        bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700
                        text-white shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                    <span>Tag</span>
                    @if (count($taggedUsers) > 0)
                        <span
                            class="bg-white/30 px-2 py-1 rounded-full text-xs font-semibold shadow text-blue-900 dark:text-blue-200 ml-1">
                            {{ count($taggedUsers) }}
                        </span>
                    @endif
                </button>
            </div>
            <!-- 🚀 Tombol Kirim -->
            <button type="submit" id="send-btn-{{ $chat->id }}"
                class="flex-shrink-0 w-14 h-14 rounded-full text-white
                    bg-gradient-to-br from-emerald-500 via-green-500 to-green-600
                    hover:from-emerald-600 hover:to-green-700
                    flex items-center justify-center shadow-xl shadow-emerald-500/40
                    transition-all duration-300 hover:scale-110 active:scale-95
                    disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100
                    focus:outline-none focus:ring-2 focus:ring-emerald-400"
                wire:loading.attr="disabled" wire:target="sendMessage" aria-label="Kirim Pesan">
                <span wire:loading.remove wire:target="sendMessage">
                    <svg class="w-7 h-7 drop-shadow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                </span>
                <span wire:loading wire:target="sendMessage">
                    <svg class="w-7 h-7 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0
                                0 5.373 0 12h4zm2 5.291A7.962
                                7.962 0 014 12H0c0 3.042
                                1.135 5.824 3 7.938l3-2.647z" />
                    </svg>
                </span>
            </button>
        </form>
    </section>

    <!-- ⚠️ Error Message -->
    @error('newMessage')
        <div
            class="mt-3 mx-auto max-w-lg px-4 py-2 rounded-xl text-sm font-medium
                   bg-red-50 dark:bg-red-900/30 border border-red-400 dark:border-red-800
                   text-red-700 dark:text-red-400 animate-pulse">
            {{ $message }}
        </div>
    @enderror

    <!-- ======================== -->
    <!-- 🏷️ MODAL TAG -->
    <!-- ======================== -->
    @if ($showTagModal)
        <div class="fixed inset-0 bg-gradient-to-br from-black/60 via-gray-900/70 to-black/60 backdrop-blur-lg z-50 flex items-center justify-center p-4"
            wire:click="closeTagModal">
            <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-2xl w-full max-w-lg max-h-[75vh] overflow-hidden ring-2 ring-emerald-400/10"
                wire:click.stop>
                <div class="p-6 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight flex items-center gap-2">
                        <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        Tag Peserta
                    </h3>
                    <button wire:click="closeTagModal"
                        class="rounded-full p-2 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                        <svg class="w-5 h-5 text-gray-500 dark:text-gray-300" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div
                    class="p-6 max-h-96 overflow-y-auto bg-gradient-to-b from-white via-gray-50 to-gray-100 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
                    @if ($this->chatMembers->count() > 0)
                        <div class="space-y-3">
                            @foreach ($this->chatMembers as $member)
                                <div class="flex items-center justify-between p-3 rounded-2xl
                        {{ in_array($member->id, $taggedUsers) ? 'bg-emerald-50 dark:bg-emerald-900/20 border-2 border-emerald-400 dark:border-emerald-600' : 'bg-gray-50 dark:bg-gray-800 border-2 border-transparent hover:border-emerald-300 dark:hover:border-emerald-600' }}
                        transition-all duration-200 cursor-pointer group"
                                    wire:click="toggleTagUser({{ $member->id }})">
                                    <div class="flex items-center space-x-3">
                                        <div
                                            class="w-11 h-11 bg-gradient-to-br from-emerald-500 to-blue-600
                                rounded-full flex items-center justify-center text-white font-bold text-base shadow-lg">
                                            {{ \Str::limit($member->name, 2, '') }}
                                        </div>
                                        <div>
                                            <div class="font-semibold text-gray-900 dark:text-white">
                                                {{ $member->name }}</div>
                                        </div>
                                    </div>
                                    <div class="flex items-center">
                                        @if (in_array($member->id, $taggedUsers))
                                            <svg class="w-6 h-6 text-emerald-500 drop-shadow" fill="currentColor"
                                                viewBox="0 0 24 24">
                                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        @else
                                            <svg class="w-6 h-6 text-gray-300 group-hover:text-emerald-400 transition-colors"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <circle cx="12" cy="12" r="10" stroke-width="2" />
                                            </svg>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-10">
                            <svg class="w-14 h-14 text-gray-300 dark:text-gray-700 mx-auto mb-4" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2m5-8a3 3 0 110-6 3 3 0 010 6m5 3a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400 font-medium">Tidak ada peserta lain di chat ini
                            </p>
                        </div>
                    @endif
                </div>

                <div
                    class="p-6 border-t border-gray-200 dark:border-gray-800 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400 font-medium">
                            <svg class="w-4 h-4 inline-block mr-1 text-emerald-500" fill="currentColor"
                                viewBox="0 0 20 20">
                                <circle cx="10" cy="10" r="10" />
                            </svg>
                            {{ count($taggedUsers) }} peserta dipilih
                        </span>
                        <button wire:click="closeTagModal"
                            class="px-6 py-2 bg-gradient-to-r from-emerald-500 to-green-600
                           hover:from-emerald-600 hover:to-green-700 text-white
                           rounded-xl font-semibold shadow-lg transition-all duration-300 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-emerald-400">
                            Selesai
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- ======================== -->
    <!-- 🧠 SCRIPT -->
    <!-- ======================== -->
    <script>
        /**
         * Scroll ke bawah pesan.
         */
        function scrollToBottom() {
            const container = document.getElementById('messages-container');
            if (!container) return;
            requestAnimationFrame(() => {
                container.scrollTo({
                    top: container.scrollHeight,
                    behavior: 'smooth'
                });
            });
        }

        /**
         * Inisialisasi input pesan (Markdown-aware)
         */
        window.initializeMessageInput = function(messageInput, chatId) {
            'use strict';
            if (messageInput.getAttribute('data-initialized') === 'true') return;
            messageInput.setAttribute('data-initialized', 'true');

            const hiddenInput = document.getElementById('hidden-message-' + chatId);

            function safeLivewireSet(property, value) {
                try {
                    @this.set(property, value);
                } catch (e) {
                    console.error(e);
                }
            }

            function updateHiddenInput() {
                const text = messageInput.innerText.trim();
                hiddenInput.value = text;
                safeLivewireSet('newMessage', text);
                messageInput.classList.toggle('empty', text === '');
            }

            messageInput.addEventListener('input', function() {
                updateHiddenInput();
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 144) + 'px';
            });

            updateHiddenInput();
        };

        /**
         * Livewire event listeners
         */
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('message-sent', () => {
                const currentChatId = @js($chat->id);
                const input = document.getElementById('message-input-' + currentChatId);
                const hidden = document.getElementById('hidden-message-' + currentChatId);

                if (input) {
                    input.innerText = '';
                    input.style.height = '48px';
                    input.classList.add('empty');
                }
                if (hidden) hidden.value = '';

                setTimeout(scrollToBottom, 50);
            });

            Livewire.on('new-message-sent', () => setTimeout(scrollToBottom, 100));
        });
    </script>

    <!-- ======================== -->
    <!-- 🎨 STYLE -->
    <!-- ======================== -->
    <style>
        [contenteditable]:empty:before,
        [contenteditable].empty:before {
            content: attr(data-placeholder);
            color: #9ca3af;
            pointer-events: none;
            display: block;
        }

        [contenteditable]:focus:before {
            content: '';
        }

        .dark [contenteditable]:empty:before,
        .dark [contenteditable].empty:before {
            color: #6b7280;
        }
    </style>
</div>
