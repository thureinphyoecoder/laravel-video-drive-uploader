<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Permission;

class GoogleDriveService
{
    protected Drive $drive;

    public function __construct()
    {
        $client = new Client();
        $client->setAuthConfig(config('services.google.drive_json'));
        $client->addScope(Drive::DRIVE);
        $client->addScope('https://www.googleapis.com/auth/drive'); // Add full drive scope

        $this->drive = new Drive($client);
    }

    public function upload(string $localPath, string $fileName): array
    {
        $fileMetadata = new DriveFile([
            'name'    => $fileName,
            'driveId' => config('services.google.shared_drive_id'), // Use shared drive
            'parents' => [config('services.google.folder_id')],
        ]);

        $file = $this->drive->files->create(
            $fileMetadata,
            [
                'data'       => file_get_contents($localPath),
                'mimeType'   => mime_content_type($localPath),
                'uploadType' => 'multipart',
                'supportsAllDrives' => true, // Enable shared drive support
                'fields'     => 'id',
            ]
        );

        // public permission
        $this->drive->permissions->create(
            $file->id,
            new Permission([
                'type' => 'anyone',
                'role' => 'reader',
            ])
        );

        return [
            'file_id'      => $file->id,
            'download_url' => "https://drive.google.com/uc?id={$file->id}&export=download",
        ];
    }
}
