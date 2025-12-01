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

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider)
    {
        $this->validateProvider($provider);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect('/login')->with('error', 'Failed to login with ' . $provider);
        }

        $user = User::where('provider_name', $provider)
                    ->where('provider_id', $socialUser->getId())
                    ->first();

        if (! $user && $socialUser->getEmail()) {
            $user = User::where('email', $socialUser->getEmail())->first();
        }

        if (! $user) {
            $user = User::create([
                'name'          => $socialUser->getName() ?: $socialUser->getNickname() ?: 'User',
                'email'         => $socialUser->getEmail(),
                'password'      => bcrypt(Str::random(32)), 
                'provider_name' => $provider,
                'provider_id'   => $socialUser->getId(),
                'avatar_url'    => $socialUser->getAvatar(),
                'role_id'    => 3 ,
            ]);
        } else {
            $user->update([
                'provider_name' => $provider,
                'provider_id'   => $socialUser->getId(),
                'avatar_url'    => $socialUser->getAvatar(),
            ]);
        }

        Auth::login($user);

        $token = $user->createToken('api-token')->plainTextToken;


        return redirect()->away('http://localhost:19006/social-success?token=' . $token);
        // ^ adjust to your frontend URL / deep link
    }

    protected function validateProvider(string $provider): void
    {
        if (! in_array($provider, ['google', 'facebook', 'apple'])) {
            abort(404);
        }
    }
}
