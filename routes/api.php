<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MembershipController;

// Rutas de auth
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::post('/forgot-password', [AuthController::class, 'sendEmailResetPassword']);
    Route::get('/validate-code-password/{code}', [AuthController::class, 'validateCodePassword']);
});

Route::get('/reset-password/{token}', function (string $token) {
    // return view('auth.reset-password', ['token' => $token]);
})->middleware('guest')->name('password.reset');

// Rutas para los miembros
Route::middleware('auth:sanctum')->prefix('members')->group(function () {
    Route::get('/', [MemberController::class, 'index']);
    Route::get('/{id}', [MemberController::class, 'show']);
    Route::post('/create', [MemberController::class, 'store']);
    Route::put('/{id}', [MemberController::class, 'update']);
    Route::delete('/{id}', [MemberController::class, 'destroy']);
});

// Rutas para las membresias
Route::middleware('auth:sanctum')->prefix('memberships')->group(function () {
    Route::get('/', [MembershipController::class, 'index']);
    Route::post('/create', [MembershipController::class, 'store']);
    Route::put('/{id}', [MembershipController::class, 'update']);
    Route::get('/show/{id}', [MembershipController::class, 'show']);
    Route::get('/activityInformationFromHome', [MembershipController::class, 'activityInformationFromHome']);
});
