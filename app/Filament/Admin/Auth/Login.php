<?php

namespace App\Filament\Admin\Auth;

use Filament\Auth\Pages\Login as BaseLogin;

class Login extends BaseLogin
{
    protected string $view = 'filament.auth.admin.login';

    protected function getViewData(): array
    {
        return array_merge(parent::getViewData(), [

            'portal' => 'System Administrator',

            'heading' => 'Motebele Systems POS',

            'description' => 'Secure administration portal for managing the entire Motebele Systems POS platform.',

            'features' => [

                'User & Role Management',

                'Company Administration',

                'Branch Administration',

                'System Configuration',

                'Audit Logs',

                'Performance Monitoring',

            ],

        ]);
    }
}