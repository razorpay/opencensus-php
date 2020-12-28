<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Models\Merchant;

class AccountControllerV2 extends Controller
{
    protected $service = Merchant\AccountV2\Service::class;

    public function createAccount()
    {
        $input = Request::all();

        $response = $this->service()->createAccountV2($input);

        return ApiResponse::json($response);
    }

    public function fetchAccount(string $accountId)
    {
        $response = $this->service()->fetchAccountV2($accountId);

        return ApiResponse::json($response);
    }
}
