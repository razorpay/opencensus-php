<?php

namespace RZP\Gateway\Netbanking\Sbi\Mock;

use phpseclib\Crypt\AES;

use RZP\Gateway\Base;
use RZP\Gateway\Base\AESCrypto;
use RZP\Gateway\Netbanking\Sbi\RequestFields;
use RZP\Gateway\Netbanking\Sbi\ResponseFields;

class Server extends Base\Mock\Server
{
    protected $aesCrypto;

    public function authorize($input)
    {
        parent::authorize($input);

        $input = $this->decryptRequest($input);

        $this->validateAuthorizeInput($input);

        $content = $this->getAuthResponse($input);

        $request = [
            'url'     => $input[RequestFields::REDIRECT_URL],
            'content' => $content,
            'method'  => 'post',
        ];

        return $this->makePostResponse($request);
    }

    public function verify($input)
    {
        parent::verify($input);

        $input = $this->decryptRequest($input);

        $this->validateActionInput($input, $this->action);

        $content = $this->getVerifyResponse($input);

        $responseToEncrypt = $this->createXmlResponse($content);

        $response = $this->encrypt($responseToEncrypt);

        return $this->makeResponse(['encdata' => $response]);
    }

    protected function getAuthResponse(array $input): array
    {
        $content = [
            ResponseFields::BANK_REF_NO     => 'AB1234',
            ResponseFields::AMOUNT          => $input[RequestFields::AMOUNT],
            ResponseFields::REF_NO          => $input[RequestFields::REF_NO],
            ResponseFields::STATUS          => 'Success',
            ResponseFields::STATUS_DESC     => 'success',
            ResponseFields::PAYMENT_ID      => $input[RequestFields::PAYMENT_ID],
        ];

        $this->content($content, $this->action);

        $contentToEncrypt = $this->getFormattedResponse($content);

        $encryptedData = $this->encrypt($contentToEncrypt);

        return ['encdata' => $encryptedData];
    }

    protected function getVerifyResponse(array $input)
    {
        $content = [
            ResponseFields::BANK_REF_NO     => 'AB1234',
            ResponseFields::AMOUNT          => $input[RequestFields::AMOUNT],
            ResponseFields::REF_NO          => $input[RequestFields::REF_NO],
            ResponseFields::STATUS          => 'Success',
            ResponseFields::STATUS_DESC     => 'success',
        ];

        $this->content($content, $this->action);

        return $content;
    }

    protected function getFormattedResponse(array $requestArray)
    {
        $request = [];

        foreach ($requestArray as $key => $value)
        {
            $request[] = $key . '=' . $value;
        }

        $requestWithoutChecksum = implode('|', $request);

        $checksum = md5($requestWithoutChecksum);

        // not actually callback, just using `callback` action to modify checksum in tests
        $this->content($checksum, 'callback');

        return $requestWithoutChecksum . '|' . RequestFields::CHECKSUM . '=' . $checksum;
    }

    protected function decryptRequest($input)
    {
        $decryptedString = $this->decrypt($input['encdata']);

        $responseStringArray = explode('|', $decryptedString);

        return $this->getResponseArray($responseStringArray);
    }

    protected function createXmlResponse(array $responseArray)
    {
        $this->content($responseArray, 'verify');

        if (is_array($responseArray) === false)
        {
            return $responseArray;
        }

        $responseArray = array_flip($responseArray);

        $xml = new \SimpleXMLElement('<VerifyOutput></VerifyOutput>');

        array_walk_recursive($responseArray, array ($xml, 'addAttribute'));

        $response = $xml->asXML();

        $this->content($response, 'verifyXML');

        return $response;
    }

    private function getResponseArray($stringArray)
    {
        $response = [];

        foreach ($stringArray as $line)
        {
            $key = explode('=', $line)[0];
            $value = explode('=', $line)[1];

            $response[$key] = $value;
        }

        return $response;
    }

    private function encrypt($stringToEncrypt)
    {
        $this->createCryptoIfNotCreated();

        return base64_encode($this->aesCrypto->encryptString($stringToEncrypt));
    }

    private function decrypt($stringToDecrypt)
    {
        $this->createCryptoIfNotCreated();

        return ($this->aesCrypto->decryptString(base64_decode($stringToDecrypt)));
    }

    private function createCryptoIfNotCreated()
    {
        if ($this->aesCrypto === null)
        {
            $this->aesCrypto = new AESCrypto(
                AES::MODE_CBC,
                hex2bin($this->getSecret()),
                $this->getIv());
        }
    }

    private function getIv()
    {
        return '1234567890123456';
    }
}
