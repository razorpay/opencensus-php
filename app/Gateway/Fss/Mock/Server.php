<?php

namespace RZP\Gateway\Fss\Mock;

use phpseclib\Crypt\TripleDES;
use RZP\Gateway\Fss;
use RZP\Gateway\Fss\Fields;
use RZP\Gateway\Fss\Entity;
use RZP\Gateway\Base;

class Server extends Base\Mock\Server
{
    public function __construct()
    {
        parent::__construct();

        $this->repo = new Fss\Repository;
    }

    public function authorize($input)
    {
        parent::authorize($input);

        $this->request($input);

        $this->validateAuthorizeInput($input);

        $requestData = $this->getDecryptedData($input[Fields::TRAN_DATA]);

        // Validating Transaction data which we sent to server after encrypting.
        $this->validateActionInput($requestData, 'authTransactionData');

        $gatewayPaymentId = $this->generateId(15);

        $responseTranData = [
            Fields::GATEWAY_CALLBACK_PAYMENT_ID => $gatewayPaymentId,
            Fields::TRACK_ID                    => $requestData[Fields::TRACK_ID],
            Entity::GATEWAY_TRANSACTION_ID      => $this->generateId(15),
            Entity::REF                         => $this->generateId(9),
            Fields::AMOUNT                      => $requestData[Fields::AMOUNT],
            Entity::AUTH                        => $this->generateId(8),
            Entity::POST_DATE                   => "null",
            Fields::RESULT                      => Fss\Status::CAPTURED,
        ];

        $this->content($responseTranData);

        $responseData = [
            Fields::GATEWAY_PAYMENT_ID      => $gatewayPaymentId,
            Fields::TRAN_DATA               => $this->getEncryptedData($responseTranData),
        ];

        $url = $input[Fields::RESPONSE_URL];

        $url .= '?' . http_build_query($responseData);

        return $url;
    }

    public function refund($input)
    {
        parent::refund($input);

        $this->request($input);

        $input = (array) simplexml_load_string($input);

        $this->validateRefundInput($input);

        $refundResponse = [
            Fields::RESULT                      => Fss\Status::CAPTURED,
            Entity::GATEWAY_TRANSACTION_ID      => $this->generateId(15),
            Fields::TRACK_ID                    => $input[Fields::TRACK_ID],
            Fields::GATEWAY_CALLBACK_PAYMENT_ID => $this->generateId(15),
            Fields::AMOUNT                      => $input[Fields::AMOUNT],
            Fields::AUTH_RES_CODE               => $this->generateId(3),
        ];

        $this->content($refundResponse);

        $content = Fss\Utility::createRequestXml($refundResponse, false);

        return $this->prepareResponse($content);
    }

    public function verifyRefund($input)
    {
        parent::verify($input);

        $this->request($input);

        $input = (array) simplexml_load_string($input);

        $responseData = [
            Fields::RESULT => Fss\Status::SUCCESS,
            Fields::AMOUNT  => $input[Fields::AMOUNT],
            Fields::TRACK_ID => $input[Fields::TRACK_ID],
            Fields::TRANSACTION_ID => $input[Fields::TRANSACTION_ID],
        ];

        $this->content($responseData, 'verify_refund');

        $content = Fss\Utility::createRequestXml($responseData, false);

        return $this->prepareResponse($content);
    }

    public function verify($input)
    {
        parent::verify($input);

        $this->request($input);

        $input = (array) simplexml_load_string($input);

        $responseData = [
            Fields::RESULT => Fss\Status::SUCCESS,
            Fields::AMOUNT  => $input[Fields::AMOUNT],
            Fields::TRACK_ID => $input[Fields::TRACK_ID],
            Fields::TRANSACTION_ID => $input[Fields::TRANSACTION_ID],
        ];

        $this->content($request);

        $content = Fss\Utility::createRequestXml($responseData, false);

        return $this->prepareResponse($content);
    }

    protected function prepareResponse($content)
    {
        $response = \Response::make($content);

        $response->headers->set('Content-Type', 'application/xml');

        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }

    protected function getEncryptedData($responseTrandata)
    {
        $tranData = Fss\Utility::createRequestXml($responseTrandata, false);

        $secretKey = $this->getGatewayInstance()->getSecret();

        $crypto = new Fss\TripleDESCrypto(TripleDES::MODE_ECB, $secretKey, false);

        $encryptedText = $crypto->encryptString($tranData, false);

        return $encryptedText;
    }

    /**
     * @param string $str
     *
     * @return array
     */
    protected function getDecryptedData(string $str): array
    {
        $secretKey = $this->getGatewayInstance()->getSecret();

        $crypto = new Fss\TripleDESCrypto(TripleDES::MODE_ECB, $secretKey, true);

        $decryptedString = $crypto->decryptString($str, true);

        // By default decrypted comes with only fields instead of nested, to let simple xml understand the data.
        //we wrap around response.

        $decryptedResult = (array) simplexml_load_string($decryptedString);

        return $decryptedResult;
    }

    /**
     * @param $size
     *
     * @return int
     */
    protected function generateId($size)
    {
        return random_integer($size);
    }
}
