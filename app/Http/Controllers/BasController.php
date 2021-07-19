<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\BankingAccountService;

class BasController extends Controller
{
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

    public function forwardLMSRequest($path = '')
    {
        $input = Request::all();

        $data =  $this->service->forwardLMSRequest($path, $input);

        return ApiResponse::json($data);
    }

    public function checkPinCodeServiceability()
    {
        $input = Request::all();

        $data =  $this->service->checkPinCodeServiceability($input);

        return ApiResponse::json($data);
    }
}

