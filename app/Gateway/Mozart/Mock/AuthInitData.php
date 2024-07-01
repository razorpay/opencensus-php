<?php

namespace RZP\Gateway\Mozart\Mock;

use RZP\Gateway\Base;

class AuthInitData extends Base\Mock\Server
{
    use Base\Mock\GatewayTrait;

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

    public function upi_mindgate($entities)
    {
        assertTrue(isset($entities['upi']['remark']));
        assertTrue($entities['upi']['remark'] === 'Test Merchant random description');

        if ($entities['upi']['flow'] === 'intent')
        {
            $response = [
                'data' =>  [
                    '_raw' =>  '[\'upi\' => [\'gateway_data\' => [\'id\' => \'I08Uh70fcsENn60create1\']],\'status_desc\' => \'Transaction initiated successfully.\',\'\' => \'NA\']',
                    'intent_url' =>  'upi => //mandate?am=2500&amrule=MAX&block=N&cu=INR&mc=5399&mode=13&orgid=&pa=razorpaypg@hdfcbank&pn=TestMerchant&purpose=14&recur=AS_PRESENTED&rev=Y&sign=&tid=I08Uh70fcsENn60create1&tn=TestMerchant&tr=I08Uh70fcsENn60create1&txnType=CREATE&validityend=08072020&validitystart=27062020',
                    'mandate' =>  [
                        'gateway_data' =>  [
                            'id' =>  'I08Uh70fcsENn60create1'
                        ]
                    ],
                    'meta' =>  [
                        'request' =>  [
                            'plain' =>  '435202|I08Uh70fcsENn60create1|5399|P2M|CREATE|test|razorpaypg@hdfcbank|TestMerchant|2500|NA|NA|NA|NA|NA|NA|NA|NA|NA'
                        ],
                        'response' =>  [
                            'content' =>  '[\n    \'pgMerchantId\' =>  \'HDFC000000000054\',\n    \'payload\' =>  \'08c8fda6be3a90976f8be49cb8812fed2b957ee2a7f16db9e91a9770fcd5dd4d8491fc3de0add1718e8e11545dc56190f36a4b8c78475f5288aef362a5bf2885c57833fb3ad6a17c2297680c07d687ce38c20fcffff73e52180760c646f04a58ba801f335c2bbfc5f39f6c7743f4e83a6e2987f6e11970ee5b3b05a031ecc91c\'\n]',
                            'plain' =>  [
                                '' =>  'NA',
                                'status' =>  'SUCCESS',
                                'status_desc' =>  'Transaction initiated successfully.',
                                'upi' =>  [
                                    'gateway_data' =>  [
                                        'id' =>  'I08Uh70fcsENn60create1'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'payment' =>  [
                        'currency' =>  'INR'
                    ],
                    'status' =>  'SUCCESS',
                    'status_code' =>  'SUCCESS',
                    'status_desc' =>  'Transaction initiated successfully.',
                    'terminal' =>  [
                        'gateway' =>  'upi_mindgate',
                        'gateway_merchant_id' =>  '435202'
                    ],
                    'upi' =>  [
                        'gateway_data' =>  [
                            'id' =>  'I08Uh70fcsENn60create1'
                        ],
                        'status_code' =>  null
                    ],
                    'version' =>  'v2'
                ],
                'error' =>  null,
                'external_trace_id' =>  'DUMMY_REQUEST_ID',
                'success' =>  true
            ];

            return $response;
        }

        $response = [
            'data' => [
                'status' => 'S',
                '_raw' => '[\'requestInfo\' => [\'pgMerchantId\' => \'MYBANK000000002927\',\'pspRefNo\' => \'MYBANK3PL564Z48DD796K4RUL7AIK3UGYK617\'],\'statusDesc\' => \'Mandate request initiated successfully\',\'errCode\' => \'MD200\',\'mandateDtls\' => [\'0\' => [\'custRefNo\' => \'023722057186\',\'requestDate\' => \'24 Aug 2020 10 => 55 PM\',\'referenceNumber\' => \'MYBANKEEY6W7LIA24RF6O2S59HUF0RPJFPWXJ\',\'txnId\' => \'MYBANKH48S0PSUK7S4UDAUKRKYE3GTY31AIR2\',\'remarks\' => \'CREATE Mandate test\',\'name\' => \'CREATE Mandate test\',\'mandateType\' => \'CREATE\',\'frequency\' => \'MONTHLY\',\'amount\' => \'78.00\',\'startDate\' => \'24 Aug 2020\',\'endDate\' => \'24 Aug 2021\',\'isRevokeable\' => \'Y\',\'payerVPA\' => \'samreen@mybank\',\'payerName\' => \'sam1996\',\'payeeVPA\' => \'sonysuper@mybank\',\'payeeName\' => \'Sony Super\',\'status\' => \'PENDING\',\'creditIfsc\' => \'MYBANK0002201\',\'crediAccount\' => \'00993564615950\',\'noOfDebit\' => \'13\',\'onBehalf_Of\' => \'PAYEE\',\'amt_rule\' => \'EXACT\',\'ruleType\' => \'ON\',\'ruleValue\' => \'24\',\'create_date_time\' => \'24 Aug 2020 10 => 55 PM\',\'ref_url\' => \'https => //www.mybank.co.in\',\'errCode\' => \'MD200\',\'payType\' => \'P2M\',\'show_QR\' => \'N\',\'purpose_code\' => \'14\',\'expire_time\' => \'100\',\'mcc\' => \'6211\',\'expiry_date_time\' => \'25 Aug 2020 12 => 35 AM\',\'message\' => \'MD200\',\'is_verified\' => \'true\',\'blockFund\' => \'N\',\'initiatedBy\' => \'PAYEE\',\'nextRecurDate\' => \'Aug 24, 2020\',\'remRecuCount\' => \'13\',\'prdMobile\' => \'919930465134\']]]',
                'errCode' => 'MD200',
                'mandate' => [
                    'gateway_data' => [
                        'id' => 'MYBANK3PL564Z48DD796K4RUL7AIK3UGYK617'
                    ],
                    'rrn' => '023722057186'
                ],
                'payment' =>  [
                    'currency' =>  'INR'
                ],
                'status_code' =>  'MD200',
                'status_desc' =>  'Mandate request initiated successfully',
                'terminal' =>  [
                    'gateway' =>  'upi_mindgate',
                    'gateway_merchant_id' =>  'MYBANK000000002927'
                ],
                'upi' =>  [
                    'gateway_data' =>  [
                        'id' =>  'MYBANK3PL564Z48DD796K4RUL7AIK3UGYK617'
                    ],
                    'gateway_payment_id' =>  '023722057186',
                    'merchant_reference' =>  'MYBANK3PL564Z48DD796K4RUL7AIK3UGYK617',
                    'npci_reference_id' =>  '023722057186',
                    'npci_txn_id' =>  'MYBANKH48S0PSUK7S4UDAUKRKYE3GTY31AIR2',
                    'status_code' =>  'MD200'
                ],
                'version' =>  'v2'
            ],
            'error' =>  null,
            'external_trace_id' =>  'DUMMY_REQUEST_ID',
            'next' =>  [],
            'success' =>  true
        ];

        return $response;
    }


    public function upi_icici($entities)
    {
        assertTrue(isset($entities['upi']['remark']));
        assertTrue($entities['upi']['remark'] === 'Test Merchant random description');

        if ($entities['upi']['flow'] === 'intent')
        {
            $response = [
                'data' => [
                    'terminal' => [
                        'gateway' => 'upi_icici',
                    ],
                    'payment' => [
                        'currency' => 'INR'
                    ],
                    'mandate' => [],
                    'upi' => [
                        'status_code' => '0'
                    ],
                    'version' => 'v2',
                    'intent_url' => 'upi://mandate?pa=invaciauat@icici&pn=Invacia Labs&tr=EZM2021082712290200025685&am=1.00&cu=INR&orgid=400011&mc=5411&purpose=14&tn=Mandate787Request&validitystart=20092021&validityend=27082022&amrule=MAX&Recur=ASPRESENTED&Recurvalue=&Recurtype=&Rev=Y&Share=Y&Block=N&umn=null&txnType=CREATE&mode=11'
                ],
                'error' => null,
                'success' => true,
                'mozart_id' => 'DUMMY_MOZART_ID',
                'external_trace_id' => 'DUMMY_REQUEST_ID',
                'next' => [],
            ];

            return $response;
        }

        $response = [
            'data' =>
                [
                    'terminal'  => [
                        'vpa'   => 'merchant@icici',
                    ],
                    'referenceNumber' => 'IFPO039F3940343',
                    'pgMerchantId' => 'HDFC000006002278',
                    'ref_url' => 'https://mer.invoice.com/upi/3ddsfsdg',
                    'amount' => 200,
                    'custRefNo' => '920515212270',
                    'mandateStatus' => 'PENDING',
                    'reqStatus' => 'S',
                    'message' => 'Mandate Request Initiated to NPCI',
                    'payerVPA' => 'testvpa@yesb',
                    'credAcc' => '01601200021634',
                    'endDate' => '26 Jul 2019',
                    'txnId' => 'HDF542de25ds56ad9896ac96cef89475623',
                    'creditIFSC' => 'HDFC0000160',
                    'mcc' => '4121',
                    'startDate' => '24 Jul 2019',
                    'isVerified' => false,
                    'errorCode' => 'MD200',
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

    public function paylater_icici($entities)
    {
        $response = [
            'data' =>
                [
                    'ResponseCode'          => '000',
                    'MobileNumber'          => '93884739457',
                    'AppName'               => 'MerchantName',
                    'TransactionIdentifier' => '3479278',
                    '_raw'                  => '',
                ],
            'error'             => null,
            'success'           => true,
            'mozart_id'         => '',
            'external_trace_id' => '',
        ];

        return $response;
    }

    public function upi_axis($entities)
    {
        assertTrue(isset($entities['upi']['remark']));
        assertTrue($entities['upi']['remark'] === 'Test Merchant random description');

        if ($entities['upi']['flow'] === 'intent') {
            [
                $response = [
                    'data' => [
                        '_raw' => '',
                        'status_code' => '00',
                        'intent_url' => 'upi://mandate?am=22.00&amrule=MAX&block=N&cu=INR&mc=5411&mode=04&orgid=000000&pn=Razorpay&purpose=14&recur=MONTHLY&recurtype=ON&recurvalue=27&rev=Y&tr=CyttkcQ3u000269&txnType=CREATE&validityend=08072020&validitystart=27062020',
                        'errCode' => '00',
                        'status' => '00',
                        'version' => 'v2',
                        'terminal' => [
                            'gateway' => 'upi_axis',
                            'gateway_merchant_id' => 'MER1234'
                        ],
                        'upi' => [
                            'status_code' => '00',
                            'gateway_data' => [
                                'id' => 'CyttkcQ3u000269'
                            ]
                        ],
                        'status_desc' => 'Success',
                        'mandate' => [
                            'gateway_data' => [
                                'id' => 'CyttkcQ3u000269'
                            ]
                        ],
                        'payment' => [
                            'currency' => 'INR'
                        ]
                    ],
                    'error' => null,
                    'external_trace_id' => '',
                    'next' => [],
                    'success' => true
                ]
            ];

            return $response;
        }

        $response = [
            'data' => [
                '_raw' => '',
                'status_code' => '00',
                'errCode' => '00',
                'status' => '00',
                'version' => 'v2',
                'terminal' => [
                    'gateway' => 'upi_axis',
                    'gateway_merchant_id' => 'RAZORPAY10040616'
                ],
                'upi' => [
                    'status_code' => '00',
                    'gateway_data' => [
                        'id' => 'NrSPyEOccuftKZ0create1'
                    ],
                    'gateway_payment_id' => '408724842701',
                    'merchant_reference' => 'NrSPyEOccuftKZ0create1',
                    'npci_reference_id' => '408724842701'
                ],
                'status_desc' => 'Success',
                'mandate' => [
                    'gateway_data' => [
                        'id' => 'NrSPyEOccuftKZ0create1'
                    ],
                    'rrn' => '408724842701'
                ],
                'payment' => [
                    'currency' => 'INR'
                ]
            ],
            'error' => null,
            'external_trace_id' => '',
            'next' => [],
            'success' => true
        ];

        return $response;
    }
}
