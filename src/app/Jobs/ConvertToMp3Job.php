<?php

namespace App\Jobs;

use App\Models\Video;
use App\Services\GoogleDriveService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Audio\Mp3;

class ConvertToMp3Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $videoId;

    public function __construct($videoId)
    {
        $this->videoId = $videoId;
    }

    public function handle()
    {
        try {
            $video = Video::findOrFail($this->videoId);

            if (!$video->google_drive_file_id) {
                throw new \Exception("Google Drive File ID is missing.");
            }

            $driveService = GoogleDriveService::clientForUser($video->user);

            // ၁။ User ရဲ့ Dynamic Folder ID ကို ရယူခြင်း (Video ရှိတဲ့ folder ထဲမှာပဲ MP3 သိမ်းဖို့ပါ)
            $userFolderId = GoogleDriveService::getOrCreateUserFolder($driveService, 'VideoToMp3_Uploads');

            // ၂။ ယာယီသိမ်းမည့်နေရာ ပြင်ဆင်ခြင်း
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0777, true);
            }

            $uniqueId = time();
            $tempVideoPath = $tempDir . '/' . $uniqueId . '.mp4';
            $tempMp3Path = $tempDir . '/' . $uniqueId . '.mp3';

            // ၃။ Drive မှ Video ကို Download ဆွဲယူခြင်း
            $response = $driveService->files->get($video->google_drive_file_id, ['alt' => 'media']);

            // Google API client version ပေါ်မူတည်ပြီး response handle လုပ်ခြင်း
            $content = ($response instanceof \Psr\Http\Message\ResponseInterface)
                ? $response->getBody()->getContents()
                : $response;

            if (empty($content)) {
                throw new \Exception("Failed to download video from Google Drive.");
            }

            file_put_contents($tempVideoPath, $content);

            // ၄။ FFmpeg ဖြင့် MP3 ပြောင်းလဲခြင်း
            $ffmpeg = FFMpeg::create([
                'ffmpeg.binaries'  => '/usr/bin/ffmpeg',
                'ffprobe.binaries' => '/usr/bin/ffprobe',
                'timeout'          => 3600,
            ]);

            $audio = $ffmpeg->open($tempVideoPath);
            $audio->save(new Mp3(), $tempMp3Path);

            // ၅။ MP3 ကို Drive ပေါ်ပြန်တင်ခြင်း (Folder ID ပါ ထည့်ပေးလိုက်ပါသည်)
            $mp3Name = pathinfo($video->original_name, PATHINFO_FILENAME) . '.mp3';
            $mp3DriveId = GoogleDriveService::upload(
                $driveService,
                $tempMp3Path,
                $mp3Name,
                $userFolderId // User Folder ထဲကို တိုက်ရိုက်သိမ်းမည်
            );

            // ၆။ Database Update
            $video->update([
                'mp3_drive_file_id' => $mp3DriveId,
                'status' => 'done'
            ]);

            // ၇။ ယာယီဖိုင်များ ပြန်ဖျက်ခြင်း
            if (file_exists($tempVideoPath)) unlink($tempVideoPath);
            if (file_exists($tempMp3Path)) unlink($tempMp3Path);
        } catch (\Exception $e) {
            Log::error("Conversion Error (ID: {$this->videoId}): " . $e->getMessage());

            // ယာယီဖိုင်များ ကျန်ခဲ့ပါက ဖျက်ပေးခြင်း (Cleanup)
            if (isset($tempVideoPath) && file_exists($tempVideoPath)) unlink($tempVideoPath);
            if (isset($tempMp3Path) && file_exists($tempMp3Path)) unlink($tempMp3Path);

            if (isset($video)) {
                $video->update(['status' => 'failed']);
            }
            throw $e;
        }
    }
}
