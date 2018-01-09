<?php

namespace RZP\Gateway\Netbanking\Indusind\Mock;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Netbanking\Indusind\Status;
use RZP\Gateway\Netbanking\Indusind\Constants;
use RZP\Gateway\Netbanking\Indusind\RequestFields;
use RZP\Gateway\Netbanking\Indusind\ResponseFields;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        $decryptedData = $this->decryptData($input);

        $this->validateActionInput($decryptedData, 'auth_decrypted');

        $postData = $this->createPostData(array_merge($input, $decryptedData));

        $this->content($postData);

        $content = $this->formatResponseData($postData);

        $callbackUrl = $decryptedData[RequestFields::RETURN_URL] . '?' . http_build_query($content);

        return $callbackUrl;
    }

    public function verify($input)
    {
        parent::verify($input);

        $this->validateActionInput($input);

        $responseArray = $this->createResponseArray();

        $response = $this->createXmlResponse($responseArray);

        return $this->makeResponse($response);
    }

    protected function createPostData(array $input)
    {
        $response = [
            ResponseFields::MERCHANT_REFERENCE => $input[RequestFields::MERCHANT_REFERENCE],
            ResponseFields::ITEM_CODE          => strtoupper($input[RequestFields::ITEM_CODE]),
            ResponseFields::AMOUNT             => $input[RequestFields::AMOUNT],
            ResponseFields::PAYEE_ID           => $input[RequestFields::PAYEE_ID],
            ResponseFields::PAID               => Constants::YES,
        ];

        $response[ResponseFields::BANK_REFERENCE_ID] = 9999999999;

        return $response;
    }

    protected function formatResponseData(array $postData)
    {
        $httpQuery = http_build_query($postData);

        $content[ResponseFields::ENCRYPTED_STRING] = $this->getGatewayInstance()->encryptString($httpQuery);

        $this->content($content, 'hash');

        return $content;
    }

    protected function decryptData(array $input)
    {
        $decryptedString = $this->getGatewayInstance()->decryptString($input[RequestFields::ENCRYPTED_STRING]);

        if (empty($decryptedString) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Encrypted string not decryptable');
        }

        parse_str($decryptedString, $decryptedData);

        return $decryptedData;
    }

    protected function createXmlResponse(array $responseArray)
    {
        $this->content($responseArray);

        $responseArray = array_flip($responseArray);

        $xml = new \SimpleXMLElement('<xml/>');

        array_walk_recursive($responseArray, array ($xml, 'addChild'));

        $response = $xml->asXML();

        return $response;
    }

    protected function createResponseArray()
    {
        return [
            ResponseFields::VERIFICATION      => Constants::YES,
            ResponseFields::BANK_REFERENCE_ID => 9999999,
        ];
    }
}
