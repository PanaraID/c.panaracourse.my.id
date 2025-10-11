<?php

use Livewire\Volt\Component;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public Message $message;
    public bool $isOwnMessage;

    public function mount(Message $message, bool $isOwnMessage)
    {
        $this->message = $message;
        $this->isOwnMessage = $isOwnMessage;
    }
};
?>

{{-- Message Bubble Wrapper --}}
<div
    class="flex mb-4 animate-slide-in-{{ $isOwnMessage ? 'right' : 'left' }} {{ $isOwnMessage ? 'justify-end' : 'justify-start' }}">
    <div class="max-w-[85%] sm:max-w-[70%] group">
        <div
            class="
                p-3 shadow-xl transition-all duration-300 transform relative
                {{ $isOwnMessage
                    ? 'bg-gradient-to-br from-emerald-500 to-green-600 text-white rounded-t-xl rounded-bl-xl rounded-br-2xl hover:from-emerald-600 hover:to-green-700 hover:scale-[1.01] shadow-emerald-500/40'
                    : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-t-xl rounded-tr-2xl rounded-br-xl shadow-gray-300/50 dark:shadow-gray-950/50 hover:scale-[1.01]' }}
            ">
            
            {{-- User Name for Other Messages --}}
            @if (!$isOwnMessage)
                <div
                    class="flex items-center space-x-1 mb-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                            clip-rule="evenodd" />
                    </svg>
                    <span>{{ $message->user->name }}</span>
                </div>
            @endif

            {{-- Message Content --}}
            <div>
                {!! Str::markdown($message->content) !!}
            </div>

            {{-- Timestamp and Status --}}
            <div
                class="flex items-center justify-end space-x-1.5 mt-1 text-[10px] sm:text-xs {{ $isOwnMessage ? 'text-white/80' : 'text-gray-400 dark:text-gray-400' }}">
                <span class="font-medium">{{ $message->created_at->format('H:i') }}</span>
            </div>
        </div>
    </div>
</div>
