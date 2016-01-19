<?php

namespace Models\Event;

use Models\Payment;
use Constants;

class Contains
{
    protected static $data = array(
        Type::PAYMENT_AUTHORIZED => [Constants\Entity::PAYMENT],
    );

    public static function getEntityNamesForEvent($event)
    {
        return self::$data[$event];
    }
}