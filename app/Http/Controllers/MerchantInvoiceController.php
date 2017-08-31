<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

use RZP\Models\Merchant\Invoice;

class MerchantInvoiceController extends Controller
{
    public function postCreateInvoiceEntities()
    {
        $input = Request::all();

        $data = $this->service('merchant_invoice')->createInvoiceEntities($input);

        return ApiResponse::json($data);
    }

    public function updateGstin($id)
    {
        $input = Request::all();

        (new Invoice\Core)->updateGstin($id, $input);

        return ApiResponse::json([]);
    }

    public function postMultipleEntities()
    {
        $input = Request::all();

        $data = $this->service('merchant_invoice')->createMulitpleInvoiceEntities($input);

        return ApiResponse::json([]);
    }
}

