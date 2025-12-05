<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\User;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Mail;
use App\Mail\AccountApprovedMail;

class AdminController extends Controller
{
    /**
     * Display a listing of the admins.
     */
    public function index()
    {
        $admins = Admin::with('user')->paginate(10);

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
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'privilege_level' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        DB::transaction(function () use ($validated) {
            
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

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
        $admin->delete();

        return redirect()->route('admins.index')->with('success', 'Admin privileges revoked.');
    }

    /**
     * Display a listing of pending agents and new users.
     */
    public function approvals()
    {
        $pendingAgents = Agent::where('status', 'pending')->with('user')->get();
        $newUsers = User::where('status', 'pending')->orderBy('created_at', 'desc')->limit(10)->get();
        
        return view('admin.approvals', compact('pendingAgents', 'newUsers'));
    }

    /**
     * Approve a new user.
     */
    public function approveUser(User $user)
    {
        $wasActive = $user->status === 'active';

        if (! $wasActive) {
            $user->update(['status' => 'active']); // Assuming 'active' is the approved status for users

            if ($user->email) {
                Mail::to($user->email)->send(new AccountApprovedMail($user));
            }
        }

        return redirect()->back()->with('success', 'User approved successfully.');
    }
}
