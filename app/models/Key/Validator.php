<?php

namespace Models\Key;

use Models\Base;
use Models\Key;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        'merchant_id'   => 'alpha_num|size:24',
        'live'          => 'size:1|in:0,1',
        'active'        => 'size:1|in:0,1'
    );
}