<?php

namespace RZP\Services\Mock;

use \WpOrg\Requests\Response;
use RZP\Trace\TraceCode;

class MyOperator extends \RZP\Services\MyOperator
{
    /**
     * {@inheritDoc}
     * Returns a sample response object for development and mocking purpose.
     */
    protected function makeCalLOutboundApiRequest(array $payload, $path, $method)
    {
        $this->trace->info(TraceCode::MYOPERATOR_CALL_OUTBOUND_API_REQ, compact('payload'));

        $resp = new \WpOrg\Requests\Response;
        $resp->success     = true;
        $resp->status_code = 200;
        $resp->body        = json_encode(
            [
                'status'       => 'success',
                'code'         => '200',
                'details'      => 'Request accepted successfully',
                'unique_id'    => '39f45ff0-e3ab-11eb-9257-46dda8eef2ac',
            ]);

        return $resp;
    }
}
