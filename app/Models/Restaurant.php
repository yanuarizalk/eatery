<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Restaurant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'address',
        'latitude',
        'longitude',
        'phone',
        'website',
        'cuisine_type',
        'rating',
        'price_level',
        'opening_hours',
        'google_place_id',
        'google_photos',
        'is_from_google',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'rating' => 'decimal:1',
        'price_level' => 'string',
        'google_photos' => 'array',
        'is_from_google' => 'boolean',
    ];

    /**
     * Get the menus for the restaurant.
     */
    public function menus(): HasMany
    {
        return $this->hasMany(Menu::class);
    }

    /**
     * Get the reviews for the restaurant.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Scope to filter by cuisine type.
     */
    public function scopeByCuisine($query, $cuisineType)
    {
        return $query->where('cuisine_type', $cuisineType);
    }

    /**
     * Scope to filter by rating.
     */
    public function scopeByRating($query, $minRating)
    {
        return $query->where('rating', '>=', $minRating);
    }

    /**
     * Scope to filter by price level.
     */
    public function scopeByPriceLevel($query, $priceLevel)
    {
        return $query->where('price_level', $priceLevel);
    }

    /**
     * Scope to search by name or description.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('cuisine_type', 'like', "%{$search}%");
        });
    }
}
