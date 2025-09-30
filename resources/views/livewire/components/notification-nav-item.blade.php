<?php

use function Livewire\Volt\{computed, state, on};
use Illuminate\Support\Facades\Auth;

$unreadCount = computed(function () {
    return Auth::user()->unreadNotificationsCount();
});

$refreshUnreadCount = function () {
    // This will trigger a re-computation of unreadCount
};

$onNotificationsUpdated = function () {
    // Force refresh when notifications are updated
};

on(['notifications-updated' => $onNotificationsUpdated]);

?>

<flux:navlist.item icon="bell" :href="route('notifications.index')" :current="request()->routeIs('notifications.*')" wire:navigate wire:poll.10s="refreshUnreadCount">
    {{ __('Notifikasi') }}
    @if($this->unreadCount > 0)
        <span class="ml-auto bg-red-500 text-white text-xs px-1.5 py-0.5 rounded-full animate-pulse">
            {{ $this->unreadCount > 99 ? '99+' : $this->unreadCount }}
        </span>
    @endif
</flux:navlist.item>
