<?php

use function Livewire\Volt\{computed, state, on, mount};
use App\Models\Chat;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

state([
    'chat' => null,
    'showAddMemberModal' => false,
    'selectedUsers' => [],
    'searchQuery' => '',
]);

mount(function (Chat $chat) {
    // Check if user can manage this chat
    if (!Auth::user()->hasRole('admin') && $chat->created_by !== Auth::id()) {
        abort(403, 'Anda tidak memiliki akses untuk mengelola chat ini.');
    }

    $this->chat = $chat;
});

$availableUsers = computed(function () {
    if (empty($this->searchQuery)) {
        return collect();
    }

    return User::where('name', 'like', '%' . $this->searchQuery . '%')
        ->orWhere('email', 'like', '%' . $this->searchQuery . '%')
        ->whereNotIn('id', $this->chat->members->pluck('id'))
        ->limit(10)
        ->get();
});

$addMembers = function () {
    $this->validate([
        'selectedUsers' => 'required|array|min:1',
        'selectedUsers.*' => 'exists:users,id',
    ]);

    $usersToAdd = User::whereIn('id', $this->selectedUsers)->get();

    foreach ($usersToAdd as $user) {
        $this->chat->members()->attach($user->id);

        Log::info('User added to chat', [
            'chat_id' => $this->chat->id,
            'chat_title' => $this->chat->title,
            'added_user_name' => $user->name,
            'added_user_id' => $user->id,
            'added_by' => Auth::user()->name,
            'added_by_id' => Auth::id(),
        ]);
    }

    $this->reset(['selectedUsers', 'searchQuery', 'showAddMemberModal']);
    $this->chat->load('members'); // Refresh members
};

$removeMember = function ($userId) {
    $user = User::findOrFail($userId);

    // Prevent removing the creator
    if ($userId === $this->chat->created_by) {
        return;
    }

    $this->chat->members()->detach($userId);

    Log::info('User removed from chat', [
        'chat_id' => $this->chat->id,
        'chat_title' => $this->chat->title,
        'removed_user_name' => $user->name,
        'removed_user_id' => $user->id,
        'removed_by' => Auth::user()->name,
        'removed_by_id' => Auth::id(),
    ]);

    $this->chat->load('members'); // Refresh members
};

$updateChatInfo = function () {
    $this->validate([
        'chat.title' => 'required|string|max:255',
        'chat.description' => 'nullable|string|max:1000',
    ]);

    $this->chat->save();

    Log::info('Chat info updated', [
        'chat_id' => $this->chat->id,
        'title' => $this->chat->title,
        'updated_by' => Auth::user()->name,
        'user_id' => Auth::id(),
    ]);

    session()->flash('success', 'Informasi chat berhasil diperbarui.');
};

?>

<div>
    <div class="max-w-4xl mx-auto p-6 space-y-8">
        <!-- Header -->
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Kelola Chat</h1>
                <p class="text-gray-600">{{ $chat->title }}</p>
            </div>
            <a href="{{ route('chat.show', $chat->slug) }}" class="text-blue-600 hover:text-blue-800">
                ← Kembali ke Chat
            </a>
        </div>

        @if (session()->has('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                {{ session('success') }}
            </div>
        @endif

        <!-- Chat Information -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Informasi Chat</h2>
            </div>
            <div class="p-6">
                <form wire:submit="updateChatInfo">
                    <div class="grid grid-cols-1 gap-4 mb-6">
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                                Judul Chat
                            </label>
                            <input wire:model="chat.title" type="text" id="title"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('chat.title')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                Deskripsi
                            </label>
                            <textarea wire:model="chat.description" id="description" rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                            @error('chat.description')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md transition-colors">
                        Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>

        <!-- Members Management -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-900">
                    Anggota Chat ({{ $chat->members->count() }})
                </h2>
                <button wire:click="$set('showAddMemberModal', true)"
                    class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Tambah Anggota
                </button>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    @foreach ($chat->members as $member)
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div
                                    class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center text-sm font-medium text-gray-700">
                                    {{ $member->initials() }}
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">{{ $member->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $member->email }}</div>
                                    @if ($member->id === $chat->created_by)
                                        <span
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            Pembuat
                                        </span>
                                    @endif
                                    @foreach ($member->roles as $role)
                                        <span
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            {{ ucfirst($role->name) }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>

                            @if ($member->id !== $chat->created_by)
                                <button wire:click="removeMember({{ $member->id }})"
                                    wire:confirm="Apakah Anda yakin ingin mengeluarkan {{ $member->name }} dari chat?"
                                    class="text-red-600 hover:text-red-800 text-sm">
                                    Keluarkan
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Add Member Modal -->
        @if ($showAddMemberModal)
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg p-6 w-full max-w-md max-h-96 overflow-y-auto">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Tambah Anggota</h3>

                    <div class="mb-4">
                        <input wire:model.live="searchQuery" type="text" placeholder="Cari nama atau email..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    @if ($this->availableUsers->isNotEmpty())
                        <div class="space-y-2 mb-6">
                            @foreach ($this->availableUsers as $user)
                                <label class="flex items-center space-x-3 p-2 hover:bg-gray-50 rounded">
                                    <input type="checkbox" wire:model="selectedUsers" value="{{ $user->id }}"
                                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900">{{ $user->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @elseif(!empty($searchQuery))
                        <p class="text-gray-500 text-sm mb-6">Tidak ada pengguna yang ditemukan.</p>
                    @else
                        <p class="text-gray-500 text-sm mb-6">Mulai mengetik untuk mencari pengguna...</p>
                    @endif

                    <div class="flex justify-end space-x-3">
                        <button type="button" wire:click="$set('showAddMemberModal', false)"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors">
                            Batal
                        </button>
                        <button wire:click="addMembers" :disabled="selectedUsers.length === 0"
                            class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            Tambah ({{ count($selectedUsers) }})
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
