<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth; // Laravel Auth Facade
use App\Http\Controllers\UploadController;
use App\Http\Controllers\Auth\GoogleAuthController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/login', fn() => view('auth.login'))->middleware('guest')->name('login');

Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->middleware('guest')->name('google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->middleware('guest')->name('google.callback');

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
    Route::get('/videos/{id}/status', [UploadController::class, 'status']);

    Route::post('/videos/{id}/convert', [UploadController::class, 'convertToMp3'])->name('videos.convert');

    // Logout Route (GETရော POSTရော အလုပ်လုပ်အောင် ဒီလိုလေး ခဏထားပါ)
    Route::match(['get', 'post'], '/logout', function (\Illuminate\Http\Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    })->name('logout');
});

require __DIR__ . '/auth.php';
