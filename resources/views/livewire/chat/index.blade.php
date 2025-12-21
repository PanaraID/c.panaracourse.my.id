{{--
    Chat Index Page
    
    Halaman utama untuk menampilkan daftar semua chat room.
    Admin dapat melihat semua chat, member hanya melihat chat mereka.
    Fitur: membuat chat baru, menghapus, bergabung, dan mengelola chat.
--}}

<?php

use Livewire\Volt\Component;
use App\Models\Chat;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

new class extends Component {
    public bool $showCreateModal = false;
    public string $title = '';
    public string $description = '';

    public function with(): array
    {
        return [
            'chats' => $this->getChats(),
        ];
    }

    /**
     * Ambil daftar chat berdasarkan role user
     * Admin: melihat semua chat
     * Member: hanya chat yang mereka ikuti
     */
    public function getChats()
    {
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            return Chat::with(['creator', 'members'])
                ->withCount(['messages', 'members'])
                ->orderByDesc('created_at')
                ->get();
        } else {
            return $user
                ->chats()
                ->with(['creator', 'members'])
                ->withCount(['messages', 'members'])
                ->orderByDesc('created_at')
                ->get();
        }
    }

    /**
     * Buat chat baru
     */
    public function createChat()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $chat = Chat::create([
            'title' => $this->title,
            'description' => $this->description,
            'created_by' => Auth::id(),
        ]);

        $chat->members()->attach(Auth::id());

        Log::info('Chat created', [
            'chat_id' => $chat->id,
            'title' => $chat->title,
            'created_by' => Auth::user()->name,
            'user_id' => Auth::id(),
        ]);

        $this->reset(['title', 'description', 'showCreateModal']);
        $this->dispatch('chat-created');
    }

    /**
     * Hapus chat (hanya admin atau pembuat)
     */
    public function deleteChat($chatId)
    {
        $chat = Chat::findOrFail($chatId);

        if (Auth::user()->hasRole('admin') || $chat->created_by === Auth::id()) {
            Log::info('Chat deleted', [
                'chat_id' => $chat->id,
                'title' => $chat->title,
                'deleted_by' => Auth::user()->name,
                'user_id' => Auth::id(),
            ]);

            $chat->delete();
            $this->dispatch('chat-deleted');
        }
    }

    /**
     * User bergabung dengan chat
     */
    public function joinChat($chatId)
    {
        $chat = Chat::findOrFail($chatId);
        $user = Auth::user();

        if (!$chat->members->contains($user)) {
            $chat->members()->attach($user->id);

            Log::info('User joined chat', [
                'chat_id' => $chat->id,
                'chat_title' => $chat->title,
                'user_name' => $user->name,
                'user_id' => $user->id,
            ]);

            $this->dispatch('chat-joined');
        }
    }
}; ?>

<div class="space-y-6 p-6 bg-white dark:bg-gray-900 transition-colors">
    {{-- Header --}}
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Chat Rooms</h2>
        @can('create-chat')
            <x-buttons.button 
                variant="primary" 
                wireClick="$set('showCreateModal', true)">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Buat Chat Baru
            </x-buttons.button>
        @endcan
    </div>

    {{-- Chat List Grid --}}
    @if($chats->isNotEmpty())
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($chats as $chat)
                <x-cards.card title="{{ $chat->title }}" hoverable shadow="sm">
                    {{-- Creator Info --}}
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Dibuat oleh <span class="font-semibold">{{ $chat->creator->name }}</span>
                    </p>

                    {{-- Description --}}
                    @if ($chat->description)
                        <p class="text-gray-700 dark:text-gray-300 text-sm mb-4">{{ $chat->description }}</p>
                    @endif

                    {{-- Member Count --}}
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-4 pb-4 border-t border-gray-200 dark:border-gray-700">
                        <span class="font-semibold">{{ $chat->members_count }}</span> anggota
                    </div>

                    {{-- Actions --}}
                    <div class="flex justify-between items-center gap-2">
                        @if ($chat->members->contains(Auth::user()))
                            <a href="{{ route('chat.show', $chat->slug) }}"
                                class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md transition-colors">
                                Masuk Chat
                            </a>
                        @else
                            <button wire:click="joinChat({{ $chat->id }})"
                                class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors">
                                Gabung
                            </button>
                        @endif

                        @if (Auth::user()->hasRole('admin') || $chat->created_by === Auth::id())
                            <div class="flex gap-1">
                                <a href="{{ route('chat.manage', $chat->slug) }}"
                                    class="p-2 text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 rounded transition-colors"
                                    title="Kelola">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </a>
                                <button wire:click="deleteChat({{ $chat->id }})"
                                    wire:confirm="Apakah Anda yakin ingin menghapus chat ini?"
                                    wire:confirm.prompt="Apakah Anda yakin?\n\nKetik HAPUS untuk konfirmasi|HAPUS"
                                    class="p-2 text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20 rounded transition-colors"
                                    title="Hapus">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                        </path>
                                    </svg>
                                </button>
                            </div>
                        @endif
                    </div>
                </x-cards.card>
            @endforeach
        </div>
    @else
        {{-- Empty State --}}
        <x-states.empty-state 
            title="Belum ada chat"
            message="{{ Auth::user()->hasRole('admin') ? 'Buat chat pertama atau bergabung dengan chat yang ada.' : 'Bergabunglah dengan chat yang sudah ada.' }}"
            icon='<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>'
        />
    @endif

    {{-- Create Chat Modal --}}
    <x-modals.dialog 
        :show="$showCreateModal"
        title="Buat Chat Baru"
        closeAction="$set('showCreateModal', false)"
        submitAction="createChat"
        submitText="Buat Chat"
        submitColor="blue"
        size="md">
        
        <form wire:submit="createChat" class="space-y-4">
            <x-forms.input-field 
                label="Judul Chat"
                name="title"
                model="title"
                type="text"
                placeholder="Masukkan judul chat"
                required
            />

            <x-forms.input-field 
                label="Deskripsi (Opsional)"
                name="description"
                model="description"
                type="textarea"
                placeholder="Masukkan deskripsi chat"
                rows="3"
                hint="Jelaskan tujuan chat ini"
            />
        </form>
    </x-modals.dialog>
</div>