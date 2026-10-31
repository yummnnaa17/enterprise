<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;

Route::post('/orders', [OrderController::class, 'store']);

Route::get('/orders/{id}', [OrderController::class, 'show'])
    ->whereNumber('id');

Route::get('/orders/user/{userId}', [OrderController::class, 'userOrders'])
    ->whereNumber('userId');

Route::patch('/orders/{id}/pay', [OrderController::class, 'pay'])
    ->whereNumber('id');