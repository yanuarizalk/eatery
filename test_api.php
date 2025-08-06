<?php

require_once 'vendor/autoload.php';

use App\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing API endpoints...\n";

// Test user creation
try {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);
    
    echo "✓ User created successfully\n";
    
    // Test JWT token generation
    $token = JWTAuth::fromUser($user);
    echo "✓ JWT token generated: " . substr($token, 0, 20) . "...\n";
    
    echo "✓ API setup is working correctly!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
} 