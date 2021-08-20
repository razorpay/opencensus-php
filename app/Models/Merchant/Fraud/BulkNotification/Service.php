<?php

namespace RZP\Models\Merchant\Fraud\BulkNotification;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function notify(array $input): array
    {
        return $this->core()->notify($input);
    }
}
