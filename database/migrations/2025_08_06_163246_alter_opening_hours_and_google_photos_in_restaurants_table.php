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
        Schema::table('restaurants', function (Blueprint $table) {
            // Change opening_hours to string with length 2056
            $table->string('opening_hours', 2056)->nullable()->change();
            // Change google_photos to text
            $table->text('google_photos')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            // Revert opening_hours to string (default length 255)
            $table->string('opening_hours')->nullable()->change();
            // Revert google_photos to string (default length 255)
            $table->string('google_photos')->nullable()->change();
        });
    }
};
