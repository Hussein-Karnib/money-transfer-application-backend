<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\UserVerification;
use App\Http\Controllers\AuditLogController;

class UserVerificationController extends Controller
{
    /**
     * GET /api/kyc
     * Return all KYC submissions for the current user (most recent first)
     */
    public function show(Request $request)
    {
        $verifications = $request->user()
            ->verifications()
            ->orderByDesc('created_at')
            ->get()
            ->map(function (UserVerification $v) {
                return [
                    'id'            => $v->id,
                    'id_type'       => $v->id_type,
                    'id_number'     => $v->id_number,
                    'status'        => $v->status,
                    'document_path' => $v->document_path,
                    'document_url'  => $v->document_path
                        ? asset('storage/' . $v->document_path)
                        : null,
                    'created_at'    => $v->created_at,
                    'verified_at'   => $v->verified_at,
                ];
            });

        AuditLogController::logSystemAction(
            $user->id,
            'submit_verification',
            'user_verifications',
            $verification->id,
            ['id_type' => $data['id_type']]
        );

        return response()->json([
            'success' => true,
            'data'    => $verifications,
        ]);
    }

    /**
     * POST /api/kyc
     * Create a new KYC submission for the current user
     */
    public function store(Request $request)
{
    $data = $request->validate([
        'id_type'        => ['required', 'string', 'max:100'],
        'id_number'      => ['required', 'string', 'max:100'],
        'document'       => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        'document_path'  => ['nullable', 'string', 'max:255'], // 👈 allow string path too
    ]);

    $user = $request->user();

    $path = null;

    // 1) If a file is uploaded, this is the source of truth
    if ($request->hasFile('document')) {
        $path = $request->file('document')->store('kyc-documents', 'public');
    }
    // 2) Otherwise, fall back to a provided document_path (for testing / seeding)
    elseif (!empty($data['document_path'])) {
        $path = $data['document_path'];  // ideally something like 'kyc-documents/test.jpg'
    }

    $verification = UserVerification::create([
        'user_id'       => $user->id,
        'id_type'       => $data['id_type'],
        'id_number'     => $data['id_number'],
        'document_path' => $path,
        'status'        => 'pending',
    ]);

    return response()->json([
        'success' => true,
        'message' => 'KYC submitted successfully.',
        'data'    => [
            'id'            => $verification->id,
            'id_type'       => $verification->id_type,
            'id_number'     => $verification->id_number,
            'status'        => $verification->status,
            'document_path' => $verification->document_path,
            'document_url'  => $verification->document_path
                ? asset('storage/' . $verification->document_path)
                : null,
            'created_at'    => $verification->created_at,
            'verified_at'   => $verification->verified_at,
        ],
    ], 201);
}

    /**
     * GET /api/admin/kyc/pending (example admin endpoint)
     */
    public function pending()
    {
        $items = UserVerification::with('user')
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->get();

        return response()->json($items);
    }

    /**
     * POST /api/admin/kyc/{id}/approve
     */
    public function approve($id)
    {
        $verification = UserVerification::with('user')->findOrFail($id);

        $verification->update([
            'status'      => 'approved',
            'verified_at' => now(),
        ]);

        return response()->json([
            'message'      => 'KYC approved',
            'verification' => $verification,
        ]);
    }

    /**
     * POST /api/admin/kyc/{id}/reject
     */
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
