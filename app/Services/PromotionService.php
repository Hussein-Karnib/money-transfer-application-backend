<?php

namespace App\Services;

use App\Models\Promotion;

class PromotionService
{
   
    public function validateAndCalculate(string $code, float $amount, ?int $destinationCountryId = null): array
    {
        $promo = Promotion::where('code', $code)->first();


        if (!$promo || !$promo->isValidFor($amount, $destinationCountryId)) {
            throw new \Exception('Invalid or expired promo code');
        }

        // Base discount
        $discount = 0.0;

        if ($promo->discount_type === 'percent') {
            $discount = $amount * ((float) $promo->discount_value) / 100;
        } else {
            $discount = (float) $promo->discount_value;
        }

        // Apply max cap if set
        if ($promo->max_discount !== null) {
            $discount = min($discount, (float) $promo->max_discount);
        }

        // Never allow discount greater than amount
        $discount = min($discount, $amount);

        return [$promo, round($discount, 2)];
    }
}


