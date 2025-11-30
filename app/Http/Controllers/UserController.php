<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

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
        return response()->json($request->user());
    }
}
