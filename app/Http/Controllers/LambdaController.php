<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Exception;
use RZP\Models\Lambda;

class LambdaController extends Controller
{
    protected $service = Lambda\Service::class;

    public function processLambda(string $type)
    {
        $input = Request::all();

        $data = $this->service()->processLambda($type, $input);

        return ApiResponse::json($data);
    }

    public function processLambdaFIRS()
    {
        $input = Request::all();

        $data = $this->service()->processLambdaFIRS($input);

        return ApiResponse::json($data);
    }

    public function processLambdaMerchantMasterFIRS()
    {
        $input = Request::all();

        $data = $this->service()->processLambdaMerchantMasterFIRS($input);

        return ApiResponse::json($data);
    }

    public function processLambdaSettlementRepatriation()
    {
        $input = Request::all();

        $data = $this->service()->processLambdaSettlementRepatriation($input);

        return ApiResponse::json($data);
    }

    public function processLambdaOpgspSettlementRepatriation()
    {
        $input = Request::all();

        $data = $this->service()->processLambdaOpgspSettlementRepatriation($input);

        return ApiResponse::json($data);
    }
}
