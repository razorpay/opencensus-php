<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;

class HubspotRequestJob extends RequestJob
{
    protected function traceRequest()
    {
        // do not log url as it contains hubspot key.
        $this->trace->info(
            TraceCode::HUBSPOT_JOB_REQUEST,
            [
                'request' => [
                    'content' => $this->request['content'],
                    'options' => $this->request['options'],
                ]
            ]);
    }
}
