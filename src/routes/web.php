<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\VideoStatusController;
use App\Http\Controllers\Auth\GoogleAuthController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// Login page (Google only)
Route::get('/login', fn() => view('auth.login'))
    ->middleware('guest')
    ->name('login');

// Google OAuth
Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])
    ->middleware('guest')
    ->name('google.redirect');

Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
    ->middleware('guest')
    ->name('google.callback');


/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    Route::get('/', function () {
        return view('upload');
    })->name('home');

    Route::post('/upload', [UploadController::class, 'store']);
    Route::get('/videos/{video}/status', [VideoStatusController::class, 'show']);
});



/*
|--------------------------------------------------------------------------
| Auth Routes (Logout only)
|--------------------------------------------------------------------------
*/
require __DIR__ . '/auth.php';
