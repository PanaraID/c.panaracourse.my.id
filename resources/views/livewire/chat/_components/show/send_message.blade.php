<?php

use Livewire\Volt\Component;
use App\Models\Chat;
use App\Models\Message;
use App\Models\MessageTag;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;
    
    /**
     * Component name for debugging
     */
    public string $componentName = 'send_message';
    
    /**
     * Chat yang sedang aktif.
     */
    public Chat $chat;

    /**
     * Isi pesan baru.
     */
    public string $newMessage = '';

    /**
     * File attachment.
     */
    public $fileAttachment = null;

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
     * Search query untuk filter peserta.
     */
    public string $searchQuery = '';

    /**
     * Mount component dan set chat aktif.
     */
    public function mount(Chat $chat): void
    {
        $this->chat = $chat;
    }

    /**
     * Remove file attachment.
     */
    public function removeFile(): void
    {
        $this->fileAttachment = null;
    }

    /**
     * Get listeners for Livewire events.
     */
    public function getListeners()
    {
        return [
            'trigger-file-upload' => 'triggerFileUpload',
        ];
    }

    /**
     * Trigger file upload from external component.
     */
    public function triggerFileUpload(): void
    {
        $this->dispatch('open-file-input');
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
        $this->searchQuery = '';
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
     * Get semua chat members tanpa filter untuk menampilkan tagged users.
     */
    public function getAllChatMembersProperty()
    {
        return $this->chat->members()
            ->where('users.id', '!=', Auth::id())
            ->select('users.id', 'users.name', 'users.email')
            ->get();
    }

    /**
     * Get chat members yang bisa di-tag (dengan filter pencarian dan limit).
     */
    public function getChatMembersProperty()
    {
        $query = $this->chat->members()
            ->where('users.id', '!=', Auth::id())
            ->select('users.id', 'users.name', 'users.email');

        if (!empty(trim($this->searchQuery))) {
            $query->where('users.name', 'like', '%' . trim($this->searchQuery) . '%');
            return $query->limit(3)->get();
        }

        return collect();
    }

    /**
     * Kirim pesan ke database.
     */
    public function sendMessage(): void
    {
        // 1️⃣ Sanitasi awal
        $this->newMessage = trim($this->newMessage);

        // Validasi: harus ada pesan atau file
        if (Str::length($this->newMessage) < 1 && !$this->fileAttachment) {
            $this->addError('newMessage', 'Pesan atau file tidak boleh kosong.');
            return;
        }

        // 2️⃣ Validasi isi pesan (jika ada)
        if (Str::length($this->newMessage) > 0) {
            $this->validate([
                'newMessage' => [
                    'nullable',
                    'string',
                    'min:1',
                    'max:5000',
                    function ($attribute, $value, $fail) {
                        if (preg_match('/(\+62|62|0)?[\s\-]?\d{2,4}[\s\-]?\d{2,4}[\s\-]?\d{2,5}/', $value)) {
                            $fail('Pesan tidak boleh mengandung nomor telepon.');
                        }
                    },
                ],
            ], [
                'newMessage.min' => 'Pesan minimal 1 karakter.',
                'newMessage.max' => 'Pesan maksimal 5000 karakter.',
            ]);
        }

        // 2.5️⃣ Validasi file (jika ada)
        if ($this->fileAttachment) {
            $this->validate([
                'fileAttachment' => 'file|max:1048576', // Max 1GB (1024 * 1024 KB)
            ], [
                'fileAttachment.file' => 'File tidak valid.',
                'fileAttachment.max' => '⚠️ Ukuran file melebihi batas maksimal 1 GB. Silakan pilih file yang lebih kecil.',
                'fileAttachment.mimes' => 'Tipe file tidak diizinkan.',
                'fileAttachment.max.file' => 'Ukuran file melebihi batas maksimal 1 GB.',
            ]);
        }

        // 3️⃣ Validasi user adalah member dari chat
        if (!$this->chat->members()->where('users.id', Auth::id())->exists()) {
            $this->addError('newMessage', 'Anda tidak memiliki akses untuk mengirim pesan di chat ini.');
            return;
        }

        // 4️⃣ Simpan pesan
        $content = $this->newMessage ?: '[File Attachment]';
        $content = str_replace(["\r\n", "\r", "\n"], '<br>', $content);

        try {
            // Begin transaction
            DB::beginTransaction();

            // Buat pesan baru
            $messageData = [
                'chat_id' => $this->chat->id,
                'user_id' => Auth::id(),
                'content' => $content,
            ];

            // Handle file upload jika ada
            if ($this->fileAttachment) {
                $fileName = $this->fileAttachment->getClientOriginalName();
                $fileExtension = $this->fileAttachment->getClientOriginalExtension();
                $fileSize = $this->fileAttachment->getSize();
                $fileMimeType = $this->fileAttachment->getMimeType();
                
                // Store file
                $filePath = $this->fileAttachment->store('chat-files', 'public');
                
                $messageData['file_path'] = $filePath;
                $messageData['file_name'] = $fileName;
                $messageData['file_type'] = $fileMimeType;
                $messageData['file_size'] = $fileSize;
            }

            $message = Message::create($messageData);

            // 5️⃣ Simpan tags jika ada
            if (!empty($this->taggedUsers)) {
                // Validasi tagged users adalah members
                $validTaggedUsers = $this->chat->members()
                    ->whereIn('users.id', $this->taggedUsers)
                    ->pluck('users.id')
                    ->toArray();

                foreach ($validTaggedUsers as $userId) {
                    MessageTag::create([
                        'message_id' => $message->id,
                        'tagged_user_id' => $userId,
                        'tagged_by_user_id' => Auth::id(),
                        'is_read' => false,
                    ]);
                }

                if (count($validTaggedUsers) > 0) {
                    $this->dispatch('new-tag-received');
                }
            }

            // Commit transaction
            DB::commit();

            // 6️⃣ Log aktivitas
            Log::info('Message sent successfully', [
                'message_id' => $message->id,
                'chat_id' => $this->chat->id,
                'user_id' => Auth::id(),
                'user_name' => Auth::user()->name ?? 'Unknown',
                'content_length' => Str::length($content),
                'tagged_users_count' => count($this->taggedUsers),
                'has_file' => $this->fileAttachment ? true : false,
            ]);

            // 7️⃣ Update state
            $this->lastMessageId = $message->id;

            // 8️⃣ Dispatch events
            $this->dispatch('new-message-sent', 
                chatTitle: $this->chat->title, 
                userName: Auth::user()->name ?? 'Pengguna', 
                messageSnippet: Str::limit(strip_tags($message->content), 50)
            );

            $this->dispatch('message-sent');

            // 9️⃣ Reset input form
            $this->reset(['newMessage', 'taggedUsers', 'fileAttachment']);
            $this->showTagModal = false;

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            throw $e;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
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

<div class="sticky bottom-0 z-10 bg-slate-400 dark:bg-gray-900/90 backdrop-blur-xl border-t border-gray-200 shadow-xl px-4 sm:px-6 py-4 w-full max-w-full overflow-hidden"
    wire:key="send-message-container-{{ $chat->id }}"
    data-component="send-message"
    data-chat-id="{{ $chat->id }}">

    <!-- ======================== -->
    <!-- 🏷️ PESERTA YANG DITAG -->
    <!-- ======================== -->
    <div class="mb-3 flex items-center justify-between">
        @if (count($taggedUsers) > 0)
            <div class="flex flex-wrap gap-1">
                @foreach ($this->allChatMembers as $member)
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

    {{-- Kolom Semua Inputan --}}
    <section class="w-full">
        <!-- File Upload Loading Indicator -->
        <div wire:loading wire:target="fileAttachment" class="mb-2 flex items-center gap-2 p-2 bg-blue-50 dark:bg-blue-900/30 rounded-lg border border-blue-200 dark:border-blue-800">
            <svg class="w-5 h-5 text-blue-500 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm text-blue-700 dark:text-blue-300">Mengunggah file...</span>
        </div>

        <!-- File Preview -->
        @if ($fileAttachment)
            <div class="mb-2 flex items-center gap-2 p-2 bg-blue-50 dark:bg-blue-900/30 rounded-lg border border-blue-200 dark:border-blue-800">
                <div class="flex-1 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                            d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                    </svg>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-blue-900 dark:text-blue-200 truncate">
                            {{ $fileAttachment->getClientOriginalName() }}
                        </p>
                        <p class="text-xs text-blue-600 dark:text-blue-400">
                            @php
                                $fileSize = $fileAttachment->getSize();
                                if ($fileSize >= 1073741824) {
                                    echo number_format($fileSize / 1073741824, 2) . ' GB';
                                } elseif ($fileSize >= 1048576) {
                                    echo number_format($fileSize / 1048576, 2) . ' MB';
                                } else {
                                    echo number_format($fileSize / 1024, 2) . ' KB';
                                }
                            @endphp
                            @if($fileSize > 1073741824)
                                <span class="ml-1 text-red-600 dark:text-red-400 font-bold">⚠️ Melebihi 1 GB!</span>
                            @endif
                        </p>
                    </div>
                </div>
                <button type="button" wire:click="removeFile"
                    class="flex-shrink-0 text-red-500 hover:text-red-700 p-1 rounded-full hover:bg-red-100 dark:hover:bg-red-900/30">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif

        <!-- Hidden File Input -->
        <input type="file" 
            wire:model="fileAttachment" 
            id="file-input-{{ $chat->id }}" 
            class="hidden"
            accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar">

        <!-- File Size Info -->
        <div class="mb-2 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>Ukuran file maksimal: <strong class="text-emerald-600 dark:text-emerald-400">1 GB</strong></span>
        </div>

        <!-- 📝 Input Pesan - PLAIN TEXT TEXTAREA -->
        <div class="flex items-center min-w-0">
            <textarea 
                wire:model="newMessage"
                id="message-input-{{ $chat->id }}" 
                placeholder="Ketik pesan..." 
                rows="1"
                class="w-full max-h-24 overflow-y-auto px-3 py-2 resize-none
                    bg-gradient-to-br from-gray-100 via-gray-50 to-gray-200 
                    dark:from-gray-800 dark:via-gray-900 dark:to-gray-700
                    text-gray-900 dark:text-gray-100 text-sm
                    shadow ring-1 ring-gray-200 dark:ring-gray-700
                    border border-transparent focus:border-emerald-500
                    focus:ring-2 focus:ring-emerald-500/20 focus:outline-none
                    transition-all duration-200
                    placeholder:text-gray-400 dark:placeholder:text-gray-500
                    scrollbar-thin scrollbar-thumb-emerald-300 scrollbar-track-gray-100 dark:scrollbar-thumb-emerald-700 dark:scrollbar-track-gray-900
                    hover:scrollbar-thumb-emerald-400
                    @error('newMessage') border-red-500 ring-2 ring-red-500/20 dark:bg-red-900/10 @enderror"
                style="min-height: 40px; line-height: 1.5;" 
                x-data="{}" 
                x-init="$nextTick(() => window.autoResizeTextarea($el))"></textarea>

            {{-- Actions --}}
            <section class="ml-2 flex items-center gap-2">
                <!-- 🏷️ Tombol Tag -->
                <button type="button" wire:click="openTagModal"
                    class="flex items-center gap-1 px-1.5 py-1 rounded-xl text-xs font-medium
                    bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700
                    text-white shadow hover:shadow-md transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-400
                    flex-shrink-0 whitespace-nowrap">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                    <span class="hidden sm:inline">Tag</span>
                    @if (count($taggedUsers) > 0)
                        <span
                            class="bg-white/30 px-1 py-0.5 rounded-full text-[10px] font-semibold shadow text-blue-900 dark:text-blue-200 ml-1">
                            {{ count($taggedUsers) }}
                        </span>
                    @endif
                </button>
                <!-- 🚀 Tombol Kirim -->
                <button type="button" 
                    wire:click="sendMessage"
                    id="send-btn-{{ $chat->id }}"
                    class="flex-shrink-0 w-10 h-10 rounded-full text-white
                    bg-gradient-to-br from-emerald-500 via-green-500 to-green-600
                    hover:from-emerald-600 hover:to-green-700
                    flex items-center justify-center shadow-xl shadow-emerald-500/40
                    transition-all duration-300 hover:scale-110 active:scale-95
                    disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100
                    focus:outline-none focus:ring-2 focus:ring-emerald-400"
                    wire:loading.attr="disabled" 
                    wire:target="sendMessage" 
                    aria-label="Kirim Pesan">
                    <span wire:loading.remove wire:target="sendMessage">
                        <svg class="w-5 h-5 drop-shadow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                    </span>
                    <span wire:loading wire:target="sendMessage">
                        <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0
                            0 5.373 0 12h4zm2 5.291A7.962
                            7.962 0 014 12H0c0 3.042
                            1.135 5.824 3 7.938l3-2.647z" />
                        </svg>
                    </span>
                </button>
            </section>
        </div>
        <style>
            /* Custom scrollbar for message input */
            #message-input-{{ $chat->id }}::-webkit-scrollbar {
                height: 8px;
                width: 8px;
                background: transparent;
            }

            #message-input-{{ $chat->id }}::-webkit-scrollbar-thumb {
                background: linear-gradient(90deg, #34d399 40%, #818cf8 100%);
                border-radius: 8px;
            }

            #message-input-{{ $chat->id }}::-webkit-scrollbar-track {
                background: transparent;
            }

            /* Firefox */
            #message-input-{{ $chat->id }} {
                scrollbar-width: thin;
                scrollbar-color: #34d399 #f3f4f6;
            }

            /* Hide scrollbar when not needed */
            #message-input-{{ $chat->id }}:not(:hover):not(:focus)::-webkit-scrollbar-thumb {
                background: #e5e7eb;
            }
        </style>
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
    @error('fileAttachment')
        <div
            class="mt-3 mx-auto max-w-lg px-4 py-3 rounded-xl text-sm font-medium
                   bg-gradient-to-r from-red-50 to-orange-50 dark:from-red-900/40 dark:to-orange-900/40 
                   border-2 border-red-500 dark:border-red-700
                   text-red-800 dark:text-red-300 shadow-lg animate-shake">
            <div class="flex items-start gap-2">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div>
                    <p class="font-bold">{{ $message }}</p>
                </div>
            </div>
        </div>
        <style>
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
                20%, 40%, 60%, 80% { transform: translateX(5px); }
            }
            .animate-shake {
                animation: shake 0.5s ease-in-out;
            }
        </style>
    @enderror

    <!-- ======================== -->
    <!-- 🏷️ PANEL TAG (Gabung di bawah input chat) -->
    <!-- ======================== -->
    @if ($showTagModal)
        <div
            class="mt-4 bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full overflow-hidden border border-emerald-300/40">
            <div class="p-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                    Tag Peserta
                </h3>
                <button wire:click="closeTagModal"
                    class="rounded-full p-2 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-4 h-4 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- 🔍 Input Pencarian -->
            <div class="p-4 border-b border-gray-200 dark:border-gray-800">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" wire:model.live.debounce.300ms="searchQuery"
                        placeholder="Cari peserta berdasarkan nama..."
                        class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 
                                  rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 
                                  bg-white dark:bg-gray-800 text-gray-900 dark:text-white 
                                  placeholder-gray-500 dark:placeholder-gray-400 text-sm">
                </div>
            </div>

            <div class="p-4 max-h-72 overflow-y-auto">
                @if ($this->chatMembers->count() > 0)
                    @if (empty(trim($searchQuery)) && $this->chat->members()->where('users.id', '!=', Auth::id())->count() > 3)
                        <div
                            class="mb-3 p-3 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg">
                            <div class="flex items-center gap-2 text-blue-700 dark:text-blue-300 text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Gunakan pencarian untuk melihat peserta.</span>
                            </div>
                        </div>
                    @endif

                    <div class="space-y-2">
                        @foreach ($this->chatMembers as $member)
                            <div class="flex items-center justify-between p-3 rounded-xl cursor-pointer
                                transition-all duration-200
                                {{ in_array($member->id, $taggedUsers)
                                    ? 'bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-400 dark:border-emerald-700'
                                    : 'bg-gray-50 dark:bg-gray-800 border border-transparent hover:border-emerald-400/50' }}"
                                wire:click="toggleTagUser({{ $member->id }})">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-9 h-9 bg-gradient-to-br from-emerald-500 to-blue-600
                                        rounded-full flex items-center justify-center text-white font-bold shadow-md">
                                        {{ Str::limit($member->name, 2, '') }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-900 dark:text-white">
                                            {{ $member->name }}
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    @if (in_array($member->id, $taggedUsers))
                                        <svg class="w-5 h-5 text-emerald-500" fill="currentColor"
                                            viewBox="0 0 24 24">
                                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="10" stroke-width="2" />
                                        </svg>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-10 text-gray-500 dark:text-gray-400">
                        @if (!empty(trim($searchQuery)))
                            <div class="flex flex-col items-center gap-2">
                                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <span>Tidak ada peserta yang ditemukan untuk "{{ $searchQuery }}"</span>
                            </div>
                        @else
                            Tidak ada peserta lain di chat ini
                        @endif
                    </div>
                @endif
            </div>

            <div class="p-4 border-t border-gray-200 dark:border-gray-800 flex justify-between items-center">
                <span class="text-sm text-gray-600 dark:text-gray-400">
                    {{ count($taggedUsers) }} peserta dipilih
                </span>
                <button wire:click="closeTagModal"
                    class="px-4 py-2 bg-gradient-to-r from-emerald-500 to-green-600 text-white rounded-lg font-semibold shadow-md hover:scale-105 transition-all duration-200">
                    Selesai
                </button>
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
         * Auto-resize textarea berdasarkan konten
         */
        window.autoResizeTextarea = function(textarea) {
            'use strict';

            function resize() {
                textarea.style.height = 'auto';
                textarea.style.height = Math.min(textarea.scrollHeight, 96) + 'px'; // max-h-24 = 96px
            }

            textarea.addEventListener('input', resize);
            textarea.addEventListener('change', resize);

            // Initial resize
            resize();
        };

        /**
         * Livewire event listeners
         */
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('message-sent', () => {
                const currentChatId = @js($chat->id);
                const textarea = document.getElementById('message-input-' + currentChatId);

                if (textarea) {
                    textarea.value = '';
                    textarea.style.height = '40px';
                }

                setTimeout(scrollToBottom, 50);
            });

            Livewire.on('new-message-sent', () => setTimeout(scrollToBottom, 100));
            
            // Handle file upload trigger from header
            Livewire.on('open-file-input', () => {
                const currentChatId = @js($chat->id);
                const fileInput = document.getElementById('file-input-' + currentChatId);
                if (fileInput) {
                    fileInput.click();
                }
            });
        });
    </script>

    <!-- ======================== -->
    <!-- 🎨 STYLE -->
    <!-- ======================== -->
    <style>
        /* Responsive layout untuk form input */
        @media (max-width: 640px) {
            /* Pada mobile, pastikan form tidak overflow */
            .w-full {
                width: 100%;
                min-width: 0;
            }

            /* Input container harus fleksibel */
            .flex-1 {
                min-width: 0;
                flex: 1 1 0%;
            }

            /* Textarea responsive */
            textarea {
                width: 100%;
                min-width: 0;
                box-sizing: border-box;
            }

            /* Tombol tag lebih kecil di mobile */
            .flex-shrink-0 {
                flex-shrink: 0;
            }
        }
    </style>
</div>