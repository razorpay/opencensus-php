<?php

namespace RZP\Models\Gateway\MethodDowntime;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Method;
use RZP\Models\Gateway\Downtime\Webhook;
use RZP\Jobs\DynamicNetBankingUrlUpdater;

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
