<?php

namespace App\Admin;

use App\Base;

class Validator extends Base\Validator
{
    protected static $generators = array(
        'date'
    );

    protected static $loginRules = array(
        'username'  =>      'required|alpha_dash',
        'password'  =>      'required|between:6,50'
    );

    protected static $callbackRules = [
        'SAMLResponse'         =>  'required|string'
    ];

    protected static $apiCallRules = [
        'auth'          =>  'required|in:proxy,admin',
        'mode'          =>  'required|in:test,live',
        'merchant_id'   =>  'sometimes|max:20',
        'content_type'  =>  'sometimes',
        'body'          =>  'sometimes',
        'method'        =>  'required|in:GET,POST,PUT,DELETE,PATCH',
        'file'          =>  'sometimes',
        'file_name'     =>  'sometimes|max:100|required_with:file',
    ];

    protected static $merchantStatsRules = [
        'sort'      =>  'sometimes|in:total_amount,total_count'
    ];

    protected static $changePasswordRules = array(
        'old_password'              => 'required',
        'password'                  => 'required|between:6,50|confirmed',
        'password_confirmation'     => 'required|between:6,50'
    );

    protected static $editRules = array(
        'email'         => 'required|email',
        'name'          => 'required|min:3|alpha_space_num|max:200',
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
            case 'internal':
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
