<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Interfaces\RestaurantRepositoryInterface;
use App\Services\GoogleMapsService;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;

class RestaurantController extends Controller
{
    protected $restaurantRepository;
    protected $googleMapsService;

    public function __construct(
        RestaurantRepositoryInterface $restaurantRepository,
        GoogleMapsService $googleMapsService
    ) {
        $this->restaurantRepository = $restaurantRepository;
        $this->googleMapsService = $googleMapsService;
    }

    /**
     * Get all restaurants with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['cuisine_type', 'min_rating', 'price_level']);

        $restaurants = $this->restaurantRepository->getAll($filters);

        return response()->json([
            'success' => true,
            'message' => 'Restaurants retrieved successfully',
            'data' => [
                'restaurants' => $restaurants,
                'count' => $restaurants->count()
            ]
        ]);
    }

    /**
     * Get restaurant by ID.
     */
    public function show(int $id): JsonResponse
    {
        $restaurant = $this->restaurantRepository->findById($id);

        if (!$restaurant) {
            return response()->json([
                'success' => false,
                'message' => 'Restaurant not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Restaurant retrieved successfully',
            'data' => [
                'restaurant' => $restaurant->load(['menus', 'reviews'])
            ]
        ]);
    }

    /**
     * Search restaurants by query.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2',
            'cuisine_type' => 'nullable|string',
            'min_rating' => 'nullable|numeric|min:0|max:5',
            'price_level' => 'nullable|integer|min:1|max:4',
        ]);

        $query = $request->input('q');
        $filters = $request->only(['cuisine_type', 'min_rating', 'price_level']);

        $googleResults = $this->googleMapsService->searchRestaurants($query);
        $restaurants = new Collection();

        if (!empty($googleResults)) {
            // Store Google results in database
            foreach ($googleResults as $googleData) {
                // Check if restaurant already exists
                $existingRestaurant = $this->restaurantRepository->findByGooglePlaceId($googleData['place_id']);

                if (!$existingRestaurant) {
                    $restaurant = $this->restaurantRepository->createFromGoogleData($googleData);

                    // Get detailed information and reviews
                    $details = $this->googleMapsService->getPlaceDetails($googleData['place_id']);
                    if ($details) {
                        $restaurant->update([
                            'description' => $details['formatted_address'] ?? $restaurant->description,
                            'phone' => $details['formatted_phone_number'] ?? $restaurant->phone,
                            'website' => $details['website'] ?? $restaurant->website,
                            'email' => $details['email'] ?? $restaurant->email,
                            'opening_hours' => $details['opening_hours'] ?? [],
                        ]);

                        // Store reviews if available
                        if (isset($details['reviews'])) {
                            foreach ($details['reviews'] as $reviewData) {
                                $restaurant->reviews()->create([
                                    'user_id' => 1, // Default user for Google reviews
                                    'rating' => $reviewData['rating'],
                                    'comment' => isset($reviewData['originalText']) ? $reviewData['originalText']['text'] : "",
                                    'reviewer_name' => $reviewData['authorAttribution']['displayName'],
                                    'google_review_id' => substr($reviewData['name'], strpos($reviewData['name'], 'reviews/') + strlen('reviews/')),
                                    'is_from_google' => true,
                                    'reviewed_at' => $reviewData['publishTime'] ?? now(),
                                ]);
                            }
                        }
                    }
                
                    $restaurants[] = $restaurant;
                } else {
                    $restaurants[] = $existingRestaurant;
                }
            }

            // Get updated results from database
            // $restaurants = $this->restaurantRepository->search($query, $filters); 
        }

        return response()->json([
            'success' => true,
            'message' => 'Search completed successfully',
            'data' => [
                'restaurants' => $restaurants->load(['menus', 'reviews']),
                'count' => $restaurants->count(),
                'query' => $query
            ]
        ]);
    }

    /**
     * Find restaurants by location.
     */
    public function nearby(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:1|max:50',
        ]);

        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');
        $radius = $request->input('radius', 10);

        $restaurants = $this->restaurantRepository->findByLocation($latitude, $longitude, $radius);

        return response()->json([
            'success' => true,
            'message' => 'Nearby restaurants retrieved successfully',
            'data' => [
                // 'restaurants' => $restaurants->load(['menus', 'reviews']),
                'restaurants' => $restaurants->load(['menus', 'reviews']),
                'count' => $restaurants->count(),
                'location' => [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'radius' => $radius
                ]
            ]
        ]);
    }

    /**
     * Find restaurants by cuisine type.
     */
    public function byCuisine(Request $request): JsonResponse
    {
        $request->validate([
            'cuisine_type' => 'required|string|min:2',
        ]);

        $cuisineType = $request->input('cuisine_type');
        $restaurants = $this->restaurantRepository->findByCuisine($cuisineType);

        return response()->json([
            'success' => true,
            'message' => 'Restaurants by cuisine retrieved successfully',
            'data' => [
                'restaurants' => $restaurants->load(['menus', 'reviews']),
                'count' => $restaurants->count(),
                'cuisine_type' => $cuisineType
            ]
        ]);
    }

    /**
     * Find restaurants by rating.
     */
    public function byRating(Request $request): JsonResponse
    {
        $request->validate([
            'min_rating' => 'required|numeric|min:0|max:5',
        ]);

        $minRating = $request->input('min_rating');
        $restaurants = $this->restaurantRepository->findByRating($minRating);

        return response()->json([
            'success' => true,
            'message' => 'Restaurants by rating retrieved successfully',
            'data' => [
                'restaurants' => $restaurants->load(['menus', 'reviews']),
                'count' => $restaurants->count(),
                'min_rating' => $minRating
            ]
        ]);
    }

    /**
     * Find restaurants by price level.
     */
    public function byPriceLevel(Request $request): JsonResponse
    {
        $request->validate([
            'price_level' => 'required|integer|min:1|max:4',
        ]);

        $priceLevel = $request->input('price_level');
        $restaurants = $this->restaurantRepository->findByPriceLevel($priceLevel);

        return response()->json([
            'success' => true,
            'message' => 'Restaurants by price level retrieved successfully',
            'data' => [
                'restaurants' => $restaurants->load(['menus', 'reviews']),
                'count' => $restaurants->count(),
                'price_level' => $priceLevel
            ]
        ]);
    }
}
