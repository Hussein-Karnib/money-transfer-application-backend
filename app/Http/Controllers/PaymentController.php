<?php 

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Transfer;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    /**
     * Allowed payment gateways (string codes, NOT IDs).
     *
     * In a real app these map to different integrations:
     * - stripe     → Stripe card payments
     * - paypal     → PayPal checkout
     * - checkout   → Checkout.com
     * - dummy      → Fake gateway for dev/testing
     * - card       → Generic card processor
     */
    private const ALLOWED_GATEWAYS = [
        'stripe',
        'paypal',
        'checkout',
        'dummy',
        'card',
        'test',
    ];

    public function __construct(
        private PaymentService $paymentService
    ) {}

    /*
      Request Body:
      {
        "transfer_id": 1,
        "gateway": "stripe",       // must be one of ALLOWED_GATEWAYS
        "gateway_ref": "ch_abc123" // optional, e.g. Stripe charge id
      }
    */
    public function store(Request $request): JsonResponse
    {
        // Validate the input data
        $validated = $request->validate([
            'transfer_id' => ['required', 'integer', 'exists:transfers,id'],
            'gateway'     => ['required', 'string', 'in:' . implode(',', self::ALLOWED_GATEWAYS)],
            'gateway_ref' => ['nullable', 'string'],
        ]);

        // Make sure the transfer belongs to the current user
        $transfer = Transfer::where('id', $validated['transfer_id'])
            ->where('sender_id', Auth::id())
            ->firstOrFail();

        // Create the payment (status: authorized / pending depending on your service)
        $payment = $this->paymentService->createPayment(
            $validated['transfer_id'],
            $validated
        );

        return response()->json([
            'success' => true,
            'message' => 'Payment authorized successfully',
            'data'    => $payment->load('transfer'),
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
            'data'    => $payment,
        ]);
    }

    /*
      Captures an authorized payment. This moves the money from the customer's account
      to your account. After capture, we should also move the transfer status
      from "queued" → "completed" (for your project).
    */
    public function capture(int $id): JsonResponse
    {
        $payment = Payment::where('id', $id)
            ->whereHas('transfer', function ($query) {
                $query->where('sender_id', Auth::id());
            })
            ->firstOrFail();

        // This should capture the payment AND update the transfer
        $payment = $this->paymentService->capturePayment($id);

        return response()->json([
            'success' => true,
            'message' => 'Payment captured successfully',
            'data'    => $payment->load('transfer'),
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
            'data'    => $payment->load('transfer'),
        ]);
    }
}
