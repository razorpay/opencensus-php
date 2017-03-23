<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use RZP\Models\P2p;

class P2pController extends Controller
{
    public function createP2p()
    {
        $input = Request::all();

        $data = $this->service('p2p')->->create($input);

        return ApiResponse::json($data);
    }

    public function getP2p($id)
    {
        $data = $this->service('p2p')->->getById($id);

        return ApiResponse::json($data);
    }

    public function getP2ps()
    {
        $input = Request::all();

        $data = $this->service('p2p')->->getMultiple($input);

        return ApiResponse::json($data);
    }

    public function rejectP2p($id)
    {
        $data = $this->service('p2p')->->reject($id);

        return ApiResponse::json($data);
    }

    public function postAuthorize(P2p\Service $p2pService, $id)
    {
        $input = Request::all();

        $data = $p2pService->authorize($id, $input);

        return ApiResponse::json($data);
    }

    public function fetchCollectRequests()
    {
        return $this->service('p2p')->->fetchCollectRequests();
    }

    public function fetchCollectRequestsPrivate($id)
    {
        return $this->service('p2p')->->fetchCollectRequestsForCustomer($id);
    }
}
