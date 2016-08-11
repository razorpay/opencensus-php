<?php

namespace RZP\Http\Controllers;

use RZP\Http\ApiResponse;
use RZP\Models\Invoice;
use Request;

class InvoiceController extends Controller
{
    protected $service;

    public function __construct()
    {
        $this->service = new Invoice\Service();
    }

    public function createInvoice()
    {
        $input = Request::all();

        $data = $this->service->create($input);

        return ApiResponse::json($data);
    }
}