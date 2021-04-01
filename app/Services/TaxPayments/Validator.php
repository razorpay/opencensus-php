<?php

namespace RZP\Services\TaxPayments;

use Requests;
use RZP\Base;
use RZP\Diag\EventCode;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

class Validator extends Base\Validator
{
    const SEND_MAIL                            = 'send_mail';
    const CREATE_DIRECT_TAX_PAYMENT            = 'create_direct_tax_payment';
    const GOOGLE_CAPTCHA_VERIFICATION_ENDPOINT = 'https://www.google.com/recaptcha/api/siteverify';

    protected static $sendMailRules = [
        'merchant_email' => 'required|email',
        'data'           => 'required|array',
        'subject'        => 'required|string',
        'template_name'  => 'required|string',
    ];

    protected static $createDirectTaxPaymentRules = [
        'g-recaptcha-response' => 'required|custom'
    ];

    function validateGRecaptchaResponse($captchaKey, $captchaResponse)
    {
        /**
         * you have to call the g-api and check if this works or not...
         *
         */

        $captchaSecret = config('app.signup.nocaptcha_secret');

        $input = [
            'secret'   => $captchaSecret,
            'response' => $captchaResponse,
        ];

        $url = self::GOOGLE_CAPTCHA_VERIFICATION_ENDPOINT;

        $response = Requests::request($url, null, $input, Requests::GET, null);

        $output = json_decode($response->body);

        if ($output->success !== true)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_CAPTCHA_FAILED,
                null,
                [
                    'output_from_google'        => (array)$output
                ]
            );
        }

    }

}
