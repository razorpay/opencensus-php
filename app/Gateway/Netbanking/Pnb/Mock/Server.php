<?php

namespace RZP\Gateway\Netbanking\Pnb\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Netbanking\Pnb;

class Server extends Base\Mock\Server
{
    /**
     * Mock authorize
     * The input array received here is the kind of response
     * we expect to receive from the gateway.
     * We run the decryption logic and return the post-response.
     *
     * @param  array $input
     * @return array
     */
    public function authorize($input)
    {
        parent::authorize($input);

        // validate the input
        $this->validateAuthorizeInput($input);

        // get decrypted string from 'encdata'
        $decryptedString = $this->getGatewayInstance()
                                ->decryptString($input[Pnb\RequestFields::ENCDATA]);

        // converts decrypted string to data array
        $decryptedData = $this->getDecryptedData($decryptedString);

        // sets callback data from input
        $callbackDataArray = $this->getCallbackResponseData($decryptedData);

        // set mock content for action authorize
        $this->content($callbackDataArray, 'authorize');

        // encrypts data
        $response = $this->getEncryptedData($callbackDataArray);

        $request = [
            'url'     => $decryptedData[Pnb\RequestFields::RETURN_URL],
            'content' => $response,
            'method'  => 'post',
        ];

        // sends post-respose as expected from bank
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

        // get decrypted string from 'encdata'
        $decryptedString = $this->getGatewayInstance()
                                ->decryptString($input[Pnb\RequestFields::ENCDATA]);

        // get decrypted string from 'encdata'
        $decryptedData = $this->getDecryptedData($decryptedString);

        // validates verify's input attributes
        $this->validateActionInput($decryptedData);

        // sets callback data from input. This same for verify in case of Pnb
        $callbackDataArray = $this->getCallbackResponseData($decryptedData);

        // set mock content for action verify
        $this->content($callbackDataArray, 'verify');

        // encrypts data
        $response = $this->getEncryptedData($callbackDataArray);

        // sends post-respose as expected from bank
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
            Pnb\ResponseFields::BANK_PAYMENT_STATUS => Pnb\Status::SUCCESS,
            Pnb\ResponseFields::BANK_TRANSACTION_ID => 99999999,
            Pnb\ResponseFields::CHALLAN_NUMBER      => $input[Pnb\RequestFields::CIN],
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
        // string http_build_query($data, $prefix, $delimiter)
        $dataString = http_build_query($data, null, '|');

        // get decrypted string from 'encdata'
        $encryptedString = $this->getGatewayInstance()
                                ->encryptString($dataString);

        return [Pnb\ResponseFields::ENCDATA => $encryptedString];
    }

    /**
     * Converts decrypted data to array
     * Follows the logic of 'formatDecrytedResponseString' in Pnb Gateway
     *
     * @param  string $decryptedString
     * @return array  $decryptedData
     */
    protected function getDecryptedData(string $decryptedString): array
    {
        $data = explode('|', $decryptedString);

        $decryptedData = [];

        foreach ($data as $dataItem)
        {
            $keyValue = explode('=', $dataItem);

            $decryptedData[$keyValue[0]] = $keyValue[1];
        }

        return $decryptedData;
    }
}
