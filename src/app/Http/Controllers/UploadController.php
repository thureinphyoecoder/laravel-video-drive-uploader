<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessVideo;
use Illuminate\Http\Request;
use App\Models\Video;
use Illuminate\Validation\ValidationException;

class UploadController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'video' => [
                'required',
                'file',
                'mimes:mp4,mov,mkv',
                'max:512000',
            ],
        ]);

        $file = $validated['video'];
        $path = $file->store('uploads', 'local');

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
