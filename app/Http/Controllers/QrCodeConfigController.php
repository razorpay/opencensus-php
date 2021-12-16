<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use Response;
use RZP\Gateway\Atom\ResponseCode;

class QrCodeConfigController extends Controller
{
    use Traits\HasCrudMethods;

    public function fetchConfigsForMerchant()
    {
        $input = Request::all();

        $response = $this->service()->fetchQrCodeConfigs($input);

        return ApiResponse::json($response);
    }

    public function update()
    {
        $input = Request::all();

        $entity = $this->service()->update($input);

        return ApiResponse::json($entity);
    }
}
