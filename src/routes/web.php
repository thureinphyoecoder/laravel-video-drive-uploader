<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UploadController;

Route::get('/', function () {
    return view('upload');
});

Route::post('/upload', [UploadController::class, 'store']);
