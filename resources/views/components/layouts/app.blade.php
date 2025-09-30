<x-layouts.app.sidebar :title="$title ?? null">
    <flux:main>
        {{ $slot }}
    </flux:main>
    
    <!-- Notification Manager for Browser Notifications -->
    @auth
        <livewire:components.notification-manager />
    @endauth
</x-layouts.app.sidebar>
