<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    protected $fillable = [
        'user_id',
        'original_name',
        'path',
        'status',
        'google_drive_file_id',
        'mp3_drive_file_id'
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
