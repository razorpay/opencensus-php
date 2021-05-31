<?php

namespace RZP\Models\Merchant\Product\Request;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function log(array $input, string $merchantProductId, string $status, string $type)
    {
        return $this->core()->create($input, $this->merchant, $merchantProductId, $status, $type);
    }
}
