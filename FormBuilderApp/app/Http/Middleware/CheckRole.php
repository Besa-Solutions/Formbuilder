<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // If user is not logged in and trying to access admin or guest routes
        if (!Auth::check() && $role !== 'anonymous') {
            return redirect()->route('login');
        }
        
        // If logged in but not the right role
        if (Auth::check() && Auth::user()->role !== $role && $role !== 'anonymous') {
            // If trying to access admin routes without being an admin
            if ($role === 'admin' && Auth::user()->role !== 'admin') {
                return redirect()->route('dashboard')->with('error', 'You do not have permission to access this resource.');
            }
        }
        
        // Anonymous route can be accessed by anyone
        if ($role === 'anonymous') {
            return $next($request);
        }
        
        // Allow admins to access any route
        if (Auth::check() && Auth::user()->role === 'admin') {
            return $next($request);
        }
        
        // Only allow guests to access guest routes
        if (Auth::check() && Auth::user()->role === 'guest' && $role === 'guest') {
            return $next($request);
        }
        
        return redirect()->route('dashboard')->with('error', 'You do not have permission to access this resource.');
    }
}
