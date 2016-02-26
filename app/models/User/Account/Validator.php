<?php

namespace Models\User;

use Models\Base;
use Models\User\Entity;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::EMAIL           =>      'required|email',
        Entity::CONTACT         =>      'required',
        Entity::MERCHANT_ID     =>      'required|unique:merchants',
    );

    protected static $createValidators = array(
        Entity::CONTACT
    );

    protected static function validateContact($input)
    {
        
    }
}