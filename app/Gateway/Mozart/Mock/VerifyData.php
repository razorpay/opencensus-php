<?php

namespace RZP\Gateway\Mozart\Mock;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Gateway\Upi\Base\Entity as UpiEntity;
use RZP\Gateway\Mozart\Mock\Upi\MozartUpiResponse;

class VerifyData extends Base\Mock\Server
{
    public function upi_airtel($entities)
    {
        $response = [
            'data' =>
                [
                    'code' => '0',
                    'errorCode' => 000,
                    'message' => 'successful',
                    'rrn' => '987654321',
                    'txnStatus' => 'SUCCESS',
                    'hdnOrderID' => $entities['payment']['id'],
                    'amount' => $entities['payment']['amount'],
                    'hash' => 'abcd',
                    '_raw' => '{\"rrn\":\"910501000856\",\"txnStatus\":\"SUCCESS\",\"hdnOrderID\":\"ablxasaasbajahskajkg\",\"hash\":\"6256e8a43ba4e56eac1ef8c1faaad0c7236595e3638d74dd7c30e787dc00235624a5d2920230cf5478c88d616474abd1185c236b3c30107f7c931fb7070e20d9\",\"messageText\":\"\",\"code\":\"0\",\"errorCode\":\"000\",\"txnId\":\"ablxasaasbajahskajkg\"',
                ],
            'error'             => null,
            'success'           => true,
            'mozart_id'         => '',
            'external_trace_id' => '',
        ];

        $this->content($response, 'verify');

        return $response;
    }

    public function upi_yesbank($entities)
    {
        if ($this->isV2Mock($entities['payment']['description']))
        {
            return $this->upiMozartV2($entities);
        }

        return []; // Yesbank Supports only v2 contracts.
    }

    public function upi_citi($entities)
    {
        $error = null;

        switch ($entities['payment']['description'])
        {
            case 'verifyNotFound':
                $error = [
                    'internal_error_code'       => 'GATEWAY_ERROR_TRANSACTION_NOT_PRESENT',
                    'gateway_error_code'        => '601',
                    'gateway_error_description' => 'transaction number does not exist',
                ];
                break;
        }

        $response = [
            'data' =>
                [
                    '_raw' => '{"TxnStatusRs": {"APIHeader": {"ClientId": "client_id","TxnRefNo": "UPI","TimeStamp": "2019-07-16T14:03:01+05:30","CountryCode": "IN"},"APIBody": {"RespCode": "601","StatusDesc": "Payment not found in citibank","RefNo": "UPI","StatusCode": "6"}}}',
                ],
            'error'             => $error,
            'success'           => empty($error),
            'mozart_id'         => '',
            'external_trace_id' => '',
        ];

        return $response;
    }

    public function netbanking_yesb($entities)
    {
        $response = [
            'error'             => null,
            'next'              => [],
            'success'           => true,
            'external_trace_id' => 'DUMMY_REQUEST_ID',
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'data' => [
                '_raw'            => 'dummy_raw_value',
                'bank_payment_id' => $entities['gateway']['pay_verify']['bank_payment_id'],
                'status'          => 'verification_successful',
                'paymentId'       => $entities['payment']['id'],
                'amount'          => $entities['payment']['amount']
            ],
        ];

        return $response;
    }

    public function paylater_icici($entities)
    {
        $response = [
            'error'             => null,
            'next'              => [],
            'success'           => true,
            'external_trace_id' => 'DUMMY_REQUEST_ID',
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'data' => [
                '_raw'            => 'dummy_raw_value',
                'bank_payment_id' => "0000000000",
                'status'          => 'verification_successful',
                'paymentId'       => $entities['payment']['id'],
                'amount'          => $entities['payment']['amount']
            ],
        ];

        return $response;
    }

    public function netbanking_kvb($entities)
    {
        $response = [
            'error'             => null,
            'next'              => [],
            'success'           => true,
            'external_trace_id' => 'DUMMY_REQUEST_ID',
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'data' => [
                '_raw'            => 'dummy_raw_value',
                'bank_payment_id' => $entities['gateway']['pay_verify']['bank_payment_id'],
                'status'          => 'verification_successful',
                'paymentId'       => $entities['payment']['id'],
                'amount'          => $entities['payment']['amount']
            ],
        ];

        return $response;
    }

    public function netbanking_sib($entities)
    {
        $response = [
            'error' => null,
            'next' => [],
            'success' => true,
            'external_trace_id' => 'DUMMY_REQUEST_ID',
            'mozart_id' => 'DUMMY_MOZART_ID',
            'data' => [
                '_raw' => ['BODY' => 'Transaction Completed Successfully']
            ],
        ];

        return $response;
    }

    public function netbanking_ubi($entities)
    {
        $response = [
            'error'             => null,
            'next'              => [],
            'success'           => true,
            'external_trace_id' => 'DUMMY_REQUEST_ID',
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'data' => [
                '_raw'              => ['Status' => 'Y'],
                'bank_payment_id'   => '999999',
                'paymentId'         => $entities['payment']['id'],
                'status'            => 'verification_successful',
            ],
        ];

        return $response;
    }

    public function netbanking_scb($entities)
    {
        $response = [
            'error'             => null,
            'next'              => [],
            'success'           => true,
            'external_trace_id' => 'DUMMY_REQUEST_ID',
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'data' => [
                '_raw'              => ['Status' => 'Y'],
                'bank_payment_id'   => '999999',
                'paymentId'         => $entities['payment']['id'],
                'status'            => 'verification_successful',
            ],
        ];

        return $response;
    }


    public function netbanking_cbi($entities)
    {
        $response = [
            'error' => null,
            'next' => [],
            'success' => true,
            'external_trace_id' => 'DUMMY_REQUEST_ID',
            'mozart_id' => 'DUMMY_MOZART_ID',
            'data' => [
                '_raw' => ['Status' => 'Y'],
                'bank_payment_id' => '2382382',
                'paymentId' => 'abcd1234',
                'status' => 'verification_successful',
            ],
        ];

        return $response;
    }

    public function netbanking_cub($entities)
    {
        $response = [
            'error'             => null,
            'next'              => [],
            'success'           => true,
            'external_trace_id' => 'DUMMY_REQUEST_ID',
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'data' => [
                '_raw'            => 'dummy_raw_value',
                'bank_payment_id' => $entities['gateway']['pay_verify']['bank_payment_id'],
                'status'          => 'verification_successful',
                'paymentId'       => $entities['payment']['id'],
                'amount'          => $entities['payment']['amount']
            ],
        ];

        return $response;
    }

    public function netbanking_ibk($entities)
    {
        $response = [
            'error'             => null,
            'next'              => [],
            'success'           => true,
            'external_trace_id' => 'DUMMY_REQUEST_ID',
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'data' => [
                '_raw'            => 'dummy_raw_value',
                'bank_payment_id' => $entities['gateway']['pay_verify']['bank_payment_id'],
                'status'          => 'verification_successful',
                'paymentId'       => $entities['payment']['id'],
                'amount'          => $entities['payment']['amount']
            ],
        ];

        return $response;
    }

    public function netbanking_idbi($entities)
    {
        $response = [
            'error'             => null,
            'next'              => [],
            'success'           => true,
            'external_trace_id' => 'DUMMY_REQUEST_ID',
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'data' => [
                '_raw'            => 'dummy_raw_value',
                'bank_payment_id' => $entities['gateway']['pay_verify']['bank_payment_id'],
                'status'          => 'verification_successful',
                'paymentId'       => $entities['payment']['id'],
                'amount'          => $entities['payment']['amount']
            ],
        ];

        return $response;
    }

    public static function bajajfinserv($entities)
    {
        $response = [
            'data' =>
                [
                    'enqinfo' => [
                        '0' => [
                            'DEALID' => 'CS905114097404',
                            'ERRORDESCRIPTION' => 'TRANSACTION PERFORMED SUCCESSFULLY',
                            'ORDERNO' => '104',
                            'REQUESTID' => '1234',
                            'RESPONSECODE' => '0'
                        ]
                    ],
                    'received' => true,
                    'requeryid' => '1234',
                    'reqid' => 'RZP200219195445344',
                    'rescode' => '00',
                    'rqtype' => 'AUTH',
                    'status' => 'verification_successful',
                    'errdesc' => 'SUCCESS',
                    '_raw' => '',
                ],
            'error'             => null,
            'success'           => true,
            'mozart_id'         => '',
            'external_trace_id' => '',
        ];

        return $response;
    }

    public function cashfree($entities)
    {
        if ($this->isV2Mock($entities['payment']['description']))
        {
            return $this->upiMozartV2($entities);
        }
    }

    public function payu($entities)
    {
        $method = $entities['payment']['method'];

        if ($method === Payment\Method::UPI)
        {
            if ($this->isV2Mock($entities['payment']['description']))
            {
                return $this->upiMozartV2($entities);
            }
        }
        if ($method === Payment\Method::WALLET)
        {
            $response = [
                'error'                        => null,
                'next'                         => [],
                'success'                      => true,
                'external_trace_id'            => 'DUMMY_REQUEST_ID',
                'mozart_id'                    => 'DUMMY_MOZART_ID',
                'data' => [
                    '_raw'                     => 'dummy_raw_data',
                    'status'                   => 'verification_successful',
                    'currency'                 => $entities['payment']['currency'],
                    'amount'                   => $entities['payment']['amount'],
                    'paymentId'                => $entities['payment']['id'],
                    'bank_reference_number'    => "dummy_back_ref_number",
                    'gateway_reference_number' => "dummy_gateway_ref_number",
                ],
            ];

            return $response;
        }
    }

    public function paytm($entities)
    {
        if ($this->isV2Mock($entities['payment']['description']))
        {
            return $this->upiMozartV2($entities);
        }
    }

    public function billdesk_optimizer($entities)
    {
        if ($this->isV2Mock($entities['payment']['description']))
        {
            return $this->upiMozartV2($entities);
        }
    }

    public function optimizer_razorpay($entities)
    {
        if ($this->isV2Mock($entities['payment']['description']))
        {
            return $this->upiMozartV2($entities);
        }
    }

    public static function cred($entities)
    {
        return;
    }

    public function ccavenue($entities)
    {
        $method = $entities['payment']['method'];

        if ($method === Payment\Method::UPI)
        {
            if ($this->isV2Mock($entities['payment']['description']) === true)
            {
                return $this->upiMozartV2($entities);
            }
        }

        $response = [
            'error'                        => null,
            'next'                         => [],
            'success'                      => true,
            'external_trace_id'            => 'DUMMY_REQUEST_ID',
            'mozart_id'                    => 'DUMMY_MOZART_ID',
            'data' => [
                '_raw'                     => 'dummy_raw_data',
                'status'                   => 'verification_successful',
                'currency'                 => $entities['payment']['currency'],
                'amount'                   => $entities['payment']['amount'],
                'paymentId'                => $entities['payment']['id'],
                'bank_reference_number'    => "dummy_back_ref_number",
                'gateway_reference_number' => "dummy_gateway_ref_number",
            ],
        ];

        return $response;
    }

    public static function wallet_phonepe($entities)
    {
        $response = [
            'data' =>
                [
                    '_raw ' => '',
                    'code' => 'PAYMENT_SUCCESS',
                    'amount' => $entities['payment']['amount'],
                    'merchantId' => 'abc',
                    'payResponseCode' => 'SUCCESS',
                    'paymentState' => 'COMPLETED',
                    'providerReferenceId' => 'phonepeProviderRefId',
                    'transactionId' => $entities['payment']['id'],
                    'message' => 'Your payment is successful.',
                    'received' => true,
                    'status' => 'verification_successful',
                    'success' => true
                ],
            'error' => null,
            'external_trace_id' => '',
            'mozart_id' => '',
            'next' => [],
            'success' => true,
        ];

        return $response;
    }

    public static function wallet_phonepeswitch($entities)
    {
        $response = [
            'data' =>
                [
                    '_raw '                 => '',
                    'code'                  => 'PAYMENT_SUCCESS',
                    'amount'                => $entities['payment']['amount'],
                    'merchantId'            => 'abc',
                    "data_status"           => "SUCCESS",
                    'payResponseCode'       => 'SUCCESS',
                    'providerReferenceId'   => 'phonepeProviderRefId',
                    'transactionId'         => $entities['payment']['id'],
                    'paymentId'             => $entities['payment']['id'],
                    'message'               => 'Your payment is successful.',
                    'received'              => true,
                    'status'                => 'verification_successful',
                    'success'               => true
                ],
            'error'             => null,
            'external_trace_id' => '',
            'mozart_id'         => '',
            'next'              => [],
            'success'           => true,
        ];

        return $response;
    }

    public static function getsimpl($entities)
    {
        $response = [
            'data'=> [
                '_raw'          => '{\"paymentId\":\"500\",\"amount\":120000,\"transaction_id\":\"60320846-5fdb-4d87-9d8a-10b992fdc593\",\"success\":true,\"Http_status\":200}',
                'api_version'   => '4.0',
                'data' => [
                    'transaction'=> [
                        'amount_in_paise'           => $entities['payment']['amount'],
                        'billing_address'           => null,
                        'delivered'                 => true,
                        'discount_amount_in_paise'  => 0,
                        'id'                        => $entities['gateway']['pay_init']['data']['transaction']['id'],
                        'items' => [
                            [
                            'sku' => '500'
                            ]
                        ],
                        'metadata' => [
                            'customer_id'   => $entities['payment']['id'],
                            'email'         => 'rzp@simpl.com'
                        ],
                        'order' => [
                            'merchant_order_id' => $entities['payment']['id']
                        ],
                        'refunds'                   => [],
                        'shipping_address'          => null,
                        'shipping_amount_in_paise'  => 0,
                        'status'                    => 'CLAIMED'
                    ]
                ],
                'status'  => 'verify_successful',
                'success' => true
            ],
            'error'             => null,
            'external_trace_id' => 'DUMMY_REQUEST_ID',
            'next'              => [],
            'success'           => true
        ];

        return $response;
    }

    public static function wallet_paypal($entities)
    {
        $response = [
            'data' =>
                [
                    '_raw ' => '{\"body\":\"{\\\"id\\\":\\\"6TH801614C6688932\\\",\\\"amount\\\":{\\\"currency_code\\\":\\\"USD\\\",\\\"value\\\":\\\"10.00\\\"},\\\"final_capture\\\":true,\\\"seller_protection\\\":{\\\"status\\\":\\\"ELIGIBLE\\\",\\\"dispute_categories\\\":[\\\"ITEM_NOT_RECEIVED\\\",\\\"UNAUTHORIZED_TRANSACTION\\\"]},\\\"disbursement_mode\\\":\\\"INSTANT\\\",\\\"seller_receivable_breakdown\\\":{\\\"gross_amount\\\":{\\\"currency_code\\\":\\\"USD\\\",\\\"value\\\":\\\"10.00\\\"},\\\"paypal_fee\\\":{\\\"currency_code\\\":\\\"USD\\\",\\\"value\\\":\\\"0.81\\\"},\\\"net_amount\\\":{\\\"currency_code\\\":\\\"USD\\\",\\\"value\\\":\\\"9.19\\\"}},\\\"invoice_id\\\":\\\"67g7bb7u6\\\",\\\"custom_id\\\":\\\"67g7bb7u6\\\",\\\"status\\\":\\\"PARTIALLY_REFUNDED\\\",\\\"create_time\\\":\\\"2019-08-01T09:23:28Z\\\",\\\"update_time\\\":\\\"2019-08-01T09:25:08Z\\\",\\\"links\\\":[{\\\"href\\\":\\\"https://api.sandbox.paypal.com/v2/payments/captures/6TH801614C6688932\\\",\\\"rel\\\":\\\"self\\\",\\\"method\\\":\\\"GET\\\"},{\\\"href\\\":\\\"https://api.sandbox.paypal.com/v2/payments/captures/6TH801614C6688932/refund\\\",\\\"rel\\\":\\\"refund\\\",\\\"method\\\":\\\"POST\\\"},{\\\"href\\\":\\\"https://api.sandbox.paypal.com/v2/checkout/orders/3A830293F71184842\\\",\\\"rel\\\":\\\"up\\\",\\\"method\\\":\\\"GET\\\"}]}\",\"header\":{\"Vary\":[\"Authorization\"],\"Server\":[\"Apache\"],\"Paypal-Debug-Id\":[\"be420230b853c\",\"be420230b853c\"],\"Http_x_pp_az_locator\":[\"sandbox.slc\"],\"Content-Length\":[\"925\"],\"Content-Type\":[\"application/json;charset=UTF-8\"],\"Date\":[\"Thu, 01 Aug 2019 09:26:46 GMT\"],\"Set-Cookie\":[\"X-PP-SILOVER=name%3DSANDBOX3.API.1%26silo_version%3D1880%26app%3Dapiplatformproxyserv%26TIME%3D1454391901%26HTTP_X_PP_AZ_LOCATOR%3Dsandbox.slc; Expires=Thu, 01 Aug 2019 09:56:46 GMT; domain=.paypal.com; path=/; Secure; HttpOnly\",\"X-PP-SILOVER=; Expires=Thu, 01 Jan 1970 00:00:01 GMT\"]},\"status\":200}',
                    "amount"=> [
                        "currency_code" => $entities['payment']['currency'],
                        "value" => $entities['payment']['amount'],
                    ],
                    "create_time" => "2019-08-01T09:23:28Z",
                    "custom_id" => "67g7bb7u6",
                    'disbursement_mode' => 'INSTANT',
                    'final_capture' => 'true',
                    "id" => "6TH801614C6688932",
                    "invoice_id" => "67g7bb7u6",
                    "links" => [
                        [
                            "href" => "https://api.sandbox.paypal.com/v2/payments/captures/6TH801614C6688932",
                            "method" => "GET",
                            "rel" => "self",
                        ],
                        [
                            "href" => "https://api.sandbox.paypal.com/v2/payments/captures/6TH801614C6688932/refund",
                            "method" =>  "POST",
                            "rel"=>  "refund",
                        ],
                        [
                            "href" => "https://api.sandbox.paypal.com/v2/checkout/orders/3A830293F71184842",
                            "method" => "GET",
                            "rel" => "up",
                        ],
                    ],
                    "seller_protection" => [
                        "dispute_categories"  => [
                            "ITEM_NOT_RECEIVED",
                            "UNAUTHORIZED_TRANSACTION",
                            ],
                            "status" => "ELIGIBLE",
                        ],
                        "seller_receivable_breakdown" => [
                            "gross_amount" => [
                                "currency_code" => "USD",
                                    "value" => "10.00",
                                ],
                                "net_amount" => [
                                "currency_code" => "USD",
                                    "value" => "9.19",
                                ],
                                "paypal_fee" => [
                                "currency_code" => "USD",
                                    "value" => "0.81",
                                ]
                        ],
                    'status' => 'verification_successful',
                    "update_time" => "2019-08-01T09:25:08Z",
                ],
            'error' => null,
            'external_trace_id' => '',
            'mozart_id' => '',
            'next' => [],
            'success' => true,
        ];

        return $response;
    }

    public function upi_juspay($entities)
    {
        if ( ($entities['payment']['vpa'] === "unexpectedpayment@abfspay") or ( $this->isV2Mock($entities['payment']['description'])) )
        {
            return $this->upiMozartV2($entities);
        }

        $response = [
            'data' =>
                [
                    '_raw' => '{"amount":"100.00","customResponse":"{}","expiry":"2016-11-25T00:10:00+05:30","gatewayReferenceId":"806115044725","gatewayResponseCode":"00","gatewayResponseMessage":"Transaction is approved","gatewayTransactionId":"XYZd0c077f39c454979...","merchantChannelId":"DEMOUATAPP","merchantId":"DEMOUAT01","merchantRequestId":"TXN1234567","payeeVpa":"merchant@abc","payerName":"Customer Name","payerVpa":"customer@xyz","transactionTimestamp":"2016-11-25T00:00:00+05:30","type":"MERCHANT_CREDITED_VIA_COLLECT","udfParameters":"{}"}',
                    'paymentId' => $entities['payment']['id'],
                    'amount' => $entities['payment']['amount'],
                    'customResponse' => '{}',
                    'expiry' => '2016-11-25T00:10:00+05:30',
                    'gatewayReferenceId' => '806115044725',
                    'gatewayResponseMessage' => 'Transaction is approved',
                    'gatewayResponseCode' => '00',
                    'gatewayTransactionId' => 'XYZd0c077f39c454979...',
                    'merchantChannelId' => 'DEMOUATAPP',
                    'upi' => [
                        'gateway_payment_id'  => 'BJJ8fa34bf3f6c64fe0bd540060eb9bcc71',
                        'npci_reference_id'   => '103800854910',
                        'merchant_reference'  => $entities['payment']['id'],
                    ],
                ],
            'error' => NULL,
            'external_trace_id' => 'DUMMY_REQUEST_ID',
            'mozart_id' => 'DUMMY_MOZART_ID',
            'next' => [],
            'success' => true
        ];

        return $response;
    }

    public function upi_sbi($entities)
    {
        $paymentId = $entities['payment']['id'];
        $vpa = $entities['payment']['vpa'];

        $response = [
            'error'             => null,
            'success'           => true,
            'external_trace_id' => 'DUMMY_REQUEST_ID',
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'data'              => [
                '_raw'            => 'dummy_raw_value',
                'gateway_response'=> [
                    'pspRefNo'      => $paymentId,
                    'upiTransRefNo' => 99999,
                    'npciTransId'   => 99999999999,
                    'custRefNo'     => "99999999999",
                    'amount'        => 500,
                    'txnAuthDate'       => Carbon::now(Timezone::IST)->toDateTimeString(),
                    'responseCode'      => "00",
                    'approvalNumber'    => random_int(100000, 999999),
                    'status'            => "S",
                    'statusDesc'        => "Payment Successful",
                    'addInfo'           => [
                        'addInfo2'          => "7971807546",
                        'statusDesc'        => "status description in addInfo not expected from gateway, but we
                                                                       still need to remove before making database call, because our
                                                                       poor database can only take 255 characters and gateway can still
                                                                       send a very large data in addInfo, Off course same applies
                                                                       for addInfo2, but since this contract is different story we are
                                                                       fine with db failure",
                    ],
                    'payerVPA' => $vpa,
                    'payeeVPA' => "razorpay@sbi",
                ],
                'paymentId'       => $paymentId,
                'bank_payment_id' => '999999',
                'status'          => 'verification_successful',
            ],
        ];

        if ($vpa === 'failedcollect@sbi')
        {
            $response['data']['gateway_response']['status'] = 'F';
            $response['data']['status'] = 'verification_failed';
            $response['data']['gateway_response']['statusDesc'] = 'Payment failed';
            $response['success'] = false;
            $response['error'] = [
                'description' => '',
                'internal_error_code' => 'BAD_REQUEST_PAYMENT_VERIFICATION_FAILED'
            ];
        }
        if ($vpa === 'failedverify@sbi')
        {
            $response['data']['gateway_response']['status'] = 'F';
            $response['data']['status'] = 'verification_failed';
            $response['data']['gateway_response']['statusDesc'] = 'Payment failed';
            $response['success'] = false;
            $response['error'] = [
                'description' => '',
                'internal_error_code' => 'BAD_REQUEST_PAYMENT_VERIFICATION_FAILED'
            ];
        }
        else if ($vpa === 'cbsdown@sbi')
        {
            $response['data']['status'] = 'verification_failed';
            $response['data']['gateway_response']['status'] = 'F';
            $response['data']['gateway_response']['statusDesc'] = 'CBS transaction processing timed out';
            $response['success'] = false;
            $response['error']['internal_error_code'] = 'BAD_REQUEST_PAYMENT_TIMED_OUT';
        }
        else if ($vpa === 'blockverify@sbi')
        {
            $response['data']['status'] = 'verification_failed';
            $response['data']['gateway_response']['status'] = 'R';
            $response['data']['gateway_response']['statusDesc'] = 'Collect Request Rejected';
            $response['success'] = false;
            $response['error']['internal_error_code'] = 'BAD_REQUEST_PAYMENT_REJECTED';
        }
        else if ($vpa === 'unexpectedPayment@sbi')
        {
            // Mocking amount for validating duplicating unexpected payment for amount mismatch
            $response['data']['gateway_response']['amount'] = 100;
        }
        else if ($vpa === 'unexpected@v2contract')
        {
            $response['data']['gateway_response']['amount'] = $entities['payment']['amount']/100;
        }

        return $response;
    }

    public function netbanking_jsb($entities)
    {
        $response = [
            'error'             => null,
            'next'              => [],
            'success'           => true,
            'external_trace_id' => 'DUMMY_REQUEST_ID',
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'data' => [
                '_raw'            => 'dummy_raw_value',
                'bank_payment_id' => $entities['gateway']['pay_verify']['bank_payment_id'],
                'status'          => 'verification_successful',
                'paymentId'       => $entities['payment']['id'],
                'amount'          => $entities['payment']['amount']
            ],
        ];

        return $response;
    }

    public function upi_icici($entities)
    {
        $response = [
            'next'                  => [],
            'error'                 => null,
            'success'               => true,
            'external_trace_id'     => 'DUMMY_REQUEST_ID',
            'mozart_id'             => 'DUMMY_MOZART_ID',
            'data'                  => [
                '_raw'              => 'dummy_raw_value',
                'paymentId'         => $entities['payment']['id'],
                'bank_payment_id'   => '999999',
                'mandate_amount'    => $entities['upi_mandate']['max_amount'],
                'status'            => 'verification_successful',
                'umn'               => '989892819',
                'rrn'               => '012345678912',
                'npci_reference_id' => '012345678912',
                'npci_txn_id'       => 'HDFC00001124',
                'gateway_data'      => [
                    'id'            => $entities['upi']['gateway_data']['id'],
                ]
            ],
        ];

        return $response;
    }

    public function upi_mindgate($entities)
    {
        if ( $entities['payment']['vpa'] === "unexpectedpayment@hdfcbank" )
        {
            return $this->upiMozartV2($entities);
        }
        $response = [
            'next'                  => [],
            'error'                 => null,
            'success'               => true,
            'external_trace_id'     => 'DUMMY_REQUEST_ID',
            'mozart_id'             => 'DUMMY_MOZART_ID',
            'data'                  => [
                '_raw'              => 'dummy_raw_value',
                'paymentId'         => $entities['payment']['id'],
                'bank_payment_id'   => '999999',
                'mandate_amount'    => $entities['upi_mandate']['max_amount'],
                'status'            => 'verification_successful',
                'umn'               => '989892819',
                'rrn'               => '012345678912',
                'npci_reference_id' => '012345678912',
                'npci_txn_id'       => 'HDFC00001124',
                'gateway_data'      => [
                    'id'            => $entities['upi']['gateway_data']['id'],
                ]
            ],
        ];

        return $response;
    }

    protected function upiMozartV2($entities)
    {
        $response = MozartUpiResponse::getDefaultInstanceForV2();

        $case = str_replace('_v2', '', $entities['payment']['description']);

        $response->mergeUpi([
            UpiEntity::VPA                  =>  $entities['payment']['vpa'] ?? '',
            UpiEntity::MERCHANT_REFERENCE   =>  $entities['payment']['id']
        ]);

        $response->setSuccess(true);

        $response->setPayment([
           'amount_authorized'  => $entities['payment']['amount'],
           'currency'           => 'INR',
        ]);

        switch ($case)
        {
            case 'verify_failed':
                $response->setSuccess(false);
                $response->setError([
                    'internal_error_code'    => ErrorCode::GATEWAY_ERROR_PAYMENT_CREATION_FAILED,
                    'gateway_error_code'     => '01',
                    'gateway_error_desc'     => 'Payment failed at gateway'
                ]);
                break;

            case 'verify_amount_mismatch':
                $response->setSuccess(true);
                $response->setPayment([
                    'amount_authorized' => $entities['payment']['amount'] + 100,
                    'currency' => 'INR'
                ]);
                break;
        }

        return $response->toArray();
    }

    public function pinelabs($entities)
    {
        if ($this->isV2Mock($entities['payment']['description']))
        {
            return $this->upiMozartV2($entities);
        }
    }

    public function upi_kotak($entities)
    {
        $paymentId = $entities['payment']['id'];
        $vpa = $entities['payment']['vpa'];

        $response = [
            "data" => [
                "_raw"      => '{"code":"00","result":"SUCCESS","data":{"status":"C","amount":"10.00","aggregatorVPA":"merchant@kotak","payerVPA":"rzp@apbl","txnid":"KMBMABCD426934594264516669306675337","orderId":"Bi4YBdQfi0fu3p","payerName":null,"referenceId":"910501000855","txntime":"2018-02-09 14:29:57.917"}}',
                "upi"       => [
                    "vpa"                 => "rzp@apbl",
                    "merchant_reference"  => $paymentId,
                    "npci_reference_id"   => "910501000855",
                    "npci_txn_id"         => "KMBMABCD426934594264516669306675337",
                    "gateway_reference"   => "910501000855",
                    "gateway_status_code" => "00"
                ],
                "terminal"  => [
                    "vpa"       => "merchant@kotak",
                    "gateway"   => "upi_kotak"
                ],
                "payment"   => [
                    "currency"          => "INR",
                    "amount_authorized" => 50000
                ],
                "status"            => "verify_successful",
                "error"             => null,
                "next"              => [],
                "success"           => true,
                "external_trace_id" => "DUMMY_REQUEST_ID",
                "mozart_id"         => "DUMMY_MOZART_ID"
            ]
        ];

        if ($vpa === 'unexpectedPayment@kotak')
        {
            // Mocking amount for validating duplicating unexpected payment for amount mismatch
            $response['data']['payment']['amount_authorized'] = 1000;
        }

        $case = $vpa;

        switch ($case) {
            case 'failedunexpectedpayment@test':
                $response['data']['success'] = false;
                $response['data']['error']   = [
                    'internal_error_code'       => ErrorCode::BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_PENDING,
                    'gateway_error_code'        => 'T01',
                    'gateway_error_desc'        => 'Transaction Pending'
                ];
                break;

            case 'unexpectedPayment@kotak':
                // Mocking amount for validating duplicating unexpected payment for amount mismatch
                $response['data']['payment']['amount_authorized'] = 1000;
                break;

            default:
                break;
        }

        return $response;
    }
}
