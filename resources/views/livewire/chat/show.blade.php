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

    public function deleteMessage($messageId)
    {
        $message = Message::findOrFail($messageId);

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

<div
    class="flex flex-col bg-gradient-to-b from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-950 h-screen rounded-2xl overflow-hidden shadow-2xl">
    <!-- Chat Header - Modern WhatsApp Style -->
    <div
        class="bg-gradient-to-r from-emerald-500 via-green-500 to-teal-500 dark:from-emerald-700 dark:via-green-700 dark:to-teal-700 px-6 py-4 shadow-lg backdrop-blur-sm">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <!-- Back Button with Animation -->
                <a href="{{ route('chat.index') }}"
                    class="text-white hover:bg-white/20 rounded-full p-2.5 transition-all duration-300 hover:scale-110 hover:rotate-[-5deg] group">
                    <svg class="w-6 h-6 group-hover:translate-x-[-2px] transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>

                <!-- Chat Avatar with Gradient -->
                <div class="relative">
                    <div
                        class="w-12 h-12 bg-gradient-to-br from-white/30 to-white/10 backdrop-blur-md rounded-full flex items-center justify-center ring-4 ring-white/30 shadow-lg animate-float">
                        <svg class="w-7 h-7 text-white drop-shadow-md" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2m5-8a3 3 0 110-6 3 3 0 010 6m5 3a2 2 0 11-4 0 2 2 0 014 0z">
                            </path>
                        </svg>
                    </div>
                    <div
                        class="absolute bottom-0 right-0 w-3.5 h-3.5 bg-green-400 rounded-full border-2 border-white animate-pulse-slow">
                    </div>
                </div>

                <!-- Chat Info -->
                <div class="flex-1">
                    <h1 class="text-white font-bold text-xl leading-tight drop-shadow-md tracking-tight">
                        {{ $chat->title }}</h1>
                    <p class="text-white/90 text-sm font-medium flex items-center space-x-1">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                        </svg>
                        <span>{{ $chat->members->count() }} anggota</span>
                    </p>
                </div>
            </div>

            <!-- Header Actions -->
            <div class="flex items-center space-x-1">
                @if (Auth::user()->hasRole('admin') || $chat->created_by === Auth::id())
                    <a href="{{ route('chat.manage', $chat->slug) }}"
                        class="text-white hover:bg-white/20 rounded-full p-2.5 transition-all duration-300 hover:scale-110 hover:rotate-12 group">
                        <svg class="w-5 h-5 group-hover:rotate-90 transition-transform duration-500" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                            </path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </a>
                @endif
            </div>
        </div>
    </div>
    <div class="flex-1 overflow-y-auto px-4 h-full sm:px-6 py-4 bg-gray-50 dark:bg-gray-900 transition duration-300"
        wire:poll.2s="refreshMessages" id="messages-container">

        @forelse($this->messages() as $message)
            @php $isOwnMessage = $message->user_id === Auth::id(); @endphp

            {{-- Message Bubble Wrapper --}}
            <div
                class="flex {{ $isOwnMessage ? 'justify-end' : 'justify-start' }} mb-4 animate-slide-in-{{ $isOwnMessage ? 'right' : 'left' }}">

                <div class="max-w-[85%] sm:max-w-[70%] group">

                    <div
                        class="p-3 shadow-xl transform transition-all duration-300
                            {{-- Own Message Styles --}}
                            {{ $isOwnMessage
                                ? 'bg-gradient-to-br from-emerald-500 to-green-600 text-white 
                                                                                           rounded-t-xl rounded-bl-xl rounded-br-2xl
                                                                                           hover:from-emerald-600 hover:to-green-700 hover:scale-[1.01] shadow-emerald-500/40'
                                : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 
                                                                                           rounded-t-xl rounded-tr-2xl rounded-br-xl
                                                                                           shadow-gray-300/50 dark:shadow-gray-950/50 hover:scale-[1.01]' }} 
                            relative">

                        {{-- User Name for Other Messages --}}
                        @if (!$isOwnMessage)
                            <div
                                class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 mb-1 flex items-center space-x-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span>{{ $message->user->name }}</span>
                            </div>
                        @endif

                        {{-- Message Content --}}
                        <div class="">
                            {!! Str::markdown($message->content) !!}
                        </div>

                        {{-- Timestamp and Status --}}
                        <div
                            class="flex items-center justify-end space-x-1.5 mt-1 text-[10px] sm:text-xs 
                                {{ $isOwnMessage ? 'text-white/80' : 'text-gray-400 dark:text-gray-400' }}">
                            <span class="font-medium">{{ $message->created_at->format('H:i') }}</span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>

                    </div>
                </div>
            </div>
        @empty
            {{-- Empty State --}}
            <div class="flex flex-col items-center justify-center h-full text-center py-20 animate-fade-in">
                <div
                    class="bg-white dark:bg-gray-800 rounded-3xl p-12 shadow-2xl max-w-sm w-full transition duration-500">
                    <div
                        class="w-20 h-20 mx-auto mb-6 bg-gradient-to-br from-emerald-500 to-green-600 rounded-full 
                            flex items-center justify-center animate-pulse-slow shadow-lg shadow-emerald-500/30">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-extrabold text-gray-800 dark:text-gray-100 mb-3">Mulai Obrolan</h3>
                    <p class="text-gray-500 dark:text-gray-400 text-base">Kirim pesan pertama Anda dan mulailah
                        percakapan. 🚀</p>
                </div>
            </div>
        @endforelse

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
        <div
            class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-xl px-6 py-4 border-t border-gray-200/50 dark:border-gray-700/50 shadow-lg">
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
        </div>
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
