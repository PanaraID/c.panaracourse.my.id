 <?php
 
 use Livewire\Volt\Component;
 use App\Models\Chat;
 use App\Models\Message;
 
 new class extends Component {
     public $chat;
 
     public string $newMessage = '';
 
     public function mount(Chat $chat)
     {
         $this->chat = $chat;
     }
 
     public function sendMessage()
     {
         // FIXME tambahkan flasher
         $this->newMessage = trim($this->newMessage);
         $this->newMessage = str_replace(["\r\n", "\n", "\r"], '<br>', $this->newMessage);
         if (preg_match('/(\+62|62|0)?[ -]?\d{2,4}[ -]?\d{2,4}[ -]?\d{2,5}/', $this->newMessage)) {
             $this->addError('newMessage', 'Pesan tidak boleh mengandung nomor telepon.');
             return;
         }
 
         if (strlen($this->newMessage) < 1) {
             $this->addError('newMessage', 'Pesan harus terdiri dari minimal 1 karakter.');
             return;
         }
         if (strlen($this->newMessage) > 5000) {
             $this->addError('newMessage', 'Pesan tidak boleh lebih dari 5000 karakter.');
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
 
         $this->lastMessageId = $message->id;
 
         $this->dispatch('new-message-sent', [
             'chat_title' => $this->chat->title,
             'user_name' => Auth::user()->name,
             'message' => \Str::limit(strip_tags($message->content), 50),
         ]);
 
         $this->dispatch('message-sent');
     }
 };
 
 ?>

 <div
     class="bg-zinc-200 dark:bg-gray-800/90 backdrop-blur-xl px-6 py-4 border-t border-gray-200/50 dark:border-gray-700/50 shadow-lg">
     <form wire:submit="sendMessage" class="flex items-end space-x-3">
         <!-- Message Input -->
         <div class="flex-1 relative" wire:ignore>
             <div id="message-input" contenteditable="true" data-placeholder="Ketik pesan..."
                 class="w-full max-h-32 overflow-y-auto px-5 py-3.5 bg-gray-50 dark:bg-gray-700 rounded-3xl focus:outline-none text-gray-900 dark:text-gray-100 text-[15px] shadow-inner border-2 transition-all duration-300
                        @error('newMessage') border-red-400 dark:border-red-500 bg-red-50 dark:bg-red-900/10 @else border-transparent focus:border-emerald-400 dark:focus:border-emerald-500 @enderror"
                 style="min-height: 48px; line-height: 1.5;"></div>
             <input type="hidden" wire:model="newMessage" id="hidden-message">
         </div>

         <!-- Attachment Button -->
         {{-- <button type="button" 
                    class="flex-shrink-0 w-11 h-11 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-300 rounded-full flex items-center justify-center transition-all duration-300 hover:scale-110 hover:rotate-12">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                            d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                    </svg>
                </button> --}}

         <!-- Send Button -->
         <button type="submit" id="send-btn"
             class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white rounded-full flex items-center justify-center shadow-lg shadow-emerald-500/50 transition-all duration-300 hover:scale-110 hover:shadow-xl hover:shadow-emerald-500/60 active:scale-95">
             <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                     d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
             </svg>
         </button>
     </form>



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

        let scrollTimeout;
        document.addEventListener('scroll', () => {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                // FIXME
            }, 1000);
        });

        // Request notification permission
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
    </script>
 </div>
