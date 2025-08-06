<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_returns_json_response()
    {
        $response = $this->getJson('/api/restaurants/nearby?latitude=40.7128&longitude=-74.0060&radius=10');

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Unauthenticated',
                    'error' => 'Authentication required'
                ]);
    }

    public function test_authenticated_request_works_with_valid_token()
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/restaurants/nearby?latitude=40.7128&longitude=-74.0060&radius=10');

        // Should not return 401, but might return other status codes depending on the implementation
        $response->assertStatus(200);
    }

    public function test_invalid_token_returns_json_response()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid_token',
        ])->getJson('/api/restaurants/nearby?latitude=40.7128&longitude=-74.0060&radius=10');

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Unauthenticated',
                    'error' => 'Authentication required'
                ]);
    }
} 