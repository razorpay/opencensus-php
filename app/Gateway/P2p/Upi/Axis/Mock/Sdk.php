<?php

namespace RZP\Gateway\P2p\Upi\Axis\Mock;

use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Gateway\P2p\Upi\Axis\Gateway;
use RZP\Gateway\P2p\Upi\Axis\Actions\BankAccountAction;

class Sdk
{
    protected $action;
    protected $input;
    protected $errors = [];
    protected $callbacks = [];

    public function setMockedRequest($request)
    {
        assertTrue('axis', $request['sdk']);

        $this->action   = $request['action'];
        $this->input    = $request['content'];
    }

    public function withError(string $code)
    {
        $response = $this->initiateResponse(true);

        $response[Fields::ERROR_CODE] = $code;
        $response[Fields::ERROR_DESCRIPTION] = str_replace('_', ' ', $code);

        $this->errors[] = $response;

        return $this;
    }

    public function call()
    {
        if (count($this->errors) > 0)
        {
            return array_pop($this->errors);
        }

        $action = camel_case(strtolower('SDK_' . $this->action));

        $content = $this->{$action}();

        $this->content($content, $this->action);

        return $content;
    }

    public function content(& $content, $action = null)
    {
        return $content;
    }

    public function sdkGetAccounts()
    {
        $response = $this->initiateResponse();

        $response[Fields::VPA_SUGGESTIONS] = [
            'suggestion@razoraxis'
        ];

        $response[Fields::ACCOUNTS] = [
            $this->createMockBankAccount(),
            $this->createMockBankAccount(),
        ];

        return $response;
    }

    public function sdkVpaAvailability()
    {
        $response = $this->initiateResponse();

        $response[Fields::AVAILABLE] = 'true';

        return $response;
    }

    public function sdkLinkAccount()
    {
        $response = $this->initiateResponse();

        $response[Fields::MASKED_ACCOUNT_NUMBER]    = 'xxxxxxxxxxxx123456';
        $response[Fields::GATEWAY_RESPONSE_CODE]    = '00';
        $response[Fields::CUSTOMER_VPA]             = $this->input[Fields::CUSTOMER_VPA];
        $response[Fields::ACCOUNT_REFERENCE_ID]     = $this->input[Fields::ACCOUNT_REFERENCE_ID];
        $response[Fields::BANK_ACCOUNT_UNIQUE_ID]   = str_random(12);

        return $response;
    }

    public function sdkSetMpin()
    {
        $response = $this->initiateResponse();

        $response[Fields::MASKED_ACCOUNT_NUMBER]    = 'xxxxxxxxxxxx123456';
        $response[Fields::GATEWAY_RESPONSE_CODE]    = '00';
        $response[Fields::CUSTOMER_VPA]             = $this->input[Fields::CUSTOMER_VPA];
        $response[Fields::CUSTOMER_MOBILE_NUMBER]   = '919000000001';
        $response[Fields::BANK_CODE]                = '600006';
        $response[Fields::BANK_ACCOUNT_UNIQUE_ID]   = str_random(12);
        $response[Fields::ACCOUNT_REFERENCE_ID]     = $this->input[Fields::ACCOUNT_REFERENCE_ID];

        return $response;
    }

    public function sdkChangeMpin()
    {
        $this->input[Fields::CUSTOMER_VPA] = 'random@razoraxis';

        return $this->sdkSetMpin();
    }

    public function sdkCheckBalance()
    {
        $response = $this->initiateResponse();

        $response[Fields::GATEWAY_RESPONSE_CODE]    = '00';
        $response[Fields::BALANCE]                  = '2206.90';

        return $response;
    }

    public function sdkSendMoney()
    {
        $response = [
            Fields::AMOUNT                      => $this->input[Fields::AMOUNT],
            Fields::BANK_ACCOUNT_UNIQUE_ID      => $this->input[Fields::ACCOUNT_REFERENCE_ID],
            Fields::BANK_CODE                   => '123456',
            Fields::CUSTOMER_MOBILE_NUMBER      => '919000000001',
            Fields::CUSTOMER_VPA                => $this->input[Fields::CUSTOMER_VPA],
            Fields::GATEWAY_REFERENCE_ID        => '123344557', // rrn
            Fields::GATEWAY_RESPONSE_CODE       => '00',
            Fields::GATEWAY_RESPONSE_MESSAGE    => 'Your transaction was successful',
            Fields::GATEWAY_TRANSACTION_ID      => $this->input[Fields::UPI_REQUEST_ID],
            Fields::MASKED_ACCOUNT_NUMBER       => 'XXXX123456',
            Fields::PAY_TYPE                    => $this->input[Fields::PAY_TYPE],
            Fields::TRANSACTION_TIME_STAMP      => $this->input[Fields::TIME_STAMP],
            Fields::UDF_PARAMETERS              => '{}'
        ];

        $stringToSign = implode($response, '');

        $gateway = new Gateway();

        $sign = $gateway->getMerchantSigner()->sign($stringToSign);

        $response[Fields::MERCHANT_PAYLOAD_SIGNATURE] = bin2hex($sign);

        $response[Fields::STATUS] = 'SUCCESS';

        return $response;
    }

    public function sdkRequestMoney()
    {
        $response = [
            Fields::AMOUNT                      => $this->input[Fields::AMOUNT],
            Fields::BANK_ACCOUNT_UNIQUE_ID      => $this->input[Fields::ACCOUNT_REFERENCE_ID],
            Fields::BANK_CODE                   => '123456',
            Fields::CUSTOMER_MOBILE_NUMBER      => '919000000001',
            Fields::CUSTOMER_VPA                => $this->input[Fields::CUSTOMER_VPA],
            Fields::GATEWAY_REFERENCE_ID        => '911416196085', // rrn
            Fields::GATEWAY_RESPONSE_CODE       => '00',
            Fields::GATEWAY_RESPONSE_MESSAGE    => 'Your transaction was successful',
            Fields::GATEWAY_TRANSACTION_ID      => $this->input[Fields::UPI_REQUEST_ID],
            Fields::MASKED_ACCOUNT_NUMBER       => 'XXXX123456',
            Fields::TRANSACTION_TIME_STAMP      => $this->input[Fields::TIMESTAMP],
            Fields::UDF_PARAMETERS              => '{}'
        ];

        $stringToSign = implode($response, '');

        $gateway = new Gateway();

        $sign = $gateway->getMerchantSigner()->sign($stringToSign);

        $response[Fields::MERCHANT_PAYLOAD_SIGNATURE] = bin2hex($sign);

        $response[Fields::STATUS] = 'SUCCESS';

        $this->setCallback('COLLECT_REQUEST_RECEIVED', $response);

        return $response;
    }

    public function callback()
    {
        return array_pop($this->callbacks);
    }

    private function createMockBankAccount()
    {
        $code = $this->input[Fields::BANK_CODE];

        return [
            Fields::BANK_CODE               => $code,
            Fields::BANK_NAME               => 'Bank ' . $code,
            Fields::MASKED_ACCOUNT_NUMBER   => 'xxxxxxxxxxxx' . $code,
            Fields::MPIN_SET                => 'false',
            Fields::MPIN_LENGTH             => '6',
            Fields::REFERENCE_ID            => str_random(16),
            Fields::TYPE                    => 'SAVINGS',
            Fields::IFSC                    => $code . str_random(5),
            Fields::NAME                    => $code . ' Bank Customer',
            Fields::BRANCH_NAME             => 'Kormangala',
            Fields::BANK_ACCOUNT_UNIQUE_ID  => str_random(32),
            Fields::OTP_LENGTH              => '6',
            Fields::ATM_PIN_LENGTH          => '4',
        ];
    }

    private function initiateResponse($error = null)
    {
        $response = [
            Fields::STATUS          => $error ? 'FAILURE' : 'SUCCESS',
            Fields::UDF_PARAMETERS  => $this->input[Fields::UDF_PARAMETERS],
        ];

        return $response;
    }

    private function setCallback(string $type)
    {
        $callback = [
            Fields::GATEWAY_REFERENCE_ID        => '911416196085',
            Fields::AMOUNT                      => $this->input[Fields::AMOUNT],
            Fields::PAYEE_VPA                   => $this->input[Fields::CUSTOMER_VPA],
            Fields::TYPE                        => $type,
            Fields::PAYER_VPA                   => $this->input[Fields::PAYER_VPA],
            Fields::TRANSACTION_TIME_STAMP      => $this->input[Fields::TIMESTAMP],
            Fields::CUSTOME_RESPONSE            => '{}',
            Fields::PAYEE_NAME                  => 'Alocal Customer',
            Fields::GATEWAY_TRANSACTION_ID      => $this->input[Fields::UPI_REQUEST_ID],
            Fields::MERCHANT_ID                 => 'MERCHANT',
            Fields::IS_VERIFIED_PAYEE           => 'false',
            Fields::MERCHANT_CUSTOMER_ID        => 'ALC02DevTok003',
            Fields::EXPIRY                      => '2019-04-25T16:11:22+05:30',
            Fields::IS_MARKED_SPAM              => 'false',
            Fields::REMARKS                     => $this->input[Fields::REMARKS],
        ];

        $this->callbacks[] = $callback;
    }
}
