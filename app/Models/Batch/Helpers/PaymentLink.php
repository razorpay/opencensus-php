<?php

namespace RZP\Models\Batch\Helpers;

use RZP\Models\Batch;
use RZP\Models\Invoice;
use RZP\Models\Customer;

class PaymentLink
{
    /**
     * Gets input array(similar to API request) for entity validation / creation.
     *
     * In bulk import file, things are flattened and not necessarily in format
     * of api request.
     *
     * @param array $entry
     * @param array $params
     *
     * @return array
     */
    public static function getEntityInput(array & $entry, array & $params): array
    {
        // Set partial_payment attribute to false if field comes as null
        // from excel file.

        $partialPayment = $entry[Batch\Header::PARTIAL_PAYMENT];

        $partialPayment = $partialPayment === null ? '0' : (string) $partialPayment;

        // Get draft, sms_notify, email_notify from $params or use default as
        // 1, 0 and 0 respectively.

        $draft       = $params[Invoice\Entity::DRAFT] ?? '1';
        $smsNotify   = $params[Invoice\Entity::SMS_NOTIFY] ?? '0';
        $emailNotify = $params[Invoice\Entity::EMAIL_NOTIFY] ?? '0';

        return [
            Invoice\Entity::DRAFT           => $draft,
            Invoice\Entity::SMS_NOTIFY      => $smsNotify,
            Invoice\Entity::EMAIL_NOTIFY    => $emailNotify,
            Invoice\Entity::TYPE            => Invoice\Type::LINK,
            Invoice\Entity::RECEIPT         => $entry[Batch\Header::INVOICE_NUMBER],
            Invoice\Entity::AMOUNT          => $entry[Batch\Header::AMOUNT],
            Invoice\Entity::DESCRIPTION     => $entry[Batch\Header::DESCRIPTION],
            Invoice\Entity::EXPIRE_BY       => $entry[Batch\Header::EXPIRE_BY],
            Invoice\Entity::PARTIAL_PAYMENT => $partialPayment,

            Invoice\Entity::CUSTOMER        => [
                Customer\Entity::NAME    => $entry[Batch\Header::CUSTOMER_NAME],
                Customer\Entity::CONTACT => $entry[Batch\Header::CUSTOMER_CONTACT],
                Customer\Entity::EMAIL   => $entry[Batch\Header::CUSTOMER_EMAIL],
            ],
        ];
    }
}
