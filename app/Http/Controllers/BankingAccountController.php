<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class BankingAccountController extends Controller
{
    use Traits\HasCrudMethods;

    public function storeMerchantCredentials(string $id)
    {
        $input = Request::all();

        $response = $this->service()->storeMerchantCredentials($id, $input);

        return ApiResponse::json($response);
    }
}
