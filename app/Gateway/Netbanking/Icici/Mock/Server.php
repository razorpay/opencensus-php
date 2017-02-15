<?php

namespace RZP\Gateway\Netbanking\Icici\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Netbanking\Icici\AESCrypto;
use RZP\Gateway\Netbanking\Icici\Constants;
use RZP\Gateway\Netbanking\Icici\Confirmation;
use RZP\Gateway\Netbanking\Icici\RequestFields;
use RZP\Gateway\Netbanking\Icici\ResponseFields;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        $decryptedData = $this->decryptData($input);

        $postData = $this->createPostData($decryptedData);

        $this->content($postData);

        $content = $this->formatResponseData($postData);

        $callbackUrl = $decryptedData['RU'] . '?' . http_build_query($content);

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
            RequestFields::PAYMENT_REFERENCE_NUBER  => $input[RequestFields::PAYMENT_REFERENCE_NUBER],
            RequestFields::ITEM_CODE                => strtoupper($input[RequestFields::ITEM_CODE]),
            RequestFields::AMOUNT                   => $input[RequestFields::AMOUNT],
            RequestFields::CURRENCY_CODE            => $input[RequestFields::CURRENCY_CODE],
            ResponseFields::STATUS                  => Confirmation::YES,
        ];

        if ($input[RequestFields::CONFIRMATION] === Confirmation::YES)
        {
            $response[ResponseFields::BANK_PAYMENT_ID] = mt_rand(1000000000, 9999999999);
        }

        return $response;
    }

    protected function formatResponseData(array $postData)
    {
        $masterKey = $this->getGatewayInstance()->getSecret();

        $httpQuery = http_build_query($postData);

        $aes = new AESCrypto($masterKey);

        $content['ES'] = base64_encode($aes->encryptString($httpQuery, $masterKey));

        $this->content($content, 'hash');

        return $content;
    }

    protected function decryptData(array $input)
    {
        $masterKey = $this->getGatewayInstance()->getSecret();

        $aes = new AESCrypto($masterKey);

        $decryptedString = $aes->decryptString(base64_decode($input['ES']), $masterKey);

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
            ResponseFields::ITEM_CODE               => $input[RequestFields::ITEM_CODE],
            ResponseFields::PAYMENT_REFERENCE_NUBER => $input[RequestFields::PAYMENT_REFERENCE_NUBER],
            ResponseFields::CURRENCY                => $input[RequestFields::CURRENCY_CODE],
            ResponseFields::PAYMENT_DATE            => $input[RequestFields::PAYMENT_DATE],
            ResponseFields::AMOUNT                  => $input[RequestFields::AMOUNT],
            ResponseFields::STATE                   => Constants::SUCCESS,
        ];
    }
}
