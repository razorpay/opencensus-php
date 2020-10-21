<?php

namespace RZP\Gateway\Upi\Base;

use RZP\Base\Validator as BaseValidator;

class Validator extends BaseValidator
{
    protected static $sbiRrnCorrectionRules = [
        'count'     => 'required|integer|min:10|max:1000',
        'match'     => 'required|string|min:3',
        'filter'    => 'sometimes|array',
        'filter.*'  => 'sometimes|array|size:3',
    ];
}
