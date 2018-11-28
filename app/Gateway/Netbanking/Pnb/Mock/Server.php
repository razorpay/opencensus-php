<?php

namespace RZP\Gateway\Netbanking\Pnb\Mock;

use DOMDocument;

use http\Env\Request;
use RZP\Gateway\Base;
use RZP\Gateway\Netbanking\Pnb\Status;
use RZP\Gateway\Netbanking\Pnb\RequestFields;
use RZP\Gateway\Netbanking\Pnb\ResponseFields;

class Server extends Base\Mock\Server
{
    const MOCK_TRANSACTION_ID = '99999999';

    const MOCK_REFUND_ID = '11111111';

    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        $decryptedString = $this->decryptString($input[RequestFields::ENCRYPTED_DATA]);

        $decryptedData = $this->getDecryptedData($decryptedString);

        $this->validateActionInput($decryptedData);

        //TODO verify hash

        $callbackDataArray = $this->getCallbackResponseData($decryptedData);

        $encryptedData = $this->getEncryptedData($callbackDataArray);

        $request = [
            'url'     => $decryptedData[RequestFields::RETURN_URL],
            'content' => [
                ResponseFields::API_KEY       => $input[ResponseFields::API_KEY],
                RequestFields::ENCRYPTED_DATA => $encryptedData
            ],
            'method'  => 'post',
        ];

        return $this->makePostResponse($request);
    }

    public function verify($input)
    {
        parent::verify($input);

        $this->validateActionInput($input);

        //TODO verify hash

        $data = $this->getVerifyResponseData($input);

        return $this->makeResponse($data);
    }

    public function refund($input)
    {
        parent::refund($input);

        $this->validateActionInput($input);

        //TODO verify hash

        $response = $this->getRefundResponseData($input);

        $this->content($response, 'refund');

        return $this->makeResponse($response);
    }

    protected function getVerifyResponseData(array $input)
    {
        $payment = $this->repo->payment->findOrFail($input[RequestFields::PAYMENT_ID]);

        $data = [
            ResponseFields::BANK_PAYMENT_ID => self::MOCK_TRANSACTION_ID,
            ResponseFields::PAYMENT_ID      => $input[ResponseFields::PAYMENT_ID],
            ResponseFields::AMOUNT          => $this->formatAmount($payment->getAmount()),
            ResponseFields::BANK_CODE       => $input[RequestFields::BANK_CODE],
            ResponseFields::RESPONSE_CODE   => '0',
        ];

        $this->content($data, 'verify');

        return [
            'data'                   => $data,
            ResponseFields::CHECKSUM => $this->generateHash($data)
        ];
    }

    protected function getRefundResponseData($request)
    {
        $data = [
            ResponseFields::REFUND_ID           => '123',
            ResponseFields::BANK_PAYMENT_ID     => $request[RequestFields::BANK_PAYMENT_ID],
            //TODO need clarity on below two fields
            ResponseFields::MERCHANT_ORDER_ID   => '',
            ResponseFields::MERCHANT_REFUND_ID  => '',
            ResponseFields::REFUND_REFERENCE_NO => self::MOCK_REFUND_ID,
        ];

        return [
            'data'                   => json_encode($data),
            ResponseFields::CHECKSUM => $this->generateHash($data)
        ];
    }

    protected function getCallbackResponseData(array $input)
    {
        $data = [
            ResponseFields::RESPONSE_CODE   => Status::SUCCESS,
            ResponseFields::BANK_PAYMENT_ID => self::MOCK_TRANSACTION_ID,
            ResponseFields::PAYMENT_ID      => $input[RequestFields::PAYMENT_ID],
            ResponseFields::AMOUNT          => $input[RequestFields::AMOUNT]
        ];

        $this->content($data, 'authorize');

        $data[ResponseFields::CHECKSUM] = $this->generateHash($data);

        return $data;
    }

    protected function getEncryptedData(array $data)
    {
        $encryption_key = $this->app['config']['gateway']['netbanking_pnb']['test_decryption_key'];

        $encryptedString = json_encode($data);

        return base64_encode(openssl_encrypt(
                                              $encryptedString,
                                      'AES-256-ECB',
                                              $encryption_key,
                                      OPENSSL_RAW_DATA
            )
        );
    }

    protected function getDecryptedData(string $decryptedString): array
    {
        return json_decode($decryptedString, true);
    }

    protected function decryptString($encryptedString)
    {
        $decryption_key = $this->app['config']['gateway']['netbanking_pnb']['test_encryption_key'];

        return openssl_decrypt(base64_decode($encryptedString),
            'AES-256-ECB',
            $decryption_key,
            OPENSSL_RAW_DATA);
    }

    protected function formatAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function generateHash($content)
    {
        $response_json = json_encode($content);

        $hash_data = $this->getSalt() . $response_json;

        return strtoupper(hash('sha512', $hash_data));
    }

    protected function getSalt()
    {
        return $this->getGatewayInstance()->getSalt();
    }
}
