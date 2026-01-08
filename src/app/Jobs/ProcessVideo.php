<?php

namespace App\Jobs;

use App\Models\Video;
use App\Services\GoogleDriveService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProcessVideo implements ShouldQueue
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
            // ၁။ Database မှ Video ကို ရှာဖွေခြင်း
            $video = Video::findOrFail($this->videoId);
            $user = $video->user;

            // ၂။ Google Drive Client အဆင်သင့်ဖြစ်အောင် လုပ်ခြင်း
            $driveService = GoogleDriveService::clientForUser($user);

            // ၃။ User ရဲ့ Drive ထဲမှာ သီးသန့် Folder ရှိမရှိစစ်ဆေးပြီး အလိုအလျောက်ဆောက်ခြင်း
            $userFolderId = GoogleDriveService::getOrCreateUserFolder($driveService, 'VideoToMp3_Uploads');

            // ၄။ ဖိုင်လမ်းကြောင်း စစ်ဆေးခြင်း
            $filePath = storage_path('app/' . $video->path);

            if (!file_exists($filePath)) {
                Log::error("File missing at: " . $filePath);
                throw new \Exception("File not found at: " . $filePath);
            }

            // ၅။ Google Drive သို့ Upload တင်ခြင်း
            // အပေါ်ကရလာတဲ့ $userFolderId ကို အောက်က upload function ထဲမှာ သေချာထည့်ပေးထားပါတယ်
            $fileId = GoogleDriveService::upload(
                $driveService,
                $filePath,
                $video->original_name,
                $userFolderId // ဒီနေရာက parameter က Service ရဲ့ ?string $folderId နဲ့ ကိုက်ညီရပါမယ်
            );

            // ၆။ အောင်မြင်လျှင် Database Status ပြောင်းခြင်း
            $video->update([
                'google_drive_file_id' => $fileId,
                'status' => 'completed'
            ]);

            // ၇။ Server ပေါ်က ယာယီဗီဒီယိုဖိုင်ကို ဖျက်ခြင်း (Disk Space ချွေတာရန်)
            if (Storage::disk('local')->exists($video->path)) {
                Storage::disk('local')->delete($video->path);
            }
        } catch (\Exception $e) {
            Log::error("Drive Upload Job Failed (ID: {$this->videoId}): " . $e->getMessage());
            if (isset($video)) {
                $video->update(['status' => 'failed']);
            }
            throw $e;
        }
    }
}
