<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Video;
use App\Jobs\ProcessVideo;
use App\Jobs\ConvertToMp3Job;
use Illuminate\Support\Facades\Log;


class UploadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'video' => 'required|file|mimes:mp4,mov,mkv,avi,wmv|max:512000',
        ]);

        if ($request->hasFile('video')) {
            try {
                $file = $request->file('video');
                $fileName = time() . '_' . $file->getClientOriginalName();

                // ယာယီသိမ်းခြင်း (Drive တင်ပြီးရင် ပြန်ဖျက်ပါမယ်)
                $path = $file->storeAs('uploads', $fileName, 'local');

                $video = Video::create([
                    'user_id'       => $request->user() ? $request->user()->id : 1,
                    'original_name' => $file->getClientOriginalName(),
                    'path'          => $path,
                    'status'        => 'uploading_to_drive',
                ]);

                // Google Drive တင်ရန် Job လွှတ်ခြင်း
                ProcessVideo::dispatch($video->id);

                return response()->json(['id' => $video->id, 'status' => 'uploading_to_drive']);
            } catch (\Exception $e) {
                Log::error("Upload failed: " . $e->getMessage());
                return response()->json(['error' => 'Server error.'], 500);
            }
        }
    }

    public function convertToMp3($id)
    {
        $video = Video::findOrFail($id);
        if ($video->status !== 'completed') {
            return response()->json(['error' => 'Upload not finished yet.'], 400);
        }

        $video->update(['status' => 'converting']);
        ConvertToMp3Job::dispatch($video->id);

        return response()->json(['success' => true]);
    }

    public function status($id)
    {
        $video = Video::findOrFail($id);
        return response()->json([
            'status' => $video->status,
            'download_url' => $video->status === 'done' ? $video->mp3_drive_file_id : null,
            'can_convert' => $video->status === 'completed'
        ]);
    }
}
