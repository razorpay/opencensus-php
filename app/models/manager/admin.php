<?php 

namespace Models\Manager;

use Models\DAL;
use Models\Service;

class Admin extends Manager
{
    protected static $loginRules = array(
        'username'  =>      'required|alpha_dash',
        'password'  =>      'required|between:6,50',
        '_token'    =>      'required'
    );
    
    protected static $registerRules = array(
        'name'                  => 'required|between:3,100|alpha_space',
        'username'              => 'required|between:3,50|alpha_dash|unique:admins',
        'password'              => 'required|between:6,50|confirmed',
        'password_confirmation' => 'required|between:6,50',
        'email'                 => 'required|email|unique:admins',
        'superadmin'            => 'required|in:1,0',
        '_token'                => 'required'
    );

    protected static $passwordRules = array(
        'old_password'          => 'required',
        'password'              => 'required|between:6,50|confirmed',
        'password_confirmation' => 'required|between:6,50',
        '_token'                => 'required');

    protected static $unsetLoginInput = array(
        'password',
        '_token'
    );

    protected static $unsetRegisterInput = array(
        '_token'
    );

    protected static $unsetPasswordInput = array(
        'password_confirmation',
        '_token'
    );

    protected static $registerGenerators = array('password');

    protected static $passwordGenerators = array('password');


    protected function generatePassword($input)
    {
        $this->setField(
            'password', \Hash::make($input['password'])
        );
    }
}