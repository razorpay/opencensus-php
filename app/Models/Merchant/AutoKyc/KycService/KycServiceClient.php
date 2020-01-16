<?php

namespace RZP\Models\Merchant\AutoKyc\KycService;

use App;
use Requests_Response;

use RZP\Trace\TraceCode;
use RZP\Http\RequestHeader;
use RZP\Models\Merchant\AutoKyc\BaseServiceClient;

trait KycServiceClient
{
    use BaseServiceClient
    {
        addRequestHeaders as protected baseAddRequestHeaders;
        sendRequest as protected baseSendRequest;
    }

    protected function addRequestHeaders(array &$request)
    {
        $this->baseAddRequestHeaders($request);

        #tentative until info client id has been allocated manually by KYC Service

        $defaultHeaders = [
            RequestHeader::X_SERVICE_ID => $this->config['x_service_id'],
        ];

        $request['headers'] = array_merge($request['headers'], $defaultHeaders);
    }

    protected function sendRequest(array $request): Requests_Response
    {
        $this->trace->info(TraceCode::KYC_SERVICE_API_REQUEST, $this->getTraceableRequest($request));

        return $this->baseSendRequest($request);
    }

    protected function traceResponse(Requests_Response $response)
    {
        $payload = [
            'status_code' => $response->status_code,
            'body'        => $response->body
        ];

        $this->trace->info(TraceCode::KYC_SERVICE_API_RESPONSE, $payload);
    }

    protected function getAuthUserName()
    {
        return $this->config['authentication'];
    }
}
