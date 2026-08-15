<?php

namespace App\Filament\Cashier\Pages;

use App\Models\RegisterSession;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use UnitEnum;
use BackedEnum;

class ShiftManagement extends Page
{
    protected static ?string $navigationLabel = 'Shift Management';

    protected static ?string $title = 'Shift Management';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon ='heroicon-o-clock';

    protected static UnitEnum|string|null $navigationGroup = 'Sales';

    protected string $view = 'filament.cashier.pages.shift-management';

    public ?RegisterSession $session = null;

    public function mount(): void
    {
        $this->loadCurrentSession();
    }

    protected function loadCurrentSession(): void
    {
        $user = Auth::user();

        $this->session = RegisterSession::query()
            ->where('user_id', $user->id)
            ->where('status', 'Open')
            ->latest('id')
            ->first();
    }

    protected function getActions(): array
    {
        if ($this->session) {
            return [
                Action::make('closeShift')
                    ->label('Close Shift')
                    ->icon('heroicon-o-lock-closed')
                    ->color('warning')
                    ->modalHeading('Close Shift')
                    ->modalDescription(
                        'Count the cash in the drawer and enter the actual closing amount.'
                    )
                    ->schema([

                        TextInput::make('closing_amount')
                            ->label('Actual Cash Count')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->prefix('M'),

                        Textarea::make('closing_notes')
                            ->label('Closing Notes')
                            ->rows(3)
                            ->placeholder(
                                'Optional notes about the shift...'
                            ),

                    ])
                    ->requiresConfirmation()
                    ->action(function (array $data): void {

                        $this->closeShift(
                            (float) $data['closing_amount'],
                            $data['closing_notes'] ?? null
                        );

                    }),
            ];
        }

        return [
            Action::make('openShift')
                ->label('Open Shift')
                ->icon('heroicon-o-lock-open')
                ->color('success')
                ->modalHeading('Open New Shift')
                ->modalDescription(
                    'Enter the amount of cash physically placed in the drawer at the beginning of the shift.'
                )
                ->schema([

                    TextInput::make('opening_float')
                        ->label('Opening Cash')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->default(0)
                        ->prefix('M'),

                ])
                ->action(function (array $data): void {

                    $this->openShift(
                        (float) $data['opening_float']
                    );

                }),
        ];
    }

    public function openShift(float $openingFloat): void
    {
        $user = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | Prevent duplicate open shifts
        |--------------------------------------------------------------------------
        */

        $existing = RegisterSession::query()
            ->where('user_id', $user->id)
            ->where('status', 'Open')
            ->first();

        if ($existing) {

            $this->session = $existing;

            Notification::make()
                ->title('Shift already open')
                ->body(
                    'You already have an active shift: ' .
                    $existing->session_number
                )
                ->warning()
                ->send();

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Determine terminal
        |--------------------------------------------------------------------------
        |
        | We will connect this to the POS terminal assignment already used
        | by the cashier.
        |
        */

        $terminalId = $user->terminal_id ?? null;

        if (! $terminalId) {

            Notification::make()
                ->title('Terminal not assigned')
                ->body(
                    'This cashier does not have a terminal assigned.'
                )
                ->danger()
                ->send();

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Company / Branch
        |--------------------------------------------------------------------------
        */

        $companyId = $user->company_id ?? null;
        $branchId = $user->branch_id ?? null;

        if (! $companyId || ! $branchId) {

            Notification::make()
                ->title('Company or branch not assigned')
                ->body(
                    'The cashier must be assigned to a company and branch before opening a shift.'
                )
                ->danger()
                ->send();

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Generate session number
        |--------------------------------------------------------------------------
        */

        $sessionNumber =
            'SES-' .
            now()->format('YmdHis') .
            '-' .
            str_pad(
                (string) ($user->id),
                4,
                '0',
                STR_PAD_LEFT
            );

        /*
        |--------------------------------------------------------------------------
        | Create session
        |--------------------------------------------------------------------------
        */

        $session = RegisterSession::create([

            'company_id' => $companyId,

            'branch_id' => $branchId,

            'terminal_id' => $terminalId,

            'user_id' => $user->id,

            'session_number' => $sessionNumber,

            'status' => 'Open',

            'opening_float' => $openingFloat,

            'opened_at' => now(),

            'opened_by' => $user->id,

        ]);

        $this->session = $session;

        Notification::make()
            ->title('Shift opened')
            ->body(
                'Shift ' .
                $session->session_number .
                ' has been opened successfully.'
            )
            ->success()
            ->send();
    }

    public function closeShift(
        float $closingAmount,
        ?string $closingNotes = null
    ): void {

        if (! $this->session) {

            Notification::make()
                ->title('No active shift')
                ->body('There is no open shift to close.')
                ->warning()
                ->send();

            return;
        }

        $user = Auth::user();

        DB::transaction(function () use (
            $closingAmount,
            $closingNotes,
            $user
        ) {

            $session = RegisterSession::query()
                ->lockForUpdate()
                ->findOrFail($this->session->id);

            if ($session->status !== 'Open') {
                throw new \RuntimeException(
                    'This shift is no longer open.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Calculate expected cash
            |--------------------------------------------------------------------------
            |
            | For now:
            |
            | Opening float
            | + cash sales
            | - refunds
            |
            | Cash movements will be added here when we wire
            | Cash In / Cash Out.
            |--------------------------------------------------------------------------
            */

            $expectedAmount =
                (float) $session->opening_float
                + (float) $session->cash_sales
                - (float) $session->refund_total;

            $difference =
                $closingAmount - $expectedAmount;

            /*
            |--------------------------------------------------------------------------
            | Close session
            |--------------------------------------------------------------------------
            */

            $session->update([

                'closing_amount' =>
                    $closingAmount,

                'expected_amount' =>
                    $expectedAmount,

                'cash_difference' =>
                    $difference,

                'closing_notes' =>
                    $closingNotes,

                'closed_at' =>
                    now(),

                'closed_by' =>
                    $user->id,

                'status' =>
                    'Closed',

            ]);

        });

        $this->loadCurrentSession();

        Notification::make()
            ->title('Shift closed')
            ->body(
                'The shift has been closed successfully.'
            )
            ->success()
            ->send();
    }
}