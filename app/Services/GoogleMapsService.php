<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

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
            $url = 'https://places.googleapis.com/v1/places:searchText';
            $headers = [
                'X-Goog-Api-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
                'X-Goog-FieldMask' => 'places.id,places.displayName.text,places.formattedAddress,places.location,places.internationalPhoneNumber,places.websiteUri,places.rating,places.priceLevel,places.regularOpeningHours,places.photos,places.types,places.reviewSummary',
            ];

            $body = [
                'textQuery' => $query,
                'includedType' => 'restaurant',
            ];

            if ($latitude !== null && $longitude !== null) {
                $body['locationBias'] = [
                    'circle' => [
                        'center' => [
                            'latitude' => $latitude,
                            'longitude' => $longitude,
                        ],
                        'radius' => $radius,
                    ],
                ];
            }

            $response = Http::withHeaders($headers)->post($url, $body);
            $data = $response->json();

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
            $url = "https://places.googleapis.com/v1/places/{$placeId}";
            $headers = [
                'X-Goog-Api-Key' => $this->apiKey,
                'X-Goog-FieldMask' => 'id,displayName.text,formattedAddress,location,internationalPhoneNumber,websiteUri,rating,priceLevel,regularOpeningHours,photos,types,reviews',
            ];

            $response = Http::withHeaders($headers)->get($url);
            $data = $response->json();

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

        if (isset($response['places'])) {
            foreach ($response['places'] as $place) {
                $places[] = [
                    'place_id' => $place['id'] ?? '', // 'name' in new API is like 'places/ChIJ...'
                    'name' => $place['displayName']['text'] ?? '', // Assuming displayName.text for name
                    'formatted_address' => $place['formattedAddress'] ?? '',
                    'geometry' => [
                        'location' => [
                            'lat' => $place['location']['latitude'] ?? 0,
                            'lng' => $place['location']['longitude'] ?? 0,
                        ]
                    ],
                    'rating' => $place['rating'] ?? 0,
                    'price_level' => $place['priceLevel'] ?? null,
                    'types' => $place['types'] ?? [],
                    'photos' => $this->formatPhotos($place['photos'] ?? []),
                    'international_phone_number' => $place['internationalPhoneNumber'] ?? null,
                    'website' => $place['websiteUri'] ?? null,
                    'opening_hours' => $this->formatOpeningHours($place['regularOpeningHours'] ?? null),
                    'review_summary' => $place['reviewSummary'] ?? null,
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
        $result = $response; // The details response is not wrapped in 'places' array

        return [
            'place_id' => $result['id'] ?? '',
            'name' => $result['displayName']['text'] ?? '',
            'formatted_address' => $result['formattedAddress'] ?? '',
            'geometry' => [
                'location' => [
                    'lat' => $result['location']['latitude'] ?? 0,
                    'lng' => $result['location']['longitude'] ?? 0,
                ]
            ],
            'formatted_phone_number' => $result['internationalPhoneNumber'] ?? null,
            'website' => $result['websiteUri'] ?? null,
            'rating' => $result['rating'] ?? 0,
            'price_level' => $result['priceLevel'] ?? null,
            'opening_hours' => $this->formatOpeningHours($result['regularOpeningHours'] ?? null),
            'types' => $result['types'] ?? [],
            'photos' => $this->formatPhotos($result['photos'] ?? []),
            // 'review_summary' => $result['reviewSummary'] ?? null,
            'reviews' => $result['reviews'] ?? null,
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
                'photo_reference' => $photo['name'] ?? '', // 'name' in new API is like 'places/ChIJ.../photos/ATKogp...'
                'height' => $photo['heightPx'] ?? 0,
                'width' => $photo['widthPx'] ?? 0,
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
            'open_now' => $openingHours['openNow'] ?? false,
            'periods' => $openingHours['periods'] ?? [],
            'weekday_text' => $openingHours['weekdayDescriptions'] ?? [], // New API uses weekdayDescriptions
        ];
    }
}