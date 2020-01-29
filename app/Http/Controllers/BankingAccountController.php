<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class BankingAccountController extends Controller
{
    use Traits\HasCrudMethods;

    public function activate(string $id)
    {
        $input = Request::all();

        $response = $this->service()->activate($id, $input);

        return ApiResponse::json($response);
    }

    public function postServiceablePincodes(string $channel)
    {
        $input = Request::all();

        $result = $this->service()->addOrRemoveServiceablePincodes($input, $channel);

        return ApiResponse::json($result);
    }

    public function processAccountInfoWebhook(string $channel)
    {
        $input = Request::all();

        $response = $this->service()->processAccountInfoWebhook($channel, $input);

        return $response;
    }

    public function bulkCreateBankingAccountsForYesbank()
    {
        $input = Request::all();

        $response = $this->service()->bulkCreateBankingAccountsForYesbank($input);

        return ApiResponse::json($response);
    }

    public function getActivationStatusChangeLog(string $id)
    {
        $response = $this->service()->getActivationStatusChangeLog($id);

        return ApiResponse::json($response);
    }
}
