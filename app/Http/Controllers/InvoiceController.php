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
    
    public function getInvoice($id)
    {
        $payment = $this->service->fetch($id);

        return ApiResponse::json($payment);
    }

    public function getInvoices()
    {
        $input = Request::all();
        
        $invoices = $this->service->fetchMultiple($input);

        return ApiResponse::json($invoices);
    }
    
    public function updateInvoiceNotificationStatus($medium)
    {
        // TODO: Fill this up once we finalize on how to update
        // email and sms statuses to sent/viewed, after delivery confirmation.
    }
}