<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permissions Permissions separated by pipe (|) for OR condition, comma (,) for AND condition
     */
    public function handle(Request $request, Closure $next, string $permissions): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        // Handle OR condition (pipe separated permissions)
        if (str_contains($permissions, '|')) {
            $permissionsArray = explode('|', $permissions);
            $hasPermission = false;
            
            foreach ($permissionsArray as $permission) {
                if ($user->hasPermissionTo(trim($permission))) {
                    $hasPermission = true;
                    break;
                }
            }
            
            if (!$hasPermission) {
                abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
            }
        }
        // Handle AND condition (comma separated permissions)
        elseif (str_contains($permissions, ',')) {
            $permissionsArray = explode(',', $permissions);
            
            foreach ($permissionsArray as $permission) {
                if (!$user->hasPermissionTo(trim($permission))) {
                    abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
                }
            }
        }
        // Handle single permission
        else {
            if (!$user->hasPermissionTo($permissions)) {
                abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
            }
        }

        return $next($request);
    }
}
