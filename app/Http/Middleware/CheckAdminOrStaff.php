<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminOrStaff
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Check if user has Admin or Staff role using Spatie's hasRole method
        $user = $request->user();

        if (!$user->hasRole('Admin') && !$user->hasRole('Staff')) {
            return response()->json(['message' => 'Forbidden. Admin or Staff access required.'], 403);
        }

        return $next($request);
    }
}
