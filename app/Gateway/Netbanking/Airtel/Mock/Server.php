<?php

namespace RZP\Gateway\Netbanking\Airtel\Mock;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Constants\HashAlgo;
use RZP\Gateway\Base\Action;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Netbanking\Airtel\Status;
use RZP\Gateway\Netbanking\Airtel\AuthFields;
use RZP\Gateway\Netbanking\Airtel\ErrorCodes;
use RZP\Gateway\Netbanking\Airtel\VerifyFields;
use RZP\Gateway\Netbanking\Airtel\RefundFields;

class Server extends Base\Mock\Server
{
    const TIME_FORMAT            = 'dmYhis';

    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        $this->verifySecureHash($input);

        $content = $this->createCallbackResponseArray($input);

        $callbackUrl = $input[AuthFields::SUCCESS_URL] . '?' .
                        http_build_query($content);

        return $callbackUrl;
    }

    public function refund($input)
    {
        parent::refund($input);

        $request = json_decode($input, true);

        $this->validateActionInput($request);

        $this->verifySecureHash($request);

        $response = $this->createRefundResponse($request);

        return $this->makeResponse($response);
    }

    public function verify($input)
    {
        parent::verify($input);

        $request = json_decode($input, true);

        $this->validateActionInput($request);

        $this->verifySecureHash($request);

        $response = $this->getVerifyResponse($request);

        return $this->makeResponse($response);
    }

    protected function createRefundResponse($request)
    {
        $date = Carbon::createFromFormat(self::TIME_FORMAT,
            $request[VerifyFields::TRANSACTION_DATE])->toDateTimeString();

        $data = [
            RefundFields::MERCHANT_ID      => $request[RefundFields::MERCHANT_ID],
            RefundFields::ERROR_CODE       => '000',
            RefundFields::AMOUNT           => $request[RefundFields::AMOUNT],
            RefundFields::TRANSACTION_ID   => mt_rand(11111111, 99999999),
            RefundFields::TRANSACTION_DATE => $date,
            RefundFields::STATUS           => Status::SUCCESS,
            RefundFields::SESSION_ID       => $request[RefundFields::SESSION_ID],
            RefundFields::MESSAGE_TEXT     => 'Transaction Created Successfully',
            RefundFields::CODE             => '0'
        ];

        $this->content($data);

        $data[RefundFields::HASH] = $this->generateHash($data, 'response');

        return json_encode($data);
    }

    protected function createCallbackResponseArray($input)
    {
        $response = [
            AuthFields::MERCHANT_ID               => $input[AuthFields::MERCHANT_ID],
            AuthFields::TRANSACTION_ID            => mt_rand(11111111, 99999999),
            AuthFields::TRANSACTION_REFERENCE_NO  => $input[AuthFields::TRANSACTION_REFERENCE_NO],
            AuthFields::TRANSACTION_AMOUNT        => $input[AuthFields::AMOUNT],
            AuthFields::TRANSACTION_DATE          => $input[AuthFields::DATE],
            AuthFields::STATUS                    => Status::SUCCESS,
            AuthFields::CODE                      => '000',
            AuthFields::MSG                       => 'eCommerce transaction successful',
            AuthFields::TRANSACTION_CURRENCY      => Currency::INR,
        ];

        $this->content($response, 'callback');

        $response[AuthFields::HASH] = $this->generateHash($response, 'response');

        $this->content($response, 'hash');

        return $response;
    }

    protected function getVerifyResponse($input)
    {
        $merchantId = $this->getGatewayInstance()->getMerchantId2();

        $gatewayPayment = $this->repo->netbanking->findByPaymentIdAndAction(
            $input[VerifyFields::TRANSACTION_REFERENCE_NO], Action::AUTHORIZE);

        $date = Carbon::createFromFormat(self::TIME_FORMAT,
            $input[VerifyFields::TRANSACTION_DATE])->toDateTimeString();

        $verifyArray = [
            VerifyFields::STATUS                => Status::SUCCESS,
            VerifyFields::TRANSACTION_ID        => $gatewayPayment['bank_payment_id'],
            VerifyFields::TRANSACTION_DATE      => $date,
            VerifyFields::TRANSACTION_AMOUNT    => $input[VerifyFields::AMOUNT],
        ];

        $response =  [
            VerifyFields::TRANSACTION               => array($verifyArray),
            VerifyFields::MERCHANT_ID               => $merchantId,
            VerifyFields::TRANSACTION_REFERENCE_NO  => $input[VerifyFields::TRANSACTION_REFERENCE_NO],
            VerifyFields::MESSAGE_TEXT              => 'Success',
            VerifyFields::CODE                      => '0',
            VerifyFields::ERROR_CODE                => '000'
        ];

        $this->content($response);

        $response[VerifyFields::HASH] = $this->generateHash($response, 'response');

        return json_encode($response);
    }

    protected function getHashValueFromContent(array $content)
    {
        switch ($this->action)
        {
            case Action::AUTHORIZE:
                return $content[AuthFields::HASH];

            case Action::VERIFY:
                return $content[VerifyFields::HASH];

            case Action::REFUND:
                return $content[RefundFields::HASH];

            default:
                throw new Exception\RuntimeException('Action not set correctly');
        }
    }

    protected function verifySecureHash(array $content)
    {
        $actual = $this->getHashValueFromContent($content);

        $generated = $this->generateHash($content);

        $this->compareHashes($actual, $generated);
    }

    protected function compareHashes($actual, $generated)
    {
        if (hash_equals($actual, $generated) === false)
        {
            throw new Exception\RuntimeException('Failed checksum verification');
        }
    }

    protected function generateHash($content, $type = 'request')
    {
        $hashString = $this->getStringToHash($content, '#', $type);

        return $this->getHashOfString($hashString);
    }

    protected function getStringToHash($data, $glue = '', $type = 'request')
    {
        switch ($this->action)
        {
            case Action::AUTHORIZE:
                $data = ($type === 'response') ? $this->getCallbackResponseHashArray($data) :
                                                 $this->getCallbackRequestHashArray($data);
                break;

            case Action::VERIFY:
                $data = ($type === 'response') ? $this->getVerifyResponseHashArray($data) :
                                                 $this->getVerifyRequestHashArray($data);
                break;

            case Action::REFUND:
                $data = ($type === 'response') ? $this->getRefundResponseHashArray($data) :
                                                 $this->getRefundRequestHashArray($data);
                break;

            default:
                throw new Exception\RuntimeException('Action not set correctly');
        }

        return implode($glue, $data);
    }

    protected function getHashOfString($string)
    {
        return hash(HashAlgo::SHA512, $string);
    }

    protected function getCallbackRequestHashArray($content)
    {
        $hashArray = [
            $content[AuthFields::MERCHANT_ID],
            $content[AuthFields::TRANSACTION_REFERENCE_NO],
            $content[AuthFields::AMOUNT],
            $content[AuthFields::DATE],
            $content[AuthFields::SERVICE],
            $this->getGatewayInstance()->getSecret()
        ];

        return $hashArray;
    }

    protected function getCallbackResponseHashArray($content)
    {
        if ($content[AuthFields::CODE] === ErrorCodes::SUCCESS)
        {
            $hashArray = [
                $content[AuthFields::MERCHANT_ID],
                $content[AuthFields::TRANSACTION_ID],
                $content[AuthFields::TRANSACTION_REFERENCE_NO],
                $content[AuthFields::TRANSACTION_AMOUNT],
                $content[AuthFields::TRANSACTION_DATE],
                $this->getGatewayInstance()->getSecret()
            ];
        }
        else
        {
            $hashArray = [
                $content[AuthFields::MERCHANT_ID],
                $content[AuthFields::TRANSACTION_REFERENCE_NO],
                $content[AuthFields::TRANSACTION_AMOUNT],
                $this->getGatewayInstance()->getSecret(),
                $content[AuthFields::CODE],
                $content[AuthFields::STATUS]
            ];
        }

        return $hashArray;
    }

    protected function getRefundRequestHashArray($data)
    {
        $hashArray = [
            $data[RefundFields::MERCHANT_ID],
            $data[RefundFields::TRANSACTION_ID],
            $data[RefundFields::AMOUNT],
            $data[RefundFields::TRANSACTION_DATE],
            $this->getGatewayInstance()->getSecret()
        ];

        return $hashArray;
    }

    protected function getRefundResponseHashArray($content)
    {
        $hashArray = [
            $content[RefundFields::MERCHANT_ID],
            $content[RefundFields::ERROR_CODE],
            $content[RefundFields::AMOUNT],
            $content[RefundFields::TRANSACTION_ID],
            $content[RefundFields::TRANSACTION_DATE],
            $content[RefundFields::STATUS],
            $this->getGatewayInstance()->getSecret()
        ];

        return $hashArray;
    }

    protected function getVerifyRequestHashArray($data)
    {
        $hashArray = [
            $data[VerifyFields::MERCHANT_ID],
            $data[VerifyFields::TRANSACTION_REFERENCE_NO],
            $data[VerifyFields::AMOUNT],
            $data[VerifyFields::TRANSACTION_DATE],
            $this->getGatewayInstance()->getSecret()
        ];

        return $hashArray;
    }

    protected function getVerifyResponseHashArray($content)
    {
        $merchantId = $this->getGatewayInstance()->getMerchantId2();

        if ($content[VerifyFields::TRANSACTION] === [])
        {
            $json = "[]";
        }
        else
        {
            $verifyArray = $content[VerifyFields::TRANSACTION][0];
            $json = '['.json_encode($verifyArray).']';
        }

        $hashArray = [
            $merchantId,
            $json,
            $content[VerifyFields::ERROR_CODE],
            $this->getGatewayInstance()->getSecret()
        ];

        return $hashArray;
    }
}
