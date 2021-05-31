<?php

namespace RZP\Models\CardMandate\CardMandateNotification;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'card_mandate_notification';

    public function findByNotificationId($id)
    {
        return $this->newQuery()
                    ->where(Entity::NOTIFICATION_ID, '=', $id)
                    ->first();
    }
}
