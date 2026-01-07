<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\VideoStatusController;

Route::get('/', function () {
    return view('upload');
});

Route::post('/upload', [UploadController::class, 'store']);

Route::get('/videos/{video}/status', [VideoStatusController::class, 'show']);
