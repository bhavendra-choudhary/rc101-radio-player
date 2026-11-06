<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if user is not authenticated
        if (!Auth::check()) {
            // Store the intended URL for redirect after login
            $request->session()->put('url.intended', $request->fullUrl());
            
            // Redirect to login page
            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
