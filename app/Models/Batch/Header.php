<?php

namespace RZP\Models\Batch;

use RZP\Exception\BadRequestException;
use RZP\Error\ErrorCode;

class Header
{
    const INPUT             = 'input';
    const OUTPUT            = 'output';

    /**
     * Refund Headers
     */

    const PAYMENT_ID        = 'Payment Id';
    const AMOUNT            = 'Amount';
    const REFUND_ID         = 'Refund Id';
    const REFUNDED_AMOUNT   = 'Refunded Amount';
    const STATUS            = 'Status';
    const ERROR_CODE        = 'Error Code';
    const ERROR_DESCRIPTION = 'Error Description';

    /**
     * Payment Link Headers
     */

    const INVOICE_NUMBER    = 'invoice_number';
    const CUSTOMER_NAME     = 'customer_name';
    const CUSTOMER_EMAIL    = 'customer_email';
    const CUSTOMER_CONTACT  = 'customer_contact';
    const DESCRIPTION       = 'description';
    const SMS_NOTIFY        = 'sms_notify';
    const EMAIL_NOTIFY      = 'email_notify';
    const EXPIRE_BY         = 'expire_by';
    const PAYMENT_LINK_ID   = 'payment_link_id';

    /**
     * Input and output file headers per type.
     */
    const PER_TYPE = [

        Type::REFUND => [

            self::INPUT => [
                self::PAYMENT_ID,
                self::AMOUNT,
            ],

            self::OUTPUT => [
                self::PAYMENT_ID,
                self::AMOUNT,
                self::REFUND_ID,
                self::REFUNDED_AMOUNT,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
        ],

        Type::PAYMENT_LINK => [

            self::INPUT => [
                self::INVOICE_NUMBER,
                self::CUSTOMER_NAME,
                self::CUSTOMER_EMAIL,
                self::CUSTOMER_CONTACT,
                self::AMOUNT,
                self::DESCRIPTION,
                self::SMS_NOTIFY,
                self::EMAIL_NOTIFY,
                self::EXPIRE_BY,
            ],

            self::OUTPUT => [
                self::INVOICE_NUMBER,
                self::CUSTOMER_NAME,
                self::CUSTOMER_EMAIL,
                self::CUSTOMER_CONTACT,
                self::AMOUNT,
                self::DESCRIPTION,
                self::SMS_NOTIFY,
                self::EMAIL_NOTIFY,
                self::EXPIRE_BY,
                self::PAYMENT_LINK_ID,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
        ],
    ];

    /**
     * Validates headers of batch input file.
     *
     * @param string $type
     * @param array  $keys
     */
    public static function validate(string $type, array $keys)
    {
        $expectedHeaders = self::PER_TYPE[$type][self::INPUT];

        $headersMissing = (bool) array_diff($expectedHeaders, $keys);

        $extraHeadersInInput = (count($expectedHeaders) !== count($keys));

        if ($headersMissing or $extraHeadersInInput)
        {
            throw new BadRequestException(
                        ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_HEADERS);
        }
    }
}
