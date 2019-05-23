<?php

namespace RZP\Gateway\Card\Fss\Mock;

use Config;
use RZP\Gateway\Card\Fss;
use RZP\Gateway\Card\Fss\Fields;
use RZP\Gateway\Card\Fss\Entity;
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

        $this->request($input, 'authorize');

        $this->validateAuthorizeInput($input);

        $acquirer = $input[Fields::ACQUIRER];

        $requestData = $this->getDecryptedData($input[Fields::TRAN_DATA], $acquirer);

        $this->request($requestData, 'authorize_decrypted');

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
            Entity::POST_DATE                   => 'null',
            Fields::RESULT                      => Fss\Status::CAPTURED,
        ];

        $this->content($responseTranData, 'authorize');

        $responseData = [
            Fields::GATEWAY_PAYMENT_ID      => $gatewayPaymentId,
            Fields::TRAN_DATA               => $this->getEncryptedData($responseTranData, $acquirer),
        ];

        $url = $input[Fields::RESPONSE_URL];

        $url .= '?' . http_build_query($responseData);

        return $url;
    }

    public function refund($input)
    {
        parent::refund($input);

        $this->request($input, 'refund');

        $input = (array) simplexml_load_string($input);

        $this->validateRefundInput($input);

        $refundResponse = [
            Fields::RESULT                 => Fss\Status::CAPTURED,
            Entity::GATEWAY_TRANSACTION_ID => $this->generateId(15),
            Fields::TRACK_ID               => $input[Fields::TRACK_ID],
            Fields::PAY_ID                 => $this->generateId(15),
            Fields::AMOUNT                 => $input[Fields::AMOUNT],
            Fields::AUTH_RES_CODE          => $this->generateId(3),
            Fields::REF                    => $this->generateId(12),
        ];

        $this->content($refundResponse, 'refund');

        $content = Fss\Utility::createRequestXml($refundResponse, false);

        return $this->prepareResponse($content);
    }

    public function verifyRefund($input)
    {
        parent::verify($input);

        $this->request($input, 'verify_refund');

        $input = (array) simplexml_load_string($input);

        $responseData = [
            Fields::RESULT         => '',
            Fields::AMOUNT         => $input[Fields::AMOUNT],
            Fields::TRACK_ID       => $input[Fields::TRACK_ID],
            Fields::TRANSACTION_ID => $input[Fields::TRANSACTION_ID],
        ];

        $this->content($responseData, 'verify_refund');

        $content = Fss\Utility::createRequestXml($responseData, false);

        return $this->prepareResponse($content);
    }

    public function verify($input)
    {
        parent::verify($input);

        $this->request($input, 'verify');

        $input = (array) simplexml_load_string($input);

        $responseData = [
            Fields::RESULT         => Fss\Status::SUCCESS,
            Fields::AMOUNT         => $input[Fields::AMOUNT],
            Fields::TRACK_ID       => $input[Fields::TRACK_ID],
            Fields::TRANSACTION_ID => $input[Fields::TRANSACTION_ID],
        ];

        $this->content($request, 'verify');

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

    protected function getEncryptedData($responseTrandata, string $acquirer)
    {
        $tranData = Fss\Utility::createRequestXml($responseTrandata, false);

        $secretKey = $this->getGatewaySecret($acquirer);

        $encryptedText = '';

        switch ($acquirer)
        {
            case Fss\Acquirer::BOB:
                $crypto = new Fss\TripleDESCrypto(Fss\TripleDESCrypto::MODE_ECB, $secretKey, false);

                $encryptedText = $crypto->encryptString($tranData);
                break;
            case Fss\Acquirer::FSS:
                $crypto = new Fss\AesCrypto(Fss\AesCrypto::MODE_CBC, $secretKey, $secretKey);

                $encryptedText = $crypto->encryptString($tranData);
                break;
            case Fss\Acquirer::SBI:
                $crypto = new Fss\SbiAesCrypto(Fss\SbiAesCrypto::MODE_ECB, $secretKey, $secretKey);

                return $crypto->encryptString($tranData);
                break;
            default:
                break;
        }

        return $encryptedText;
    }

    /**
     * @param string $str
     * @param string $acquirer
     *
     * @return array
     */
    protected function getDecryptedData(string $str, string $acquirer): array
    {
        $secretKey = $this->getGatewaySecret($acquirer);

        $decryptedString = '';

        switch ($acquirer)
        {
            case Fss\Acquirer::BOB:
                $crypto = new Fss\TripleDESCrypto(Fss\TripleDESCrypto::MODE_ECB, $secretKey, true);

                $decryptedString = $crypto->decryptString($str);
                break;
            case Fss\Acquirer::FSS:
                $crypto = new Fss\AesCrypto(Fss\AesCrypto::MODE_CBC, $secretKey, $secretKey);

                $decryptedString = $crypto->decryptString($str);
                break;
            case Fss\Acquirer::SBI:
                $crypto = new Fss\SbiAesCrypto(Fss\SbiAesCrypto::MODE_ECB, $secretKey);

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

    private function getGatewaySecret($acquirer)
    {
        return Config::get('gateway.card_fss.' . strtolower($acquirer) . '.test_hash_secret');
    }
}
