<?php

namespace App\Services;

use InvalidArgumentException;

class DiscountService
{
    public function calculate(
        float $unitPrice,
        float $quantity,
        float $discount = 0,
        float $costPrice = 0
    ): array {

        if ($unitPrice < 0) {
            throw new InvalidArgumentException('Selling price cannot be negative.');
        }

        if ($costPrice < 0) {
            throw new InvalidArgumentException('Cost price cannot be negative.');
        }

        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be greater than zero.');
        }

        if ($discount < 0) {
            throw new InvalidArgumentException('Discount cannot be negative.');
        }

        $grossAmount = round($unitPrice * $quantity, 2);
        $costAmount = round($costPrice * $quantity, 2);

        $maximumDiscount = max(0, round($grossAmount - $costAmount, 2));

        $discount = round($discount, 2);

        if ($discount > $maximumDiscount) {
            throw new InvalidArgumentException(
                'Discount cannot exceed M' .
                number_format($maximumDiscount, 2) .
                '. The selling price cannot be lower than the product cost price.'
            );
        }

        $netAmount = round($grossAmount - $discount, 2);

        return [
            'gross_amount' => $grossAmount,
            'cost_amount' => $costAmount,
            'maximum_discount' => $maximumDiscount,
            'discount' => $discount,
            'net_amount' => $netAmount,
            'effective_unit_price' => $netAmount / $quantity,
        ];
    }
}
