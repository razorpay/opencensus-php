<?php

namespace RZP\Gateway\Netbanking\Pnb\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Netbanking\Pnb\Status;
use RZP\Gateway\Netbanking\Pnb\RequestFields;
use RZP\Gateway\Netbanking\Pnb\ResponseFields;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        $decryptedString = $this->getGatewayInstance()
                                ->decryptString($input[RequestFields::ENCDATA]);

        $decryptedData = $this->getDecryptedData($decryptedString);

        $this->validateActionInput($decryptedData);

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

    protected function getCallbackResponseData(array $input)
    {
        $data = [
            ResponseFields::BANK_PAYMENT_STATUS => Status::SUCCESS,
            ResponseFields::BANK_TRANSACTION_ID => self::MOCK_TRANSACTION_ID,
            ResponseFields::CHALLAN_NUMBER      => $input[RequestFields::CHALLAN_NUMBER],
        ];

        return $data;
    }

    protected function getEncryptedData(array $data)
    {
        $dataString = http_build_query($data, null, '|');

        $encryptedString = $this->getGatewayInstance()
                                ->encryptString($dataString);

        return [ResponseFields::ENCDATA => $encryptedString];
    }

    protected function getDecryptedData(string $decryptedString): array
    {
        $decryptedString = str_replace('|', '&', $decryptedString);

        parse_str($decryptedString, $decryptedData);

        return $decryptedData;
    }
}
