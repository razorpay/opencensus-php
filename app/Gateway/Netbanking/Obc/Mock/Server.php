<?php

namespace RZP\Gateway\Netbanking\Obc\Mock;

use RZP\Gateway\Base\Mock;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Netbanking\Obc\Status;
use RZP\Gateway\Netbanking\Obc\RequestFields;
use RZP\Gateway\Netbanking\Obc\ResponseFields;

/**
 * This class cannot be marked as final as it will be mocked for test cases
 * Class Server
 * @package RZP\Gateway\Netbanking\Obc\Mock
 */
class Server extends Mock\Server
{
    /**
     * @var Gateway
     */
    private $gatewayInstance = null;

    public function authorize($input)
    {
        parent::authorize($input);

        $this->decryptAuthRequest($input);

        $this->validateAuthorizeInput($input);

        $content = $this->getAuthResponse($input);

        $request = [
            'url'     => $input[RequestFields::RETURN_URL],
            'content' => $content,
            'method'  => 'get',
        ];

        return $this->makePostResponse($request);
    }

    public function verify($input)
    {
        parent::verify($input);

        $this->validateActionInput($input, $this->action);

        $verifyResponse = $this->getVerifyResponse($input);

        $stringResponse = $this->getResponseString($verifyResponse);

        return $this->makeResponse($stringResponse);
    }

    private function getAuthResponse(array $input): array
    {
        $queryArray = $this->getQueryArray($input[RequestFields::QUERY_STRING]);

        $this->validateActionInput($queryArray, $this->action . '_qs');

        $content = [
            ResponseFields::PAID            => Status::SUCCESS,
            ResponseFields::BANK_PAYMENT_ID => 9999999999,
            ResponseFields::CURRENCY        => Currency::INR,
            ResponseFields::AMOUNT          => $queryArray[RequestFields::TXN_AMOUNT],
            ResponseFields::PAYEE_ID        => $queryArray[RequestFields::PAYEE_ID],
            ResponseFields::PAY_REF_NUM     => $queryArray[RequestFields::PAY_REF_NUM],
            ResponseFields::ITEM_CODE       => $queryArray[RequestFields::ITEM_CODE],
            ResponseFields::DEBIT_ACC_NUM   => 1234567890,
        ];

        $this->content($content, $this->action);

        $queryStringToEncrypt = http_build_query($content);

        $encryptedString = $this->encrypt($queryStringToEncrypt);

        return [$encryptedString => ""];
    }

    private function getVerifyResponse(array $input)
    {
        $verifyResponseArray = [
            ResponseFields::PAYEE_ID        => $input[RequestFields::PAYEE_ID],
            ResponseFields::PAY_REF_NUM     => $input[RequestFields::PAY_REF_NUM],
            ResponseFields::ITEM_CODE       => $input[RequestFields::ITEM_CODE],
            ResponseFields::AMOUNT          => $input[RequestFields::AMOUNT],
            ResponseFields::BANK_PAYMENT_ID => $input[RequestFields::BID],
            ResponseFields::TXN_STATUS      => Status::VERIFY_SUCCESS,
        ];

        return $verifyResponseArray;
    }


    private function getResponseString($reponse)
    {
        $responseString = '';

        foreach ($reponse as $key => $value)
        {
            $responseString .= $key . '=' . $value . '|';
        }

        return $responseString;
    }

    private function getQueryArray(string $queryString)
    {
        $querySubArray = explode('|', $queryString);

        $array = [];

        foreach ($querySubArray as $subArray)
        {
            $explodedArray = explode('~', $subArray);

            $key = explode('.', $explodedArray[0])[1];

            $value = $explodedArray[1];

            $array[$key] = $value;
        }

        return $array;
    }

    private function decryptAuthRequest(array & $input)
    {
        $input[RequestFields::RETURN_URL] = $this->decrypt($input[RequestFields::RETURN_URL]);
        $input[RequestFields::QUERY_STRING] = $this->decrypt($input[RequestFields::QUERY_STRING]);
    }

    /**
     * This method encrypts and then encodes the input string
     * @param string $stringToEncrypt
     * @return string
     */
    private function encrypt(string $stringToEncrypt)
    {
        return $this->getGatewayInstance()->encrypt($stringToEncrypt);
    }

    private function decrypt(string $stringToDecrypt)
    {
        return $this->getGatewayInstance()->decrypt($stringToDecrypt);
    }

    protected function getGatewayInstance($bankingType = null)
    {
        if ($this->gatewayInstance === null)
        {
            $this->gatewayInstance = parent::getGatewayInstance($bankingType);
        }

        return $this->gatewayInstance;
    }
}
