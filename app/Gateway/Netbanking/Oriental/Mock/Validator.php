<?php

namespace RZP\Gateway\Netbanking\Oriental\Mock;

use RZP\Base;
use RZP\Gateway\Netbanking\Oriental\RequestFields;

final class Validator extends Base\Validator
{
    protected static $authRules = [
        RequestFields::RETURN_URL   => 'required|string|url',
        RequestFields::CATEGORY_ID  => 'required|string|in:400',
        RequestFields::QUERY_STRING => 'required|string'
    ];
}
