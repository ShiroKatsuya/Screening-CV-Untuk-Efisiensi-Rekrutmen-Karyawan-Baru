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
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('position_applied');
            $table->text('skills')->nullable()->charset('utf8mb4')->collation('utf8mb4_unicode_ci');
            $table->integer('years_experience')->default(0);
            $table->string('education_level')->nullable();
            $table->string('cv_file_path');
            $table->longText('cv_text')->nullable()->charset('utf8mb4')->collation('utf8mb4_unicode_ci');
            $table->json('features')->nullable();
            $table->float('score')->nullable();
            $table->string('recommendation')->nullable()->charset('utf8mb4')->collation('utf8mb4_unicode_ci');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
