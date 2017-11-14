<?php

namespace RZP\Http\Controllers;

use Request;
use Response;
use View;
use ApiResponse;
use RZP\Exception\BaseException;
use Illuminate\Http\Response as ResponseCodes;

class InvoiceController extends Controller
{
    public function createInvoice()
    {
        $input = Request::all();

        $invoice = $this->service()->create($input);

        return ApiResponse::json($invoice);
    }

    public function getInvoice(string $id)
    {
        $input = Request::all();

        $invoice = $this->service()->fetch($id, $input);

        return ApiResponse::json($invoice);
    }

    public function getInvoices()
    {
        $input = Request::all();

        $invoices = $this->service()->fetchMultiple($input);

        return ApiResponse::json($invoices);
    }

    public function updateInvoice(string $id)
    {
        $input = Request::all();

        $invoice = $this->service()->update($id, $input);

        return ApiResponse::json($invoice);
    }

    public function issueInvoice(string $id)
    {
        $invoice = $this->service()->issue($id);

        return ApiResponse::json($invoice);
    }

    public function deleteInvoice(string $id)
    {
        $response = $this->service()->delete($id);

        return ApiResponse::json($response);
    }

    // -------------------------- Line Items --------------------------

    public function addLineItems(string $id)
    {
        $input = Request::all();

        $invoice = $this->service()->addLineItems($id, $input);

        return ApiResponse::json($invoice);
    }

    public function updateLineItem(string $id, string $lineItemId)
    {
        $input = Request::all();

        $invoice = $this->service()->updateLineItem($id, $lineItemId, $input);

        return ApiResponse::json($invoice);
    }

    public function removeLineItem(string $id, string $lineItemId)
    {
        $invoice = $this->service()->removeLineItem($id, $lineItemId);

        return ApiResponse::json($invoice);
    }

    public function removeManyLineItems(string $id)
    {
        $input = Request::all();

        $invoice = $this->service()->removeManyLineItems($id, $input);

        return ApiResponse::json($invoice);
    }

    // -------------------------- End Line Items --------------------------

    public function sendNotifications()
    {
        $summary = $this->service()->sendNotificationsInBulk();

        return ApiResponse::json($summary);
    }

    public function sendNotification(string $id, string $medium)
    {
        $data = $this->service()->sendNotification($id, $medium);

        return ApiResponse::json($data);
    }

    public function cancelInvoice(string $id)
    {
        $invoice = $this->service()->cancelInvoice($id);

        return ApiResponse::json($invoice);
    }

    public function expireInvoices()
    {
        $summary = $this->service()->expireInvoices();

        return ApiResponse::json($summary);
    }

    public function getInvoiceStatus(string $id)
    {
        $data = $this->service()->fetchStatus($id);

        return ApiResponse::json($data);
    }

    public function updateInvoiceNotificationStatus(string $medium)
    {
        // TODO: Fill this up once we finalize on how to update
        // email and sms statuses to sent/viewed, after delivery confirmation.
    }

    public function getInvoiceView(string $invoiceId)
    {
        $error = Request::get('error');

        try
        {
            $data = $this->service()->getInvoiceViewData($invoiceId);
        }
        catch (BaseException $e)
        {
            $data = $e->getError()->toPublicArray();
        }

        if (empty($error) === false)
        {
            $data['error'] = $error;
        }

        $view = 'invoice.index';

        //
        // Following is only temporary and is to be removed soon.
        // In case of Uber, a different hosted page is being served.
        // For testing purposes have made one more test account behave same way.
        //

        $idsForUberFlow = [
            '82LK42BGTN2bOe', // Uber's
            '7SVOQZGZuwHr4I', // Amit. M's
        ];

        if ((empty($data['merchant']) === false) and
            (in_array($data['merchant']['id'], $idsForUberFlow, true) === true))
        {
            $view = 'invoice.uber';
        }

        //
        // This route gets called as part of callback_url during payment
        // creation when pop-up doesn't work. We send the request parameters
        // to blade and there JS code handles invoice.callback_url.
        //

        $data['request_params'] = Request::all();

        return View::make($view)
                   ->with('data', $data);
    }

    /**
     * Gets invoice pdf file.
     * Redirects to signed aws s3 url. Additionally if download=1 in sent in query
     * then redirects and forces download.
     *
     * @param string $id
     */
    public function getInvoicePdf(string $id)
    {
        $download = (bool) Request::input('download', '0');

        $url = $this->service()->getInvoicePdfSignedUrl($id, $download);

        if ($url === null)
        {
            $data['error']['description'] = 'No pdf file found';

            return response()
                        ->view('invoice.index', ['data' => $data])
                        ->setStatusCode(ResponseCodes::HTTP_BAD_REQUEST);
        }

        return redirect($url);
    }

    public function issueInvoicesOfBatch(string $batchId)
    {
        $input = Request::all();

        $response = $this->service()->issueInvoicesOfBatch($batchId, $input);

        return ApiResponse::json($response);
    }

    /**
     * Temporary solution: Used by dashboard to show 'Issue all links'
     * against list of batch ids. This endpoint returns batch_ids for which
     * that action should be shown. Filter happens by checking if there is
     * any non-draft invoice in the batch.
     *
     * @return ApiResponse
     */
    public function getIssuableByBatchIds()
    {
        $input = Request::all();

        $response = $this->service()->getIssuableByBatchIds($input);

        return ApiResponse::json($response);
    }
}
