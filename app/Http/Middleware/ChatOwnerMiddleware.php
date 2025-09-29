<?php

namespace App\Http\Middleware;

use App\Models\Chat;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
        $chatParam = $request->route('chat');

        // Handle both string (slug) and model instances
        if ($chatParam instanceof Chat) {
            $chat = $chatParam;
        } elseif (is_string($chatParam)) {
            // Route model binding hasn't occurred yet, manually resolve by slug
            $chat = Chat::where('slug', $chatParam)->first();
        } else {
            $chat = null;
        }

        // If chat is not found 
        if (!$chat) {
            Log::warning('ChatOwnerMiddleware: Chat not found or invalid', [
                'user_id' => $user->id,
                'route_name' => $request->route()->getName(),
                'chat_parameter' => $chatParam,
                'chat_parameter_type' => gettype($chatParam),
            ]);
            abort(404, 'Chat tidak ditemukan.');
        }

        // Load chat members relationship if not already loaded
        if (!$chat->relationLoaded('members')) {
            $chat->load('members');
        }

        switch ($type) {
            case 'owner':
                if ($chat->created_by !== $user->id) {
                    Log::warning('ChatOwnerMiddleware: Access denied - not owner', [
                        'user_id' => $user->id,
                        'chat_id' => $chat->id,
                        'chat_owner_id' => $chat->created_by,
                        'access_type' => $type,
                    ]);
                    abort(403, 'Hanya pemilik chat yang dapat mengakses halaman ini.');
                }
                break;
                
            case 'owner-or-admin':
                if ($chat->created_by !== $user->id && !$user->hasRole('admin')) {
                    Log::warning('ChatOwnerMiddleware: Access denied - not owner or admin', [
                        'user_id' => $user->id,
                        'chat_id' => $chat->id,
                        'chat_owner_id' => $chat->created_by,
                        'is_admin' => $user->hasRole('admin'),
                        'access_type' => $type,
                    ]);
                    abort(403, 'Hanya pemilik chat atau admin yang dapat mengakses halaman ini.');
                }
                break;
                
            case 'member':
            default:
                // Check if user is member of the chat or admin
                $isMember = $chat->members->contains($user);
                $isAdmin = $user->hasRole('admin');
                
                if (!$isMember && !$isAdmin) {
                    Log::warning('ChatOwnerMiddleware: Access denied - not member or admin', [
                        'user_id' => $user->id,
                        'chat_id' => $chat->id,
                        'is_member' => $isMember,
                        'is_admin' => $isAdmin,
                        'access_type' => $type,
                    ]);
                    abort(403, 'Anda tidak memiliki akses ke chat ini.');
                }
                break;
        }

        // Log successful access
        Log::info('ChatOwnerMiddleware: Access granted', [
            'user_id' => $user->id,
            'chat_id' => $chat->id,
            'access_type' => $type,
            'route' => $request->route()->getName(),
        ]);

        return $next($request);
    }
}
