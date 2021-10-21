<?php

namespace RZP\Models\Merchant\Fraud\Checker;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function milestoneCron($category): array
    {
        return $this->core()->milestoneCron($category);
    }
}
