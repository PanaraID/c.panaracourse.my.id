<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class SecureCookieTokenRefresh
{
    /**
     * Handle an incoming request and refresh token if needed.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only refresh for authenticated users
        if (auth()->check()) {
            $user = auth()->user();
            $currentToken = $request->cookie('user_token');
            
            if ($currentToken && $this->shouldRefreshToken($user)) {
                // Create new token
                $newTokenName = 'user_token_' . now()->timestamp;
                $newToken = $user->createToken($newTokenName, ['*'], now()->addDays(30))->plainTextToken;
                
                
                logger()->info('Token refreshed', [
                    'user_id' => $user->id,
                    'new_token_name' => $newTokenName
                ]);
            }
        }

        return $response;
    }

    /**
     * Determine if token should be refreshed.
     * Refresh if token is older than 7 days.
     */
    private function shouldRefreshToken($user): bool
    {
        $lastTokenCreated = $user->tokens()
            ->where('name', 'like', 'user_token_%')
            ->latest('created_at')
            ->first();

        if (!$lastTokenCreated) {
            return true;
        }

        return $lastTokenCreated->created_at->diffInDays(now()) >= 7;
    }
}