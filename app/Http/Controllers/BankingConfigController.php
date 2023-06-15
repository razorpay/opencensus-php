<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Models\BankingConfig;


class BankingConfigController extends Controller
{
    public function fetchAllBankingConfigs()
    {
        $data = (new BankingConfig\Service())->fetchAllBankingConfigs();

        return ApiResponse::json($data);
    }

    public function upsertBankingConfigs()
    {
        $input = Request::all();

        $data = (new BankingConfig\Service())->upsertBankingConfigs($input);

        return ApiResponse::json($data);
    }

    public function getBankingConfig()
    {
        $input = Request::all();

        $data = (new BankingConfig\Service())->getBankingConfig($input);

        return ApiResponse::json($data);
    }

}
