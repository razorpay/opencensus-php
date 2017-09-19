<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

class P2pController extends Controller
{
    public function createP2p()
    {
        $input = Request::all();

        $data = $this->service()->create($input);

        return ApiResponse::json($data);
    }

    public function getP2p($id)
    {
        $data = $this->service()->getById($id);

        return ApiResponse::json($data);
    }

    public function getP2ps()
    {
        $input = Request::all();

        $data = $this->service()->getMultiple($input);

        return ApiResponse::json($data);
    }

    public function rejectP2p($id)
    {
        $data = $this->service()->reject($id);

        return ApiResponse::json($data);
    }

    public function postAuthorize($id)
    {
        $input = Request::all();

        $data = $this->service()->authorize($id, $input);

        return ApiResponse::json($data);
    }

    public function fetchCollectRequests()
    {
        return $this->service()->fetchCollectRequests();
    }

    public function fetchCollectRequestsPrivate($id)
    {
        return $this->service()->fetchCollectRequestsForCustomer($id);
    }
}
