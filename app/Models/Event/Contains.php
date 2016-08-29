<?php

namespace RZP\Models\Event;

use RZP\Models\Payment;
use RZP\Constants;

class Contains
{
    protected static $data = array(
        Type::PAYMENT_AUTHORIZED => [Constants\Entity::PAYMENT],
        Type::PAYMENT_FAILED     => [Constants\Entity::PAYMENT]
    );

    public static function getEntityNamesForEvent($event)
    {
        return self::$data[$event];
    }
}
