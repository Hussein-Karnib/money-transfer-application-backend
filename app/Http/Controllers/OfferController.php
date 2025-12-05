<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OfferController extends Controller
{
    /**
     * Display available offers for purchase
     */
    public function index()
    {
        $user = Auth::user();
        
        // Define available offers
        $availableOffers = [
            [
                'name' => 'Fee Shield Pass',
                'description' => 'Reduce transfer fees by up to 60% on your next transfer. Perfect for larger amounts!',
                'price' => 9.99,
                'savings' => 'Up to 60% fee reduction',
                'icon' => 'shield-check',
                'color' => 'primary',
            ],
            [
                'name' => 'Instant Upgrade',
                'description' => 'Upgrade any transfer to instant delivery (15 minutes). Skip the queue!',
                'price' => 14.99,
                'savings' => 'Instant delivery guarantee',
                'icon' => 'lightning-charge',
                'color' => 'warning',
            ],
            [
                'name' => 'Rate Lock Premium',
                'description' => 'Lock in today\'s exchange rate for 48 hours. Protect against rate fluctuations.',
                'price' => 7.99,
                'savings' => 'Rate protection for 48h',
                'icon' => 'lock-fill',
                'color' => 'info',
            ],
            [
                'name' => 'Cash Pickup Priority',
                'description' => 'Guarantee fast cash availability at partner agents. Priority processing.',
                'price' => 12.99,
                'savings' => 'Priority pickup service',
                'icon' => 'cash-coin',
                'color' => 'success',
            ],
            [
                'name' => 'Mobile Wallet Bonus',
                'description' => 'Add 5% cashback bonus to recipient mobile wallet. They receive extra money!',
                'price' => 19.99,
                'savings' => '5% cashback bonus',
                'icon' => 'wallet2',
                'color' => 'danger',
            ],
            [
                'name' => 'Transfer Bundle Pack',
                'description' => 'Get all premium features: Fee Shield + Instant + Rate Lock. Best value!',
                'price' => 29.99,
                'savings' => 'All-in-one premium pack',
                'icon' => 'gift-fill',
                'color' => 'purple',
            ],
        ];

        // Get user's purchased active offers
        $purchasedOffers = DB::table('user_offers')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->orderBy('purchased_at', 'desc')
            ->get();

        return view('offers.index', compact('availableOffers', 'purchasedOffers', 'user'));
    }

    /**
     * Purchase an offer
     */
    public function purchase(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'offer_name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0.01'],
        ]);

        // Define available offers with prices
        $offers = [
            'Fee Shield Pass' => 9.99,
            'Instant Upgrade' => 14.99,
            'Rate Lock Premium' => 7.99,
            'Cash Pickup Priority' => 12.99,
            'Mobile Wallet Bonus' => 19.99,
            'Transfer Bundle Pack' => 29.99,
        ];

        $offerName = $validated['offer_name'];
        $offerPrice = $offers[$offerName] ?? null;

        if (!$offerPrice) {
            return back()->withErrors(['offer_name' => 'Invalid offer selected.'])->withInput();
        }

        // Verify price matches
        if (abs($offerPrice - $validated['price']) > 0.01) {
            return back()->withErrors(['price' => 'Offer price mismatch.'])->withInput();
        }

        // Check user balance
        if ($user->balance < $offerPrice) {
            return back()->withErrors(['price' => 'Insufficient balance. Available: $' . number_format($user->balance, 2)])->withInput();
        }

        // Check currency
        if ($user->balance_currency !== 'USD') {
            return back()->withErrors(['price' => 'Offers can only be purchased with USD balance.'])->withInput();
        }

        try {
            DB::transaction(function () use ($user, $offerName, $offerPrice) {
                // Deduct balance
                $user->decrement('balance', $offerPrice);

                // Create offer purchase record
                $expiresAt = now()->addDays(30); // Offers expire in 30 days
                
                DB::table('user_offers')->insert([
                    'user_id' => $user->id,
                    'offer_name' => $offerName,
                    'price' => $offerPrice,
                    'description' => $this->getOfferDescription($offerName),
                    'status' => 'active',
                    'purchased_at' => now(),
                    'expires_at' => $expiresAt,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Log audit
                AuditLogController::logSystemAction(
                    $user->id,
                    'purchase_offer',
                    'user_offers',
                    null,
                    [
                        'offer_name' => $offerName,
                        'price' => $offerPrice,
                    ]
                );
            });

            return redirect()->route('offers.index')
                ->with('success', "Successfully purchased '{$offerName}'! You can now use it when creating transfers.");

        } catch (\Exception $e) {
            \Log::error('Offer purchase error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'offer_name' => $offerName,
            ]);

            return back()->withErrors(['price' => 'An error occurred during purchase. Please try again.'])->withInput();
        }
    }

    private function getOfferDescription(string $offerName): string
    {
        $descriptions = [
            'Fee Shield Pass' => 'Reduce transfer fees by up to 60% on your next transfer.',
            'Instant Upgrade' => 'Upgrade any transfer to instant delivery (15 minutes).',
            'Rate Lock Premium' => 'Lock in today\'s exchange rate for 48 hours.',
            'Cash Pickup Priority' => 'Guarantee fast cash availability at partner agents.',
            'Mobile Wallet Bonus' => 'Add 5% cashback bonus to recipient mobile wallet.',
            'Transfer Bundle Pack' => 'Get all premium features: Fee Shield + Instant + Rate Lock.',
        ];

        return $descriptions[$offerName] ?? '';
    }
}
