<?php

namespace App\Filament\Cashier\Widgets;

use App\Models\RegisterSession;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class ShiftStatusWidget extends Widget
{
    protected string $view = 'filament.cashier.widgets.shift-status-widget';

    protected int|string|array $columnSpan = 1;

    public function getSession(): ?RegisterSession
    {
        return RegisterSession::query()
            ->where('user_id', Auth::id())
            ->where('status', 'Open')
            ->latest('id')
            ->first();
    }
}