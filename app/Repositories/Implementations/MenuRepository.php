<?php

namespace App\Repositories\Implementations;

use App\Models\Menu;
use App\Repositories\Interfaces\MenuRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class MenuRepository implements MenuRepositoryInterface
{
    public function getByRestaurant(int $restaurantId, array $filters = []): Collection
    {
        $query = Menu::where('restaurant_id', $restaurantId);

        if (isset($filters['category'])) {
            $query->byCategory($filters['category']);
        }

        if (isset($filters['dietary_info'])) {
            $query->byDietary($filters['dietary_info']);
        }

        if (isset($filters['min_price']) && isset($filters['max_price'])) {
            $query->byPriceRange($filters['min_price'], $filters['max_price']);
        }

        if (isset($filters['available']) && $filters['available']) {
            $query->available();
        }

        return $query->get();
    }

    public function findById(int $id): ?Menu
    {
        return Menu::find($id);
    }

    public function getByCategory(int $restaurantId, string $category): Collection
    {
        return Menu::where('restaurant_id', $restaurantId)
            ->byCategory($category)
            ->get();
    }

    public function getByDietary(int $restaurantId, string $dietaryInfo): Collection
    {
        return Menu::where('restaurant_id', $restaurantId)
            ->byDietary($dietaryInfo)
            ->get();
    }

    public function getByPriceRange(int $restaurantId, float $minPrice, float $maxPrice): Collection
    {
        return Menu::where('restaurant_id', $restaurantId)
            ->byPriceRange($minPrice, $maxPrice)
            ->get();
    }

    public function getAvailable(int $restaurantId): Collection
    {
        return Menu::where('restaurant_id', $restaurantId)
            ->available()
            ->get();
    }
} 