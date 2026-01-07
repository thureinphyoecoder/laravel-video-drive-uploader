<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Video;
use App\Jobs\ProcessVideo;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function store(Request $request)
    {

        $request->validate([
            'video' => 'required|file|mimes:mp4,mov,mkv,avi,wmv|max:512000',
        ]);

        if ($request->hasFile('video')) {
            $file = $request->file('video');


            $fileName = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('uploads', $fileName);


            $video = Video::create([
                'user_id' => $request->user()->id,
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'status' => 'queued',
            ]);

            ProcessVideo::dispatch($video->id);

            return response()->json([
                'id' => $video->id,
                'status' => 'queued',
                'message' => 'Video upload successful. Processing started.'
            ]);
        }

        return response()->json(['error' => 'No video file provided.'], 400);
    }

    public function status($id)
    {
        $video = Video::findOrFail($id);


        return response()->json([
            'status' => $video->status,
            'download_url' => $video->status === 'done' ? $video->mp3_download_url : null,
        ]);
    }
}
