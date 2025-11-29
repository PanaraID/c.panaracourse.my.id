<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
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
        // Configure rate limiters
        $this->configureRateLimiting();
        
        // Register logout event listener
        Event::listen(Logout::class, LogUserLogout::class);
        
        // Register message sent event listener
        Event::listen(
            \App\Events\MessageSent::class,
            \App\Listeners\SendMessageNotification::class
        );
        
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
    }
    
    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Login rate limiter - 5 attempts per minute per email+IP
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->input('email').$request->ip());
        });
        
        // API rate limiter - 60 requests per minute
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
