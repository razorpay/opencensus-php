<?php

namespace RZP\Models\Batch\Helpers;

use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Models\Customer;
use RZP\Models\Batch\Header;

class RecurringCharge
{
    const ORDER_NOTES_PREFIX    = 'notes_';
    const ORDER_NOTES_MAX_COUNT = 5;

    public static function getPaymentInput(array $entry, Order\Entity $order): array
    {
        $customerId = $entry[Header::RECURRING_CHARGE_CUSTOMER_ID];

        $customer = (new Customer\Service)->fetch($customerId);

        $request = [
            Payment\Entity::TOKEN       => $entry[Header::RECURRING_CHARGE_TOKEN],
            Payment\Entity::AMOUNT      => $entry[Header::RECURRING_CHARGE_AMOUNT],
            Payment\Entity::CURRENCY    => $entry[Header::RECURRING_CHARGE_CURRENCY],
            Payment\Entity::DESCRIPTION => $entry[Header::RECURRING_CHARGE_DESCRIPTION],
            Payment\Entity::EMAIL       => $customer[Customer\Entity::EMAIL],
            Payment\Entity::CONTACT     => $customer[Customer\Entity::CONTACT],
            Payment\Entity::CUSTOMER_ID => $customer[Customer\Entity::ID],
            Payment\Entity::ORDER_ID    => $order->getPublicId(),
            Payment\Entity::RECURRING   => '1',
        ];

        return $request;
    }

    public static function getOrderInput(array $entry): array
    {
        $request = [
            Order\Entity::AMOUNT          => $entry[Header::RECURRING_CHARGE_AMOUNT],
            Order\Entity::CURRENCY        => $entry[Header::RECURRING_CHARGE_CURRENCY],
            Order\Entity::RECEIPT         => $entry[Header::RECURRING_CHARGE_RECEIPT],
            Order\Entity::PAYMENT_CAPTURE => true,
            Order\Entity::NOTES           => $entry[HEADER::NOTES] ?? [],
        ];

        return $request;
    }
}
