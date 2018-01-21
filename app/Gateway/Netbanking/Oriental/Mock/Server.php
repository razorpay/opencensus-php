<?php

namespace RZP\Gateway\Netbanking\Oriental\Mock;

use RZP\Gateway\Base\Mock;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Netbanking\Oriental\Status;
use RZP\Gateway\Netbanking\Oriental\RequestFields;
use RZP\Gateway\Netbanking\Oriental\ResponseFields;

/**
 * This class cannot be marked as final as it will be mocked for test cases
 * Class Server
 * @package RZP\Gateway\Netbanking\Oriental\Mock
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

        $this->content($content);

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

        $this->validateActionInput($input, 'verify');

        $response = $this->getVerifyResponse($input);

        return $this->makeResponse($response);
    }

    private function getAuthResponse(array $input)
    {
        $queryArray = $this->getQueryArray($input[RequestFields::QUERY_STRING]);

        return [
            ResponseFields::PAID            => Status::SUCCESS,
            ResponseFields::BANK_PAYMENT_ID => 9999999999,
            ResponseFields::CURRENCY        => Currency::INR,
            ResponseFields::AMOUNT          => $queryArray[RequestFields::TXN_AMOUNT],
            ResponseFields::PAYEE_ID        => $queryArray[RequestFields::PAYEE_ID],
            ResponseFields::PAY_REF_NUM     => $queryArray[RequestFields::PAY_REF_NUM],
            ResponseFields::ITEM_CODE       => $queryArray[RequestFields::ITEM_CODE],
            ResponseFields::DEBIT_ACC_NUM   => 1234567890,
        ];
    }

    private function getVerifyResponse(array $input)
    {
        return [
            ResponseFields::PAYEE_ID        => $input[RequestFields::PAYEE_ID],
            ResponseFields::PAY_REF_NUM     => $input[RequestFields::PAY_REF_NUM],
            ResponseFields::ITEM_CODE       => $input[RequestFields::ITEM_CODE],
            ResponseFields::AMOUNT          => $input[RequestFields::AMOUNT],
            ResponseFields::CURRENCY        => $input[RequestFields::CRN],
            ResponseFields::BANK_PAYMENT_ID => $input[RequestFields::BID],
            ResponseFields::TXN_STATUS      => Status::VERIFY_SUCCESS,
        ];
    }

    private function getQueryArray(string $queryString)
    {
        $querySubArray = explode('|', $queryString);

        $array = [];

        foreach ($querySubArray as $subArray)
        {
            $explodedArray = explode('~', $subArray);

            $key = $explodedArray[0];

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
