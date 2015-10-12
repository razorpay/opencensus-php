<?php

namespace Models\Merchant;

use Models\Base;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        'name'                  => 'required|alpha_space|max:200',
        'email'                 => 'required|email|unique:merchants',
        'password'              => 'required|between:7,50|confirmed|numbers|letters',
        'password_confirmation' => 'required|between:7,50',
        'captcha'               => 'required'
    );

    protected static $createValidators = array('captcha');

    protected static $unsetCreateInput = array(
        'captcha'
    );

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

    protected static $changeEmailRules = array(
        'email'         => 'required|email'
    );

    protected static $changePasswordValidators = array('changePassword');

    protected static $terminalRules = array(
        'mode'                                      => 'required|in:test,live',
        'gateway'                                   => '',
        'gateway_merchant_id'                       => '',
        'gateway_terminal_id'                       => '',
        'gateway_terminal_password'                 => 'confirmed',
        'gateway_terminal_password_confirmation'    => '',
        'gateway_access_code'                       => '',
        'gateway_secure_secret'                     => '',
        'card'                                      => 'required',
        'category'                                  => 'sometimes'
    );

    protected static $banksRules = array(
        'banks'                                      => 'required|array'
    );

    protected static $editRules = array(
        'website'                   => 'sometimes|url',
        'category'                  => 'sometimes|numeric|digits:4',
        'international'             => 'sometimes|boolean',
        'billing_label'             => 'sometimes|max:255',
        'transaction_report_email'  => 'sometimes|email|max:255',
        'hold_funds'                => 'sometimes|in:0,1',
        'receipt_email_enabled'     => 'sometimes|in:0,1',
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

    protected function validateCaptcha($input)
    {
        if($_SERVER['HTTP_HOST'] === 'dashboard.razorpay.com' OR $_SERVER['HTTP_HOST'] === 'betadashboard.razorpay.com')
        {
            $captchaResponse = $input['captcha'];

            if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR']) {
                $clientIpAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else {
                $clientIpAddress = $_SERVER['REMOTE_ADDR'];
            }

            $url = "https://www.google.com/recaptcha/api/siteverify?secret=".$_ENV['NOCAPTCHA_SECRET']."&response=".$captchaResponse."&remoteip=".$clientIpAddress;

            $response = \Requests::get($url);

            $output = json_decode($response->body);

            if($output->success !== true)
            {
                $this->addError('captcha', 'Captcha Failed');
            }
        }
    }
}
