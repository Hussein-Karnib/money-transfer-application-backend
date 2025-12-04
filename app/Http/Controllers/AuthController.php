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
        // Check if this is an API request
        if ($request->wantsJson() || $request->is('api/*')) {
            $data = $request->validate([
                'name'     => ['required', 'string', 'max:255'],
                'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
                'role_id'  => ['nullable', 'integer', 'exists:roles,id'],
            ]);

            // Determine which role to assign
            if (isset($data['role_id'])) {
                $allowedRole = Role::where('id', $data['role_id'])
                    ->whereIn('name', ['User', 'user', 'customer', 'Customer', 'Agent', 'agent'])
                    ->first();
                
                if (!$allowedRole) {
                    // Try to find customer role by name variations, fallback to role id 3
                    $userRole = Role::whereIn('name', ['customer', 'Customer', 'User', 'user'])->first();
                    if (!$userRole) {
                        $userRole = Role::find(3); // Default customer role
                    }
                    if (!$userRole) {
                        $userRole = Role::first();
                    }
                    if (!$userRole) {
                        throw new \Exception('No user role found in database. Please seed the roles table.');
                    }
                    $roleId = $userRole->id;
                } else {
                    $roleId = $data['role_id'];
                }
            } else {
                // Try to find customer role by name variations, fallback to role id 3
                $userRole = Role::whereIn('name', ['customer', 'Customer', 'User', 'user'])->first();
                if (!$userRole) {
                    $userRole = Role::find(3); // Default customer role
                }
                if (!$userRole) {
                    $userRole = Role::first();
                }
                if (!$userRole) {
                    throw new \Exception('No user role found in database. Please seed the roles table.');
                }
                $roleId = $userRole->id;
            }

            $isAgent = $roleId == 2;

            DB::transaction(function () use ($data, $roleId, $isAgent, &$user, &$agent) {
                $user = User::create([
                    'name'     => $data['name'],
                    'email'    => $data['email'],
                    'password' => Hash::make($data['password']),
                    'role_id'  => $roleId,
                ]);

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

        // Web registration
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Default to Customer role for web registration (role id 3 from migration)
        // Try 'customer' first, then 'User', then fallback to role id 3
        $userRole = Role::whereIn('name', ['customer', 'Customer', 'User', 'user'])->first();
        
        // If no role found, use role id 3 (default customer role from migration)
        if (!$userRole) {
            $userRole = Role::find(3);
        }
        
        // If still no role, use the first available role
        if (!$userRole) {
            $userRole = Role::first();
        }
        
        if (!$userRole) {
            throw new \Exception('No user role found in database. Please seed the roles table with: admin (id=1), agent (id=2), customer (id=3).');
        }
        
        $roleId = $userRole->id;

        DB::transaction(function () use ($data, $roleId, &$user) {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
                'role_id'  => $roleId,
            ]);
        });

        // Auto-login the user after registration
        Auth::login($user);

        return redirect()->route('dashboard')->with('success', 'Registration successful! Welcome to Money Transfer.');
    }
    public function login(Request $request)
    {
        // Check if this is an API request
        if ($request->wantsJson() || $request->is('api/*')) {
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

        // Web login
        // Regenerate CSRF token to prevent 419 errors
        $request->session()->regenerateToken();
        
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            // Redirect based on user role
            $user = Auth::user();
            
            if ($user->role) {
                $roleName = strtolower($user->role->name);
                
                // Redirect admins to admin dashboard
                if ($roleName === 'admin') {
                    return redirect()->route('admin.dashboard')->with('success', 'Welcome back, Admin!');
                }
                
                // Redirect agents to agent portal
                if ($roleName === 'agent') {
                    $agent = \App\Models\Agent::where('user_id', $user->id)->first();
                    if ($agent) {
                        return redirect()->route('portal.dashboard')->with('success', 'Welcome back!');
                    }
                }
            }
            
            // Regular users go to regular dashboard
            return redirect()->intended(route('dashboard'))->with('success', 'Welcome back!');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function logout(Request $request)
    {
        // Check if this is an API request
        if ($request->wantsJson() || $request->is('api/*')) {
            $request->user()->currentAccessToken()->delete();
            return response()->json([
                'message' => 'Logged out.',
            ]);
        }

        // Web logout
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'You have been logged out.');
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
