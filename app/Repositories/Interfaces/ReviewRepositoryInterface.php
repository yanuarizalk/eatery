<?php

namespace App\Repositories\Interfaces;

use App\Models\Review;
use Illuminate\Database\Eloquent\Collection;

interface ReviewRepositoryInterface
{
    /**
     * Get all reviews for a restaurant.
     */
    public function getByRestaurant(int $restaurantId, array $filters = []): Collection;

    /**
     * Find review by ID.
     */
    public function findById(int $id): ?Review;

    /**
     * Get reviews by rating.
     */
    public function getByRating(int $restaurantId, int $rating): Collection;

    /**
     * Get reviews by minimum rating.
     */
    public function getByMinRating(int $restaurantId, int $minRating): Collection;

    /**
     * Get Google reviews.
     */
    public function getGoogleReviews(int $restaurantId): Collection;

    /**
     * Create review from Google data.
     */
    public function createFromGoogleData(int $restaurantId, array $googleData): Review;
} 