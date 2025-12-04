<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;

class AdminController extends Controller
{
    /**
     * Display a listing of the admins.
     */
    public function index()
    {
        // Eager load the 'user' relationship to display names and emails
        $admins = Admin::with('user')->paginate(10);

        // If using an API: return response()->json($admins);
        // If using Blade:
        return view('admin.manage_admins.index', compact('admins'));
    }

    /**
     * Show the form for creating a new admin.
     */
    public function create()
    {
        return view('admin.manage_admins.create');
    }

    /**
     * Store a newly created Admin and the associated User account.
     * using a DB Transaction to ensure data integrity.
     */
    public function store(Request $request)
    {
        // 1. Validate both User details and Admin details
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'privilege_level' => ['required', 'integer', 'min:1', 'max:5'], // Assuming 1-5 levels
        ]);

        // 2. Use a Transaction: If creating the Admin fails, the User won't be created either.
        DB::transaction(function () use ($validated) {
            
            // A. Create the User
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                // You might want to set a 'role_id' here if you are using the Roles table from your schema
                // 'role_id' => 1 // e.g., 1 for Admin
            ]);

            // B. Create the Admin entry linked to that User
            Admin::create([
                'user_id' => $user->id,
                'privilege_level' => $validated['privilege_level'],
            ]);
        });

        return redirect()->route('admins.index')->with('success', 'New Admin created successfully.');
    }

    /**
     * Display the specified admin.
     */
    public function show(Admin $admin)
    {
        $admin->load('user');
        return view('admin.manage_admins.show', compact('admin'));
    }

    /**
     * Show the form for editing the specified admin.
     */
    public function edit(Admin $admin)
    {
        return view('admin.manage_admins.edit', compact('admin'));
    }

    /**
     * Update the specified admin in storage.
     */
    public function update(Request $request, Admin $admin)
    {
        $validated = $request->validate([
            'privilege_level' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        $admin->update([
            'privilege_level' => $validated['privilege_level'],
        ]);

        return redirect()->route('admins.index')->with('success', 'Admin privileges updated.');
    }

    /**
     * Remove the specified admin from storage.
     * Note: This removes Admin privileges, but usually keeps the User account.
     */
    public function destroy(Admin $admin)
    {
        // Option A: Delete ONLY the admin record (Demote to regular user)
        $admin->delete();

        // Option B: Delete the User entirely (Uncomment if desired)
        // $admin->user->delete(); 

        return redirect()->route('admins.index')->with('success', 'Admin privileges revoked.');
    }

    /**
     * Display a listing of pending agents and new users.
     */
    public function approvals()
    {
        $pendingAgents = \App\Models\Agent::where('status', 'pending')->with('user')->get();
        $newUsers = \App\Models\User::orderBy('created_at', 'desc')->limit(10)->get();
        
        return view('admin.approvals', compact('pendingAgents', 'newUsers'));
    }

    /**
     * Approve a new user.
     */
    public function approveUser(User $user)
    {
        $user->update(['status' => 'active']); // Assuming 'active' is the approved status for users
        
        return redirect()->back()->with('success', 'User approved successfully.');
    }
}