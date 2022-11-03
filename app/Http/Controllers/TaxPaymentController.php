<?php

namespace RZP\Http\Controllers;

use Mail;
use ApiResponse;

class TaxPaymentController extends Controller
{

    public function __construct()
    {
        parent::__construct();

        $this->service = $this->app['tax-payments'];
    }

    public function webHookHandler()
    {
        try
        {
            $response = $this->service->webHookHandler($this->input);

            $code = 200;

        }
        catch (\Exception $e)
        {
            $response = $e->getError()->toPublicArray();

            $code = 400;
        }

        $response = ApiResponse::json($response, $code);

        return $response;
    }

    public function createDirectTaxPayment()
    {
        try
        {
            $response = $this->service->createDirectTaxPayment($this->input);

            $code = 200;

        }
        catch (\Exception $e)
        {
            $response = $e->getError()->toPublicArray();

            $code = 400;
        }

        $response = ApiResponse::json($response, $code);

        $this->addCorsHeaders($response);

        return $response;
    }

    public function getTdsCategories()
    {
        try
        {
            $response = $this->service->getTdsCategories();

            $code = 200;

        }
        catch (\Exception $e)
        {
            $response = $e->getError()->toPublicArray();

            $code = 400;
        }

        $response = ApiResponse::json($response, $code);

        $this->addCorsHeaders($response);

        return $response;
    }

    public function cancelQueuedPayouts()
    {
        return $this->service->cancelQueuedPayouts();
    }

    public function create()
    {
        return $this->service->create($this->ba->getMerchant(),
                                      $this->input,
                                      $this->ba->getUser());
    }

    public function addPenalty()
    {
        return $this->service->addPenalty();
    }

    public function monthlySummary()
    {
        return $this->service->monthlySummary($this->ba->getMerchant());
    }

    public function adminActions()
    {
        return $this->service->adminActions($this->input);
    }

    public function enabledMerchantSettings()
    {
        return $this->service->settingsOfTaxPaymentEnabledMerchants($this->input);
    }

    public function initiateMonthlyPayouts()
    {
        return $this->service->initiateMonthlyPayouts();
    }

    public function payTaxPayment(string $taxPaymentId)
    {
        return $this->service->payTaxPayment($this->ba->getMerchant(),
                                             $taxPaymentId,
                                             $this->input,
                                             $this->ba->getUser());
    }

    /*
     * This is called by internal apps to trigger the email
     */
    public function sendMail()
    {
        return $this->service->sendMail($this->input);
    }

    /*
     * This is used by the SetCronJob to hit Mail sending routes on the MS
     */
    public function mailCron()
    {
        return $this->service->mailCron($this->input);
    }

    public function bulkPayTaxPayment()
    {
        return $this->service->bulkPayTaxPayment($this->ba->getMerchant(),
                                                 $this->input,
                                                 $this->ba->getUser());
    }

    public function cancel(string $taxPaymentId)
    {
        return $this->service->cancel($this->ba->getMerchant(), $taxPaymentId, $this->input, $this->ba->getUser());
    }

    public function bulkChallanDownload()
    {
        return $this->service->bulkChallanDownload($this->ba->getMerchant(), $this->input);
    }

    public function listTaxPayments()
    {
        return $this->service->listTaxPayments($this->ba->getMerchant(), $this->input);
    }

    /*
     * returns all the tax-payment related settings
     */
    public function getAllSettings()
    {
        return $this->service->getAllSettings($this->ba->getMerchant());
    }

    public function addOrUpdateSettings()
    {
        return $this->service->addOrUpdateSettings($this->ba->getMerchant(), $this->input, $this->ba->getUser());
    }

    public function addOrUpdateSettingsForAutoTds()
    {
        return $this->service->addOrUpdateSettingsForAutoTds($this->ba->getMerchant(), $this->input, $this->ba->getUser());
    }

    public function getTaxPayment(string $taxPaymentId)
    {

        return $this->service->getTaxPayment($this->ba->getMerchant(), $taxPaymentId, $this->input);
    }

    public function markAsPaid()
    {
        return $this->service->markAsPaid($this->ba->getMerchant(), $this->input, $this->ba->getUser());
    }

    public function uploadChallan()
    {
        return $this->service->uploadChallan($this->ba->getMerchant(),$this->input);
    }

    public function updateChallanFileId(string $taxPaymentId)
    {
        return $this->service->updateChallanFileId($this->ba->getMerchant(),$taxPaymentId, $this->input);
    }

    public function edit(string $taxPaymentId)
    {
        return $this->service->edit($this->ba->getMerchant(),$taxPaymentId, $this->input, $this->ba->getUser());
    }

    public function allowCors()
    {
        $response = ApiResponse::json([]);

        $this->addCorsHeaders($response);

        return $response;
    }

    private function addCorsHeaders(& $response)
    {
        $response->headers->set('Access-Control-Allow-Origin', $this->config['applications.vendor_payments.tax_payment_lite_fe_endpoint']);

        $response->headers->set('Access-Control-Allow-Credentials' , 'true');

        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
    }

    public function getInvalidTanStatus()
    {
        return $this->service->getInvalidTanStatus($this->ba->getMerchant());
    }

    public function listDowntimeSchedule()
    {
        return $this->service->listDowntimeSchedule();
    }

    public function getDowntimeSchedule(string $module)
    {
        return $this->service->getDowntimeSchedule($module);
    }

    public function getDTPConfig()
    {
        try
        {
            $response = $this->service->getDTPConfig();

            $code = 200;
        }
        catch (\Exception $e)
        {
            $response = $e->getError()->toPublicArray();

            $code = 500;
        }

        $response = ApiResponse::json($response, $code);

        $this->addCorsHeaders($response);

        return $response;
    }

    public function getDowntimeSchedulePublic()
    {
        try
        {
            $module = 'direct_tax_payment';

            $response = $this->service->getDowntimeSchedule($module);

            $code = 200;

        }
        catch (\Exception $e)
        {
            $response = $e->getError()->toPublicArray();

            $code = 400;
        }

        $response = ApiResponse::json($response, $code);

        $this->addCorsHeaders($response);

        return $response;
    }

    public function reminderCallback(string $mode, string $type, string $entityId)
    {
        return $this->service->reminderCallback($mode, $type, $entityId);
    }

    public function fetchPendingGstPayments()
    {
        return $this->service->fetchPendingGstPayments($this->ba->getMerchant(), $this->ba->getUser());
    }
    
    public function internalIciciAction()
    {
        return $this->service->internalIciciAction($this->input);
    }
}
