<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            // column မရှိသေးမှ ထည့်ရန် (ပိုသေချာအောင်)
            if (!Schema::hasColumn('videos', 'google_drive_file_id')) {
                $table->string('google_drive_file_id')->nullable()->after('path');
            }
            if (!Schema::hasColumn('videos', 'mp3_drive_file_id')) {
                $table->string('mp3_drive_file_id')->nullable()->after('google_drive_file_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            //
        });
    }
};
