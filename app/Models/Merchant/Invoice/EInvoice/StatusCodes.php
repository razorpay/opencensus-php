<?php

namespace RZP\Models\Merchant\Invoice\EInvoice;

final class StatusCodes
{
    public static $retryableErrorCodes = [
        '3043',
        '2146',
        '2147',
        'SYS_5001',
    ];

    public static $invalidDataErrorCodes = [
        '2169',
        '2195',
        '2211',
        '2212',
        '2213',
        '2231',
        '2243',
        '2246',
        '2247',
        '2248',
        '2249',
        '2268',
        '2273',
        '2274',
        '2275',
        '2276',
        '3028',
        '3029',
        '3074',
        '3075',
    ];
}