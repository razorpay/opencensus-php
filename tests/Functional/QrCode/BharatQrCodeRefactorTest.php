<?php

namespace Functional\QrCode;

use Lib\CRC16;
use RZP\Models\BharatQr\Tags;
use RZP\Services\Mozart;
use RZP\Models\Pricing\Fee;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Payment\Method;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\QrCode\NonVirtualAccountQrCodeTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

/*
 *Create Bharat Qr for Mintoak -> (multiple_use, single_use)
 * Create Bharat Qr for Mindgate -> (multiple_use, single_use)
 * Payment Refactor Flow for Mintoak -> (multiple_use,single_use)
 * Duplicate Pyament for Mintoak ->(single_use)
 * Amount Mismatch for Mintoak -> (single_use)
 * Payment Refactor Flow for Mindgate -> (multiple_use,single_use)
 * Amount Mismatch for Mindgate -> (single_use)
 * Status Check for Mintoak -> (single_use)
 * Status Check for Mindgate -> (single_use)*/
class BharatQrCodeRefactorTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;
    use NonVirtualAccountQrCodeTrait;

    public $mintoakBqrString = '0002010102120827ABCD0000000000000000000000026310010A0000005240113test@hdfcbank27340010A000000524O116MNUTAAi0WYeMqrv252045399530335654041.005802IN5903qui6009BANGALORE610656003062220518OqMNUTAAi0WYeMqrv263049A6E';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/BharatQrCodeRefactorTestData.php';


        parent::setUp();
        $this->fixtures->merchant->createAccount('LiveAccountMer');
        $this->fixtures->on('live')->merchant->edit('LiveAccountMer', ['activated' => true, 'live' => true]);
        $this->fixtures->on('live')->merchant->enableMethod('LiveAccountMer', Method::UPI);
        $this->fixtures->on('live')->merchant->addFeatures(['qr_codes', 'bharat_qr_v2', 'bharat_qr', 'omni_enabled'], 'LiveAccountMer');

        $this->fixtures->on('live')->merchant->edit('LiveAccountMer', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);
        $this->fixtures->on('live')->create('merchant_detail:sane', ['merchant_id' => 'LiveAccountMer']);
        $this->fixtures->on('live')->merchant->activate();

        // Mock splitz to return 'on' for qr code create refactor experiment
        $this->setMockSplitzTreatment(
            [
                $this->config->get('app.qr_code_create_refactor_gateway') => 'on',
                $this->config->get('app.qr_payment_refactor_gateway')=> 'on',
            ]
        );

        //WARN: Remember to mock Mozart in each test, or else we shall start making network calls!!
        $this->config['applications.mozart.mock'] = false;

        $this->config['gateway.mock_upi_mozart'] = true;

        $this->createPricingForOffline();
        $this->createPricingForOfflineUnexpected();

    }
    public function createPricingForOffline()
    {
        $posQRPricingPlan = [
            'plan_id' => '1hDYlICobzOCYt',
            'plan_name' => 'TestMerchantPosUPIPricingPlan1',
            'payment_method' => 'upi',
            'org_id' => '100000razorpay',
            'type' => 'pricing',
            'feature' => 'payment',
            'receiver_type' => 'offline',
            'fee_bearer' => 'platform',
            'percent_rate' => 0,
            'fixed_rate' => 0,
            'channel' => 'in_person',
        ];

        $this->fixtures->create('pricing', $posQRPricingPlan);
    }

    public function createPricingForOfflineUnexpected()
    {
        $posQRPricingPlan = [
            'plan_id' => '1hDYlICobzOCYt',
            'plan_name' => 'TestMerchantPosUPIPricingPlan1',
            'payment_method' => 'upi',
            'org_id' => '100000razorpay',
            'type' => 'pricing',
            'feature' => 'payment',
            'receiver_type' => null,
            'fee_bearer' => 'platform',
            'percent_rate' => 0,
            'fixed_rate' => 0,
            'channel' => 'in_person',
        ];

        $this->fixtures->create('pricing', $posQRPricingPlan);
    }

    public function mockMozartResponse(&$count = 0,$res = null)
    {
        $this->mozartMock = \Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('mozart', $this->mozartMock);

        $this->mozartMock
            ->shouldReceive('sendRawRequest')
            ->andReturnUsing(
                function ($request) use ($res, &$count) {
                    ++$count;
                    $reqArray = json_decode($request['content'], true);


                    if (is_null($res) === false)
                    {
                        return json_encode($res);
                    }

                    return json_encode([
                        'success' => true,
                        'error'   => null,
                        'data'    => [
                            'qr_code' => [
                                'qr_string' => $this->mintoakBqrString,
                                'reference' => $reqArray['qr_code']['id']. 'qrv2',
                            ],
                        ],
                    ]);
                }
            );
    }

    public function testUpiHdfcMintoakMultipleUseCreateUPIQrCode()
    {
        $this->fixtures->on('live')->create('terminal:dedicated_upi_hdfcmintoak_terminal');
        $count = 0;
        $this->mockUpiQrMozartResponse();
        $this->createMerchantQrCode(
            [
                'merchant_id'    => 'LiveAccountMer',
                'request_source' => 'ezetap',
                'tid' => '222333'
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCode = $this->getDbLastEntity('qr_code', 'live');

        $this->assertEquals('LiveAccountMer', $qrCode->getMerchantId());
        $this->assertEquals('upi_qr', $qrCode->getProvider());
        $this->assertEquals('multiple_use', $qrCode->getUsageType());
        $this->assertEquals('active', $qrCode->getStatus());

    }
    public function testHdfcMintoakSingleUseCreateBharatQrCode()
    {
        $this->fixtures->on('live')->create('terminal:dedicated_upi_hdfcmintoak_terminal');
        $count = 0;
        $this->mockUpiQrMozartResponse(
            count: $count,
        );

        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'bharat_qr',
                'fixed_amount'   => true,
                'payment_amount' => 100,
                'request_source' => 'ezetap',
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCode = $this->getDbLastEntity('qr_code', 'live');

        $this->assertEquals(100, $qrCode->getAmount());
        $this->assertEquals('LiveAccountMer', $qrCode->getMerchantId());
        $this->assertEquals('bharat_qr', $qrCode->getProvider());
        $this->assertEquals('single_use', $qrCode->getUsageType());
        $this->assertEquals('active', $qrCode->getStatus());
        $this->assertEquals(['hdfc_mintoak_tid' => '222333'], $qrCode->getNotes()->toArray());


        // This asserts that we are actually using the response from Mozart

        $this->assertEquals($this->mintoakBqrString, $qrCode->getQrString());

        // This asserts that calls are going to the mock Mozart layer properly
        $this->assertEquals(1, $count);
    }

    public function testUpiHdfcMintoakCreateBharatQrCodeFlowWithAllGatewaysDown()
    {
        $this->fixtures->on('live')->create('terminal:dedicated_upi_hdfcmintoak_terminal');

        $this->mozartMock = \Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('mozart', $this->mozartMock);

        $this->mozartMock
            ->shouldReceive('sendRawRequest')
            ->andReturnUsing(
                function ($request) use (&$count) {
                    return json_encode([
                        'success' => false,
                        'error'   => [
                            'gateway_error_code'        => 'RandomErrorCode',
                            'gateway_error_description' => 'Gateway faced a random error',
                        ],
                        'data'    => [],
                    ]);
                }
            );

        $this->expectException(BadRequestException::class);
        $this->expectExceptionCode('SERVER_ERROR_MOZART_SERVICE_GATEWAY_ERROR');
        $this->expectExceptionMessage('We are facing some trouble completing your request at the moment. Please try again shortly.');

        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'bharat_qr',
                'fixed_amount'   => true,
                'payment_amount' => 100,
                'request_source' => 'ezetap'
            ],
            'live',
            'LiveAccountMer'
        );
    }

     public function testUpiHdfcMindgateCreateBharatQrCodeMultipleUse()
    {
        $this->fixtures->on('live')->create('terminal:dedicated_upi_mindgate_terminal');


        $this->setMockSplitzTreatment(
            [
                $this->config->get('app.qr_code_create_refactor_gateway') => 'off',
                $this->config->get('app.qr_payment_refactor_existing_gateway')=> 'off',
            ]
        );


        $count = 0;
        $this->mockMozartResponse(
            count: $count,
        );
        $this->createQrCode(
            [
                'usage'          => 'multiple_use',
                'type'           => 'bharat_qr',
                'fixed_amount'   => true,
                'payment_amount' => 100,
                'request_source' => 'ezetap'
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCode = $this->getDbLastEntity('qr_code', 'live');
        $this->assertEquals(100, $qrCode->getAmount());
        $this->assertEquals('LiveAccountMer', $qrCode->getMerchantId());
        $this->assertEquals('bharat_qr', $qrCode->getProvider());
        $this->assertEquals('multiple_use', $qrCode->getUsageType());
        $this->assertEquals('active', $qrCode->getStatus());

        $this->assertBqrString($qrCode, $qrCode->getQrString(),true);
    }

    public function testUpiHdfcMindgateCreateBharatQrCodeSingleUse()
    {
        $this->fixtures->on('live')->create('terminal:dedicated_upi_mindgate_terminal');
        $this->setMockSplitzTreatment(
            [
                $this->config->get('app.qr_code_create_refactor_gateway') => 'off',
                $this->config->get('app.qr_payment_refactor_gateway')=> 'off',
            ]
        );
        $count = 0;
        $this->mockMozartResponse(
            count: $count,
        );
        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'bharat_qr',
                'fixed_amount'   => true,
                'payment_amount' => 100,
                'request_source' => 'ezetap'
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCode = $this->getDbLastEntity('qr_code', 'live');
        $this->assertEquals(100, $qrCode->getAmount());
        $this->assertEquals('LiveAccountMer', $qrCode->getMerchantId());
        $this->assertEquals('bharat_qr', $qrCode->getProvider());
        $this->assertEquals('single_use', $qrCode->getUsageType());
        $this->assertEquals('active', $qrCode->getStatus());

        $this->assertBqrString($qrCode, $qrCode->getQrString(),false);
    }

    public function testCreateBqrPaymentViaRefactorFlowForHdfcMintoakMultipleUse()
    {
        $this->markTestSkipped('We are not using refactor flow for mintoak sqr');
        $this->fixtures->on('live')->create('terminal:dedicated_upi_hdfcmintoak_terminal');

        // These are used during assertions at the end of the test
        $count = 0;

        $this->mockMozartResponse(
            count: $count
        );

        $this->createQrCode(
            [
                'usage'          => 'multiple_use',
                'type'           => 'bharat_qr',
                'fixed_amount'   => true,
                'payment_amount' => 100,
                'request_source' => 'ezetap',
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCode = $this->getDbLastEntity('qr_code', 'live');

        $this->mockMozartResponse(
            count: $count,
            res  : [
                'success' => true,
                'error'   => null,
                'data'    => [
                    '_raw' => 'RandomRawData',
                    'upi'      => [
                        'vpa'                => 'payervpa@upi',
                        'merchant_reference' => $qrCode->getId().'qrv2',
                        'npci_reference_id'  => 'RndmNpciRefId',
                        'gateway_timestamp'  => 1722114963,
                    ],
                    'payment'  => [
                        'currency' => 'INR',
                        'amount_authorized'  => 100,
                        'payer_account_type' => 'bank_account',
                    ],
                    'terminal' => [
                        'gateway'             => 'hdfc_mintoak',
                        'vpa'                 => 'test@mintoak',
                    ],
                ],
            ]
        );

        $this->ba->directAuth();

        $response = $this->makeRequestAndGetContent(
            [
                'method' => 'POST',
                'url' => '/callback/hdfc_mintoak',
                'content' => [
                    'data' => 'RandomEncryptedUnPreProcessedDataForUpiAxisUpiOrQrPaymentCallback',
                ]
            ]
        );

        $this->assertEquals('SUCCESS', $response['status']);
        $this->assertNull($response['error_message']);

        $qrpRequest = $this->getDbLastEntity('qr_payment_request', 'live');
        $qrPayment = $this->getDbLastEntity('qr_payment', 'live');
        $payment = $this->getDbLastEntity('payment', 'live');
        $qrCode = $this->getDbEntity('qr_code', ['id' => $qrCode->getId()], 'live');
        $upi = $this->getDbLastEntity('upi', 'live');

        $this->assertEquals(1, $qrpRequest->isCreated());
        $this->assertEquals(1, $qrpRequest->expected);
        $this->assertEquals($qrCode->getId(), $qrpRequest->getQrCodeId());
        $this->assertEquals('RndmNpciRefId', $qrpRequest->getTransactionReference());
        $this->assertEmpty($qrpRequest->getFailureReason());

        $this->assertTrue($qrPayment->expected);
        $this->assertEquals($qrCode->getId(), $qrPayment->qrCode->getId());
        $this->assertEquals('RndmNpciRefId', $qrPayment->getProviderReferenceId());
        $this->assertEquals('hdfc_mintoak', $qrPayment->getGateway());
        $this->assertEquals($payment->getId(), $qrPayment->getPaymentId());
        $this->assertEquals(100, $qrPayment->getAmount());
        $this->assertEquals($qrCode->getId().'qrv2', $qrPayment->getMerchantReference());
        $this->assertEquals('payervpa@upi', $qrPayment->getAttribute('payer_vpa'));
        $this->assertNotNull($qrPayment->getTransactionTime());

        $this->assertEquals('RndmNpciRefId', $payment->getReference16());
        $this->assertEquals('qr_code', $payment->getReceiverType());
        $this->assertEquals($qrCode->getId(), $payment->receiver->getId());
        $this->assertEquals('captured', $payment->getStatus());
        $this->assertEquals('payervpa@upi', $payment->getVpa());
        $this->assertEquals('hdfc_mintoak', $payment->getGateway());
        $this->assertEquals('100HdfcMtkTrmn', $payment->getTerminalId());
        $this->assertEquals('in_person', $payment->getReference13());
        $this->assertEquals('bank_account', $payment->getReference2());
        $this->assertEquals(100, $payment->getAmount());

        $this->assertEquals('hdfc_mintoak', $upi->getGateway());
        $this->assertEquals($payment->getId(), $upi->getPaymentId());
        $this->assertEquals('authorize', $upi->getAction());
        $this->assertEquals('pay', $upi->getType());
        $this->assertEquals(100, $upi->getAmount());
        $this->assertEquals('payervpa@upi', $upi->getVpa());
        $this->assertEquals($qrCode->getId().'qrv2', $upi->getMerchantReference());
        $this->assertEquals('RndmNpciRefId', $upi->getNpciReferenceId());
        $this->assertEquals('mozart', $upi->getAttribute('acquirer'));

        $this->assertEquals('active', $qrCode->getStatus());
        $this->assertEquals(100, $qrCode->getAttribute('payments_amount_received'));
        $this->assertEquals(1, $qrCode->getAttribute('payments_received_count'));
    }

    public function mockUpiQrMozartResponse(&$count = 0, $res = null)
    {
        $this->mozartMock = \Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('mozart', $this->mozartMock);

        $this->mozartMock
            ->shouldReceive('sendRawRequest')
            ->andReturnUsing(
                function ($request) use (&$count, $res) {
                    ++$count;

                    if (is_null($res) === false)
                    {
                        return json_encode($res);
                    }

                    $reqArray = json_decode($request['content'], true);

                    return json_encode([
                        'data' => [
                            'payment' => [
                                'currency' => 'INR',
                            ],
                            'qr_code' => [
                                'qr_string' => $this->mintoakBqrString,
                                'reference' => $reqArray['qr_code']['id'].'qrv2',
                            ],
                            'status' => 'intent_inititated',
                            'terminal' => [
                                'gateway' => 'hdfc_mintoak',
                                'gateway_merchant_id' => 'LiveAccountMer',
                                'vpa' => '',
                            ],
                            'upi' => [
                                'gateway_status_code' => 'created',
                                'merchant_reference' => $reqArray['qr_code']['id'].'qrv2',
                            ],
                        ],
                        'error' => null,
                        'next' => [
                            'intent_url' => 'upi://pay?ver=01&mode=19&pa=222333@mintoak&pn=AnsonAntony2&tr=RZPQPFF8zCxIPZ26kqrv2&cu=INR&mc=5817&qrMedium=04&tn=PaymenttoAnsonAntony2',
                        ],
                        'success' => true,
                    ]);
                }
            );
    }

    public function testCreateBQRPaymentViaRefactorFlowForHdfcMintoakSingleUse()
    {
        $this->fixtures->on('live')->create('terminal:dedicated_upi_hdfcmintoak_terminal');

        // These are used during assertions at the end of the test
        $count = 0;

        $this->mockMozartResponse(
            count: $count
        );

        $this->mockUpiQrMozartResponse();
        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'bharat_qr',
                'fixed_amount'   => true,
                'payment_amount' => 100,
                'request_source' => 'ezetap',
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCode = $this->getDbLastEntity('qr_code', 'live');

        $this->mockMozartResponse(
            count: $count,
            res  : [
                'success' => true,
                'error'   => null,
                'data'    => [
                    '_raw' => 'RandomRawData',
                    'upi'      => [
                        'vpa'                => '',
                        'merchant_reference' => $qrCode->getId().'qrv2',
                        'npci_reference_id'  => 'RndmNpciRefId',
                        'gateway_timestamp'  => 1722114963,
                        'gateway_amount' => 100,
                        'gateway_payment_id' => 'randompaymentid',
                    ],
                    'payment'  => [
                        'currency' => 'INR',
                        'amount_authorized'  => 100,
                        'payer_account_type' => 'bank_account',
                    ],
                    'terminal' => [
                        'gateway'             => 'hdfc_mintoak',
                        'gateway_merchant_id'                 => '222333',
                    ],
                ],
            ]
        );

        $this->ba->directAuth();

        $response = $this->makeRequestAndGetContent(
            [
                'method' => 'POST',
                'url' => '/callback/hdfc_mintoak',
                'content' => [
                    'transactionDetail' => 'RandomEncryptedUnPreProcessedDataForUpiAxisUpiOrQrPaymentCallback',
                    'terminalId' => '222333',
                ]
            ]
        );

        $this->assertEquals('SUCCESS', $response['status']);
        $this->assertNull($response['error_message']);

        $qrpRequest = $this->getDbLastEntity('qr_payment_request', 'live');
        $qrPayment = $this->getDbLastEntity('qr_payment', 'live');
        $payment = $this->getDbLastEntity('payment', 'live');
        $qrCode = $this->getDbEntity('qr_code', ['id' => $qrCode->getId()], 'live');
        $upi = $this->getDbLastEntity('upi', 'live');

        $this->assertEquals(1, $qrpRequest->isCreated());
        $this->assertEquals(1, $qrpRequest->expected);
        $this->assertEquals($qrCode->getId(), $qrpRequest->getQrCodeId());
        $this->assertEquals('RndmNpciRefId', $qrpRequest->getTransactionReference());
        $this->assertEmpty($qrpRequest->getFailureReason());

        $this->assertTrue($qrPayment->expected);
        $this->assertEquals($qrCode->getId(), $qrPayment->qrCode->getId());
        $this->assertEquals('RndmNpciRefId', $qrPayment->getProviderReferenceId());
        $this->assertEquals('hdfc_mintoak', $qrPayment->getGateway());
        $this->assertEquals($payment->getId(), $qrPayment->getPaymentId());
        $this->assertEquals(100, $qrPayment->getAmount());
        $this->assertEquals($qrCode->getId().'qrv2', $qrPayment->getMerchantReference());
        $this->assertEquals('dummyhdfcmintoak@vpa', $qrPayment->getAttribute('payer_vpa'));
        $this->assertNotNull($qrPayment->getTransactionTime());

        $this->assertEquals('RndmNpciRefId', $payment->getReference16());
        $this->assertEquals('qr_code', $payment->getReceiverType());
        $this->assertEquals($qrCode->getId(), $payment->receiver->getId());
        $this->assertEquals('captured', $payment->getStatus());
        $this->assertEquals('dummyhdfcmintoak@vpa', $payment->getVpa());
        $this->assertEquals('hdfc_mintoak', $payment->getGateway());
        $this->assertEquals('100HdfcMtkTrmn', $payment->getTerminalId());
        $this->assertEquals('in_person', $payment->getReference13());
        $this->assertEquals('bank_account', $payment->getReference2());
        $this->assertEquals(100, $payment->getAmount());

        $this->assertEquals('hdfc_mintoak', $upi->getGateway());
        $this->assertEquals($payment->getId(), $upi->getPaymentId());
        $this->assertEquals('authorize', $upi->getAction());
        $this->assertEquals('pay', $upi->getType());
        $this->assertEquals(100, $upi->getAmount());
        $this->assertEquals('', $upi->getVpa());
        $this->assertEquals($qrCode->getId().'qrv2', $upi->getMerchantReference());
        $this->assertEquals('RndmNpciRefId', $upi->getNpciReferenceId());
        $this->assertEquals('mozart', $upi->getAttribute('acquirer'));

        $this->assertEquals('closed', $qrCode->getStatus());
        $this->assertEquals(100, $qrCode->getAttribute('payments_amount_received'));
        $this->assertEquals(1, $qrCode->getAttribute('payments_received_count'));
    }

    public function testCreateUPIQrPaymentForHdfcMintoakMultipleUse()
    {
        $this->fixtures->on('live')->create('terminal:dedicated_upi_hdfcmintoak_terminal');

        // These are used during assertions at the end of the test
        $count = 0;

        $this->mockUpiQrMozartResponse();
        $this->createMerchantQrCode(
            [
                'merchant_id'    => 'LiveAccountMer',
                'request_source' => 'ezetap',
                'tid' => '222333'
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCode = $this->getDbLastEntity('qr_code', 'live');

        $this->mockMozartResponse(
            count: $count,
            res  : [
                'success' => true,
                'error'   => null,
                'data'    => [
                    '_raw' => 'RandomRawData',
                    'upi'      => [
                        'vpa'                => '',
                        'merchant_reference' => $qrCode->getId().'qrv2',
                        'npci_reference_id'  => 'RndmNpciRefId',
                        'gateway_timestamp'  => 1722114963,
                        'gateway_amount' => 100,
                        'gateway_payment_id' => 'randompaymentid',
                    ],
                    'payment'  => [
                        'currency' => 'INR',
                        'amount_authorized'  => 100,
                        'payer_account_type' => 'bank_account',
                    ],
                    'terminal' => [
                        'gateway'             => 'hdfc_mintoak',
                        'gateway_merchant_id'                 => '222333',
                    ],
                ],
            ]
        );

        $this->ba->directAuth();

        $response = $this->makeRequestAndGetContent(
            [
                'method' => 'POST',
                'url' => '/callback/hdfc_mintoak',
                'content' => [
                    'transactionDetail' => 'RandomEncryptedUnPreProcessedDataForUpiAxisUpiOrQrPaymentCallback',
                    'terminalId' => '222333',
                ]
            ]
        );

        $this->assertEquals('SUCCESS', $response['status']);
        $this->assertNull($response['error_message']);

        $qrpRequest = $this->getDbLastEntity('qr_payment_request', 'live');
        $qrPayment = $this->getDbLastEntity('qr_payment', 'live');
        $payment = $this->getDbLastEntity('payment', 'live');
        $qrCode = $this->getDbEntity('qr_code', ['id' => $qrCode->getId()], 'live');
        $upi = $this->getDbLastEntity('upi', 'live');

        $this->assertEquals(1, $qrpRequest->isCreated());
        $this->assertEquals(1, $qrpRequest->expected);
        $this->assertEquals($qrCode->getId(), $qrpRequest->getQrCodeId());
        $this->assertEquals('RndmNpciRefId', $qrpRequest->getTransactionReference());
        $this->assertEmpty($qrpRequest->getFailureReason());

        $this->assertTrue($qrPayment->expected);
        $this->assertEquals($qrCode->getId(), $qrPayment->qrCode->getId());
        $this->assertEquals('RndmNpciRefId', $qrPayment->getProviderReferenceId());
        $this->assertEquals('hdfc_mintoak', $qrPayment->getGateway());
        $this->assertEquals($payment->getId(), $qrPayment->getPaymentId());
        $this->assertEquals(100, $qrPayment->getAmount());
        $this->assertEquals($qrCode->getId().'qrv2', $qrPayment->getMerchantReference());
        $this->assertEquals('dummyhdfcmintoak@vpa', $qrPayment->getAttribute('payer_vpa'));
        $this->assertNotNull($qrPayment->getTransactionTime());

        $this->assertEquals('RndmNpciRefId', $payment->getReference16());
        $this->assertEquals('qr_code', $payment->getReceiverType());
        $this->assertEquals($qrCode->getId(), $payment->receiver->getId());
        $this->assertEquals('captured', $payment->getStatus());
        $this->assertEquals('dummyhdfcmintoak@vpa', $payment->getVpa());
        $this->assertEquals('hdfc_mintoak', $payment->getGateway());
        $this->assertEquals('100HdfcMtkTrmn', $payment->getTerminalId());
        $this->assertEquals('in_person', $payment->getReference13());
        $this->assertEquals('bank_account', $payment->getReference2());
        $this->assertEquals(100, $payment->getAmount());

        $this->assertEquals('hdfc_mintoak', $upi->getGateway());
        $this->assertEquals($payment->getId(), $upi->getPaymentId());
        $this->assertEquals('authorize', $upi->getAction());
        $this->assertEquals('pay', $upi->getType());
        $this->assertEquals(100, $upi->getAmount());
        $this->assertEquals('', $upi->getVpa());
        $this->assertEquals($qrCode->getId().'qrv2', $upi->getMerchantReference());
        $this->assertEquals('RndmNpciRefId', $upi->getNpciReferenceId());
        $this->assertEquals('mozart', $upi->getAttribute('acquirer'));

        $this->assertEquals('active', $qrCode->getStatus());
        $this->assertEquals(100, $qrCode->getAttribute('payments_amount_received'));
        $this->assertEquals(1, $qrCode->getAttribute('payments_received_count'));
    }

    public function testCreateDuplicateBQrPaymentViaRefactorFlowForHdfcMintoak()
    {

        // These are used during assertions at the end of the test
        $count = 0;

        $this->testCreateBQrPaymentViaRefactorFlowForHdfcMintoakSingleUse();

        $qrCode = $this->getDbLastEntity('qr_code', 'live');

        $countOfQrPaymentBefore = count($this->getDbEntities(entity: 'qr_payment', mode: 'live'));
        $countOfQrPaymentRequestBefore = count($this->getDbEntities(entity: 'qr_payment_request', mode: 'live'));
        $countOfPaymentBefore = count($this->getDbEntities(entity: 'payment', mode: 'live'));
        $countOfUpiEntityBefore = count($this->getDbEntities(entity: 'upi', mode: 'live'));

        $this->mockMozartResponse(
            count: $count,
            res  : [
                'success' => true,
                'error'   => null,
                'data'    => [
                    '_raw' => 'RandomRawData',
                    'upi'      => [
                        'vpa'                => 'payervpa@upi',
                        'merchant_reference' => $qrCode->getId() . 'qrv2',
                        'npci_reference_id'  => 'RndmNpciRefId',
                        'gateway_timestamp'  => 1722114963,
                    ],
                    'payment'  => [
                        'currency' => 'INR',
                        'amount_authorized'  => 100,
                        'payer_account_type' => 'bank_account',
                    ],
                    'terminal' => [
                        'gateway'             => 'hdfc_mintoak',
                        'gateway_merchant_id'                 => '222333',
                    ],
                ],
            ]
        );

        $this->ba->directAuth();

        $response = $this->makeRequestAndGetContent(
            [
                'method' => 'POST',
                'url' => '/callback/hdfc_mintoak',
                'content' => [
                    'transactionDetail' => 'RandomEncryptedUnPreProcessedDataForUpiAxisUpiOrQrPaymentCallback',
                    'terminalId' => '222333',
                ]
            ]
        );

        $countOfQrPaymentAfter = count($this->getDbEntities(entity: 'qr_payment', mode: 'live'));
        $countOfQrPaymentRequestAfter = count($this->getDbEntities(entity: 'qr_payment_request', mode: 'live'));
        $countOfPaymentAfter = count($this->getDbEntities(entity: 'payment', mode: 'live'));
        $countOfUpiEntityAfter = count($this->getDbEntities(entity: 'upi', mode: 'live'));

        $this->assertEquals($countOfQrPaymentRequestBefore + 1, $countOfQrPaymentRequestAfter);
        $this->assertEquals($countOfQrPaymentBefore, $countOfQrPaymentAfter);
        $this->assertEquals($countOfPaymentBefore, $countOfPaymentAfter);
        $this->assertEquals($countOfUpiEntityBefore, $countOfUpiEntityAfter);

        $this->assertEquals('SUCCESS', $response['status']);
        $this->assertNull($response['error_message']);

        $qrpRequest = $this->getDbLastEntity('qr_payment_request', 'live');
        $qrCode = $this->getDbEntity('qr_code', ['id' => $qrCode->getId()], 'live');

        $this->assertEquals(0, $qrpRequest->isCreated());
        $this->assertEquals(0, $qrpRequest->expected);
        $this->assertEquals('QR_PAYMENT_DUPLICATE_NOTIFICATION', $qrpRequest->getFailureReason());
        $this->assertEquals($qrCode->getId(), $qrpRequest->getQrCodeId());
        $this->assertEquals('RndmNpciRefId', $qrpRequest->getTransactionReference());
    }

    public function testCreateBQrPaymentViaRefactorFlowWithAmountMismatchForHdfcMintoak()
    {
        $this->fixtures->on('live')->create('terminal:dedicated_upi_hdfcmintoak_terminal');

        // These are used during assertions at the end of the test
        $count = 0;

        $this->mockUpiQrMozartResponse(
            count: $count
        );

        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'bharat_qr',
                'fixed_amount'   => true,
                'payment_amount' => 100,
                'request_source' => 'ezetap',
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCode = $this->getDbLastEntity('qr_code', 'live');

        $this->mockMozartResponse(
            count: $count,
            res  : [
                'success' => true,
                'error'   => null,
                'data'    => [
                    '_raw' => 'RandomRawData',
                    'upi'      => [
                        'vpa'                => 'payervpa@upi',
                        'merchant_reference' => $qrCode->getId() . 'qrv2',
                        'npci_reference_id'  => 'RndmNpciRefId',
                        'gateway_timestamp'  => 1722114963,
                    ],
                    'payment'  => [
                        'currency' => 'INR',
                        'amount_authorized'  => 150,
                        'payer_account_type' => 'bank_account',
                    ],
                    'terminal' => [
                        'gateway'             => 'hdfc_mintoak',
                        'gateway_merchant_id'                 => '222333',
                    ],
                ],
            ]
        );

        $this->ba->directAuth();

        $response = $this->makeRequestAndGetContent(
            [
                'method' => 'POST',
                'url' => '/callback/hdfc_mintoak',
                'content' => [
                    'transactionDetail' => 'RandomEncryptedUnPreProcessedDataForUpiAxisUpiOrQrPaymentCallback',
                    'terminalId' => '222333',
                ]
            ]
        );

        $this->assertEquals('SUCCESS', $response['status']);
        $this->assertNull($response['error_message']);

        $qrpRequest = $this->getDbLastEntity('qr_payment_request', 'live');
        $qrPayment = $this->getDbLastEntity('qr_payment', 'live');
        $payment = $this->getDbLastEntity('payment', 'live');
        $qrCode = $this->getDbEntity('qr_code', ['id' => $qrCode->getId()], 'live');
        $upi = $this->getDbLastEntity('upi', 'live');

        $this->assertEquals(1, $qrpRequest->isCreated());
        $this->assertEquals(0, $qrpRequest->expected);
        $this->assertEquals($qrCode->getId(), $qrpRequest->getQrCodeId());
        $this->assertEquals('RndmNpciRefId', $qrpRequest->getTransactionReference());
        $this->assertEmpty($qrpRequest->getFailureReason());

        $this->assertFalse($qrPayment->expected);
        $this->assertEquals($qrCode->getId(), $qrPayment->qrCode->getId());
        $this->assertEquals('RndmNpciRefId', $qrPayment->getProviderReferenceId());
        $this->assertEquals('hdfc_mintoak', $qrPayment->getGateway());
        $this->assertEquals($payment->getId(), $qrPayment->getPaymentId());
        $this->assertEquals(150, $qrPayment->getAmount());
        $this->assertEquals($qrCode->getId() . 'qrv2', $qrPayment->getMerchantReference());
        $this->assertEquals('payervpa@upi', $qrPayment->getAttribute('payer_vpa'));
        $this->assertNotNull($qrPayment->getTransactionTime());
        $this->assertEquals('Actual payment amount does not match expected payment amount', $qrPayment->getAttribute('unexpected_reason'));

        $this->assertEquals('RndmNpciRefId', $payment->getReference16());
        $this->assertEquals('qr_code', $payment->getReceiverType());
        $this->assertEquals($qrCode->getId(), $payment->receiver->getId());
        $this->assertEquals('refunded', $payment->getStatus());
        $this->assertEquals('payervpa@upi', $payment->getVpa());
        $this->assertEquals('hdfc_mintoak', $payment->getGateway());
        $this->assertEquals('100HdfcMtkTrmn', $payment->getTerminalId());
        $this->assertEquals('in_person', $payment->getReference13());
        $this->assertEquals('bank_account', $payment->getReference2());
        $this->assertEquals(150, $payment->getAmount());

        $this->assertEquals('hdfc_mintoak', $upi->getGateway());
        $this->assertEquals($payment->getId(), $upi->getPaymentId());
        $this->assertEquals('authorize', $upi->getAction());
        $this->assertEquals('pay', $upi->getType());
        $this->assertEquals(150, $upi->getAmount());
        $this->assertEquals('payervpa@upi', $upi->getVpa());
        $this->assertEquals($qrCode->getId() . 'qrv2', $upi->getMerchantReference());
        $this->assertEquals('RndmNpciRefId', $upi->getNpciReferenceId());
        $this->assertEquals('mozart', $upi->getAttribute('acquirer'));
    }

    public function testCreateBQrPaymentViaRefactorFlowForHdfcMindgateMultipleUse()
    {
        $terminal = $this->fixtures->on('live')->create('terminal:dedicated_upi_mindgate_terminal');

        $this->setMockRazorxTreatment(['api_upi_mindgate_pre_process_v1' => 'upi_mindgate']);

        $this->setMockSplitzTreatment(
            [
                $this->config->get('app.qr_code_create_refactor_gateway') => 'off',
                $this->config->get('app.qr_payment_refactor_existing_gateway')=> 'off',
            ]
        );
        $this->createQrCode(
            [
                'usage'          => 'multiple_use',
                'type'           => 'bharat_qr',
                'fixed_amount'   => true,
                'payment_amount' => 100,
                'request_source' => 'ezetap',
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCodeEntity = $this->getDbLastEntity('qr_code', 'live');
        $response = $this->makeUpiMindgatePayment(
            $qrCodeEntity,
            $terminal,
            [   'amount'      => '100',
                'vpa'         => 'razorpay@hdfcbank',
            ],
            [   'vpa'         => 'razorpay@hdfcbank',
            ]

        );
        $this->ba->directAuth();
        $this->assertEquals('SUCCESS', $response['success']);
        $this->assertNull($response['error_message']);

        $qrpRequest = $this->getDbLastEntity('qr_payment_request', 'live');
        $qrPayment = $this->getDbLastEntity('qr_payment', 'live');
        $payment = $this->getDbLastEntity('payment', 'live');
        $qrCode = $this->getDbEntity('qr_code', ['id' => $qrCodeEntity->getId()], 'live');
        $upi = $this->getDbLastEntity('upi', 'live');

        $this->assertEquals(1, $qrpRequest->isCreated());
        $this->assertEquals(1, $qrpRequest->expected);
        $this->assertEquals($qrCode->getId(), $qrpRequest->getQrCodeId());
        $this->assertEquals(107611570997, $qrpRequest->getTransactionReference());
        $this->assertEmpty($qrpRequest->getFailureReason());

        $this->assertTrue($qrPayment->expected);
        $this->assertEquals($qrCode->getId(), $qrPayment->qrCode->getId());
        $this->assertEquals(107611570997, $qrPayment->getProviderReferenceId());
        $this->assertEquals('upi_mindgate', $qrPayment->getGateway());
        $this->assertEquals($payment->getId(), $qrPayment->getPaymentId());
        $this->assertEquals(100, $qrPayment->getAmount());
        $this->assertEquals($qrCode->getId(), $qrPayment->getMerchantReference());
        $this->assertEquals('razorpay@hdfcbank', $qrPayment->getAttribute('payer_vpa'));
        $this->assertNotNull($qrPayment->getTransactionTime());

        $this->assertEquals(107611570997, $payment->getReference16());
        $this->assertEquals('qr_code', $payment->getReceiverType());
        $this->assertEquals($qrCode->getId(), $payment->receiver->getId());
        $this->assertEquals('captured', $payment->getStatus());
        $this->assertEquals('razorpay@hdfcbank', $payment->getVpa());
        $this->assertEquals('upi_mindgate', $payment->getGateway());
        $this->assertEquals('100MinDedTrmnl', $payment->getTerminalId());
        $this->assertEquals('in_person', $payment->getReference13());
        $this->assertEquals('bank_account', $payment->getReference2());
        $this->assertEquals(100, $payment->getAmount());

        $this->assertEquals('upi_mindgate', $upi->getGateway());
        $this->assertEquals($payment->getId(), $upi->getPaymentId());
        $this->assertEquals('authorize', $upi->getAction());
        $this->assertEquals('pay', $upi->getType());
        $this->assertEquals(100, $upi->getAmount());
        $this->assertEquals('razorpay@hdfcbank', $upi->getVpa());
        $this->assertEquals($qrCode->getId().'qrv2', $upi->getMerchantReference());
        $this->assertEquals(107611570997, $upi->getNpciReferenceId());
        $this->assertEquals('hdfc', $upi->getAttribute('acquirer'));

        $this->assertEquals('active', $qrCode->getStatus());
        $this->assertEquals(100, $qrCode->getAttribute('payments_amount_received'));
        $this->assertEquals(1, $qrCode->getAttribute('payments_received_count'));
    }

    public function testCreateBQrPaymentViaRefactorFlowForHdfcMindgateSingleUse()
    {
        $terminal = $this->fixtures->on('live')->create('terminal:dedicated_upi_mindgate_terminal');

        $this->setMockRazorxTreatment(['api_upi_mindgate_pre_process_v1' => 'upi_mindgate']);
        $this->setMockSplitzTreatment(
            [
                $this->config->get('app.qr_code_create_refactor_gateway') => 'off',
                $this->config->get('app.qr_payment_refactor_existing_gateway')=> 'off',
            ]
        );
        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'bharat_qr',
                'fixed_amount'   => true,
                'payment_amount' => 100,
                'request_source' => 'ezetap',
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCodeEntity = $this->getDbLastEntity('qr_code', 'live');
        $response = $this->makeUpiMindgatePayment(
            $qrCodeEntity,
            $terminal,
            [   'amount'      => '100',
                'vpa'         => 'razorpay@hdfcbank',
            ],
            [   'vpa'         => 'razorpay@hdfcbank',
            ]

        );
        $this->ba->directAuth();

        $this->assertEquals('SUCCESS', $response['success']);
        $this->assertNull($response['error_message']);

        $qrpRequest = $this->getDbLastEntity('qr_payment_request', 'live');
        $qrPayment = $this->getDbLastEntity('qr_payment', 'live');
        $payment = $this->getDbLastEntity('payment', 'live');
        $qrCode = $this->getDbEntity('qr_code', ['id' => $qrCodeEntity->getId()], 'live');
        $upi = $this->getDbLastEntity('upi', 'live');

        $this->assertEquals(1, $qrpRequest->isCreated());
        $this->assertEquals(1, $qrpRequest->expected);
        $this->assertEquals($qrCode->getId(), $qrpRequest->getQrCodeId());
        $this->assertEquals(107611570997, $qrpRequest->getTransactionReference());
        $this->assertEmpty($qrpRequest->getFailureReason());

        $this->assertTrue($qrPayment->expected);
        $this->assertEquals($qrCode->getId(), $qrPayment->qrCode->getId());
        $this->assertEquals(107611570997, $qrPayment->getProviderReferenceId());
        $this->assertEquals('upi_mindgate', $qrPayment->getGateway());
        $this->assertEquals($payment->getId(), $qrPayment->getPaymentId());
        $this->assertEquals(100, $qrPayment->getAmount());
        $this->assertEquals($qrCode->getId(), $qrPayment->getMerchantReference());
        $this->assertEquals('razorpay@hdfcbank', $qrPayment->getAttribute('payer_vpa'));
        $this->assertNotNull($qrPayment->getTransactionTime());

        $this->assertEquals(107611570997, $payment->getReference16());
        $this->assertEquals('qr_code', $payment->getReceiverType());
        $this->assertEquals($qrCode->getId(), $payment->receiver->getId());
        $this->assertEquals('captured', $payment->getStatus());
        $this->assertEquals('razorpay@hdfcbank', $payment->getVpa());
        $this->assertEquals('upi_mindgate', $payment->getGateway());
        $this->assertEquals('100MinDedTrmnl', $payment->getTerminalId());
        $this->assertEquals('in_person', $payment->getReference13());
        $this->assertEquals('bank_account', $payment->getReference2());
        $this->assertEquals(100, $payment->getAmount());

        $this->assertEquals('upi_mindgate', $upi->getGateway());
        $this->assertEquals($payment->getId(), $upi->getPaymentId());
        $this->assertEquals('authorize', $upi->getAction());
        $this->assertEquals('pay', $upi->getType());
        $this->assertEquals(100, $upi->getAmount());
        $this->assertEquals('razorpay@hdfcbank', $upi->getVpa());
        $this->assertEquals($qrCode->getId().'qrv2', $upi->getMerchantReference());
        $this->assertEquals(107611570997, $upi->getNpciReferenceId());
        $this->assertEquals('hdfc', $upi->getAttribute('acquirer'));

        $this->assertEquals('closed', $qrCode->getStatus());
        $this->assertEquals(100, $qrCode->getAttribute('payments_amount_received'));
        $this->assertEquals(1, $qrCode->getAttribute('payments_received_count'));
    }

    public function testCreateBQrPaymentViaRefactorFlowWithAmountMismatchForHdfcMindgate()
    {
        $terminal = $this->fixtures->create('terminal:dedicated_upi_mindgate_terminal',);
        $this->setMockRazorxTreatment(['api_upi_mindgate_pre_process_v1' => 'upi_mindgate']);
        $this->setMockSplitzTreatment(
            [
                $this->config->get('app.qr_code_create_refactor_gateway') => 'off',
                $this->config->get('app.qr_payment_refactor_existing_gateway')=> 'off',
            ]
        );
        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'bharat_qr',
                'fixed_amount'   => true,
                'payment_amount' => 100,
                'request_source' => 'ezetap',
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCodeEntity = $this->getDbLastEntity('qr_code', 'live');
        $this->ba->directAuth();
        $response = $this->makeUpiMindgatePayment($qrCodeEntity, $terminal,
            [   'amount'      => '200',
                'vpa'         => 'razorpay@hdfcbank',
            ],
        );

        $this->assertEquals(true, $response['success']);
        $this->assertNull($response['error_message']);

        $qrpRequest = $this->getDbLastEntity('qr_payment_request', 'live');
        $qrPayment = $this->getDbLastEntity('qr_payment', 'live');
        $payment = $this->getDbLastEntity('payment', 'live');
        $qrCode = $this->getDbEntity('qr_code', ['id' => $qrCodeEntity->getId()], 'live');
        $upi = $this->getDbLastEntity('upi', 'live');

        $this->assertEquals(1, $qrpRequest->isCreated());
        $this->assertEquals(0, $qrpRequest->expected);
        $this->assertEquals($qrCode->getId(), $qrpRequest->getQrCodeId());
        $this->assertEquals(107611570997, $qrpRequest->getTransactionReference());
        $this->assertEmpty($qrpRequest->getFailureReason());

        $this->assertFalse($qrPayment->expected);
        $this->assertEquals($qrCode->getId(), $qrPayment->qrCode->getId());
        $this->assertEquals(107611570997, $qrPayment->getProviderReferenceId());
        $this->assertEquals('upi_mindgate', $qrPayment->getGateway());
        $this->assertEquals($payment->getId(), $qrPayment->getPaymentId());
        $this->assertEquals(200, $qrPayment->getAmount());
        $this->assertEquals($qrCode->getId(), $qrPayment->getMerchantReference());
        $this->assertEquals('razorpay@hdfcbank', $qrPayment->getAttribute('payer_vpa'));
        $this->assertNotNull($qrPayment->getTransactionTime());
        $this->assertEquals('Actual payment amount does not match expected payment amount', $qrPayment->getAttribute('unexpected_reason'));

        $this->assertEquals(107611570997, $payment->getReference16());
        $this->assertEquals('qr_code', $payment->getReceiverType());
        $this->assertEquals($qrCode->getId(), $payment->receiver->getId());
        $this->assertEquals('refunded', $payment->getStatus());
        $this->assertEquals('razorpay@hdfcbank', $payment->getVpa());
        $this->assertEquals('upi_mindgate', $payment->getGateway());
        $this->assertEquals('100MinDedTrmnl', $payment->getTerminalId());
        $this->assertEquals('in_person', $payment->getReference13());
        $this->assertEquals('bank_account', $payment->getReference2());
        $this->assertEquals(200, $payment->getAmount());

        $this->assertEquals('upi_mindgate', $upi->getGateway());
        $this->assertEquals($payment->getId(), $upi->getPaymentId());
        $this->assertEquals('authorize', $upi->getAction());
        $this->assertEquals('pay', $upi->getType());
        $this->assertEquals(200, $upi->getAmount());
        $this->assertEquals('razorpay@hdfcbank', $upi->getVpa());
        $this->assertEquals($qrCode->getId() . 'qrv2', $upi->getMerchantReference());
        $this->assertEquals(107611570997, $upi->getNpciReferenceId());
        $this->assertEquals('hdfc', $upi->getAttribute('acquirer'));
    }

    public function testQrStatusCheckViaRefactorFlowForMintoak()
    {
        $this->testHdfcMintoakSingleUseCreateBharatQrCode();
        $qrCode = $this->getDbLastEntity('qr_code', 'live');

        $qrCodeId = $qrCode['id'];

        $this->testData[__FUNCTION__]['request']['url'] = $this->testData[__FUNCTION__]['request']['url'] . $qrCodeId;

        putenv("IS_WORKER_POD=true");

        $this->ba->reminderAppAuth();

        $this->mockMozartResponse(
            count: $count,
            res  : [
                'success' => true,
                'error'   => null,
                'data'    => [
                    '_raw' => 'RandomRawData',
                    'upi'      => [
                        'vpa'                => 'payervpa@upi',
                        'merchant_reference' => str_after($qrCodeId, 'qr_') . 'qrv2',
                        'npci_reference_id'  => 'RndmNpciRefId',
                        'gateway_timestamp'  => 1722114963,
                    ],
                    'payment'  => [
                        'amount_authorized'  => 100,
                        'payer_account_type' => 'bank_account',
                    ],
                    'terminal' => [
                        'gateway'             => 'hdfc_mintoak',
                        'gateway_merchant_id' => '222333',
                    ],
                ],
            ]
        );

        $this->startTest();

        // To unset the env key variable
        putenv("IS_WORKER_POD");

        $qrpRequest = $this->getDbLastEntity('qr_payment_request', 'live');
        $qrPayment = $this->getDbLastEntity('qr_payment', 'live');
        $payment = $this->getDbLastEntity('payment', 'live');
        $qrCode = $this->getDbEntity('qr_code', ['id' => str_after($qrCodeId, 'qr_')], 'live');
        $upi = $this->getDbLastEntity('upi', 'live');

        $this->assertEquals(1, $qrpRequest->isCreated());
        $this->assertEquals(1, $qrpRequest->expected);
        $this->assertEquals($qrCode->getId(), $qrpRequest->getQrCodeId());
        $this->assertEquals('RndmNpciRefId', $qrpRequest->getTransactionReference());
        $this->assertEmpty($qrpRequest->getFailureReason());

        $this->assertTrue($qrPayment->expected);
        $this->assertEquals($qrCode->getId(), $qrPayment->qrCode->getId());
        $this->assertEquals('RndmNpciRefId', $qrPayment->getProviderReferenceId());
        $this->assertEquals('hdfc_mintoak', $qrPayment->getGateway());
        $this->assertEquals($payment->getId(), $qrPayment->getPaymentId());
        $this->assertEquals(100, $qrPayment->getAmount());
        $this->assertEquals($qrCode->getId() . 'qrv2', $qrPayment->getMerchantReference());
        $this->assertEquals('payervpa@upi', $qrPayment->getAttribute('payer_vpa'));
        $this->assertNotNull($qrPayment->getTransactionTime());

        $this->assertEquals('RndmNpciRefId', $payment->getReference16());
        $this->assertEquals('qr_code', $payment->getReceiverType());
        $this->assertEquals($qrCode->getId(), $payment->receiver->getId());
        $this->assertEquals('captured', $payment->getStatus());
        $this->assertEquals('payervpa@upi', $payment->getVpa());
        $this->assertEquals('hdfc_mintoak', $payment->getGateway());
        $this->assertEquals('100HdfcMtkTrmn', $payment->getTerminalId());
        $this->assertEquals('in_person', $payment->getReference13());
        $this->assertEquals('bank_account', $payment->getReference2());
        $this->assertEquals(100, $payment->getAmount());

        $this->assertEquals('hdfc_mintoak', $upi->getGateway());
        $this->assertEquals($payment->getId(), $upi->getPaymentId());
        $this->assertEquals('authorize', $upi->getAction());
        $this->assertEquals('pay', $upi->getType());
        $this->assertEquals(100, $upi->getAmount());
        $this->assertEquals('payervpa@upi', $upi->getVpa());
        $this->assertEquals($qrCode->getId() . 'qrv2', $upi->getMerchantReference());
        $this->assertEquals('RndmNpciRefId', $upi->getNpciReferenceId());
        $this->assertEquals('mozart', $upi->getAttribute('acquirer'));

        $this->assertEquals('closed', $qrCode->getStatus());
        $this->assertEquals(100, $qrCode->getAttribute('payments_amount_received'));
        $this->assertEquals(1, $qrCode->getAttribute('payments_received_count'));
    }

    public function testQrStatusCheckViaRefactorFlowForMindgate()
    {
        $this->config['gateway.mock_upi_mozart'] = true;
        $this->fixtures->create('terminal:dedicated_upi_mindgate_terminal',);
        $remindersCallCount = 0;
        $reminderDeleteCallCount = 0;
        $this->mockRemindersRequestForStatusCheck($remindersCallCount, false, $reminderDeleteCallCount);

        $this->mockSplitzTreatmentForStatusCheck();

        $previousCount = count($this->getDbEntities('qr_code', [], 'live'));
        $this->setMockRazorxTreatment(['api_upi_mindgate_pre_process_v1' => 'upi_mindgate']);

        $this->setMockSplitzTreatment(
            [
                $this->config->get('app.qr_code_create_refactor_gateway') => 'off',
                $this->config->get('app.qr_payment_refactor_existing_gateway')=> 'off',
            ]
        );
        $qrCode        = $this->createQrCode(
            [
                'type'           => 'bharat_qr',
                'usage'          => 'single_use',
                'fixed_amount'   => true,
                'payment_amount' => 4000,
                'request_source' => 'ezetap'
            ],
            'live',
            'LiveAccountMer'
        );
        $newCount      = count($this->getDbEntities('qr_code', [], 'live'));
        $this->assertEquals($previousCount + 1, $newCount);
        $qrCodeId = $qrCode['id'];

        $this->testData[__FUNCTION__]['request']['url'] = $this->testData[__FUNCTION__]['request']['url'] . $qrCodeId;

        $requestData['content']['BankRRN'] = '326414338959';
        $requestData['content']['merchantTranId'] = str_after($qrCodeId, 'qr_') . 'qrv2';

        $this->mockServerContentFunction(function (&$content, $action = null) use ($qrCodeId, $requestData) {
            if ($action === 'verify')
            {
                $content = $this->getMockedUpiMindgateQrStatusCheckResponse("00", $qrCodeId,
                    $requestData['content']['BankRRN'],'razorpay upi','45678765','4000','razorpay@hdfcbank');
            }
        }, 'upi_mozart');

        $this->ba->reminderAppAuth();

        $this->startTest();
        $this->runQrPaymentAssertions(str_after($qrCodeId, 'qr_'), $requestData, 'live');

        $this->assertEquals(1, $reminderDeleteCallCount);

    }

    public function testBatchServiceMapDevice()
    {
        $this->testUpiHdfcMindgateCreateBharatQrCodeMultipleUse();
        $actualEzetapNotificationCallCount =0 ;
        $eventList =[] ;
        $this->mockEzetapNotification($actualEzetapNotificationCallCount,$eventList);

        $qrCode = $this->getDbLastEntity('qr_code','live');
        $this->ba->appAuth();
        $response = $this->makeRequestAndGetContent([
            'method' => 'POST',
            'url' => '/payments/single_stack/device/update',
            'content' => [
                'device_id' => 'randomDeviceId',
                'map_identifiers' => [
                    'qr_string' => $qrCode->getQrString(),
                ],
            ]
        ]);

        $this->assertEquals('qr_'.$qrCode['id'],$response['id']);
        $this->assertEquals('randomDeviceId',$response['device_id']);

        $response = $this->makeRequestAndGetContent([
            'method' => 'POST',
            'url' => '/payments/single_stack/device/update',
            'content' => [
                'device_id' => 'randomDeviceId',
                'unmap_identifiers' => [
                    'unmap_from_merchant' => 'Yes',
                    'unmap_from_qr' => 'yes'
                ],
            ]
        ]);

        $qrCode->reload();

        $this->assertEquals(true,$response['success']);
        $this->assertEquals($qrCode['id'],$response['id']);
        $this->assertNull($qrCode->getDeviceId());
    }

    public function testBatchServiceMapDeviceForHdfcMintoakUpiQr()
    {
        $this->testUpiHdfcMintoakMultipleUseCreateUPIQrCode();
        $actualEzetapNotificationCallCount =0 ;
        $eventList =[] ;
        $this->mockEzetapNotification($actualEzetapNotificationCallCount,$eventList);

        $qrCode = $this->getDbLastEntity('qr_code','live');
        $this->ba->appAuth();
        $response = $this->makeRequestAndGetContent([
            'method' => 'POST',
            'url' => '/payments/single_stack/device/update',
            'content' => [
                'device_id' => 'randomDeviceId',
                'map_identifiers' => [
                    'qr_string' => $qrCode->getQrString(),
                ],
            ]
        ]);

        $this->assertEquals('qr_'.$qrCode['id'],$response['id']);
        $this->assertEquals('randomDeviceId',$response['device_id']);

        $response = $this->makeRequestAndGetContent([
            'method' => 'POST',
            'url' => '/payments/single_stack/device/update',
            'content' => [
                'device_id' => 'randomDeviceId',
                'unmap_identifiers' => [
                    'unmap_from_merchant' => 'Yes',
                    'unmap_from_qr' => 'yes'
                ],
            ]
        ]);

        $qrCode->reload();

        $this->assertEquals(true,$response['success']);
        $this->assertEquals($qrCode['id'],$response['id']);
        $this->assertNull($qrCode->getDeviceId());
    }

    public function testBatchServiceMapDeviceForHdfcMintoakBharatQr()
    {
        $this->fixtures->on('live')->create('terminal:dedicated_upi_mindgate_terminal');


        $this->setMockSplitzTreatment(
            [
                $this->config->get('app.qr_code_create_refactor_gateway') => 'off',
                $this->config->get('app.qr_payment_refactor_existing_gateway')=> 'off',
            ]
        );


        $count = 0;
        $this->mockMozartResponse(
            count: $count,
        );
        $this->createQrCode(
            [
                'usage'          => 'multiple_use',
                'type'           => 'bharat_qr',
                'fixed_amount'   => true,
                'payment_amount' => 100,
                'request_source' => 'ezetap'
            ],
            'live',
            'LiveAccountMer'
        );

        $actualEzetapNotificationCallCount =0 ;
        $eventList =[] ;
        $this->mockEzetapNotification($actualEzetapNotificationCallCount,$eventList);

        $qrCode = $this->getDbLastEntity('qr_code','live');
        $this->ba->appAuth();
        $response = $this->makeRequestAndGetContent([
            'method' => 'POST',
            'url' => '/payments/single_stack/device/update',
            'content' => [
                'device_id' => 'randomDeviceId',
                'map_identifiers' => [
                    'qr_string' => $qrCode->getQrString(),
                ],
            ]
        ]);

        $this->assertEquals('qr_'.$qrCode['id'],$response['id']);
        $this->assertEquals('randomDeviceId',$response['device_id']);

        $response = $this->makeRequestAndGetContent([
            'method' => 'POST',
            'url' => '/payments/single_stack/device/update',
            'content' => [
                'device_id' => 'randomDeviceId',
                'unmap_identifiers' => [
                    'unmap_from_merchant' => 'Yes',
                    'unmap_from_qr' => 'yes'
                ],
            ]
        ]);

        $qrCode->reload();

        $this->assertEquals(true,$response['success']);
        $this->assertEquals($qrCode['id'],$response['id']);
        $this->assertNull($qrCode->getDeviceId());
    }
}
