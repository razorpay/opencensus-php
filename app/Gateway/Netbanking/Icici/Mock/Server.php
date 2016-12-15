<?php

namespace RZP\Gateway\Netbanking\Icici\Mock;

use RZP\Gateway\Base;
use RZP\Lib\AesTrait;
use RZP\Gateway\Netbanking\Icici\RequestFields;
use RZP\Gateway\Netbanking\Icici\ResponseFields;
use RZP\Gateway\Netbanking\Icici\Constants;
use RZP\Gateway\Netbanking\Icici\Confirmation;

class Server extends Base\Mock\Server
{
    const MODE_ECB = 1;

    use AesTrait;

    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        $decryptedData = $this->decryptData($input);

        $postData = $this->createPostData($decryptedData); // response from icici bank

        // For test cases
        $this->content($postData);

        $content = $this->formatPostData($postData);

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

    protected function createPostData($input)
    {
        $response = [
            RequestFields::PAYMENT_REFERENCE_NUBER  => $input[RequestFields::PAYMENT_REFERENCE_NUBER],
            RequestFields::ITEM_CODE                => strtoupper($input[RequestFields::ITEM_CODE]),
            RequestFields::AMOUNT                   => $input[RequestFields::AMOUNT],
            RequestFields::CURRENCY_CODE            => $input[RequestFields::CURRENCY_CODE],
            ResponseFields::STATUS                  => 'Y',
        ];

        if ($input[RequestFields::CONFIRMATION] === Confirmation::YES)
        {
            $response[ResponseFields::BANK_PAYMENT_ID] = mt_rand(1000000000, 9999999999); // Random 10 digit number
        }

        // Forcing PAID to be Y for the mock server

        return $response;
    }

    protected function formatPostData($postData)
    {
        $masterKey = $this->getGatewayInstance()->getMasterKey();

        $httpQuery = http_build_query($postData);

        $content['ES'] = base64_encode($this->encryptString($httpQuery, $masterKey));

        return $content;
    }

    protected function decryptData($input)
    {
        $masterKey = $this->getGatewayInstance()->getMasterKey();

        $decryptedString = $this->decryptString(base64_decode($input['ES']), $masterKey);

        // Removing the %22 tags in the return URL
        $string = str_replace('%22', '', $decryptedString);

        parse_str($string, $decryptedData);

        return $decryptedData;

    }

    protected function createXmlResponse($responseArray)
    {
        // For test cases
        $this->content($responseArray);

        $responseArray = array_flip($responseArray);

        $xml = new \SimpleXMLElement('<VerifyOutput/>');
        array_walk_recursive($responseArray, array ($xml, 'addAttribute'));

        $response = $xml->asXML();

        return $response;
    }

    protected function createResponseArray($input)
    {
        return [
            ResponseFields::ITEM_CODE               => $input[RequestFields::ITEM_CODE],
            ResponseFields::PAYMENT_REFERENCE_NUBER => $input[RequestFields::PAYMENT_REFERENCE_NUBER],
            ResponseFields::CURRENCY                => $input[RequestFields::CURRENCY_CODE],
            ResponseFields::PAYMENT_DATE            => $input[RequestFields::PAYMENT_DATE],
            ResponseFields::AMOUNT                  => number_format($input[RequestFields::AMOUNT], 2, '.', ''),
            ResponseFields::STATE                   => Constants::SUCCESS,
        ];
    }
}
