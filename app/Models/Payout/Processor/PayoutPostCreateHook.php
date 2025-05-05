<?php

namespace RZP\Models\Payout\Processor;

use RZP\Trace\TraceCode;
use RZP\Models\Payout\Entity;
use RZP\Models\Payout\SourceRequestIDMapping\Core as SourceRequestIDMappingCore;

class PayoutPostCreateHook
{
    /**
     * Creates a mapping between source request ID and payout when a payout is created
     * 
     * @param Entity $payout The newly created payout
     * @return void
     */
    public static function captureAwsTraceId(Entity $payout)
    {
        try {
            $sourceRequestIDMappingCore = new SourceRequestIDMappingCore();
            
            $sourceRequestIDMappingCore->createSourceRequestIdMapping($payout->getId());
        } 
        catch (\Throwable $e) {
            // Just log the error, don't fail the payout
            app('trace')->error(
                'payout.source_request_id.capture_error',
                [
                    'payout_id' => $payout->getId(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]
            );
        }
    }
} 