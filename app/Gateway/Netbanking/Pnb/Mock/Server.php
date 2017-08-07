<?php

namespace RZP\Gateway\Netbanking\Pnb\Mock;

use DOMDocument;

use RZP\Gateway\Base;
use RZP\Gateway\Netbanking\Pnb\Status;
use RZP\Gateway\Netbanking\Pnb\RequestFields;
use RZP\Gateway\Netbanking\Pnb\ResponseFields;

class Server extends Base\Mock\Server
{
    const MOCK_TRANSACTION_ID = 99999999;

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
            'content' => [
                RequestFields::ENCDATA => $response
            ],
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

        $callbackDataArray = $this->getVerifyResponseData($decryptedData);

        $this->content($callbackDataArray, 'verify');

        $encdata = $this->getEncryptedData($callbackDataArray);

        $html = $this->prepareVerifyResponseHtml($encdata);

        return $this->prepareResponse($html);
    }

    protected function getVerifyResponseData(array $input)
    {
        $data = [
            ResponseFields::BANK_PAYMENT_STATUS_VERIFY => Status::SUCCESS,
            ResponseFields::BANK_TRANSACTION_ID_VERIFY => self::MOCK_TRANSACTION_ID,
            ResponseFields::CHALLAN_NUMBER_VERIFY      => $input[RequestFields::CHALLAN_NUMBER],
            ResponseFields::ITEM_CODE                  => $input[RequestFields::ITEM_CODE],
        ];

        return $data;
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

        return $encryptedString;
    }

    protected function getDecryptedData(string $decryptedString): array
    {
        $decryptedString = str_replace('|', '&', $decryptedString);

        parse_str($decryptedString, $decryptedData);

        return $decryptedData;
    }

    protected function prepareVerifyResponseHtml($content)
    {
        ob_start();

        require ('VerifyResponseHtml.php');

        $html = ob_get_clean();

        $html = str_replace("{{encdata}}", $content, $html);

        return $html;
    }

    protected function prepareResponse($html)
    {
        $response = \Response::make($html);

        $response->headers->set('Content-Type', 'text/html; charset=UTF-8');
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }
}
