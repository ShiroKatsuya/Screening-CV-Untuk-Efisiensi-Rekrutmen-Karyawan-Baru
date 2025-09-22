<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update database charset to utf8mb4
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER DATABASE ' . DB::connection()->getDatabaseName() . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        }
        
        // Update existing candidates table charset
        Schema::table('candidates', function (Blueprint $table) {
            // Convert existing columns to utf8mb4
            if (DB::connection()->getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE candidates CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert database charset if needed
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER DATABASE ' . DB::connection()->getDatabaseName() . ' CHARACTER SET utf8 COLLATE utf8_unicode_ci');
            
            Schema::table('candidates', function (Blueprint $table) {
                DB::statement('ALTER TABLE candidates CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
            });
        }
    }
};
