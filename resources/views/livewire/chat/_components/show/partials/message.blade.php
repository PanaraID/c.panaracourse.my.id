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

        $this->message = $message->load('taggedUsers'); // Load tagged users
        $this->isOwnMessage = $isOwnMessage;
        $this->isReaded = $user->hasReadMessage($message);

        // Mengonversi Markdown menjadi HTML yang aman
        $this->parsedContent = Str::markdown($message->content);
        if (env('APP_ENV') === 'local') {
            logger()->debug('Parsed Content: ' . $this->parsedContent);
        }
    }


    public function markNotificationsRead()
    {
        $this->markChatNotificationsAsRead();
    }
};
?>

{{-- Message Bubble Wrapper --}}
<div data-is-readed="{{ $isReaded ? 'true' : 'false' }}" 
     data-message-id="{{ $message->id }}"
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
            @php
                // Deteksi jika konten tidak ada spasi sama sekali
                $isNoSpace = !Str::contains($message->content, ' ');
            @endphp
            
            {{-- File Attachment Display --}}
            @if($message->file_path)
                <div class="mb-2">
                    @php
                        $fileUrl = asset('storage/' . $message->file_path);
                        $isImage = Str::startsWith($message->file_type, 'image/');
                        $isVideo = Str::startsWith($message->file_type, 'video/');
                        $isPdf = $message->file_type === 'application/pdf';
                    @endphp
                    
                    @if($isImage)
                        {{-- Image Display --}}
                        <div class="rounded-lg overflow-hidden">
                            <a href="{{ $fileUrl }}" target="_blank">
                                <img src="{{ $fileUrl }}" 
                                     alt="{{ $message->file_name }}" 
                                     class="max-w-full h-auto rounded-lg hover:opacity-90 transition cursor-pointer"
                                     style="max-height: 300px; object-fit: contain;">
                            </a>
                        </div>
                    @elseif($isVideo)
                        {{-- Video Display --}}
                        <div class="rounded-lg overflow-hidden">
                            <video controls class="max-w-full h-auto rounded-lg" style="max-height: 300px;">
                                <source src="{{ $fileUrl }}" type="{{ $message->file_type }}">
                                Browser Anda tidak mendukung video.
                            </video>
                        </div>
                    @else
                        {{-- General File Display --}}
                        <a href="{{ $fileUrl }}" target="_blank" download="{{ $message->file_name }}"
                           class="flex items-center gap-3 p-3 rounded-lg {{ $isOwnMessage ? 'bg-white/20 hover:bg-white/30' : 'bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600' }} transition">
                            <div class="flex-shrink-0">
                                @if($isPdf)
                                    <svg class="w-10 h-10 {{ $isOwnMessage ? 'text-white' : 'text-red-500' }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                                    </svg>
                                @else
                                    <svg class="w-10 h-10 {{ $isOwnMessage ? 'text-white' : 'text-blue-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                            d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium {{ $isOwnMessage ? 'text-white' : 'text-gray-900 dark:text-white' }} truncate">
                                    {{ $message->file_name }}
                                </p>
                                <p class="text-xs {{ $isOwnMessage ? 'text-white/70' : 'text-gray-500 dark:text-gray-400' }}">
                                    @php
                                        $fileSize = $message->file_size;
                                        if ($fileSize >= 1073741824) {
                                            echo number_format($fileSize / 1073741824, 2) . ' GB';
                                        } elseif ($fileSize >= 1048576) {
                                            echo number_format($fileSize / 1048576, 2) . ' MB';
                                        } else {
                                            echo number_format($fileSize / 1024, 2) . ' KB';
                                        }
                                    @endphp
                                </p>
                            </div>
                            <svg class="w-5 h-5 {{ $isOwnMessage ? 'text-white' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </a>
                    @endif
                    
                    @if($message->file_name && !in_array($message->file_type, ['image/', 'video/']))
                        <p class="text-xs {{ $isOwnMessage ? 'text-white/70' : 'text-gray-500 dark:text-gray-400' }} mt-1">
                            {{ $message->file_name }}
                        </p>
                    @endif
                </div>
            @endif

            <div class="{{ !$isOwnMessage && !$isReaded ? 'font-medium' : '' }} prose dark:prose-invert">
                @php
                    // Fungsi untuk auto-link sebagai closure
                    $autoLinkBrother = function ($text) {
                        $pattern = '/(https?:\/\/[^\s<]+)/i';
                        return preg_replace_callback($pattern, function ($matches) {
                            $url = e($matches[0]);
                            $displayUrl = Str::limit(str_replace(['http://', 'https://'], '', $matches[0]), 40);
                            return "<a href=\"{$url}\" target=\"_blank\" rel=\"noopener noreferrer\" class=\"inline-flex items-center gap-1 px-2 py-1 rounded bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 font-medium hover:bg-blue-100 dark:hover:bg-blue-800 transition\">
                                <svg class=\"w-4 h-4 text-blue-500 dark:text-blue-400\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                                    <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M14 3h7v7m0 0L10 21l-7-7 11-11z\" />
                                </svg>
                                {$displayUrl}
                            </a>";
                        }, $text);
                    };
                @endphp

                @if($isNoSpace)
                    {{-- Jika tidak ada spasi, pecah setiap 20 karakter dan tambahkan <br> --}}
                    {!! $autoLinkBrother(implode('<br>', str_split($message->content, 20))) !!}
                @else
                    {{-- Tampilkan konten yang sudah di-parse dan di-sanitize, lalu auto-link --}}
                    {!! $autoLinkBrother($parsedContent) !!}
                @endif
            </div>

            {{-- Tagged Users Info --}}
            @if($message->taggedUsers->count() > 0)
                <div class="mt-2 pt-2 border-t {{ $isOwnMessage ? 'border-white/20' : 'border-gray-200 dark:border-gray-600' }}">
                    <div class="flex items-center gap-1 flex-wrap">
                        <svg class="w-3 h-3 {{ $isOwnMessage ? 'text-white/70' : 'text-blue-500 dark:text-blue-400' }}" 
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        <span class="text-xs {{ $isOwnMessage ? 'text-white/70' : 'text-gray-500 dark:text-gray-400' }}">
                            Menandai:
                        </span>
                        @foreach($message->taggedUsers as $taggedUser)
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                        {{ $isOwnMessage 
                                           ? 'bg-white/20 text-white' 
                                           : 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300' }}">
                                @if($taggedUser->id === Auth::id())
                                    <span class="w-2 h-2 bg-red-500 rounded-full mr-1 animate-pulse"></span>
                                    Anda
                                @else
                                    {{ $taggedUser->name }}
                                @endif
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Timestamp and Status --}}
            <div
                class="flex items-center justify-end space-x-1.5 mt-1 text-[10px] sm:text-xs {{ $isOwnMessage ? 'text-white/80' : (!$isReaded ? 'text-blue-500 dark:text-blue-400' : 'text-gray-400 dark:text-gray-400') }}">
                <span class="font-medium">{{ $message->created_at->format('H:i') }}</span>
            </div>
        </div>
    </div>
</div>