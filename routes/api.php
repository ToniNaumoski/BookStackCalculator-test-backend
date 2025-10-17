<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookCalculationController;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/user', function (Request $request) {
    return $request->user();
    
})->middleware('auth:sanctum');

// Book Calculation Routes
Route::prefix('calculations')->group(function () {
    Route::post('/', [BookCalculationController::class, 'calculate']);
    Route::get('/', [BookCalculationController::class, 'index']);
    Route::get('/{id}', [BookCalculationController::class, 'show']);
    Route::delete('/{id}', [BookCalculationController::class, 'destroy']);
    // Health check endpoint

})->middleware('auth:sanctum');
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'Book Stack Calculator API is running',
        'timestamp' => now()->toISOString()
    ]);
});