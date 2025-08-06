<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RestaurantController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\ReviewController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// Test route
Route::get('/test-auth', function () {
    return response()->json(['message' => 'Authentication working']);
})->middleware('auth.api:api');

// Protected routes
Route::middleware('auth.api:api')->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('2fa/enable', [AuthController::class, 'enable2FA']);
        Route::post('2fa/disable', [AuthController::class, 'disable2FA']);
        Route::post('2fa/verify', [AuthController::class, 'verify2FA']);
    });

    // Restaurant routes
    Route::prefix('restaurants')->group(function () {
        Route::get('/', [RestaurantController::class, 'index']);
        Route::get('/search', [RestaurantController::class, 'search']);
        Route::get('/nearby', [RestaurantController::class, 'nearby']);
        Route::get('/cuisine', [RestaurantController::class, 'byCuisine']);
        Route::get('/rating', [RestaurantController::class, 'byRating']);
        Route::get('/price-level', [RestaurantController::class, 'byPriceLevel']);
        Route::get('/{id}', [RestaurantController::class, 'show']);
    });

    // Menu routes
    Route::prefix('restaurants/{restaurantId}/menus')->group(function () {
        Route::get('/', [MenuController::class, 'index']);
        Route::get('/category', [MenuController::class, 'byCategory']);
        Route::get('/dietary', [MenuController::class, 'byDietary']);
        Route::get('/price-range', [MenuController::class, 'byPriceRange']);
        Route::get('/available', [MenuController::class, 'available']);
        Route::get('/{menuId}', [MenuController::class, 'show']);
    });

    // Review routes
    Route::prefix('restaurants/{restaurantId}/reviews')->group(function () {
        Route::get('/', [ReviewController::class, 'index']);
        Route::get('/rating', [ReviewController::class, 'byRating']);
        Route::get('/min-rating', [ReviewController::class, 'byMinRating']);
        Route::get('/google', [ReviewController::class, 'googleReviews']);
        Route::get('/{reviewId}', [ReviewController::class, 'show']);
    });
});