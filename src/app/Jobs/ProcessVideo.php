<?php

namespace App\Jobs;

use App\Models\Video;
use App\Services\GoogleDriveService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $videoId) {}

    public function handle()
    {
        $video = Video::findOrFail($this->videoId);
        $user = $video->user;


        $localPath = storage_path('app/' . $video->path);

        if (!file_exists($localPath)) {
            Log::error("Original video file not found at: " . $localPath);
            $video->update(['status' => 'failed']);
            return;
        }

        try {
            $video->update(['status' => 'processing']);


            $drive = GoogleDriveService::clientForUser($user);


            $videoDriveId = GoogleDriveService::upload(
                $drive,
                $localPath,
                $video->original_name
            );


            $mp3Directory = storage_path("app/uploads");
            $mp3Path = $mp3Directory . "/{$video->id}.mp3";

            if (!file_exists($mp3Directory)) {
                mkdir($mp3Directory, 0755, true);
            }


            $sourceFile = escapeshellarg($localPath);
            $outputFile = escapeshellarg($mp3Path);


            $command = "ffmpeg -y -i {$sourceFile} -vn -acodec libmp3lame -q:a 2 {$outputFile} 2>&1";
            exec($command, $output, $resultCode);

            if ($resultCode !== 0) {
                Log::error("FFmpeg Conversion failed: " . implode("\n", $output));
                $video->update(['status' => 'failed']);
                return;
            }


            $mp3FileName = pathinfo($video->original_name, PATHINFO_FILENAME) . '.mp3';
            $mp3DriveId = GoogleDriveService::upload(
                $drive,
                $mp3Path,
                $mp3FileName
            );


            $video->update([
                'status' => 'done',
                'drive_file_id' => $videoDriveId,
                'mp3_drive_file_id' => $mp3DriveId,
            ]);


            if (file_exists($mp3Path)) {
                unlink($mp3Path);
            }
        } catch (\Exception $e) {
            Log::error("ProcessVideo Job failed: " . $e->getMessage());
            $video->update(['status' => 'failed']);
        }
    }
}
