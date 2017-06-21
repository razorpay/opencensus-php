<?php

namespace RZP\Gateway\Netbanking\Pnb;

class RefundFields
{
    const ACCOUNT_NUMBER = 'account_number';
    const CURRENCY_CODE  = 'currency_code';
    const SERVICE_OUTLET = 'service_outlet';
    const TXN_TYPE       = 'txn_type';
    const TXN_AMOUNT     = 'txn_amount';

    // alignments
    const LEFT  = 'left';
    const RIGHT = 'right';

    // index wrt to origin, and corresponding alignment
    const INDEX_MAP = [
        self::LEFT => [
            self::ACCOUNT_NUMBER => 1,
            self::CURRENCY_CODE  => 17,
            self::SERVICE_OUTLET => 20,
            self::TXN_TYPE       => 28,
        ],
        self::RIGHT => [
            self::TXN_AMOUNT => 45,
        ]
    ];
}

