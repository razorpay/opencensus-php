<?php

namespace RZP\Models\Adjustment;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::AMOUNT        =>  'required|integer',
        Entity::CURRENCY      =>  'required|in:INR',
        Entity::DESCRIPTION   =>  'required|min:10',
    );
}