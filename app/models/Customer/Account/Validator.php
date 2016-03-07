<?php

namespace Models\Customer;

use Models\Base;
use Models\Customer\Entity;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::EMAIL           =>      'required|email|unique:customers',
        Entity::CONTACT         =>      'required|unique:customers',
        Entity::MERCHANT_ID     =>      'required',
        Entity::NAME            =>      'sometimes',
    );

    protected static $editRules = array(
        Entity::EMAIL           =>      'required|email|unique:customers',
        Entity::CONTACT         =>      'required|unique:customers',
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