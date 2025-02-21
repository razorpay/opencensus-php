<?php

namespace RZP\Tests\Functional\Helpers\QrCode;

use DB;
use Mail;
use Queue;
use Mockery;
use RZP\Services\Mock;
use RZP\Error\ErrorCode;
use RZP\Models\QrCode\Type;
use RZP\Services\RazorXClient;
use RZP\Services\SplitzService;
use RZP\Models\Payment\Gateway;
use RZP\Services\Mock\Reminders;
use RZP\Exception\ServerErrorException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Entity;
use RZP\Models\Order\Entity as OrderEntity;


trait NonVirtualAccountQrCodeTrait
{
    private function createQrCode(array $input = [], $mode = 'test', $merchantId = '10000000000000', array $headers = [])
    {
        $this->ba->privateAuth();

        if ($mode === 'live')
        {
            $this->ba->privateAuth('rzp_live_' . $merchantId);
        }

        $defaultValues = $this->getDefaultQrCodeRequestArray();

        $attributes = array_merge($defaultValues, $input);

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/qr_codes',
            'content' => $attributes,
            'headers' => $headers,
        ];

        return $this->makeRequestAndGetContent($request);
    }

    private function processRefund($id, $mode = 'test', $merchantId = '10000000000000')
    {
        $this->ba->privateAuth();

        if ($mode === 'live')
        {
            $this->ba->privateAuth('rzp_live_' . $merchantId);
        }

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/'. $id . '/refund',
        ];

        return $this->makeRequestAndGetContent($request);
    }

    public function createQrForSingleStack(array $input = [], array $headers = [])
    {
        $this->ba->appAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/terminal/qr_codes/device/create',
            'content' => $input,
            'headers' => $headers,
        ];

        return $this->makeRequestAndGetContent($request);
    }

    private function createMerchantQrCode(array $input = [], array $headers = [])
    {
        $this->ba->appAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/merchant/qr_codes',
            'content' => $input,
            'headers' => $headers,
        ];

        return $this->makeRequestAndGetContent($request);
    }

    private function updateDeviceIdForQrCode(array $input = [], array $headers = [])
    {
        $this->ba->appAuth();

        $request = [
            'method'  => 'PUT',
            'url'     => '/payments/qr_codes/device/map',
            'content' => $input,
            'headers' => $headers,
        ];

        return $this->makeRequestAndGetContent($request);
    }


    private function unMapDeviceIdforQrCode(array $input = [], array $headers = [])
    {
        $this->ba->appAuth();

        $request = [
            'method' => 'PUT',
            'url' => '/payments/qr_codes/device/unmap',
            'content' => $input,
            'headers' => $headers,
        ];

        return $this->makeRequestAndGetContent($request);
    }



    private function downloadQrCode(string $id, $mode = 'test', $merchantId = '10000000000000')
    {
        if ($mode === 'live')
        {
            $request = [
                'method'  => 'GET',
                'url'     => '/l/qrcode/' . $id,
            ];
        }
        else
        {
            $request = [
                'method'  => 'GET',
                'url'     => '/t/qrcode/' . $id,
            ];
        }

        $this->ba->directAuth();

        return $this->sendRequest($request);
    }

    private function closeQrCode(string $id, $mode = 'test', $merchantId = '10000000000000')
    {
        $request = [
            'method'  => 'POST',
            'url'     => '/payments/qr_codes/'.$id.'/close',
        ];

        $this->ba->privateAuth();

        if ($mode === 'live')
        {
            $this->ba->privateAuth('rzp_live_' . $merchantId);
        }

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function getDefaultQrCodeRequestArray()
    {
        return [
            'name'         => 'Test QR Code',
            'description'  => 'QR code for tests',
            'usage'        => 'multiple_use',
            'type'         => 'bharat_qr',
            'fixed_amount' => '0',
            'notes'        => [
                'a' => 'b',
            ],
        ];
    }

    private function fetchQrPayment(string $id = null)
    {
        $url = '';
        if ($id === null)
        {
            $url = '/payments/qr_payments';

        }
        else
        {
            $url = '/payments/qr_codes/' . $id . '/payments';
        }
        $request = [
            'method' => 'GET',
            'url'    => $url,
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function fetchQrCode(string $id = null, $input = [])
    {
        if ($id === null)
        {
            $url = '/payments/qr_codes';
        }
        else
        {
            $url = '/payments/qr_codes/' . $id;
        }

        $request = [
            'method'  => 'GET',
            'url'     => $url,
            'content' => $input
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function makeUpiIciciPayment($request, $expected = 'true')
    {
        $this->ba->directAuth();

        $content = $this->getMockServer('upi_icici')->getAsyncCallbackContentForBharatQr($request['content']);

        $request['raw'] = $content;

        $response = $this->makeRequestAndGetContent($request);

        if (isset($response['success']) === true)
        {
            $this->assertEquals($expected, $response['success']);
        }
        else
        {
            $xmlResponse = $response['original'];

            $response = $this->parseResponseXml($xmlResponse);

            $this->assertEquals('OK', $response[0]);
        }

        return $response;
    }

    private function makeIciciQrPaymentViaUpiTransferRoute($request)
    {
        $this->ba->directAuth();

        $content = $this->getMockServer('upi_icici')->getAsyncCallbackContentForBharatQr($request['content']);

        $request['raw'] = $content;

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(true, $response['valid']);

        return $response;
    }

    private function makeUpiIciciPaymentInternal($request)
    {
        $this->ba->appAuth();

        return $this->makeRequestAndGetContent($request);
    }

    public function parseResponseXml(string $response): array
    {
        return (array) simplexml_load_string(trim($response));
    }

    public function ecollectValidateVpa($gateway, $vpaPrefix, $input)
    {
        $url = '/test/ecollect/validate/' . $gateway . '/' . $vpaPrefix;

        $request = [
            'method'  => 'POST',
            'url'     => $url,
            'raw'     => $input
        ];

        $response = $this->makeRequestAndGetRawContent($request);

        return $response;
    }

    private function createQrCodeForCheckout($order = null, $amount = null)
    {
        $this->ba->publicAuth();

        if ($order !== null)
        {
            $input[Entity::ENTITY_TYPE] = 'order';
            $input[Entity::ENTITY_ID] = $order->getPublicId();
        }
        else
        {
            $input[Entity::REQ_AMOUNT] = $amount;
        }

        $request = [
            'method'  => 'POST',
            'url'     => '/checkout/qr_codes',
            'content' => $input,
        ];

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }


    private function hitUpiIciciCallbackForQRv2($qrCode, $order, $changes = []) {
        $qrCodeId = $qrCode['id'];
        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData['testProcessIciciQrPayment'];
        $rrn = '000011100101';
        $request['content']['BankRRN'] = $rrn;
        $request['content']['merchantTranId'] = $qrCodeId . 'qrv2';
        $request['content']['PayerAmount'] = $order->getAmount() / 100;

        if (!empty($changes['PayerAmount'])) {
            $request['content']['PayerAmount'] = $changes['PayerAmount'];
        }

        $this->makeUpiIciciPayment($request);
    }

    private function fetchAndAssertQrEntity() {
        $qrCode = $this->getDbLastEntity('qr_code');
        $qrCodeId = $qrCode['id'];
        $this->assertNotNull($qrCodeId);
        return $qrCode;
    }

    private function createQrCodeForPaymentLinksOrder($order)
    {
        $this->ba->paymentLinksAuth();

        $input[Entity::ENTITY_TYPE] = 'order';
        $input[Entity::ENTITY_ID] = $order->getPublicId();
        $input[Entity::DESCRIPTION] = empty($description) ? 'default razorpay transaction note' : $description;
        $input[Entity::CLOSE_BY] = empty($closeby)? strtotime('+60 minutes', time()) : $closeby;

        $request = [
            'method'  => 'POST',
            'url'     => '/payment_links/qr_codes',
            'content' => $input,
        ];

        $response = $this->makeRequestAndGetContent($request);

        return [$input, $response];
    }

    private function fetchPaymentByQrCodeIdOnCheckout(string $id)
    {
        $this->ba->publicAuth();

        $url = '/checkout/qr_code/' . $id . '/payment';

        $request = [
            'method'  => 'GET',
            'url'     => $url,
        ];

        return $this->makeRequestAndGetContent($request);
    }

    private function makeUpiYesBankPayment($qrCodeEntity, $payment = [], $upiEntity = [])
    {
        $this->ba->directAuth();

        $defaultPaymentData = [
            'amount'      => '300',
            'description' => '',
            'vpa'         => 'abcba@yesbank',
        ];

        $payment = array_merge($defaultPaymentData, $payment);

        $defaultUPIData = [
            'gateway_payment_id' => '13570',
            'vpa'                => 'testvpa@yesb',
            'merchant_reference' => 'RZPY' . $qrCodeEntity['reference'] . 'qrv2',
        ];

        $upiEntity = array_merge($defaultUPIData, $upiEntity);

        $request = $this->getMockServer('upi_yesbank')->getCallback($upiEntity, $payment);

        $this->makeRequestAndGetContent($request);
    }

    private function makeUpiYesBankPayment60($qrCodeEntity, $payment = [], $upiEntity = [])
    {
        $this->ba->directAuth();

        $defaultPaymentData = [
            'amount'      => '300',
            'description' => '',
            'vpa'         => 'abcba@yesbank',
        ];

        $payment = array_merge($defaultPaymentData, $payment);

        $defaultUPIData = [
            'gateway_payment_id' => '13570',
            'vpa'                => 'testvpa@yesb',
            'merchant_reference' => 'RZPY' . $qrCodeEntity['reference'] . 'qrv2',
        ];

        $upiEntity = array_merge($defaultUPIData, $upiEntity);

        $request = $this->getMockServer('upi_yesbank')->getCallback60($upiEntity, $payment);

        $this->makeRequestAndGetContent($request);
    }

    private function makeUpiRzpapbPayment($qrCodeEntity)
    {
        $this->ba->directAuth();

        $request = [
            'url' => '/callback/upi_rzpapb',
            'method' => 'POST',
            'raw' => json_encode(
                [
                    'id' => str_after($qrCodeEntity['id'], 'qr_') . 'qrv2',
                    'amount' => 100,
                    'description' => 'create_collect_success',
                    'gateway' => 'upi_rzpapb',
                    'terminal_id' => 'RzpApbOffTrmnl',
                    'vpa' => 'payervpa@upi',
                ]
            ),
        ];

        $this->makeRequestAndGetContent($request);
    }

    private function makeUpiRzpapbPaymentForFailedStatus($qrCodeEntity)
    {
        $this->ba->directAuth();

        $request = [
            'url' => '/callback/upi_rzpapb',
            'method' => 'POST',
            'raw' => json_encode(
                [
                    'id' => str_after($qrCodeEntity['id'], 'qr_') . 'qrv2',
                    'amount' => 100,
                    'description' => 'payment_failed',
                    'gateway' => 'upi_rzpapb',
                    'terminal_id' => 'RzpApbOffTrmnl',
                    'vpa' => 'payervpa@upi',
                ]
            ),
        ];

        $this->makeRequestAndGetContent($request);
    }

    private function makeUpiRzpapbPaymentWithOffer($qrCodeEntity)
    {
        $this->ba->directAuth();

        $request = [
            'url' => '/callback/upi_rzpapb',
            'method' => 'POST',
            'raw' => json_encode(
                [
                    'id' => str_after($qrCodeEntity['id'], 'qr_') . 'qrv2',
                    'amount' => 90,
                    'description' => 'create_success_for_offer',
                    'gateway' => 'upi_rzpapb',
                    'terminal_id' => 'RzpApbOffTrmnl',
                    'vpa' => 'payervpa@upi',
                ]
            ),
        ];

        $this->makeRequestAndGetContent($request);
    }

    private function makeUpiRzpapbPaymentWithEmi($qrCodeEntity)
    {
        $this->ba->directAuth();

        $request = [
            'url' => '/callback/upi_rzpapb',
            'method' => 'POST',
            'raw' => json_encode(
                [
                    'id' => str_after($qrCodeEntity['id'], 'qr_') . 'qrv2',
                    'amount' => 90,
                    'description' => 'create_success_for_emi',
                    'gateway' => 'upi_rzpapb',
                    'terminal_id' => 'RzpApbOffTrmnl',
                    'vpa' => 'payervpa@upi',
                ]
            ),
        ];

        $this->makeRequestAndGetContent($request);
    }

    private function makeUpiMindgatePayment($qrCodeEntity,$terminal, $payment = [], $upiEntity = [])
    {
        $this->ba->directAuth();

        $defaultPaymentData = [
            'amount'      => '300',
            'description' => '',
            'vpa'         => 'testvpa@hdfcbank',
        ];

        $payment = array_merge($defaultPaymentData, $payment);

        $payment_id = $qrCodeEntity['reference'] . 'qrv2' ;
        $gateway_payment_id = '80276224983';

        if ($qrCodeEntity['usage'] === 'multiple_use')
        {
            $payment_id = 'STQ' . $payment_id . '!' . $gateway_payment_id;
        }

        $defaultUPIData = [
            'gateway_payment_id' => $gateway_payment_id,
            'vpa'                => 'testvpa@hdfcbank',
            'payment_id'         =>  $payment_id,
        ];

        $upiEntity = array_merge($defaultUPIData, $upiEntity);

        $content = $this->getMockServer('upi_mindgate')->getCallback($upiEntity, $payment);

        $content['pgMerchantId'] = $terminal['gateway_merchant_id'];

        return $this->makeS2SCallbackAndGetContent($content,'upi_mindgate');
    }

    private function makeUpiKotakPayment($qrCodeEntity, $contents = [])
    {
        $this->ba->directAuth();

        $request = [
            'url'     => '/callback/upi_kotak',
            'method'  => 'POST',
            'content' => [
                'transactionreferencenumber' => str_after($qrCodeEntity['id'], 'qr_') . 'qrv2',
                'aggregatorcode'             => 'RAZORPAY',
                'rrn'                        => '107611570997',
                'amount'                     => '3.00',
                'payervpa'                   => 'pullak@okhdfcbank',
                'remarks'                    => 'RazorpayOrdercreatedsuccessfully',
                'status'                     => 'SUCCESS',
                'merchantcode'               => 'razorpayupi',
                'refid'                      => str_after($qrCodeEntity['id'], 'qr_') . 'qrv2',
                'payeevpa'                   => 'testvpa@kotak',
                'description'                => 'RazorpayOrdercreatedsuccessfully',
                'transactionid'              => 'HDF746e74c617b54b97bc36eadc6c89cbe4',
                'transactionTimestamp'       => '1792422246',
                'type'                       => 'PAY',
                'checksum'                   => 'RandomChecksumaa901b86b07e3531a33b7208f17d38c82b528bd45c44bd6194',
                'refurl'                     => 'https://upi.hdfcbank.com',
                'statusCode'                 => '00',
            ],
            'header'  => [
                'content_type' => 'application/json',
            ],
        ];

        $request['content'] = array_merge($request['content'], $contents);

        $this->gateway = 'upi_kotak';

        return $this->makeS2SCallbackAndGetContent(json_encode($request['content']), $this->gateway);
    }

    private function makeUpiAirtelPayment($qrCodeEntity, $contents = [])
    {
        $this->ba->directAuth();

        $request = [
            'url'     => '/callback/upi_airtel',
            'method'  => 'POST',
            'content' => [
                'amount'                     => '3.00',
                'mid'                        => 'razorpayupi',
                'rrn'                        => '107611570997',
                'txnStatus'                  => 'SUCCESS',
                'hdnOrderID'                 => str_after($qrCodeEntity['id'], 'qr_') . 'qrv2',
                'messageText'                => 'SUCCESS',
                'code'                       => '0',
                'errorCode'                  => '0',
                'hash'                       => '8e11b31ec34d05b8e6decc63224ae90467332d665db7cfeb2a9038692ecdb393a18d9dc2feb07352d766945280971de1195b203057cf697ebccb7fe9b959be9a',
                'payerVPA'                   => 'pullak@okhdfcbank',
                'payeeVPA'                   => 'testvpa@mairtel',
                'txnRefNo'                   => 'FT220XXXX24791',
            ],
            'header'  => [
                'content_type' => 'application/json',
            ],
        ];

        $request['content'] = array_merge($request['content'], $contents);

        $this->gateway = 'upi_airtel';

        return $this->makeS2SCallbackAndGetContent(json_encode($request['content']), $this->gateway);
    }

    private function getIntentParamsFromQRString($qrString)
    {
        $queryString = parse_url($qrString, PHP_URL_QUERY);

        parse_str($queryString, $params);

        return $params;
    }

    private function makeUpiPaymentInternal($request)
    {
        $this->ba->appAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function setMockRazorxTreatment(array $razorxTreatment, string $defaultBehaviour = 'control')
    {
        // Mock Razorx
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                              function ($mid, $feature, $mode) use ($razorxTreatment, $defaultBehaviour)
                              {
                                  if (array_key_exists($feature, $razorxTreatment) === true)
                                  {
                                      return $razorxTreatment[$feature];
                                  }

                                  return strtolower($defaultBehaviour);
                              }));
    }

    /**
     * @throws \Exception
     */
    protected function setMockSplitzTreatment(array $Experiments = [])
    {
        $this->splitzMock = Mockery::mock(SplitzService::class)->makePartial();

        $this->app->instance('splitzService', $this->splitzMock);

       $this->splitzMock
            ->shouldReceive('evaluateRequest')
           ->andReturnUsing(function ($input) use ($Experiments) {
                if (array_key_exists($input['experiment_id'], $Experiments) === true)
                {
                    $result = ($Experiments[$input['experiment_id']] ?? null) === 'on' ? 'on' : 'off';
                    return [
                        "response" => [
                            "variant" => [
                                "variables" => [
                                    [
                                        "key" => "result",
                                        "value" => $result
                                    ]
                                ]
                            ]
                        ]
                    ];
                }

                return [
                     "response" => [
                          "variant" => [
                            "variables" => [
                                 [
                                      "key" => "result",
                                      "value" => "off"
                                 ]
                            ]
                          ]
                     ]
                ];
           });
    }

    protected function mockSplitzTreatment($output)
    {
        $this->splitzMock = Mockery::mock(SplitzService::class)->makePartial();

        $this->app->instance('splitzService', $this->splitzMock);

        $this->splitzMock
            ->shouldReceive('evaluateRequest')
            ->andReturn($output);
    }

    protected function mockHolygrailIciciFlowSplitzExerpiementEnabled()
    {
        $output = [
            'response' => [
                'variant' => [
                    'variables' => [
                        [
                            'key' => 'holygrail',
                            'value' => 'on'
                        ]
                    ]
                ]
            ]
        ];

        $this->mockSplitzTreatment($output);
    }

    protected function mockSplitzTreatmentForStatusCheck($qrStatusCheckOutput = 'on', $dedicatedTerminalOutput = 'on')
    {
        $this->splitzMock = Mockery::mock(SplitzService::class)->makePartial();

        $this->app->instance('splitzService', $this->splitzMock);

        $this->splitzMock
            ->shouldReceive('evaluateRequest')
            ->andReturnUsing(function ($input) use ($qrStatusCheckOutput, $dedicatedTerminalOutput) {
                // If the experiment to evaluate is related to status check splitz, return the mock output
                if ($input['experiment_id'] === 'MbIfehaPwEDEN6')
                {
                    return [
                        "response" => [
                            "variant" => [
                                "variables" => [
                                    [
                                        "key" => "result",
                                        "value" => $qrStatusCheckOutput,
                                    ],
                                    [
                                        "key" => "holygrail",
                                        "value" => "on",
                                    ]
                                ]
                            ]
                        ]
                    ];
                }

                if ($input['experiment_id'] === 'DedicatedQrExp')
                {
                    return [
                        "response" => [
                            "variant" => [
                                "variables" => [
                                    [
                                        "key" => "result",
                                        "value" => $dedicatedTerminalOutput,
                                    ],
                                    [
                                        "key" => "holygrail",
                                        "value" => "on",
                                    ]
                                ]
                            ]
                        ]
                    ];
                }

                // For all other experiments return an off value
                // For example, evaluating if a dedicated terminal is enabled or not.
                return [
                    "response" => [
                        "variant" => [
                            "variables" => [
                                [
                                    "key" => "result",
                                    "value" => "off",
                                ],
                                [
                                    "key" => "holygrail",
                                    "value" => "on",
                                ]
                            ]
                        ]
                    ]
                ];
            });
    }

    protected function mockSplitzTreatmentForEzetapNotification($ezetapNotification = 'on', $dedicatedTerminalOutput = 'on')
    {
        $this->splitzMock = Mockery::mock(SplitzService::class)->makePartial();

        $this->app->instance('splitzService', $this->splitzMock);

        $this->splitzMock
            ->shouldReceive('evaluateRequest')
            ->andReturnUsing(function ($input) use ($ezetapNotification, $dedicatedTerminalOutput) {
                // If the experiment to evaluate is related to status check splitz, return the mock output
                if ($input['experiment_id'] === 'P1ihasxhcDs1ZE')
                {
                    return [
                        "response" => [
                            "variant" => [
                                "variables" => [
                                    [
                                        "key" => "result",
                                        "value" => $ezetapNotification,
                                    ]
                                ]
                            ]
                        ]
                    ];
                }

                if ($input['experiment_id'] === 'DedicatedQrExp')
                {
                    return [
                        "response" => [
                            "variant" => [
                                "variables" => [
                                    [
                                        "key" => "result",
                                        "value" => $dedicatedTerminalOutput,
                                    ]
                                ]
                            ]
                        ]
                    ];
                }

                // For all other experiments return an off value
                // For example, evaluating if a dedicated terminal is enabled or not.
                return [
                    "response" => [
                        "variant" => [
                            "variables" => [
                                [
                                    "key" => "result",
                                    "value" => "off"
                                ]
                            ]
                        ]
                    ]
                ];
            });
    }

    protected function getDedicatedTerminalSplitzResponseForOnVariant()
    {
        $output = [
            "response" => [
                "variant" => [
                    "variables" => [
                        [
                            "key" => "result",
                            "value" => "on"
                        ]
                    ]
                ]
            ]
        ];
        return $output;
    }

    protected function getHolygrailIciciFlowSplitzResponseForOnVaiant()
    {
        $output = [
            "response" => [
                "variant" => [
                    "variables" => [
                        [
                            "key" => "holygrail",
                            "value" => "on"
                        ]
                    ]
                ]
            ]
        ];
        return $output;
    }

    protected function getDedicatedTerminalSplitzResponseForVariantON()
    {
         $this->mockSplitzTreatment([
            "response" => [
                "variant" => [
                    "variables" => [
                        [
                            "key" => "result",
                            "value" => "on"
                        ]
                    ]
                ]
            ]
        ]);
    }

    public function runQrPaymentAssertions($qrCodeId, $request, $mode = 'test'): void
    {
        $qrPayment        = $this->getDbLastEntity('qr_payment', $mode);
        $payment          = $this->getDbLastEntity('payment', $mode);
        $qrPaymentRequest = $this->getDbLastEntity('qr_payment_request', $mode);
        $upi              = $this->getDbLastEntity('upi', $mode);

        $rrn            = $request['content']['BankRRN'];
        $merchantTranId = $request['content']['merchantTranId'];

        $this->assertEquals($rrn, $upi['npci_reference_id']);
        $this->assertEquals($merchantTranId , $upi['merchant_reference']);
        $this->assertEquals($qrCodeId, $qrPayment['merchant_reference']);
        $this->assertEquals(null, $qrPaymentRequest['failure_reason']);
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals($qrPayment['payment_id'], $payment['id']);
        $this->assertEquals(1, $qrPayment['expected']);
        $this->assertEquals($rrn, $payment['acquirer_data']['rrn']);
        $this->assertEquals($rrn, $payment['reference16']);
    }

    public function getMockedQrStatusCheckResponse($status, $qrCodeId, $rrn)
    {
        $content = [
            'data' => [
                'meta' => [
                    'response' => [
                        'plain' => [
                            'TxnCompletionDate' => '20230921023436',
                            'TxnInitDate'       => '20230921023408',
                            'amount'            => '40.00',
                            'merchantId'        => '403343',
                            'merchantTranId'    => str_after($qrCodeId, 'qr_') . "qrv2",
                            'message'           => 'Transaction Successful',
                            'payerVA'           => 'razorpay@icici',
                            'response'          => '0',
                            'status'            => $status,
                            'subMerchantId'     => '403343',
                            'success'           => 'true',
                            'terminalId'        => '5411',
                        ]
                    ]
                ],
                'upi' => [
                    'vpa' => 'razorpay@icici',
                    'status_code' => '0',
                    'npci_reference_id' => $rrn,
                    'merchant_reference' => str_after($qrCodeId, 'qr_') . 'qrv2',
                    'gateway_payment_id' => '235291019373',
                    'gateway_amount' => 26553,
                ],
                'payment' => [
                    'currency' => 'INR',
                    'amount_authorized' => '4000',
                    'payer_account_type' => 'credit_card',
                ],
                'terminal' => [
                    'gateway_merchant_id' => '403343',
                    'gateway' => 'upi_icici'
                ],
                'version' => 'v2'
            ],
            'error' => null,
            'external_trace_id' => 'DUMMY_REQUEST_ID',
            'success' => true,
        ];
        if (empty($rrn) === false)
        {
            $content['data']['meta']['response']['plain']['OriginalBankRRN'] = $rrn;
        }

        return $content;
    }

    public function getMockedYesbankQrStatusCheckResponse($status, $qrCodeId, $rrn)
    {
        $content = [
            'data' => [
                'status'   => $status,
                'meta'     => [
                    'response' => [
                        'plain' => [
                            'Add10'             => 'NA',
                            'Add2'              => 'NA',
                            'Add3'              => 'testpayment',
                            'Add4'              => 'SAVINGS',
                            'Add5'              => 'NA',
                            'Add6'              => 'NA',
                            'Add7'              => 'NA',
                            'Add8'              => 'NA',
                            'Add9'              => 'NA',
                            'Amount'            => '40.0',
                            'ApprovalNumber'    => '933462',
                            'CustRefNo'         => '326836533213',
                            'MerchantRefNo'     => 'RZPY'. str_after($qrCodeId, 'qr_') .'qrv2',
                            'NpciRefId'         => 'NA',
                            'NpciTxnId'         => 'YBL144231ce5eb64a58998c6e6f14fa263a',
                            'PayeeAadhaar'      => 'NA',
                            'PayeeAcountNo'     => 'SCRUBBED_PII (10)',
                            'PayeeIfsc'         => 'YESB0000022',
                            'PayeeName'         => 'SCRUBBED_PII (40)',
                            'PayeeVpa'          => 'rzpvqfalegriaholidaysandhospitalitylimited@yesbank',
                            'PayerAccountName'  => 'SCRUBBED_PII (15)',
                            'PayerAccountNo'    => 'SCRUBBED_PII (10)',
                            'PayerIfsc'         => 'SBIN0012159',
                            'PayerVpa'          => '7747931160@ybl',
                            'ResponseCode'      => '00',
                            'Status'            => 'SUCCESS',
                            'StatusDescription' => 'Transaction success',
                            'TimeOutTxnStatus'  => 'NA',
                            'TxnAuthDate'       => '2023:09:25 20:12:13',
                            'YblTxnId'          => '13508165557'
                        ]
                    ]
                ],
                'terminal' =>
                    [
                        'gateway' => 'upi_yesbank',
                        'vpa'     => 'testvpa@yesb',
                    ],
                'upi'      =>
                    [
                        'merchant_reference' => 'RZPY' . str_after($qrCodeId, 'qr_') . 'qrv2',
                        'gateway_payment_id' => '13508165557',
                        'npci_reference_id'  => $rrn,
                        'vpa'                => 'mitasha@oksbi',
                    ],
                'payment'  => [
                    'amount_authorized' => '4000',
                    'currency'          => 'INR',
                ]
            ]
        ];

        return $content;
    }
    public function getMockedUpiMindgateQrStatusCheckResponse($status, $qrCodeId, $rrn, $pgMerchantId, $gatewayPaymentId, $amount, $vpa)
    {
        if ($status === 'U30')
        {
            $statusMsg = 'FAILED';
        }
        elseif ($status === '01')
        {
            $statusMsg = 'PENDING';
        }
        elseif($status === '00')
        {
            $statusMsg = 'SUCCESS';
        }
        else
        {
            $statusMsg = 'FAILURE';
        }
        $content = [
            'data'    => [
                '_raw'     => 'raw_content',
                'meta'     => [
                    'request'  => [
                        'content' => 'encrypted_request'
                    ],
                    'response' => [
                        'plain' => [
                              'status' => $statusMsg,
                        ],
                    ]
                ],
                'payment'  => [
                    'amount_authorized'  => $amount,
                    'currency'           => 'INR',
                    'payer_account_type' => 'bank_account'
                ],
                'status'   => 'payment_successful',
                'terminal' => [
                    'gateway' => 'upi_mindgate',
                    'gateway_merchant_id' => $pgMerchantId
                ],
                'upi'      => [
                    'gateway' => 'upi_mindgate',
                    'gateway_amount'     => $amount,
                    'gateway_payment_id' => $gatewayPaymentId,
                    'gateway_status_code'=> $status,
                    'status_code'=> $status,
                    'merchant_reference' => str_after($qrCodeId, 'qr_') . 'qrv2',
                    'npci_reference_id'  => $rrn,
                    'vpa'                => $vpa
                ],
                'version'  => 'v2'
            ],
            'error'   => null,
            'success' => true
        ];

        return $content;
    }

    public function getMockedUpiAirtelQrStatusCheckResponse($status, $qrCodeId, $rrn, $pgMerchantId, $amount, $vpa,$gatewayMerchantId2)
    {
        $statusBool = false;
        if ($status === 'U69')
        {
            $statusMsg = 'FAILED';
        }
        elseif ($status === '01')
        {
            $statusMsg = 'PENDING';
        }
        elseif($status === '0')
        {
            $statusMsg = 'SUCCESS';
            $statusBool = true;
        }
        else
        {
            $statusMsg = 'FAILURE';

        }
        $content = [
            'data'    => [
                '_raw'     => 'raw_content',
                'meta'     => [
                    'response' => [
                        'content' => [
                            'txnStatus' => $statusMsg,
                        ],
                    ]
                ],
                'payment'  => [
                    'amount_authorized'  => $amount,
                    'currency'           => 'INR'
                ],
                'status'   => 'verify_successful',
                'terminal' => [
                    'gateway'               => 'upi_airtel',
                    'gateway_merchant_id'   => $pgMerchantId,
                    'gateway_merchant_id2'  => $gatewayMerchantId2
                ],
                'upi'      => [
                    'gateway_status_code'   => $status,
                    'status_code'           => $status,
                    'gateway_payment_id'    => 'FT5748103957835',
                    'merchant_reference'    => str_after($qrCodeId, 'qr_') . 'qrv2',
                    'npci_reference_id'     => $rrn,
                    'npci_txn_id'           => 'APB2443360925859',
                    'npci_response_code'    => $status,
                    'vpa'                   => $vpa
                ],
                'version'  => 'v2'
            ],
            'error'   => null,
            'success' => $statusBool,
        ];

        return $content;
    }

    public function runEntityAssertionsForPaymentLinksQR(Entity $qrCode, $createQrInput, OrderEntity $order)
    {
        $this->assertEquals(true, $qrCode->isPaymentLinksQrCode());

        $this->assertEquals($order->getId(), $qrCode->getEntityId());
        $this->assertEquals('order', $qrCode->getEntityType());

        $this->assertEquals(true, !empty($qrCode));
        $this->assertNotNull($qrCode['id']);
        $this->assertEquals('single_use', $qrCode->getUsageType());
        $this->assertEquals('upi_qr', $qrCode['provider']);

        // Intent link checks
        $tr = 'icicirefID';
        $this->assertStringContainsString($tr, $qrCode['qr_string']);
//        $this->assertStringContainsString('qrmoremegast', $qrCode['qr_string']);
        $this->assertStringContainsString('@icici', $qrCode['qr_string']);
        $this->assertStringContainsString('am=' . $qrCode['amount']/100, $qrCode['qr_string']);

        $this->assertGreaterThan(0, $qrCode['fixed_amount']);

        $this->assertGreaterThan(time() + 5*100, $qrCode[Entity::CLOSE_BY]);

        $this->assertEquals($createQrInput[Entity::DESCRIPTION], $qrCode[Entity::DESCRIPTION]);

    }

    public function runEntityAssertions($response)
    {
        $qrCodeEntity = $this->getLastEntity('qr_code', true);

        $tr = 'RZP' . substr($response['id'], 3, 14) . 'qrv2';
        $this->assertStringContainsString($tr, $qrCodeEntity['qr_string']);
//        $this->assertStringContainsString('qrmoremegast', $qrCodeEntity['qr_string']);
        $this->assertStringContainsString('@icici', $qrCodeEntity['qr_string']);

        if ($qrCodeEntity['fixed_amount'] === true)
        {
            $amount = $qrCodeEntity['amount'] / 100;

            $this->assertStringContainsString('am=' . $amount, $qrCodeEntity['qr_string']);
        }

        if ($response['type'] === Type::BHARAT_QR)
        {
            $this->assertStringContainsString('0518' . substr($response['id'], 3) . 'qrv2', $qrCodeEntity['qr_string']);
        }
    }

    public function runEntityAssertionsForDedicatedTerminalQr($response, $terminal, $mode = 'test')
    {
        $qrCodeEntity = $this->getLastEntity('qr_code', true, $mode);

        $this->assertEquals($qrCodeEntity['id'], $response['id']);

        $vpa = null;
        switch ($terminal->getGateway())
        {
            case Gateway::UPI_ICICI:
            {
                $vpa = $terminal->getGatewayMerchantId2();

                switch ($qrCodeEntity['usage'])
                {
                    case "single_use":
                    {
                        $this->assertStringContainsString('icicirefID', $qrCodeEntity['qr_string']);

                        break;
                    }
                    case "multiple_use":
                    {
                        $tr = 'RZP' . substr($response['id'], 3, 14) . 'qrv2';
                        $this->assertStringContainsString($tr, $qrCodeEntity['qr_string']);
                        break;
                    }
                }
                break;
            }
            case Gateway::UPI_MINDGATE:
            {
                $vpa = $terminal->getGatewayMerchantId2();
                switch ($qrCodeEntity['usage'])
                {
                    case "single_use":
                    {
                        $tr = substr($response['id'], 3, 14) . 'qrv2';
                        $this->assertStringContainsString($tr, $qrCodeEntity['qr_string']);
                        break;
                    }
                    case "multiple_use":
                    {
                        $tr = 'STQ' . substr($response['id'], 3, 14) . 'qrv2';
                        $this->assertStringContainsString($tr, $qrCodeEntity['qr_string']);
                        break;
                    }
                }
                break;
            }

            case Gateway::UPI_AIRTEL:
            {
                $vpa = $terminal->getGatewayMerchantId2();
                $tr = substr($response['id'], 3, 14) . 'qrv2';
                switch ($qrCodeEntity['usage'])
                {
                    case 'single_use':
                    {
                        $this->assertStringContainsString($tr, $qrCodeEntity['qr_string']);
                        break;
                    }
                    case 'multiple_use':
                    {
                        $this->assertStringNotContainsString('tr=', $qrCodeEntity['qr_string'], 'Static QR Should not contain tr attribute');
                        $this->assertStringNotContainsString('mode=', $qrCodeEntity['qr_string'], 'Static QR Should not contain mode attributes');
                        break;
                    }
                }
                break;
            }

            default:
            {
                $vpa = $terminal->getVpa();

                switch ($qrCodeEntity['usage'])
                {
                    case "single_use":
                    {
                        $this->assertStringContainsString('icicirefID', $qrCodeEntity['qr_string']);

                        break;
                    }
                    case "multiple_use":
                    {
                        $tr = substr($response['id'], 3, 14) . 'qrv2';
                        $this->assertStringContainsString($tr, $qrCodeEntity['qr_string']);
                        break;
                    }
                }
                break;
            }
        }

        $this->assertStringContainsString($vpa, $qrCodeEntity['qr_string']);

        if ($qrCodeEntity['fixed_amount'] === true)
        {
            $amount = $qrCodeEntity['amount'] / 100;

            $this->assertStringContainsString('am=' . $amount, $qrCodeEntity['qr_string']);
        }

        if ($response['type'] === Type::BHARAT_QR)
        {
            $this->assertStringContainsString('0518' . substr($response['id'], 3) . 'qrv2', $qrCodeEntity['qr_string']);
        }
    }

    public function enableRazorXTreatmentForCCOnUPI()
    {
        $razorx = \Mockery::mock(RazorXClient::class)->makePartial();

        $this->app->instance('razorx', $razorx);

        $razorx->shouldReceive('getTreatment')
               ->andReturnUsing(function (string $id, string $featureFlag, string $mode)
               {
                   if ($featureFlag === (RazorxTreatment::ALLOW_CC_ON_UPI_PRICING))
                   {
                       return 'on';
                   }
                   return 'control';
               });
    }

    protected function mockSplitzTreatmentBulkRequest($output)
    {
        $this->splitzMock = Mockery::mock(SplitzService::class)->makePartial();

        $this->app->instance('splitzService', $this->splitzMock);

        $this->splitzMock
            ->shouldReceive('bulkCallsToSplitz')
            ->andReturn($output);

        $this->splitzMock
            ->shouldReceive('evaluateRequest')
            ->andReturn([
                            "response" => [
                                "variant" => [
                                    "variables" => [
                                        [
                                            "key"   => "result",
                                            "value" => 'off',
                                        ],
                                    ],
                                ],
                            ],
                        ]
            );
    }

    public function mockRemindersRequestForStatusCheck(&$count = 0, $fail = false, &$disableCount = 0)
    {
        $remindersMock = \Mockery::mock(Reminders::class)->makePartial();

        $this->app->instance('reminders', $remindersMock);

        if ($fail === true) {
            $remindersMock->shouldReceive('createReminder')
                          ->andThrow(new ServerErrorException('Test error', ErrorCode::SERVER_ERROR));

            $remindersMock->shouldReceive('disableReminderUsingEntityIdAndNamespace')
                          ->andThrow(new ServerErrorException('Test error', ErrorCode::SERVER_ERROR));
        }
        else {
            $remindersMock->shouldReceive('createReminder')
                          ->andReturnUsing(function ($input, $merchantId) use (&$count) {
                              $count++;
                              return ['id' => 'DErKK3a9tEGlph'];
                          });

            $remindersMock->shouldReceive('disableReminderUsingEntityIdAndNamespace')
                          ->andReturnUsing(function ($entityId, $namespace, $merchantId) use (&$disableCount) {
                              $disableCount++;
                              return ['success' => true];
                          });
        }
    }

    public function mockEzetapNotification(&$actualCallCount = 0, &$eventList = [],$fail = false)
    {
        $ezetapNotificationMock = \Mockery::mock(\RZP\Services\EzetapNotification\Mock\EzetapNotification::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('ezetapNotification', $ezetapNotificationMock);

        if ($fail === true)
        {
            $ezetapNotificationMock->shouldReceive('sendEzetapRequest')
                                   ->andThrow(new ServerErrorException('Test error', ErrorCode::SERVER_ERROR));
        }
        else
        {

            $ezetapNotificationMock->shouldReceive('actualCall')
                                   ->andReturnUsing(function($event) use (&$actualCallCount, &$eventList) {
                                       $actualCallCount++;
                                       array_push($eventList,$event->event);
                                       return null;
                                   });

        }
    }

    public function handleUfhService($qrCodeId)
    {
        $ufhServiceMock = Mockery::mock(Mock\UfhService::class, [$this->app])->makePartial();
        $exception = new ServerErrorException('Unavailable', 'SERVER_ERROR');
        $count = 0;

        $ufhServiceMock->shouldReceive('fetchFiles')
                       ->andReturnUsing(function() use (&$count, $exception, $qrCodeId) {
                           if ($count == 0) {
                               ++$count;
                               throw $exception;
                           }
                           else {
                               ++$count;
                               return [
                                   'entity'  => 'collection',
                                   'count'   => 1,
                                   'items'   => [
                                       [
                                           'id'            => 'file_10RandomFileId',
                                           'type'          => 'explanation_letter',
                                           'entity_type'   => 'qr_code',
                                           'entity_id'     => $qrCodeId,
                                           'name'          => 'QrCode.jpg',
                                           'location'      => 'random/qrcode/location',
                                           'bucket'        => 'test_bucket',
                                           'mime'          => 'image/jpeg',
                                           'extension'     => 'jpg',
                                           'merchant_id'   => '10000000000000',
                                           'store'         => 's3',
                                       ],
                                   ],
                               ];
                           }
                       });

        $this->app->instance('ufh.service', $ufhServiceMock);
    }

    protected function buildQRUnexpectedPaymentRequest($terminal)
    {
        $this->fixtures->merchant->createAccount('100DemoAccount');
        $this->fixtures->merchant->enableUpi('100DemoAccount');

        $content = $this->getDefaultUpiUnexpectedPaymentArray();

        unset($content['upi']['account_number'], $content['upi']['ifsc'], $content['upi']['npci_txn_id'], $content['upi']['gateway_data']);
        $content['upi']['merchant_reference'] = 'SBI4bff5573b6304cabbc98946f2600420e';
        $content['terminal']['gateway'] = $terminal['gateway'];
        $content['terminal']['gateway_merchant_id'] = $terminal['gateway_merchant_id'];

        return $content;
    }

    protected function makeUnexpectedLivePaymentAndGetContent(array $content)
    {
        $request = [
            'url' => '/payments/create/upi/unexpected',
            'method' => 'POST',
            'content' => $content,
        ];

        $this->ba->appAuth('rzp_live');

        return $this->makeRequestAndGetContent($request);
    }

    protected  function createOrderForQrCode(array $input = [], $mode,$merchantId = '10000000000000', array $headers = [])
    {
        $this->ba->privateAuth();

        if($mode === 'live')
        {
            $this->ba->privateAuth('rzp_live_' . $merchantId);
        }

        $defaultValues = $this->getDefaultOrderRequestArray();

        $attributes = array_merge($defaultValues, $input);

        $request = [
            'method'  => 'POST',
            'url'     => '/v1/orders',
            'content' => $attributes,
            'headers' => $headers,
        ];

        return $this->makeRequestAndGetContent($request);

    }

    private function getDefaultOrderRequestArray()
    {
        return [
            'amount'         => 4000,
            'currency'  => 'INR',
            'receipt' => 'Receipt no. 1',
            'notes' => [
                "notes_key_1" => "Tea, Earl Grey, Hot",
                "notes_key_2" => "Tea, Earl Grey… decaf."
            ],
        ];
    }
}
