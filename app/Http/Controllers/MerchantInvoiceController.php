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

        $data = (new Invoice\Service())->createInvoiceEntities($input);

        return ApiResponse::json($data);
    }

    public function updateGstin($id)
    {
        $input = Request::all();

        (new Invoice\Core)->updateGstin($id, $input);

        return ApiResponse::json([]);
    }
}

