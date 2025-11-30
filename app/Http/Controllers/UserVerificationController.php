<?php

namespace App\Http\Controllers;

use App\Models\UserVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\User_Verification;

class UserVerificationController extends Controller
{
    public function myKyc(Request $request)
    {
        $verifications = $request->user()
            ->verifications()
            ->orderByDesc('created_at')
            ->get();

        return response()->json($verifications);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_type'    => ['required', 'string', 'max:100'],
            'id_number'  => ['required', 'string', 'max:100'],
            'document'   => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ]);

        $user = $request->user();

        $path = null;
        if ($request->hasFile('document')) {
            $path = $request->file('document')->store('kyc-documents', 'public');
        }

        $verification = User_Verification::create([
            'user_id'       => $user->id,
            'id_type'       => $data['id_type'],
            'id_number'     => $data['id_number'],
            'document_path' => $path,
            'status'        => 'pending',
        ]);

        return response()->json($verification, 201);
    }

    public function pending()
    {
        $items = User_Verification::with('user')
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->get();

        return response()->json($items);
    }

    public function approve($id)
    {
        $verification = User_Verification::with('user')->findOrFail($id);

        $verification->update([
            'status'      => 'approved',
            'verified_at' => now(),
        ]);


        return response()->json([
            'message'      => 'KYC approved',
            'verification' => $verification,
        ]);
    }

    public function reject(Request $request, $id)
    {
        $verification = User_Verification::findOrFail($id);

        $verification->update([
            'status'      => 'rejected',
            'verified_at' => null,
        ]);

        return response()->json([
            'message'      => 'KYC rejected',
            'verification' => $verification,
        ]);
    }
}
