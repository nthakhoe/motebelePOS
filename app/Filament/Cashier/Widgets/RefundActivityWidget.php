<?php

namespace App\Filament\Cashier\Widgets;

use App\Models\RegisterSession;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class RefundActivityWidget extends Widget
{
    protected string $view =
        'filament.cashier.widgets.refund-activity-widget';

    protected int|string|array $columnSpan = 1;

    public function getRefundCount(): int
    {
        return (int) RegisterSession::query()
            ->where('user_id', Auth::id())
            ->whereDate('opened_at', today())
            ->sum('refund_count');
    }

    public function getSessionRefundCount(): int
    {
        return (int) RegisterSession::query()
            ->where('user_id', Auth::id())
            ->where('status', 'Open')
            ->value('refund_count');
    }
}