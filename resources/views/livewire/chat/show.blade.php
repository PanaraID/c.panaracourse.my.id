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

    public function mount(Chat $chat)
    {
        if (!$chat->members->contains(Auth::user()) && !Auth::user()->hasRole('admin')) {
            abort(403, 'Anda tidak memiliki akses ke chat ini.');
        }

        $this->chat = $chat;

        Log::info('User accessed chat', [
            'chat_id' => $chat->id,
            'chat_title' => $chat->title,
            'user_name' => Auth::user()->name,
            'user_id' => Auth::id(),
        ]);
    }
};

?>

<div class="flex flex-col bg-gradient-to-b from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-950 h-screen overflow-hidden shadow-2xl">
    @livewire('chat._components.show.header', ['chat' => $chat])

    <div class="flex-1 overflow-y-auto px-4 h-full sm:px-6 py-4 bg-gray-200 dark:bg-gray-900 transition duration-300" id="messages-container">
        @if ($chat->messages()->count() == 0)
            @livewire('chat._components.show.empty_chat')
        @else
            @livewire('chat._components.show.messages', ['chat' => $chat])
        @endif
    </div>

    <!-- Message Input Footer -->
    @can('send-message')
        @livewire('chat._components.show.send_message', ['chat' => $chat])
    @endcan

</div>
