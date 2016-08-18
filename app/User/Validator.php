<?php

namespace App\User;

use App\Base;

class Validator extends Base\Validator
{
    public static $createRules = array(
        // Individual Name
        'name'                  => 'required|alpha_space|max:200',
        // Merchant Business Name
        'business_name'         => 'alpha_space_num|max:200',

        // Mobile Number (optional). Saved in merchant_details
        'contact_mobile'        => 'sometimes|numeric|digits_between:8,11',

        // Merchant email. Used for both merchant and user accounts
        'email'                 => 'required|email|unique:users',
        'password'              => 'required|between:7,50|confirmed|numbers|letters',
        'password_confirmation' => 'required|between:7,50',
        'captcha'               => 'required',
        'invitation'            => 'max:40',
        'ref'                   => 'sometimes|max:255'
    );

    protected static $upgradeRules = [
        'business_name'         =>  'required|min:4|alpha_space|max:200'
    ];


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
