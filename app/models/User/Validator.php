<?php

namespace Models\User;

use Models\Base;

class Validator extends Base\Validator
{
    /**
     * The validation rules for creating a user entity.
     * 
     * @var array
     */
    protected static $createRules = array(
        'name'                  => 'required|alpha_space|max:200',
        'email'                 => 'required|email|unique:users',
        'password'              => 'required|between:7,50|confirmed|numbers|letters',
        'password_confirmation' => 'required|between:7,50',
        'captcha'               => 'required'
    );

    /**
     * The input keys to unset once data has been validated.
     * 
     * @var array
     */
    protected static $unsetCreateInput = array('captcha');
}