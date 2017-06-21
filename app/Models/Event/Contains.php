<?php

namespace RZP\Models\Event;

use RZP\Models\Payment;
use RZP\Constants\Entity as Constants;

class Contains
{
    protected static $data = [
        Type::PAYMENT_AUTHORIZED        => [Constants::PAYMENT],
        Type::PAYMENT_FAILED            => [Constants::PAYMENT],
        Type::PAYMENT_CAPTURED          => [Constants::PAYMENT],
        Type::ORDER_PAID                => [Constants::PAYMENT, Constants::ORDER],
        Type::INVOICE_PAID              => [Constants::PAYMENT, Constants::ORDER, Constants::INVOICE],
        Type::VPA_EDITED                => [Constants::VPA, Constants::CUSTOMER, Constants::BANK_ACCOUNT],
        Type::P2P_CREATED               => [Constants::P2P, 'sink', 'source'],
        Type::P2P_REJECTED              => [Constants::P2P, 'sink', 'source'],
        Type::P2P_TRANSFERRED           => [Constants::P2P, 'sink', 'source'],
        Type::SUBSCRIPTION_ACTIVATED    => [Constants::SUBSCRIPTION],
        Type::SUBSCRIPTION_OVERDUE      => [Constants::SUBSCRIPTION],
        Type::SUBSCRIPTION_HALTED       => [Constants::SUBSCRIPTION],
        Type::SUBSCRIPTION_CHARGED      => [Constants::PAYMENT, Constants::SUBSCRIPTION],
        // Type::SUBSCRIPTION_EXPIRED      => [Constants::SUBSCRIPTION],
    ];

    public static function getEntityNamesForEvent($event)
    {
        return self::$data[$event];
    }
}
