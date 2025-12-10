<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NotAdmin
{
    /**
     * Handle an incoming request.
     * Blocks users with role 'admin' from accessing booking-related routes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && $user->role === 'admin') {
            abort(403, 'Admins are not allowed to make bookings.');
        }
        return $next($request);
    }
}
