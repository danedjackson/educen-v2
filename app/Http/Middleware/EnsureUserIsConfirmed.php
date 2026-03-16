<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsConfirmed
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if a user is authenticated
        if (Auth::check()) {
            $user = Auth::user();

            // If the user's account is not confirmed and they are not already on the awaiting confirmation page, redirect them
            if (! $user->user_confirmed_at && ! $request->is('awaiting-confirmation')) {
                return redirect()->route('awaiting-confirmation');
            }
        }
        return $next($request);
    }
}
