<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'user_id',
        'rating',
        'comment',
        'reviewer_name',
        'google_review_id',
        'is_from_google',
        'reviewed_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_from_google' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the restaurant that owns the review.
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Get the user that owns the review.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter by rating.
     */
    public function scopeByRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Scope to filter by minimum rating.
     */
    public function scopeByMinRating($query, $minRating)
    {
        return $query->where('rating', '>=', $minRating);
    }

    /**
     * Scope to filter Google reviews.
     */
    public function scopeFromGoogle($query)
    {
        return $query->where('is_from_google', true);
    }
}
