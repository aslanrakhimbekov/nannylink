<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\DocumentController;
use App\Http\Controllers\Api\v1\NannySlotController;
use App\Http\Controllers\Api\v1\BookingController;
use App\Http\Controllers\Api\v1\ReviewController;
use App\Http\Controllers\Api\v1\EscrowController;
use App\Http\Controllers\Api\v1\MessageController;

Route::prefix('v1')->group(function () {
    // Auth routes
    Route::post('/auth/request-otp', [AuthController::class, 'requestOtp']);
    Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/auth/telegram-callback', [AuthController::class, 'telegramCallback']);

    // Sanctum guarded routes
    Route::middleware('auth:sanctum')->group(function () {
        // Profile endpoint
        Route::match(['post', 'put', 'patch'], '/profile', [AuthController::class, 'updateProfile']);

        // Telegram link route
        Route::post('/auth/telegram-link', [AuthController::class, 'telegramLink']);

        // Nanny slots endpoints
        Route::get('/nanny/slots', [NannySlotController::class, 'index']);
        Route::post('/nanny/slots', [NannySlotController::class, 'store']);
        Route::delete('/nanny/slots/{id}', [NannySlotController::class, 'destroy']);
        Route::get('/nannies/{id}/slots', [NannySlotController::class, 'nannySlots']);

        // Parent search for nearby nannies
        Route::get('/nannies/nearby', [BookingController::class, 'nearbyNannies']);

        // Bookings endpoints
        Route::get('/bookings', [BookingController::class, 'index']);
        Route::post('/bookings', [BookingController::class, 'store']);
        Route::get('/bookings/{id}', [BookingController::class, 'show']);
        Route::post('/bookings/{id}/confirm', [BookingController::class, 'confirm']);
        Route::post('/bookings/{id}/reject', [BookingController::class, 'reject']);
        Route::post('/bookings/{id}/cancel', [BookingController::class, 'cancel']);
        Route::post('/nanny/balance/deposit', [BookingController::class, 'deposit']);

        // Escrow endpoints
        Route::post('/bookings/{id}/pay', [EscrowController::class, 'pay']);
        Route::post('/bookings/{id}/complete', [EscrowController::class, 'complete']);

        // Chat messages endpoints
        Route::get('/bookings/{id}/messages', [MessageController::class, 'index']);
        Route::post('/bookings/{id}/messages', [MessageController::class, 'store']);

        // Nanny documents upload
        Route::post('/nanny/documents', [DocumentController::class, 'store']);
        Route::delete('/nanny/documents/{id}', [DocumentController::class, 'destroy']);

        // Reviews
        Route::post('/reviews', [ReviewController::class, 'store']);
        Route::get('/nannies/{id}/reviews', [ReviewController::class, 'nannyReviews']);

        // Admin user deletion
        Route::delete('/admin/users/{id}', [AuthController::class, 'deleteUser']);
    });
});
