<?php

namespace RZP\Models\Payment\Downtime;

use RZP\Models\Base;
use RZP\Constants\Entity as EntityConstants;

class Service extends Base\Service
{
    protected function getRepository()
    {
        return $this->repo->getCustomDriver(EntityConstants::PAYMENT_DOWNTIME);
    }

    public function createFromGatewayDowntimes(array $input)
    {
        return $this->core()->createFromGatewayDowntimes($input);
    }

    public function getMethodDowntimeDataForMerchant(array $input): array
    {
        $downtimes = $this->getRepository()->fetchCurrentAndFutureDowntimes();

        return $downtimes->toArrayPublic();
    }
}
