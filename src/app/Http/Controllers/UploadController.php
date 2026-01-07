<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessVideo;
use Illuminate\Http\Request;
use App\Models\Video;


// use Illuminate\Support\Facades\Storage;


class UploadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'video' => 'required|file|max:512000', // 500 MB
        ]);

        $file = $request->file('video');

        $path = $file->store('uploads');

        $video = Video::create([
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'size' => $file->getSize(),
            'status' => 'uploaded',
        ]);

        ProcessVideo::dispatch($video);

        return response()->json([
            'id' => $video->id,
            'status' => $video->status,
        ]);
    }
}
