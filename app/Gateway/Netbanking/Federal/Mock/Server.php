<?php

namespace RZP\Gateway\Netbanking\Federal\Mock;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Models\Bank\IFSC;
use RZP\Gateway\Netbanking\Federal\Status;
use RZP\Gateway\Netbanking\Federal\RequestFields;
use RZP\Gateway\Netbanking\Federal\ResponseFields;

class Server extends Base\Mock\Server
{
    protected $bank = IFSC::FDRL;

    public function authorize($input)
    {
        parent::authorize($input);

        if (isset($input['Action_ShoppingMall_Login_Init']) === true)
        {
            $input[RequestFields::ACTION] = 'Y';
            unset($input['Action_ShoppingMall_Login_Init']);
        }

        $this->validateAuthorizeInput($input);

        $this->validateChecksum($input);

        $response = $this->getCallbackResponseData($input);

        $this->content($response, 'authorize');

        $response = $this->generateHash($response);

        $request = [
            'url' => $input[RequestFields::RETURN_URL],
            'content' => $response,
            'method' => 'post',
        ];

        return $this->makePostResponse($request);
    }

    public function verify($input)
    {
        parent::verify($input);

        $this->validateActionInput($input);

        $response = $this->getVerifyResponseData($input);

        return $this->makeResponse($response);
    }

    protected function getCallbackResponseData(array $input)
    {
        $data = [
            ResponseFields::AMOUNT          => $input[RequestFields::AMOUNT],
            ResponseFields::BANK_PAYMENT_ID => 99999999,
            ResponseFields::ITEM_CODE       => $input[RequestFields::ITEM_CODE],
            ResponseFields::PAYMENT_ID      => $input[RequestFields::PAYMENT_ID],
            ResponseFields::STATE_FLAG      => $input[RequestFields::STATE_FLAG],
            ResponseFields::PAYEE_ID        => $input[RequestFields::PAYEE_ID],
            ResponseFields::PAID            => Status::YES,
        ];

        if (strpos($input[RequestFields::PAYMENT_ID], '.') !== false)
        {
            $accountNumber = explode('.', $input[RequestFields::PAYMENT_ID])[1];

            $this->assertAccountNumberLength($accountNumber);
        }

        return $data;
    }

    protected function getVerifyResponseData(array $input)
    {
        $content = [
            $input[RequestFields::PAYMENT_ID],
            $input[RequestFields::ITEM_CODE],
            99999999,
            $input[RequestFields::AMOUNT],
            'S',
        ];

        $response = $this->getStringFromContent($content, '|');

        $this->content($response, 'verify');

        return $response . "\n\u0000\u0000\u0000\u0000\u0000";
    }

    protected function getStringFromContent($content, $glue = '')
    {
        return implode($glue, $content);
    }

    protected function validateChecksum($input)
    {
        $actual = $input[RequestFields::HASH];

        unset($input[ResponseFields::HASH]);

        $hashParams = [
            $input[RequestFields::PAYEE_ID],
            $input[RequestFields::PAYMENT_ID],
            $input[RequestFields::ITEM_CODE],
            $input[RequestFields::AMOUNT],
        ];

        $expected = $this->getGatewayInstance()->generateHash($hashParams);

        if (hash_equals($actual, $expected) === false)
        {
            throw new Exception\RuntimeException('Failed checksum verification');
        }
    }

    protected function generateHash($content)
    {
        $hashParams = [
            $content[ResponseFields::PAYEE_ID],
            $content[ResponseFields::PAYMENT_ID],
            $content[ResponseFields::ITEM_CODE],
            $content[ResponseFields::AMOUNT],
            $content[ResponseFields::BANK_PAYMENT_ID],
            $content[ResponseFields::PAID],
        ];

        $content[ResponseFields::HASH] = parent::generateHash($hashParams);

        return $content;
    }
}
