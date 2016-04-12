<?php

namespace Models\Customer;

use Models\Base;
use Models\Customer\Entity;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::CONTACT         => 'required',
        Entity::MERCHANT_ID     => 'required',
        Entity::NAME            => 'sometimes|alpha_space_num|max:50',
        Entity::EMAIL           => 'sometimes|email',
        Entity::NOTES           => 'sometimes|notes',
    );

    protected static $editRules = array(
        Entity::CONTACT         =>      'sometimes',
        Entity::NAME            =>      'sometimes|alhpa_space_num|max:50',
        Entity::ACTIVE          =>      'sometimes|in:0,1',
        Entity::EMAIL           =>      'sometimes|email',
    );

    protected static $createValidators = array(
        Entity::CONTACT
    );

    protected static $editValidators = array(
        Entity::CONTACT
    );

    protected static function validateContact($input)
    {
        ;
    }
}