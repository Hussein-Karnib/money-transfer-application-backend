<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;


class AuthController extends Controller
{
    
     public function register(Request $request)
    {
       
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role_id'  => ['nullable', 'integer', 'exists:roles,id'],
        ]);

        // Determine which role to assign
        if (isset($data['role_id'])) {
            // Validate that the role is allowed for public registration (User or Agent only)
            $allowedRole = Role::where('id', $data['role_id'])
                ->whereIn('name', ['User', 'Agent'])
                ->first();
            
            if (!$allowedRole) {
                // If invalid role_id provided, default to User
                $userRole = Role::where('name', 'User')->firstOrFail();
                $roleId = $userRole->id;
            } else {
                $roleId = $data['role_id'];
            }
        } else {
            // Default to User role if no role_id provided
            $userRole = Role::where('name', 'User')->firstOrFail();
            $roleId = $userRole->id;
        }

        // Check if this is an Agent registration (role_id 2)
        $isAgent = $roleId == 2;

        DB::transaction(function () use ($data, $roleId, $isAgent, &$user, &$agent) {
            // 1. Create the User Account
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
                'role_id'  => $roleId,
            ]);

            // 2. If role_id is 2 (Agent), automatically create Agent record
            if ($isAgent) {
                $agent = Agent::create([
                    'user_id' => $user->id,
                    'store_name' => $data['name'] . "'s Store",
                    'address' => null,
                    'latitude' => null,
                    'longitude' => null,
                    'status' => 'pending',
                ]);
            }
        });

        // If you're using Sanctum:
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => $isAgent ? 'Agent registered successfully. Pending admin approval.' : 'User registered successfully.',
            'data'    => [
                'user'  => $user,
                'token' => $token,
            ],
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
            'name'        => 'required|string|max:255',
            'email'       => 'nullable|email',
            'avatar'      => 'nullable|string',
        ]);
        
        $user = null;
        
        if (!empty($data['email'])) {
            $user = User::where('email', $data['email'])->first();
        }
        
        if (!$user) {
            $user = User::create([
                'name'          => $data['name'],
                'email'         => $data['email'] ?? null,
                'password'      => null, 
                'role_id'       => 3,    
                'status'        => 'active',
                'avatar_url'    => $data['avatar'] ?? null,
            ]);
        } else {
            $user->update([
                'name'          => $data['name'],
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
