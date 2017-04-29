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

        $invoice = $this->service('invoice')->create($input);

        return ApiResponse::json($invoice);
    }

    public function getInvoice($id)
    {
        $invoice = $this->service('invoice')->fetch($id);

        return ApiResponse::json($invoice);
    }

    public function getInvoices()
    {
        $input = Request::all();

        $invoices = $this->service('invoice')->fetchMultiple($input);

        return ApiResponse::json($invoices);
    }

    public function updateInvoice($id)
    {
        $input = Request::all();

        $invoice = $this->service('invoice')->update($id, $input);

        return ApiResponse::json($invoice);
    }

    public function issueInvoice($id)
    {
        $invoice = $this->service('invoice')->issue($id);

        return ApiResponse::json($invoice);
    }

    public function deleteInvoice($id)
    {
        $response = $this->service('invoice')->delete($id);

        return ApiResponse::json($response);
    }

    // -------------------------- Line Items --------------------------

    public function addLineItems($id)
    {
        $input = Request::all();

        $invoice = $this->service('invoice')->addLineItems($id, $input);

        return ApiResponse::json($invoice);
    }

    public function updateLineItem($id, $lineItemId)
    {
        $input = Request::all();

        $invoice = $this->service('invoice')->updateLineItem($id, $lineItemId, $input);

        return ApiResponse::json($invoice);
    }

    public function removeLineItem($id, $lineItemId)
    {
        $invoice = $this->service('invoice')->removeLineItem($id, $lineItemId);

        return ApiResponse::json($invoice);
    }

    public function removeManyLineItems($id)
    {
        $input = Request::all();

        $invoice = $this->service('invoice')->removeManyLineItems($id, $input);

        return ApiResponse::json($invoice);
    }

    // -------------------------- End Line Items --------------------------

    public function sendNotifications()
    {
        $summary = $this->service('invoice')->sendNotificationsInBulk();

        return ApiResponse::json($summary);
    }

    public function sendNotification($id, $medium)
    {
        $data = $this->service('invoice')->sendNotification($id, $medium);

        return ApiResponse::json($data);
    }

    public function cancelInvoice($id)
    {
        $invoice = $this->service('invoice')->cancelInvoice($id);

        return ApiResponse::json($invoice);
    }

    public function expireInvoices()
    {
        $summary = $this->service('invoice')->expireInvoices();

        return ApiResponse::json($summary);
    }

    public function getInvoiceStatus($id)
    {
        $data = $this->service('invoice')->fetchStatus($id);

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
            $data = $this->service('invoice')->getInvoiceViewData($invoiceId);
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
        list($displayName, $path) = $this->service('invoice')->getInvoicePdf($id);

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
