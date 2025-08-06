<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Interfaces\ReviewRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReviewController extends Controller
{
    protected $reviewRepository;

    public function __construct(ReviewRepositoryInterface $reviewRepository)
    {
        $this->reviewRepository = $reviewRepository;
    }

    /**
     * Get reviews for a restaurant.
     */
    public function index(Request $request, int $restaurantId): JsonResponse
    {
        $filters = $request->only(['rating', 'min_rating', 'from_google']);
        
        $reviews = $this->reviewRepository->getByRestaurant($restaurantId, $filters);

        return response()->json([
            'success' => true,
            'message' => 'Reviews retrieved successfully',
            'data' => [
                'reviews' => $reviews->load('user'),
                'count' => $reviews->count(),
                'restaurant_id' => $restaurantId
            ]
        ]);
    }

    /**
     * Get review by ID.
     */
    public function show(int $restaurantId, int $reviewId): JsonResponse
    {
        $review = $this->reviewRepository->findById($reviewId);

        if (!$review || $review->restaurant_id !== $restaurantId) {
            return response()->json([
                'success' => false,
                'message' => 'Review not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Review retrieved successfully',
            'data' => [
                'review' => $review->load(['user', 'restaurant'])
            ]
        ]);
    }

    /**
     * Get reviews by rating.
     */
    public function byRating(Request $request, int $restaurantId): JsonResponse
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
        ]);

        $rating = $request->input('rating');
        $reviews = $this->reviewRepository->getByRating($restaurantId, $rating);

        return response()->json([
            'success' => true,
            'message' => 'Reviews by rating retrieved successfully',
            'data' => [
                'reviews' => $reviews->load('user'),
                'count' => $reviews->count(),
                'rating' => $rating,
                'restaurant_id' => $restaurantId
            ]
        ]);
    }

    /**
     * Get reviews by minimum rating.
     */
    public function byMinRating(Request $request, int $restaurantId): JsonResponse
    {
        $request->validate([
            'min_rating' => 'required|integer|min:1|max:5',
        ]);

        $minRating = $request->input('min_rating');
        $reviews = $this->reviewRepository->getByMinRating($restaurantId, $minRating);

        return response()->json([
            'success' => true,
            'message' => 'Reviews by minimum rating retrieved successfully',
            'data' => [
                'reviews' => $reviews->load('user'),
                'count' => $reviews->count(),
                'min_rating' => $minRating,
                'restaurant_id' => $restaurantId
            ]
        ]);
    }

    /**
     * Get Google reviews.
     */
    public function googleReviews(int $restaurantId): JsonResponse
    {
        $reviews = $this->reviewRepository->getGoogleReviews($restaurantId);

        return response()->json([
            'success' => true,
            'message' => 'Google reviews retrieved successfully',
            'data' => [
                'reviews' => $reviews,
                'count' => $reviews->count(),
                'restaurant_id' => $restaurantId
            ]
        ]);
    }
}
