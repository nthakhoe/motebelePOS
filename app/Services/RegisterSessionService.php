<?php

namespace App\Services;

use App\Models\RegisterSession;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RegisterSessionService
{
    /**
     * Get the currently open shift for a cashier.
     */
    public function currentForUser(int $userId): ?RegisterSession
    {
        return RegisterSession::query()
            ->where('user_id', $userId)
            ->where('status', 'Open')
            ->latest('id')
            ->first();
    }

    /**
     * Record a completed sale against the cashier's active shift.
     */
    public function recordSale(Sale $sale): void
    {
        DB::transaction(function () use ($sale) {

            $session = RegisterSession::query()
                ->where('user_id', $sale->user_id)
                ->where('company_id', $sale->company_id)
                ->where('branch_id', $sale->branch_id)
                ->where('status', 'Open')
                ->lockForUpdate()
                ->first();

            if (! $session) {
                throw new RuntimeException(
                    'No open register session exists for this sale.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Payment totals
            |--------------------------------------------------------------------------
            */

            $payments = $sale->payments()
                ->with('paymentMethod')
                ->where('status', 'completed')
                ->get();

            $cash = 0;
            $card = 0;
            $mobile = 0;
            $bank = 0;
            $credit = 0;

            foreach ($payments as $payment) {

                $amount = (float) $payment->amount_paid;

                $method = strtolower(
                    trim(
                        $payment->paymentMethod?->name
                        ?? $sale->sale_type
                        ?? ''
                    )
                );

                if (
                    str_contains($method, 'cash')
                ) {
                    $cash += $amount;

                } elseif (
                    str_contains($method, 'card')
                ) {
                    $card += $amount;

                } elseif (
                    str_contains($method, 'mobile')
                    || str_contains($method, 'mpesa')
                    || str_contains($method, 'ecocash')
                    || str_contains($method, 'wallet')
                ) {
                    $mobile += $amount;

                } elseif (
                    str_contains($method, 'bank')
                    || str_contains($method, 'transfer')
                ) {
                    $bank += $amount;

                } elseif (
                    str_contains($method, 'credit')
                ) {
                    $credit += $amount;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Sale totals
            |--------------------------------------------------------------------------
            */

            $subtotal = (float) $sale->subtotal;
            $discount = (float) $sale->discount;
            $tax = (float) $sale->tax;
            $total = (float) $sale->total;

            /*
            |--------------------------------------------------------------------------
            | Update session
            |--------------------------------------------------------------------------
            */

            $session->increment(
                'cash_sales',
                $cash
            );

            $session->increment(
                'card_sales',
                $card
            );

            $session->increment(
                'mobile_sales',
                $mobile
            );

            $session->increment(
                'bank_sales',
                $bank
            );

            $session->increment(
                'credit_sales',
                $credit
            );

            $session->increment(
                'gross_sales',
                $subtotal
            );

            $session->increment(
                'discount_total',
                $discount
            );

            $session->increment(
                'tax_total',
                $tax
            );

            $session->increment(
                'net_sales',
                $total
            );

            $session->increment(
                'transaction_count',
                1
            );

            $session->increment(
                'receipt_count',
                1
            );
        });
    }

    /**
     * Record a completed refund against the active shift.
     */
    public function recordRefund(
        Sale $sale,
        float $refundAmount,
        int $userId
    ): void {

        DB::transaction(function () use (
            $sale,
            $refundAmount,
            $userId
        ) {

            $session = RegisterSession::query()
                ->where('user_id', $userId)
                ->where('company_id', $sale->company_id)
                ->where('branch_id', $sale->branch_id)
                ->where('status', 'Open')
                ->lockForUpdate()
                ->first();

            if (! $session) {
                throw new RuntimeException(
                    'No open register session exists for this refund.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Refund
            |--------------------------------------------------------------------------
            */

            $session->increment(
                'refund_total',
                $refundAmount
            );

            $session->increment(
                'refund_count',
                1
            );
        });
    }
}