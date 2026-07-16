<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::get('/pdo-test', function () {
    try {
        $pdo = new PDO(
            'mysql:host=67.205.146.116;port=3306;dbname=MotebelePOS',
            'motebele_pos',
            'Test1234!'
        );

        return 'PDO Connected!';
    } catch (Throwable $e) {
        return $e->getMessage();
    }
});

Route::get('/db-test', function () {
    try {
        DB::connection()->getPdo();

        return 'Connected';
    } catch (\Throwable $e) {
        return response()->json([
            'class' => get_class($e),
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'previous' => $e->getPrevious()?->getMessage(),
            'config' => config('database.connections.mysql'),
        ]);
    }
});

//Route::get('/', function () {
//    return view('welcome');
//});

Route::get('/', function () {
    return view('landing.index');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
