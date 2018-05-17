<?php

namespace RZP\Gateway\Netbanking\Obc\Mock;

use RZP\Gateway\Base;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Netbanking\Obc\Status;
use RZP\Gateway\Netbanking\Obc\RequestFields;
use RZP\Gateway\Netbanking\Obc\ResponseFields;

class Server extends Base\Mock\Server
{
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

        $content = $this->getVerifyResponse($input);

        return $this->makeResponse($content);
    }

    protected function getAuthResponse(array $input): array
    {
        $queryArray = $this->parseQuery($input[RequestFields::QUERY_STRING]);

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

        return [$encryptedString => ''];
    }

    protected function getVerifyResponse(array $input)
    {
        $content = [
            ResponseFields::PAYEE_ID        => $input[RequestFields::PAYEE_ID],
            ResponseFields::PAY_REF_NUM     => $input[RequestFields::PAY_REF_NUM],
            ResponseFields::ITEM_CODE       => $input[RequestFields::ITEM_CODE],
            ResponseFields::AMOUNT          => $input[RequestFields::AMOUNT],
            ResponseFields::BANK_PAYMENT_ID => $input[RequestFields::BID],
            ResponseFields::TXN_STATUS      => Status::VERIFY_SUCCESS,
        ];

        $this->content($content, $this->action);

        // when BID is not found in OBC db, response is a string. refer testVerifyBidNotfound
        if (is_array($content) === true)
        {
            $stringResponse = http_build_query($content, null, '|');
        }
        else
        {
            $stringResponse = $content;
        }

        return $stringResponse;
    }

    protected function parseQuery(string $query)
    {
        $querySubArray = explode('|', $query);

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

    protected function decryptAuthRequest(array & $input)
    {
        $input[RequestFields::RETURN_URL] = $this->decrypt($input[RequestFields::RETURN_URL]);
        $input[RequestFields::QUERY_STRING] = $this->decrypt($input[RequestFields::QUERY_STRING]);
    }

    protected function encrypt(string $stringToEncrypt)
    {
        return $this->getGatewayInstance()->encrypt($stringToEncrypt);
    }

    protected function decrypt(string $stringToDecrypt)
    {
        return $this->getGatewayInstance()->decrypt($stringToDecrypt);
    }
}
