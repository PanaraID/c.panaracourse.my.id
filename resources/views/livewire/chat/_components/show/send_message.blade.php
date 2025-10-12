<?php

use Livewire\Volt\Component;
use App\Models\Chat;
use App\Models\Message;
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
     * Mount component dan set chat aktif.
     */
    public function mount(Chat $chat): void
    {
        $this->chat = $chat;
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
                'required', 'string', 'min:1', 'max:5000',
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

            // 6️⃣ Dispatch events ke frontend
            $this->dispatch('new-message-sent',
                chatTitle: $this->chat->title,
                userName: Auth::user()->name ?? 'Pengguna',
                messageSnippet: Str::limit(strip_tags($message->content), 50)
            );

            $this->dispatch('message-sent');

            // 7️⃣ Reset input form
            $this->reset('newMessage');
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

    <form wire:submit="sendMessage" class="flex items-end gap-4">

        <!-- 📝 Input Pesan -->
        <div class="flex-1 relative">
            <div
                id="message-input-{{ $chat->id }}"
                wire:ignore
                contenteditable="true"
                data-placeholder="Ketik pesan..."
                class="w-full max-h-36 overflow-y-auto px-6 py-4 
                       bg-gradient-to-br from-gray-100 via-gray-50 to-gray-200 
                       dark:from-gray-800 dark:via-gray-900 dark:to-gray-700
                       rounded-3xl text-gray-900 dark:text-gray-100 text-[15px]
                       shadow-lg ring-1 ring-gray-200 dark:ring-gray-700
                       border-2 border-transparent focus:border-emerald-500
                       focus:ring-4 focus:ring-emerald-500/20 focus:outline-none
                       transition-all duration-300 transform-gpu
                       @error('newMessage') border-red-500 ring-4 ring-red-500/20 dark:bg-red-900/10 @enderror"
                style="min-height: 52px; line-height: 1.6;"
                x-data="{}"
                x-init="$nextTick(() => window.initializeMessageInput($el, '{{ $chat->id }}'))">
            </div>

            <input type="hidden" wire:model="newMessage" id="hidden-message-{{ $chat->id }}">
        </div>

        <!-- 🚀 Tombol Kirim -->
        <button
            type="submit"
            id="send-btn-{{ $chat->id }}"
            class="flex-shrink-0 w-14 h-14 rounded-full text-white
                   bg-gradient-to-br from-emerald-500 via-green-500 to-green-600
                   hover:from-emerald-600 hover:to-green-700
                   flex items-center justify-center shadow-xl shadow-emerald-500/40
                   transition-all duration-300 hover:scale-110 active:scale-95
                   disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
            wire:loading.attr="disabled"
            wire:target="sendMessage"
            aria-label="Kirim Pesan">

            <span wire:loading.remove wire:target="sendMessage">
                <svg class="w-7 h-7 drop-shadow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                          d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
            </span>

            <span wire:loading wire:target="sendMessage">
                <svg class="w-7 h-7 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10"
                            stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                          d="M4 12a8 8 0 018-8V0C5.373 0 
                             0 5.373 0 12h4zm2 5.291A7.962 
                             7.962 0 014 12H0c0 3.042 
                             1.135 5.824 3 7.938l3-2.647z"/>
                </svg>
            </span>
        </button>
    </form>

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
                container.scrollTo({ top: container.scrollHeight, behavior: 'smooth' });
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
                try { @this.set(property, value); } catch (e) { console.error(e); }
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
