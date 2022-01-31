<?php

namespace RZP\Services\TaxPayments;

use RZP\Http\Request\Requests;
use RZP\Base;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

class Validator extends Base\Validator
{
    const SEND_MAIL = 'send_mail';
    const CREATE_DIRECT_TAX_PAYMENT = 'create_direct_tax_payment';
    const GOOGLE_CAPTCHA_VERIFICATION_ENDPOINT = 'https://www.google.com/recaptcha/api/siteverify';
    const TAX_PAYMENT_ENABLED_MERCHANTS = 'tax_payment_enabled_merchants';

    protected static $sendMailRules = [
        'merchant_email' => 'required|email',
        'data' => 'required|array',
        'subject' => 'required|string',
        'template_name' => 'required|string',
        'cc_emails' => 'sometimes|array',
    ];

    protected static $createDirectTaxPaymentRules = [
        'g-recaptcha-response' => 'required|custom'
    ];

    protected static $taxPaymentEnabledMerchantsRules = [
        'offset' => 'filled|integer|min:0',
        'limit' => 'filled|integer|min:1|max:100',
        'tax_feature_key' => 'string',
    ];

    function validateGRecaptchaResponse($captchaKey, $captchaResponse)
    {
        /**
         * you have to call the g-api and check if this works or not...
         *
         */

        $captchaSecret = config('app.signup.nocaptcha_secret');

        $input = [
            'secret' => $captchaSecret,
            'response' => $captchaResponse,
        ];

        $url = self::GOOGLE_CAPTCHA_VERIFICATION_ENDPOINT;

        $response = Requests::request($url, [], $input, Requests::GET);

        $output = json_decode($response->body);

        if ($output->success !== true) {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_CAPTCHA_FAILED,
                null,
                [
                    'output_from_google' => (array)$output
                ]
            );
        }

    }

}
