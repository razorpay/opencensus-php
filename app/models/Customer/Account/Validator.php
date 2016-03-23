<?php

namespace Models\Customer;

use Models\Base;
use Models\Customer\Entity;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::EMAIL           =>      'sometimes|email',
        Entity::CONTACT         =>      'required|unique:customers',
        Entity::MERCHANT_ID     =>      'required',
        Entity::NAME            =>      'sometimes',
    );

    protected static $editRules = array(
        Entity::EMAIL           =>      'sometimes|email',
        Entity::CONTACT         =>      'sometimes|unique:customers',
        Entity::NAME            =>      'sometimes',
        Entity::ACTIVE          =>      'sometimes|in:0,1'
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