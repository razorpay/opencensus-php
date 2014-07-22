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
        'password_confirmation' => 'required|between:6,50',
        '_token'                => 'required'
    );

    protected static $loginRules = array(
        'email'     =>      'required|email',
        'password'  =>      'required|between:6,50',
        'remember'  =>      'in:on',
        '_token'    =>      'required'
    );

    protected static $unsetRegisterInput = array(
        'password_confirmation',
        '_token'
    );

    protected static $unsetLoginInput = array(
        'password',
        '_token'
    );

    protected static $registerGenerators = array('id', 'confirm_token', 'password');

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

    /**
     * Generates UUid ID
     */
    public function generateId()
    {
        $this->setField(
            'id', bin2hex(openssl_random_pseudo_bytes(24/2)));
    }

    /**
     * Generates Confirmation token
     */
    public function generateConfirmToken()
    {
        $this->setField(
            'confirm_token', bin2hex(openssl_random_pseudo_bytes(32/2)));
    }
}