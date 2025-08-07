<?php

namespace App\Http\Middleware;

use App\Repositories\Interfaces\ApiRequestRepositoryInterface;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequests
{
    protected $apiRequestRepository;

    public function __construct(ApiRequestRepositoryInterface $apiRequestRepository)
    {
        $this->apiRequestRepository = $apiRequestRepository;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // Log the request
        $apiRequest = $this->apiRequestRepository->logRequest([
            'method' => $request->method(),
            'endpoint' => $request->path(),
            'client_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'request_headers' => $request->headers->all(),
            'request_body' => $request->all(),
            'query_params' => $request->query->all(),
            'user_id' => Auth::id(),
        ]);

        // Process the request
        $response = $next($request);

        // Calculate response time
        $responseTime = round((microtime(true) - $startTime) * 1000);

        // Update with response data
        $this->apiRequestRepository->updateResponse($apiRequest->id, [
            'response_status' => $response->getStatusCode(),
            'response_body' => $this->getResponseBody($response),
            'response_time_ms' => $responseTime,
        ]);

        return $response;
    }

    /**
     * Get response body content.
     */
    private function getResponseBody(Response $response): array
    {
        $content = $response->getContent();
        
        // Try to decode JSON, fallback to string
        $decoded = json_decode($content, true);
        
        return $decoded ?: ['content' => $content];
    }
}
