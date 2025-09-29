<?php

namespace App\Http\Middleware;

use App\Models\Chat;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ChatOwnerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $type Type of access check: 'owner', 'member', or 'owner-or-admin'
     */
    public function handle(Request $request, Closure $next, string $type = 'member'): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $chat = $request->route('chat');

        // If chat is not found
        if (!$chat instanceof Chat) {
            abort(404);
        }

        switch ($type) {
            case 'owner':
                if ($chat->created_by !== $user->id) {
                    abort(403, 'Hanya pemilik chat yang dapat mengakses halaman ini.');
                }
                break;
                
            case 'owner-or-admin':
                if ($chat->created_by !== $user->id && !$user->hasRole('admin')) {
                    abort(403, 'Hanya pemilik chat atau admin yang dapat mengakses halaman ini.');
                }
                break;
                
            case 'member':
            default:
                // Check if user is member of the chat or admin
                if (!$chat->members->contains($user) && !$user->hasRole('admin')) {
                    abort(403, 'Anda tidak memiliki akses ke chat ini.');
                }
                break;
        }

        return $next($request);
    }
}
