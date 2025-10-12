<?php

use Livewire\Volt\Component;
use App\Models\Chat;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

new class extends Component {
    /**
     * Properti untuk model Chat yang sedang aktif.
     * @var \App\Models\Chat
     */
    public Chat $chat;
    
    /**
     * Isi pesan baru yang akan dikirim.
     * @var string
     */
    public string $newMessage = '';

    /**
     * ID pesan terakhir yang dikirim (untuk referensi client-side jika diperlukan).
     * @var int|null
     */
    public $lastMessageId = null;

    /**
     * Mount component dan set properti chat.
     * @param \App\Models\Chat $chat
     */
    public function mount(Chat $chat): void
    {
        $this->chat = $chat;
    }

    /**
     * Kirim pesan baru ke chat.
     */
    public function sendMessage(): void
    {
        // 1. Validasi dan sanitasi awal
        $this->newMessage = trim($this->newMessage);
        
        // Memastikan pesan tidak kosong setelah trim
        if (Str::length($this->newMessage) < 1) {
            $this->addError('newMessage', 'Pesan tidak boleh kosong.');
            return;
        }

        // 2. Validasi Kustom
        $this->validate([
            'newMessage' => [
                'required',
                'string',
                'min:1',
                'max:5000',
                // Regex untuk mendeteksi nomor telepon (contoh: +62 8xx, 08xx, 62 8xx)
                function ($attribute, $value, $fail) {
                    // Pola ini mendeteksi format yang umum untuk nomor telepon
                    if (preg_match('/(\+62|62|0)?[\s\-]?\d{2,4}[\s\-]?\d{2,4}[\s\-]?\d{2,5}/', $value)) {
                        $fail('Pesan tidak boleh mengandung nomor telepon.');
                    }
                },
            ],
        ]);

        // Ganti baris baru (\n, \r\n) dengan tag <br> untuk tampilan HTML
        $content = str_replace(["\r\n", "\n", "\r"], '<br>', $this->newMessage);

        try {
            // 3. Buat dan simpan pesan ke database
            $message = Message::create([
                'chat_id' => $this->chat->id,
                'user_id' => Auth::id(),
                'content' => $content,
            ]);

            // 4. Log Aktivitas
            Log::info('Message sent successfully', [
                'message_id' => $message->id,
                'chat_id' => $this->chat->id,
                'user_name' => Auth::user()->name ?? 'Unknown',
                'content_length' => Str::length($content),
            ]);

            // 5. Update state
            $this->lastMessageId = $message->id;

            // 6. Dispatch Events
            // Event untuk notifikasi (jika ada sistem notifikasi lain)
            $this->dispatch('new-message-sent', 
                chatTitle: $this->chat->title,
                userName: Auth::user()->name ?? 'Pengguna',
                messageSnippet: Str::limit(strip_tags($message->content), 50)
            );

            // Event untuk reset form dan scroll di client-side
            $this->dispatch('message-sent');

            // 7. Reset input form
            $this->reset('newMessage');

            // TODO: Tambahkan notifikasi flasher di sini jika digunakan
            // flasher()->success('Pesan berhasil dikirim.');

        } catch (\Exception $e) {
            // 8. Error Handling
            Log::error('Failed to send message', [
                'chat_id' => $this->chat->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(), // Tambahkan trace untuk debugging lebih lanjut
            ]);
            
            $this->addError('newMessage', 'Terjadi kesalahan sistem saat mengirim pesan. Silakan coba lagi.');
        }
    }
};

?>

<div
    class="sticky bottom-0 bg-white dark:bg-gray-900/90 backdrop-blur-xl px-4 sm:px-6 py-4 border-t border-gray-200 dark:border-gray-800 shadow-xl z-10">
    <form wire:submit="sendMessage" class="flex items-end space-x-3">
        <div class="flex-1 relative">
            <div id="message-input-{{ $chat->id }}" contenteditable="true" data-placeholder="Ketik pesan..."
                class="w-full max-h-36 overflow-y-auto px-5 py-3.5 bg-gray-100 dark:bg-gray-700 rounded-3xl focus:outline-none text-gray-800 dark:text-gray-100 text-[15px] shadow-inner transition-all duration-300 transform-gpu
                       @error('newMessage') border-2 border-red-500 ring-4 ring-red-500/20 dark:bg-red-900/10 @else border-2 border-transparent focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 @enderror"
                style="min-height: 48px; line-height: 1.5;"
                x-data="{}" 
                x-init="
                    $nextTick(() => {
                        window.initializeMessageInput($el, '{{ $chat->id }}');
                    });
                ">
            </div>
            <input type="hidden" wire:model="newMessage" id="hidden-message-{{ $chat->id }}">
        </div>

        <button type="submit" id="send-btn-{{ $chat->id }}"
            class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white rounded-full flex items-center justify-center shadow-lg shadow-emerald-500/50 transition-all duration-300 hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
            wire:loading.attr="disabled"
            wire:target="sendMessage">
            <span wire:loading.remove wire:target="sendMessage">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                        d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                </svg>
            </span>
            <span wire:loading wire:target="sendMessage">
                <svg class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
            </span>
        </button>
    </form>

    @error('newMessage')
        <div class="mt-3 mx-auto max-w-lg px-4 py-2 bg-red-50 dark:bg-red-900/30 border border-red-400 dark:border-red-800 rounded-xl text-red-700 dark:text-red-400 text-sm font-medium animate-pulse">
            {{ $message }}
        </div>
    @enderror

    <script>
        // Gunakan fungsi global agar bisa diakses oleh x-init
        window.initializeMessageInput = function(messageInput, chatId) {
            'use strict';
            
            let isInitialized = messageInput.getAttribute('data-initialized') === 'true';
            if (isInitialized) return;
            
            messageInput.setAttribute('data-initialized', 'true');

            const hiddenInput = document.getElementById('hidden-message-' + chatId);
            const sendBtn = document.getElementById('send-btn-' + chatId);
            
            // --- Helper Functions ---
            
            // Safe Livewire set helper
            function safeLivewireSet(property, value) {
                // Menggunakan Livewire.find(componentId) atau @this jika sudah terikat
                if (window.Livewire && typeof Livewire !== 'undefined' && @this) {
                    try {
                        // Gunakan $wire untuk akses yang lebih direct di Volt component
                        @this.set(property, value);
                    } catch (error) {
                        console.error('Livewire set error:', property, error);
                    }
                }
            }

            function updateHiddenInput() {
                const text = messageInput.innerText.trim();
                if (hiddenInput) {
                    hiddenInput.value = text;
                    safeLivewireSet('newMessage', text);
                }
                
                // Show/hide placeholder
                if (text === '') {
                    messageInput.classList.add('empty');
                } else {
                    messageInput.classList.remove('empty');
                }
            }

            function scrollToBottom() {
                const container = document.getElementById('messages-container');
                // Asumsi 'messages-container' adalah div utama yang menampung pesan dan bisa discroll
                if (container) {
                    requestAnimationFrame(() => {
                        container.scrollTo({
                            top: container.scrollHeight,
                            behavior: 'smooth'
                        });
                    });
                }
            }

            // --- Event Listeners ---

            // Handle input changes
            messageInput.addEventListener('input', function() {
                updateHiddenInput();

                // Auto-resize
                this.style.height = 'auto';
                // Batasi tinggi maksimal 144px (sekitar 6 baris)
                this.style.height = Math.min(this.scrollHeight, 144) + 'px';
            });

            // Handle paste - strip formatting
            messageInput.addEventListener('paste', function(e) {
                e.preventDefault();
                const text = (e.clipboardData || window.clipboardData).getData('text/plain');
                
                // Insert text at cursor position
                const selection = window.getSelection();
                if (selection.rangeCount > 0) {
                    const range = selection.getRangeAt(0);
                    range.deleteContents();
                    range.insertNode(document.createTextNode(text));
                    range.collapse(false);
                }
                
                updateHiddenInput();
            });

            // Handle Enter key: Kirim jika Enter tanpa Shift
            messageInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    if (this.innerText.trim() !== '' && sendBtn && !sendBtn.disabled) {
                        sendBtn.click();
                    }
                }
            });
            
            // Panggil inisialisasi awal
            updateHiddenInput();
            
            // Set initial focus
            messageInput.focus();
        };

        // Livewire Initialization
        document.addEventListener('livewire:initialized', () => {
            // Clear editor after message sent
            Livewire.on('message-sent', () => {
                // Gunakan ID unik untuk memastikan hanya input yang benar yang di-reset
                const currentChatId = @js($chat->id);
                const messageInput = document.getElementById('message-input-' + currentChatId);

                if (messageInput) {
                    messageInput.innerText = '';
                    messageInput.style.height = '48px'; // Kembali ke tinggi awal
                    messageInput.classList.add('empty');
                    
                    // Reset hidden input
                    const hiddenInput = document.getElementById('hidden-message-' + currentChatId);
                    if (hiddenInput) hiddenInput.value = '';
                    
                    messageInput.focus();
                }
                
                // Beri waktu sebentar sebelum scroll untuk memastikan DOM sudah update
                setTimeout(scrollToBottom, 50);
            });

            // Scroll to bottom on new messages
            Livewire.on('new-message-sent', () => {
                setTimeout(scrollToBottom, 100);
            });
        });

        // Placeholder CSS Styles (Diletakkan di sini agar dekat dengan logika input)
        // Gunakan style tag di bawah div utama atau di file CSS terpisah
    </script>

    <style>
        /* CSS untuk Placeholder pada contenteditable */
        [contenteditable]:empty:before {
            content: attr(data-placeholder);
            color: #9ca3af; /* Abu-abu terang */
            pointer-events: none;
            display: block; /* Penting untuk visibilitas */
        }
        
        /* Opsi: Jika ingin placeholder tetap ada saat ada spasi kosong */
        [contenteditable].empty:before {
             content: attr(data-placeholder);
             color: #9ca3af;
             pointer-events: none;
             display: block;
        }

        /* Hilangkan placeholder saat fokus */
        [contenteditable]:focus:before {
            content: '';
        }

        /* Dark mode placeholder */
        .dark [contenteditable]:empty:before,
        .dark [contenteditable].empty:before {
            color: #6b7280; /* Abu-abu gelap */
        }
    </style>
</div>