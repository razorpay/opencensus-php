<?php

namespace Models\Customer;

use Models\Base;
use Models\Customer\Entity;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::CONTACT         => 'sometimes|contact_syntax',
        Entity::NAME            => 'sometimes|alpha_space_num|max:50',
        Entity::EMAIL           => 'sometimes|email',
        Entity::NOTES           => 'sometimes|notes',
    );

    protected static $editRules = array(
        Entity::CONTACT         => 'sometimes|contact_syntax',
        Entity::NAME            => 'sometimes|alpha_space_num|max:50',
        Entity::ACTIVE          => 'sometimes|in:0,1',
        Entity::EMAIL           => 'sometimes|email',
    );

    public static $contactRules = array(
        Entity::CONTACT => 'required|contact_syntax|phone:AUTO,LENIENT,IN,mobile,fixed_line'
    );
}