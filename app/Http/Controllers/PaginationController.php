<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Services\Pagination\Service as PaginationService;

/**
 * Add func to run specific operations
 *
 * Class PaginationController
 * @package RZP\Http\Controllers
 */
class PaginationController extends Controller
{
    protected $service = PaginationService::class;

    /**
     * Runs trim space job using pagination service
     *
     * @return mixed
     */
    public function trimSpacesForMerchant()
    {
        $response = $this->service()->startTrimProcess();

        return ApiResponse::json($response);
    }

    public function populateRedisKeyForTrimSpace()
    {
        $input = Request::all();

        $response = $this->service()->populateRedisKey($input);

        return ApiResponse::json($response);
    }
}
