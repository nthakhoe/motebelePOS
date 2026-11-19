<?php

namespace App\Services;

class TaxService
{
    /**
     * Calculate tax from an exclusive price.
     */
    public function calculateExclusive(
        float $price,
        float $qty,
        float $rate
    ): array {

        $subtotal = $price * $qty;
        $tax = round($subtotal * ($rate / 100), 2);

        return [
            'subtotal' => round($subtotal, 2),
            'tax' => $tax,
            'total' => round($subtotal + $tax, 2),
        ];
    }

    /**
     * Calculate all applicable taxes from a tax-inclusive price.
     *
     * Percentage taxes are calculated against the same
     * tax-exclusive base.
     *
     * Fixed-value levies are deducted before extracting
     * the percentage-tax base.
     */
    public function calculateInclusive(
        float $price,
        float $qty,
        float $rate,
        float $additionalRate = 0,
        ?string $additionalType = 'PercentageLevy',
        ?string $mainType = 'VAT'
    ): array {

        $grossTotal = round($price * $qty, 2);

        $mainRate = max(0, $rate);
        $additionalRate = max(0, $additionalRate);

        $fixedLevy = 0.00;
        $percentageRate = 0.00;

        // Main tax
        if (in_array($mainType, ['VAT', 'NonVAT', 'PercentageLevy'], true)) {
            $percentageRate += $mainRate;
        }

        // Additional tax
        if ($additionalType === 'PercentageLevy') {
            $percentageRate += $additionalRate;
        } elseif ($additionalType === 'FixedValueLevy') {
            $fixedLevy = round($qty * $additionalRate, 2);
        }

        if ($fixedLevy > $grossTotal) {
            throw new \InvalidArgumentException(
                'Fixed levy cannot exceed the tax-inclusive selling price.'
            );
        }

        $denominator = 1 + ($percentageRate / 100);

        if ($denominator <= 0) {
            throw new \InvalidArgumentException(
                'Invalid combined tax rates.'
            );
        }

        $subtotal = round(
            ($grossTotal - $fixedLevy) / $denominator,
            2
        );

        $mainTax = 0.00;
        $additionalTax = 0.00;

        if (in_array($mainType, ['VAT', 'NonVAT', 'PercentageLevy'], true)) {
            $mainTax = round($subtotal * ($mainRate / 100), 2);
        }

        if ($additionalType === 'PercentageLevy') {
            $additionalTax = round(
                $subtotal * ($additionalRate / 100),
                2
            );
        } elseif ($additionalType === 'FixedValueLevy') {
            $additionalTax = $fixedLevy;
        }

        $totalTax = round($mainTax + $additionalTax, 2);

        return [
            'subtotal' => $subtotal,
            'tax' => $totalTax,
            'main_tax' => $mainTax,
            'additional_tax' => $additionalTax,
            'fixed_levy' => $fixedLevy,
            'total' => $grossTotal,
        ];
    }
}
