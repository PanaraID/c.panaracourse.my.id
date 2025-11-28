<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Logout;
use App\Listeners\LogUserLogout;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register logout event listener
        Event::listen(Logout::class, LogUserLogout::class);
        
        // Register message sent event listener
        Event::listen(
            \App\Events\MessageSent::class,
            \App\Listeners\SendMessageNotification::class
        );
        
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
    }
}
