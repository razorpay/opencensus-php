<?php

namespace RZP\Models\Merchant\Fraud\BulkNotification;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function notify(array $input, $source): array
    {
        return $this->core()->notify($input, $source);
    }

    public function notifyPostBatch(array $input)
    {
        return $this->core()->notifyPostBatch($input);
    }

    public function createFraudBatch(array $input)
    {
        return $this->core()->createFraudBatch($input);
    }
}
