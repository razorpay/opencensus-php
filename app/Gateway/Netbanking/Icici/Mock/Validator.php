<?php

namespace RZP\Gateway\Netbanking\Icici\Mock;

use RZP\Base;
use RZP\Constants\Mode;
use RZP\Gateway\Netbanking\Icici\AesTrait;
use RZP\Gateway\Netbanking\Icici\Gateway as IciciGateway;
use RZP\Gateway\Netbanking\Icici\RequestFields;

class Validator extends Base\Validator
{
    use AesTrait;

    const MODE_ECB = 1;

    protected static $authRules = [
        RequestFields::OBJ_NAME                  => 'required|in:bay_mc_login',
        RequestFields::BAY_BANKID                => 'required|in:ICI',
        RequestFields::MODE                      => 'required|alpha',
        RequestFields::PAYEE_ID                  => 'required|alpha_num',
        RequestFields::SPID                      => 'required|alpha_num',
        RequestFields::ENCRYPTED_STRING          => 'required',
    ];

    protected static $authValidators = [
        RequestFields::ENCRYPTED_STRING,
    ];

    protected static $verifyRules = [
        RequestFields::OBJ_NAME                  => 'required|in:bay_mc_login',
        RequestFields::BAY_BANKID                => 'required|in:ICI',
        RequestFields::MODE                      => 'required|alpha',
        RequestFields::PAYEE_ID                  => 'required|alpha_num',
        RequestFields::SPID                      => 'required|alpha_num',
        RequestFields::AMOUNT                    => 'required|numeric',
        RequestFields::PAYMENT_REFERENCE_NUBER   => 'required|alpha_num',
        RequestFields::ITEM_CODE                 => 'required|alpha_num',
        RequestFields::CURRENCY_CODE             => 'required|in:INR',
        RequestFields::PAYMENT_DATE              => 'required',
    ];

    protected function validateES($input)
    {
        $gateway = new IciciGateway;

        $gateway->setMode(Mode::TEST);

        $masterKey = $gateway->getMasterKey();

        $decryptedString = $this->decryptString($input['ES'], $masterKey);

        if ($decryptedString === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Encrypted string not decryptable');
        }

        // Removing the %22 tags in the return URL
        $string = str_replace('%22', '', $decryptedString);

        parse_str($string, $decryptedData);

        if (!isset($decryptedData[RequestFields::AMOUNT]))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Amount not specified');
        }

        else if ((!isset($decryptedData[RequestFields::CURRENCY_CODE])) or
            ($decryptedData[RequestFields::CURRENCY_CODE] !== 'INR'))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Currency not set correctly');
        }

        else if (!isset($decryptedData[RequestFields::PAYMENT_REFERENCE_NUBER]))
        {
            throw new Exception\BadRequestValidationFailureException(
                'PRN not specified');
        }

        else if (!isset($decryptedData[RequestFields::ITEM_CODE]))
        {
            throw new Exception\BadRequestValidationFailureException(
                'ITC not specified');
        }

        else if (!isset($decryptedData[RequestFields::RETURN_URL]))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Return URL not specified');
        }
    }
}
