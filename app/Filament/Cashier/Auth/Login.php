<?php

namespace App\Filament\Cashier\Auth;

use Filament\Auth\Pages\Login as BaseLogin;

class Login extends BaseLogin
{
    protected string $view = 'filament.auth.cashier.login';

    protected function getViewData(): array
    {
        return array_merge(parent::getViewData(), [

            'portal' => 'Cashier Portal',

            'heading' => 'Motebele Systems POS',

            'description' => 'Fast, secure and compliant retail point of sale.',

            'features' => [

                'Sales Processing',

                'Barcode Scanning',

                'Receipt Printing',

                'Cash Register',

                'Shift Closing',

                'Lekuka E-Invoicing',

            ],

        ]);
    }
}