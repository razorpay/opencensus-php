<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class BankingAccountController extends Controller
{
    use Traits\HasCrudMethods;

g    public function postServiceablePincodes(string $channel)
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

    public function processBankAccountInformation(string $channel)
    {
        $input = Request::all();

        $response = $this->service()->processBankAccountInfoNotification($channel, $input);

        return $response;
    }
}
