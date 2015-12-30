<?php

namespace Models\User;

use Models\Base;

class Validator extends Base\Validator
{
    protected static $loginRules = array(
        'email'     =>      'required|email',
        'password'  =>      'required|between:6,50',
    );

    protected static $unsetLoginInput = array(
        'password'
    );

    protected static $changePasswordRules = array(
        'old_password'              => 'required',
        'password'                  => 'required|between:7,50|confirmed|numbers|letters',
        'password_confirmation'     => 'required|between:7,50'
    );

    protected static $changePasswordValidators = array('changePassword');

    protected function validateChangePassword($input)
    {
        $oldPassword = $input['old_password'];

        $password = $this->entity->password;

        if (\Hash::check($oldPassword, $password) === false)
        {
            $this->addError('old_password', 'Incorrect password');
        }
    }
}
