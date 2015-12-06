<?php

namespace Models\Event;

use Models\Payment;
use Constants\Entity;

class Contains
{
    protected static $data = array(
        Type::PAYMENT_AUTHORIZED => [Entity::PAYMENT].
    );

    public static function getEntityNamesForEvent($event)
    {
        return self::$data[$event];
    }
}