<?php

namespace App\User;

use App\Base;

class Validator extends Base\Validator
{
    const DISABLE_CAPTCHA_SECRET = 'DISABLE_THE_CAPTCHA_YOU_SHALL';

    public static $createRules = array(
        // Individual Name
        'name'                  => 'sometimes|alpha_space|max:200',
        // Merchant Business Name
        'business_name'         => 'alpha_space_num|max:200|min:4',

        // Mobile Number (optional). Saved in merchant_details
        'contact_mobile'        => 'sometimes|numeric|digits_between:8,11',

        // Merchant email. Used for both merchant and user accounts
        'email'                 => 'required|email|unique:users',
        'password'              => 'required|between:7,50|confirmed|numbers|letters',
        'password_confirmation' => 'required|between:7,50',
        'captcha'               => 'required_unless:captcha_disable,'. self::DISABLE_CAPTCHA_SECRET,
        'invitation'            => 'max:40',
        'ref'                   => 'sometimes|max:255'
    );

    protected static $upgradeRules = [
        'business_name'         =>  'required|min:4|alpha_space|max:200'
    ];

    protected static $createValidators = ['captcha'];

    protected static $unsetCreateInput = [
        'captcha'
    ];

    protected static $unsetLoginInput = [
        'password'
    ];

    protected static $preSignupRules = [
        'name'                  => 'sometimes|alpha_space|max:200',
        'contact_mobile'        => 'sometimes|numeric|digits_between:8,11',
    ];

    protected function validateCaptcha($input)
    {
        if ((isset($input['captcha_disable'])) and
            ($input['captcha_disable'] === self::DISABLE_CAPTCHA_SECRET))
        {
            return;
        }

        // TODO: Replace this with $request->ip
        if($_SERVER['HTTP_HOST'] === 'dashboard.razorpay.com' OR $_SERVER['HTTP_HOST'] === 'betadashboard.razorpay.com')
        {
            $captchaResponse = $input['captcha'];

            if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR']) {
                $clientIpAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else {
                $clientIpAddress = $_SERVER['REMOTE_ADDR'];
            }

            $noCaptchaSecret = config('razorpay.signup.nocaptcha_secret');

            $url = "https://www.google.com/recaptcha/api/siteverify?secret=".$noCaptchaSecret."&response=".$captchaResponse."&remoteip=".$clientIpAddress;

            $response = \Requests::get($url);

            $output = json_decode($response->body);

            if($output->success !== true)
            {
                $this->addError('captcha', 'Captcha Failed');
            }
        }
    }
}
