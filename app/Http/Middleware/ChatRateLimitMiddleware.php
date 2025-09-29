<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ChatRateLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  int  $maxAttempts Maximum attempts per minute
     */
    public function handle(Request $request, Closure $next, int $maxAttempts = 60): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $userId = Auth::id();
        $key = "chat_rate_limit:user_{$userId}";
        
        // Get current attempt count
        $attempts = Cache::get($key, 0);
        
        if ($attempts >= $maxAttempts) {
            Log::warning('Chat rate limit exceeded', [
                'user_id' => $userId,
                'user_name' => Auth::user()->name,
                'attempts' => $attempts,
                'max_attempts' => $maxAttempts,
                'ip_address' => $request->ip(),
                'route' => $request->route()->getName(),
            ]);
            
            abort(429, 'Terlalu banyak permintaan. Silakan tunggu sebentar.');
        }
        
        // Increment attempt count with 1 minute expiry
        Cache::put($key, $attempts + 1, now()->addMinute());
        
        return $next($request);
    }
}
