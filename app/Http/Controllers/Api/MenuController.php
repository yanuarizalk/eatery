<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Interfaces\MenuRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MenuController extends Controller
{
    protected $menuRepository;

    public function __construct(MenuRepositoryInterface $menuRepository)
    {
        $this->menuRepository = $menuRepository;
    }

    /**
     * Get menu items for a restaurant.
     */
    public function index(Request $request, int $restaurantId): JsonResponse
    {
        $filters = $request->only(['category', 'dietary_info', 'min_price', 'max_price', 'available']);
        
        $menuItems = $this->menuRepository->getByRestaurant($restaurantId, $filters);

        return response()->json([
            'success' => true,
            'message' => 'Menu items retrieved successfully',
            'data' => [
                'menu_items' => $menuItems,
                'count' => $menuItems->count(),
                'restaurant_id' => $restaurantId
            ]
        ]);
    }

    /**
     * Get menu item by ID.
     */
    public function show(int $restaurantId, int $menuId): JsonResponse
    {
        $menuItem = $this->menuRepository->findById($menuId);

        if (!$menuItem || $menuItem->restaurant_id !== $restaurantId) {
            return response()->json([
                'success' => false,
                'message' => 'Menu item not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Menu item retrieved successfully',
            'data' => [
                'menu_item' => $menuItem->load('restaurant')
            ]
        ]);
    }

    /**
     * Get menu items by category.
     */
    public function byCategory(Request $request, int $restaurantId): JsonResponse
    {
        $request->validate([
            'category' => 'required|string|min:2',
        ]);

        $category = $request->input('category');
        $menuItems = $this->menuRepository->getByCategory($restaurantId, $category);

        return response()->json([
            'success' => true,
            'message' => 'Menu items by category retrieved successfully',
            'data' => [
                'menu_items' => $menuItems,
                'count' => $menuItems->count(),
                'category' => $category,
                'restaurant_id' => $restaurantId
            ]
        ]);
    }

    /**
     * Get menu items by dietary info.
     */
    public function byDietary(Request $request, int $restaurantId): JsonResponse
    {
        $request->validate([
            'dietary_info' => 'required|string|min:2',
        ]);

        $dietaryInfo = $request->input('dietary_info');
        $menuItems = $this->menuRepository->getByDietary($restaurantId, $dietaryInfo);

        return response()->json([
            'success' => true,
            'message' => 'Menu items by dietary info retrieved successfully',
            'data' => [
                'menu_items' => $menuItems,
                'count' => $menuItems->count(),
                'dietary_info' => $dietaryInfo,
                'restaurant_id' => $restaurantId
            ]
        ]);
    }

    /**
     * Get menu items by price range.
     */
    public function byPriceRange(Request $request, int $restaurantId): JsonResponse
    {
        $request->validate([
            'min_price' => 'required|numeric|min:0',
            'max_price' => 'required|numeric|min:0|gte:min_price',
        ]);

        $minPrice = $request->input('min_price');
        $maxPrice = $request->input('max_price');
        $menuItems = $this->menuRepository->getByPriceRange($restaurantId, $minPrice, $maxPrice);

        return response()->json([
            'success' => true,
            'message' => 'Menu items by price range retrieved successfully',
            'data' => [
                'menu_items' => $menuItems,
                'count' => $menuItems->count(),
                'price_range' => [
                    'min_price' => $minPrice,
                    'max_price' => $maxPrice
                ],
                'restaurant_id' => $restaurantId
            ]
        ]);
    }

    /**
     * Get available menu items.
     */
    public function available(int $restaurantId): JsonResponse
    {
        $menuItems = $this->menuRepository->getAvailable($restaurantId);

        return response()->json([
            'success' => true,
            'message' => 'Available menu items retrieved successfully',
            'data' => [
                'menu_items' => $menuItems,
                'count' => $menuItems->count(),
                'restaurant_id' => $restaurantId
            ]
        ]);
    }
}
