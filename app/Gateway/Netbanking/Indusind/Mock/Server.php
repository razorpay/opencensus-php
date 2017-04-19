<?php

namespace RZP\Gateway\Netbanking\Indusind\Mock;

use RZP\Gateway\Base;
use phpseclib\Crypt\AES;
use RZP\Gateway\Netbanking\Indusind\Status;
use RZP\Gateway\Netbanking\Indusind\Constants;
use RZP\Gateway\Netbanking\Base as Netbanking;
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

        $postData = $this->createPostData(array_merge($input,$decryptedData));

        $this->content($postData);

        $content = $this->formatResponseData($postData);

        $callbackUrl = $decryptedData[RequestFields::RETURN_URL] . '?' . http_build_query($content);

        return $callbackUrl;
    }

    public function verify($input)
    {
        parent::verify($input);

        $this->validateActionInput($input);

        $responseArray = $this->createResponseArray($input);

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
        $masterKey = $this->getGatewayInstance()->getSecret();

        $httpQuery = http_build_query($postData);

        $aes = new Netbanking\AESCrypto(AES::MODE_ECB, $masterKey);

        $content[ResponseFields::ENCRYPTED_STRING] = bin2hex($aes->encryptString($httpQuery));

        $this->content($content, 'hash');

        return $content;
    }

    protected function decryptData(array $input)
    {
        $masterKey = $this->getGatewayInstance()->getSecret();

        $aes = new Netbanking\AESCrypto(AES::MODE_ECB, $masterKey);

        $decryptedString = $aes->decryptString(hex2bin($input[RequestFields::ENCRYPTED_STRING]));

        if ($decryptedString === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Encrypted string not decryptable');
        }

        // Removing the %22 tags in the return URL
        $string = str_replace('%22', '', $decryptedString);

        parse_str($string, $decryptedData);

        return $decryptedData;
    }

    protected function createXmlResponse(array $responseArray)
    {
        $this->content($responseArray);

        $responseArray = array_flip($responseArray);

        $xml = new \SimpleXMLElement('<VerifyOutput/>');

        array_walk_recursive($responseArray, array ($xml, 'addAttribute'));

        $response = $xml->asXML();

        return $response;
    }

    protected function createResponseArray(array $input)
    {
        return [
            ResponseFields::STATUS  => Constants::SUCCESS,
        ];
    }
}
