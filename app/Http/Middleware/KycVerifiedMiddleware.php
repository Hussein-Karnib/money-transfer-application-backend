<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class KycVerifiedMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        $isVerified = $user?->verifications()
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
