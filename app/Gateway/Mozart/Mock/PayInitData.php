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
