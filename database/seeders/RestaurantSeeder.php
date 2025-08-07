<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use App\Models\Menu;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class RestaurantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a default user for Google reviews
        $defaultUser = User::firstOrCreate([
            'email' => 'google@eatery.com'
        ], [
            'name' => 'Google Reviews',
            'password' => bcrypt('password'),
        ]);

        // Create sample restaurants
        $restaurants = [
            [
                'name' => 'Pizza Palace',
                'description' => 'Authentic Italian pizza restaurant',
                'address' => '123 Main St',
                'latitude' => 40.7128,
                'longitude' => -74.0060,
                'phone' => '+1-555-123-4567',
                'website' => 'https://pizzapalace.com',
                'cuisine_type' => 'Italian',
                'rating' => 4.5,
                'price_level' => 'PRICE_LEVEL_MODERATE', // Changed to string
                'opening_hours' => json_encode([
                    'monday' => '11:00-22:00',
                    'tuesday' => '11:00-22:00',
                    'wednesday' => '11:00-22:00',
                    'thursday' => '11:00-22:00',
                    'friday' => '11:00-23:00',
                    'saturday' => '11:00-23:00',
                    'sunday' => '12:00-21:00'
                ]),
            ],
            [
                'name' => 'Sushi Master',
                'description' => 'Premium Japanese sushi restaurant',
                'address' => '456 Oak Ave',
                'latitude' => 34.0522,
                'longitude' => -118.2437,
                'phone' => '+1-555-987-6543',
                'website' => 'https://sushimaster.com',
                'cuisine_type' => 'Japanese',
                'rating' => 4.8,
                'price_level' => 'PRICE_LEVEL_HIGH', // Changed to string
                'opening_hours' => json_encode([
                    'monday' => '11:30-22:30',
                    'tuesday' => '11:30-22:30',
                    'wednesday' => '11:30-22:30',
                    'thursday' => '11:30-22:30',
                    'friday' => '11:30-23:30',
                    'saturday' => '11:30-23:30',
                    'sunday' => '12:00-22:00'
                ]),
            ],
            [
                'name' => 'Burger Joint',
                'description' => 'Classic American burgers and fries',
                'address' => '789 Pine St',
                'latitude' => 41.8781,
                'longitude' => -87.6298,
                'phone' => '+1-555-456-7890',
                'website' => 'https://burgerjoint.com',
                'cuisine_type' => 'American',
                'rating' => 4.2,
                'price_level' => 'PRICE_LEVEL_LOW', // Changed to string
                'opening_hours' => json_encode([
                    'monday' => '10:00-21:00',
                    'tuesday' => '10:00-21:00',
                    'wednesday' => '10:00-21:00',
                    'thursday' => '10:00-21:00',
                    'friday' => '10:00-22:00',
                    'saturday' => '10:00-22:00',
                    'sunday' => '11:00-20:00'
                ]),
            ]
        ];

        foreach ($restaurants as $restaurantData) {
            $restaurant = Restaurant::create($restaurantData);

            // Add menu items
            $menuItems = [
                [
                    'name' => 'Margherita Pizza',
                    'description' => 'Fresh mozzarella, tomato sauce, basil',
                    'price' => 15.99,
                    'category' => 'Main Course',
                    'dietary_info' => 'Vegetarian',
                    'allergens' => json_encode(['dairy', 'gluten']),
                    'is_available' => true,
                ],
                [
                    'name' => 'Pepperoni Pizza',
                    'description' => 'Pepperoni, mozzarella, tomato sauce',
                    'price' => 17.99,
                    'category' => 'Main Course',
                    'dietary_info' => null,
                    'allergens' => json_encode(['dairy', 'gluten', 'pork']),
                    'is_available' => true,
                ],
                [
                    'name' => 'Caesar Salad',
                    'description' => 'Fresh romaine lettuce, parmesan, croutons',
                    'price' => 8.99,
                    'category' => 'Appetizer',
                    'dietary_info' => 'Vegetarian',
                    'allergens' => json_encode(['dairy', 'gluten']),
                    'is_available' => true,
                ]
            ];

            foreach ($menuItems as $menuData) {
                $restaurant->menus()->create($menuData);
            }

            // Add reviews
            $reviews = [
                [
                    'user_id' => $defaultUser->id,
                    'rating' => 5,
                    'comment' => 'Excellent food and service!',
                    'reviewer_name' => 'John Doe',
                    'reviewed_at' => now()->subDays(5),
                ],
                [
                    'user_id' => $defaultUser->id,
                    'rating' => 4,
                    'comment' => 'Great atmosphere, good food.',
                    'reviewer_name' => 'Jane Smith',
                    'reviewed_at' => now()->subDays(10),
                ],
                [
                    'user_id' => $defaultUser->id,
                    'rating' => 5,
                    'comment' => 'Highly recommended!',
                    'reviewer_name' => 'Mike Johnson',
                    'reviewed_at' => now()->subDays(15),
                ]
            ];

            foreach ($reviews as $reviewData) {
                $restaurant->reviews()->create($reviewData);
            }
        }
    }
}
