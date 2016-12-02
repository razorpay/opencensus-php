<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use RZP\Models\P2p;

class P2pController extends Controller
{
    protected $service;

    public function __construct()
    {
        $this->service = new P2p\Service;
    }

    public function createP2p()
    {
        $input = Request::all();

        $data = $this->service->create($input);

        return ApiResponse::json($data);
    }

    public function getP2p($id)
    {
        $data = $this->service->getById($id);

        return ApiResponse::json($data);
    }

    public function getP2ps()
    {
        $input = Request::all();

        $data = $this->service->getMultiple($input);

        return ApiResponse::json($data);
    }

    public function rejectP2p($id)
    {
        $data = $this->service->reject($id);

        return ApiResponse::json($data);
    }

    public function postAuthorize(P2p\Service $p2pService, $id)
    {
        $input = Request::all();

        $data = $p2pService->authorize($id, $input);

        return ApiResponse::json($data);
    }

    public function fetchCollectRequests($customerId)
    {
        return $this->service->fetchCollectRequests($customerId);
    }
}
