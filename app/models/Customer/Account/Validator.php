<?php

namespace Models\Customer;

use Models\Base;
use Models\Customer\Entity;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::CONTACT         =>      'required',
        Entity::MERCHANT_ID     =>      'required',
        Entity::NAME            =>      'sometimes',
        Entity::EMAIL           =>      'sometimes',
    );

    protected static $editRules = array(
        Entity::CONTACT         =>      'sometimes',
        Entity::NAME            =>      'sometimes',
        Entity::ACTIVE          =>      'sometimes|in:0,1',
        Entity::EMAIL           =>      'sometimes',
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