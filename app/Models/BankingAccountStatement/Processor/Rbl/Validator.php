<?php

namespace RZP\Models\BankingAccountStatement\Processor\Rbl;

use RZP\Base;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\BankingAccountStatement\Processor\Rbl\RequestResponseFields as F;

class Validator extends Base\Validator
{
    const HEADER    = F::PAYMENT_GENERIC_RESPONSE . '.' . F::HEADER;
    const BODY      = F::PAYMENT_GENERIC_RESPONSE . '.' . F::BODY;
    const FIELDS    = self::BODY . '.' . F::TRANSACTION_DETAILS;
    const BALANCE   = self::FIELDS . '.*.' . F::TRANSACTION_BALANCE;
    const SUMMARY   = self::FIELDS . '.*.' . F::TRANSACTION_SUMMARY;
    const AMOUNT    = self::SUMMARY . '.' . F::TRANSACTION_AMOUNT;

    protected static $rblResponseRules = [
        F::PAYMENT_GENERIC_RESPONSE                         => 'required|array',

        self::HEADER                                        => 'required|array',
        self::HEADER . '.' . F::STATUS                      => 'required|string|in:SUCCESS',

        self::BODY                                          => 'required|array',
        self::BODY . '.' . F::HAS_MORE_DATA                 => 'present|nullable|string',

        self::FIELDS                                        => 'present|array',
        self::FIELDS . '.*.' . F::TRANSACTION_POSTED_DATE   => 'required|date_format:' . Gateway::DATE_FORMAT,
        self::FIELDS . '.*.' . F::TRANSACTION_CATEGORY      => 'required|string',
        self::FIELDS . '.*.' . F::TRANSACTION_ID_RESPONSE   => 'required|string',
        self::FIELDS . '.*.' . F::TRANSACTION_SERIAL_NUMBER => 'required|integer',

        self::BALANCE                                       => 'required|array',
        self::BALANCE . '.' . F::CURRENCY_CODE              => 'required|string|in:INR',
        self::BALANCE . '.' . F::AMOUNT_VALUE               => 'required|numeric',

        self::SUMMARY                                       => 'required|array',
        self::SUMMARY . '.' . F::INSTRUMENT_ID              => 'present|string',
        self::SUMMARY . '.' . F::TRANSACTION_DATE_RESPONSE  => 'required|date_format:' . Gateway::DATE_FORMAT,
        self::SUMMARY . '.' . F::TRANSACTION_DESCRIPTION    => 'required|string',
        self::SUMMARY . '.' . F::TRANSACTION_TYPE_RESPONSE  => 'required|alpha|max:1',

        self::AMOUNT                                        => 'required|array',
        self::AMOUNT. '.' . F::CURRENCY_CODE                => 'required|string|in:INR',
        self::AMOUNT . '.' . F::AMOUNT_VALUE                => 'required|numeric|min:0',
    ];

    const RESPONSE_HEADER        = F::FETCH_ACCOUNT_STATEMENT_RESPONSE . '.' . F::HEADER;
    const ACCOUNT_STATEMENT_DATA = F::FETCH_ACCOUNT_STATEMENT_RESPONSE . '.' . F::ACCOUNT_STATEMENT_DATA;
    const FILE_DATA              = self::ACCOUNT_STATEMENT_DATA . '.' . F::FILE_DATA;

    protected static $rblStatementFetchResponseV2Rules = [
        F::FETCH_ACCOUNT_STATEMENT_RESPONSE                    => 'required',

        self::RESPONSE_HEADER                                  => 'required|array',
        self::RESPONSE_HEADER . '.' . F::STATUS                => 'required|string|in:Success,Failure',
        self::RESPONSE_HEADER . '.' . F::STATUS_DESCRIPTION    => 'sometimes|string',

        self::ACCOUNT_STATEMENT_DATA                           => 'required|array',
    ];

    protected static $rblStatementFetchResponseV2RecordRules = [
        F::TRANSACTION_ID_RESPONSE   => 'required|string',
        F::TRANSACTION_SERIAL_NUMBER => 'required|integer',
        F::TRANSACTION_DATE          => 'required',
        F::TRANSACTION_POSTED_DATE   => 'required|filled|string',
        F::TRANSACTION_CATEGORY      => 'required|string',
        F::TRANSACTION_TYPE          => 'required|alpha|max:1',
        F::TRANSACTION_DESCRIPTION   => 'required|string',
        F::TRANSACTION_AMOUNT        => 'required|numeric|min:0',
        F::TRANSACTION_BALANCE       => 'required|numeric',
    ];

    protected static $rblStatementFetchResponseValidators = [
        'next_key'
    ];

    protected function validateNextKey(array $input)
    {
        $header = $input[F::FETCH_ACCOUNT_STATEMENT_RESPONSE][F::HEADER];

        // Next key is sent in response by Mozart as mandatory field.
        // If bank doesn't send next key, then we get null value for this field.
        if (array_key_exists(F::NEXT_KEY, $header) === false)
        {
            throw new BadRequestValidationFailureException(
                "Next Key doesn't exist",
                F::NEXT_KEY,
                $header
            );
        }

        if (is_string($header[F::NEXT_KEY]) === false)
        {
            // Next key can be null in case of no records found.
            if ((array_key_exists(F::STATUS_DESCRIPTION, $header) === true) and
                ($header[F::STATUS_DESCRIPTION] === "No Records Found"))
            {
                return;
            }

            throw new BadRequestValidationFailureException(
                'Next Key is invalid',
                F::NEXT_KEY,
                $header
            );
        }
    }
}
