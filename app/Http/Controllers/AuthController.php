<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;


class AuthController extends Controller
{
    
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'phone'    => 'required|string|max:20',
            'role_id'  => 'nullable|integer|exists:roles,id',
        ]);

        $defaultRoleId = 3; 

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'phone'    => $data['phone'],
            'role_id'  => $data['role_id'] ?? $defaultRoleId,
            'status'   => 'active',
        ]);

        $token = $user->createToken('mobile-token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ], 201);
    }
    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }
        
        $user->tokens()->delete();

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out.',
        ]);
    }

    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logged out from all devices.',
        ]);
    }


    public function socialLogin(Request $request)
    {
        $data = $request->validate([
            'provider'    => 'required|in:google,facebook,apple',
            'provider_id' => 'required|string',
            'name'        => 'required|string|max:255',
            'email'       => 'nullable|email',
            'avatar'      => 'nullable|string',
        ]);
    
        $user = User::where('provider_name', $data['provider'])
            ->where('provider_id', $data['provider_id'])
            ->first();
    
        if (!$user && !empty($data['email'])) {
            $user = User::where('email', $data['email'])->first();
        }
    
        if (!$user) {
            $user = User::create([
                'name'          => $data['name'],
                'email'         => $data['email'] ?? null,
                'password'      => null, 
                'role_id'       => 3,    
                'status'        => 'active',
                'provider_name' => $data['provider'],
                'provider_id'   => $data['provider_id'],
                'avatar_url'    => $data['avatar'] ?? null,
            ]);
        } else {
            $user->update([
                'name'          => $data['name'],
                'provider_name' => $data['provider'],
                'provider_id'   => $data['provider_id'],
                'avatar_url'    => $data['avatar'] ?? $user->avatar_url,
                'status'        => $user->status ?: 'active',
            ]);
        }
    
        $token = $user->createToken('api')->plainTextToken;
    
        return response()->json([
            'user'  => $user,
            'token' => $token,
        ]);
    }

}
