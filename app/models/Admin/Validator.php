<?php

namespace Models\Admin;

use Models\Base;

class Validator extends Base\Validator
{
    protected static $generators = array(
        'date'
    );

    protected static $loginRules = array(
        'username'  =>      'required|alpha_dash',
        'password'  =>      'required|between:6,50'
    );

    protected static $apiCallRules = [
        'auth'      =>      'required|in:proxy,admin',
        'mode'      =>      'required|in:test,live',
        'url'       =>      'required',
        'merchant_id'=>     'sometimes|max:20',
        'content_type'=>    'sometimes',
        'body'      =>      'sometimes'
    ];

    protected static $createRules = array(
        'name'                  => 'required|between:3,100|alpha_space',
        'username'              => 'required|between:3,50|alpha_dash|unique:admins',
        'password'              => 'required|between:6,50|confirmed',
        'password_confirmation' => 'required|between:6,50',
        'email'                 => 'required|email|unique:admins',
        'superadmin'            => 'required|in:1,0'
    );

    protected static $changePasswordRules = array(
        'old_password'              => 'required',
        'password'                  => 'required|between:6,50|confirmed',
        'password_confirmation'     => 'required|between:6,50'
    );

    protected static $apiCallValidators = array('apiCall');

    protected static $changePasswordValidators = array('changePassword');

    protected function validateApiCall($input)
    {
        switch ($input['auth']) {
            case 'proxy':
                if (!isset($input['merchant_id']))
                {
                    $this->addError('merchant_id', 'Merchant Id must be specified for Proxy Auth');
                }
                break;

            case 'admin':
                break;

            default:
                $this->addError('auth', 'Invalid Auth Method Specified');
                break;
        }
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

    protected static $getBeneficiaryRules = array(
        'date' => 'sometimes|date_format:Y-m-d',
    );
}
