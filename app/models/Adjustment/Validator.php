<?php

namespace Models\Adjustment;

use Models\Base;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::AMOUNT        =>  'required|numeric',
        Entity::CURRENCY      =>  'required|in:INR',
        Entity::DESCRIPTION   =>  'required|min:10',
    );
}