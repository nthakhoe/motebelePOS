<?php

namespace App\Filament\Company\Widgets;

use App\Models\Payment;
use Filament\Widgets\Widget;

class PaymentMethodBreakdown extends Widget
{
    protected string $view =
        'filament.company.widgets.payment-method-breakdown';

    protected int|string|array $columnSpan = 1;

    public array $paymentMethods = [];

    public float $totalPayments = 0;

    public function mount(): void
    {
        $companyId = auth()->user()->company_id;

        $payments = Payment::query()
            ->where('company_id', $companyId)
            ->whereDate('payment_date', today())
            ->whereIn('status', [
                'paid',
                'completed',
                'success',
                'successful',
            ])
            ->whereHas('sale', function ($query) {
                $query->where('status', 'completed');
            })
            ->with('paymentMethod')
            ->get();

        $grouped = $payments
            ->groupBy('payment_method_id')
            ->map(function ($items) {

                $first = $items->first();

                return [
                    'name' => $first->paymentMethod?->name
                        ?? 'Unknown',

                    'amount' => (float) $items->sum('amount_paid'),

                    'count' => $items->count(),
                ];
            })
            ->values();

        $this->totalPayments = (float) $grouped->sum('amount');

        $this->paymentMethods = $grouped
            ->map(function ($item) {

                $item['percentage'] =
                    $this->totalPayments > 0
                        ? round(
                            ($item['amount'] / $this->totalPayments) * 100,
                            1
                        )
                        : 0;

                return $item;
            })
            ->sortByDesc('amount')
            ->values()
            ->toArray();
    }
}