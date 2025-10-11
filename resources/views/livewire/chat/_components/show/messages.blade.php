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


<div>
    @foreach ($this->messages() as $message)
        @php $isOwnMessage = $message->user_id === Auth::id(); @endphp

        @livewire('chat._components.show.partials.message', ['message' => $message, 'isOwnMessage' => $isOwnMessage])
    @endforeach
</div>
