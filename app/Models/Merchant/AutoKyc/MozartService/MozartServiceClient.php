<?php

namespace RZP\Models\Merchant\AutoKyc\MozartService;

use \WpOrg\Requests\Response;

use RZP\Trace\TraceCode;
use RZP\Models\Merchant\AutoKyc\BaseServiceClient;

trait MozartServiceClient
{
    use BaseServiceClient
    {
        addRequestHeaders as protected baseAddRequestHeaders;
        sendRequest as protected baseSendRequest;
    }

    protected function sendRequest(array $request)
    {
        $this->trace->info(TraceCode::CAPITAL_INTEGRATION_API_REQUEST, $this->getTraceableRequest($request));

        return $this->baseSendRequest($request);
    }

    protected function traceResponse($response)
    {
        $payload = [
            'status_code' => $response->status_code,
            'body'        => $response->body
        ];

        $this->trace->info(TraceCode::CAPITAL_INTEGRATION_API_RESPONSE, $payload);
    }
}
