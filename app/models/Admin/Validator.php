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
        'merchant_id'=>     'sometimes|max:20',
        'content_type'=>    'sometimes',
        'body'      =>      'sometimes',
        'method'    =>      'required|in:GET,POST,PUT,DELETE,PATCH',
        'file'      =>      'sometimes',
        'file_name' =>      'sometimes|max:100|required_with:file',
    ];

    protected static $addTagsRules = [
        'tags'      =>      'required|max:255',
    ];

    protected static $addFeaturesRules = [
        'features'    =>    'required|max:255',
    ];

    protected static $merchantStatsRules = [
        'sort'      =>  'sometimes|in:total_amount,successful_txn_count,txn_count'
    ];

    protected static $addTagsValidators = [
        'addTags'
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

    protected static $changeBankDetailsRules = array(
        'bank_name'             => 'required|alpha_space|max:255',
        'bank_account_number'   => 'required|alpha_num|between:5,20',
        'bank_account_name'     => 'required|alpha_space_num|max:40',
        'bank_account_type'     => 'required|alpha_space|max:20',
        'bank_branch'           => 'required|max:255',
        'bank_branch_ifsc'      => 'required|alpha_num|max:11',
        'bank_beneficiary_address1' => 'required|max:30',
        'bank_beneficiary_address2' => 'max:30',
        'bank_beneficiary_address3' => 'max:30',
        'bank_beneficiary_city'      => 'required|max:30',
        'bank_beneficiary_state'     => 'required|max:2',
        'bank_beneficiary_pin'       => 'required|integer|digits:6'
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

    protected function validateAddTags($input)
    {
        $tags = explode(',', $input['tags']);
        if (count($tags) < 1)
        {
            $this->addError('tags', 'Atleast one tag must be specified');
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
