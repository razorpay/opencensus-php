<?php

namespace App\Lead;

use App\Base;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        'email'                 => 'required|email|unique:leads',
    );

}
