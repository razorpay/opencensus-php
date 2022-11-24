<?php

namespace RZP\Gateway\P2p\Upi\Axis\Mock;

use Carbon\Carbon;
use phpseclib\Crypt\RSA;
use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Gateway\P2p\Upi\Axis\Gateway;
use RZP\Gateway\P2p\Upi\Axis\Actions\UpiAction;
use RZP\Gateway\P2p\Upi\Axis\Actions\MandateAction;
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

        $stringToSign = implode('', $response);

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

        $sign = $this->signContent(implode( '', $response));

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

        $sign = $this->signContent(implode('', $response));

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

        $sign = $this->signContent(implode('', $response));

        $response[Fields::MERCHANT_PAYLOAD_SIGNATURE] = $sign;

        $response[Fields::STATUS] = 'SUCCESS';

        return $response;
    }

    public function sdkApproveDeclineMandate()
    {
        switch($this->input[Fields::REQUEST_TYPE])
        {
            case MandateAction::APPROVE:
                return $this->sdkAuthorizeMandate();
            case MandateAction::DECLINE:
                return $this->sdkRejectMandate();
        }
    }

    public function sdkPauseUnpauseMandate()
    {
        switch($this->input[Fields::REQUEST_TYPE])
        {
            case MandateAction::PAUSE:
                return $this->sdkPauseMandate();
            case MandateAction::UNPAUSE:
                return $this->sdkUnpauseMandate();
        }
    }

    public function sdkUpdateOrRevokeMandate()
    {
        switch($this->input[Fields::REQUEST_TYPE])
        {
            case MandateAction::REVOKE:
                return $this->sdkRevokeMandate();
        }
    }

    public function sdkAuthorizeMandate()
    {
        $response = [
            Fields::AMOUNT                      => 100,
            Fields::AMOUNT_RULE                 => 'EXACT',
            Fields::BLOCK_FUND                  => false,
            Fields::EXPIRY                      => $this->formattedTime(30),
            Fields::GATEWAY_MANDATE_ID          => $this->input[Fields::MANDATE_REQUEST_ID],
            Fields::GATEWAY_REFERENCE_ID        => '911416196085',
            Fields::GATEWAY_RESPONSE_CODE       => '00',
            Fields::GATEWAY_RESPONSE_MESSAGE    => 'Mandate is successfully approved',
            Fields::GATEWAY_RESPONSE_STATUS     => 'SUCCESS',
            Fields::INITIATED_BY                => 'payer',
            Fields::MANDATE_APPROVAL_TIMESTAMP  => Carbon::now()->toIso8601String(),
            Fields::MANDATE_NAME                => 'Sample mandate test',
            Fields::MANDATE_TIMESTAMP           => Carbon::now()->toIso8601String(),
            Fields::MANDATE_TYPE                => ($this->input[Fields::REQUEST_TYPE] === 'UPDATE') ?? 'CREATE',
            Fields::MERCHANT_CUSTOMER_ID        => $this->input[Fields::MERCHANT_CUSTOMER_ID],
            Fields::MERCHANT_REQUEST_ID         => $this->input[Fields::MERCHANT_REQUEST_ID],
            Fields::ORG_MANDATE_ID              => str_random(35),
            Fields::PAYEE_MCC                   => '2222',
            Fields::PAYEE_NAME                  => 'Payee Name',
            Fields::PAYEE_VPA                   => 'test@razoraxis',
            Fields::PAYER_REVOCABLE             => true,
            Fields::PAYER_VPA                   => 'customer@razoraxis',
            Fields::RECURRENCE_PATTERN          => 'WEEKLY',
            Fields::RECURRENCE_RULE             => 'BEFORE',
            Fields::RECURRENCE_VALUE            => '2',
            Fields::REF_URL                     => 'https::example.com',
            Fields::REMARKS                     => 'sample remarks',
            Fields::ROLE                        => 'PAYER',
            Fields::SHARE_TO_PAYEE              => true,
            Fields::TRANSACTION_TYPE            => 'UPI_MANDATE',
            Fields::UMN                         => str_random(10).'@bajaj',
            Fields::VALIDITY_END                => Carbon::now()->addYears(10)->toDateString(),
            Fields::VALIDITY_START              => Carbon::now()->toDateString(),
            Fields::UDF_PARAMETERS              => '{}',
        ];

        $this->content($response, $this->action);

        $sign = $this->signContent(implode('', $response));

        $response[Fields::MERCHANT_PAYLOAD_SIGNATURE] = $sign;

        $response[Fields::STATUS] = 'SUCCESS';

        return $response;
    }

    public function sdkRejectMandate()
    {
        $response = [
            Fields::AMOUNT                      => 100,
            Fields::AMOUNT_RULE                 => 'EXACT',
            Fields::BANK_ACCOUNT_UNIQUE_ID      => '162b957e5dc7957ae9fe99eed69daa5ff1d65b89a312bdede56d843f20b15645',
            Fields::BLOCK_FUND                  => false,
            Fields::EXPIRY                      => $this->formattedTime(30),
            Fields::GATEWAY_MANDATE_ID          => $this->input[Fields::MANDATE_REQUEST_ID],
            Fields::GATEWAY_REFERENCE_ID        => '911416196085',
            Fields::GATEWAY_RESPONSE_CODE       => ($this->input[Fields::REQUEST_TYPE] === 'UPDATE') ? 'QT' : 'ZA',
            Fields::GATEWAY_RESPONSE_MESSAGE    => 'Mandate is declined',
            Fields::GATEWAY_RESPONSE_STATUS     => 'DECLINED',
            Fields::INITIATED_BY                => 'payer',
            Fields::MANDATE_APPROVAL_TIMESTAMP  => Carbon::now()->toIso8601String(),
            Fields::MANDATE_NAME                => 'Sample mandate test',
            Fields::MANDATE_TIMESTAMP           => Carbon::now()->toIso8601String(),
            Fields::MANDATE_TYPE                => ($this->input[Fields::REQUEST_TYPE] === 'UPDATE') ?? 'CREATE',
            Fields::MERCHANT_CUSTOMER_ID        => $this->input[Fields::MERCHANT_CUSTOMER_ID],
            Fields::MERCHANT_REQUEST_ID         => $this->input[Fields::MERCHANT_REQUEST_ID],
            Fields::ORG_MANDATE_ID              => str_random(35),
            Fields::PAYEE_MCC                   => '2222',
            Fields::PAYEE_NAME                  => 'Payee Name',
            Fields::PAYEE_VPA                   => 'test@razoraxis',
            Fields::PAYER_NAME                  => 'Payer name',
            Fields::PAYER_REVOCABLE             => true,
            Fields::PAYER_VPA                   => 'customer@razoraxis',
            Fields::RECURRENCE_PATTERN          => 'WEEKLY',
            Fields::RECURRENCE_RULE             => 'BEFORE',
            Fields::RECURRENCE_VALUE            => '2',
            Fields::REF_URL                     => 'https::example.com',
            Fields::REMARKS                     => 'sample remarks',
            Fields::ROLE                        => 'PAYER',
            Fields::SHARE_TO_PAYEE              => true,
            Fields::TRANSACTION_TYPE            => 'UPI_MANDATE',
            Fields::UMN                         => str_random(10).'@bajaj',
            Fields::VALIDITY_END                => Carbon::now()->addYears(10)->toDateString(),
            Fields::VALIDITY_START              => Carbon::now()->toDateString(),
            Fields::UDF_PARAMETERS              => '{}',
        ];

        $this->content($response, $this->action);

        $sign = $this->signContent(implode('', $response));

        $response[Fields::MERCHANT_PAYLOAD_SIGNATURE] = $sign;

        $response[Fields::STATUS] = 'SUCCESS';

        return $response;
    }

    public function sdkPauseMandate()
    {
        $response = [
            Fields::AMOUNT                      => 100,
            Fields::AMOUNT_RULE                 => 'EXACT',
            Fields::BANK_ACCOUNT_UNIQUE_ID      => '162b957e5dc7957ae9fe99eed69daa5ff1d65b89a312bdede56d843f20b15645',
            Fields::BLOCK_FUND                  => false,
            Fields::GATEWAY_MANDATE_ID          => $this->input[Fields::UPI_REQUEST_ID],
            Fields::GATEWAY_REFERENCE_ID        => '911416196085',
            Fields::GATEWAY_RESPONSE_CODE       => '00',
            Fields::GATEWAY_RESPONSE_MESSAGE    => 'Mandate is paused',
            Fields::GATEWAY_RESPONSE_STATUS     => 'SUCCESS',
            Fields::INITIATED_BY                => 'payer',
            Fields::MANDATE_NAME                => 'Sample mandate test',
            Fields::MANDATE_TIMESTAMP           => Carbon::now()->toIso8601String(),
            Fields::MANDATE_TYPE                => $this->input[Fields::REQUEST_TYPE],
            Fields::MERCHANT_CUSTOMER_ID        => $this->input[Fields::MERCHANT_CUSTOMER_ID],
            Fields::MERCHANT_REQUEST_ID         => $this->input[Fields::MERCHANT_REQUEST_ID],
            Fields::ORG_MANDATE_ID              => $this->input[Fields::ORG_MANDATE_ID],
            Fields::PAUSE_END                   => $this->input[Fields::PAUSE_END] ,
            Fields::PAUSE_START                 => $this->input[Fields::PAUSE_START] ,
            Fields::PAYEE_MCC                   => '2222',
            Fields::PAYEE_NAME                  => 'Payee Name',
            Fields::PAYEE_VPA                   => 'test@razoraxis',
            Fields::PAYER_NAME                  => 'Payer name',
            Fields::PAYER_REVOCABLE             => true,
            Fields::PAYER_VPA                   => 'customer@razoraxis',
            Fields::RECURRENCE_PATTERN          => 'WEEKLY',
            Fields::RECURRENCE_RULE             => 'BEFORE',
            Fields::RECURRENCE_VALUE            => '2',
            Fields::REF_URL                     => 'https::example.com',
            Fields::REMARKS                     => $this->input[Fields::REMARKS],
            Fields::ROLE                        => 'PAYER',
            Fields::SHARE_TO_PAYEE              => true,
            Fields::TRANSACTION_TYPE            => 'UPI_MANDATE',
            Fields::UMN                         => str_random(10).'@bajaj',
            Fields::VALIDITY_END                => Carbon::now()->addYears(10)->toDateString(),
            Fields::VALIDITY_START              => Carbon::now()->toDateString(),
            Fields::UDF_PARAMETERS              => '{}',
        ];

        $this->content($response, $this->action);

        $sign = $this->signContent(implode('', $response));

        $response[Fields::MERCHANT_PAYLOAD_SIGNATURE] = $sign;

        $response[Fields::STATUS] = 'SUCCESS';

        return $response;
    }

    public function sdkUnpauseMandate()
    {
        $response = [
            Fields::AMOUNT                      => 100,
            Fields::AMOUNT_RULE                 => 'EXACT',
            Fields::BANK_ACCOUNT_UNIQUE_ID      => '162b957e5dc7957ae9fe99eed69daa5ff1d65b89a312bdede56d843f20b15645',
            Fields::BLOCK_FUND                  => false,
            Fields::GATEWAY_MANDATE_ID          => $this->input[Fields::UPI_REQUEST_ID],
            Fields::GATEWAY_REFERENCE_ID        => '911416196085',
            Fields::GATEWAY_RESPONSE_CODE       => '00',
            Fields::GATEWAY_RESPONSE_MESSAGE    => 'Mandate is successfully unpaused',
            Fields::GATEWAY_RESPONSE_STATUS     => 'SUCCESS',
            Fields::INITIATED_BY                => 'payer',
            Fields::MANDATE_NAME                => 'Sample mandate test',
            Fields::MANDATE_TIMESTAMP           => Carbon::now()->toIso8601String(),
            Fields::MANDATE_TYPE                => $this->input[Fields::REQUEST_TYPE],
            Fields::MERCHANT_CUSTOMER_ID        => $this->input[Fields::MERCHANT_CUSTOMER_ID],
            Fields::MERCHANT_REQUEST_ID         => $this->input[Fields::MERCHANT_REQUEST_ID],
            Fields::ORG_MANDATE_ID              => $this->input[Fields::ORG_MANDATE_ID],
            Fields::PAYEE_MCC                   => '2222',
            Fields::PAYEE_NAME                  => 'Payee Name',
            Fields::PAYEE_VPA                   => 'test@razoraxis',
            Fields::PAYER_NAME                  => 'Payer name',
            Fields::PAYER_REVOCABLE             => true,
            Fields::PAYER_VPA                   => 'customer@razoraxis',
            Fields::RECURRENCE_PATTERN          => 'WEEKLY',
            Fields::RECURRENCE_RULE             => 'BEFORE',
            Fields::RECURRENCE_VALUE            => '2',
            Fields::REF_URL                     => 'https::example.com',
            Fields::REMARKS                     => $this->input[Fields::REMARKS],
            Fields::ROLE                        => 'PAYER',
            Fields::SHARE_TO_PAYEE              => true,
            Fields::TRANSACTION_TYPE            => 'UPI_MANDATE',
            Fields::UMN                         => str_random(10).'@bajaj',
            Fields::VALIDITY_END                => Carbon::now()->addYears(10)->toDateString(),
            Fields::VALIDITY_START              => Carbon::now()->toDateString(),
            Fields::UDF_PARAMETERS              => '{}',
        ];

        $this->content($response, $this->action);

        $sign = $this->signContent(implode( '', $response));

        $response[Fields::MERCHANT_PAYLOAD_SIGNATURE] = $sign;

        $response[Fields::STATUS] = 'SUCCESS';

        return $response;
    }

    public function sdkRevokeMandate()
    {
        $response = [
            Fields::AMOUNT                      => 100,
            Fields::AMOUNT_RULE                 => 'EXACT',
            Fields::BANK_ACCOUNT_UNIQUE_ID      => '162b957e5dc7957ae9fe99eed69daa5ff1d65b89a312bdede56d843f20b15645',
            Fields::BLOCK_FUND                  => false,
            Fields::EXPIRY                      => $this->formattedTime(30),
            Fields::GATEWAY_MANDATE_ID          => $this->input[Fields::UPI_REQUEST_ID],
            Fields::GATEWAY_REFERENCE_ID        => '911416196085',
            Fields::GATEWAY_RESPONSE_CODE       => '00',
            Fields::GATEWAY_RESPONSE_MESSAGE    => 'Mandate is successfully revoked',
            Fields::GATEWAY_RESPONSE_STATUS     => 'SUCCESS',
            Fields::INITIATED_BY                => 'payer',
            Fields::MANDATE_NAME                => 'Sample mandate test',
            Fields::MANDATE_TIMESTAMP           => Carbon::now()->toIso8601String(),
            Fields::MANDATE_TYPE                => $this->input[Fields::REQUEST_TYPE],
            Fields::MERCHANT_CUSTOMER_ID        => $this->input[Fields::MERCHANT_CUSTOMER_ID],
            Fields::MERCHANT_REQUEST_ID         => $this->input[Fields::MERCHANT_REQUEST_ID],
            Fields::ORG_MANDATE_ID              => str_random(35),
            Fields::PAYEE_MCC                   => '2222',
            Fields::PAYEE_NAME                  => 'Payee Name',
            Fields::PAYEE_VPA                   => 'test@razoraxis',
            Fields::PAYER_NAME                  => 'Payer name',
            Fields::PAYER_REVOCABLE             => true,
            Fields::PAYER_VPA                   => 'customer@razoraxis',
            Fields::RECURRENCE_PATTERN          => 'WEEKLY',
            Fields::RECURRENCE_RULE             => 'BEFORE',
            Fields::RECURRENCE_VALUE            => '2',
            Fields::REF_URL                     => 'https::example.com',
            Fields::REMARKS                     => 'sample remarks',
            Fields::ROLE                        => 'PAYER',
            Fields::SHARE_TO_PAYEE              => true,
            Fields::TRANSACTION_TYPE            => 'UPI_MANDATE',
            Fields::UMN                         => str_random(10).'@bajaj',
            Fields::VALIDITY_END                => Carbon::now()->addYears(10)->toDateString(),
            Fields::VALIDITY_START              => Carbon::now()->toDateString(),
            Fields::UDF_PARAMETERS              => '{}',
        ];

        $this->content($response, $this->action);

        $sign = $this->signContent(implode('', $response ));

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
        $recurrencePattern = ['ONETIME', 'DAILY', 'ASPRESENTED'];

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

            case UpiAction::INCOMING_MANDATE_PAUSE:
                return $this->sdkPauseMandate();

            case UpiAction::CUSTOMER_INCOMING_MANDATE_CREATE_REQUEST_RECEIVED:
            case UpiAction::CUSTOMER_INCOMING_MANDATE_UPDATE_REQUEST_RECEIVED:
                $callback = [
                    Fields::AMOUNT                      => $input[Fields::AMOUNT],
                    Fields::AMOUNT_RULE                 => $input[Fields::AMOUNT_RULE],
                    Fields::BLOCK_FUND                  => $input[Fields::BLOCK_FUND] ?? false,
                    Fields::EXPIRY                      => $input[Fields::EXPIRY] ?? $this->formattedTime(30),
                    Fields::GATEWAY_MANDATE_ID          => $input[Fields::GATEWAY_MANDATE_ID] ?? str_random(35),
                    Fields::GATEWAY_REFERENCE_ID        => '911416196085',
                    Fields::IS_MARKED_SPAM              => 'false',
                    Fields::IS_VERIFIED_PAYEE           => 'true',
                    Fields::INITIATED_BY                => $input[Fields::INITIATED_BY] ?? 'PAYER',
                    Fields::MANDATE_NAME                => 'Sample mandate test',
                    Fields::MANDATE_TIMESTAMP           => $input[Fields::MANDATE_TIMESTAMP] ??
                                                           Carbon::now()->toIso8601String(),
                    Fields::MERCHANT_CUSTOMER_ID        => $this->input[Fields::MERCHANT_CUSTOMER_ID],
                    Fields::MERCHANT_ID                 => 'MERCHANT',
                    Fields::ORG_MANDATE_ID              => $input[Fields::ORG_MANDATE_ID] ?? str_random(35),
                    Fields::PAYEE_MCC                   => '2222',
                    Fields::PAYEE_NAME                  => 'Payee Name',
                    Fields::PAYEE_VPA                   => $input[Fields::PAYEE_VPA] ?? 'test@razoraxis',
                    Fields::PAYER_REVOCABLE             => $input[Fields::PAYER_REVOCABLE] ?? true,
                    Fields::PAYER_VPA                   => $input[Fields::PAYER_VPA] ?? 'customer@razoraxis',
                    Fields::RECURRENCE_PATTERN          => $input[Fields::RECURRENCE_PATTERN] ?? 'WEEKLY',
                    Fields::REF_URL                     => 'https::example.com',
                    Fields::ROLE                        => $input[Fields::ROLE] ?? 'PAYER',
                    Fields::SHARE_TO_PAYEE              => $input[Fields::SHARE_TO_PAYEE] ?? true,
                    Fields::TRANSACTION_TYPE            => $input[Fields::TRANSACTION_TYPE] ?? 'UPI_MANDATE',
                    Fields::TYPE                        => $type,
                    Fields::UMN                         => $input[Fields::UMN] ?? str_random(10).'@bajaj',
                    Fields::VALIDITY_START              => $input[Fields::VALIDITY_START] ??
                                                           Carbon::now()->toDateString(),
                    Fields::VALIDITY_END                => $input[Fields::VALIDITY_END] ??
                                                           Carbon::now()->addYears(10)->toDateString(),
                ];

                if(in_array($callback[Fields::RECURRENCE_PATTERN], $recurrencePattern, true) === false)
                {
                    $callback[Fields::RECURRENCE_RULE]  = $input[Fields::RECURRENCE_RULE] ?? 'BEFORE';
                    $callback[Fields::RECURRENCE_VALUE] = $input[Fields::RECURRENCE_VALUE] ?? '2';
                }

                if($input[Fields::MANDATE_TYPE] == 'UPDATE')
                {
                    $callback[Fields::ACCOUNT_REFERENCE_ID] = $input[Fields::ACCOUNT_REFERENCE_ID];
                    $callback[Fields::MANDATE_TYPE]         = $input[Fields::MANDATE_TYPE];
                }

                break;

            case UpiAction::CUSTOMER_INCOMING_MANDATE_CREATED:
            case UpiAction::CUSTOMER_INCOMING_MANDATE_UPDATED:
            case UpiAction::MANDATE_STATUS_UPDATE:
            case UpiAction::CUSTOMER_OUTGOING_MANDATE_PAUSED:
                $callback = [
                    Fields::ACCOUNT_REFERENCE_ID        => $input[Fields::ACCOUNT_REFERENCE_ID],
                    Fields::AMOUNT                      => $input[Fields::AMOUNT],
                    Fields::AMOUNT_RULE                 => $input[Fields::AMOUNT_RULE],
                    Fields::BLOCK_FUND                  => $input[Fields::BLOCK_FUND] ?? false,
                    Fields::EXPIRY                      => $input[Fields::EXPIRY] ?? $this->formattedTime(30),
                    Fields::GATEWAY_MANDATE_ID          => $input[Fields::GATEWAY_MANDATE_ID] ?? str_random(35),
                    Fields::GATEWAY_REFERENCE_ID        => '911416196085',
                    Fields::GATEWAY_RESPONSE_CODE       => $input[Fields::GATEWAY_RESPONSE_CODE] ?? $successCode,
                    Fields::GATEWAY_RESPONSE_MESSAGE    => $input[Fields::GATEWAY_RESPONSE_MESSAGE] ??
                                                           'Mandate is successfully created',
                    Fields::GATEWAY_RESPONSE_STATUS     => 'SUCCESS',
                    Fields::INITIATED_BY                => $input[Fields::INITIATED_BY] ?? 'PAYER',
                    Fields::MANDATE_NAME                => 'Sample mandate test',
                    Fields::MANDATE_TIMESTAMP           => $input[Fields::MANDATE_TIMESTAMP] ??
                                                           Carbon::now()->toIso8601String(),
                    Fields::MERCHANT_CUSTOMER_ID        => $this->input[Fields::MERCHANT_CUSTOMER_ID],
                    Fields::MERCHANT_ID                 => 'MERCHANT',
                    Fields::MERCHANT_REQUEST_ID         => $input[Fields::MERCHANT_REQUEST_ID] ?? null,
                    Fields::ORG_MANDATE_ID              => $input[Fields::ORG_MANDATE_ID] ?? str_random(35),
                    Fields::PAYEE_MCC                   => '2222',
                    Fields::PAYEE_NAME                  => 'Payee Name',
                    Fields::PAYEE_VPA                   => $input[Fields::PAYEE_VPA] ?? 'test@razoraxis',
                    Fields::PAYER_NAME                  => 'Payer Name',
                    Fields::PAYER_REVOCABLE             => $input[Fields::PAYER_REVOCABLE] ?? true,
                    Fields::PAYER_VPA                   => $input[Fields::PAYER_VPA] ?? 'customer@razoraxis',
                    Fields::RECURRENCE_PATTERN          => $input[Fields::RECURRENCE_PATTERN] ?? 'WEEKLY',
                    Fields::REF_URL                     => 'https::example.com',
                    Fields::ROLE                        => $input[Fields::ROLE] ?? 'PAYER',
                    Fields::SHARE_TO_PAYEE              => $input[Fields::SHARE_TO_PAYEE] ?? true,
                    Fields::TRANSACTION_TYPE            => $input[Fields::TRANSACTION_TYPE] ?? 'UPI_MANDATE',
                    Fields::TYPE                        => $type,
                    Fields::UMN                         => $input[Fields::UMN] ?? str_random(10).'@bajaj',
                    Fields::VALIDITY_START              => $input[Fields::VALIDITY_START] ??
                                                           Carbon::now()->toDateString(),
                    Fields::VALIDITY_END                => $input[Fields::VALIDITY_END] ??
                                                           Carbon::now()->addYears(10)->toDateString(),
                ];

                if(in_array($callback[Fields::RECURRENCE_PATTERN], $recurrencePattern, true) === false)
                {
                    $callback[Fields::RECURRENCE_RULE]  = $input[Fields::RECURRENCE_RULE] ?? 'BEFORE';
                    $callback[Fields::RECURRENCE_VALUE] = $input[Fields::RECURRENCE_VALUE] ?? '2';
                }

                if (($callback[Fields::GATEWAY_RESPONSE_CODE]) === 'JPMP' or
                    ($input[Fields::MANDATE_TYPE] == 'PAUSE'))
                {
                    $callback[Fields::PAUSE_START]      = $input[Fields::PAUSE_START] ??
                                                          Carbon::now()->toDateString();
                    $callback[Fields::PAUSE_END]        = $input[Fields::PAUSE_END] ??
                                                          Carbon::now()->addYears(10)->toDateString();
                }
                if(empty($input[Fields::MANDATE_TYPE]) === false)
                {
                    $callback[Fields::MANDATE_TYPE] = $input[Fields::MANDATE_TYPE];
                }
                // mandate approval timestamp should be set for approval request
                if(isset($input[Fields::MANDATE_APPROVAL_TIMESTAMP]) === true)
                {
                    $callback[Fields::MANDATE_APPROVAL_TIMESTAMP]  = $input[Fields::MANDATE_APPROVAL_TIMESTAMP];
                }

                if($type === UpiAction::MANDATE_STATUS_UPDATE && $input[Fields::MANDATE_TYPE] == 'PAUSE')
                {
                    $callback[Fields::STATUS] = 'PAUSE';
                }
                else if($type === UpiAction::MANDATE_STATUS_UPDATE)
                {
                    $callback[Fields::STATUS] = 'completed';
                }
                break;

            case UpiAction::CUSTOMER_INCOMING_PRE_PAYMENT_NOTIFICATION_MANDATE_RECEIVED:
                $callback = [
                    Fields::AMOUNT                      => $input[Fields::AMOUNT],
                    Fields::GATEWAY_MANDATE_ID          => $input[Fields::GATEWAY_MANDATE_ID],
                    Fields::GATEWAY_REFERENCE_ID        => '911416196085',
                    Fields::GATEWAY_RESPONSE_CODE       => $input[Fields::GATEWAY_RESPONSE_CODE] ?? $successCode,
                    Fields::GATEWAY_RESPONSE_MESSAGE    => $input[Fields::GATEWAY_RESPONSE_MESSAGE] ??
                                                           'Mandate Notification Received Successfully',
                    Fields::GATEWAY_RESPONSE_STATUS     => 'SUCCESS',
                    Fields::MERCHANT_CUSTOMER_ID        => $input[Fields::MERCHANT_CUSTOMER_ID],
                    Fields::MERCHANT_REQUEST_ID         => $input[Fields::MERCHANT_REQUEST_ID] ?? null,
                    Fields::NEXT_EXECUTION              => $input[Fields::NEXT_EXECUTION] ??
                                                           Carbon::now()->addDays(1)->toIso8601String(),
                    Fields::ORG_MANDATE_ID              => $input[Fields::ORG_MANDATE_ID] ?? str_random(35),
                    Fields::SEQ_NUMBER                  => $input[Fields::SEQ_NUMBER] ?? '1',
                    Fields::TYPE                        => $type,
                    Fields::UMN                         => $input[Fields::UMN],
                ];
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
