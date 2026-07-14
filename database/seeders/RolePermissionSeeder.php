<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin
        Role::findByName('Super Admin')
            ->syncPermissions(Permission::all());

        // Company Admin
        Role::findByName('Company Admin')
            ->syncPermissions(
                Permission::whereNotIn('name', [
                    'delete_companies',
                    'update_companies',
                ])->get()
            );

        // Manager
        Role::findByName('Manager')
            ->syncPermissions([
                'view_dashboard',

                'view_sales',
                'view_any_sales',

                'create_sales',

                'view_products',
                'view_inventory',

                'view_reports',
                'export_reports',
            ]);

        // Cashier
        Role::findByName('Cashier')
            ->syncPermissions([

                'view_dashboard',

                'view_products',

                'view_inventory',

                'create_sales',

                'view_sales',

                'print_sales',

                'view_payments',
            ]);

        // Inventory Clerk
        Role::findByName('Inventory Clerk')
            ->syncPermissions([
                'view_products',

                'create_products',

                'update_products',

                'view_inventory',

                'update_inventory',

                'view_stock_adjustments',

                'create_stock_adjustments',
            ]);

        // Accountant
        Role::findByName('Accountant')
            ->syncPermissions([
                'view_reports',

                'export_reports',

                'view_payments',

                'view_expenses',

                'create_expenses',

                'update_expenses',
            ]);
    }
}