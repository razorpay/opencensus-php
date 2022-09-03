<?php

namespace RZP\Models\Merchant\LinkedAccountReferenceData;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function createLinkedAccountReferenceData(array $input)
    {
        $this->trace->info(TraceCode::LA_REFERENCE_DATA_CREATE_REQUEST_RECEIVED, [
            Entity::INPUT_COUNT   => count($input)
        ]);

        return $this->core()->createMany($input);
    }
}
