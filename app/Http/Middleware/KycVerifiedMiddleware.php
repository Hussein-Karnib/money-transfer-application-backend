<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class KycVerifiedMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // If user has a role and is Admin, bypass KYC check
        if ($user->role && in_array($user->role->name, ['Admin'], true)) {
            return $next($request);
        }

        // For normal users: must have at least one approved verification
        $isVerified = $user->verifications()
            ->where('status', 'approved')
            ->exists();

        if (! $isVerified) {
            return response()->json([
                'message' => 'KYC not completed',
            ], 403);
        }

        return $next($request);
    }
}
