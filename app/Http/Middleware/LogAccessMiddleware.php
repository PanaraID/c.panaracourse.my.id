<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $context Optional context for logging
     */
    public function handle(Request $request, Closure $next, string $context = 'page_access'): Response
    {
        $response = $next($request);
        
        // Log successful access after request is processed
        if (Auth::check()) {
            Log::info(ucfirst(str_replace('_', ' ', $context)), [
                'user_id' => Auth::id(),
                'user_name' => Auth::user()->name,
                'route' => $request->route()->getName(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'parameters' => $request->route()->parameters(),
                'status_code' => $response->getStatusCode(),
            ]);
        }

        return $response;
    }
}
