<?php

namespace App\Services;

use App\Models\User;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Illuminate\Support\Facades\Log;

class GoogleDriveService
{
    public static function clientForUser(User $user): Drive
    {
        $client = new Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setAccessType('offline');

        $client->setApprovalPrompt('force');
        $client->setScopes([Drive::DRIVE_FILE]);

        $client->setAccessToken([
            'access_token'  => $user->google_access_token,
            'refresh_token' => $user->google_refresh_token,
            'expires_in'    => 3600,

            'created'       => $user->updated_at->timestamp,
        ]);

        if ($client->isAccessTokenExpired()) {
            if ($user->google_refresh_token) {
                try {
                    $token = $client->fetchAccessTokenWithRefreshToken($user->google_refresh_token);

                    if (!isset($token['error'])) {

                        $user->update([
                            'google_access_token' => $token['access_token'],

                            'google_token_expires_at' => now()->addSeconds($token['expires_in']),
                        ]);
                    } else {
                        Log::error("Google Token Refresh Error: " . $token['error']);
                    }
                } catch (\Exception $e) {
                    Log::error("Google Auth Exception: " . $e->getMessage());
                }
            }
        }

        return new Drive($client);
    }

    public static function upload(Drive $drive, string $path, string $name): string
    {

        $folderId = config('services.google.folder_id');

        $fileMetadata = new DriveFile([
            'name' => $name,
            'parents' => $folderId ? [$folderId] : [],
        ]);


        $content = fopen($path, 'r');

        try {

            $uploaded = $drive->files->create(
                $fileMetadata,
                [
                    'data' => $content,
                    'mimeType' => mime_content_type($path),

                    'uploadType' => 'resumable',
                    'fields' => 'id',
                ]
            );

            return $uploaded->id;
        } catch (\Exception $e) {
            Log::error("Google Drive Upload Failed: " . $e->getMessage());
            throw $e;
        } finally {

            if (is_resource($content)) {
                fclose($content);
            }
        }
    }
}
