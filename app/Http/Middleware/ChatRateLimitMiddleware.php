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
     * @param  int  $decayMinutes Duration in minutes for rate limit reset
     */
    public function handle(Request $request, Closure $next, int $maxAttempts = 60, int $decayMinutes = 1): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $userId = Auth::id();
        $key = "chat_rate_limit:user_{$userId}";
        
        // Get current attempt count
        $attempts = Cache::get($key, 0);
        
        // Check if rate limit is exceeded
        if ($attempts >= $maxAttempts) {
            $retryAfter = $decayMinutes * 60; // Convert minutes to seconds
            
            Log::warning('Chat rate limit exceeded', [
                'user_id' => $userId,
                'user_name' => Auth::user()->name,
                'attempts' => $attempts,
                'max_attempts' => $maxAttempts,
                'retry_after_seconds' => $retryAfter,
                'decay_minutes' => $decayMinutes,
                'ip_address' => $request->ip(),
                'route' => $request->route()?->getName(),
                'user_agent' => $request->userAgent(),
            ]);
            
            $retryMinutes = ceil($retryAfter / 60);
            abort(429, "Terlalu banyak permintaan. Silakan tunggu {$retryMinutes} menit lagi.");
        }
        
        // Increment attempt count with configurable expiry
        $expiresAt = now()->addMinutes($decayMinutes);
        Cache::put($key, $attempts + 1, $expiresAt);
        
        // Log rate limit info for monitoring
        if (($attempts + 1) > ($maxAttempts * 0.8)) { // Log when approaching limit
            Log::info('Chat rate limit warning - approaching limit', [
                'user_id' => $userId,
                'attempts' => $attempts + 1,
                'max_attempts' => $maxAttempts,
                'percentage_used' => round((($attempts + 1) / $maxAttempts) * 100, 2),
            ]);
        }
        
        return $next($request);
    }
}
