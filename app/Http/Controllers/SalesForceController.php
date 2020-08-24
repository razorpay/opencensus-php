<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Razorpay\Trace\Logger;
use Request;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
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

}