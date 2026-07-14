<?php

namespace App\Services;

class TaxService
{
    /**
     * Calculate VAT from an exclusive price.
     */
    public function calculateExclusive(
        float $price,
        float $qty,
        float $rate
    ): array {

        $subtotal = $price * $qty;

        $tax = round(
            $subtotal * ($rate / 100),
            2
        );

        return [

            'subtotal' => round($subtotal, 2),

            'tax' => $tax,

            'total' => round($subtotal + $tax, 2),

        ];
    }

    /**
     * Calculate VAT from an inclusive price.
     */
    public function calculateInclusive(
        float $price,
        float $qty,
        float $rate
    ): array {

        $total = $price * $qty;

        $tax = round(
            $total - ($total / (1 + ($rate / 100))),
            2
        );

        return [

            'subtotal' => round($total - $tax, 2),

            'tax' => $tax,

            'total' => round($total, 2),

        ];
    }
}