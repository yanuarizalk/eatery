<?php

namespace App\Repositories\Interfaces;

use App\Models\ApiRequest;

interface ApiRequestRepositoryInterface
{
    /**
     * Log an API request.
     */
    public function logRequest(array $requestData): ApiRequest;

    /**
     * Update request with response data.
     */
    public function updateResponse(int $requestId, array $responseData): ApiRequest;
} 