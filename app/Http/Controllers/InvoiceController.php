<?php

namespace RZP\Http\Controllers;

use Request;
use View;
use ApiResponse;
use RZP\Models\Invoice;

class InvoiceController extends Controller
{
    protected $service;

    public function __construct()
    {
        parent::__construct();

        $this->service = new Invoice\Service();
    }

    public function createInvoice()
    {
        $input = Request::all();

        $invoice = $this->service->create($input);

        return ApiResponse::json($invoice);
    }

    public function getInvoice($id)
    {
        $invoice = $this->service->fetch($id);

        return ApiResponse::json($invoice);
    }

    public function getInvoices()
    {
        $input = Request::all();

        $invoices = $this->service->fetchMultiple($input);

        return ApiResponse::json($invoices);
    }

    public function sendNotifications()
    {
        $summary = $this->service->sendNotificationsInBulk();

        return ApiResponse::json($summary);
    }

    public function sendNotification($id, $medium)
    {
        $data = $this->service->sendNotification($id, $medium);

        return ApiResponse::json($data);
    }

    public function expireInvoices()
    {
        $summary = $this->service->expireInvoices();

        return ApiResponse::json($summary);
    }

    public function getInvoiceStatus($id)
    {
        $data = $this->service->fetchStatus($id);

        return ApiResponse::json($data);
    }

    public function updateInvoiceNotificationStatus($medium)
    {
        // TODO: Fill this up once we finalize on how to update
        // email and sms statuses to sent/viewed, after delivery confirmation.
    }

    public function getInvoiceView($invoiceId)
    {
        $data = $this->service->getInvoiceViewDetails($invoiceId);

        return View::make('invoice.index')->with('data', $data);
    }
}
