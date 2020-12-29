<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

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

        $data = $this->service('merchant_invoice')->createMultipleInvoiceEntities($input);

        return ApiResponse::json([]);
    }

    public function getBankingInvoices()
    {
        $input = Request::all();

        $invoices = $this->service()->fetchMultipleBankingInvoices($input);

        return ApiResponse::json($invoices);
    }

    public function verify()
    {
        $input = Request::all();

        $invoices = $this->service()->verify($input);

        return ApiResponse::json($invoices);
    }

    public function pdfControl()
    {
        $input = Request::all();

        $result = $this->service()->pdfControl($input);

        return ApiResponse::json($result);
    }

    public function generationControl()
    {
        $input = Request::all();

        $result = $this->service()->generationControl($input);

        return ApiResponse::json($result);
    }

    public function entityCreateAdmin()
    {
        $input = Request::all();

        $result = $this->service()->createInvoiceEntities($input);

        return ApiResponse::json($result);
    }
}
