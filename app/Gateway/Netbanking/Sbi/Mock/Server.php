<?php

namespace RZP\Gateway\Netbanking\Sbi\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Netbanking\Sbi\Crypto;
use RZP\Gateway\Netbanking\Sbi\RequestFields;
use RZP\Gateway\Netbanking\Sbi\ResponseFields;

class Server extends Base\Mock\Server
{
    const ENCRYPTION_METHOD = 'aes-256-gcm';

    protected $crypto;

    public function authorize($input)
    {
        parent::authorize($input);

        $input = $this->decryptRequest($input);

        $this->validateAuthorizeInput($input);

        $content = $this->getAuthResponse($input);

        $request = [
            'url'     => $input[RequestFields::REDIRECT_URL] ?? $input[RequestFields::MANDATE_RETURN_URL],
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

        return $this->makeResponse($content);
    }

    protected function getAuthResponse(array $input): array
    {
        if (isset($input[RequestFields::MANDATE_PAYMENT_ID]) === true)
        {
            $content = [
                ResponseFields::MANDATE_SBI_REF         => 'IGAAAAGNN6',
                ResponseFields::MANDATE_TXN_AMOUNT      => $input[RequestFields::MANDATE_TXN_AMOUNT],
                ResponseFields::MANDATE_SBI_STATUS      => 'Success',
                ResponseFields::MANDATE_SBI_DESCRIPTION => 'success',
                ResponseFields::MANDATE_PAYMENT_ID      => $input[RequestFields::MANDATE_PAYMENT_ID]
            ];
        }
        else
        {
            $content = [
                ResponseFields::BANK_REF_NO     => 'IGAAAAGNN6',
                ResponseFields::AMOUNT          => $input[RequestFields::AMOUNT],
                ResponseFields::REF_NO          => $input[RequestFields::REF_NO],
                ResponseFields::STATUS          => 'Success',
                ResponseFields::STATUS_DESC     => 'success',
                ResponseFields::PAYMENT_ID      => $input[RequestFields::PAYMENT_ID],
            ];
        }

        $this->content($content, $this->action);

        $contentToEncrypt = $this->getFormattedResponse($content);

        $encryptedData = $this->encrypt($contentToEncrypt);

        return ['encdata' => $encryptedData];
    }

    protected function getVerifyResponse(array $input)
    {
        if (isset($input[RequestFields::MANDATE_VERIFY_TXN_AMOUNT]) === true)
        {
            $content = [
                ResponseFields::MANDATE_SBI_REF         => 'IGAAAAGNN6',
                ResponseFields::MANDATE_PAYMENT_ID      => $input[RequestFields::MANDATE_PAYMENT_ID],
                ResponseFields::MANDATE_SBI_STATUS      => 'Success',
                ResponseFields::MANDATE_SBI_DESCRIPTION => 'Completed Successfully',
                ResponseFields::MANDATE_TXN_AMOUNT      => '1.00'
            ];
        }
        else
        {
            $content = [
                ResponseFields::BANK_REF_NO     => 'IGAAAAGNN6',
                ResponseFields::AMOUNT          => $input[RequestFields::AMOUNT],
                ResponseFields::REF_NO          => $input[RequestFields::REF_NO],
                ResponseFields::STATUS          => 'Success',
                ResponseFields::STATUS_DESC     => 'success',
            ];
        }

        $this->content($content, $this->action);

        $contentToEncrypt = $this->getFormattedResponse($content);

        $encryptedData = $this->encrypt($contentToEncrypt);

        $this->content($encryptedData, 'verify_enc');

        return $encryptedData;
    }

    protected function getFormattedResponse(array $requestArray)
    {
        $requestWithoutChecksum = urldecode(http_build_query($requestArray, '', '|'));

        $checksum = hash('sha256', $requestWithoutChecksum);

        // not actually callback, just using `callback` action to modify checksum in tests
        $this->content($checksum, 'callback');

        return $requestWithoutChecksum . '|' . RequestFields::CHECKSUM . '=' . $checksum;
    }

    protected function decryptRequest($input)
    {
        $decryptedString = $this->decrypt($input['encdata']);

        $response = [];

        parse_str(strtr($decryptedString, '|', '&'), $response);

        return $response;
    }

    private function encrypt($stringToEncrypt)
    {
        $this->createCryptoIfNotCreated();

        return $this->crypto->encrypt($stringToEncrypt);
    }

    private function decrypt($stringToDecrypt)
    {
        $this->createCryptoIfNotCreated();

        return $this->crypto->decrypt($stringToDecrypt);
    }

    private function createCryptoIfNotCreated()
    {
        if ($this->crypto === null)
        {
            $this->crypto = new Crypto(
                $this->getSecret(),
                $this->getIv(),
                self::ENCRYPTION_METHOD);
        }
    }

    private function getIv()
    {
        return $this->getGatewayInstance()->getIv();
    }
}
