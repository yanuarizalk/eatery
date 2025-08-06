<?php

namespace App\Providers;

use App\Repositories\Interfaces\RestaurantRepositoryInterface;
use App\Repositories\Interfaces\MenuRepositoryInterface;
use App\Repositories\Interfaces\ReviewRepositoryInterface;
use App\Repositories\Interfaces\ApiRequestRepositoryInterface;
use App\Repositories\Implementations\RestaurantRepository;
use App\Repositories\Implementations\MenuRepository;
use App\Repositories\Implementations\ReviewRepository;
use App\Repositories\Implementations\ApiRequestRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(RestaurantRepositoryInterface::class, RestaurantRepository::class);
        $this->app->bind(MenuRepositoryInterface::class, MenuRepository::class);
        $this->app->bind(ReviewRepositoryInterface::class, ReviewRepository::class);
        $this->app->bind(ApiRequestRepositoryInterface::class, ApiRequestRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
