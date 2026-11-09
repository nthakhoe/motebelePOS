<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Models\Sale;
use App\Services\ReceiptPrinter;
use App\Http\Controllers\Cashier\SaleReceiptController;
use App\Models\RestaurantOrder;
use Illuminate\Support\Facades\Auth;

Route::middleware(['auth'])
    ->get('/restaurant/orders/{order}/preliminary-bill', function (int $order) {

        $user = Auth::user();

        $query = RestaurantOrder::query()
            ->where('id', $order)
            ->where('company_id', $user->company_id);

        if ($user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }

        $restaurantOrder = $query
            ->with([
                'company',
                'branch',
                'table.area',
                'waiter',
                'items',
            ])
            ->first();

        abort_if(
            !$restaurantOrder,
            404,
            'Restaurant order not found for your company or branch.'
        );

        abort_unless(
            $restaurantOrder->status === RestaurantOrder::STATUS_BILL_REQUESTED,
            403,
            'A preliminary bill can only be printed after requesting a bill.'
        );

        return view('restaurant.print.preliminary-bill', [
            'order' => $restaurantOrder,
            'company' => $restaurantOrder->company,
            'branch' => $restaurantOrder->branch,
        ]);

    })->name('restaurant.orders.preliminary-bill');
/*Route::middleware(['auth'])
    ->get('/restaurant/orders/{order}/preliminary-bill', function (int $order) {

        $user = Auth::user();

        $restaurantOrder = RestaurantOrder::query()
            ->where('id', $order)
            ->where('company_id', $user->company_id)
            ->where('branch_id', $user->branch_id)
            ->where('status', RestaurantOrder::STATUS_BILL_REQUESTED)
            ->with([
                'company',
                'branch',
                'table.area',
                'waiter',
                'items',
            ])
            ->firstOrFail();

        return view(
            'restaurant.print.preliminary-bill',
            [
                'order' => $restaurantOrder,
                'company' => $restaurantOrder->company,
                'branch' => $restaurantOrder->branch,
            ]
        );

    })->name('restaurant.orders.preliminary-bill');*/

Route::middleware(['auth'])->group(function () {

    Route::get(
        '/cashier/sales/{sale}/receipt',
        SaleReceiptController::class
    )->name('cashier.sales.receipt');

});

Route::get('/test-receipt/{sale}', function (Sale $sale, ReceiptPrinter $printer) {

    $receipt = $printer->build($sale);

    return view('receipts.thermal', compact('receipt'));

});

Route::get('/', function () {
    return view('welcome');
});

//Route::get('/', function () {
//    return view('landing.index');
//});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
