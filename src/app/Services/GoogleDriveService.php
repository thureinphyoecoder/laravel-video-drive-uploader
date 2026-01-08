<?php

namespace App\Services;

use App\Models\User;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Permission;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;

class GoogleDriveService
{
    public static function clientForUser(User $user): Drive
    {
        $client = new Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setAccessType('offline');
        $client->setScopes([Drive::DRIVE_FILE]);

        try {
            $accessToken = Crypt::decryptString($user->google_access_token);
            $refreshToken = $user->google_refresh_token ? Crypt::decryptString($user->google_refresh_token) : null;

            $client->setAccessToken([
                'access_token'  => $accessToken,
                'refresh_token' => $refreshToken,
                'expires_in'    => 3600,
                'created'       => $user->updated_at->timestamp,
            ]);

            if ($client->isAccessTokenExpired()) {
                if ($refreshToken) {
                    $newToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);
                    if (!isset($newToken['error'])) {
                        $user->update([
                            'google_access_token' => Crypt::encryptString($newToken['access_token']),
                            'google_token_expires_at' => now()->addSeconds($newToken['expires_in']),
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Google Auth Error: " . $e->getMessage());
            throw $e;
        }

        return new Drive($client);
    }

    public static function upload(Drive $drive, string $path, string $name, ?string $folderId = null): string
    {
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $mimeType = ($extension === 'mp3') ? 'audio/mpeg' : 'video/mp4';


        $parents = $folderId ? [$folderId] : (config('services.google.folder_id') ? [config('services.google.folder_id')] : []);

        $fileMetadata = new DriveFile([
            'name' => $name,
            'parents' => $parents,
        ]);

        $content = file_get_contents($path);

        try {
            $uploaded = $drive->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => $mimeType,
                'uploadType' => 'multipart',
                'fields' => 'id',
            ]);

            if (!$uploaded->id) {
                throw new \Exception("Upload succeeded but ID was not returned.");
            }

            try {
                $permission = new Permission([
                    'type' => 'anyone',
                    'role' => 'reader',
                ]);
                $drive->permissions->create($uploaded->id, $permission);
            } catch (\Exception $pe) {
                Log::warning("Could not set file permission: " . $pe->getMessage());
            }

            return $uploaded->id;
        } catch (\Exception $e) {
            Log::error("Drive Upload Failed: " . $e->getMessage());
            throw $e;
        }
    }

    public static function getOrCreateUserFolder(Drive $driveService, $folderName = 'VideoToMp3_Uploads')
    {
        $query = "mimeType='application/vnd.google-apps.folder' and name='$folderName' and trashed=false";
        $results = $driveService->files->listFiles(['q' => $query]);

        if (count($results->getFiles()) > 0) {
            return $results->getFiles()[0]->id;
        }

        $folderMetadata = new DriveFile([
            'name' => $folderName,
            'mimeType' => 'application/vnd.google-apps.folder'
        ]);

        $folder = $driveService->files->create($folderMetadata, ['fields' => 'id']);
        $folderId = $folder->id;

        $permission = new Permission([
            'type' => 'anyone',
            'role' => 'reader',
        ]);
        $driveService->permissions->create($folderId, $permission);

        return $folderId;
    }
}
