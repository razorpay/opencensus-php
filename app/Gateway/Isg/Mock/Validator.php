<?php

namespace RZP\Gateway\Isg\Mock;

use RZP\Base;
use RZP\Gateway\Isg\Field;

class Validator extends Base\Validator
{
    protected static $verifyRules = [
        Field::TRANSACTION_ID              => 'sometimes|alpha_num',
        Field::PRIMARY_ID                  => 'required|alpha_num',
        Field::TERMINAL_ID                 => 'required|numeric|digits:8',
        Field::MERCHANT_PAN                => 'sometimes|numeric|digits:16',
        Field::TRANSACTION_DATE            => 'required|string|date_format:Ymd',
        Field::TRANSACTION_AMOUNT          => 'required|string',
    ];
}
