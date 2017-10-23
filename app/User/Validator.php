<?php

namespace App\User;

use App\Base;

class Validator extends Base\Validator
{
    protected static $upgradeRules = [
        'business_name'         =>  'required|min:4|alpha_space|max:200'
    ];

    protected static $unsetLoginInput = [
        'password'
    ];

    protected static $preSignupRules = [
        'name'                  => 'sometimes|alpha_space|max:200',
        'contact_mobile'        => 'sometimes|numeric|digits_between:8,11',
    ];
}
