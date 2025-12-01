<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/*
 PaymentController
 Payment Flow:
 1. User creates a transfer (status: "queued")
 2. User authorizes payment (POST /api/payments) - status: "authorized", transfer: "paid"
 3. Payment is captured (POST /api/payments/{id}/capture) - status: "captured", transfer: "in_progress"
 4. If needed, payment can be refunded (POST /api/payments/{id}/refund)
 */
class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

   /*
     * Request Body:
      {
        "transfer_id": 1,
        "gateway": "stripe",
        "gateway_ref": "ch_abc123" // Optional
      }
     */
    public function store(Request $request): JsonResponse
    {
        // Validate the input data
        $request->validate([
            'transfer_id' => ['required', 'integer', 'exists:transfers,id'],
            'gateway' => ['required', 'string', 'in:stripe,paypal,checkout'],
            'gateway_ref' => ['nullable', 'string'],
        ]);

       
        $transfer = \App\Models\Transfer::where('id', $request->transfer_id)
            ->where('sender_id', Auth::id())
            ->firstOrFail();

       
        $payment = $this->paymentService->createPayment($request->transfer_id, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Payment authorized successfully',
            'data' => $payment->load('transfer'),
        ], 201);
    }

   
    public function show(int $id): JsonResponse
    {
        $payment = Payment::where('id', $id)
            ->whereHas('transfer', function ($query) {
                $query->where('sender_id', Auth::id());
            })
            ->with('transfer')
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $payment,
        ]);
    }

    /*
      Captures an authorized payment. This moves the money from the customer's account
      to your account. After capture, the transfer status changes to "in_progress".
     */
    public function capture(int $id): JsonResponse
    {
        
        $payment = Payment::where('id', $id)
            ->whereHas('transfer', function ($query) {
                $query->where('sender_id', Auth::id());
            })
            ->firstOrFail();

       
        $payment = $this->paymentService->capturePayment($id);

        return response()->json([
            'success' => true,
            'message' => 'Payment captured successfully',
            'data' => $payment->load('transfer'),
        ]);
    }

   
    public function refund(int $id): JsonResponse
    {
       
        $payment = Payment::where('id', $id)
            ->whereHas('transfer', function ($query) {
                $query->where('sender_id', Auth::id());
            })
            ->firstOrFail();

       
        $payment = $this->paymentService->refundPayment($id);

        return response()->json([
            'success' => true,
            'message' => 'Payment refunded successfully',
            'data' => $payment->load('transfer'),
        ]);
    }
}

