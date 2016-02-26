<?php

namespace Models\User;

use Models\Base;
use Models\User\Entity;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::EMAIL           =>      'required|email|unique:users',
        Entity::CONTACT         =>      'required|unique:users',
        Entity::MERCHANT_ID     =>      'required',
        Entity::NAME            =>      'sometimes',
    );

    protected static $editRules = array(
        Entity::EMAIL           =>      'required|email|unique:users',
        Entity::CONTACT         =>      'required|unique:users',
        Entity::NAME            =>      'sometimes',
    );

    protected static $createValidators = array(
        Entity::CONTACT
    );

    protected static $editValidators = array(
        Entity::CONTACT
    );

    protected static function validateContact($input)
    {
        
    }
}