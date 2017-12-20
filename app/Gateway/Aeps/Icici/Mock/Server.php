<?php

namespace RZP\Gateway\Aeps\Icici\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Aeps\Icici;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        $response = $this->getAuthResponse($input);

        return $this->makePostResponse($response);
    }

    protected function getAuthResponse(string $input): string
    {
        $data = [
            Icici\ResponseConstants::AUTH_MESSAGE_TYPE_INDICATOR                => '0110',
            Icici\ResponseConstants::AUTH_PAN                                   => '5085350123456789003',
            Icici\ResponseConstants::AUTH_PROC_CODE                             => '421000',
            Icici\ResponseConstants::AUTH_AMOUNT                                => '000000010000',
            Icici\ResponseConstants::AUTH_SYSTEM_TRACE_AUDIT_NO                 => '005810',
            Icici\ResponseConstants::AUTH_TIME                                  => '182938',
            Icici\ResponseConstants::AUTH_DATE                                  => '0321',
            Icici\ResponseConstants::AUTH_NETWORK_INTERNATIONAL_IDENTIFIER      => '001',
            Icici\ResponseConstants::AUTH_POINT_OF_SERVICE_CONDITION_CODE       => '05',
            Icici\ResponseConstants::AUTH_RRN                                   => '708018541465',
            Icici\ResponseConstants::AUTH_AUTHORIZATION_IDENTIFICATION_RESPONSE => '541465',
            Icici\ResponseConstants::AUTH_RESPONSE_CODE                         => '00',
            Icici\ResponseConstants::AUTH_CARD_ACCEPTOR_TERMINAL_IDENTIFICATION => '13016403',
            Icici\ResponseConstants::AUTH_CARD_ACCEPTOR_NAME                    => 'Pune - Check PUNE',
            Icici\ResponseConstants::AUTH_ADDITIONAL_AMOUNTS
                => '1001356C0000002548081002356C000000254737',
            Icici\ResponseConstants::AUTH_AUTHENTICATION_CODE
                => 'da433aa16bd84a479300a8f75e329eae',
            Icici\ResponseConstants::AUTH_BENEF_ACCOUNT_NUMBER                  => '1111111111111',
            Icici\ResponseConstants::AUTH_REMITTER
                => 'null|ANIL V KAPOOR|cwduid| | |Card not present.| |n/a||da433aa16bd84a479300a8f75e329eae',
        ];

        $this->content($data, 'auth');

        $xml = $this->generateXml($data);

        return $xml;
    }

    public function refund($input)
    {
        $input = json_decode($input, true);

        $encryptor = $this->getEncryptor();

        $sessionKey = $encryptor->decryptSessionKey($input[Icici\RequestConstants::REFUND_REQUEST_ENCRYPTEDKEY], true);

        $decryptedData = $encryptor->decryptUsingSessionKey(
            $input[Icici\RequestConstants::REFUND_REQUEST_ENCRYPTEDDATA],
            $sessionKey
        );

        $data = json_decode($decryptedData, true);

        $responseData = $this->getRefundResponse($encryptor, $data);

        return $this->prepareResponse($responseData);
    }

    protected function getRefundResponse($encryptor, $data)
    {
        $data = [
            Icici\ResponseConstants::REFUND_SUCCESS       => 'true',
            Icici\ResponseConstants::REFUND_RESPONSE      => '0',
            Icici\ResponseConstants::REFUND_MESSAGE       => 'Transaction Successful',
            Icici\ResponseConstants::REFUND_BANKRRN       => '732516577130',
            Icici\ResponseConstants::REFUND_UPITRANLOGID  => '577130',
            Icici\ResponseConstants::REFUND_USERPROFILE   => '723',
            Icici\ResponseConstants::REFUND_SEQNO         => $data[Icici\RequestConstants::REFUND_DATA_SEQ_NO],
            Icici\ResponseConstants::REFUND_MOBILEAPPDATA => 'MobileAppData',
        ];

        $this->content($data, 'refund');

        $encryptor = $this->getEncryptor();

        $sessionKey = $encryptor->generateSkey();

        $encryptedData = $encryptor->encryptUsingSessionKey(json_encode($data), $sessionKey);

        $encryptedSessionKey = $encryptor->encryptSessionKey($sessionKey, $this->mode, 'refund');

        $responseData = [
            Icici\ResponseConstants::REFUND_RESPONSE_REQUESTID            => '',
            Icici\ResponseConstants::REFUND_RESPONSE_SERVICE              => 'UPI',
            Icici\ResponseConstants::REFUND_RESPONSE_ENCRYPTEDKEY         => $encryptedSessionKey,
            Icici\ResponseConstants::REFUND_RESPONSE_OAEPHASHINGALGORITHM => 'NONE',
            Icici\ResponseConstants::REFUND_RESPONSE_IV                   => '',
            Icici\ResponseConstants::REFUND_RESPONSE_ENCRYPTEDDATA        => $encryptedData,
            Icici\ResponseConstants::REFUND_RESPONSE_CLIENTINFO           => '',
            Icici\ResponseConstants::REFUND_RESPONSE_OPTIONALPARAM        => '',
        ];

        return json_encode($responseData);
    }

    protected function prepareResponse($content)
    {
        $response = \Response::make($content);

        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    protected function makePostResponse($content)
    {

        $response = \Response::make($content);

        $response->headers->set('Content-Type', 'text/xml; charset=UTF-8');
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }

    protected function generateXml(array $data): string
    {
        $content = '<isomsg><header>00000000</header>' . "\n";

        foreach ($data as $key => $value)
        {
            $content .= '<field id="' . $key . '" value="' . $value . '"/>' . "\n";
        }

        $content .= '</isomsg>';

        return $content;
    }

    protected function getEncryptor()
    {
        $encryptor = new Icici\Encryptor(2, $this->getGatewayInstance()->getIv());

        $encryptor->setPublicKey($this->getPublicKey());
        $encryptor->setPrivateKey($this->getPrivateKey());

        return $encryptor;
    }

    protected function getPublicKey()
    {
        return file_get_contents(__DIR__ . '/' . Gateway::MOCK_CERT_PATH_PUBLIC);
    }

    protected function getPrivateKey()
    {
        return file_get_contents(__DIR__ . '/' . Gateway::MOCK_CERT_PATH_PRIVATE);
    }
}
