<?php

namespace RZP\Http\Controllers;

use View;
use Config;
use Request;
use Response;
use ApiResponse;
use RZP\Constants;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Exception\BaseException;
use RZP\Models\Merchant\Preferences;
use Illuminate\Http\Response as ResponseCodes;

class InvoiceController extends Controller
{
    public function createInvoice()
    {
        $input = Request::all();

        $invoice = $this->service()->create($input);

        return ApiResponse::json($invoice);
    }

    /**
     *  Route to create bulk invoices.
     *  Currently it is used by batch Service
     */
    public function createInvoiceBulk()
    {
        $input = Request::all();

        $response = $this->service()->createBulkInvoice($input);

        return ApiResponse::json($response);
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

    public function getInvoicesCount()
    {
        $input = Request::all();

        $invoiceCount = $this->service()->getInvoicesCount($input);

        return ApiResponse::json($invoiceCount);
    }

    public function updateInvoice(string $id)
    {
        $input = Request::all();

        $invoice = $this->service()->update($id, $input);

        return ApiResponse::json($invoice);
    }

    public function updateBillingPeriod(string $id)
    {
        $input = Request::all();

        $invoice = $this->service()->updateBillingPeriod($id, $input);

        return ApiResponse::json($invoice);
    }

    public function issueInvoice(string $id)
    {
        $invoice = $this->service()->issue($id);

        return ApiResponse::json($invoice);
    }

    public function notifyInvoicesOfBatch(string $batchId)
    {
        $input = Request::all();

        $this->service()->notifyInvoicesOfBatch($batchId, $input);

        return ApiResponse::json([]);
    }

    public function cancelInvoicesOfBatch(string $batchId)
    {
        $this->service()->cancelInvoicesOfBatch($batchId);

        return ApiResponse::json([]);
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

        //
        // We pull the merchant.id because we don't want the same to be sent to view.
        // If ever this condition is being removed from here, need to remove merchant.id from ViewDataSerializer
        //
        $merchantId = array_pull($data, 'merchant.id');

        $view = 'invoice.index';

        if (isset($data['invoice']) and $data['invoice']['type'] !== 'invoice')
        {
            $view = 'invoice.payment_link';

            $routeName = $this->app['api.route']->getCurrentRouteName();

            // Gets mode per route and sets application & db mode.
            $mode = str_contains($routeName, '_test') ? Mode::TEST : Mode::LIVE;

            // Get razorx treatment
            $variant = $this->app->razorx->getTreatment(
                $merchantId,
                Merchant\RazorxTreatment::RENDERING_PREFERENCES_PAYMENT_LINKS,
                $mode
            );

            if (strtolower($variant) === 'on')
            {
                $view = 'invoice.payment_link_options';
            }
        }

        if (isset($data['invoice']) and $data['invoice']['entity_type'] === Constants\Entity::SUBSCRIPTION_REGISTRATION)
        {
            $view = 'invoice.auth_link';
        }

        if ($merchantId === Preferences::MID_UBER)
        {
            $view = 'invoice.uber';
        }

        if (isset($data['error']) === true)
        {
            $view = 'public.error';
        }

        //
        // This route gets called as part of callback_url during payment
        // creation when pop-up doesn't work. We send the request parameters
        // to blade and there JS code handles invoice.callback_url.
        //
        $data['request_params'] = Request::all();

        $data['lumberjack_key'] = Config::get('applications.lumberjack.static_key');

        return View::make($view)
                   ->with('data', $data);
    }



    /*
     * Below function is added to test Rendering Preferences on a different route.
     * Will delete after testing.
     */
    public function getInvoiceViewForTest(string $invoiceId)
    {
        $error = Request::get('error');

        try
        {
            $data = $this->service()->getInvoiceViewDataForTest($invoiceId);
        }
        catch (BaseException $e)
        {
            $data = $e->getError()->toPublicArray();
        }

        if (empty($error) === false)
        {
            $data['error'] = $error;
        }

        //
        // We pull the merchant.id because we don't want the same to be sent to view.
        // If ever this condition is being removed from here, need to remove merchant.id from ViewDataSerializer
        //
        $merchantId = array_pull($data, 'merchant.id');

        $view = 'invoice.index';

        if (isset($data['invoice']) and $data['invoice']['type'] !== 'invoice')
        {
            $view = 'invoice.payment_link_options';

            $routeName = $this->app['api.route']->getCurrentRouteName();

            // Gets mode per route and sets application & db mode.
            $mode = str_contains($routeName, '_test') ? Mode::TEST : Mode::LIVE;

            // Get razorx treatment
            $variant = $this->app->razorx->getTreatment(
                $merchantId,
                Merchant\RazorxTreatment::RENDERING_PREFERENCES_PAYMENT_LINKS,
                $mode
            );

            if (strtolower($variant) === 'on')
            {
                $view = 'invoice.payment_link_options';
            }
        }

        if (isset($data['invoice']) and $data['invoice']['entity_type'] === Constants\Entity::SUBSCRIPTION_REGISTRATION)
        {
            $view = 'invoice.auth_link';
        }

        if ($merchantId === Preferences::MID_UBER)
        {
            $view = 'invoice.uber';
        }

        if (isset($data['error']) === true)
        {
            $view = 'public.error';
        }

        //
        // This route gets called as part of callback_url during payment
        // creation when pop-up doesn't work. We send the request parameters
        // to blade and there JS code handles invoice.callback_url.
        //
        $data['request_params'] = Request::all();

        $data['lumberjack_key'] = Config::get('applications.lumberjack.static_key');

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
                        ->view('public.error', ['data' => $data])
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
