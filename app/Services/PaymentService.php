<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Exception;

class PaymentService
{
    /**
     * Record payment for a sale.
     */
    public function recordPayment(
        Sale $sale,
        int $paymentMethodId,
        float $amountReceived,
        User $cashier,
        ?string $referenceNumber = null,
        ?string $authorizationCode = null,
        ?string $remarks = null,
    ): Payment {

        return DB::transaction(function () use (
            $sale,
            $paymentMethodId,
            $amountReceived,
            $cashier,
            $referenceNumber,
            $authorizationCode,
            $remarks
        ) {

            $paymentMethod = PaymentMethod::findOrFail($paymentMethodId);

            if ($amountReceived < $sale->total) {

                throw new Exception(
                    'Amount received is less than the sale total.'
                );

            }

            $change = $amountReceived - $sale->total;

            $payment = Payment::create([

                'company_id' => $sale->company_id,

                'branch_id' => $sale->branch_id,

                'sale_id' => $sale->id,

                'payment_method_id' => $paymentMethod->id,

                'user_id' => $cashier->id,

                'amount_due' => $sale->total,

                'amount_received' => $amountReceived,

                'amount_paid' => $sale->total,

                'change_amount' => $change,

                'reference_number' => $referenceNumber,

                'authorization_code' => $authorizationCode,

                'status' => 'Completed',

                'payment_date' => now(),

                'remarks' => $remarks,

            ]);

            $sale->update([

                'amount_paid' => $sale->total,

                'change' => $change,

                'status' => 'Completed',

                'completed_at' => now(),

            ]);

            return $payment;

        });

    }
}