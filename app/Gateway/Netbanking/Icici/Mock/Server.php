<?php

namespace RZP\Gateway\Netbanking\Icici\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Netbanking;

use RZP\Gateway\Netbanking\Icici\RequestFields;
use RZP\Gateway\Netbanking\Icici\ResponseFields;
use RZP\Gateway\Netbanking\Icici\Constants;
use RZP\Gateway\Netbanking\Icici\Confirmation;
use RZP\Gateway\Netbanking\Icici\AesTrait;
use phpseclib\Crypt\AES;

class Server extends Base\Mock\Server
{
    const MODE_ECB = 1;

    use AesTrait;

    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        $masterKey = $this->getGatewayInstance()->getMasterKey();

        $decryptedString = $this->decryptString($input['ES'], $masterKey);

        // Removing the %22 tags in the return URL
        $string = str_replace('%22', '', $decryptedString);

        parse_str($string, $decryptedData);

        // $decrypted_data['RU'] now contains the callback URL
        $callbackUrl = $decryptedData['RU'];

        $postData = $this->createPostData($decryptedData); // response from icici bank

        // For test cases
        $this->content($postData);

        $content = $this->formatPostData($postData);

        $request = [
            'url' => $callbackUrl,
            'content' => $content,
            'method' => 'post', // for debug only
        ];

        $callbackUrl .= '?' . http_build_query($content);

        return $callbackUrl;
    }

    public function verify($input)
    {
        parent::verify($input);

        $this->validateActionInput($input);

        $response = $this->createXmlResponse($input);

        return $this->makeResponse($response);
    }

    protected function createPostData($input)
    {
        $response = array(
            RequestFields::PAYMENT_REFERENCE_NUBER  => $input[RequestFields::PAYMENT_REFERENCE_NUBER],
            RequestFields::ITEM_CODE                => strtoupper($input[RequestFields::ITEM_CODE]),
            RequestFields::AMOUNT                   => $input[RequestFields::AMOUNT],
            RequestFields::CURRENCY_CODE            => $input[RequestFields::CURRENCY_CODE],
            ResponseFields::STATUS                  => 'Y',
        );

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

        $content['ES'] = $this->encryptString($httpQuery, $masterKey);

        return $content;
    }

    protected function createXmlResponse($input)
    {
        // Hardcoding success for now
        $xmlArray = $this->createXmlArray($input);

        // For test cases
        $this->content($xmlArray);

        $xmlArray = array_flip($xmlArray);

        $xml = new \SimpleXMLElement('<VerifyOutput/>');
        array_walk_recursive($xmlArray, array ($xml, 'addAttribute'));

        $response = $xml->asXML();

        return $response;
    }

    protected function createXmlArray($input)
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
