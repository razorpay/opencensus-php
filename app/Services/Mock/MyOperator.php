<?php

namespace RZP\Services\Mock;

use Requests_Response;
use RZP\Trace\TraceCode;

class MyOperator extends \RZP\Services\MyOperator
{
    /**
     * {@inheritDoc}
     * Returns a sample response object for development and mocking purpose.
     */
    protected function makeCalLOutboundApiRequest(array $payload, $path, $method): Requests_Response
    {
        $this->trace->info(TraceCode::MYOPERATOR_CALL_OUTBOUND_API_REQ, compact('payload'));

        $resp = new Requests_Response;
        $resp->success     = true;
        $resp->status_code = 200;
        $resp->body        = json_encode(
            [
                'status'       => 'success',
                'code'         => '200',
                'message'      => 'Call queued successfully',
                'reference_id' => '1000000000000000',
            ]);

        return $resp;
    }
}
