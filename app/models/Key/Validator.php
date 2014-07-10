<?php

namespace Models\Key;

use Models\Base;
use Models\Key;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        'merchant_id'   => 'numeric',
        'live'          => 'size:1|in:0,1',
        'active'        => 'size:1|in:0,1'
    );
}