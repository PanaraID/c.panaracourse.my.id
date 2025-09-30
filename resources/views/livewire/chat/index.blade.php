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

    public function getChats()
    {
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            // Admin can see all chats
            return Chat::with(['creator', 'members'])
                ->withCount(['messages', 'members'])
                ->orderByDesc('created_at')
                ->get();
        } else {
            // Member can only see chats they're part of
            return $user
                ->chats()
                ->with(['creator', 'members'])
                ->withCount(['messages', 'members'])
                ->orderByDesc('created_at')
                ->get();
        }
    }

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

        // Add creator as member
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

    public function deleteChat($chatId)
    {
        $chat = Chat::findOrFail($chatId);

        // Only admin or creator can delete chat
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

<div>
    <div class="space-y-6 p-6 bg-white dark:bg-gray-900 transition-colors">
        <div class="flex justify-between items-center">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Chat Rooms</h2>
            @can('create-chat')
                <button wire:click="$set('showCreateModal', true)"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Buat Chat Baru
                </button>
            @endcan
        </div>

        <!-- Chat List -->
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($chats as $chat)
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow">
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">{{ $chat->title }}</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Dibuat oleh {{ $chat->creator->name }}</p>
                            </div>
                            <div class="flex space-x-2">
                                @if (Auth::user()->hasRole('admin') || $chat->created_by === Auth::id())
                                    <button wire:click="deleteChat({{ $chat->id }})"
                                        wire:confirm="Apakah Anda yakin ingin menghapus chat ini?"
                                        wire:confirm.prompt="Apakah Anda yakin?\n\nKetik HAPUS untuk konfirmasi|HAPUS"
                                        class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                            </path>
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        </div>

                        @if ($chat->description)
                            <p class="text-gray-700 dark:text-gray-300 text-sm mb-4">{{ $chat->description }}</p>
                        @endif

                        <div class="flex justify-between items-center text-sm text-gray-500 dark:text-gray-400 mb-4">
                            <span>{{ $chat->members_count }} anggota</span>
                        </div>

                        <div class="flex justify-between items-center">
                            @if ($chat->members->contains(Auth::user()))
                                <a href="{{ route('chat.show', $chat->slug) }}"
                                    class="inline-flex items-center px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md transition-colors">
                                    Masuk Chat
                                </a>
                            @else
                                <button wire:click="joinChat({{ $chat->id }})"
                                    class="inline-flex items-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors">
                                    Gabung
                                </button>
                            @endif

                            @if (Auth::user()->hasRole('admin') || $chat->created_by === Auth::id())
                                <a href="{{ route('chat.manage', $chat->slug) }}"
                                    class="text-gray-600 hover:text-gray-800 dark:text-gray-300 dark:hover:text-gray-100 text-sm">
                                    Kelola
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($chats->isEmpty())
            <div class="text-center py-12">
                <div class="w-24 h-24 mx-auto mb-4 text-gray-300 dark:text-gray-700">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Belum ada chat</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-4">{{ Auth::user()->hasRole('admin') ? 'Buat chat pertama atau bergabung dengan chat yang ada.' : 'Bergabunglah dengan chat yang sudah ada.' }}</p>
            </div>
        @endif

        <!-- Create Chat Modal -->
        @if ($showCreateModal)
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-80 flex items-center justify-center z-50">
                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-md">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Buat Chat Baru</h3>

                    <form wire:submit="createChat">
                        <div class="mb-4">
                            <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                Judul Chat
                            </label>
                            <input wire:model="title" type="text" id="title" name="title"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-900 dark:text-gray-100"
                                placeholder="Masukkan judul chat">
                            @error('title')
                                <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                Deskripsi (Opsional)
                            </label>
                            <textarea wire:model="description" id="description" rows="3"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-900 dark:text-gray-100"
                                placeholder="Masukkan deskripsi chat"></textarea>
                            @error('description')
                                <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="flex justify-end space-x-3">
                            <button type="button" wire:click="$set('showCreateModal', false)"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-md transition-colors">
                                Batal
                            </button>
                            <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors">
                                Buat Chat
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>