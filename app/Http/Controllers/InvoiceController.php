<?php

namespace RZP\Http\Controllers;

use Request;
use Response;
use View;
use ApiResponse;
use RZP\Models\Invoice;
use RZP\Exception\BaseException;
use Illuminate\Http\Response as ResponseCodes;

class InvoiceController extends Controller
{
    protected $service;

    public function __construct()
    {
        parent::__construct();

        $this->service = new Invoice\Service;
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

    public function updateInvoice($id)
    {
        $input = Request::all();

        $invoice = $this->service->update($id, $input);

        return ApiResponse::json($invoice);
    }

    public function issueInvoice($id)
    {
        $invoice = $this->service->issue($id);

        return ApiResponse::json($invoice);
    }

    public function deleteInvoice($id)
    {
        $response = $this->service->delete($id);

        return ApiResponse::json($response);
    }

    // -------------------------- Line Items --------------------------

    public function addLineItems($id)
    {
        $input = Request::all();

        $invoice = $this->service->addLineItems($id, $input);

        return ApiResponse::json($invoice);
    }

    public function updateLineItem($id, $lineItemId)
    {
        $input = Request::all();

        $invoice = $this->service->updateLineItem($id, $lineItemId, $input);

        return ApiResponse::json($invoice);
    }

    public function removeLineItem($id, $lineItemId)
    {
        $invoice = $this->service->removeLineItem($id, $lineItemId);

        return ApiResponse::json($invoice);
    }

    public function removeManyLineItems($id)
    {
        $input = Request::all();

        $invoice = $this->service->removeManyLineItems($id, $input);

        return ApiResponse::json($invoice);
    }

    // -------------------------- End Line Items --------------------------

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

    public function cancelInvoice($id)
    {
        $invoice = $this->service->cancelInvoice($id);

        return ApiResponse::json($invoice);
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
        $error = Request::get('error');

        try
        {
            $data = $this->service->getInvoiceViewData($invoiceId);
        }
        catch (BaseException $e)
        {
            $data = $e->getError()->toPublicArray();
        }

        if (empty($error) === false)
        {
            $data['error'] = $error;
        }

        return View::make('invoice.index')
                   ->with('data', $data);
    }

    public function getInvoicePdf($id)
    {
        list($displayName, $path) = $this->service->getInvoicePdf($id);

        if ($path === null)
        {
            $data = [
                'error' => [
                    'description' => 'No pdf file found'
                ],
            ];

            return response()
                        ->view('invoice.index', ['data' => $data])
                        ->setStatusCode(ResponseCodes::HTTP_BAD_REQUEST);
        }

        $download = Request::input('download', '0');

        if ($download === '1')
        {
            return Response::download($path, "$displayName.pdf");
        }

        return Response::file($path);
    }
}
