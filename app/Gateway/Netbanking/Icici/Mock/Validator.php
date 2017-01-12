<?php

namespace RZP\Gateway\Netbanking\Icici\Mock;

use RZP\Base;
use RZP\Exception;
use RZP\Constants\Mode;
use phpseclib\Crypt\AES;
use RZP\Gateway\Netbanking\Icici\Gateway as IciciGateway;
use RZP\Gateway\Netbanking\Icici\RequestFields;

class Validator extends Base\Validator
{
    const MODE_ECB = 1;

    protected static $authRules = [
        RequestFields::MODE                      => 'required|alpha|in:P,V',
        RequestFields::PAYEE_ID                  => 'required|string',
        RequestFields::SPID                      => 'required|string',
        RequestFields::ENCRYPTED_STRING          => 'required',
    ];

    protected static $authValidators = [
        RequestFields::ENCRYPTED_STRING,
    ];

    protected static $verifyRules = [
        RequestFields::MODE                      => 'required|alpha|size:1',
        RequestFields::PAYEE_ID                  => 'required|string',
        RequestFields::SPID                      => 'required|string',
        RequestFields::AMOUNT                    => 'required|numeric',
        RequestFields::PAYMENT_REFERENCE_NUBER   => 'required|alpha_num',
        RequestFields::ITEM_CODE                 => 'required|alpha_num',
        RequestFields::CURRENCY_CODE             => 'required|in:INR',
        RequestFields::ACCOUNT_NO                => 'sometimes|string',
        RequestFields::PAYMENT_DATE              => 'required',
    ];

    protected function validateES($input)
    {
        $masterKey = $this->getGatewayMasterKey();

        $decryptedString = $this->decryptString(base64_decode($input['ES']), $masterKey);

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

    protected function assertField($data, $field)
    {
        if (!isset($data[$field]))
        {
            throw new Exception\BadRequestValidationFailureException(
                $field . ' not specified');
        }
    }

    protected function decryptString(string $string, string $masterKey)
    {
        $aes = new AES(self::MODE_ECB);
        $aes->setKey($masterKey);

        return $aes->decrypt($string);
    }

    protected function getGatewayMasterKey()
    {
        $gateway = new IciciGateway;

        $gateway->setMode(Mode::TEST);

        return $gateway->getSecret();
    }
}
