<?php

namespace RZP\Models\Transaction;

use RZP\Models\Base;

class Validator extends Base\Validator
{
    protected static $uniqueEntityIdRules = array(
        Entity::ENTITY_ID       => 'required|alpha_num|size:14|unique:transactions');
}
