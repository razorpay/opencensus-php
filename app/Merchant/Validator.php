<?php

namespace App\Merchant;

use App\Base;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        'name'                  => 'required|alpha_space_num|max:200',
        'email'                 => 'required|email|unique:merchants',
    );

    protected static $createSubmerchantRules = array(
        'name'                  => 'required|alpha_space_num|max:200',
        'email'                 => 'sometimes|email',
    );

    protected static $unsetCreateInput = array(
        'captcha'
    );

    protected static $changeEmailRules = array(
        'email'         => 'required|email'
    );

    protected static $changeNameRules = array(
        'name'         => 'required|min:4|alpha_space_num|max:200'
    );

    protected static $loginRules = array(
        'email'     =>      'required|email',
        'password'  =>      'required|between:6,50',
    );

    protected static $terminalRules = array(
        'mode'                                      => 'required|in:test,live',
        'gateway'                                   => '',
        'gateway_merchant_id'                       => '',
        'gateway_merchant_id2'                      => '',
        'gateway_terminal_id'                       => '',
        'gateway_terminal_password'                 => 'confirmed',
        'gateway_terminal_password_confirmation'    => '',
        'gateway_access_code'                       => '',
        'gateway_secure_secret'                     => '',
        'card'                                      => 'required',
        'emi'                                       => '',
        'emi_duration'                              => '',
        'shared'                                    => '',
        'category'                                  => '',
        'gateway_acquirer'                          => 'sometimes|string',
    );

    protected static $banksRules = array(
        'banks'                                      => 'required|array'
    );

    protected static $updateTeamMemberRules = array(
        'role'  => 'required|in:owner,manager,operations,finance,developer'
    );

    protected static $api_dashboard_mappings = array(
            'id'        => 'id',
            'name'      => 'name',
            'email'     => 'email',
            'activated' => 'activated'
    );

    protected static $keyRules = array(
        'id'                    => 'required',
        'merchant_id'           => 'required',
        'delay_roll'            => 'required|in:0,1'
    );

    public static function buildKeyUpdateData($old_key_data)
    {
        return array(
            'delay_roll'    =>  $old_key_data['delay_roll']
        );
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
