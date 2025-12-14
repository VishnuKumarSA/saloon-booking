<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingController;

Route::get('/', [BookingController::class, 'index']);
Route::get('/slots', [BookingController::class, 'slots']);
Route::post('/book', [BookingController::class, 'store']);
Route::get('/available-beauticians', [BookingController::class, 'availableBeauticians']);


