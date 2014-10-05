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
    );

    protected static $changePasswordRules = array(
        'old_password'              => 'required',
        'password'                  => 'required|between:6,50|confirmed',
        'password_confirmation'     => 'required|between:6,50'
    );

    protected static $changePasswordValidators = array('changePassword');

    protected static $terminalRules = array(
        'gateway'                                   => 'required',
        'gateway_merchant_id'                       => 'required',
        'gateway_terminal_id'                       => 'required',
        'gateway_terminal_password'                 => 'required|confirmed',
        'gateway_terminal_password_confirmation'    => 'required'
    );

    protected static $unsetLoginInput = array(
        'password'
    );

    protected static $unsetTerminalInput = array(
        'gateway_terminal_password_confirmation'
    );

    protected static $api_dashboard_mappings = array(
            'id'        => 'id',
            'name'      => 'name',
            'email'     => 'email',
            'activated' => 'activated'
    );

    protected static $registerGenerators = array('id', 'confirm_token', 'password');

    protected static $loginGenerators = array('remember');

    protected static $passwordGenerators = array('password');

    protected function generatePassword($input)
    {
        $this->setField(
            'password', \Hash::make($input['password'])
        );
    }

    protected function generateRemember($input)
    {
        $remember = (isset($input['remember'])) and
                    ($input['remember'] === 'on');

        $this->setField('remember', $remember);
    }

    /**
     * Generates UUid ID
     */
    public function generateId()
    {
        $this->setField('id', bin2hex(openssl_random_pseudo_bytes(24/2)));
    }

    /**
     * Generates Confirmation token
     */
    public function generateConfirmToken()
    {
        $this->setField(
            'confirm_token', bin2hex(openssl_random_pseudo_bytes(32/2)));
    }

    protected function validateChangePassword($input)
    {
        $oldPassword = $input['old_password'];

        $password = $this->entity->password;

        if (\Hash::check($oldPassword, $password) === false)
        {
            $this->addError('old_password', 'Incorrect password');
        }
    }

    public static function checkAPIMatch($merchant, $api_response)
    {
        foreach (static::$api_dashboard_mappings as $key => $value)
        {
            if ($api_response[$key] !== $merchant[$value])
            {
                throw new \Exception(
                    'Merchant data mismatch with api for '.$merchant['id'].' at '.$key);
            }
        }
    }
}