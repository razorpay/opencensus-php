<?php

namespace Models\Adjustment;

use Models\Base;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::AMOUNT        =>  'required|integer',
        Entity::CURRENCY      =>  'sometimes|in:INR',
        Entity::DESCRIPTION   =>  'required|min:10',
    );
}