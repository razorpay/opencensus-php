<?php

namespace RZP\Gateway\Netbanking\Icici\Mock;

use RZP\Base;
use RZP\Exception;
use RZP\Constants\Mode;
use phpseclib\Crypt\Base as Crypto;
use RZP\Gateway\Netbanking\Base as Netbanking;
use RZP\Gateway\Netbanking\Icici\RequestFields;
use RZP\Gateway\Netbanking\Icici\Gateway as IciciGateway;

class Validator extends Base\Validator
{
    protected static $authRules = [
        RequestFields::MODE             => 'required|alpha|in:P',
        RequestFields::PAYEE_ID         => 'required|string',
        RequestFields::SPID             => 'required|string',
        RequestFields::ENCRYPTED_STRING => 'required|string',
    ];

    protected static $authValidators = [
        RequestFields::ENCRYPTED_STRING,
    ];

    protected static $verifyRules = [
        RequestFields::MODE          => 'required|alpha|size:1|in:V',
        RequestFields::PAYEE_ID      => 'required|string',
        RequestFields::SPID          => 'required|string',
        RequestFields::AMOUNT        => 'required|numeric',
        RequestFields::PAYMENT_ID    => 'required|alpha_num|size:14',
        RequestFields::ITEM_CODE     => 'required|alpha_num|size:14',
        RequestFields::CURRENCY_CODE => 'required|in:INR',
        RequestFields::ACCOUNT_NO    => 'sometimes|string',
        RequestFields::PAYMENT_DATE  => 'required|date_format:Y-m-d',
    ];

    protected function validateES(array $input)
    {
        $masterKey = $this->getGatewayMasterKey();

        $aes = new Netbanking\AESCrypto(Crypto::MODE_ECB, $masterKey);

        $decryptedString = $aes->decryptString(base64_decode($input['ES']));

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
        $this->assertField($decryptedData, RequestFields::PAYMENT_ID);
        $this->assertField($decryptedData, RequestFields::ITEM_CODE);
        $this->assertField($decryptedData, RequestFields::RETURN_URL);
    }

    protected function assertField(array $data, $field)
    {
        if (isset($data[$field]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                $field . ' not specified');
        }
    }

    protected function getGatewayMasterKey()
    {
        $gateway = new IciciGateway;

        $gateway->setMode(Mode::TEST);

        return $gateway->getSecret();
    }
}
