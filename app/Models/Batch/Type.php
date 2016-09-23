<?php

namespace RZP\Models\Batch;

class Type
{
    const REFUND = 'refund';

    // const TYPES = [
    //     self::REFUND,
    // ];

    const INPUT_HEADERS = [
        self::REFUND => [
            'Payment Id',
            'Amount'
        ],
    ];

    const OUTPUT_HEADERS = [
        self::REFUND => [
            'Payment Id',
            'Amount',
            'Refund Id',
            'Refunded Amount',
            'Status',
            'Comment'
        ]
    ];

    public static function exists($type)
    {
        return defined(get_class().'::'.strtoupper($type));
    }

    public static function getInputHeaders($type)
    {
        return self::INPUT_HEADERS[$type];
    }

    public static function getOutputHeaders($type)
    {
        return self::OUTPUT_HEADERS[$type];
    }
}
