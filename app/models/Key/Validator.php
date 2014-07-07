<?php

namespace Models\Key;

use Models\Base;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        'key_id'        => 'required|alpha_num',
        'merchant_id'   => 'required|numeric',
        'secret'        => 'required|max:100',
        'live'          => 'size:1|in:0,1',
        'active'        => 'size:1|in:0,1'
    );

    protected static $unsetCreateInput = array('key_id');
}