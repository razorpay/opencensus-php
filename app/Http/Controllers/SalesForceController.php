<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Razorpay\Trace\Logger;
use Request;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Base\JitValidator;
use RZP\Exception\BadRequestException;
use RZP\Http\Response\Header;
use RZP\Models\SalesForce\SalesForceEventRequestDTO;
use RZP\Models\SalesForce\SalesForceEventRequestType;
use RZP\Models\SalesForce\SalesForceService;
use RZP\Services\SalesForceClient;
use RZP\Trace\TraceCode;

class SalesForceController extends Controller {

    /** @var $salesForceClient SalesForceClient */
    private $salesForceClient;

    /** @var $salesForceService SalesForceService */
    private $salesForceService;

    /** @var $logger Logger */
    private $logger;

    public function __construct() {
        parent::__construct();
        $this->salesForceClient = new SalesForceClient($this->app);
        $this->salesForceService = new SalesForceService($this->salesForceClient);
        $this->logger = $this->trace;

    }

    public function sendSalesForceEventForOneCa(string $mid)
    {
        $input = Request::all();
        try {
            $salesForceEventRequest = $this->buildSalesForceEventRequestDTO($input);
            $merchant = $this->app['basicauth']->getMerchant();
            $this->salesForceService->raiseEventForOneCa($merchant, $salesForceEventRequest);
        } catch (\Throwable $e) {
            $this->logger->traceException($e, Logger::CRITICAL,
                TraceCode::SALESFORCE_EVENT_REQUEST_FAILED);
            throw $e;
        }

        return ApiResponse::json([], 202);
    }

    public function sendSalesForceEvent(string $mid) {
        $input = Request::all();
        try {
            $salesForceEventRequest = $this->buildSalesForceEventRequestDTO($input);
            $merchant = $this->app['basicauth']->getMerchant();
            $this->salesForceService->raiseEvent($merchant, $salesForceEventRequest);
        } catch (\Throwable $e) {
            $this->logger->traceException($e, Logger::CRITICAL,
                TraceCode::SALESFORCE_EVENT_REQUEST_FAILED);
            throw $e;
        }

        return ApiResponse::json([], 202);
    }

    public function sendSalesForceEventWebsite(string $mid) {
        $this->app['basicauth']->setMerchantById($mid);
        $this->app['rzp.mode'] = Mode::LIVE;
        $response = $this->sendSalesForceEvent($mid);
        $response->headers->set(Header::ACCESS_CONTROL_ALLOW_ORIGIN, $this->app['config']->get('app.razorpay_website_url'));
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
        return $response;
    }

    public function sendSalesForceEventWebsiteCors(string $mid) {
        $response = ApiResponse::json([]);

        $response->headers->set('Access-Control-Allow-Origin', $this->app['config']->get('app.razorpay_website_url'));

        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');

        return $response;
    }

    public function getMerchantDetailsOnOpportunity(string $mid) {
        $input = Request::all();
        $opportunities = $input['opportunity'];
        $merchantDetails = $this->salesForceService->getMerchantDetailsOnOpportunity($mid, $opportunities);
        if (empty($merchantDetails["unauthorized"]) === false &&
            $merchantDetails["unauthorized"] === true)
        {
            return ApiResponse::unauthorized(
                ErrorCode::BAD_REQUEST_UNAUTHORIZED);
        }
        return ApiResponse::json($merchantDetails);
    }

    public function buildSalesForceEventRequestDTO(array $input): SalesForceEventRequestDTO {
        if ((isset($input['event_type']) === true)
            && (isset($input['event_properties']) === true)) {
            $salesForceEventRequestDTO = new SalesForceEventRequestDTO();
            $salesForceEventRequestDTO->setEventType(new SalesForceEventRequestType($input['event_type']));
            $salesForceEventRequestDTO->setEventProperties($input['event_properties']);
            return $salesForceEventRequestDTO;
        } else {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_FIELDS_FOR_SALESFORCE_EVENT_REQUEST, null, null,
                "Salesforce Event Request should contain event_type and event_properties");
        }
    }

    public function getSalesforceDetailsForMerchantIDs() {
        $input = Request::all();
        (new JitValidator)->rules(['merchant_ids' => 'required|array'])
            ->input($input)
            ->validate();

        $merchantIds = $input['merchant_ids'];
        $merchantDetails = $this->salesForceService->getSalesforceDetailsForMerchantIDs($merchantIds);
        return ApiResponse::json($merchantDetails);
    }

}
