<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Models\Sale;
use App\Services\ReceiptPrinter;
use App\Http\Controllers\Cashier\SaleReceiptController;

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
