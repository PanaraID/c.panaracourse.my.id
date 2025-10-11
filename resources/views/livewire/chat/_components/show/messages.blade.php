<?php

use Livewire\Volt\Component;
use App\Models\Chat;

new class extends Component {
    public $chat;

    public function mount(Chat $chat)
    {
        $this->chat = $chat;
    }

    public function messages()
    {
        return $this->chat->messages()->with('user')->latest()->take(50)->get()->reverse();
    }
};

?>

<div class="space-y-6">
    @php $prevDate = null; @endphp
    @foreach ($this->messages() as $message)
        @php
            $isOwnMessage = $message->user_id === Auth::id();
            $isReaded = $message->readed_at !== null;
            $currentDate = $message->created_at->toDateString();
        @endphp

        @if ($prevDate !== $currentDate)
            <div class="flex items-center justify-center my-8">
                <div class="flex-grow h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent"></div>
                <span class="mx-4 px-6 py-2 rounded-full bg-white shadow text-gray-600 text-sm font-semibold border border-gray-200">
                    {{ \Carbon\Carbon::parse($message->created_at)->translatedFormat('l, d F Y') }}
                </span>
                <div class="flex-grow h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent"></div>
            </div>
            @php $prevDate = $currentDate; @endphp
        @endif

        <div class="transition-all duration-200">
            @livewire('chat._components.show.partials.message', ['message' => $message, 'isOwnMessage' => $isOwnMessage, 'isReaded' => $isReaded])
        </div>
    @endforeach
</div>
