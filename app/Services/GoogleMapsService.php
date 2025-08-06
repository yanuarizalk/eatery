<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class GoogleMapsService
{
    private $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.google.maps_api_key');

        if (!$this->apiKey) {
            Log::warning('Google Maps API key not configured');
        }
    }

    /**
     * Search for restaurants using Google Places API.
     */
    public function searchRestaurants(string $query, float $latitude = null, float $longitude = null, int $radius = 5000): array
    {
        if (!$this->apiKey) {
            Log::warning('Google Maps API key not configured');
            return [];
        }

        try {
            $params = [
                'query' => $query,
                'type' => 'restaurant',
                'radius' => $radius,
                'key' => $this->apiKey,
            ];

            if ($latitude && $longitude) {
                $params['location'] = "{$latitude},{$longitude}";
            }

            $url = 'https://maps.googleapis.com/maps/api/place/textsearch/json?' . http_build_query($params);
            $response = file_get_contents($url);
            $data = json_decode($response, true);

            return $this->formatPlacesResponse($data);
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
        if (!$this->apiKey) {
            Log::warning('Google Maps API key not configured');
            return null;
        }

        try {
            $params = [
                'place_id' => $placeId,
                'fields' => 'name,formatted_address,geometry,formatted_phone_number,website,email,rating,price_level,opening_hours,photos,types,reviews',
                'key' => $this->apiKey,
            ];

            $url = 'https://maps.googleapis.com/maps/api/place/details/json?' . http_build_query($params);
            $response = file_get_contents($url);
            $data = json_decode($response, true);

            return $this->formatPlaceDetails($data);
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

        if (isset($response['results'])) {
            foreach ($response['results'] as $place) {
                $places[] = [
                    'place_id' => $place['place_id'],
                    'name' => $place['name'],
                    'formatted_address' => $place['formatted_address'],
                    'geometry' => [
                        'location' => [
                            'lat' => $place['geometry']['location']['lat'],
                            'lng' => $place['geometry']['location']['lng'],
                        ]
                    ],
                    'rating' => $place['rating'] ?? 0,
                    'price_level' => $place['price_level'] ?? null,
                    'types' => $place['types'] ?? [],
                    'photos' => $this->formatPhotos($place['photos'] ?? []),
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
        $result = $response['result'] ?? $response;

        return [
            'place_id' => $result['place_id'] ?? '',
            'name' => $result['name'] ?? '',
            'formatted_address' => $result['formatted_address'] ?? '',
            'geometry' => [
                'location' => [
                    'lat' => $result['geometry']['location']['lat'] ?? 0,
                    'lng' => $result['geometry']['location']['lng'] ?? 0,
                ]
            ],
            'formatted_phone_number' => $result['formatted_phone_number'] ?? null,
            'website' => $result['website'] ?? null,
            'email' => $result['email'] ?? null,
            'rating' => $result['rating'] ?? 0,
            'price_level' => $result['price_level'] ?? null,
            'opening_hours' => $this->formatOpeningHours($result['opening_hours'] ?? null),
            'types' => $result['types'] ?? [],
            'photos' => $this->formatPhotos($result['photos'] ?? []),
            'reviews' => $this->formatReviews($result['reviews'] ?? []),
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
                'photo_reference' => $photo['photo_reference'] ?? '',
                'height' => $photo['height'] ?? 0,
                'width' => $photo['width'] ?? 0,
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
            'open_now' => $openingHours['open_now'] ?? false,
            'periods' => $openingHours['periods'] ?? [],
            'weekday_text' => $openingHours['weekday_text'] ?? [],
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
                'review_id' => ($review['author_name'] ?? '') . '_' . ($review['time'] ?? ''),
                'author_name' => $review['author_name'] ?? '',
                'rating' => $review['rating'] ?? 0,
                'text' => $review['text'] ?? '',
                'time' => $review['time'] ?? 0,
            ];
        }

        return $formattedReviews;
    }
}