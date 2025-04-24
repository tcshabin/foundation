<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContactApi\SignUpController;
use App\Http\Controllers\ContactApi\LoginController;
use App\Http\Controllers\ContactApi\UserController;
use App\Http\Controllers\ContactApi\ContactController;
use App\Http\Middleware\JwtMiddleware;
use App\Http\Middleware\ValidateAppKey;

const CONTACT_ID = '{contactId}';

// ───── Public Routes with App Key ─────
Route::middleware([ValidateAppKey::class])->group(function () {
    Route::post('/signup/email', [SignUpController::class, 'sendVerificationEmail']);
    Route::post('/signup', [SignUpController::class, 'signup']);
    Route::post('login', [LoginController::class, 'login']);
    Route::put('login', [LoginController::class, 'refreshAccessToken']);
    Route::post('/forgot-password', [LoginController::class, 'forgotPassword']);
});

// ───── Public Routes without Middleware ─────
Route::get('/signup/email/{token}', [SignUpController::class, 'verifyEmail']);
Route::get('/app/signup/email/{token}', [SignUpController::class, 'verifyEmail']);
Route::post('/reset-password', [LoginController::class, 'resetPassword']);
Route::post('/app/reset-password', [LoginController::class, 'resetPassword']);

// ───── Protected Routes (JWT Required) ─────
Route::middleware([JwtMiddleware::class])->group(function () {
    Route::get('users', [UserController::class, 'userDetails']);

    Route::prefix('contacts')->group(function () {
        Route::get('/', [ContactController::class, 'listContact']);
        Route::post('/', [ContactController::class, 'addContact']);
        Route::get(CONTACT_ID, [ContactController::class, 'viewContact']);
        Route::post(CONTACT_ID, [ContactController::class, 'updateContact']);
        Route::delete(CONTACT_ID, [ContactController::class, 'deleteContact']);
    });
});

