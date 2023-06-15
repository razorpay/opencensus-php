<?php

namespace Functional\QrCode;

use Carbon\Carbon;
use RZP\Models\Pricing\Fee;
use RZP\Models\QrCode\Type;
use RZP\Models\Payment\Gateway;
use RZP\Exception\LogicException;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Exception\InvalidArgumentException;
use RZP\Models\QrPayment\UnexpectedPaymentReason;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Tests\Functional\Helpers\QrCode\NonVirtualAccountQrCodeTrait;

class UpiYesBankQRCodeTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;
    use NonVirtualAccountQrCodeTrait;


    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NonVirtualAccountQrCodeTestData.php';

        parent::setUp();

        $this->config['gateway.mock_upi_mozart'] = true;

        $this->fixtures->merchant->addFeatures(['qr_codes', 'bharat_qr_v2']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->edit('10000000000000', ['billing_label' => 'more-megastore-account']);

        $this->fixtures->merchant->activate();

        $this->fixtures->on('test')->merchant->edit('10000000000000', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        $this->fixtures->merchant->createAccount('LiveAccountMer');

        $this->fixtures->on('live')->merchant->edit('LiveAccountMer', ['activated' => true, 'live' => true]);

        $this->fixtures->on('live')->merchant->addFeatures(['qr_codes', 'bharat_qr_v2'], 'LiveAccountMer');

        $this->fixtures->on('live')->merchant->enableMethod('LiveAccountMer', 'upi');

        $this->fixtures->on('live')->merchant->edit('LiveAccountMer', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        $this->fixtures->create('terminal:dedicated_upi_yesbank_terminal');

    }


    public function testCreateStaticQrWithoutTerminal() :void
    {
        $this->expectException(LogicException::class);

        $this->expectExceptionMessage('No Terminal applicable.');

        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ],
            'live',
            'LiveAccountMer');
    }

    public function testCreateStaticQrWithTerminal() :void
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

        $this->mockSplitzTreatment($output);

        $response = $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ],
        );

        $this->runEntityAssertions();
    }


    public function testCreateStaticQrWithAmount() :void
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

        $this->mockSplitzTreatment($output);

        $response = $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'payment_amount' => '300',
                'type'  => 'upi_qr',
                'fixed_amount' => true,
            ],
        );

        $this->assertEquals(true, $response['fixed_amount']);
        $this->assertEquals(300, $response['payment_amount']);
        $this->runEntityAssertions();
    }

    public function testPaymentForStaticQrCode()
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

        $this->mockSplitzTreatment($output);

        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ],
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true);

        $this->makeUpiYesBankPayment($qrCodeEntity);

        $qrPayment = $this->getLastEntity('qr_payment', true);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(300, $payment['amount']);
        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals(1, $qrPayment['expected']);
    }

    public function testPaymentOnDynamicQrCode() :void
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

        $this->mockSplitzTreatment($output);

        $this->createQrCode(
            [
                'usage' => 'single_use',
                'type'  => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
            ],
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true);

        $this->makeUpiYesBankPayment($qrCodeEntity);

        $qrPayment = $this->getLastEntity('qr_payment', true);
        $payment = $this->getLastEntity('payment', true);
        $qrCodeUpdatedEntity = $this->getLastEntity('qr_code', true);

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(300, $payment['amount']);
        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals(1, $qrPayment['expected']);
        $this->assertEquals('closed', $qrCodeUpdatedEntity['status']);
        $this->assertEquals('107611570997', $payment['reference16']);
    }

    public function testPaymentForClosedQrCode()
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

        $this->mockSplitzTreatment($output);

        $this->createQrCode(
            [
                'usage' => 'single_use',
                'type'  => 'upi_qr',
            ],
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true);

        $qrCodeId = $qrCodeEntity['id'];

        $qrCode = $this->closeQrCode($qrCodeId);

        $this->assertEquals('closed', $qrCode['status']);

        $this->fixtures->stripSign($qrCodeId);

        $this->makeUpiYesBankPayment($qrCodeEntity);

        $qrPayment = $this->getDbLastEntity('qr_payment');
        $payment = $this->getLastEntity('payment', true);

        $refund = $this->getDbLastEntity('refund');
        $this->assertEquals(UnexpectedPaymentReason::QR_PAYMENT_ON_CLOSED_QR_CODE, $refund['notes']['refund_reason']);

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('refunded', $payment['status']);
        $this->assertEquals(300, $payment['amount']);
        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals($qrCodeId, $qrPayment['qr_code_id']);
        $this->assertEquals(0, $qrPayment['expected']);
    }

    public function testPaymentForInvalidQrCode()
    {
        self::markTestSkipped();

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

        $this->mockSplitzTreatment($output);
        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ],
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true);

        $qrCodeEntity['id'] = 'qr_ABCEDF1234567890';
        $qrCodeEntity['reference'] = 'ABCEDF1234567890';

        $this->makeUpiYesBankPayment($qrCodeEntity);

        $qrPayment = $this->getDbLastEntity('qr_payment');
        $payment = $this->getLastEntity('payment', true);
    }

    public function testPaymentWithDisabledUpiMethod()
    {
        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage(UnexpectedPaymentReason::QR_CODE_PAYMENT_FAILED_UPI_NOT_ENABLED);

        $this->fixtures->merchant->disableMethod('10000000000000', 'upi');

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

        $this->mockSplitzTreatment($output);

        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ],
        );
    }

    public function testPaymentForUnsuccessfulStatusCallback()
    {
        //Note: Callbacks with failed status are not processed and not stored in DB
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

        $this->mockSplitzTreatment($output);

        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ],
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true);

        $payment = [
            'amount'          => '300',
            'description'     => 'callback_failed_v2',
            'vpa'             => 'testvpa@yesb',
        ];

        $this->makeUpiYesBankPayment($qrCodeEntity,$payment);

        $qrPayment = $this->getDbLastEntity('qr_payment');
        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(null, $qrPayment);
        $this->assertEquals(null, $payment);
    }

    public function testMultiplePaymentsForStaticQR()
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

        $this->mockSplitzTreatment($output);

        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ],
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true);

        $this->makeUpiYesBankPayment($qrCodeEntity);

        $qrPayment = $this->getLastEntity('qr_payment', true);
        $payment = $this->getLastEntity('payment', true);
        $qrCodeEntityPostPayment = $this->getEntityById('qr_code', $qrCodeEntity['id'], true);

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals(1, $qrPayment['expected']);
        $this->assertEquals(300, $qrCodeEntityPostPayment['payments_amount_received']);
        $this->assertEquals('active', $qrCodeEntityPostPayment['status']);
    }

    public function testCreateDynamicQrCode() :void
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

        $this->mockSplitzTreatment($output);

        $response = $this->createQrCode(
            [
                'usage' => 'single_use',
                'type'  => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
            ],
        );

        $this->runEntityAssertions();
    }

    public function testCreateDynamicQrWithoutTerminal() :void
    {
        $this->expectException(LogicException::class);

        $this->expectExceptionMessage('No Terminal applicable.');

        $this->createQrCode(
            [
                'usage' => 'single_use',
                'type'  => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
            ],
            'live',
            'LiveAccountMer');
    }

    public function testCreateDynamicQrWithCloseByMoreThanExpectedLimit(): void
    {
        //Maximum permissible limit of closeBy is 45 days or 64800 minutes

        $this->expectException(BadRequestValidationFailureException::class);

        $this->expectExceptionMessage('QR expiry time cannot be more than 64800 minutes from the current time');

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

        $this->mockSplitzTreatment($output);

        $days = 46;

        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
                'close_by'       => Carbon::now()->getTimestamp() + ($days * 24 * 60 * 60),
            ],
        );
    }

    public function testCreateDynamicQrCodeFalseGatewayResponse() :void
    {
        $this->expectException(BadRequestException::class);

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

        $this->mockSplitzTreatment($output);

        $this->fixtures->create('terminal:live_dedicated_upi_yesbank_terminal');

        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
            ],
            'live',
            'LiveAccountMer');
    }

    //It tests Qr payment fetch flow done via internal flow for Yes bank qr codes whose payment is not received by razorpay
    public function testProcessYesBankQrReconInternalWithoutPayment()
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

        $this->mockSplitzTreatment($output);

        $this->createQrCode(
            [
                'usage' => 'single_use',
                'type'  => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
            ],
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true);

        $request = $this->testData['testProcessYesBankQrPaymentInternal'];
        $request['content']['data']['upi']['merchant_reference'] = $qrCodeEntity['reference'] . 'qrv2';
        $request['content']['data']['upi']['npci_reference_id'] = (string) random_int(100000000000, 999999999999);

        $response = $this->makeUpiPaymentInternal($request);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(300, $payment['amount']);
        $this->assertEquals(Gateway::UPI_YESBANK, $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);
        $this->assertEquals($response['payment']['id'], 'pay_' . $payment['id']);
        $this->assertEquals('captured', $response['payment']['status']);
    }

    public function testProcessYesBankQrReconInternal()
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

        $this->mockSplitzTreatment($output);

        $this->createQrCode(
            [
                'usage' => 'single_use',
                'type'  => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
            ],
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true);
        $this->makeUpiYesBankPayment($qrCodeEntity);
        $payment = $this->getDbLastEntity('payment');

        $request = $this->testData['testProcessYesBankQrPaymentInternal'];
        $request['content']['data']['upi']['merchant_reference'] = $qrCodeEntity['reference'] . 'qrv2';
        $request['content']['data']['upi']['npci_reference_id'] = $payment['reference16'];

        $response = $this->makeUpiPaymentInternal($request);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(300, $payment['amount']);
        $this->assertEquals(Gateway::UPI_YESBANK, $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);
        $this->assertEquals($response['payment']['id'], 'pay_' . $payment['id']);
        $this->assertEquals('captured', $response['payment']['status']);
    }

    private function runEntityAssertions(): void
    {
        $qrCodeEntity = $this->getLastEntity('qr_code', true);

        $this->assertStringContainsString('@yesb', $qrCodeEntity['qr_string']);

        if ($qrCodeEntity['provider'] === 'upi_qr')
        {
            $trValue = $this->getTRFieldFromString($qrCodeEntity['qr_string']);
            $this->assertEquals(18, strlen($trValue));
            $this->assertTrue(str_ends_with($trValue, 'qrv2'));
        }

        if($qrCodeEntity['usage'] === 'single_use')
        {
            $amount = $qrCodeEntity['amount'] / 100;
            $this->assertStringContainsString('am=' . $amount, $qrCodeEntity['qr_string']);
        }
        else
        {
            if ($qrCodeEntity['fixed_amount'] === true)
            {
                $amount = $qrCodeEntity['amount'] / 100;
                $this->assertStringContainsString('am=' . $amount, $qrCodeEntity['qr_string']);
            }
        }
    }

    protected function enableRazorXTreatmentForQrDedicatedTerminal() :void
    {
        $this->setMockRazorxTreatment([RazorxTreatment::DEDICATED_TERMINAL_QR_CODE => RazorxTreatment::RAZORX_VARIANT_ON]);
    }

    public function testCreateQrWithCloseOnDemandEnabled()
    {
        $output = $this->getDedicatedTerminalSplitzResponseForOnVariant();

        $this->mockSplitzTreatment($output);
        $this->setMockRazorxTreatment(
            [
                RazorxTreatment::DISABLE_QR_CODE_ON_DEMAND_CLOSE => RazorxTreatment::RAZORX_VARIANT_ON
            ]);

        $this->fixtures->merchant->addFeatures(['close_qr_on_demand']);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage("Your current configuration does not support QR creation. Contact support for further assistance");

        $this->createQrCode(
            ['usage' => 'single_use', 'type' => 'upi_qr', 'fixed_amount' => true, 'payment_amount' => 100,
             'name'  => 'Mitasha']
        );
    }

    public function testCreateBharatQrCodeWithDedicatedTerminal()
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

        $this->mockSplitzTreatment($output);

        $response = $this->createQrCode();

        $expectedResponse = $this->testData['testCreateBharatQrCode'];

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $this->runEntityAssertions($response);
    }

    public function testCreateBharatQrCodeWithNoDedicatedTerminal()
    {
        $this->enableRazorXTreatmentForQrDedicatedTerminal();

        $this->expectExceptionMessage('No Terminal applicable.');

        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'bharat_qr',
                'fixed_amount'   => true,
                'payment_amount' => 100,
            ],
            'live',
            'LiveAccountMer'
        );
    }

}
