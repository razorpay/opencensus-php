<?php

namespace RZP\Gateway\Fss\Mock;

use Config;
use RZP\Gateway\Fss;
use RZP\Gateway\Fss\Fields;
use RZP\Gateway\Fss\Entity;
use RZP\Gateway\Base;
use RZP\Models\Terminal\Repository as TerminalRepo;

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

        $tranportalId = $input[Fields::TRANPORTAL_ID];

        $requestData = $this->getDecryptedData($input[Fields::TRAN_DATA], $tranportalId);

        // Validating Transaction data which we sent to server after encrypting.
        $this->validateActionInput($requestData, 'authTransactionData');

        $gatewayPaymentId = $this->generateId(15);

        $responseTranData = [
            Fields::PAY_ID                      => $gatewayPaymentId,
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
            Fields::TRAN_DATA               => $this->getEncryptedData($responseTranData, $tranportalId),
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
            Fields::PAY_ID                      => $this->generateId(15),
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
            Fields::RESULT         => Fss\Status::SUCCESS,
            Fields::AMOUNT         => $input[Fields::AMOUNT],
            Fields::TRACK_ID       => $input[Fields::TRACK_ID],
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

    protected function getEncryptedData($responseTrandata, $terminalId)
    {
        $tranData = Fss\Utility::createRequestXml($responseTrandata, false);

        list($secretKey, $gatewayAcquirer) = $this->getGatewaySecretAndAcquirer($terminalId);

        $crypto = new Fss\TripleDESCrypto(Fss\TripleDESCrypto::MODE_ECB, $secretKey, false);

        $encryptedText = $crypto->encryptString($tranData, false);

        return $encryptedText;
    }

    /**
     * @param string $str
     * @param string $terminalId
     *
     * @return array
     */
    protected function getDecryptedData(string $str, string $terminalId): array
    {
        list($secretKey, $gatewayAcquirer) = $this->getGatewaySecretAndAcquirer($terminalId);

        $decryptedString = "";

        switch ($gatewayAcquirer)
        {
            case Fss\Acquirer::BOB:
                $crypto = new Fss\TripleDESCrypto(Fss\TripleDESCrypto::MODE_ECB, $secretKey, true);

                $decryptedString = $crypto->decryptString($str);
                break;
            case Fss\Acquirer::FSS:
                $crypto = new Fss\AesCrypto(Fss\AesCrypto::MODE_CBC, $secretKey, $secretKey);

                $decryptedString = $crypto->decryptString($str);
                break;
            default:
                break;
        }

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

    private function getGatewaySecretAndAcquirer($terminalId)
    {
        $terminal = (new TerminalRepo)->getByGatewayTerminalId($terminalId);

        $gatewayAcquirer = $terminal->getGatewayAcquirer();

        return [Config::get('gateway.fss.' . $gatewayAcquirer . '_test_hash_secret'), $gatewayAcquirer];
    }
}
