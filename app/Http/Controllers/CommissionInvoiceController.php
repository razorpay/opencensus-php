<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class CommissionInvoiceController extends Controller
{
    public function postCreateInvoices()
    {
        $input = Request::all();

        $data = $this->service()->createInvoiceEntities($input);

        return ApiResponse::json($data);
    }

    public function postFixInvoicesStatus()
    {
        $input = Request::all();

        $data = $this->service()->fixInvoicesStatus($input);

        return ApiResponse::json($data);
    }

    public function changeStatus($id)
    {
        $input = Request::all();

        $data = $this->service()->changeStatus($id, $input);

        return ApiResponse::json($data);
    }

    public function clearOnHoldForInvoiceBulk()
    {
        $input = Request::all();

        $data = $this->service()->clearOnHoldForInvoiceBulk($input);

        return ApiResponse::json($data);
    }

    public function fetch($id)
    {
        $input = Request::all();

        $data = $this->service()->fetch($id, $input);

        return ApiResponse::json($data);
    }

    public function fetchBulk()
    {
        $input = Request::all();

        $data = $this->service()->fetchBulk($input);

        return ApiResponse::json($data);
    }
}
