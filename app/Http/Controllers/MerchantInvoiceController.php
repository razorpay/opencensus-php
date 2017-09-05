<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

class MerchantInvoiceController extends Controller
{
    public function postCreateInvoiceEntities()
    {
        $input = Request::all();

        $data = $this->service()->createInvoiceEntities($input);

        return ApiResponse::json($data);
    }

    public function updateGstin($id)
    {
        $input = Request::all();

        $data = $this->service()->updateGstin($id, $input);

        return ApiResponse::json([]);
    }

    public function postMultipleEntities()
    {
        $input = Request::all();

        $data = $this->service('merchant_invoice')->createMulitpleInvoiceEntities($input);

        return ApiResponse::json([]);
    }
}

