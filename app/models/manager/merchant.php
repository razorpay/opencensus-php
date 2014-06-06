<?php 

namespace Models\Manager;

use Models\DAL;
use Models\Service;

class Merchant extends Manager
{
    protected static $registerRules = array(
        'name'                  => 'required|alpha_space|max:200',
        'email'                 => 'required|email|unique:merchants',
        'password'              => 'required|between:6,50|confirmed',
        'password_confirmation' => 'required|between:6,50'
    );

    protected static $loginRules = array(
        'email'     =>      'required|email',
        'password'  =>      'required|between:6,50',
        'remember'  =>      'in:on'
    );

    protected static $unsetRegisterInput = array(
        'password_confirmation'
    );

    protected static $unsetLoginInput = array(
        'password'
    );

    protected static $registerGenerators = array('password');

    protected static $loginGenerators = array('remember');

    protected function generatePassword($input)
    {
        $this->setField(
            'password', \Hash::make($input['password'])
        );
    }

    protected function generateRemember($input)
    {
        $this->setField(
            'remember', isset($input['remember']) && $input['remember'] === 'on'
        );
    }
}