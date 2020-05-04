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

    public function recoveryPayoutCron(array $input)
    {
        $feeRecovery = $this->core()->recoveryPayoutCron($input);

        return $feeRecovery;
    }

    public function createManualRecovery(array $input)
    {
        $response = $this->core()->createManualRecovery($input);

        return $response;
    }
}
