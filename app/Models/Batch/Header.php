<?php

namespace RZP\Models\Batch;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

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

    const INVOICE_NUMBER      = 'Invoice Number';
    const CUSTOMER_NAME       = 'Customer Name';
    const CUSTOMER_EMAIL      = 'Customer Email';
    const CUSTOMER_CONTACT    = 'Customer Contact';
    const DESCRIPTION         = 'Description';
    const EXPIRE_BY           = 'Expire By';
    const PARTIAL_PAYMENT     = 'Partial Payment';
    const PAYMENT_LINK_ID     = 'Payment Link Id';
    const SHORT_URL           = 'Payment Link Short URL';

    /**
     * Irctc Headers
     */
    const MERCHANT_REFERENCE = 'merchant_reference';
    const REFUND_TYPE        = 'refund_type';
    const REFUND_AMOUNT      = 'refund_amount';
    const CANCELLATION_DATE  = 'cancellation_date';
    const PAYMENT_AMOUNT     = 'payment_amount';
    const CANCELLATION_ID    = 'cancellation_id';
    const PAYMENT_DATE       = 'payment_date';
    const REFUND_DATE        = 'refund_date';

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
                self::EXPIRE_BY,
                self::PARTIAL_PAYMENT,
            ],

            self::OUTPUT => [
                self::INVOICE_NUMBER,
                self::CUSTOMER_NAME,
                self::CUSTOMER_EMAIL,
                self::CUSTOMER_CONTACT,
                self::AMOUNT,
                self::DESCRIPTION,
                self::EXPIRE_BY,
                self::PARTIAL_PAYMENT,
                self::STATUS,
                self::PAYMENT_LINK_ID,
                self::SHORT_URL,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
        ],

        Type::IRCTC_REFUND => [

            self::INPUT => [
                self::MERCHANT_REFERENCE,
                self::REFUND_TYPE,
                self::REFUND_AMOUNT,
                self::PAYMENT_ID,
                self::CANCELLATION_DATE,
                self::PAYMENT_AMOUNT,
                self::CANCELLATION_ID,
            ],

            self::OUTPUT => [
                self::MERCHANT_REFERENCE,
                self::REFUND_TYPE,
                self::REFUND_AMOUNT,
                self::PAYMENT_ID,
                self::STATUS,
                self::REFUND_DATE,
                self::REFUND_ID,
                self::CANCELLATION_DATE,
                self::PAYMENT_AMOUNT,
                self::CANCELLATION_ID,
                self::ERROR_DESCRIPTION,
            ],
        ],

        Type::IRCTC_SETTLEMENT => [

            self::INPUT => [
                self::PAYMENT_ID,
                self::PAYMENT_AMOUNT,
                self::PAYMENT_DATE,
                self::MERCHANT_REFERENCE,
            ],

            self::OUTPUT => [
                self::PAYMENT_ID,
                self::PAYMENT_AMOUNT,
                self::PAYMENT_DATE,
                self::MERCHANT_REFERENCE,
                self::STATUS,
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
     *
     * @throws BadRequestException
     */
    public static function validate(string $type, array $keys)
    {
        $expectedHeaders = self::PER_TYPE[$type][self::INPUT];

        $headersMissing = (bool) array_diff($expectedHeaders, $keys);

        $extraHeadersInInput = (count($expectedHeaders) !== count($keys));

        if (($headersMissing === true) or ($extraHeadersInInput === true))
        {
            throw new BadRequestException(
                        ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_HEADERS,
                        null,
                        [
                            'expected_headers'  => $expectedHeaders,
                            'input_headers'     => $keys,
                            'headers_missing'   => $headersMissing,
                            'extra_headers'     => $extraHeadersInInput,
                        ]);
        }
    }

    public static function getInputHeadersForType(string $type): array
    {
        return self::PER_TYPE[$type][self::INPUT];
    }

    public static function getOutputHeadersForType(string $type): array
    {
        return self::PER_TYPE[$type][self::OUTPUT];
    }
}
