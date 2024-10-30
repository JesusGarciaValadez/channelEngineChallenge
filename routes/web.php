<?php

use App\Orders\Http\Controllers\OrderController;
use App\OrderLines\Http\Controllers\OrderLineController;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;

Route::get('/', static function (): Factory|\Illuminate\Contracts\View\View|Application {
    return view('welcome');
});

Route::prefix('order-lines')->group(function (): void {
    Route::get('/', [OrderLineController::class, 'index'])->name('order_lines.index');
    Route::put('/{orderLine:merchant_product_no}/update-stock', [OrderLineController::class, 'update'])->name('order_lines.update');
});
