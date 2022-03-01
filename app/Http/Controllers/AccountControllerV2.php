<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Constants\HyperTrace;
use RZP\Models\Merchant;
use RZP\Trace\Tracer;

class AccountControllerV2 extends Controller
{
    protected $service = Merchant\AccountV2\Service::class;

    public function createAccount()
    {
        $input = Request::all();

        $response = Tracer::inspan(['name' => HyperTrace::CREATE_ACCOUNT_V2], function () use ($input) {

            return $this->service()->createAccountV2($input);
        });

        return ApiResponse::json($response);
    }

    public function fetchAccount(string $accountId)
    {
        $response = Tracer::inspan(['name' => HyperTrace::FETCH_ACCOUNT_V2], function () use ($accountId) {

        return $this->service()->fetchAccountV2($accountId);
        });

        return ApiResponse::json($response);
    }

    public function editAccount(string $accountId)
    {
        $input = Request::all();

        $response = Tracer::inspan(['name' => HyperTrace::EDIT_ACCOUNT_V2], function () use ($accountId, $input) {

            return $this->service()->editAccountV2($accountId, $input);
        });

        return ApiResponse::json($response);
    }

    public function deleteAccount(string $accountId)
    {
        $response = Tracer::inspan(['name' => HyperTrace::DELETE_ACCOUNT_V2], function () use ($accountId) {

            return $this->service()->deleteAccountV2($accountId);
        });

        return ApiResponse::json($response);
    }
}
