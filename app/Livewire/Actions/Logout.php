<?php

namespace App\Livewire\Actions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class Logout
{
    /**
     * Log the current user out of the application.
     */
    public function __invoke()
    {
        // Get current user before logout
        $user = Auth::user();
        
        // Revoke all user tokens
        if ($user && method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }
        
        Auth::guard('web')->logout();

        Session::invalidate();
        Session::regenerateToken();
        
        // Clear the sanctum token cookie
        cookie()->queue(cookie()->forget('sanctum_token'));
        
        // Flash message to clear localStorage token
        session()->flash('clear_token', true);

        return redirect('/');
    }
}
