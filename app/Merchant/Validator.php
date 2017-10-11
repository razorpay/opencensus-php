<?php

namespace App\Merchant;

use App\Base;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        'name'                  => 'sometimes|alpha_space_num|max:200',
        'email'                 => 'required|email|unique:merchants',
    );

    protected static $createSubmerchantRules = array(
        'name'                  => 'required|alpha_space_num|max:200',
        'email'                 => 'sometimes|email',
    );

    protected static $createSubmerchantUserRules = array(
        'id'                    => 'required|alpha_num',
        'password'              => 'required|between:7,50|confirmed|numbers|letters',
        'password_confirmation' => 'required|between:7,50',
        'email'                 => 'required|email|unique:users',
    );

    protected static $unsetCreateInput = array(
        'captcha'
    );

    protected static $changeEmailRules = array(
        'email'         => 'required|email'
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
        'gateway_client_certificate'                => 'sometimes|file|mimetypes:application/octet-stream',
        'card'                                      => 'required',
        'emi'                                       => '',
        'emi_duration'                              => '',
        'shared'                                    => '',
        'international'                             => '',
        'category'                                  => '',
        'gateway_acquirer'                          => 'sometimes|string',
    );

    protected static $banksRules = array(
        'banks'                                      => 'required|array'
    );

    protected static $updateTeamMemberRules = array(
        'role'  => 'required|in:owner,manager,operations,finance,support,admin,sellerapp'
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
