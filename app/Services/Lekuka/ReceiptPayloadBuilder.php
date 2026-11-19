<?php

namespace App\Services\Lekuka;

use App\Models\LekukaDevice;
use App\Models\LekukaFiscalDay;
use App\Models\LekukaReceipt;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class ReceiptPayloadBuilder
{
    public function __construct(
        protected ReceiptCanonicalizer $canonicalizer,
        protected SignatureService $signature,
    ) {
    }

    /**
     * Build Lekuka receipt payload.
     *
     * Receipt counters are supplied by ReceiptService.
     * This class does NOT allocate or increment fiscal counters.
     */
    public function build(
        Sale $sale,
        LekukaFiscalDay $day,
        LekukaDevice $device,
        int $receiptCounter,
        int $receiptGlobalNo,
    ): array {

        $sale->loadMissing([
            'items.product',
            'payments.paymentMethod',
            'customer',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Basic Receipt Information
        |--------------------------------------------------------------------------
        */

        $payload = [

            'receiptType' => 'Receipt',

            'receiptCurrency' => 'LSL',

            'receiptCounter' => $receiptCounter,

            'receiptGlobalNo' => $receiptGlobalNo,

            'invoiceNo' => $sale->sale_number,

            'receiptDate' => $sale->created_at
                ? $sale->created_at->format('Y-m-d\TH:i:s')
                : now()->format('Y-m-d\TH:i:s'),

            /*
             * Your POS prices are VAT inclusive.
             */
            'receiptLinesTaxInclusive' => true,

            'taxRoundingType' => 'PerReceipt',

            'receiptLines' => [],

            'receiptTaxes' => [],

            'receiptPayments' => [],

            'receiptTotal' => round(
                (float) $sale->total,
                2
            ),

            'receiptPrintForm' => 'Receipt48',
        ];

        /*
        |--------------------------------------------------------------------------
        | Buyer
        |--------------------------------------------------------------------------
        */

        if (
            $sale->customer &&
            $sale->customer->customer_code !== 'WALK-IN'
        ) {

            $buyerData = [

                'buyerRegisterName' => trim(
                    $sale->customer->first_name . ' ' .
                    $sale->customer->last_name
                ),
            ];

            /*
             * Only include TIN when one actually exists.
             */
            if (!empty($sale->customer->tin)) {
                $buyerData['buyerTIN'] = $sale->customer->tin;
            }

            $payload['buyerData'] = $buyerData;
        }

        /*
        |--------------------------------------------------------------------------
        | Receipt Lines
        |--------------------------------------------------------------------------
        */

        $includeLineTaxCodes = $sale->items->isNotEmpty()
            && $sale->items->every(
                fn ($item) => !empty($item->lekuka_tax_code)
            );

        foreach ($sale->items as $index => $item) {

            $taxId = (int) $item->lekuka_tax_id;
            $taxType = $item->lekuka_tax_type;
            $taxRate = $item->tax_rate !== null
                ? (float) $item->tax_rate
                : null;

            if ($taxId <= 0 || empty($taxType)) {
                throw new \RuntimeException(
                    "Missing main Lekuka tax configuration for sale item ID {$item->id}."
                );
            }

            if (!in_array($taxType, ['VAT', 'NonVAT', 'Exempt'], true)) {
                throw new \RuntimeException(
                    "Invalid main tax type '{$taxType}' for sale item ID {$item->id}."
                );
            }

            $lineTotal = round((float) $item->line_total, 2);

            $line = [
                'receiptLineType' => 'Sale',
                'receiptLineSubType' => 'Product',
                'receiptLineNo' => $index + 1,

                'receiptLineHSCode' => $item->product?->hs_code,

                'receiptLineName' => $item->product?->product_name
                    ?? 'Unknown Product',

                'receiptLinePrice' => round(
                    (float) $item->unit_price,
                    2
                ),

                'receiptLineQuantity' => (float) $item->quantity,

                'receiptLineTotal' => $lineTotal,

                'taxID' => $taxId,
            ];

            // Include taxCode only when every receipt line has one.
            if ($includeLineTaxCodes) {
                $line['taxCode'] = $item->lekuka_tax_code;
            }

            // Exempt taxes must not contain taxRate.
            if ($taxType !== 'Exempt' && $taxRate !== null) {
                $line['taxRate'] = $taxRate;
            }

            /*
             * Additional tax allocation.
             * The rate and type are defined by Lekuka using taxID.
             */
            if ($item->lekuka_additional_tax_id !== null) {

                $additionalTaxId = (int) $item->lekuka_additional_tax_id;

                if (
                    $additionalTaxId <= 0 ||
                    empty($item->lekuka_additional_tax_type) ||
                    $item->lekuka_additional_tax_rate === null
                ) {
                    throw new \RuntimeException(
                        "Incomplete additional tax configuration for sale item ID {$item->id}."
                    );
                }

                if (
                    !in_array(
                        $item->lekuka_additional_tax_type,
                        ['PercentageLevy', 'FixedValueLevy'],
                        true
                    )
                ) {
                    throw new \RuntimeException(
                        "Invalid additional tax type '{$item->lekuka_additional_tax_type}' for sale item ID {$item->id}."
                    );
                }

                if ($additionalTaxId === $taxId) {
                    throw new \RuntimeException(
                        "Main and additional tax IDs cannot be identical for sale item ID {$item->id}."
                    );
                }

                $line['additionalTaxes'] = [
                    [
                        'taxID' => $additionalTaxId,
                    ],
                ];
            }

            $payload['receiptLines'][] = $line;
        }

        /*
        |--------------------------------------------------------------------------
        | Receipt Taxes
        |--------------------------------------------------------------------------
        */

        $payload['receiptTaxes'] = $this->buildReceiptTaxes(
            $sale->items
        );

        
        /*
        |--------------------------------------------------------------------------
        | Receipt Payments
        |--------------------------------------------------------------------------
        */

        $completedPayments = $sale->payments->filter(
            fn ($payment) =>
                strtolower((string) $payment->status) === 'completed'
        );

        foreach ($completedPayments as $payment) {

            $methodName = strtolower(
                trim($payment->paymentMethod?->name ?? '')
            );

            $moneyTypeCode = match ($methodName) {

                'cash' => 'Cash',

                'card', 'credit card', 'debit card' => 'Card',

                'mpesa',
                'm-pesa',
                'mobile money',
                'mobilemoney',
                'mobile money payment',
                'ecocash',
                'eco cash' => 'MobileWallet',

                'coupon' => 'Coupon',

                'credit' => 'Credit',

                'bank transfer',
                'banktransfer' => 'BankTransfer',

                default => 'Other',
            };

            $payload['receiptPayments'][] = [
                'moneyTypeCode' => $moneyTypeCode,
                'paymentAmount' => round(
                    (float) $payment->amount_paid,
                    2
                ),
            ];
        }

        /*
         * Validate that receipt payments reconcile with receipt total.
         */
        $paymentTotal = round(
            collect($payload['receiptPayments'])
                ->sum('paymentAmount'),
            2
        );

        if ($paymentTotal !== $payload['receiptTotal']) {
            throw new \RuntimeException(
                "Receipt payment total ({$paymentTotal}) does not match " .
                "receipt total ({$payload['receiptTotal']})."
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Previous Receipt Hash
        |--------------------------------------------------------------------------
        |
        | The previous receipt hash is part of the receipt signing chain.
        |
        | receipt_global_no is currently a VARCHAR column in your database,
        | therefore use a numeric CAST when ordering.
        |
        */

        $previousHash = LekukaReceipt::query()
            ->where('device_id', $device->id)
            ->where('fiscal_day_no', $day->fiscal_day_no)
            ->whereNotNull('device_hash')
            ->where('receipt_counter', '<', $receiptCounter)
            ->orderByRaw('CAST(receipt_counter AS UNSIGNED) DESC')
            ->value('device_hash');

        /*
        |--------------------------------------------------------------------------
        | Canonical Receipt
        |--------------------------------------------------------------------------
        */

        $canonical = $this->canonicalizer->build(

            payload: $payload,

            device: $device,

            previousReceiptHash: $previousHash,
        );

        /*
        |--------------------------------------------------------------------------
        | Device Signature
        |--------------------------------------------------------------------------
        */

        $signature = $this->signature->sign(

            device: $device,

            canonicalData: $canonical,
        );

        /*
        |--------------------------------------------------------------------------
        | Attach Device Signature
        |--------------------------------------------------------------------------
        */

        $payload['receiptDeviceSignature'] = [

            'hash' =>
                $signature['hash'],

            'signature' =>
                $signature['signature'],

            'certificateThumbprint' =>
                $signature['certificateThumbprint']
                ?? $device->thumbprint,
        ];

        /*
        |--------------------------------------------------------------------------
        | Return Complete Receipt Package
        |--------------------------------------------------------------------------
        */

        return [

            'payload' => [

                'receipt' => $payload,

            ],

            'signature' => $signature,

            'canonical' => $canonical,

        ];
    
    }

    /**
     * Build receipt-level tax records.
     *
     * Supports:
     * - VAT
     * - NonVAT
     * - Exempt
     * - PercentageLevy
     * - FixedValueLevy
     *
     * Uses PerReceipt rounding.
     */
    protected function buildReceiptTaxes($items): array
    {
        $combinations = [];

        /*
         * Group receipt lines by their complete tax allocation.
         * This allows the correct calculation where different
         * products have different combinations of taxes.
         */
        foreach ($items as $item) {

            $taxes = [];

            $mainType = $item->lekuka_tax_type;
            $mainRate = $item->tax_rate !== null
                ? (float) $item->tax_rate
                : null;

            $mainTax = [
                'taxID' => (int) $item->lekuka_tax_id,
                'taxType' => $mainType,
                'taxRate' => $mainRate,
                'taxCode' => $item->lekuka_tax_code,
            ];

            $this->validateReceiptTax(
                $mainTax,
                $item->id,
                false
            );

            $taxes[] = $mainTax;

            if ($item->lekuka_additional_tax_id !== null) {

                $additionalTax = [
                    'taxID' => (int) $item->lekuka_additional_tax_id,
                    'taxType' => $item->lekuka_additional_tax_type,
                    'taxRate' => (float) $item->lekuka_additional_tax_rate,
                    'taxCode' => $item->lekuka_additional_tax_code,
                ];

                $this->validateReceiptTax(
                    $additionalTax,
                    $item->id,
                    true
                );

                $taxes[] = $additionalTax;
            }

            $taxIds = array_column($taxes, 'taxID');

            if (count($taxIds) !== count(array_unique($taxIds))) {
                throw new \RuntimeException(
                    "Duplicate tax allocation for sale item ID {$item->id}."
                );
            }

            $combinationKey = json_encode($taxes);

            if (!isset($combinations[$combinationKey])) {

                $combinations[$combinationKey] = [
                    'taxes' => $taxes,
                    'lineTotal' => 0,
                    'quantity' => 0,
                ];
            }

            $combinations[$combinationKey]['lineTotal'] +=
                (float) $item->line_total;

            $combinations[$combinationKey]['quantity'] +=
                (float) $item->quantity;
        }

        $taxGroups = [];

        foreach ($combinations as $combination) {

            $taxes = $combination['taxes'];

            $lineTotal = (float) $combination['lineTotal'];

            $quantity = (float) $combination['quantity'];

            $fixedLevyAmount = 0.0;

            $percentageRateTotal = 0.0;

            foreach ($taxes as $tax) {

                if ($tax['taxType'] === 'FixedValueLevy') {

                    $fixedLevyAmount +=
                        $quantity * $tax['taxRate'];
                }

                if (
                    in_array(
                        $tax['taxType'],
                        ['VAT', 'NonVAT', 'PercentageLevy'],
                        true
                    )
                ) {
                    $percentageRateTotal += $tax['taxRate'];
                }
            }

            /*
             * Remove fixed levies first.
             */
            $amountBeforePercentageTaxes =
                $lineTotal - $fixedLevyAmount;

            /*
             * Extract the common sales base from the
             * tax-inclusive amount.
             */
            $denominator = 1 + ($percentageRateTotal / 100);

            if ($denominator <= 0) {
                throw new \RuntimeException(
                    'Invalid combined tax rates in receipt.'
                );
            }

            $salesAmountWithoutTax =
                $amountBeforePercentageTaxes / $denominator;

            $multipleTaxes = count($taxes) > 1;

            foreach ($taxes as $tax) {

                $taxId = $tax['taxID'];
                $taxType = $tax['taxType'];
                $taxRate = $tax['taxRate'];
                $taxCode = $tax['taxCode'];

                /*
                 * Calculate tax amount according to
                 * the Lekuka tax type.
                 */
                $taxAmount = match ($taxType) {

                    'Exempt' => 0.0,

                    'FixedValueLevy' =>
                        $quantity * $taxRate,

                    'VAT',
                    'NonVAT',
                    'PercentageLevy' =>
                        $salesAmountWithoutTax
                        * $taxRate / 100,

                    default => throw new \RuntimeException(
                        "Unsupported tax type '{$taxType}'."
                    ),
                };

                /*
                 * Lekuka salesAmountWithTax:
                 *
                 * Single tax:
                 *   Original line total.
                 *
                 * Multiple taxes:
                 *   Common sales base + this tax amount.
                 */
                if ($taxType === 'Exempt' || !$multipleTaxes) {

                    $salesAmountWithTax = $lineTotal;

                } else {

                    $salesAmountWithTax =
                        $salesAmountWithoutTax + $taxAmount;
                }

                $taxKey = implode('|', [
                    $taxId,
                    $taxType,
                    $taxRate !== null
                        ? number_format($taxRate, 4, '.', '')
                        : '',
                    $taxCode ?? '',
                ]);

                if (!isset($taxGroups[$taxKey])) {

                    $taxGroups[$taxKey] = [
                        'taxID' => $taxId,
                        'taxType' => $taxType,
                        'taxRate' => $taxRate,
                        'taxCode' => $taxCode,
                        'taxAmount' => 0,
                        'salesAmountWithTax' => 0,
                    ];
                }

                $taxGroups[$taxKey]['taxAmount'] += $taxAmount;

                $taxGroups[$taxKey]['salesAmountWithTax'] +=
                    $salesAmountWithTax;
            }
        }

        /*
         * Build final receiptTaxes array.
         */
        $receiptTaxes = [];

        foreach ($taxGroups as $group) {

            $record = [
                'taxID' => $group['taxID'],
                'taxType' => $group['taxType'],

                'taxAmount' => round(
                    $group['taxAmount'],
                    2
                ),

                'salesAmountWithTax' => round(
                    $group['salesAmountWithTax'],
                    2
                ),
            ];

            if (!empty($group['taxCode'])) {
                $record['taxCode'] = $group['taxCode'];
            }

            if (
                $group['taxType'] !== 'Exempt' &&
                $group['taxRate'] !== null
            ) {
                $record['taxRate'] = $group['taxRate'];
            }

            $receiptTaxes[] = $record;
        }

        return $receiptTaxes;
    }

    /**
     * Validate a tax allocation before building the payload.
     */
    protected function validateReceiptTax(
        array $tax,
        int $saleItemId,
        bool $additional
    ): void {

        if ($tax['taxID'] <= 0 || empty($tax['taxType'])) {
            throw new \RuntimeException(
                "Invalid tax configuration for sale item ID {$saleItemId}."
            );
        }

        $allowedTypes = $additional
            ? ['PercentageLevy', 'FixedValueLevy']
            : ['VAT', 'NonVAT', 'Exempt'];

        if (!in_array($tax['taxType'], $allowedTypes, true)) {
            throw new \RuntimeException(
                "Tax type '{$tax['taxType']}' is not allowed as "
                . ($additional ? 'an additional tax' : 'a main tax')
                . " for sale item ID {$saleItemId}."
            );
        }

        if (
            $tax['taxType'] !== 'Exempt' &&
            $tax['taxRate'] === null
        ) {
            throw new \RuntimeException(
                "Missing tax rate for sale item ID {$saleItemId}."
            );
        }

        if (
            $tax['taxRate'] !== null &&
            $tax['taxRate'] < 0
        ) {
            throw new \RuntimeException(
                "Negative tax rate for sale item ID {$saleItemId}."
            );
        }
    }

}