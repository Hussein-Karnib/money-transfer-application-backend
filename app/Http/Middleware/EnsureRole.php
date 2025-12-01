<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureRole
{
    /**
     * Handle an incoming request.
     *
     * @param  array<int, string>  $roles
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = Auth::user();

        if (! $user) {
            abort(401, 'Authentication required.');
        }

        $roleName = $user->role->name ?? null;

        if ($roleName && in_array($roleName, $roles, true)) {
            return $next($request);
        }

        abort(403, 'You do not have permission to access this resource.');
    }
}

