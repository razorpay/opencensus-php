<?php

namespace RZP\Models\BankingAccountStatement\Details;

use Cache;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class Service extends Base\Service
{
    public function create(array $input): array
    {
        $entity = $this->core()->create($input);

        return $entity->toArray();
    }
}
