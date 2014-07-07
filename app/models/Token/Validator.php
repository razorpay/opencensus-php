<?php
namespace Models\Manager;

use Models\Base;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        'merchant_id' => 'required|numeric');

    private static $TOKEN_LEN = Entity::ID_LENGTH;
}
