<?php

namespace RZP\Gateway\Netbanking\Icici\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Netbanking;

use RZP\Gateway\Netbanking\Icici\RequestFields;
use RZP\Gateway\Netbanking\Icici\ResponseFields;
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

        $content = $this->formatPostData($postData);

        $request = array(
            'url' => $callbackUrl,
            'content' => $content,
            'method' => 'post', // for debug only
        );

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

    public function createPostData($input)
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

    public function formatPostData($postData)
    {
        $masterKey = $this->getGatewayInstance()->getMasterKey();

        $httpQuery = http_build_query($postData);

        $content['ES'] = $this->encryptString($httpQuery, $masterKey);

        return $content;
    }

    public function createXmlResponse($input)
    {
        $response = '<?xml version="1.0" encoding="utf-8" ?>' . PHP_EOL;
        $response .= '<VerifyOutput ITC="'. $input['ITC'] .'" PRN="'. $input['PRN'] .'" CURRENCY="'. $input['CRN'] .'" PMTDATE="'. $input['Pmt_Date'] .'" AMT="'. number_format($input['AMT'], 2, '.', '') .'" STATUS="SUCCESS" />';

        return $response;
    }
}
