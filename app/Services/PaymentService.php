<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Transfer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class PaymentService
{
    
    public function createPayment(int $transferId, array $data): Payment
    {
        return DB::transaction(function () use ($transferId, $data) {
            $transfer = Transfer::findOrFail($transferId);

            // Validate transfer can accept payment
            if (!in_array($transfer->status, ['queued'])) {
                throw new \Exception('Transfer is not in a state that accepts payment');
            }

            $gateway = $data['gateway'] ?? 'stripe';
            $amount = $transfer->total_amount;
            $currency = $transfer->currency_from;

            // Call mock payment gateway to authorize payment
            $gatewayResponse = $this->mockGatewayAuthorize($gateway, $amount, $currency, $data);

            // Create payment record
            $payment = Payment::create([
                'transfer_id' => $transferId,
                'amount' => $amount,
                'currency_code' => $currency,
                'gateway' => $gateway,
                'gateway_ref' => $gatewayResponse['reference'] ?? $data['gateway_ref'] ?? null,
                'status' => $gatewayResponse['status'] === 'success' ? 'authorized' : 'failed',
                'authorized_at' => $gatewayResponse['status'] === 'success' ? now() : null,
            ]);

            // Update transfer status
            if ($gatewayResponse['status'] === 'success') {
                $transfer->update(['status' => 'paid']);
                
                // Create transfer event
                $transfer->events()->create([
                    'status' => 'paid',
                    'note' => "Payment authorized via {$gateway}",
                    'actor_type' => 'system',
                ]);
            } else {
                $transfer->update(['status' => 'failed']);
                
                // Create transfer event
                $transfer->events()->create([
                    'status' => 'failed',
                    'note' => "Payment authorization failed: {$gatewayResponse['message']}",
                    'actor_type' => 'system',
                ]);
                
                throw new \Exception("Payment authorization failed: {$gatewayResponse['message']}");
            }

            return $payment;
        });
    }

   
    public function capturePayment(int $paymentId): Payment
    {
        $payment = Payment::findOrFail($paymentId);

        if ($payment->status !== 'authorized') {
            throw new \Exception('Payment must be authorized to capture');
        }

        // Call mock gateway to capture payment
        $gatewayResponse = $this->mockGatewayCapture($payment->gateway, $payment->gateway_ref);

        if ($gatewayResponse['status'] !== 'success') {
            throw new \Exception("Payment capture failed: {$gatewayResponse['message']}");
        }

        $payment->update([
            'status' => 'captured',
            'captured_at' => now(),
        ]);

        // Update transfer status
        $transfer = $payment->transfer;
        $transfer->update(['status' => 'in_progress']);
        
        // Create transfer event
        $transfer->events()->create([
            'status' => 'in_progress',
            'note' => 'Payment captured, transfer processing',
            'actor_type' => 'system',
        ]);

        return $payment->fresh();
    }

  
    public function refundPayment(int $paymentId, ?float $amount = null): Payment
    {
        $payment = Payment::findOrFail($paymentId);

        if (!in_array($payment->status, ['captured', 'authorized'])) {
            throw new \Exception('Payment cannot be refunded in current status');
        }

        $refundAmount = $amount ?? $payment->amount;

        // Validate refund amount
        if ($refundAmount > $payment->amount) {
            throw new \Exception('Refund amount cannot exceed payment amount');
        }

        // Call mock gateway to process refund
        $gatewayResponse = $this->mockGatewayRefund($payment->gateway, $payment->gateway_ref, $refundAmount);

        if ($gatewayResponse['status'] !== 'success') {
            throw new \Exception("Payment refund failed: {$gatewayResponse['message']}");
        }

        $payment->update([
            'status' => 'refunded',
            'refunded_at' => now(),
        ]);

        // Update transfer status
        $transfer = $payment->transfer;
        $transfer->update(['status' => 'refunded']);
        
        // Create transfer event
        $transfer->events()->create([
            'status' => 'refunded',
            'note' => "Payment refunded: {$refundAmount} {$payment->currency_code}",
            'actor_type' => 'system',
        ]);

        return $payment->fresh();
    }

 
    private function mockGatewayAuthorize(string $gateway, float $amount, string $currency, array $data): array
    {
        // Simulate API call delay
        usleep(100000); // 0.1 seconds

        // Simulate different gateway behaviors
        $gatewayRefs = [
            'stripe' => 'ch_' . strtoupper(substr(md5(uniqid()), 0, 24)),
            'paypal' => 'PAY-' . strtoupper(substr(md5(uniqid()), 0, 17)),
            'checkout' => 'pay_' . strtoupper(substr(md5(uniqid()), 0, 20)),
        ];

        // Mock: Simulate failure for very large amounts (for testing)
        if ($amount > 1000000) {
            return [
                'status' => 'failed',
                'message' => 'Amount exceeds maximum limit',
                'reference' => null,
            ];
        }

        // Mock: Simulate failure for specific test scenarios
        if (isset($data['test_fail']) && $data['test_fail'] === true) {
            return [
                'status' => 'failed',
                'message' => 'Payment declined by bank',
                'reference' => null,
            ];
        }

        // Success response
        return [
            'status' => 'success',
            'message' => 'Payment authorized successfully',
            'reference' => $gatewayRefs[$gateway] ?? 'ref_' . strtoupper(substr(md5(uniqid()), 0, 20)),
            'gateway' => $gateway,
            'authorized_at' => now()->toIso8601String(),
        ];
    }

   
    private function mockGatewayCapture(string $gateway, ?string $gatewayRef): array
    {
        // Simulate API call delay
        usleep(100000); // 0.1 seconds

        if (empty($gatewayRef)) {
            return [
                'status' => 'failed',
                'message' => 'Invalid gateway reference',
            ];
        }

        // Success response
        return [
            'status' => 'success',
            'message' => 'Payment captured successfully',
            'reference' => $gatewayRef,
            'captured_at' => now()->toIso8601String(),
        ];
    }


    private function mockGatewayRefund(string $gateway, ?string $gatewayRef, float $amount): array
    {
        // Simulate API call delay
        usleep(150000); // 0.15 seconds

        if (empty($gatewayRef)) {
            return [
                'status' => 'failed',
                'message' => 'Invalid gateway reference',
            ];
        }

        // Success response
        return [
            'status' => 'success',
            'message' => 'Refund processed successfully',
            'reference' => $gatewayRef,
            'refund_amount' => $amount,
            'refunded_at' => now()->toIso8601String(),
        ];
    }
}

