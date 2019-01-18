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

    public static function getPaymentInput(array $entry, Order\Entity $order, Customer\Entity $customer): array
    {
        $customerId = $entry[Header::RECURRING_CHARGE_CUSTOMER_ID];

        $request = [
            Payment\Entity::TOKEN       => $entry[Header::RECURRING_CHARGE_TOKEN],
            Payment\Entity::AMOUNT      => $entry[Header::RECURRING_CHARGE_AMOUNT],
            Payment\Entity::CURRENCY    => $entry[Header::RECURRING_CHARGE_CURRENCY],
            Payment\Entity::DESCRIPTION => $entry[Header::RECURRING_CHARGE_DESCRIPTION],
            Payment\Entity::EMAIL       => $customer->getEmail(),
            Payment\Entity::CONTACT     => $customer->getContact(),
            Payment\Entity::CUSTOMER_ID => $customer->getPublicId(),
            Payment\Entity::ORDER_ID    => $order->getPublicId(),
            Payment\Entity::RECURRING   => '1',
            Payment\Entity::NOTES       => $entry[HEADER::NOTES] ?? []
        ];

        return $request;
    }

    public static function getOrderInput(array $entry): array
    {
        $receipt = $entry[Header::RECURRING_CHARGE_RECEIPT];

        $receipt = empty($receipt) === true ? null : (string) $receipt;

        $request = [
            Order\Entity::AMOUNT          => $entry[Header::RECURRING_CHARGE_AMOUNT],
            Order\Entity::CURRENCY        => $entry[Header::RECURRING_CHARGE_CURRENCY],
            Order\Entity::RECEIPT         => $receipt,
            Order\Entity::PAYMENT_CAPTURE => true,
            Order\Entity::NOTES           => $entry[HEADER::NOTES] ?? [],
        ];

        return $request;
    }
}
