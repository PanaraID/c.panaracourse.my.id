<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $roles Roles separated by pipe (|) for OR condition, comma (,) for AND condition
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        // Handle OR condition (pipe separated roles)
        if (str_contains($roles, '|')) {
            $rolesArray = explode('|', $roles);
            $hasRole = false;
            
            foreach ($rolesArray as $role) {
                if ($user->hasRole(trim($role))) {
                    $hasRole = true;
                    break;
                }
            }
            
            if (!$hasRole) {
                abort(403, 'Anda tidak memiliki akses ke halaman ini.');
            }
        }
        // Handle AND condition (comma separated roles)
        elseif (str_contains($roles, ',')) {
            $rolesArray = explode(',', $roles);
            
            foreach ($rolesArray as $role) {
                if (!$user->hasRole(trim($role))) {
                    abort(403, 'Anda tidak memiliki akses ke halaman ini.');
                }
            }
        }
        // Handle single role
        else {
            if (!$user->hasRole($roles)) {
                abort(403, 'Anda tidak memiliki akses ke halaman ini.');
            }
        }

        return $next($request);
    }
}
