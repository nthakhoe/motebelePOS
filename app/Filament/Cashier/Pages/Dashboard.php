<?php

namespace App\Filament\Cashier\Pages;
use App\Filament\Cashier\Widgets\ShiftStatusWidget;
use App\Filament\Cashier\Widgets\RefundActivityWidget;
use App\Filament\Cashier\Widgets\QuickActionsWidget;
use UnitEnum;
use BackedEnum;
use Filament\Pages\Page;

class Dashboard extends Page
{
    protected string $view = 'filament.cashier.pages.dashboard';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = 1;

    protected static UnitEnum|string|null $navigationGroup = null;

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getFooterWidgets(): array
    {
        return [
            QuickActionsWidget::class,
            ShiftStatusWidget::class,
            RefundActivityWidget::class,
        ];
    }

    public function getColumns(): int | array
    {
        return 2;
    }
}
