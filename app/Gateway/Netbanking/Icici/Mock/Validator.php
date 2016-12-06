<?php

namespace RZP\Gateway\Netbanking\Icici\Mock;

use RZP\Base;
use RZP\Exception;
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
        RequestFields::PAYEE_ID                  => 'required|string',
        RequestFields::SPID                      => 'required|string',
        RequestFields::ENCRYPTED_STRING          => 'required',
    ];

    protected static $authValidators = [
        RequestFields::ENCRYPTED_STRING,
    ];

    protected static $verifyRules = [
        RequestFields::OBJ_NAME                  => 'required|in:bay_mc_login',
        RequestFields::BAY_BANKID                => 'required|in:ICI',
        RequestFields::MODE                      => 'required|alpha',
        RequestFields::PAYEE_ID                  => 'required|string',
        RequestFields::SPID                      => 'required|string',
        RequestFields::AMOUNT                    => 'required|numeric',
        RequestFields::PAYMENT_REFERENCE_NUBER   => 'required|alpha_num',
        RequestFields::ITEM_CODE                 => 'required|alpha_num',
        RequestFields::CURRENCY_CODE             => 'required|in:INR',
        RequestFields::ACCOUNT_NO                => 'sometimes',
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

        $this->assertField($decryptedData, RequestFields::AMOUNT);
        $this->assertField($decryptedData, RequestFields::CURRENCY_CODE);
        $this->assertField($decryptedData, RequestFields::PAYMENT_REFERENCE_NUBER);
        $this->assertField($decryptedData, RequestFields::ITEM_CODE);
        $this->assertField($decryptedData, RequestFields::RETURN_URL);
    }

    protected function assertField($decryptedData, $field)
    {
        if (!isset($decryptedData[$field]))
        {
            throw new Exception\BadRequestValidationFailureException(
                $field . ' not specified');
        }
    }
}
