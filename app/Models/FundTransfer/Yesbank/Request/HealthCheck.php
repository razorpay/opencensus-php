<?php

namespace RZP\Models\FundTransfer\Yesbank\Request;

use RZP\Trace\TraceCode;
use RZP\Models\Payment\Gateway;

class HealthCheck extends Status
{
    // will be checking status on these FTA is for corresponding health check requests
    const IMPS_REQ_REF_NO = 'CXgFVqAShnGM7S';
    const VPA_REQ_REF_NO  = 'CXg9IY5FfnveVz';

    protected $requestTraceCode = TraceCode::NODAL_HEALTH_CHECK_REQUEST;

    protected $responseTraceCode = TraceCode::NODAL_HEALTH_CHECK_RESPONSE;

    protected $responseIdentifier = Constants::STATUS_RESPONSE_IDENTIFIER;

    public function __construct(string $type = null)
    {
        parent::__construct($type);
    }

    /**
     * Should give the request body for the current request class
     *
     * @return string
     */
    public function requestBody(): string
    {
        $body = [
            Constants::STATUS_REQUEST_IDENTIFIER => [
                Constants::VERSION              => self::VERSION,
                Constants::CUSTOMER_ID          => $this->customerId,
                Constants::REQUEST_REFERENCE_NO => self::IMPS_REQ_REF_NO,
            ],
        ];

        $this->requestTrace = $body;

        return json_encode($body);
    }

    public function getRequestInputForGateway(): array
    {
        //
        // For now, we would be hardcoding the terminal. Later, have to
        // figure out how to do terminal selection for this, since each
        // merchant might have a different terminal. Use-case being merchant
        // wants the payout/refund to happen from their custom vpa handle
        // instead of from razorpay handle
        //
        $terminal = $this->repo->terminal->findByGatewayAndTerminalData(Gateway::UPI_YESBANK);

        return [
            'terminal' => $terminal->toArray(),
            'gateway_input' => [
                'ref_id'    => self::VPA_REQ_REF_NO,
            ]
        ];
    }

    public function processResponse($response): array
    {
        return [
            'http_status_code' => $response->status_code,
            'body'             => json_decode($response->body, true),
        ];
    }
}
