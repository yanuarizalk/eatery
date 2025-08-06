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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('rating'); // 1-5 scale
            $table->text('comment')->nullable();
            $table->string('reviewer_name')->nullable();
            $table->string('google_review_id')->nullable()->unique();
            $table->boolean('is_from_google')->default(false);
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            
            $table->index(['restaurant_id', 'rating']);
            $table->index('rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
