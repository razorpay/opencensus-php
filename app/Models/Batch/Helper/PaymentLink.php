<?php

namespace RZP\Models\Batch\Helper;

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
     *
     * @return array
     */
    public static function getEntityInput(array & $entry): array
    {
        return [

            // All payment links are getting created in draft state.
            Invoice\Entity::DRAFT        => '1',

            Invoice\Entity::TYPE         => Invoice\Type::LINK,

            Invoice\Entity::RECEIPT      => $entry[Batch\Header::INVOICE_NUMBER],
            Invoice\Entity::CUSTOMER     => [
                Customer\Entity::NAME    => $entry[Batch\Header::CUSTOMER_NAME],
                Customer\Entity::CONTACT => $entry[Batch\Header::CUSTOMER_CONTACT],
                Customer\Entity::EMAIL   => $entry[Batch\Header::CUSTOMER_EMAIL],
            ],
            Invoice\Entity::AMOUNT       => $entry[Batch\Header::AMOUNT],
            Invoice\Entity::DESCRIPTION  => $entry[Batch\Header::DESCRIPTION],
            Invoice\Entity::SMS_NOTIFY   => (string) $entry[Batch\Header::SMS_NOTIFY],
            Invoice\Entity::EMAIL_NOTIFY => (string) $entry[Batch\Header::EMAIL_NOTIFY],
            Invoice\Entity::EXPIRE_BY    => $entry[Batch\Header::EXPIRE_BY],
        ];
    }
}
