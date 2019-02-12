<?php

namespace RZP\Gateway\Netbanking\Idfc\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Netbanking;
use RZP\Gateway\Netbanking\Idfc;
use RZP\Gateway\Netbanking\Idfc\Fields;
use RZP\Gateway\Netbanking\Idfc\TransactionDetails;
use RZP\Gateway\Netbanking\Idfc\StatusCode;
use function Sodium\increment;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        if (isset($input[Fields::ACCOUNT_NUMBER]) === false)
        {
            $input = array_merge(array_slice($input, 0, 5, true),
                [Fields::ACCOUNT_NUMBER => ''],
                array_slice($input, 5, 5, true));
        }

        $responseCode = $this->validateChecksum($input);

        $callbackDataArray = $this->getCallbackResponseData($input, $responseCode);

        $request = [
            'url'     => $input[Fields::RETURN_URL],
            'content' => $callbackDataArray,
            'method'  => 'post',
        ];

        return $this->makePostResponse($request);
    }

    public function verify($input)
    {
        parent::verify($input);

        $input = json_decode($input, true);

        $this->validateActionInput($input, 'verify');

        $input = array_merge(array_slice($input, 0, 3, true),
                             [Fields::ACCOUNT_NUMBER => ''],
                             array_slice($input, 3, 4, true));

        $responseCode = $this->validateChecksum($input);

        $verifyResponseDataArray = $this->getVerifyResponseData($input, $responseCode);

        return $this->makeJsonResponse($verifyResponseDataArray);
    }

    protected function validateChecksum($content)
    {
        $receivedChecksum = $content[Fields::CHECKSUM];

        unset($content[Fields::CHECKSUM]);

        $calculatedChecksum = $this->generateHash($content);

        if ($receivedChecksum !== $calculatedChecksum)
        {
            $responseCode = Idfc\StatusCode::CHECKSUM_FAILED;
        }
        else
        {
            $responseCode = Idfc\StatusCode::SUCCESS_CODE;
        }

        return $responseCode;
    }

    protected function makeJsonResponse(array $content)
    {
        $json = json_encode($content);

        $response = $this->makeResponse($json);

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');

        return $response;
    }

    protected function getStringToHash($content, $glue = '|')
    {
        return implode($glue, $content);
    }

    protected function getHashOfString($str)
    {
        return strtoupper(hash_hmac('sha512', $str, '', false));
    }

    protected function getCallbackResponseData($content, $responseCode)
    {
        $data = [
            Fields::MERCHANT_ID             => $content[Fields::MERCHANT_ID],
            Fields::PAYMENT_ID              => $content[Fields::PAYMENT_ID],
            Fields::AMOUNT                  => $content[Fields::AMOUNT],
            Fields::TRANSACTION_TYPE        => $content[Fields::TRANSACTION_TYPE],
            Fields::ACCOUNT_NUMBER          => isset($content[Fields::ACCOUNT_NUMBER])
                                                ? $content[Fields::ACCOUNT_NUMBER]
                                                : '',
            Fields::PAYMENT_DESCRIPTION     => $content[Fields::PAYMENT_DESCRIPTION],
            Fields::BANK_REFERENCE_NUMBER   => '9999999999',
            Fields::RESPONSE_CODE           => $responseCode,
            Fields::RESPONSE_MESSAGE        => 'Success',
            Fields::PAYMENT_STATUS          => TransactionDetails::PAYMENT_SUCCESS,
        ];

        $this->content($data, 'authorize');

        $checksum = $this->generateHash($data);

        $data[Fields::CHECKSUM] = $checksum;

        return $data;

    }

    protected function getVerifyResponseData($content)
    {
        $data = [
            Fields::MERCHANT_ID             => $content[Fields::MERCHANT_ID],
            Fields::PAYMENT_ID              => $content[Fields::PAYMENT_ID],
            Fields::AMOUNT                  => $content[Fields::AMOUNT],
            Fields::ACCOUNT_NUMBER          => isset($content[Fields::ACCOUNT_NUMBER])
                                                ? $content[Fields::ACCOUNT_NUMBER]
                                                : '',
            Fields::TRANSACTION_TYPE        => $content[Fields::TRANSACTION_TYPE],
            Fields::BANK_REFERENCE_NUMBER   => '9999999999',
            Fields::STATUS_RESULT           => TransactionDetails::STATUS_SUCCESS,
            Fields::RESPONSE_CODE           => StatusCode::SUCCESS_CODE,
            Fields::RESPONSE_MESSAGE        => 'Success',
        ];

        $this->content($data, 'verify');

        $checksum = $this->generateHash($data);

        $data[Fields::CHECKSUM] = $checksum;

        return $data;
    }
}
