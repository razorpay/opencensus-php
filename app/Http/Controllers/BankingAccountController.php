<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class BankingAccountController extends Controller
{
    use Traits\HasCrudMethods;

    public function storeCredentials(string $id)
    {
        $input = Request::all();

        $response = $this->service()->storeCredentials($id, $input);

        return ApiResponse::json($response);
    }
}
