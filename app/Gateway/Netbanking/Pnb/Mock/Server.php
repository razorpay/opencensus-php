<?php

namespace RZP\Gateway\Netbanking\Pnb\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Netbanking\Pnb\Status;
use RZP\Gateway\Netbanking\Pnb\RequestFields;
use RZP\Gateway\Netbanking\Pnb\ResponseFields;

class Server extends Base\Mock\Server
{
    /**
     * Mock authorize
     *
     * The input array received here is similar to the response
     * we expect to receive from the gateway.
     * We run the decryption logic and return the post-response.
     *
     * @param  array $input
     * @return array
     */
    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        $decryptedString = $this->getGatewayInstance()
                                ->decryptString($input[RequestFields::ENCDATA]);

        $decryptedData = $this->getDecryptedData($decryptedString);

        $callbackDataArray = $this->getCallbackResponseData($decryptedData);

        $this->content($callbackDataArray, 'authorize');

        $response = $this->getEncryptedData($callbackDataArray);

        $request = [
            'url'     => $decryptedData[RequestFields::RETURN_URL],
            'content' => $response,
            'method'  => 'post',
        ];

        return $this->makePostResponse($request);
    }

    /**
     * Mock verify
     * The request-response flow of verify for Pnb
     * is same as that of payment request-response
     *
     * @param  array $input
     * @return array
     */
    public function verify($input)
    {
        parent::verify($input);

        $decryptedString = $this->getGatewayInstance()
                                ->decryptString($input[RequestFields::ENCDATA]);

        $decryptedData = $this->getDecryptedData($decryptedString);

        $this->validateActionInput($decryptedData);

        $callbackDataArray = $this->getCallbackResponseData($decryptedData);

        $this->content($callbackDataArray, 'verify');

        $response = $this->getEncryptedData($callbackDataArray);

        return $this->makeResponse($response);
    }

    /**
     * Sets the data expected from the callback.
     * Attrs set are 'BankStatus', 'BankTransID', 'CIN'
     *
     * @param  array $input
     * @return array $data
     */
    protected function getCallbackResponseData(array $input)
    {
        $data = [
            ResponseFields::BANK_PAYMENT_STATUS => Status::SUCCESS,
            ResponseFields::BANK_TRANSACTION_ID => self::MOCK_TRANSACTION_ID,
            ResponseFields::CHALLAN_NUMBER      => $input[RequestFields::CHALLAN_NUMBER],
        ];

        return $data;
    }

    /**
     * Converts data array to encrypted string
     * as expected to be returned by bank
     *
     * @param array $data
     * @param ['encdata' => $encryptedString];
     */
    protected function getEncryptedData(array $data)
    {
        $dataString = http_build_query($data, null, '|');

        $encryptedString = $this->getGatewayInstance()
                                ->encryptString($dataString);

        return [ResponseFields::ENCDATA => $encryptedString];
    }

    /**
     * Converts decrypted data to array
     * Follows the logic of 'formatDecryptedResponseString' in Pnb Gateway
     *
     * @param  string $decryptedString
     * @return array  $decryptedData
     */
    protected function getDecryptedData(string $decryptedString): array
    {
        $search = '|';

        $replace = '&';

        $decryptedString = str_replace($search, $replace, $decryptedString);

        parse_str($decryptedString, $decryptedData);

        return $decryptedData;
    }
}
