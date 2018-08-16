<?php

namespace RZP\Models\Batch\Helpers;

use RZP\Models\Card;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Models\Customer;
use RZP\Models\Batch\Header;

class DirectDebit
{
    const ORDER_NOTES_PREFIX    = 'notes_';
    const ORDER_NOTES_MAX_COUNT = 5;

    public static function getPaymentInput(array $row, Order\Entity $order, Customer\Entity $customer): array
    {
        $request = [
            Payment\Entity::METHOD         => Payment\Method::CARD,
            Payment\Entity::AMOUNT         => $row[Header::DIRECT_DEBIT_AMOUNT],
            Payment\Entity::EMAIL          => $row[Header::DIRECT_DEBIT_EMAIL],
            Payment\Entity::CONTACT        => $row[Header::DIRECT_DEBIT_CONTACT],
            Payment\Entity::CURRENCY       => $row[Header::DIRECT_DEBIT_CURRENCY],
            Payment\Entity::DESCRIPTION    => $row[Header::DIRECT_DEBIT_DESCRIPTION],
            Payment\Entity::CARD           => [
                Card\Entity::NUMBER        =>  $row[Header::DIRECT_DEBIT_CARD_NUMBER],
                Card\Entity::EXPIRY_MONTH  =>  $row[Header::DIRECT_DEBIT_EXPIRY_MONTH],
                Card\Entity::EXPIRY_YEAR   =>  $row[Header::DIRECT_DEBIT_EXPIRY_YEAR],
                Card\Entity::NAME          =>  $row[Header::DIRECT_DEBIT_CARDHOLDER_NAME],
            ],
            Payment\Entity::AUTH_TYPE      => Payment\AuthType::SKIP,
            Payment\Entity::CUSTOMER_ID    => $customer->getPublicId(),
            Payment\Entity::ORDER_ID       => $order->getPublicId(),
        ];

        return $request;
    }

    public static function getOrderInput(array $row): array
    {
        $request = [
            Order\Entity::AMOUNT          => $row[Header::DIRECT_DEBIT_AMOUNT],
            Order\Entity::CURRENCY        => $row[Header::DIRECT_DEBIT_CURRENCY],
            Order\Entity::RECEIPT         => $row[Header::DIRECT_DEBIT_RECEIPT],
            Order\Entity::PAYMENT_CAPTURE => true,
            Order\Entity::NOTES           => $row[HEADER::NOTES] ?? [],
        ];

        return $request;
    }

    public static function getCustomerInput(array $row): array
    {
        $request = [
            Customer\Entity::EMAIL   => $row[Header::DIRECT_DEBIT_EMAIL],
            Customer\Entity::CONTACT => $row[Header::DIRECT_DEBIT_CONTACT],
        ];

        return $request;
    }
}
