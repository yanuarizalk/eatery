<?php

namespace App\Services;

use Google\Client;
use Google\Service\Places;
use Illuminate\Support\Facades\Log;

class GoogleMapsService
{
    private $client;
    private $placesService;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setApiKey(config('services.google.maps_api_key'));
        $this->placesService = new Places($this->client);
    }

    /**
     * Search for restaurants using Google Places API.
     */
    public function searchRestaurants(string $query, float $latitude = null, float $longitude = null, int $radius = 5000): array
    {
        try {
            $params = [
                'query' => $query,
                'type' => 'restaurant',
                'radius' => $radius,
            ];

            if ($latitude && $longitude) {
                $params['location'] = "{$latitude},{$longitude}";
            }

            $response = $this->placesService->textSearch($params);
            
            return $this->formatPlacesResponse($response);
        } catch (\Exception $e) {
            Log::error('Google Places API error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get detailed information about a place.
     */
    public function getPlaceDetails(string $placeId): ?array
    {
        try {
            $response = $this->placesService->get($placeId, [
                'fields' => 'name,formatted_address,geometry,formatted_phone_number,website,email,rating,price_level,opening_hours,photos,types,reviews'
            ]);

            return $this->formatPlaceDetails($response);
        } catch (\Exception $e) {
            Log::error('Google Places Details API error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Format places search response.
     */
    private function formatPlacesResponse($response): array
    {
        $places = [];

        if (isset($response->results)) {
            foreach ($response->results as $place) {
                $places[] = [
                    'place_id' => $place->place_id,
                    'name' => $place->name,
                    'formatted_address' => $place->formatted_address,
                    'geometry' => [
                        'location' => [
                            'lat' => $place->geometry->location->lat,
                            'lng' => $place->geometry->location->lng,
                        ]
                    ],
                    'rating' => $place->rating ?? 0,
                    'price_level' => $place->price_level ?? null,
                    'types' => $place->types ?? [],
                    'photos' => $this->formatPhotos($place->photos ?? []),
                ];
            }
        }

        return $places;
    }

    /**
     * Format place details response.
     */
    private function formatPlaceDetails($response): array
    {
        return [
            'place_id' => $response->place_id,
            'name' => $response->name,
            'formatted_address' => $response->formatted_address,
            'geometry' => [
                'location' => [
                    'lat' => $response->geometry->location->lat,
                    'lng' => $response->geometry->location->lng,
                ]
            ],
            'formatted_phone_number' => $response->formatted_phone_number ?? null,
            'website' => $response->website ?? null,
            'email' => $response->email ?? null,
            'rating' => $response->rating ?? 0,
            'price_level' => $response->price_level ?? null,
            'opening_hours' => $this->formatOpeningHours($response->opening_hours ?? null),
            'types' => $response->types ?? [],
            'photos' => $this->formatPhotos($response->photos ?? []),
            'reviews' => $this->formatReviews($response->reviews ?? []),
        ];
    }

    /**
     * Format photos array.
     */
    private function formatPhotos(array $photos): array
    {
        $formattedPhotos = [];
        
        foreach ($photos as $photo) {
            $formattedPhotos[] = [
                'photo_reference' => $photo->photo_reference,
                'height' => $photo->height,
                'width' => $photo->width,
            ];
        }

        return $formattedPhotos;
    }

    /**
     * Format opening hours.
     */
    private function formatOpeningHours($openingHours): ?array
    {
        if (!$openingHours) {
            return null;
        }

        return [
            'open_now' => $openingHours->open_now ?? false,
            'periods' => $openingHours->periods ?? [],
            'weekday_text' => $openingHours->weekday_text ?? [],
        ];
    }

    /**
     * Format reviews array.
     */
    private function formatReviews(array $reviews): array
    {
        $formattedReviews = [];
        
        foreach ($reviews as $review) {
            $formattedReviews[] = [
                'review_id' => $review->author_name . '_' . $review->time,
                'author_name' => $review->author_name,
                'rating' => $review->rating,
                'text' => $review->text,
                'time' => $review->time,
            ];
        }

        return $formattedReviews;
    }
} 