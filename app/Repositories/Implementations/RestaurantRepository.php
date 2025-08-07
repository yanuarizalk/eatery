<?php

namespace App\Repositories\Implementations;

use App\Models\Restaurant;
use App\Repositories\Interfaces\RestaurantRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

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
        // return Restaurant::selectRaw(`
        //     *
        // `)
        return Restaurant::selectRaw("
            *, 
            (6371 * acos(
                cos(radians(?)) * cos(radians(latitude)) * 
                cos(radians(longitude) - radians(?)) + 
                sin(radians(?)) * sin(radians(latitude))
            )) as distance
        ", [$latitude, $longitude, $latitude])
        ->groupBy('restaurants.id', 'restaurants.name', 'restaurants.description', 'restaurants.address', 'restaurants.latitude', 'restaurants.longitude', 'restaurants.phone', 'restaurants.website', 'restaurants.email', 'restaurants.cuisine_type', 'restaurants.rating', 'restaurants.price_level', 'restaurants.opening_hours', 'restaurants.google_place_id', 'restaurants.google_photos', 'restaurants.is_from_google')
        ->havingRaw('(6371 * acos(
            cos(radians(?)) * cos(radians(latitude)) * 
            cos(radians(longitude) - radians(?)) + 
            sin(radians(?)) * sin(radians(latitude))
        )) <= ?', [$latitude, $longitude, $latitude, $radius])
        ->orderBy(DB::raw('(6371 * acos(
            cos(radians(' . $latitude . ')) * cos(radians(latitude)) * 
            cos(radians(longitude) - radians(' . $longitude . ')) + 
            sin(radians(' . $latitude . ')) * sin(radians(latitude))
        ))'))
        ->get();
        // return Restaurant::selectRaw(Restaurant::raw(`
        //     select * from restaurants r
        //     join (
        //         select id, (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) distance
        //         from restaurants
        //     ) rd on r.id = rd.id
        //     where rd.distance <= ?
        //     order by rd.distance
        // `, [$latitude, $longitude, $latitude, $radius]))->get();
        // return Restaurant::selectRaw('restaurants.*, 
        //         (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance
        //     ', [$latitude, $longitude, $latitude])
        //     ->join('restaurants as rd', 'restaurants.id', '=', 'rd.id')
        //     ->having('distance', '<=', $radius)
        //     ->orderBy('distance')
        //     ->get();
        // return Restaurant::selectRaw('*, 
        //     ((select 
        //         (6371 * acos(cos(radians(?)) * cos(radians(r.latitude)) * cos(radians(r.longitude) - radians(?)) + sin(radians(?)) * sin(radians(r.latitude)))) AS distance
        //     from restaurants r where id = r.id) distance
        //     where id = restaurants.id
        //     ) AS distance',
        //     [$latitude, $longitude, $latitude]
        // )
        //     ->having('distance', '<=', $radius)
        //     ->orderBy('distance')
        //     ->get();
        // return Restaurant::selectRaw('*, 
        //     (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance',
        //     [$latitude, $longitude, $latitude]
        // )
        //     ->having('distance', '<=', $radius)
        //     ->orderBy('distance')
        //     ->get();
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
            // 'city' => $googleData['address_components']['locality'] ?? '',
            // 'state' => $googleData['address_components']['administrative_area_level_1'] ?? '',
            // 'country' => $googleData['address_components']['country'] ?? '',
            // 'postal_code' => $googleData['address_components']['postal_code'] ?? '',
            'latitude' => $googleData['geometry']['location']['lat'] ?? null,
            'longitude' => $googleData['geometry']['location']['lng'] ?? null,
            'phone' => $googleData['formatted_phone_number'] ?? null,
            'website' => $googleData['website'] ?? null,
            // 'email' => $googleData['email'] ?? null,
            'cuisine_type' => $googleData['types'][0] ?? null,
            'rating' => $googleData['rating'] ?? 0,
            'price_level' => $googleData['price_level'] ?? null,
            'opening_hours' => $googleData['opening_hours'] ?? [],
            'google_place_id' => $googleData['place_id'] ?? null,
            'google_photos' => $googleData['photos'] ?? [],
            'is_from_google' => true,
        ]);
    }

    public function findByGooglePlaceId(string $placeId): ?Restaurant
    {
        return Restaurant::where('google_place_id', $placeId)->first();
    }
}