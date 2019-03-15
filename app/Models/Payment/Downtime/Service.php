<?php

namespace RZP\Models\Payment\Downtime;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function createFromGatewayDowntimes(array $input)
    {
        $this->core()->createFromGatewayDowntimes($input);
    }

    public function getMethodDowntimeDataForMerchant(array $input): array
    {
        $downtimes = $this->repo->method_downtime->fetchCurrentAndFutureDowntimes();

        return $downtimes->toArrayPublic();
    }
}
