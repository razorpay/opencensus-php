<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class BankingAccountController extends Controller
{
    use Traits\HasCrudMethods;

    public function storeCredentialsAndActivateAccount(string $id)
    {
        $input = Request::all();

        $response = $this->service()->storeCredentialsAndActivateAccount($id, $input);

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

    public function bulkCreateBankingAccountsForYesbank(string $limit)
    {
        $input = Request::all();

        $response = $this->service()->bulkCreateBankingAccountsForYesbank($input, $limit);

        return ApiResponse::json($response);
    }
}
