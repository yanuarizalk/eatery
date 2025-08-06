<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\GoogleMapsService;

class GoogleMapsServiceTest extends TestCase
{
    public function test_google_maps_service_initializes_without_error()
    {
        $service = new GoogleMapsService();
        $this->assertInstanceOf(GoogleMapsService::class, $service);
    }

    public function test_search_restaurants_returns_array()
    {
        $service = new GoogleMapsService();
        $result = $service->searchRestaurants('restaurant', 40.7128, -74.0060, 5000);
        $this->assertIsArray($result);
    }

    public function test_get_place_details_returns_null_or_array()
    {
        $service = new GoogleMapsService();
        $result = $service->getPlaceDetails('test_place_id');
        $this->assertTrue(is_array($result) || is_null($result));
    }
} 