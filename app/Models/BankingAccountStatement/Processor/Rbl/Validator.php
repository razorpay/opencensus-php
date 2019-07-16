<?php

namespace RZP\Models\BankingAccountStatement\Processor\Rbl;

use RZP\Base;
use RZP\Models\BankingAccountStatement\Processor\Rbl\RequestResponseFields as F;

class Validator extends Base\Validator
{
    const HEADER    = F::ACC_STMT_DATE_RANGE_RESPONSE . '.' . F::HEADER;
    const BODY      = F::ACC_STMT_DATE_RANGE_RESPONSE . '.' . F::BODY;
    const FIELDS    = self::BODY . '.' . F::TRANSACTION_DETAILS;
    const BALANCE   = self::FIELDS . '.*.' . F::TRANSACTION_BALANCE;
    const SUMMARY   = self::FIELDS . '.*.' . F::TRANSACTION_SUMMARY;
    const AMOUNT    = self::SUMMARY . '.' . F::TRANSACTION_AMOUNT;

    protected static $rblResponseRules = [
        F::ACC_STMT_DATE_RANGE_RESPONSE                     => 'required|array',

        self::HEADER                                        => 'required|array',
        self::HEADER . '.' . F::STATUS                      => 'required|string|in:SUCCESS',

        self::BODY                                          => 'required|array',
        self::BODY . '.' . F::HAS_MORE_DATA                 => 'present|nullable|string',

        self::FIELDS                                        => 'required|array',
        self::FIELDS . '.*.' . F::TRANSACTION_POSTED_DATE   => 'required|date_format:' . Gateway::DATE_FORMAT,
        self::FIELDS . '.*.' . F::TRANSACTION_CATEGORY      => 'required|string',
        self::FIELDS . '.*.' . F::TRANSACTION_ID_RESPONSE   => 'required|string',
        self::FIELDS . '.*.' . F::TRANSACTION_SERIAL_NUMBER => 'required|integer',

        self::BALANCE                                       => 'required|array',
        self::BALANCE . '.' . F::CURRENCY_CODE              => 'required|string|in:INR',
        self::BALANCE . '.' . F::AMOUNT_VALUE               => 'required|numeric|min:0',

        self::SUMMARY                                       => 'required|array',
        self::SUMMARY . '.' . F::INSTRUMENT_ID              => 'present|string',
        self::SUMMARY . '.' . F::TRANSACTION_DATE_RESPONSE  => 'required|date_format:' . Gateway::DATE_FORMAT,
        self::SUMMARY . '.' . F::TRANSACTION_DESCRIPTION    => 'required|string',
        self::SUMMARY . '.' . F::TRANSACTION_TYPE_RESPONSE  => 'required|alpha|max:1',

        self::AMOUNT                                        => 'required|array',
        self::AMOUNT. '.' . F::CURRENCY_CODE                => 'required|string|in:INR',
        self::AMOUNT . '.' . F::AMOUNT_VALUE                => 'required|numeric|min:0',
    ];
}
