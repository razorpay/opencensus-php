<?php

namespace RZP\Models\Batch\Helpers;

use RZP\Models\Batch;
use RZP\Models\Invoice;
use RZP\Models\Customer;
use RZP\Models\Base\Utility;

class PaymentLink
{
    const PARTIAL_PAYMENT_INPUT_MAP = [
        'yes'   => '1',
        'no'    => '0',
        ''      => '0',

        // For backward compatibility with old inputs files formats. To be removed later
        '1'     => '1',
        '0'     => '0',
    ];

    /**
     * Gets input array(similar to API request) for entity validation / creation.
     *
     * In bulk import file, things are flattened and not necessarily in format
     * of api request.
     *
     * @param array $entry
     * @param array $params - Additional parameters besides $entry(file row), e.g. batch configuration/request values
     *
     * @return array
     */
    public static function getEntityInput(array & $entry, array $params = []): array
    {
        // Set partial_payment attribute to false if field comes as null from excel file.
        $partialPayment = array_get($entry, Batch\Header::PARTIAL_PAYMENT);
        $partialPayment = self::PARTIAL_PAYMENT_INPUT_MAP[strtolower(trim($partialPayment))] ?? '0';

        $receipt = $entry[Batch\Header::INVOICE_NUMBER];
        $receipt = empty($receipt) === true ? null : (string) $receipt;

        $expireBy = Utility::parseAsEpoch($entry[Batch\Header::EXPIRE_BY]);

        // Amount needs to be formatted this way as excel reader in cases
        // reads 4255 as 4244.99999. This is known php + excel issue.
        $amount = $entry[Batch\Header::AMOUNT] ?? $entry[Batch\Header::AMOUNT_IN_PAISE];
        $amount = (is_numeric($amount) === true) ? (int) number_format($amount, 0, '', '') : $amount;

        // Get draft, sms_notify, email_notify from $params or use default as
        // 1, 0 and 0 respectively.

        $draft       = $params[Invoice\Entity::DRAFT] ?? '1';
        $smsNotify   = $params[Invoice\Entity::SMS_NOTIFY] ?? '0';
        $emailNotify = $params[Invoice\Entity::EMAIL_NOTIFY] ?? '0';

        // Build customer input

        $customer    = [
            Customer\Entity::NAME    => (string) $entry[Batch\Header::CUSTOMER_NAME],
            Customer\Entity::CONTACT => (string) $entry[Batch\Header::CUSTOMER_CONTACT],
            Customer\Entity::EMAIL   => (string) $entry[Batch\Header::CUSTOMER_EMAIL],
        ];

        $customer = array_filter($customer);

        $input = [
            Invoice\Entity::DRAFT           => $draft,
            Invoice\Entity::SMS_NOTIFY      => $smsNotify,
            Invoice\Entity::EMAIL_NOTIFY    => $emailNotify,
            Invoice\Entity::TYPE            => Invoice\Type::LINK,
            Invoice\Entity::RECEIPT         => $receipt,
            Invoice\Entity::AMOUNT          => $amount,
            Invoice\Entity::DESCRIPTION     => (string) $entry[Batch\Header::DESCRIPTION],
            Invoice\Entity::EXPIRE_BY       => $expireBy,
            Invoice\Entity::PARTIAL_PAYMENT => $partialPayment,
            Invoice\Entity::CUSTOMER        => $customer,
            Invoice\Entity::NOTES           => $entry[Batch\Header::NOTES] ?? [],
        ];

        // Optional: First Payment Min Amount
        if (empty($entry[Batch\Header::FIRST_PAYMENT_MIN_AMOUNT]) === false)
        {
            $firstMinAmount = $entry[Batch\Header::FIRST_PAYMENT_MIN_AMOUNT];
            $firstMinAmount = (is_numeric($amount) === true) ?
                (int) number_format($firstMinAmount, 0, '', '') : $firstMinAmount;

            $input[Invoice\Entity::FIRST_PAYMENT_MIN_AMOUNT] = $firstMinAmount;
        }

        if (empty($entry[Batch\Header::CURRENCY]) === false)
        {
            $input[Invoice\Entity::CURRENCY] = $entry[Batch\Header::CURRENCY];
        }

        return $input;
    }
}
