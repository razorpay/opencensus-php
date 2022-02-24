<?php

namespace RZP\Gateway\Mozart\Mock;

use RZP\Gateway\Base;

class VerifyRefundData extends Base\Mock\Server
{
    public function bajajfinserv($entities)
    {
        $response = [
            'data' =>
                [
                    'enqinfo' => [
                        '0' => [
                            'DEALID' => 'CS905114097404',
                            'ERRORDESCRIPTION' => 'TRANSACTION PERFORMED SUCCESSFULLY',
                            'Key' => $entities['terminal']['gateway_secure_secret'],
                            'ORDERNO' => '104',
                            'REQUESTID' => '1234',
                            'RESPONSECODE' => '0'
                        ]
                    ],
                    'received' => true,
                    'requeryid' => '1234',
                    'reqid' => 'RZP200219195445345',
                    'rescode' => '00',
                    'rqtype' => 'CAN',
                    'status' => 'verification_successful',
                    'valkey' => $entities['terminal']['gateway_secure_secret'],
                    'errdesc' => 'SUCCESS',
                    'Key' => $entities['terminal']['gateway_secure_secret'],
                    '_raw' => '',
                ],
            'error'             => null,
            'success'           => true,
            'mozart_id'         => '',
            'external_trace_id' => '',
        ];

        return $response;
    }

    public function wallet_phonepe($entities)
    {
        $response = [
            'data' =>
                [
                    '_raw' => '',
                    'code' => 'PAYMENT_SUCCESS',
                    'amount' => $entities['payment']['amount'],
                    'merchantId' => 'abc',
                    'payResponseCode' => 'SUCCESS',
                    'paymentState' => 'COMPLETED',
                    'providerReferenceId' => 'phonepeProviderRefId',
                    'transactionId' => $entities['refund']['id'],
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

    public function wallet_paypal($entities)
    {
        $response = [
            'data'=> [
                    "_raw"=> "{\"header\":{\"Date\":[\"Tue, 30 Jul 2019 12:22:02 GMT\"],\"Set-Cookie\":[\"X-PP-SILOVER=name%3DSANDBOX3.API.1%26silo_version%3D1880%26app%3Dapiplatformproxyserv%26TIME%3D1781940317%26HTTP_X_PP_AZ_LOCATOR%3Dsandbox.slc; Expires=Tue, 30 Jul 2019 12:52:03 GMT; domain=.paypal.com; path=/; Secure; HttpOnly\",\"X-PP-SILOVER=; Expires=Thu, 01 Jan 1970 00:00:01 GMT\"],\"Content-Type\":[\"application/json\"],\"Server\":[\"Apache\"],\"Paypal-Debug-Id\":[\"1035ec70c6c6c\",\"1035ec70c6c6c\"],\"Http_x_pp_az_locator\":[\"sandbox.slc\"],\"Vary\":[\"Authorization\"],\"Content-Length\":[\"701\"]},\"status\":200,\"body\":\"{\\\"id\\\":\\\"78027727TF804050W\\\",\\\"amount\\\":{\\\"currency_code\\\":\\\"USD\\\",\\\"value\\\":\\\"1.00\\\"},\\\"seller_payable_breakdown\\\":{\\\"gross_amount\\\":{\\\"currency_code\\\":\\\"USD\\\",\\\"value\\\":\\\"1.00\\\"},\\\"paypal_fee\\\":{\\\"currency_code\\\":\\\"USD\\\",\\\"value\\\":\\\"0.04\\\"},\\\"net_amount\\\":{\\\"currency_code\\\":\\\"USD\\\",\\\"value\\\":\\\"0.96\\\"},\\\"total_refunded_amount\\\":{\\\"currency_code\\\":\\\"USD\\\",\\\"value\\\":\\\"1.00\\\"}},\\\"invoice_id\\\":\\\"nj8yy7gy\\\",\\\"custom_id\\\":\\\"nj8yy7gy\\\",\\\"status\\\":\\\"COMPLETED\\\",\\\"create_time\\\":\\\"2019-07-30T05:21:16-07:00\\\",\\\"update_time\\\":\\\"2019-07-30T05:21:16-07:00\\\",\\\"links\\\":[{\\\"href\\\":\\\"https://api.sandbox.paypal.com/v2/payments/refunds/78027727TF804050W\\\",\\\"rel\\\":\\\"self\\\",\\\"method\\\":\\\"GET\\\"},{\\\"href\\\":\\\"https://api.sandbox.paypal.com/v2/payments/captures/8DS61651XA862144J\\\",\\\"rel\\\":\\\"up\\\",\\\"method\\\":\\\"GET\\\"}]}\"}",
                    "amount"=> [
                        "currency_code" => $entities['payment']['currency'],
                        "value" => $entities['payment']['amount'],
                    ],
                    "create_time" => "2019-08-01T02:25:08-07:00",
                    "custom_id" => "67g7bb7u6",
                    "id" => "09188073PT4749456",
                    "invoice_id" => "67g7bb7u6",
                    "links" => [
                        [
                            "href" => "https://api.sandbox.paypal.com/v2/payments/refunds/09188073PT4749456",
                            "method" => "GET",
                            "rel" => "self",
                        ],
                        [
                            "href" => "https://api.sandbox.paypal.com/v2/payments/captures/6TH801614C6688932",
                            "method" => "GET",
                            "rel" => "up",
                        ]
                    ],
                    "seller_payable_breakdown" => [
                        "gross_amount" => [
                            "currency_code" => "USD",
                                "value" => "1.00",
                            ],
                            "net_amount" => [
                            "currency_code" => "USD",
                                "value" => "0.96",
                            ],
                            "paypal_fee" => [
                            "currency_code" => "USD",
                                "value" => "0.04",
                            ],
                            "total_refunded_amount" => [
                            "currency_code" => "USD",
                                "value" => "1.00",
                            ]
                        ],
                    "status"=> "verification_successful",
                    "update_time" => "2019-08-01T02:25:08-07:00",
                ],
            'error'=> null,
            'external_trace_id'=> '',
            'mozart_id'=> '',
            'next'=> [],
            'success'=> true,
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
                        'refunds' => [
                            [
                                'amount_in_paise'           => 50,
                                'billing_address'           => [],
                                'delivered'                 => false,
                                'discount_amount_in_paise'  => 0,
                                'id'                        => '32e6ae2c-8d4e-4cdd-8d78-9ec753af7c7f',
                                'items'                     => [],
                                'metadata'                  => null,
                                'order' => [
                                    'merchant_order_id' => 'pg_payment_1234r1'
                                ],
                                'shipping_address'          => [],
                                'shipping_amount_in_paise'  => 0,
                                'status'                    => 'REFUND',
                            ]
                        ],
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

        $this->content($response, 'verify_refund');

        return $response;
    }

    public function netbanking_scb($entities)
    {
        $response = [
            'data' =>
                [
                    'code' => '0',
                    'errorCode' => 000,
                    'message' => 'successful',
                    'txnStatus' => 'SUCCESS',
                    'paymentId' => $entities['payment']['id'],
                    'amount' => $entities['payment']['amount'],
                    'hash' => 'abcd',
                    '_raw' => '',
                ],
            'error'             => null,
            'success'           => true,
            'mozart_id'         => '',
            'external_trace_id' => '',
        ];

        $this->content($response, 'verify_refund');

        return $response;
    }

    public function cred($entities)
    {
        $response = [
            'data' =>
                [
                    'code' => '0',
                    'errorCode' => 000,
                    'message' => 'successful',
                    'txnStatus' => 'SUCCESS',
                    'paymentId' => $entities['payment']['id'],
                    'amount' => $entities['payment']['amount'],
                    'hash' => 'abcd',
                    '_raw' => '',
                ],
            'error'             => null,
            'success'           => true,
            'mozart_id'         => '',
            'external_trace_id' => '',
        ];

        $this->content($response, 'verify_refund');

        return $response;
    }
}
