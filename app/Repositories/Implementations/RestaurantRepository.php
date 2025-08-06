<?php

namespace App\Repositories\Implementations;

use App\Models\Restaurant;
use App\Repositories\Interfaces\RestaurantRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class RestaurantRepository implements RestaurantRepositoryInterface
{
    public function getAll(array $filters = []): Collection
    {
        $query = Restaurant::query();

        if (isset($filters['cuisine_type'])) {
            $query->byCuisine($filters['cuisine_type']);
        }

        if (isset($filters['min_rating'])) {
            $query->byRating($filters['min_rating']);
        }

        if (isset($filters['price_level'])) {
            $query->byPriceLevel($filters['price_level']);
        }

        return $query->get();
    }

    public function findById(int $id): ?Restaurant
    {
        return Restaurant::find($id);
    }

    public function search(string $query, array $filters = []): Collection
    {
        $restaurantQuery = Restaurant::search($query);

        if (isset($filters['cuisine_type'])) {
            $restaurantQuery->byCuisine($filters['cuisine_type']);
        }

        if (isset($filters['min_rating'])) {
            $restaurantQuery->byRating($filters['min_rating']);
        }

        if (isset($filters['price_level'])) {
            $restaurantQuery->byPriceLevel($filters['price_level']);
        }

        return $restaurantQuery->get();
    }

    public function findByLocation(float $latitude, float $longitude, float $radius = 10): Collection
    {
        return Restaurant::selectRaw('*, 
            (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance', 
            [$latitude, $longitude, $latitude])
            ->having('distance', '<=', $radius)
            ->orderBy('distance')
            ->get();
    }

    public function findByCuisine(string $cuisineType): Collection
    {
        return Restaurant::byCuisine($cuisineType)->get();
    }

    public function findByRating(float $minRating): Collection
    {
        return Restaurant::byRating($minRating)->get();
    }

    public function findByPriceLevel(int $priceLevel): Collection
    {
        return Restaurant::byPriceLevel($priceLevel)->get();
    }

    public function createFromGoogleData(array $googleData): Restaurant
    {
        return Restaurant::create([
            'name' => $googleData['name'] ?? '',
            'description' => $googleData['formatted_address'] ?? '',
            'address' => $googleData['formatted_address'] ?? '',
            'city' => $googleData['address_components']['locality'] ?? '',
            'state' => $googleData['address_components']['administrative_area_level_1'] ?? '',
            'country' => $googleData['address_components']['country'] ?? '',
            'postal_code' => $googleData['address_components']['postal_code'] ?? '',
            'latitude' => $googleData['geometry']['location']['lat'] ?? null,
            'longitude' => $googleData['geometry']['location']['lng'] ?? null,
            'phone' => $googleData['formatted_phone_number'] ?? null,
            'website' => $googleData['website'] ?? null,
            'email' => $googleData['email'] ?? null,
            'cuisine_type' => $googleData['types'][0] ?? null,
            'rating' => $googleData['rating'] ?? 0,
            'price_level' => $googleData['price_level'] ?? null,
            'opening_hours' => json_encode($googleData['opening_hours'] ?? []),
            'google_place_id' => $googleData['place_id'] ?? null,
            'google_photos' => json_encode($googleData['photos'] ?? []),
            'is_from_google' => true,
        ]);
    }

    public function findByGooglePlaceId(string $placeId): ?Restaurant
    {
        return Restaurant::where('google_place_id', $placeId)->first();
    }
} 