<?php

namespace App\Services\Procurement;

use App\Models\Supplier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SupplierService
{
    /**
     * Create a new supplier.
     */
    public function create(array $data): Supplier
    {
        return DB::transaction(function () use ($data) {

            if (empty($data['supplier_code'])) {
                $data['supplier_code'] = $this->generateSupplierCode();
            }

            return Supplier::create($data);

        });
    }

    /**
     * Update an existing supplier.
     */
    public function update(Supplier $supplier, array $data): Supplier
    {
        return DB::transaction(function () use ($supplier, $data) {

            $supplier->update($data);

            return $supplier->fresh();

        });
    }

    /**
     * Activate supplier.
     */
    public function activate(Supplier $supplier): void
    {
        $supplier->update([
            'is_active' => true,
        ]);
    }

    /**
     * Deactivate supplier.
     */
    public function deactivate(Supplier $supplier): void
    {
        $supplier->update([
            'is_active' => false,
        ]);
    }

    /**
     * Generate the next supplier code.
     */
    public function generateSupplierCode(): string
    {
        $lastSupplier = Supplier::orderByDesc('id')->first();

        if (! $lastSupplier) {
            return 'SUP000001';
        }

        $number = (int) Str::after($lastSupplier->supplier_code, 'SUP');

        return 'SUP' . str_pad($number + 1, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate supplier outstanding balance.
     */
    public function outstandingBalance(Supplier $supplier): float
    {
        return (float) $supplier->current_balance;
    }

    /**
     * Validate supplier before procurement.
     */
    public function validateSupplier(Supplier $supplier): void
    {
        if (! $supplier->is_active) {
            throw new InvalidArgumentException(
                'This supplier is inactive.'
            );
        }
    }
}