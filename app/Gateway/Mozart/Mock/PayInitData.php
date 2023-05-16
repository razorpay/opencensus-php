<?php

namespace RZP\Gateway\Mozart\Mock;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Gateway\Upi\Base\Entity as UpiEntity;
use RZP\Gateway\Mozart\Mock\Upi\MozartUpiResponse;

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

    public function cashfree($entities)
    {
        if ($this->isV2Mock($entities['payment']['description']))
        {
            return $this->upiMozartV2($entities);
        }
    }

    public function upi_juspay($entities)
    {
        if ($this->isV2Mock($entities['payment']['description']) === true)
        {
            return $this->upiMozartV2($entities);
        }

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
                    'merchant_reference' => $entities['payment']['id'],
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

    public function upi_yesbank($entities)
    {
        if ($this->isV2Mock($entities['payment']['description']))
        {
            return $this->upiMozartV2($entities);
        }
    }

    public function paytm($entities)
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
            if ($this->isV2Mock($entities['payment']['description']) === true)
            {
                return $this->upiMozartV2($entities);
            }
        }
        if ($method === Payment\Method::WALLET)
        {
            $url = $this->route->getUrlWithPublicAuth(
                'mock_mozart_payment_post',
                ['gateway' => 'payu', 'callbackUrl' => $entities['callbackUrl']]);

            $response = [
                'data' => [],
                'error' => null,
                'success' => true,
                'next' => [
                    'redirect' => [
                        'method' => 'post',
                        'url' => $url,
                        'content' => [
                            'command' => 'initiateTransaction',
                            'access_code' => 'random_access_code',
                            'encode_data' => 'random_encrypted_string',

                        ],
                    ]
                ],
                'mozart_id' => 'DUMMY_MOZART_ID',
                'external_trace_id' => 'DUMMY_REQUEST_ID',
            ];

            return $response;
        }
    }

    public function cred($entities)
    {
        // will optimize it later
        if ((empty($entities['cred']['app_present']) === false) and
            ($entities['cred']['app_present'] === true))
        {
            $response = [
                'data' =>
                    [
                        'trackingID' => $entities['payment']['id'],
                        'gatewayTransactionId' => '123ase!234',
                        'status' => 'CREATED',
                        'checkout_mode' => 'intent',
                        '_raw' => '{"response": {"tracking_id": "","reference_id": "34","state": "CREATED","checkout_mode": "INTENT","intent_url": "<URL>","expiry_time": "<TIME_IN_EPOCH>"},"status": "200","error_code": "","error_message": "","error_description": ""}',
                    ],
                'error' => NULL,
                'external_trace_id' => 'DUMMY_REQUEST_ID',
                'mozart_id' => 'DUMMY_MOZART_ID',
                'next' => [
                    'redirect' => [
                           'method' => 'post',
                           "url" => "cred://pay?am=". $entities['payment']['amount'] . "&cu=INRPAISE&mc=5411"
                       ]
                ],
                'success' => true,
            ];

            return $response;
        }

        if ((empty($entities['cred']['app_present']) === true) or
            ($entities['cred']['app_present'] === false));
        {
            if ($entities['cred']['device'] === 'mobile')
            {
                $response = [
                    'data' =>
                        [
                            'trackingID' => $entities['payment']['id'],
                            'gatewayTransactionId' => '123ase!234',
                            'status' => 'CREATED',
                            'checkout_mode' => 'collect',
                            '_raw' => '{"response": {"tracking_id": "","reference_id": "34","state": "CREATED","checkout_mode": "COLLECT","intent_url": "<URL>","expiry_time": "<TIME_IN_EPOCH>"},"status": "200","error_code": "","error_message": "","error_description": ""}',
                        ],
                    'error' => NULL,
                    'external_trace_id' => 'DUMMY_REQUEST_ID',
                    'mozart_id' => 'DUMMY_MOZART_ID',
                    'next' => [],
                    'success' => true,
                ];
            } else {
                $response = [
                    'data' =>
                        [
                            'trackingID' => $entities['payment']['id'],
                            'gatewayTransactionId' => '123ase!234',
                            'status' => 'CREATED',
                            'checkout_mode' => 'web',
                            '_raw' => '{"response": {"tracking_id": "","reference_id": "34","state": "CREATED","checkout_mode": "WEB","web_url": "<URL>","expiry_time": "<TIME_IN_EPOCH>"},"status": "200","error_code": "","error_message": "","error_description": ""}',
                        ],
                    'error' => NULL,
                    'external_trace_id' => 'DUMMY_REQUEST_ID',
                    'mozart_id' => 'DUMMY_MOZART_ID',
                    'next' => [
                        'redirect' => [
                            'method' => 'post',
                            'content' => [],
                            "url" => "cred://pay?am=". $entities['payment']['amount'] . "&cu=INRPAISE&mc=5411"
                        ]
                    ],
                    'success' => true,
                ];
            }

            return $response;
        }

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

        $url = $this->route->getUrlWithPublicAuth(
            'mock_mozart_payment_post',
            ['gateway' => 'ccavenue', 'callbackUrl' => $entities['callbackUrl']]);

        $response = [
            'data'              => [],
            'error'             => null,
            'success'           => true,
            'next'              => [
                'redirect' => [
                    'method'  => 'post',
                    'url'     => $url,
                    'content' => [
                        'access_code' => 'random_access_code',
                        'command'     =>  'initiateTransaction',
                        'encode_data' => 'random_encrypted_string',

                    ],
                ]
            ],
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'external_trace_id' => 'DUMMY_REQUEST_ID',
        ];

        return $response;
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
                    'upi'    => [
                        'npci_reference_id' => '987654321',
                        'vpa'               => $entities['payment']['vpa'],
                    ]
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

    public function upi_sbi($entities)
    {
        if ($entities['upi']['flow'] === 'intent')
        {
            $remark = str_replace(" ", "", $entities['upi']['remark']);

            $intentUrl = sprintf("upi://pay?am=100.00&cu=INR&mc=%s&pa=some@sbi&pn=merchantname&tn=%s&tr=pay_someid",
                                 $entities['merchant']['category'], $remark);

            $response = [
                'data' => [],
                'error' => null,
                'success' => true,
                'mozart_id' => 'DUMMY_MOZART_ID',
                'external_trace_id' => 'DUMMY_REQUEST_ID',
                'next' => [
                    'redirect' => [
                        'method' => 'post',
                        "url" => $intentUrl
                    ],
                ],
            ];

            return $response;
        }

        $vpa = $entities['payment']['vpa'];

        $response = [
            'data' =>
                [
                    'gateway_response'=>
                        [
                            'status' => 'S',
                            'pspRefNo' => $entities['payment']['id'],
                            'txnAuthDate' => Carbon::now(Timezone::IST)->toDateTimeString(),
                            'payerVPA' => $vpa,
                            'amount' => $entities['payment']['amount'],
                            'payeeVPA' => 'razorpay@sbi',
                            'statusDesc' => 'Transaction Pending waiting for response',
                            'upiTransRefNo' => '12345678901',
                            'npciTransId'   => '99999999',
                            'custRefNo'     => "123456789012",
                        ],
                ],
            'error'             => null,
            'success'           => true,
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'external_trace_id' => 'DUMMY_REQUEST_ID',
        ];

        if (($vpa === 'failedcollect@sbi') or ($vpa === 'blockverify@sbi'))
        {
            $response['data']['gateway_response']['status'] = 'F';
            $response['data']['gateway_response']['statusDesc'] = 'Payment failed';
            $response['success'] = false;
            $response['error'] = [
                'description' => '',
                'internal_error_code' => 'BAD_REQUEST_PAYMENT_FAILED'
            ];
        }
        else if ($vpa === 'cbsdown@sbi')
        {
            $response['data']['gateway_response']['status'] = 'T';
            $response['data']['gateway_response']['statusDesc'] = 'CBS transaction processing timed out';
            $response['success'] = false;
            $response['error']['internal_error_code'] = 'BAD_REQUEST_PAYMENT_TIMED_OUT';
        }
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

    public function netbanking_jsb($entities)
    {
        $url = $this->route->getUrlWithPublicAuth(
            'mock_mozart_payment_post',
            ['gateway' => 'netbanking_jsb','callbackUrl' => $entities['callbackUrl']]);

        $response = [
            'data'              => [],
            'error'             => null,
            'success'           => true,
            'next'              => [
                'redirect' => [
                    'method'  => 'post',
                    'url'     => $url,
                    'content' => [
                        'qs' => 'random_encrypted_string',
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
                    'status_code'           => '0',
                    'errCode'               => 'MD200',                               // UPI Switch/NPCI Error code
                    'npci_txn_id'           => 'HDF542de25ds56ad9896ac96cef89475623', // txnId
                    'npci_reference_id'     => '011300040570',                        // custRefNo
                    'gateway_payment_id'    => 'GatewayPaymentIdDebit',
                    'vpa'                   => $entities['payment']['vpa'] ?? 'some@hdfcbank',           // payerVPA
                    'mandateStatus'         => 'PENDING',
                    'reqStatus'             => 'S',
                    'message'               => 'Mandate Request Initiated to NPCI',
                    'payerVPA'              => 'testvpa@yesb',
                    'payeeVPA'              => 'india.uber@hdfcbank',
                    'credAcc'               => '01601200021634',
                    'endDate'               => '26 Jul 2019',
                    'txnId'                 => 'HDF542de25ds56ad9896ac96cef89475623',
                    'creditIFSC'            => 'HDFC0000160',
                    'mcc'                   => '4121',
                    'startDate'             => '24 Jul 2019',
                    '_raw' => '{"status": "S","statusDesc": "Mandate request initiated successfully","errCode": "MD200","mandateDtls": [{"payeeName": "Razorpay","errCode": "MD200","mcc": "6012","remRecuCount": 0,"payType": "P2M","show_QR": "N","message": "Mandate Request Initiated to NPCI","is_verified": true,"requestDate": "22 Apr 2020 12:56 AM","txnId": "HDFA3D2FF1416365154E0535DB2E20A9668","remarks": "Mandate Create","startDate": "22 Apr 2020","nextRecurDate": "Apr 22, 2020","endDate": "22 Apr 2020","ref_url": "https://mer.invoice.com/upi/3ddsfsdg","amount": "1.52","purpose_code": "00","custRefNo": "011300040570","referenceNumber": "CyAtSQ3u000203","payerName": "Customer","create_date_time": "22 Apr 2020 12:56 AM","initiatedBy": "PAYEE","frequency": "ONETIME","payerVPA": "jahangirali@hdfcbank","creditIfsc": "HDFC0004272","onBehalf_Of": "PAYEE","amt_rule": "MAX","name": "Mandate","status": "PENDING","crediAccount": "50100100670996","noOfDebit": 1,"mandateType": "CREATE","isRevokeable": "N","payeeVPA": "razorpay01@hdfcbank","blockFund": "Y"}],"requestInfo": {"pgMerchantId": "HDFC000000000054","pspRefNo": "CyAtSQ3u000203"}}',
                ],
            'error' => null,
            'success' => true,
            'mozart_id' => '',
            'external_trace_id' => '',
        ];

        switch ($entities['payment']['description'])
        {
            case 'mandateCreateFailed':
                $response['success'] = false;
                $response['error'] = [
                   'gateway_error_code' => 'QN',
                   'gateway_error_description' => 'DUPLICATE MANDATE REQUEST',
                   'internal_error_code' => 'GATEWAY_ERROR_PAYMENT_DUPLICATE_REQUEST'
                ];
                break;
        }

        return $response;
    }

    public function upi_icici($entities)
    {
        $response = [
            'data' =>
                [
                    'status_code'           => '0',
                    'errCode'               => 'MD200',                               // UPI Switch/NPCI Error code
                    'npci_txn_id'           => 'HDF542de25ds56ad9896ac96cef89475623', // txnId
                    'npci_reference_id'     => '011300040570',                        // custRefNo
                    'gateway_payment_id'    => 'GatewayPaymentIdDebit',
                    'mandateStatus'         => 'PENDING',
                    'reqStatus'             => 'S',
                    'message'               => 'Mandate Request Initiated to NPCI',
                    'payerVPA'              => 'testvpa@yesb',
                    'payeeVPA'              => 'india.uber@hdfcbank',
                    'credAcc'               => '01601200021634',
                    'endDate'               => '26 Jul 2019',
                    'txnId'                 => 'HDF542de25ds56ad9896ac96cef89475623',
                    'creditIFSC'            => 'HDFC0000160',
                    'mcc'                   => '4121',
                    'startDate'             => '24 Jul 2019',
                    '_raw' => '{"status": "S","statusDesc": "Mandate request initiated successfully","errCode": "MD200","mandateDtls": [{"payeeName": "Razorpay","errCode": "MD200","mcc": "6012","remRecuCount": 0,"payType": "P2M","show_QR": "N","message": "Mandate Request Initiated to NPCI","is_verified": true,"requestDate": "22 Apr 2020 12:56 AM","txnId": "HDFA3D2FF1416365154E0535DB2E20A9668","remarks": "Mandate Create","startDate": "22 Apr 2020","nextRecurDate": "Apr 22, 2020","endDate": "22 Apr 2020","ref_url": "https://mer.invoice.com/upi/3ddsfsdg","amount": "1.52","purpose_code": "00","custRefNo": "011300040570","referenceNumber": "CyAtSQ3u000203","payerName": "Customer","create_date_time": "22 Apr 2020 12:56 AM","initiatedBy": "PAYEE","frequency": "ONETIME","payerVPA": "jahangirali@hdfcbank","creditIfsc": "HDFC0004272","onBehalf_Of": "PAYEE","amt_rule": "MAX","name": "Mandate","status": "PENDING","crediAccount": "50100100670996","noOfDebit": 1,"mandateType": "CREATE","isRevokeable": "N","payeeVPA": "razorpay01@hdfcbank","blockFund": "Y"}],"requestInfo": {"pgMerchantId": "HDFC000000000054","pspRefNo": "CyAtSQ3u000203"}}',
                ],
            'error' => null,
            'success' => true,
            'mozart_id' => '',
            'external_trace_id' => '',
        ];

        switch ($entities['payment']['description'])
        {
            case 'mandateCreateFailed':
                $response['success'] = false;
                $response['error'] = [
                    'gateway_error_code' => 'QN',
                    'gateway_error_description' => 'DUPLICATE MANDATE REQUEST',
                    'internal_error_code' => 'GATEWAY_ERROR_PAYMENT_DUPLICATE_REQUEST'
                ];
                break;
        }

        return $response;
    }

    protected function upiMozartV2($entities)
    {
        $response = MozartUpiResponse::getDefaultInstanceForV2();

        $case = str_replace('_v2', '', $entities['payment']['description']);

        $response->mergeUpi([
           UpiEntity::MERCHANT_REFERENCE   =>  $entities['payment']['id']
        ]);

        if(!empty($entities['payment']['vpa']))
        {
            $response->mergeUpi([
                'vpa' => $entities['payment']['vpa'],
            ]);
        }

        $response->setSuccess(true);

        if ($entities['upi']['flow'] === 'intent')
        {
            $intent_url   = "upi://pay?am=%d&cu=INR&mc=5411&pa=%s&pn=%s&tn=PayViaRazorpay&tr=%s";
            $merchantName = str_replace(' ', '', $entities['merchant']['name']);
            $amount       = substr_replace(strval($entities['payment']['amount']), '.', '-2', 0);
            $vpa          = $entities['terminal']['vpa'];


            $intent_url = sprintf($intent_url, $amount, $vpa, $merchantName, $entities['payment']['id']);


            $response->setNext([
               'intent_url' => $intent_url
            ]);
        }
        else {
            $response->setNext([
               'vpa' => $entities['terminal']['vpa']
            ]);
        }

        switch ($case)
        {
            case 'collect_request_failed':
            case 'intent_request_failed':
            case 'late_authorized':
            case 'verify_amount_mismatch':
                $response->setSuccess(false);
                $response->setError([
                   'internal_error_code'    => ErrorCode::GATEWAY_ERROR_PAYMENT_CREATION_FAILED,
                   'gateway_error_code'     => '01',
                   'gateway_error_desc'     => 'Payment failed at gateway'
                ]);
                return $response->toArray();
            case 'collect_request_pending':
                $response->setSuccess(false);
                $response->setError([
                   'internal_error_code'    => ErrorCode::GATEWAY_ERROR_TRANSACTION_PENDING,
                   'gateway_error_code'     => 'E002',
                   'gateway_error_desc'     => 'Transaction Pending'
                ]);
                return $response->toArray();

        }

        return $response->toArray();
    }

    public function billdesk_sihub($entities)
    {
        return [
            'success' => true,
            'error'   => null,
            'data'              => [
                'amount'    => 6,
                'currency'  => 356,
                'id'        => '18TYP7OD7XDM',
                'status'    => 'notified',
                'delivered_at' => Carbon::now()->timestamp,
            ],
        ];
    }

    public function pinelabs($entities)
    {
        $method = $entities['payment']['method'];

        if ($method === Payment\Method::UPI)
        {
            if ($this->isV2Mock($entities['payment']['description']) === true)
            {
                return $this->upiMozartV2($entities);
            }
        }
    }
}
