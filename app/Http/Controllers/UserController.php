<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('role')->paginate(20);
        return response()->json($users);
    }

    public function show($id)
    {
        $user = User::with(['role', 'bankAccounts', 'verifications'])
            ->findOrFail($id);

        return response()->json($user);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name'  => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:50'],
        ]);

        $user->update($data);

        return response()->json($user);
    }

     public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'user' => $request->user(),
        ]);
    }

    // PUT /api/me
    public function update(Request $request)
    {
        $user = $request->user();

        // Validate profile fields
        $data = $request->validate([
            'name'     => 'sometimes|string|max:255',
            'email'    => 'sometimes|email|unique:users,email,' . $user->id,
            'phone'    => 'sometimes|string|max:20',
            'password' => 'sometimes|string|min:6',
        ]);

        // If password was sent, hash it
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        // Update user
        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'user'    => $user,
        ]);
    }
}

