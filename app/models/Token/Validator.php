<?php
namespace Models\Token;

use Models\Base;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        'merchant_id' => 'required|numeric');
}
