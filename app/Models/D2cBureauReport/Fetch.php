<?php

namespace RZP\Models\D2cBureauReport;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS         => [
            Entity::MERCHANT_ID          => 'filled|alpha_num|size:14',
            Entity::USER_ID              => 'filled|alpha_num|size:14',
            Entity::D2C_BUREAU_DETAIL_ID => 'filled|alpha_num|size:14',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::MERCHANT_ID,
            Entity::USER_ID,
            Entity::D2C_BUREAU_DETAIL_ID,
        ]
    ];
}
