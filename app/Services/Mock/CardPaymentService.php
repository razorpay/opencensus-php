<?php

namespace RZP\Services\Mock;

use RZP\Models\Payment;
use RZP\Exception\BaseException;
use RZP\Reconciliator\Base\Constants;
use RZP\Services\CardPaymentService as BaseCardPaymentService;

class CardPaymentService extends BaseCardPaymentService
{
    public function content(& $content, $action = '')
    {
        return $content;
    }
    
    public function action(string $gateway, string $action, array $input): array
    {
        if ($action === 'fail') {
            $this->throwServiceErrorException(new BaseException('timed out or something'));
        }
        
        if ($action === 'authorize' && $gateway === 'payu') {
            return $this->payuAuthorizeMock($input);
        }
        
        if ($action === 'callback' && $gateway === 'payu') {
            return $this->payuCallbackMock($input);
        }
        
        if ($action === 'authorize_failed' && $gateway === 'payu') {
            return $this->payuVerifyMock($input);
        }
        
        return $input;
    }
    
    protected function payuAuthorizeMock(array $input)
    {
        return [
            "data" =>
                [
                    "content" =>
                        [
                            "additional_info" => "[\"last4Digits\"=>\"3684\",\"tavv\"=>\"ABQBAIeZBAAgIwMTI1YXAAAAAAA=\",\"tokenRefNo\"=>\"a6d69808-7505-4f2f-9f7c-64106bd0f33e\",\"trid\"=>\"77700011118\"]",
                            "amount" => $input['payment']['amount'],
                            "enforce_paymethod" => "creditcard|debitcard",
                            "furl" => $input['callbackUrl'],
                            "hash" => "1da538f8045de732254697bde08674921e799146482220792088075efbdb9f834f1f31699b7913262526264fd77757b877c968338ed92c60f17efa7c1856f572",
                            "key" => "12345",
                            "pg" => "CC",
                            "productinfo" => "XXXX",
                            "store_card_token" => "6528249099933684",
                            "storecard_token_type" => 1,
                            "surl" => $input['callbackUrl'],
                            "txnid" => $input['payment']['id'],
                            "udf1" => "",
                            "udf2" => "pay_" . $input['payment']['id']
                        ],
                    "method" => "POST",
                    "url" => "https://test.payu.in/_payment"
                ],
            "payment" =>
                [
                    "auth_type" => "3ds"
                ],
            "success" => true,
            "status_code" => 200
        ];
    }
    
    protected function payuCallbackMock(array $input)
    {
        return [
            "data" =>
                [
                    "payment" => [
                        "reference2" => "12345",
                        "reference1" => "12345",
                        "two_factor_auth" => "unknown",
                    ],
                    "acquirer" => [
                        "reference2" => "12345",
                    ]
                ],
            "payment" => [
                "reference2" => "12345",
                "two_factor_auth" => "unknown",
            ],
            "success" => true,
            "status_code" => 200
        ];
    }
    
    protected function payuVerifyMock(array $input)
    {
        return [
            "data" =>
                [
                    "payment" => [
                        "reference2" => "12345"
                    ],
                    "amount" => $input['payment']['amount'],
                    "gateway_success" => true
                ],
            "payment" => [
                "reference2" => "12345"
            ],
            "success" => true,
            "status_code" => 200
        ];
    }

    public function fetchMultiple(string $entityName, array $input): array
    {
        return [];
    }

    public function fetch(string $entityName, string $id, $input)
    {
        return [];
    }


    public function authorizeAcrossTerminals(Payment\Entity $payment, array $gatewayInput, array $terminals)
    {
        return [];
    }

    public function fetchAuthorizationData(array $input)
    {
//        $paymentId = $input['payment_ids'][0];

        $return = [];

        foreach ($input['payment_ids'] as $paymentId)
        {
            $fields = $input['fields'];

            $dummyData = [
                Constants::RRN                       => '123412341234',
                Constants::STATUS                    => 'failed',
                Constants::AUTH_CODE                 => '',
                Constants::GATEWAY_TRANSACTION_ID    => '1234456789',
                Constants::NETWORK_TRANSACTION_ID    => '0392166726767771',
                Constants::GATEWAY_REFERENCE_ID2     => '6584842357886332606090',
            ];

            $response = [];

            // Add the asked fields in response
            foreach ($fields as $field)
            {
                $response[$field] = $dummyData[$field] ?? null;
            }

                $return[$paymentId] = $response;
        }

        $this->content($return, 'fetchAuthorizationData');

        return $return;
    }

    public function fetchPaymentIdFromCapsPIDs(array $input)
    {
        return [];
    }
}
