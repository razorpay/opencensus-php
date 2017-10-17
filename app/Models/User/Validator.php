<?php

namespace RZP\Models\User;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    const DISABLE_CAPTCHA_SECRET = 'DISABLE_THE_CAPTCHA_YOU_SHALL';

    protected static $createRules = [
        Entity::ID                    => 'sometimes|max:14',
        Entity::NAME                  => 'sometimes|string|max:200',
        Entity::EMAIL                 => 'required|email|unique:users,email',
        Entity::PASSWORD              => 'required|between:7,50|confirmed|numbers|letters',
        Entity::PASSWORD_CONFIRMATION => 'required|between:7,50',
        Entity::CONTACT_MOBILE        => 'sometimes|max:15',
        Entity::REMEMBER_TOKEN        => 'sometimes',
        Entity::CONFIRM_TOKEN         => 'sometimes',
        Entity::CAPTCHA               => 'required_without:captcha_disable',
        Entity::CAPTCHA_DISABLE       => 'sometimes|string',
    ];

    protected static $editRules = [
        Entity::NAME                  => 'sometimes|string|max:200',
        Entity::EMAIL                 => 'sometimes|email|unique:users,email',
        Entity::CONTACT_MOBILE        => 'sometimes|max:15',
    ];

    protected static $changePasswordRules = [
        Entity::PASSWORD              => 'required|between:7,50|confirmed|numbers|letters',
        Entity::PASSWORD_CONFIRMATION => 'required|between:7,50',
        Entity::OLD_PASSWORD          => 'sometimes|string',
    ];

    protected static $actionRules = [
        Entity::ACTION                => 'required|custom',
        Entity::MERCHANT_ID           => 'required|max:14',
        Entity::ROLE                  => 'sometimes|string',
    ];

    protected static $loginRules = [
        Entity::EMAIL                 => 'required|email',
        Entity::PASSWORD              => 'required|between:6,50',
    ];

    protected static $confirmRules = [
        Entity::CONFIRM_TOKEN         => 'sometimes',
        Entity::EMAIL                 => 'sometimes|email',
    ];

    protected static $preSignupRules = [
        Entity::NAME                  => 'sometimes|alpha_space|max:200',
        Entity::CONTACT_MOBILE        => 'sometimes|numeric|digits_between:8,11',
    ];

    protected static $createValidators = [
        'captcha'
    ];

    /**
     * Google captcha validation.
     *
     * @param array $input
     */
    protected function validateCaptcha(array $input)
    {
        if ((isset($input[Entity::CAPTCHA_DISABLE])) and
            ($input[Entity::CAPTCHA_DISABLE] === self::DISABLE_CAPTCHA_SECRET))
        {
            return;
        }

        if($_SERVER['HTTP_HOST'] === 'dashboard.razorpay.com' or $_SERVER['HTTP_HOST'] === 'betadashboard.razorpay.com')
        {
            $captchaResponse = $input[Entity::CAPTCHA] ?? null;

            if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) and $_SERVER['HTTP_X_FORWARDED_FOR'])
            {
                $clientIpAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
            else
            {
                $clientIpAddress = $_SERVER['REMOTE_ADDR'];
            }

            $noCaptchaSecret = config('app.signup.nocaptcha_secret');

            $input = [
                'secret'   => $noCaptchaSecret,
                'response' => $captchaResponse,
                'remoteip' => $clientIpAddress,
            ];

            $captchaQuery = http_build_query($input);

            $url = "https://www.google.com/recaptcha/api/siteverify?". $captchaQuery;

            $response = \Requests::get($url);

            $output = json_decode($response->body);

            if($output->success !== true)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_CAPTCHA_FAILED);
            }
        }
    }

    protected function validateAction(string $attribute, string $action)
    {
        if (Action::exists($action) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_USER_ACTION_NOT_SUPPORTED);
        }
    }
}
