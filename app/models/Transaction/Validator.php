<?php

namespace Models\Transaction;

use Models\Base;

class Validator extends Base\Validator
{
    protected static $uniqueEntityIdRules = array(
        Entity::ENTITY_ID       => 'required|alpha_num|size:14|unique:transactions');
}
