<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [

            'dashboard',

            'users',
            'roles',
            'permissions',

            'companies',
            'branches',
            'terminals',

            'customers',
            'customer_groups',

            'suppliers',

            'categories',
            'brands',
            'units',

            'products',
            'inventory',
            'stock_adjustments',

            'purchases',
            'purchase_orders',

            'sales',
            'quotations',
            'returns',

            'cash_registers',
            'payments',

            'expenses',

            'reports',

            'settings',

            'audit_logs',

            'e_invoicing',
        ];

        $actions = [

            'view',

            'view_any',

            'create',

            'update',

            'delete',

            'restore',

            'force_delete',

            'export',

            'print',

            'approve',

            'cancel',
        ];

        foreach ($modules as $module) {

            foreach ($actions as $action) {

                Permission::firstOrCreate([
                    'name' => "{$action}_{$module}",
                    'guard_name' => 'web',
                ]);

            }
        }
    }
}