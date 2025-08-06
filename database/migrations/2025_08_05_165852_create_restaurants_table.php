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
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('address');
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('country');
            $table->string('postal_code')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('email')->nullable();
            $table->string('cuisine_type')->nullable();
            $table->decimal('rating', 2, 1)->default(0);
            $table->integer('price_level')->nullable(); // 1-4 scale
            $table->string('opening_hours')->nullable();
            $table->string('google_place_id')->nullable()->unique();
            $table->string('google_photos')->nullable(); // JSON array of photo URLs
            $table->boolean('is_from_google')->default(false);
            $table->timestamps();
            
            $table->index(['latitude', 'longitude']);
            $table->index('cuisine_type');
            $table->index('rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};
