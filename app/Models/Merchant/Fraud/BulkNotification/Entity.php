<?php

namespace RZP\Models\Merchant\Fraud\BulkNotification;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    protected $entity = 'bulk_fraud_notification';

    public function getMorphClass()
    {
        return $this->entity;
    }
}
