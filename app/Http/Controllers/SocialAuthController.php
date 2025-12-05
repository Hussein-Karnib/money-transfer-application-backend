<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirect(string $provider)
    {
        $this->validateProvider($provider);

        return Socialite::driver($provider)
            ->redirectUrl(route('social.callback', ['provider' => $provider]))
            ->redirect();
    }

    public function callback(Request $request, string $provider)
    {
        $this->validateProvider($provider);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect()->route('home')->with('error', 'Failed to login with ' . $provider . ': ' . $e->getMessage());
        }

        try {
            $user = null;

            if ($socialUser->getEmail()) {
                $user = User::where('email', $socialUser->getEmail())->first();
            }

            if (! $user) {
                $user = User::create([
                    'name'          => $socialUser->getName() ?: $socialUser->getNickname() ?: 'User',
                    'email'         => $socialUser->getEmail(),
                    'password'      => bcrypt(Str::random(32)), 
                    'avatar_url'    => $socialUser->getAvatar(),
                    'role_id'       => 3,
                ]);
            } else {
                $user->update([
                    'avatar_url'    => $socialUser->getAvatar(),
                ]);
            }

            // Load role before login
            $user->load('role');

            // Login the user (remember_token column was removed, so we can't use remember me)
            Auth::login($user);
            
            // Regenerate session for security (do this AFTER login)
            $request->session()->regenerate();

            // Get the authenticated user (fresh instance)
            $authenticatedUser = Auth::user();
            if ($authenticatedUser) {
                $authenticatedUser->load('role');
            }
            
            // Redirect based on user role
            if ($authenticatedUser && $authenticatedUser->role) {
                $roleName = strtolower($authenticatedUser->role->name);
                
                // Redirect admins to admin dashboard
                if ($roleName === 'admin') {
                    return redirect()->route('admin.dashboard')->with('success', 'Welcome back, Admin!');
                }
                
                // Redirect agents to agent portal
                if ($roleName === 'agent') {
                    $agent = \App\Models\Agent::where('user_id', $authenticatedUser->id)->first();
                    if ($agent) {
                        return redirect()->route('portal.dashboard')->with('success', 'Welcome back, Agent!');
                    }
                }
            }
            
            // Regular users (customer/user) go to user dashboard
            return redirect()->route('dashboard')->with('success', 'Welcome back!');
            
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Social login callback error: ' . $e->getMessage(), [
                'provider' => $provider,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('home')->with('error', 'An error occurred during login. Please try again.');
        }
    }

    protected function validateProvider(string $provider): void
    {
        if (! in_array($provider, ['google', 'facebook', 'apple'])) {
            abort(404);
        }
    }
}
