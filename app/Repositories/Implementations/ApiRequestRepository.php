<?php

namespace App\Repositories\Implementations;

use App\Models\ApiRequest;
use App\Repositories\Interfaces\ApiRequestRepositoryInterface;

class ApiRequestRepository implements ApiRequestRepositoryInterface
{
    public function logRequest(array $requestData): ApiRequest
    {
        return ApiRequest::create($requestData);
    }

    public function updateResponse(int $requestId, array $responseData): ApiRequest
    {
        $apiRequest = ApiRequest::find($requestId);
        
        if ($apiRequest) {
            $apiRequest->update($responseData);
        }

        return $apiRequest;
    }
} 