<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


// use Illuminate\Support\Facades\Storage;


class UploadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'video' => 'required|file|max:512000', // 500 MB
        ]);

        $path = $request->file('video')->store('uploads');

        return response()->json([
            'message' => 'Uploaded',
            'path' => $path,
        ]);
    }
}
