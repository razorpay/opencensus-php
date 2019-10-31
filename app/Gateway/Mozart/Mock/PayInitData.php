<?php

namespace RZP\Gateway\Mozart\Mock;

use RZP\Gateway\Base;

class PayInitData extends Base\Mock\Server
{
    use Base\Mock\GatewayTrait;

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

    public function wallet_paypal($entities)
    {
        $url = $this->route->getUrlWithPublicAuth(
            'mock_mozart_payment_post',
            ['gateway' => 'wallet_paypal', 'callbackUrl' => $entities['callbackUrl']]);

        $response = [
            'data' => [
                    "OrderId" => "5YG0152953511483M",
                    "_raw" => "{\"body\":\"{\\\"id\\\":\\\"5YG0152953511483M\\\",\\\"links\\\":[{\\\"href\\\":\\\"https://api.sandbox.paypal.com/v2/checkout/orders/5YG0152953511483M\\\",\\\"rel\\\":\\\"self\\\",\\\"method\\\":\\\"GET\\\"},{\\\"href\\\":\\\"https://www.sandbox.paypal.com/checkoutnow?token=5YG0152953511483M\\\",\\\"rel\\\":\\\"approve\\\",\\\"method\\\":\\\"GET\\\"},{\\\"href\\\":\\\"https://api.sandbox.paypal.com/v2/checkout/orders/5YG0152953511483M\\\",\\\"rel\\\":\\\"update\\\",\\\"method\\\":\\\"PATCH\\\"},{\\\"href\\\":\\\"https://api.sandbox.paypal.com/v2/checkout/orders/5YG0152953511483M/capture\\\",\\\"rel\\\":\\\"capture\\\",\\\"method\\\":\\\"POST\\\"}],\\\"status\\\":\\\"CREATED\\\"}\",\"header\":{\"Date\":[\"Tue, 30 Jul 2019 11:11:44 GMT\"],\"Content-Length\":[\"501\"],\"Http_x_pp_az_locator\":[\"sandbox.slc\"],\"Set-Cookie\":[\"X-PP-SILOVER=name%3DSANDBOX3.API.1%26silo_version%3D1880%26app%3Dapiplatformproxyserv%26TIME%3D4028973149%26HTTP_X_PP_AZ_LOCATOR%3Dsandbox.slc; Expires=Tue, 30 Jul 2019 11:41:45 GMT; domain=.paypal.com; path=/; Secure; HttpOnly\",\"X-PP-SILOVER=; Expires=Thu, 01 Jan 1970 00:00:01 GMT\"],\"Vary\":[\"Authorization\"],\"Content-Type\":[\"application/json\"],\"Server\":[\"Apache\"],\"Paypal-Debug-Id\":[\"5ab1b731c02a6\",\"5ab1b731c02a6\"]},\"status\":201}",
                    "ActionLink" => [
                        [
                            "href" => "https://api.sandbox.paypal.com/v2/checkout/orders/8WA29343W72537449",
                            "method" =>  "GET",
                            "rel" =>  "self",
                        ],
                        [
                            "href" =>  "https://www.sandbox.paypal.com/checkoutnow?token=8WA29343W72537449",
                            "method" =>  "GET",
                            "rel" =>  "approve",
                        ],
                        [
                            "href" =>  "https://api.sandbox.paypal.com/v2/checkout/orders/8WA29343W72537449",
                            "method" =>  "PATCH",
                            "rel" =>  "update",
                        ],
                        [
                            "href" =>  "https://api.sandbox.paypal.com/v2/checkout/orders/8WA29343W72537449/capture",
                            "method" =>  "POST",
                            "rel" =>  "capture",
                        ]
                    ],
                    'status' => 'authorization_successful',
                ],
            'error'             => null,
            'success'           => true,
            "next" => [
                "redirect" => [
                    "method" => "get",
                    "url" => $url,
                        ]
                    ],
            'mozart_id'         => 'DUMMY_MOZART_ID',
            'external_trace_id' => 'DUMMY_REQUEST_ID',
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
}
