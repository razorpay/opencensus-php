<?php

namespace RZP\Models\Event;

use RZP\Models\Payment;
use RZP\Constants;

class Contains
{
    protected static $data = array(
        Type::PAYMENT_AUTHORIZED => [Constants\Entity::PAYMENT],
        Type::PAYMENT_FAILED     => [Constants\Entity::PAYMENT],
        Type::ORDER_PAID         => [Constants\Entity::PAYMENT, Constants\Entity::ORDER],
        Type::INVOICE_PAID       => [Constants\Entity::PAYMENT, Constants\Entity::ORDER, Constants\Entity::INVOICE],
    );

    public static function getEntityNamesForEvent($event)
    {
        return self::$data[$event];
    }
}
