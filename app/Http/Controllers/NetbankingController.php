<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Http\Controllers\Controller;
use RZP\Models\NetbankingConfig;

class NetbankingController extends Controller
{
    public function createNetBankingConfigs()
    {
        $input = Request::all();

        $data = (new NetbankingConfig\Service())->createNetBankingConfiguration($input);

        return ApiResponse::json($data);
    }


    public function fetchNetbankingConfigs()
    {
        $input = Request::all();

        $data = (new NetbankingConfig\Service())->fetchNetbankingConfigs($input);

        return ApiResponse::json($data);
    }


    public function editNetbankingConfigs()
    {
        $input = Request::all();

        $data = (new NetbankingConfig\Service())->editNetbankingConfigs($input);

        return ApiResponse::json($data);
    }

}
