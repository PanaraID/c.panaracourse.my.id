<?php

use Livewire\Volt\Component;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str; // Tambahkan ini

new class extends Component {
    public Message $message;
    public bool $isOwnMessage;
    public bool $isReaded;
    public string $parsedContent; // Property baru

    public function mount(Message $message, bool $isOwnMessage, bool $isReaded)
    {
        $user = Auth::user();

        $this->message = $message;
        $this->isOwnMessage = $isOwnMessage;
        $this->isReaded = $user->hasReadMessage($message);

        // Mengonversi Markdown menjadi HTML yang aman
        $this->parsedContent = Str::markdown($message->content, [
            // Konfigurasi untuk menghilangkan HTML mentah (mencegah XSS dan tombol)
            'html_input' => 'strip', 
            // Menonaktifkan tautan yang dianggap berbahaya
            'allow_unsafe_links' => false, 
            
            // Konfigurasi CommonMark untuk mendukung format dasar (seperti WhatsApp)
            'commonmark' => [
                'enable_em' => true,     // *teks* atau _teks_
                'enable_strong' => true, // **teks** atau __teks__
            ],
            // Anda dapat mengatur 'extensions' untuk membatasi fitur (misalnya, menghapus TableExtension)
        ]);
    }


    public function markNotificationsRead()
    {
        $this->markChatNotificationsAsRead();
    }
};
?>

{{-- Message Bubble Wrapper --}}
<div data-is-readed="{{ $isReaded ? 'true' : 'false' }}"
    class="flex mb-4 animate-slide-in-{{ $isOwnMessage ? 'right' : 'left' }} {{ $isOwnMessage ? 'justify-end' : 'justify-start' }}">
    <div class="max-w-[85%] sm:max-w-[70%] group">
        <div
            class="
                p-3 shadow-xl transition-all duration-300 transform relative
                {{ $isOwnMessage
                    ? 'bg-gradient-to-br from-emerald-500 to-green-600 text-white rounded-t-xl rounded-bl-xl rounded-br-2xl hover:from-emerald-600 hover:to-green-700 hover:scale-[1.01] shadow-emerald-500/40'
                    : (!$isReaded
                        ? 'bg-gradient-to-br from-blue-100 to-blue-200 dark:from-blue-900 dark:to-blue-800 text-gray-900 dark:text-gray-100 rounded-t-xl rounded-tr-2xl rounded-br-xl shadow-blue-300/50 dark:shadow-blue-950/50 hover:scale-[1.01] border-l-4 border-blue-500'
                        : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-t-xl rounded-tr-2xl rounded-br-xl shadow-gray-300/50 dark:shadow-gray-950/50 hover:scale-[1.01]') }}
            ">

            {{-- User Name for Other Messages --}}
            @if (!$isOwnMessage)
                <div
                    class="flex items-center space-x-1 mb-1 text-xs font-semibold {{ !$isReaded ? 'text-blue-600 dark:text-blue-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                            clip-rule="evenodd" />
                    </svg>
                    <span>{{ $message->user->name }}</span>
                    @if (!$isReaded)
                        <span
                            class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[8px] bg-blue-500 text-white">NEW</span>
                    @endif
                </div>
            @endif

            {{-- Message Content (PERUBAHAN DI SINI) --}}
            <div class="{{ !$isOwnMessage && !$isReaded ? 'font-medium' : '' }} prose dark:prose-invert">
                {{-- Tampilkan konten yang sudah di-parse dan di-sanitize --}}
                {!! $parsedContent !!} 
            </div>

            {{-- Timestamp and Status --}}
            <div
                class="flex items-center justify-end space-x-1.5 mt-1 text-[10px] sm:text-xs {{ $isOwnMessage ? 'text-white/80' : (!$isReaded ? 'text-blue-500 dark:text-blue-400' : 'text-gray-400 dark:text-gray-400') }}">
                <span class="font-medium">{{ $message->created_at->format('H:i') }}</span>
            </div>
        </div>
    </div>
</div>