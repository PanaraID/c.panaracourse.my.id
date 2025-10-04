<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateWithCookie
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is already authenticated via session
        if (Auth::check()) {
            return $next($request);
        }

        // Try to authenticate using cookie token
        $token = $request->cookie('user_token');
        
        if ($token) {
            // Add Bearer token to request headers for Sanctum
            $request->headers->set('Authorization', 'Bearer ' . $token);
        }

        return $next($request);
    }
}