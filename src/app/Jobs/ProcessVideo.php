<?php

namespace App\Jobs;

use App\Models\Video;
use App\Services\GoogleDriveService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessVideo implements ShouldQueue
{
    use Queueable;

    public function __construct(public Video $video) {}

    public function handle(GoogleDriveService $drive): void
    {
        $this->video->update(['status' => 'processing']);

        $localPath = storage_path('app/' . $this->video->path);

        if (!file_exists($localPath)) {
            Log::error('Local video missing', ['video_id' => $this->video->id]);
            $this->video->update(['status' => 'failed']);
            return;
        }

        try {
            $result = $drive->upload(
                $localPath,
                $this->video->original_name
            );

            $this->video->update([
                'status' => 'done',
                'drive_file_id' => $result['file_id'],
                'download_url' => $result['download_url'],
            ]);

            unlink($localPath);
        } catch (\Throwable $e) {
            Log::error('Video processing failed', [
                'video_id' => $this->video->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->video->update(['status' => 'failed']);
        }
    }
}
