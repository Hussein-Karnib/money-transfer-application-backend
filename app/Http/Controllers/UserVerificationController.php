<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\UserVerification;
use App\Http\Controllers\AuditLogController;

class UserVerificationController extends Controller
{
    // GET /api/kyc (for current user)
    public function myKyc(Request $request)
    {
        $verifications = $request->user()
            ->verifications()
            ->orderByDesc('created_at')
            ->get();

        return response()->json($verifications);
    }

    // POST /api/kyc
    public function store(Request $request)
    {
        // IMPORTANT: use "document" as the file field name in the request
        $data = $request->validate([
            'id_type'   => ['required', 'string', 'max:100'],
            'id_number' => ['required', 'string', 'max:100'],
            'document'  => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ]);

        $user = $request->user();

        $path = null;
        if ($request->hasFile('document')) {
            // stores in storage/app/public/kyc-documents
            // make sure you ran: php artisan storage:link
            $path = $request->file('document')->store('kyc-documents', 'public');
        }

        $verification = UserVerification::create([
            'user_id'       => $user->id,
            'id_type'       => $data['id_type'],
            'id_number'     => $data['id_number'],
            'document_path' => $path,         // column in DB
            'status'        => 'pending',
        ]);

        AuditLogController::logSystemAction(
            $user->id,
            'submit_verification',
            'user_verifications',
            $verification->id,
            ['id_type' => $data['id_type']]
        );

        return response()->json([
            'success' => true,
            'message' => 'KYC submitted successfully.',
            'data'    => $verification,
        ], 201);
    }

    // GET /api/admin/kyc/pending  (for example)
    public function pending()
    {
        $items = UserVerification::with('user')
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->get();

        return response()->json($items);
    }

    // POST /api/admin/kyc/{id}/approve
    public function approve($id)
    {
        $verification = UserVerification::with('user')->findOrFail($id);

        $verification->update([
            'status'      => 'approved',
            'verified_at' => now(),        // make sure this column exists in migration
        ]);

        return response()->json([
            'message'      => 'KYC approved',
            'verification' => $verification,
        ]);
    }

    // POST /api/admin/kyc/{id}/reject
    public function reject(Request $request, $id)
    {
        $verification = UserVerification::findOrFail($id);

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
