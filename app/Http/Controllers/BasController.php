<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\BankingAccountService;
use RZP\Models\BankingAccount\Activation\Detail\Entity;
use RZP\Models\BankingAccount\Activation\Detail\Service;
use RZP\Models\BankingAccount\Activation\Notification\Notifier;

class BasController extends Controller
{
    /** @var BankingAccountService\Service $service */
    protected $service;

    public function __construct()
    {
        parent::__construct();

        $this->service = new BankingAccountService\Service();
    }

    /**
     * Validates businessId for the corresponding merchant and forwards to banking account service.
     *
     * @param string $path
     *
     * @return mixed
     * @throws \RZP\Exception\BadRequestException
     */
    public function forwardRequest($path = '')
    {
        $input = Request::all();

        $data =  $this->service->preProcessAndForwardRequest($path, $input);

        return ApiResponse::json($data);
    }

    /**
     *  This function is responsible to create balance and banking_account_statement_details
     *  for ICICI current account. Payouts, banking account statement fetch and other modules
     *  depend on these.
     *
     *  It's called from BAS service only.
     *
     * @param string $merchantId
     *
     * @return
     */
    public function createCurrentAccountBankingDependencies(string $merchantId)
    {
        $input = Request::all();

        $data =  $this->service->createCurrentAccountBankingDependencies($merchantId, $input);

        return ApiResponse::json($data);
    }

    public function forwardCronRequest($path = '')
    {
        $input = Request::all();

        $data =  $this->service->forwardCronRequest($path, $input);

        return ApiResponse::json($data);
    }

    public function initiateExternalBvsValidation()
    {
        $input = Request::all();

        $response = $this->service->requestBvsValidation($input);

        return ApiResponse::json($response);
    }

    public function forwardSalesforceRequest($path = '')
    {
        $input = Request::all();

        $basPath = 'salesforce/'.$path;

        $data =  $this->service->forwardLMSRequest($basPath, $input);

        return ApiResponse::json($data);
    }

    public function forwardLMSRequest($path = '')
    {
        $input = Request::all();

        $data =  $this->service->forwardLMSRequest($path, $input);

        return ApiResponse::json($data);
    }

    public function checkPinCodeServiceability()
    {
        // We are keeping this for now for backward compatability, TODO: Remove this once everything moves to common serviceability
        $input = Request::all();

        $data =  $this->service->checkPinCodeServiceability($input);

        return ApiResponse::json($data);
    }

    public function checkPinCodeServiceabilityBulk()
    {
        $input = Request::all();

        $data =  $this->service->checkPinCodeServiceabilityBulk($input);

        $response = ApiResponse::json($data);

        // Request does not qualify for a preflight request but still requires CORS policy
        $response->headers->set('Access-Control-Allow-Origin', $this->app['config']->get('app.razorpay_website_url'));

        return $response;
    }

    public function checkCommonServiceability(){

        $input = Request::all();

        $input["merchant_id"] = optional($this->ba->getMerchant())->getId() ?? '';

        $data =  $this->service->checkCommonServiceability($input);

        return ApiResponse::json($data);
    }

    public function slotBookForBankingAccount()
    {
        $input = Request::all();

        $data = $this->service->slotBookForBankingAccount($input);

        return ApiResponse::json($data);
    }

    public function getFreeSlotForBankingAccount()
    {
        $input = Request::all();

        $data = $this->service->getFreeSlotForBankingAccount($input);

        return ApiResponse::json($data);
    }

    public function rescheduleSlotForBankingAccount()
    {
        $input = Request::all();

        $data = $this->service->rescheduleSlotForBankingAccount($input);

        return ApiResponse::json($data);
    }

    public function getRecentFreeSlotForBankingAccount()
    {
        $input = Request::all();

        $data = $this->service->getRecentFreeSlotForBankingAccount($input);

        return ApiResponse::json($data);
    }

    public function sendCaLeadToSalesForce()
    {
        $input = Request::all();

        $data =  $this->service->sendCaLeadToSalesForce($input);

        return ApiResponse::json($data);
    }

    public function sendCaLeadStatusToSalesForce()
    {
        $input = Request::all();

        $data =  $this->service->sendCaLeadStatusToSalesForce($input);

        return ApiResponse::json($data);
    }

    public function sendCaLeadToFreshDesk()
    {
        $input = Request::all();

        $data =  $this->service->sendCaLeadToFreshDesk($input);

        return ApiResponse::json($data);
    }

    public function sendRblApplicationInProgressLeadsToSalesForce()
    {
        $data =  $this->service->sendRblApplicationInProgressLeadsToSalesForce();

        return ApiResponse::json($data);
    }

    public function getSlotBookingDetailsForBankingAccountAndChannel()
    {
        $input = Request::all();

        $channel = $input['channel'];

        $bankingAccount = $input['id'];

        $response = [];

        if ($channel === 'rbl'){

            $notifier = new Notifier();

            $response = (new Service($notifier))->getSlotBookingDetailsForBankingAccount($bankingAccount);
        }
        // ICICI Needs to be implemented

        return ApiResponse::json($response);
    }

    public function archiveBankingAccountDependencies()
    {
        $input = Request::all();

        $data =  $this->service->archiveBankingAccount($input);

        return ApiResponse::json($data);
    }

    public function unarchiveBankingAccountDependencies()
    {
        $input = Request::all();

        $data =  $this->service->unArchiveBankingAccount($input);

        return ApiResponse::json($data);
    }

    public function getMerchantAttributes(string $merchantId, string $group)
    {
        $data =  $this->service->getMerchantAttributes($merchantId, $group);

        return ApiResponse::json($data);
    }

    public function handleNotifications()
    {
        $input = Request::all();

        $data =  $this->service->handleNotifications($input);

        return ApiResponse::json($data);
    }

    public function tokenizeValues()
    {
        $input = Request::all();

        $data =  $this->service->tokenizeValues($input);

        return ApiResponse::json($data);
    }

}

