<?php

namespace RZP\Gateway\P2p\Upi\Axis\Mock;

use Carbon\Carbon;
use phpseclib\Crypt\RSA;
use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Gateway\P2p\Upi\Axis\Gateway;
use RZP\Gateway\P2p\Upi\Axis\Actions\UpiAction;
use RZP\Gateway\P2p\Upi\Axis\Actions\TransactionAction;
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

    public function withError(string $code, string $description = null)
    {
        $response = $this->initiateResponse(true);

        $response[Fields::ERROR_CODE] = $code;
        $response[Fields::ERROR_DESCRIPTION] = $description ?? str_replace('_', ' ', $code);

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
            $this->createMockBankAccount('000001'),
            $this->createMockBankAccount('000002'),
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

        $this->content($response, $this->action);

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

        $this->content($response, $this->action);

        $sign = $this->signContent(implode($response, ''));

        $response[Fields::MERCHANT_PAYLOAD_SIGNATURE] = $sign;

        $response[Fields::STATUS] = 'SUCCESS';

        return $response;
    }

    public function sdkPayCollect()
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

        $this->content($response, $this->action);

        $sign = $this->signContent(implode($response, ''));

        $response[Fields::MERCHANT_PAYLOAD_SIGNATURE] = $sign;

        $response[Fields::STATUS] = 'SUCCESS';

        return $response;
    }

    public function sdkDeclineCollect()
    {
        $response = [
            Fields::AMOUNT                      => $this->input[Fields::AMOUNT],
            Fields::CUSTOMER_MOBILE_NUMBER      => '919000000001',
            Fields::CUSTOMER_VPA                => $this->input[Fields::CUSTOMER_VPA],
            Fields::GATEWAY_REFERENCE_ID        => '911416196085', // rrn
            Fields::GATEWAY_RESPONSE_CODE       => 'ZA',
            Fields::GATEWAY_RESPONSE_MESSAGE    => 'Transaction declined',
            Fields::GATEWAY_TRANSACTION_ID      => $this->input[Fields::UPI_REQUEST_ID],
            Fields::TRANSACTION_TIME_STAMP      => $this->input[Fields::TIMESTAMP],
            Fields::UDF_PARAMETERS              => '{}'
        ];

        $this->content($response, $this->action);

        $sign = $this->signContent(implode($response, ''));

        $response[Fields::MERCHANT_PAYLOAD_SIGNATURE] = $sign;

        $response[Fields::STATUS] = 'SUCCESS';

        return $response;
    }

    public function callback()
    {
        $content = json_encode(array_pop($this->callbacks));

        return [
            'server' => [
                'HTTP_X-Merchant-Payload-Signature' => $this->signContent($content),
            ],
            'content' => $content,
        ];
    }

    public function setCallback(string $type, array $input)
    {
        $successCode = '00';
        $successMessage = 'Your transaction is approved';

        switch ($type)
        {
            case UpiAction::COLLECT_REQUEST_RECEIVED:
                $callback = [
                    Fields::AMOUNT                      => $input[Fields::AMOUNT],
                    Fields::CUSTOME_RESPONSE            => '{}',
                    Fields::EXPIRY                      => $input[Fields::EXPIRY] ?? $this->formattedTime(30),
                    Fields::GATEWAY_REFERENCE_ID        => $input[Fields::GATEWAY_REFERENCE_ID] ?? '911416196085',
                    Fields::GATEWAY_TRANSACTION_ID      => $input[Fields::GATEWAY_TRANSACTION_ID] ?? str_random(35),
                    Fields::IS_VERIFIED_PAYEE           => 'false',
                    Fields::IS_MARKED_SPAM              => 'false',
                    Fields::MERCHANT_CUSTOMER_ID        => $input[Fields::MERCHANT_CUSTOMER_ID],
                    Fields::MERCHANT_ID                 => 'MERCHANT',
                    Fields::PAYEE_NAME                  => 'Alocal Customer',
                    Fields::PAYEE_MCC                   => $input[Fields::PAYEE_MCC] ?? '2222',
                    Fields::REF_URL                     => $input[Fields::REF_URL] ?? 'https::example.com',
                    Fields::PAYEE_VPA                   => $input[Fields::PAYEE_VPA],
                    Fields::PAYER_VPA                   => $input[Fields::PAYER_VPA],
                    Fields::REMARKS                     => $input[Fields::REMARKS],
                    Fields::TRANSACTION_TIME_STAMP      => $input[Fields::TIMESTAMP] ?? Carbon::now()->getTimestamp(),
                    Fields::TYPE                        => $type,
                ];
                break;

            case UpiAction::CUSTOMER_CREDITED_VIA_PAY:
                $callback = [
                    Fields::AMOUNT                      => $input[Fields::AMOUNT],
                    Fields::BANK_ACCOUNT_UNIQUE_ID      => str_random(16),
                    Fields::BANK_CODE                   => random_integer(6),
                    Fields::CUSTOME_RESPONSE            => '{}',
                    Fields::GATEWAY_REFERENCE_ID        => '911416196085',
                    Fields::GATEWAY_RESPONSE_CODE       => $input[Fields::GATEWAY_RESPONSE_CODE] ?? $successCode,
                    Fields::GATEWAY_RESPONSE_MESSAGE    => $input[Fields::GATEWAY_RESPONSE_MESSAGE] ?? $successMessage,
                    Fields::GATEWAY_TRANSACTION_ID      => $input[Fields::GATEWAY_TRANSACTION_ID] ?? str_random(35),
                    Fields::MASKED_ACCOUNT_NUMBER       => 'xxxxx0123456',
                    Fields::MERCHANT_CUSTOMER_ID        => $input[Fields::MERCHANT_CUSTOMER_ID],
                    Fields::MERCHANT_ID                 => 'MERCHANT',
                    Fields::PAYEE_MOBILE_NUMBER         => '919000000001',
                    Fields::PAYEE_VPA                   => $input[Fields::PAYEE_VPA],
                    Fields::PAYEE_MCC                   => $input[Fields::PAYEE_MCC] ?? '2222',
                    Fields::REF_URL                     => $input[Fields::REF_URL] ?? 'https::example.com',
                    Fields::PAYER_NAME                  => $input[Fields::PAYER_NAME] ?? 'Beneficiary Name',
                    Fields::PAYER_VPA                   => $input[Fields::PAYER_VPA],
                    Fields::TRANSACTION_TIME_STAMP      => $input[Fields::TIMESTAMP] ?? Carbon::now()->getTimestamp(),
                    Fields::TYPE                        => $type,
                ];
                break;

            case UpiAction::CUSTOMER_CREDITED_VIA_COLLECT:
                $callback = [
                    Fields::AMOUNT                      => $input[Fields::AMOUNT],
                    Fields::BANK_ACCOUNT_UNIQUE_ID      => str_random(16),
                    Fields::BANK_CODE                   => random_integer(6),
                    Fields::CUSTOME_RESPONSE            => '{}',
                    Fields::GATEWAY_REFERENCE_ID        => '911416196085',
                    Fields::GATEWAY_RESPONSE_CODE       => $input[Fields::GATEWAY_RESPONSE_CODE] ?? $successCode,
                    Fields::GATEWAY_RESPONSE_MESSAGE    => $input[Fields::GATEWAY_RESPONSE_MESSAGE] ?? $successMessage,
                    Fields::GATEWAY_TRANSACTION_ID      => $input[Fields::GATEWAY_TRANSACTION_ID] ?? str_random(35),
                    Fields::MASKED_ACCOUNT_NUMBER       => 'xxxxx0123456',
                    Fields::MERCHANT_CUSTOMER_ID        => $input[Fields::MERCHANT_CUSTOMER_ID],
                    Fields::MERCHANT_ID                 => 'MERCHANT',
                    Fields::MERCHANT_REQUEST_ID         => $input[Fields::MERCHANT_REQUEST_ID],
                    Fields::PAYEE_MOBILE_NUMBER         => '919000000001',
                    Fields::PAYEE_MCC                   => $input[Fields::PAYEE_MCC] ?? '2222',
                    Fields::REF_URL                     => $input[Fields::REF_URL] ?? 'https::example.com',
                    Fields::PAYEE_VPA                   => $input[Fields::PAYEE_VPA],
                    Fields::PAYER_NAME                  => $input[Fields::PAYER_NAME] ?? 'Beneficiary Name',
                    Fields::PAYER_VPA                   => $input[Fields::PAYER_VPA],
                    Fields::TRANSACTION_TIME_STAMP      => $input[Fields::TIMESTAMP] ?? Carbon::now()->getTimestamp(),
                    Fields::TYPE                        => $type,
                ];
                break;

            case UpiAction::CUSTOMER_DEBITED_VIA_COLLECT:
            case UpiAction::CUSTOMER_DEBITED_VIA_PAY:
            case UpiAction::CUSTOMER_DEBITED_FOR_MERCHANT_VIA_PAY:
            case UpiAction::CUSTOMER_DEBITED_FOR_MERCHANT_VIA_COLLECT:
                $callback = [
                    Fields::AMOUNT                      => $input[Fields::AMOUNT],
                    Fields::BANK_ACCOUNT_UNIQUE_ID      => str_random(16),
                    Fields::BANK_CODE                   => random_integer(6),
                    Fields::CUSTOME_RESPONSE            => '{}',
                    Fields::GATEWAY_REFERENCE_ID        => '911416196085',
                    Fields::GATEWAY_RESPONSE_CODE       => $input[Fields::GATEWAY_RESPONSE_CODE] ?? $successCode,
                    Fields::GATEWAY_RESPONSE_MESSAGE    => $input[Fields::GATEWAY_RESPONSE_MESSAGE] ?? $successMessage,
                    Fields::GATEWAY_TRANSACTION_ID      => $input[Fields::GATEWAY_TRANSACTION_ID] ?? str_random(35),
                    Fields::MASKED_ACCOUNT_NUMBER       => 'xxxxx0123456',
                    Fields::MERCHANT_CUSTOMER_ID        => $input[Fields::MERCHANT_CUSTOMER_ID],
                    Fields::MERCHANT_ID                 => 'MERCHANT',
                    Fields::MERCHANT_REQUEST_ID         => $input[Fields::MERCHANT_REQUEST_ID] ?? null,
                    Fields::PAYEE_NAME                  => $input[Fields::PAYEE_NAME] ?? 'Beneficiary Name',
                    Fields::PAYEE_VPA                   => $input[Fields::PAYEE_VPA],
                    Fields::PAYEE_MCC                   => $input[Fields::PAYEE_MCC] ?? '2222',
                    Fields::REF_URL                     => $input[Fields::REF_URL] ?? 'https::example.com',
                    Fields::PAYER_MOBILE_NUMBER         => '919000000001',
                    Fields::PAYER_VPA                   => $input[Fields::PAYER_VPA],
                    Fields::TRANSACTION_TIME_STAMP      => $input[Fields::TIMESTAMP] ?? Carbon::now()->getTimestamp(),
                    Fields::TYPE                        => $type,
                ];

                if ($callback[Fields::GATEWAY_RESPONSE_CODE] === 'U69')
                {
                    unset($callback[Fields::MERCHANT_REQUEST_ID]);
                }

                break;

            default:
                $callback = $input;
        }

        $this->callbacks[] = $callback;
    }

    private function createMockBankAccount($mask)
    {
        $code = $this->input[Fields::BANK_CODE];

        return [
            Fields::BANK_CODE               => $code,
            Fields::BANK_NAME               => 'Bank ' . $code,
            Fields::MASKED_ACCOUNT_NUMBER   => 'xxxxxxxxxxxx' . $mask,
            Fields::MPIN_SET                => 'false',
            Fields::MPIN_LENGTH             => '6',
            Fields::REFERENCE_ID            => str_random(16),
            Fields::TYPE                    => 'SAVINGS',
            Fields::IFSC                    => $code . str_random(5),
            Fields::NAME                    => $code . ' Bank Customer',
            Fields::BRANCH_NAME             => 'Kormangala',
            Fields::BANK_ACCOUNT_UNIQUE_ID  => 'UniqueWithCode' . $mask,
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

    private function signContent(string $string)
    {
        $rsa = new RSA();

        $rsa->loadKey(env('P2P_UPI_AXIS_BANK_PRIVATE_KEY'), RSA::PRIVATE_FORMAT_PKCS1);

        $rsa->setHash('sha256');

        $rsa->setMGFHash('sha256');

        $rsa->setSignatureMode(RSA::SIGNATURE_PSS);

        $sign = $rsa->sign($string);

        return bin2hex($sign);
    }

    private function formattedTime($minutes = 0)
    {
        return Carbon::now()->addMinutes(30)->toIso8601String();
    }
}
