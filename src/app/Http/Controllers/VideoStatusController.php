<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\Request;

class VideoStatusController extends Controller
{
    public function show(Video $video)
    {
        return response()->json([
            'status' => $video->status,
        ]);
    }
}
