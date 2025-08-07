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

    public function all(array $filters = [])
    {
        $query = ApiRequest::query();

        if (isset($filters['method'])) {
            $query->byMethod($filters['method']);
        }

        if (isset($filters['endpoint'])) {
            $query->byEndpoint($filters['endpoint']);
        }

        if (isset($filters['client_ip'])) {
            $query->byClientIp($filters['client_ip']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->byDateRange($filters['start_date'], $filters['end_date']);
        }

        return $query->paginate(15);
    }
}