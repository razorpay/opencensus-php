<?php

namespace RZP\Models\Event;

use RZP\Models\Payment;
use RZP\Constants\Entity;

class Contains
{
    protected static $data = [
        Type::PAYMENT_AUTHORIZED        => [Entity::PAYMENT],
        Type::PAYMENT_FAILED            => [Entity::PAYMENT],
        Type::ORDER_PAID                => [Entity::PAYMENT, Entity::ORDER],
        Type::INVOICE_PAID              => [Entity::PAYMENT, Entity::ORDER, Entity::INVOICE],
        Type::VPA_EDITED                => [Entity::VPA, Entity::CUSTOMER, Entity::BANK_ACCOUNT],
        Type::P2P_CREATED               => [Entity::P2P, 'sink', 'source'],
        Type::P2P_REJECTED              => [Entity::P2P, 'sink', 'source'],
        Type::P2P_TRANSFERRED           => [Entity::P2P, 'sink', 'source'],
        Type::SUBSCRIPTION_ACTIVATED    => [Entity::SUBSCRIPTION],
        Type::SUBSCRIPTION_OVERDUE      => [Entity::SUBSCRIPTION],
        Type::SUBSCRIPTION_HALTED       => [Entity::SUBSCRIPTION],
        // Type::SUBSCRIPTION_EXPIRED      => [Entity::SUBSCRIPTION],
        Type::VIRTUAL_ACCOUNT_CREDITED  => [Entity::PAYMENT, Entity::BANK_TRANSFER],
        // Type::VIRTUAL_ACCOUNT_CLOSED    => [Entity::VIRTUAL_ACCOUNT],
    ];

    public static function getEntityNamesForEvent($event)
    {
        return self::$data[$event];
    }
}
