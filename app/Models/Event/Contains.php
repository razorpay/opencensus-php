<?php

namespace RZP\Models\Event;

use RZP\Models\Payment;
use RZP\Constants;

class Contains
{
    protected static $data = array(
        Type::PAYMENT_AUTHORIZED        => [Constants\Entity::PAYMENT],
        Type::PAYMENT_FAILED            => [Constants\Entity::PAYMENT],
        Type::ORDER_PAID                => [Constants\Entity::PAYMENT, Constants\Entity::ORDER],
        Type::INVOICE_PAID              => [Constants\Entity::PAYMENT, Constants\Entity::ORDER, Constants\Entity::INVOICE],
        Type::VPA_EDITED                => [Constants\Entity::VPA, Constants\Entity::CUSTOMER, Constants\Entity::BANK_ACCOUNT],
        Type::P2P_CREATED               => [Constants\Entity::P2P, 'sink', 'source'],
        Type::P2P_REJECTED              => [Constants\Entity::P2P, 'sink', 'source'],
        Type::P2P_TRANSFERRED           => [Constants\Entity::P2P, 'sink', 'source'],
        Type::SUBSCRIPTION_ACTIVATED    => [Constants\Entity::SUBSCRIPTION],
        Type::SUBSCRIPTION_OVERDUE      => [Constants\Entity::SUBSCRIPTION],
        Type::SUBSCRIPTION_HALTED       => [Constants\Entity::SUBSCRIPTION],
        // Type::SUBSCRIPTION_EXPIRED      => [Constants\Entity::SUBSCRIPTION],
        Type::ACCOUNT_CREDITED          => [Constants\Entity::PAYMENT, Constants\Entity::BANK_TRANSFER],
    );

    public static function getEntityNamesForEvent($event)
    {
        return self::$data[$event];
    }
}
