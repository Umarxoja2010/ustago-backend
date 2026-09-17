<?php

use App\Http\Controllers\Api\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Api\Admin\MasterController as AdminMasterController;
use App\Http\Controllers\Api\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Api\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Api\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Api\Admin\ServiceController as AdminServiceController;
use App\Http\Controllers\Api\Admin\SpecialOfferController as AdminSpecialOfferController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\MasterController;
use App\Http\Controllers\Api\MasterProfileController;
use App\Http\Controllers\Api\MasterServiceController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\SpecialOfferController;
use App\Http\Controllers\Api\UserPhoneController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\WorkingHourController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', fn () => response()->json(['success' => true, 'message' => 'pong']));

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

// Public master browsing — no auth required (customers browse before signup too).
Route::get('/offers', [SpecialOfferController::class, 'index']);
Route::get('/services', [ServiceController::class, 'index']);
Route::get('/masters', [MasterController::class, 'index']);
Route::get('/masters/{master}', [MasterController::class, 'show']);
Route::get('/masters/{master}/reviews', [ReviewController::class, 'forMaster']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::patch('/user', [AuthController::class, 'updateProfile']);
    Route::match(['patch', 'put'], '/user/profile', [AuthController::class, 'updateProfile']);
    Route::post('/user/avatar', [AuthController::class, 'uploadAvatar']);

    Route::get('/user/phones', [UserPhoneController::class, 'index']);
    Route::post('/user/phones', [UserPhoneController::class, 'store']);
    Route::delete('/user/phones/{phone}', [UserPhoneController::class, 'destroy']);
    Route::post('/user/phones/{phone}/restore', [UserPhoneController::class, 'restore']);
    Route::post('/user/phones/{phone}/primary', [UserPhoneController::class, 'makePrimary']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead']);

    // --- Customer-only (Phase 8) ---
    Route::middleware('role:customer')->group(function () {
        Route::apiResource('vehicles', VehicleController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::get('/favorites', [FavoriteController::class, 'index']);
        Route::post('/masters/{master}/favorite', [FavoriteController::class, 'store']);
        Route::delete('/masters/{master}/favorite', [FavoriteController::class, 'destroy']);
    });

    // --- Mechanic-only (Phase 9) ---
    Route::middleware('role:mechanic')->prefix('master')->group(function () {
        Route::get('/profile', [MasterProfileController::class, 'show']);
        Route::patch('/profile', [MasterProfileController::class, 'update']);
        Route::post('/photo', [MasterProfileController::class, 'uploadPhoto']);

        Route::get('/services', [MasterServiceController::class, 'index']);
        Route::post('/services', [MasterServiceController::class, 'store']);
        Route::patch('/services/{masterService}', [MasterServiceController::class, 'update']);
        Route::delete('/services/{masterService}', [MasterServiceController::class, 'destroy']);

        Route::get('/working-hours', [WorkingHourController::class, 'index']);
        Route::put('/working-hours', [WorkingHourController::class, 'update']);

        Route::get('/reviews', [ReviewController::class, 'mine']);

    });

    // --- Bookings (Phase 10) — shared endpoints, scoped by role inside the controller ---
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/{booking}', [BookingController::class, 'show']);
    Route::middleware('role:customer')->post('/bookings', [BookingController::class, 'store']);
    Route::patch('/bookings/{booking}/status', [BookingController::class, 'updateStatus']);
    Route::middleware('role:customer')->patch('/bookings/{booking}/reschedule', [BookingController::class, 'reschedule']);

    // --- Reviews (Phase 11) ---
    Route::middleware('role:customer')->post('/reviews', [ReviewController::class, 'store']);

    // --- Admin (Phase 12) ---
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::get('/users/{user}', [AdminUserController::class, 'show']);
        Route::patch('/users/{user}/status', [AdminUserController::class, 'updateStatus']);

        Route::get('/masters', [AdminMasterController::class, 'index']);
        Route::get('/masters/{master}', [AdminMasterController::class, 'show']);
        Route::patch('/masters/{master}/verification', [AdminMasterController::class, 'updateVerification']);

        Route::get('/services', [AdminServiceController::class, 'index']);
        Route::post('/services', [AdminServiceController::class, 'store']);
        Route::patch('/services/{service}', [AdminServiceController::class, 'update']);
        Route::delete('/services/{service}', [AdminServiceController::class, 'destroy']);

        Route::apiResource('offers', AdminSpecialOfferController::class);
        Route::patch('offers/{offer}/toggle', [AdminSpecialOfferController::class, 'toggle']);

        Route::get('/bookings', [AdminBookingController::class, 'index']);
        Route::get('/bookings/{booking}', [AdminBookingController::class, 'show']);
        Route::patch('/bookings/{booking}/status', [AdminBookingController::class, 'updateStatus']);

        Route::get('/reviews', [AdminReviewController::class, 'index']);
        Route::patch('/reviews/{review}/hide', [AdminReviewController::class, 'toggleHide']);

        Route::get('/notifications', [AdminNotificationController::class, 'index']);
        Route::post('/notifications/broadcast', [AdminNotificationController::class, 'store']);

        Route::get('/reports/overview', [AdminReportController::class, 'overview']);
        Route::get('/reports/signups', [AdminReportController::class, 'signups']);
        Route::get('/reports/top-services', [AdminReportController::class, 'topServices']);
    });
});
