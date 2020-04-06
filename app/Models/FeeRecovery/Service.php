<?php

namespace RZP\Models\FeeRecovery;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function createRecoveryPayout(array $input)
    {
        $feeRecovery = $this->core()->createFeeRecoveryPayout($input);

        return $feeRecovery->toArrayPublic();
    }
}
