<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\api;
use Barryvdh\Debugbar\Facades\Debugbar;


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

Route::group(['middleware' => ['json']], function() {
    Route::get('/', [api::class, 'home']);
    Route::post('/contact', [api::class, 'contact']);
    Route::get('/properties', [api::class, 'properties']);
    Route::get('/properties/{id}', [api::class, 'property']);
    Route::get('/categories', [api::class, 'categories']);
    Route::get('/users', [api::class, 'users']);
    Route::get('/users/{id}', [api::class, 'user']);
    Route::post('/login', [api::class, 'login']);
    Route::post('/register', [api::class, 'register']);
    // Route::get('/verification.verify', [api::class, 'verify'])->name('verification.verify');
    Route::get('/verify-email/{id}/{hash}', [api::class, 'verify'])->name('verification.verify');
    Route::post('/forgot', [api::class, 'forgot']);
    Route::post('/reset', [api::class, 'reset'])->name('password.reset');
    
    Route::group(['middleware' => ['auth:sanctum']], function() {
        Route::post('/logout', [api::class, 'logout']);
        Route::post('/email/verification-notification', [api::class, 'sendVerificationEmail']);
        Route::get('/profile', [api::class, 'profile']);
        Route::get('/profile/properties', [api::class, 'profileProperties']);
        Route::delete('/profile/properties/{id}', [api::class, 'deleteProperty']);
        Route::get('/profile/properties/trashed', [api::class, 'trashedProperty']);
        Route::post('/profile/properties', [api::class, 'addProperty']);
    });
});


// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

