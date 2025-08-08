<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class RestaurantApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_restaurants_with_authentication()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/restaurants');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'restaurants',
                    'count'
                ]
            ]);
    }

    public function test_cannot_access_restaurants_without_authentication()
    {
        $response = $this->getJson('/api/restaurants');

        $response->assertStatus(401);
    }

    public function test_can_search_restaurants()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/restaurants/search?q=pizza');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'restaurants',
                    'count',
                    'query'
                ]
            ]);
    }
    public function test_can_search_restaurants_with_location()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/restaurants/search?q=pizza&latitude=40.7128&longitude=-74.0060');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'restaurants',
                    'count',
                    'query'
                ]
            ]);
    }
}
