<?php

namespace RZP\Gateway\Netbanking\Axis\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Netbanking\Axis\AesTrait;
use RZP\Gateway\Netbanking\Axis\Constants;
use RZP\Gateway\Netbanking\Axis\RequestFields;
use RZP\Gateway\Netbanking\Axis\ResponseFields;

class Server extends Base\Mock\Server
{
    const MODE_ECB = 1;

    use AesTrait;

    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        $decryptedData = $this->getDecryptedData($input);

        $response = $this->createResponse($decryptedData);

        // for test cases
        $this->content($response);

        $callbackUrl = $input[RequestFields::RETURN_URL] . '?' .
                        http_build_query($response);

        return $callbackUrl;
    }

    protected function getDecryptedData($input)
    {
        $masterKey = $this->getGatewayInstance()->getMasterKey();

        $decryptedString = $this->decryptString(
            $input[RequestFields::ENCRYPTED_STRING], $masterKey);

        $toReplace   = ['~', '$'];
        $willReplace = ['=', '&'];

        $decryptedString = str_replace($toReplace, $willReplace, $decryptedString);

        parse_str($decryptedString, $data);

        return $data;
    }

    protected function createResponse($data)
    {
        $response =  [
            ResponseFields::STATUS                      => Constants::YES,
            ResponseFields::MERCHANT_UNIQUE_REFERENCE   => $data[RequestFields::MERCHANT_UNIQUE_REFERENCE],
            ResponseFields::BANK_REFERENCE_ID           => mt_rand(1000000000, 9999999999), // 10 digit no.
            ResponseFields::ITEM_CODE                   => $data[RequestFields::ITEM_CODE],
            ResponseFields::AMOUNT                      => $data[RequestFields::AMOUNT],
            ResponseFields::CURRENCY_CODE               => Constants::INDIAN_RUPEE,
            ResponseFields::FLAG                        => Constants::SUCCESS,
        ];

        // Make sure this is correct, there is some lack of clarity here
        $query = http_build_query($response);

        $masterKey = $this->getGatewayInstance()->getMasterKey();

        $content['qs'] = $this->encryptString($query, $masterKey);

        return $content;
    }
}
