<?php

namespace App\Filament\Company\Auth;

use Filament\Auth\Pages\Login as BaseLogin;

class Login extends BaseLogin
{
    protected string $view = 'filament.auth.company.login';

    protected function getViewData(): array
    {
        return array_merge(parent::getViewData(), [

            'portal' => 'Company Portal',

            'heading' => 'Motebele Systems POS',

            'description' => 'Manage your business operations, inventory, employees, reports and settings from a centralized dashboard.',

            'features' => [

                'Business Dashboard',

                'Inventory Management',

                'Sales Analytics',

                'Customer Management',

                'Employee Management',

                'Business Reports',

            ],

        ]);
    }
}