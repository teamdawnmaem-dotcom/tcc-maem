<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle($request, Closure $next, $role)
    {
        if (!Auth::check()) {
            return redirect()->route('login')
                ->withErrors(['access_denied' => 'Please log in first.']);
        }

        $userRole = strtolower(Auth::user()->user_role);
        $requiredRole = strtolower($role);

        if ($userRole !== $requiredRole) {
            return redirect()->route('login')
                ->withErrors(['access_denied' => 'Unauthorized access.']);
        }

        return $next($request);
    }
}
