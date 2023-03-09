<?php

namespace RZP\Http\Controllers;

use View;
use Config;
use Request;
use Response;
use ApiResponse;
use RZP\Constants;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Invoice\Type;
use RZP\Models\Invoice\Entity;
use RZP\Exception\BaseException;
use RZP\Models\Merchant\Preferences;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Feature\Constants as Feature;
use Illuminate\Http\Response as ResponseCodes;

class InvoiceController extends Controller
{
    protected $paymentlinkservice;

    const VALIDATION_ERROR = 'VALIDATION_ERROR';

    public function __construct()
    {
        parent::__construct();

        $this->paymentlinkservice = $this->app['paymentlinkservice'];
    }

    public function createInvoice()
    {
        $input = Request::all();

        if ($this->shouldForwardToPaymentLinkService($input, true) === true)
        {
            $response =  $this->paymentlinkservice->sendRequest($this->app->request);

            return ApiResponse::json($response['response'], $response['status_code']);
        }

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

        if ($this->shouldForwardToPaymentLinkService([], false, $id) === true)
        {
            try
            {
                $response = $this->paymentlinkservice->sendRequest($this->app->request);

                if ($response['status_code'] === 200)
                {
                    return ApiResponse::json($response['response']);
                }

                $this->trace->info(TraceCode::PAYMENT_LINK_SERVICE_NO_DATA_FOUND, ['id' => $id]);
                // all other cases , do nothing. will try fetching from invoice repo
            }
            catch(\Throwable $e)
            {
                $this->trace->warning(TraceCode::PAYMENT_LINK_SERVICE_NO_DATA_FOUND, ['id' => $id]);
                // do nothing. will try fetching from invoice repo
            }
        }

        $invoice = $this->service()->fetch($id, $input);

        return ApiResponse::json($invoice);
    }

    public function getInvoices()
    {
        $input = Request::all();

        if ($this->shouldForwardToPaymentLinkService($input, true, null, true) === true)
        {
            try
            {
                $response = $this->paymentlinkservice->sendRequest($this->app->request);

                if ($response['status_code'] === 200)
                {
                    return ApiResponse::json($response['response']);
                }

                $this->trace->warning(TraceCode::PAYMENT_LINK_SERVICE_NO_DATA_FOUND, ['input' => $input]);
            }
            catch(\Throwable $e)
            {
                $this->trace->warning(TraceCode::PAYMENT_LINK_SERVICE_NO_DATA_FOUND, ['input' => $input]);
                // do nothing. will try fetching from invoice repo
            }
        }

        $invoices = $this->service()->fetchMultiple($input);

        return ApiResponse::json($invoices);
    }

    public function getInvoiceDetailsForCheckout($id)
    {
        $data = $this->service()->getInvoiceDetailsForCheckout($id);

        return ApiResponse::json($data);
    }

    public function getInvoicesCount()
    {
        $input = Request::all();

        if ($this->shouldForwardToPaymentLinkService() === true)
        {
            try
            {
                $response = $this->paymentlinkservice->sendRequest($this->app->request);

                if ($response['status_code'] === 200)
                {
                    return ApiResponse::json($response['response']);
                }

                $this->trace->warning(TraceCode::PAYMENT_LINK_SERVICE_NO_DATA_FOUND, ['input' => $input]);
            }
            catch(\Throwable $e)
            {
                $this->trace->warning(TraceCode::PAYMENT_LINK_SERVICE_NO_DATA_FOUND, ['input' => $input]);
                // do nothing. will try fetching from invoice repo
            }
        }

        $invoiceCount = $this->service()->getInvoicesCount($input);

        return ApiResponse::json($invoiceCount);
    }

    public function updateInvoice(string $id)
    {
        $input = Request::all();

        if ($this->shouldForwardToPaymentLinkService([], false, $id) === true)
        {
            try
            {
                $response = $this->paymentlinkservice->sendRequest($this->app->request);

                $jsonResponse = $response['response'];

                if ($response['status_code'] === 200)
                {
                    return ApiResponse::json($jsonResponse);
                }

                if ($response['status_code'] === 400)
                {
                    $errorCode = $jsonResponse['error']['code'] ?? null;

                    if ($errorCode === self::VALIDATION_ERROR)
                    {
                        return ApiResponse::json($jsonResponse, $response['status_code']);
                    }
                }

                $this->trace->warning(TraceCode::PAYMENT_LINK_SERVICE_NO_DATA_FOUND, ['id' => $id, 'input' => $input]);
            }
            catch(\Throwable $e)
            {
                $this->trace->warning(TraceCode::PAYMENT_LINK_SERVICE_NO_DATA_FOUND, ['id' => $id, 'input' => $input]);
                // do nothing. will try fetching from invoice repo
            }
        }

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
        if ($this->shouldForwardToPaymentLinkService() === true)
        {
            try
            {
                $response = $this->paymentlinkservice->sendRequest($this->app->request);

                if ($response['status_code'] === 200)
                {
                    return ApiResponse::json($response['response']);
                }

                $this->trace->warning(TraceCode::PAYMENT_LINK_SERVICE_NO_DATA_FOUND, ['id' => $id]);
            }
            catch(\Throwable $e)
            {
                $this->trace->warning(TraceCode::PAYMENT_LINK_SERVICE_NO_DATA_FOUND, ['id' => $id]);
                // do nothing. will try fetching from invoice repo
            }
        }

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
        if ($this->shouldForwardToPaymentLinkService() === true)
        {
            try {
                $response = $this->paymentlinkservice->sendRequest($this->app->request);

                if ($response['status_code'] === 200)
                {
                    return ApiResponse::json($response['response']);
                }

                $this->trace->warning(TraceCode::PAYMENT_LINK_SERVICE_NO_DATA_FOUND, ['id' => $id]);
            } catch (\Throwable $e)
            {
                $this->trace->warning(TraceCode::PAYMENT_LINK_SERVICE_NO_DATA_FOUND, ['id' => $id]);
                // do nothing. will try fetching from invoice repo
            }
        }

        $data = $this->service()->sendNotification($id, $medium);

        return ApiResponse::json($data);
    }

    public function cancelInvoice(string $id)
    {
        if ($this->shouldForwardToPaymentLinkService() === true)
        {
            try
            {
                $response = $this->paymentlinkservice->sendRequest($this->app->request);

                if ($response['status_code'] === 200)
                {
                    return ApiResponse::json($response['response']);
                }

                $this->trace->warning(TraceCode::PAYMENT_LINK_SERVICE_NO_DATA_FOUND, ['id' => $id]);
            }
            catch(\Throwable $e)
            {
                $this->trace->warning(TraceCode::PAYMENT_LINK_SERVICE_NO_DATA_FOUND, ['id' => $id]);
                // do nothing. will try fetching from invoice repo
            }
        }

        $invoice = $this->service()->cancelInvoice($id);

        return ApiResponse::json($invoice);
    }

    public function expireInvoices()
    {
        $summary = $this->service()->expireInvoices();

        return ApiResponse::json($summary);
    }

    public function deleteInvoices()
    {
        $input = Request::all();

        $summary = $this->service()->deleteInvoices($input);

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

    public function maskPIIData(array & $data, array $maskFields = [])
    {
        foreach ($maskFields as $field)
        {
            try
            {
                $fieldData = $data['invoice']['subscription_registration']['bank_account'][$field] ?? null;

                if($fieldData !== null)
                {
                    if ($field === 'beneficiary_email')
                    {
                        $data['invoice']['subscription_registration']['bank_account'][$field] = mask_email($fieldData);
                    }
                    else
                    {
                        $data['invoice']['subscription_registration']['bank_account'][$field] = mask_except_last4($fieldData);
                    }
                }
            }
            catch (\Exception $e)
            {
                $this->trace->traceException($e, TraceCode::INVALID_FIELD_FOR_MASKING, ['field' => $field]);
            }
        }
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
            $view = 'invoice.payment_link_options';

            $routeName = $this->app['api.route']->getCurrentRouteName();

            // Gets mode per route and sets application & db mode.
            $mode = str_contains($routeName, '_test') ? Mode::TEST : Mode::LIVE;

        }

        if (isset($data['invoice']) and $data['invoice']['entity_type'] === Constants\Entity::SUBSCRIPTION_REGISTRATION)
        {
            if(isset($data['invoice']['subscription_registration']['upiAutopayPromoIntentUrl']))
            {
                return redirect($data['invoice']['subscription_registration']['upiAutopayPromoIntentUrl']);
            }

            $routeName = $this->app['api.route']->getCurrentRouteName();

            // Gets mode per route and sets application & db mode.
            $mode = str_contains($routeName, '_test') ? Mode::TEST : Mode::LIVE;

            // Get razorx treatment
            $variant = $this->app->razorx->getTreatment(
                $merchantId,
                Merchant\RazorxTreatment::BLOCK_PAN_DETAIL_IN_AUTHLINK_HTML,
                $mode
            );
            if (strtolower($variant) === 'on')
            {
                unset($data['merchant']['pan']);
                unset($data['invoice']['customer_details']);
            }

            $this->maskPIIData($data, ['account_number', 'beneficiary_email', 'beneficiary_mobile', 'name']);

            $view = 'invoice.auth_link';
        }

        if ($merchantId === Preferences::MID_UBER)
        {
            $view = 'invoice.uber';
        }

        if (isset($data['error']) === true)
        {
            $view = 'public.error';

            if ((isset($data['error']['metadata']) === true) &&
                (isset($data['error']['metadata']['use_end_state_format']) === true))
            {
                $view = 'invoice.payment_link_end_state';

                $data['error']['code'] = 'end_state';

                $data['error'] += $data['error']['metadata'];

                unset($data['error']['metadata']);
            }
        }

        //
        // This route gets called as part of callback_url during payment
        // creation when pop-up doesn't work. We send the request parameters
        // to blade and there JS code handles invoice.callback_url.
        //
        $data['request_params'] = Request::all();

        $data['lumberjack_key'] = Config::get('applications.lumberjack.static_key');

        $mode = ($data["is_test_mode"] ?? true) ? "test" : "live";

        $keylessHeader = null;
        if (empty($merchantId) === false)
        {
            $keylessHeader = $this->app['keyless_header']->get(
                $merchantId,
                $mode);
        }

        return View::make($view)
            ->with('data', $data)
            ->with('keyless_header', $keylessHeader);
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
            $this->maskPIIData($data, ['account_number', 'beneficiary_email', 'beneficiary_mobile', 'name']);

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

        $mode = ($data["is_test_mode"] ?? true) ? "test" : "live";

        $keylessHeader = null;
        if (empty($merchantId) === false)
        {
            $keylessHeader = $this->app['keyless_header']->get(
                $merchantId,
                $mode);
        }

        return View::make($view)
            ->with('data', $data)
            ->with('keyless_header', $keylessHeader);
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

    public function sendEmailForPaymentLinkService()
    {
        $input = Request::all();

        $response = $this->service()->sendEmailForPaymentLinkService($input);

        return ApiResponse::json($response);
    }

    public function switchPlVersions()
    {
        $input = Request::all();

        $response = $this->service()->switchPlVersions($input);

        return ApiResponse::json($response);
    }

    public function dccPaymentInvoiceCron()
    {
        $input = Request::all();

        $response = $this->service()->dccPaymentInvoiceCron($input);

        return ApiResponse::json($response);
    }

    protected function shouldForwardToPaymentLinkService(
        array $input = [],
        bool $checkForInput = false,
        string $id = null,
        bool $checkForBothFlags = false
    ): bool
    {
        if ($this->app->runningUnitTests() === true)
        {
            return false;
        }

        if ($this->app['basicauth']->isPaymentLinkServiceApp() === true)
        {
            return false;
        }

        $merchant = $this->app['basicauth']->getMerchant();


        if ($merchant !== null)
        {
            if ($checkForInput === true)
            {
                return $this->checkInputHasTypeLinkOREcod($input);
            }

            if ($id !== null)
            {
                try
                {
                    return $this->service()->checkForInvoiceTypeForPlServiceForwarding($id, []);
                }
                catch (\Exception $e)
                {
                    if($e->getMessage() === PublicErrorDescription::BAD_REQUEST_FORBIDDEN)
                    {
                        throw $e;
                    }
                    // do nothing. will return true since id doesnt exist in api
                }
            }

            return true;
        }

        return false;
    }

    protected function checkInputHasTypeLinkOREcod(array $input): bool
    {
        if ((isset($input[Entity::TYPE]) === true)
            && (Type::isPaymentLinkType($input[Entity::TYPE]) === true))
        {
            return true;
        }

        if ((isset($input[Entity::TYPES]) === true) && (is_array($input[Entity::TYPES]) === true))
        {
            if (in_array(Type::LINK, $input[Entity::TYPES]) === true)
            {
                return true;
            }

            if(in_array(Type::ECOD, $input[Entity::TYPES]) === true)
            {
                return true;
            }
        }
        return false;
    }
}
