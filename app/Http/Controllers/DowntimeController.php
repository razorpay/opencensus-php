<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Constants\Entity as E;
use RZP\Models\Gateway\MethodDowntime;

class DowntimeController extends Controller
{
    public function createFromGatewayDowntimes()
    {
        $input = Request::all();

        $response = $this->service(E::METHOD_DOWNTIME)->createFromGatewayDowntimes($input);

        return ApiResponse::json($response);
    }

    public function getMethodDowntimeData()
    {
        $input = Request::all();

        $data = $this->service(E::METHOD_DOWNTIME)->getMethodDowntimeDataForMerchant($input);

        return ApiResponse::json($data);
    }
}
