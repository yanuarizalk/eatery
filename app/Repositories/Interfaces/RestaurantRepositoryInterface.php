<?php

namespace App\Repositories\Interfaces;

use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Collection;

interface RestaurantRepositoryInterface
{
    /**
     * Get all restaurants with optional filters.
     */
    public function getAll(array $filters = []): Collection;

    /**
     * Find restaurant by ID.
     */
    public function findById(int $id): ?Restaurant;

    /**
     * Search restaurants by query.
     */
    public function search(string $query, array $filters = []): Collection;

    /**
     * Find restaurants by location.
     */
    public function findByLocation(float $latitude, float $longitude, float $radius = 10): Collection;

    /**
     * Find restaurants by cuisine type.
     */
    public function findByCuisine(string $cuisineType): Collection;

    /**
     * Find restaurants by rating.
     */
    public function findByRating(float $minRating): Collection;

    /**
     * Find restaurants by price level.
     */
    public function findByPriceLevel(int $priceLevel): Collection;

    /**
     * Create restaurant from Google Places data.
     */
    public function createFromGoogleData(array $googleData): Restaurant;

    /**
     * Find restaurant by Google Place ID.
     */
    public function findByGooglePlaceId(string $placeId): ?Restaurant;
} 