<?php

namespace RZP\Models\Merchant\Product\TncMap\Acceptance;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Constants::ACCEPTED => 'required|boolean|in:1',
    ];
}
