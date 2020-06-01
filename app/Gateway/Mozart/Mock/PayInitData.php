<?php

namespace RZP\Gateway\Mozart\Mock;

use RZP\Gateway\Base;

class PayInitData extends Base\Mock\Server
{
    use Base\Mock\GatewayTrait;

    public function hdfc_debit_emi($entities)
    {
        return [
            'data' =>
                [
                    'OrderConfirmationStatus' => 'Yes',
                    '_raw'                    => '',
                ],
            'error'             => null,
            'success'           => true,
            'mozart_id'         => '',
            'external_trace_id' => '',
        ];
    }

    public function bajajfinserv($entities)
    {
        $response = [
            'data' =>
                [
                    'Errordescription' => 'SUCCESS (OTP First Process Completed Succesfully)',
                    'Key' => $entities['terminal']['gateway_secure_secret'],
                    'MobileNo' => '2376',
                    'RequestID' => 'RZP190219162906767',
                    'Responsecode' => '0',
                    'status' => 'OTP_sent',
                    '_raw' => '',
                ],
            'next' => [
                'redirect' => [
                    'content' => [
                        'type' => 'otp',
                        'bank' => '',
                        'next' => [
                            'submit_otp',
                        ]
                    ],
                    'method' => 'post',
                    'url' => 'www.test.com',
                ]
            ],
            'error'             => null,
            'success'           => true,
            'mozart_id'         => '',
            'external_trace_id' => '',
        ];

        return $response;
    }

    public function paylater_icici($entities)
    {
        $response = [
            'data' =>
                [
                    'ResponseCode'          => '000',
                    'MobileNumber'          => '93884739457',
                    'AppName'               => 'MerchantName',
                    'TransactionIdentifier' => '3479278',
                    'bank_payment_id'       => '1234567890',
                    '_raw'                  => '',
                ],
            'error'             => null,
            'success'           => true,
            'mozart_id'         => '',
            'external_trace_id' => '',
        ];

        return $response;
    }

    public function upi_juspay($entities)
    {
        $response = [
            'data' =>
                [
                    'customerVpa' => '8123715658@upi',
                    'gatewayResponseCode' => '00',
                    'gatewayResponseMessage' => 'Accepted Collect Request',
                    'gatewayTransactionId' => 'BJJ3d0c077f39c454a...',
                    'merchantChannelId' => 'MERCHANT',
                    'merchantId' => 'MERCHANT',
                    'merchantRequestId' => $entities['payment']['id'],
                    'responseCode' => 'SUCCESS',
                    'responseMessage' => 'SUCCESS',
                    'transactionTimestamp' => '2017-06-30T17:43:40+05:30',
                    'udfParameters' => '{}',
                    '_raw' => '{"responseCode":"SUCCESS","responseMessage":"SUCCESS","payload":{"merchantId":"MERCHANT","merchantChannelId":"MERCHANTAPP","merchantRequestId":"HEYYOU45","customerVpa":"8123715658@upi","transactionTimestamp":"2017-06-30T17:43:40+05:30","gatewayTransactionId":"BJJ3d0c077f39c454a...","gatewayResponseCode":"00","gatewayResponseMessage":"Accepted Collect Request"},"udfParameters":"{}"}',
                    'status' => 'collect_inititated',
                ],
            'error' => NULL,
            'external_trace_id' => 'DUMMY_REQUEST_ID',
            'mozart_id' => 'DUMMY_MOZART_ID',
            'next' => [],
            'success' => true,
        ];

        switch ($entities['payment']['description']) {
            case 'intentPayment':
            case 'intentWithRefIdAbsent':
                $response['data'] = [];
                $response['next'] = [
                   'redirect' => [
                       'method' => 'post',
                       "url" => "upi://pay?am=100.00&cu=INR&mc=5411&pa=some@abfspay&pn=merchantname&tn=PayviaRazorpay&tr=pay_someid"
                   ]
                ];
             break;

            case 'paymentCreateFailed':
                $response['success'] = false;
                $response['data'] = [];
                $response['error']['internal_error_code'] = 'GATEWAY_ERROR_REQUEST_ERROR';
            break;
        }
        return $response;
    }

    public function cred($entities)
    {
        return;
    }

    public function wallet_phonepe($entities)
    {
        $this->gateway = $entities['payment']['gateway'];

        $url = $this->route->getUrlWithPublicAuth(
            'mock_mozart_payment_post',
            [
                'gateway' => $entities['payment']['gateway'],
                'paymentId' => $entities['payment']['id']
            ]);

        $output = [
            'code'    => 'PAYMENT_SUCCESS',
            'merchantId' => 'abc',
            'transactionId' => $entities['payment']['id'],
            'amount' => $entities['payment']['amount'],
            'providerReferenceId' => 'phonepeProviderRefId',
        ];

        $response = [
            'data' => [
                '_raw' => '',
                'code' => '',
                'message' => '',
                'received' => true,
                'status' => 'authorization_successfull',
                'success' => null
            ],
            'error' => null,
            'external_trace_id' => '',
            'mozart_id' => '',
            'next' => [
                'redirect' => [
                    'content' => $output,
                    'method' => 'post',
                    'url' => $url,
                ]
            ],
            'success' => true
        ];

        return $response;
    }

    public function wallet_phonepeswitch($entities)
    {
        $this->gateway = $entities['payment']['gateway'];

        $url = $this->route->getUrlWithPublicAuth(
            'mock_mozart_payment_post',
            [
                'gateway' => $entities['payment']['gateway'],
                'paymentId' => $entities['payment']['id']
            ]);

        $response = [
            'data' => [
                '_raw'        => '',
                'code'        => 'SUCCESS',
                'redirectUrl' => 'phonepe://checkoutResolve?reservationId=R2003021518197620892086\u0026redirectUrl=https://zeta-api.razorpay.com/v1/callback/wallet_phonepeswitch',
                'received'    => true,
                'status'      => 'authorization_successfull',
                'success'     => true
            ],
            'error'             => null,
            'external_trace_id' => '',
            'mozart_id'         => '',
            'next'              => [
                'redirect'      => [
                    'content' => [],
                    'method'  => 'post',
                    'url'     => $url,
                ]
            ],
            'success' => true
        ];

        return $response;
    }

    public function upi_airtel($entities)
    {
        $response = [
            'data' =>
                [
                    'code' => '0',
                    'errorCode' => '000',
                    'messageText' => 'Success',
                    'rrn' => '987654321',
                    'hdnOrderID' => $entities['payment']['id'],
                    'hash' => 'abcd',
                    '_raw' => '{"rrn":"910501000855","txnStatus":"PENDING","hdnOrderID":"ablxasabsjahskajkg","hash":"abcd","messageText":"Success","code":"0","errorCode":"000","txnId":"AIR461D026C5D8A48C8AED25897B9AB1877"}',
                    'status' => 'authorization_successful',
                ],
            'error'             => null,
            'success'           => true,
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'external_trace_id' => 'DUMMY_REQUEST_ID',
        ];

        return $response;
    }

    public function upi_citi($entities)
    {
        $response = [
            'data' =>
                [
                    'StatusCode' => '2',
                    'StatusDesc' => 'Accepted - Processing In Progress',
                    'RespCode' => '201',
                    'rrn' => '987654321',
                    'TxnRefNo' => $entities['payment']['id'],
                    '_raw' => '{"CollectionInitAck":{"APIHeader":{"ClientId":"ClientId","TxnRefNo":"UPI","TimeStamp":"2019-07-16T14:03:01+05:30","CountryCode":"IN"},"APIBody":{"TxnRefNo":"UPI","FPSTxnId":"Random_id","StatusCode":"2","RespCode":"201","StatusDesc":"Accepted - Processing In Progress"}}}',
                    'status' => 'collect_inititated',
                ],
            'error'             => null,
            'success'           => true,
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'external_trace_id' => 'DUMMY_REQUEST_ID',
        ];

        return $response;
    }

    public function netbanking_yesb($entities)
    {
        $url = $this->route->getUrlWithPublicAuth(
            'mock_mozart_payment_post',
            [
                'gateway'   => 'netbanking_yesb',
                'paymentId' => $entities['payment']['id'],
                'amount'    => $entities['payment']['amount']
            ]);

        $response = [
            'error'             => null,
            'data'              => [],
            'success'           => true,
            'external_trace_id' => 'DUMMY_REQUEST_ID',
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'next'              => [
                    'redirect' => [
                        'method'  => 'post',
                        'url'     => $url,
                        'content' => [
                            'PID'     => 'DUMMY_USER',
                            'encdata' => 'dummy_request_data',
                    ],
                ],
            ],
        ];

        return $response;
    }

    public function netbanking_kvb($entities)
    {
        $url = $this->route->getUrlWithPublicAuth(
            'mock_mozart_payment_post',
            [
                'gateway'   => 'netbanking_kvb',
                'paymentId' => $entities['payment']['id'],
                'amount'    => $entities['payment']['amount']
            ]);

        $response = [
            'error'             => null,
            'data'              => [],
            'success'           => true,
            'external_trace_id' => 'DUMMY_REQUEST_ID',
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'next'              => [
                'redirect' => [
                    'method'  => 'post',
                    'url'     => $url,
                    'content' => [
                        'PID'     => 'DUMMY_USER',
                        'encdata' => 'dummy_request_data',
                    ],
                ],
            ],
        ];

        return $response;
    }

    public function getsimpl($entities)
    {
        $response = [
            'data' => [
                'payment_id'    => $entities['payment']['public_id'],
                '_raw'          => '{\"amount\":400,\"Http_status\":200,\"transaction_id\":\"05f7e47f-64d2-45e2-b8db-b09a7d112606\",\"success\":true,\"paymentId\":\"pg_payment_id15\"}',
                'api_version'   => '4.0',
                'data' => [
                    'due_by' => [
                        'due_by_in_time'  => '2019-09-20T23:59:59+05:30',
                        'due_by_in_words' => '20 September, 2019'
                    ],
                    'transaction' => [
                        'amount_in_paise'          => $entities['payment']['amount'],
                        'billing_address'          => null,
                        'delivered'                => true,
                        'discount_amount_in_paise' => 0,
                        'id'                       => '05f7e47f-64d2-45e2-b8db-b09a7d112606',
                        'items' => [
                            [
                                'sku' => $entities['payment']['public_id']
                            ]
                        ],
                        'metadata' => [
                            'customer_id' => $entities['payment']['public_id'],
                            'email'       => 'rzp@simpl.com'
                        ],
                        'order' => [
                            'merchant_order_id' => $entities['payment']['public_id'],
                        ],
                        'shipping_address'         => null,
                        'shipping_amount_in_paise' => 0,
                        'status'                   => 'CLAIMED'
                    ]
                ],
                'status'  => 'payment_successful',
                'success' => true
            ],
            'error'             => null,
            'external_trace_id' => 'DUMMY_REQUEST_ID',
            'next'              => [],
            'success'           => true
        ];

        return $response;
    }

    public function netbanking_sib($entities)
    {
        $url = $this->route->getUrlWithPublicAuth(
             'mock_mozart_payment_post',
                       ['gateway' => 'netbanking_sib', 'callbackUrl' => $entities['callbackUrl']]);

        $response = [
            'data'              => [],
            'error'             => null,
            'success'           => true,
            'next'              => [
                            'redirect' => [
                                'method'  => 'post',
                                'url'     => $url,
                                'content' => [
                                    'QS' => 'random_encrypted_string',
                                ],
                            ]
            ],
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'external_trace_id' => 'DUMMY_REQUEST_ID',
        ];

        return $response;
    }

    public function netbanking_ubi($entities)
    {
        $url = $this->route->getUrlWithPublicAuth(
            'mock_mozart_payment_post',
            ['gateway' => 'netbanking_ubi', 'callbackUrl' => $entities['callbackUrl']]);

        $response = [
            'data'              => [],
            'error'             => null,
            'success'           => true,
            'next'              => [
                'redirect' => [
                    'method'  => 'get',
                    'url'     => $url,
                    'content' => [
                        'QS' => 'random_encrypted_string',
                    ],
                ]
            ],
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'external_trace_id' => 'DUMMY_REQUEST_ID',
        ];

        return $response;
    }

    public function netbanking_scb($entities)
    {
        $url = $this->route->getUrlWithPublicAuth(
            'mock_mozart_payment_post',
            ['gateway' => 'netbanking_scb', 'callbackUrl' => $entities['callbackUrl']]);

        $response = [
            'data'              => [],
            'error'             => null,
            'success'           => true,
            'next'              => [
                'redirect' => [
                    'method'  => 'post',
                    'url'     => $url,
                    'content' => [
                        'encrypted_data' => 'random_encrypted_string',
                        'api_key'        => 'random_merchant_id',
                    ],
                ]
            ],
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'external_trace_id' => 'DUMMY_REQUEST_ID',
        ];

        return $response;
    }

    public function netbanking_cbi($entities)
    {
        $url = $this->route->getUrlWithPublicAuth(
            'mock_mozart_payment_post',
            ['gateway' => 'netbanking_cbi', 'callbackUrl' => $entities['callbackUrl']]);

        $response = [
            'data'              => [],
            'error'             => null,
            'success'           => true,
            'next'              => [
                'redirect' => [
                    'method'  => 'post',
                    'url'     => $url,
                    'content' => [
                        'QS' => 'random_encrypted_string',
                    ],
                ]
            ],
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'external_trace_id' => 'DUMMY_REQUEST_ID',
        ];

        return $response;
    }

    public function netbanking_cub($entities)
    {
        $url = $this->route->getUrlWithPublicAuth(
            'mock_mozart_payment_post',
            ['gateway' => 'netbanking_cub', 'callbackUrl' => $entities['callbackUrl']]);

        $response = [
            'data'              => [],
            'error'             => null,
            'success'           => true,
            'next'              => [
                'redirect' => [
                    'method'  => 'post',
                    'url'     => $url,
                    'content' => [
                        'MDATA' => 'random_encrypted_string',
                    ],
                ]
            ],
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'external_trace_id' => 'DUMMY_REQUEST_ID',
        ];

        return $response;
    }

    public function netbanking_ibk($entities)
    {
        $url = $this->route->getUrlWithPublicAuth(
            'mock_mozart_payment_post',
            ['gateway' => 'netbanking_ibk', 'callbackUrl' => $entities['callbackUrl']]);
            $response = [
                'data'              => [],
                'error'             => null,
                'success'           => true,
                'next'              => [
                    'redirect' => [
                        'method'  => 'post',
                        'url'     => $url,
                        'content' => [
                            'encparam'     => 'random_encrypted_string',
                            'merchantcode' => 'Test Account',
                        ],
                        ]
                ],
                'mozart_id'         => 'DUMMY_MOZART_ID',
                'external_trace_id' => 'DUMMY_REQUEST_ID',
            ];

        return $response;
    }

    public function netbanking_idbi($entities)
    {
        $url = $this->route->getUrlWithPublicAuth(
            'mock_mozart_payment_post',
            ['gateway' => 'netbanking_idbi','callbackUrl' => $entities['callbackUrl']]);

        $response = [
            'data'              => [],
            'error'             => null,
            'success'           => true,
            'next'              => [
                'redirect' => [
                    'method'  => 'post',
                    'url'     => $url,
                    'content' => [
                        'encdata' => 'random_encrypted_string',
                    ],
                ]
            ],
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'external_trace_id' => 'DUMMY_REQUEST_ID',
        ];

        return $response;
    }

    public function google_pay($entities)
    {
        $response = [
            'data'              => [],
            'error'             => null,
            'success'           => true,
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'external_trace_id' => 'DUMMY_REQUEST_ID',
        ];

        return $response;
    }

    public function upi_mindgate($entities)
    {
        $response = [
            'data' =>
                [
                    'referenceNumber' => 'IFPO039F3940343',
                    'pgMerchantId' => 'HDFC000006002278',
                    'ref_url' => 'https://mer.invoice.com/upi/3ddsfsdg',
                    'amount' => 200,
                    'custRefNo' => '920515212270',
                    'mandateStatus' => 'COMPLETED',
                    'reqStatus' => 'S',
                    'message' => 'Transaction success',
                    'payerVPA' => 'testvpa@yesb',
                    'payeeVPA' => 'india.uber@hdfcbank',
                    'credAcc' => '01601200021634',
                    'endDate' => '26 Jul 2019',
                    'txnId' => 'HDF542de25ds56ad9896ac96cef89475623',
                    'creditIFSC' => 'HDFC0000160',
                    'mcc' => '4121',
                    'startDate' => '24 Jul 2019',
                    'isVerified' => false,
                    'respCode' => 'MD200',
                    'umn' => 'MER5cb6b2b0640caa3d93d190095c003@hdfcbank',
                    'status' => 'mandate_execution_successful',
                    '_raw' => '',
                    ''
                ],
            'error' => null,
            'success' => true,
            'mozart_id' => '',
            'external_trace_id' => '',
        ];

        return $response;
    }
}
