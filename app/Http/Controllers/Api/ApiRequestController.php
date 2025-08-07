<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Interfaces\ApiRequestRepositoryInterface;
use Illuminate\Http\Request;

class ApiRequestController extends Controller
{
    protected $apiRequestRepository;

    public function __construct(ApiRequestRepositoryInterface $apiRequestRepository)
    {
        $this->apiRequestRepository = $apiRequestRepository;
    }

    public function index(Request $request)
    {
        $filters = $request->all();
        $apiRequests = $this->apiRequestRepository->all($filters);
        return response()->json($apiRequests);
    }
}
