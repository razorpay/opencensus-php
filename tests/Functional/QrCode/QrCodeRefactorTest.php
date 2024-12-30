<?php

namespace Functional\QrCode;

use RZP\Error\ErrorCode;
use RZP\Exception\IntegrationException;
use RZP\Services\Mozart;
use RZP\Models\Pricing\Fee;
use RZP\Models\Payment\Method;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\QrCode\NonVirtualAccountQrCodeTrait;

class QrCodeRefactorTest extends TestCase
{
    use DbEntityFetchTrait;
    use NonVirtualAccountQrCodeTrait;
    use PaymentTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/QrCodeRefactorTestData.php';

        parent::setUp();

        $this->fixtures->merchant->createAccount('LiveAccountMer');
        $this->fixtures->on('live')->merchant->edit('LiveAccountMer', ['activated' => true, 'live' => true]);
        $this->fixtures->on('live')->merchant->addFeatures(['qr_codes'], 'LiveAccountMer');
        $this->fixtures->on('live')->merchant->enableMethod('LiveAccountMer', 'upi');
        $this->fixtures->on('live')->merchant->edit('LiveAccountMer', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);
        $this->fixtures->on('live')->create('merchant_detail:sane', ['merchant_id' => 'LiveAccountMer']);

        // Although we are creating mock upi_yesbank and upi_icici terminal, we expect to use the new gateway module for QR create
        $this->fixtures->create(
            'terminal:dedicated_upi_yesbank_offline_terminal',
            [
                'id'          => '101YesDedTrmnl',
                'merchant_id' => 'LiveAccountMer',
                'vpa' => 'randomvpa@yesbank',
                'gateway_merchant_id' => 'RndmYesbnkGtwyMrchtId',
            ]
        );

        $this->fixtures->create(
            'terminal:dedicated_upi_icici_terminal',
            [
                'id'          => '11LiveAccTrmnl',
                'merchant_id' => 'LiveAccountMer',
                'gateway_merchant_id2' => 'randomvpa@icici',
                'gateway_merchant_id' => 'RndmIcicGtwyMrchtId',
                'type'                      => [
                    'pay'               => '1',
                    'non_recurring'     => '1',
                    'offline'           => '1',
                    'collect'           => '1',
                ],
            ]
        );

        $this->getDedicatedTerminalSplitzResponseForVariantON();

        // Mock Splitz to return 'on' for qr code create refactor experiment
        $this->setMockSplitzTreatment(
            [
                $this->config->get('app.qr_code_create_refactor_gateway') => 'on',
                $this->config->get('app.qr_payment_refactor_gateway')=> 'on',
            ]
        );

        //WARN: Remember to mock Mozart in each test, or else we shall start making network calls!!
        $this->config['applications.mozart.mock'] = false;

        $this->fixtures->merchant->createAccount(Account::DEMO_ACCOUNT);

        $this->fixtures->merchant->enableMethod(Account::DEMO_ACCOUNT, Method::UPI);

        $this->fixtures->merchant->activate();

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

    public function mockMozartResponse(&$count = 0, $res = null)
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
                                                   'qr_string' => 'RandomQrString',
                                                   'reference' => $reqArray['qr_code']['id'] . 'qrv2',
                                               ],
                                           ],
                                       ]);
                }
            );
    }

    public function testCreateQrCodeViaRefactorFlow()
    {
        // These are used during assertions at the end of the test
        $count = 0;

        $this->mockMozartResponse(
            count: $count
        );

        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 100,
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCode = $this->getDbLastEntity('qr_code', 'live');

        $this->assertEquals(100, $qrCode->getAmount());
        $this->assertEquals('LiveAccountMer', $qrCode->getMerchantId());
        $this->assertEquals('upi_qr', $qrCode->getProvider());
        $this->assertEquals('single_use', $qrCode->getUsageType());
        $this->assertEquals('active', $qrCode->getStatus());

        // This asserts that we are actually using the response from Mozart
        $this->assertEquals('RandomQrString', $qrCode->getQrString());

        // This asserts that calls are going to the mock Mozart layer properly
        $this->assertEquals(1, $count);
    }

    public function testCreateQrCodeViaRefactorFlowWithAllGatewaysDown()
    {
        // These are used during assertions at the end of the test
        $count = 0;

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
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 100,
            ],
            'live',
            'LiveAccountMer'
        );

        // No more assertions shall work as expectException shall catch the exception and finish asserting the
        // exception object and halt the test.
    }

    public function testCreateQrCodeViaRefactorFlowWithOneGatewayDown()
    {
        // These are used during assertions at the end of the test
        $count = 0;

        $this->mozartMock = \Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('mozart', $this->mozartMock);

        $this->mozartMock
            ->shouldReceive('sendRawRequest')
            ->andReturnUsing(
                function ($request) use (&$count) {
                    if ($count++ === 0)
                    {
                        return json_encode([
                                               'success' => false,
                                               'error'   => [
                                                   'gateway_error_code'        => 'RandomErrorCode',
                                                   'gateway_error_description' => 'Gateway faced a random error',
                                               ],
                                               'data'    => [],
                                           ]);
                    }

                    return json_encode([
                                           'success' => true,
                                           'error'   => null,
                                           'data'    => [
                                               'qr_code' => [
                                                   'qr_string' => 'RandomQrString2',
                                                   'reference' => $request['qr_code']['id'] . 'qrv2',
                                               ],
                                           ],
                                       ]);
                }
            );

        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 100,
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCode = $this->getDbLastEntity('qr_code', 'live');

        $this->assertEquals(100, $qrCode->getAmount());
        $this->assertEquals('LiveAccountMer', $qrCode->getMerchantId());
        $this->assertEquals('upi_qr', $qrCode->getProvider());
        $this->assertEquals('single_use', $qrCode->getUsageType());
        $this->assertEquals('active', $qrCode->getStatus());

        // This asserts that we are actually using the response from Mozart
        $this->assertEquals('RandomQrString2', $qrCode->getQrString());

        // This asserts that calls are going to the mock Mozart layer properly
        $this->assertEquals(2, $count);
    }

    public function testCreateQrPaymentViaRefactorFlow()
    {
        $this->fixtures->merchant->addFeatures(['omni_enabled'], 'LiveAccountMer');

        // These are used during assertions at the end of the test
        $count = 0;

        $this->mockMozartResponse(
            count: $count
        );

        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
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
                               'amount_authorized'  => 100,
                               'payer_account_type' => 'bank_account',
                           ],
                           'terminal' => [
                               'gateway'             => 'upi_yesbank',
                               'gateway_merchant_id' => 'RndmYesbnkGtwyMrchtId',
                               'vpa'                 => 'randomvpa@yesbank',
                           ],
                       ],
                   ]
        );

        $this->ba->directAuth();

        $response = $this->makeRequestAndGetContent(
            [
                'method' => 'POST',
                'url' => '/callback/upi_yesbank',
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
        $this->assertEquals('upi_yesbank', $qrPayment->getGateway());
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
        $this->assertEquals('upi_yesbank', $payment->getGateway());
        $this->assertEquals('101YesDedTrmnl', $payment->getTerminalId());
        $this->assertEquals('in_person', $payment->getReference13());
        $this->assertEquals('bank_account', $payment->getReference2());
        $this->assertEquals(100, $payment->getAmount());

        $this->assertEquals('upi_yesbank', $upi->getGateway());
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

    public function testCreateQrPaymentViaRefactorFlowForStaticQr()
    {
        $this->fixtures->merchant->addFeatures(['omni_enabled'], 'LiveAccountMer');

        // These are used during assertions at the end of the test
        $count = 0;

        $this->mockMozartResponse(
            count: $count
        );

        $this->createQrCode(
            [
                'usage'          => 'multiple_use',
                'type'           => 'upi_qr',
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
                               'amount_authorized'  => 100,
                               'payer_account_type' => 'bank_account',
                           ],
                           'terminal' => [
                               'gateway'             => 'upi_yesbank',
                               'gateway_merchant_id' => 'RndmYesbnkGtwyMrchtId',
                               'vpa'                 => 'randomvpa@yesbank',
                           ],
                       ],
                   ]
        );

        $this->ba->directAuth();

        $response = $this->makeRequestAndGetContent(
            [
                'method' => 'POST',
                'url' => '/callback/upi_yesbank',
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
        $this->assertEquals('upi_yesbank', $qrPayment->getGateway());
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
        $this->assertEquals('upi_yesbank', $payment->getGateway());
        $this->assertEquals('101YesDedTrmnl', $payment->getTerminalId());
        $this->assertEquals('in_person', $payment->getReference13());
        $this->assertEquals('bank_account', $payment->getReference2());
        $this->assertEquals(100, $payment->getAmount());

        $this->assertEquals('upi_yesbank', $upi->getGateway());
        $this->assertEquals($payment->getId(), $upi->getPaymentId());
        $this->assertEquals('authorize', $upi->getAction());
        $this->assertEquals('pay', $upi->getType());
        $this->assertEquals(100, $upi->getAmount());
        $this->assertEquals('payervpa@upi', $upi->getVpa());
        $this->assertEquals($qrCode->getId() . 'qrv2', $upi->getMerchantReference());
        $this->assertEquals('RndmNpciRefId', $upi->getNpciReferenceId());
        $this->assertEquals('mozart', $upi->getAttribute('acquirer'));

        $this->assertEquals('active', $qrCode->getStatus());
        $this->assertEquals(100, $qrCode->getAttribute('payments_amount_received'));
        $this->assertEquals(1, $qrCode->getAttribute('payments_received_count'));
    }

    public function testCreateDuplicateQrPaymentViaRefactorFlow()
    {
        // These are used during assertions at the end of the test
        $count = 0;

        $this->testCreateQrPaymentViaRefactorFlow();

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
                               'gateway'             => 'upi_yesbank',
                               'gateway_merchant_id' => 'RndmYesbnkGtwyMrchtId',
                               'vpa'                 => 'randomvpa@yesbank',
                           ],
                       ],
                   ]
        );

        $this->ba->directAuth();

        $response = $this->makeRequestAndGetContent(
            [
                'method' => 'POST',
                'url' => '/callback/upi_yesbank',
                'content' => [
                    'data' => 'RandomEncryptedUnPreProcessedDataForUpiAxisUpiOrQrPaymentCallback',
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

    public function testCreateExtraQrPaymentForDynamicQrViaRefactorFlow()
    {
        // These are used during assertions at the end of the test
        $count = 0;

        $this->testCreateQrPaymentViaRefactorFlow();

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
                               'npci_reference_id'  => 'ExtraRndmNpciRefId',
                               'gateway_timestamp'  => 1722114969,
                           ],
                           'payment'  => [
                               'currency' => 'INR',
                               'amount_authorized'  => 100,
                               'payer_account_type' => 'bank_account',
                           ],
                           'terminal' => [
                               'gateway'             => 'upi_yesbank',
                               'gateway_merchant_id' => 'RndmYesbnkGtwyMrchtId',
                               'vpa'                 => 'randomvpa@yesbank',
                           ],
                       ],
                   ]
        );

        $this->ba->directAuth();

        $response = $this->makeRequestAndGetContent(
            [
                'method' => 'POST',
                'url' => '/callback/upi_yesbank',
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
        $this->assertEquals(0, $qrpRequest->expected);
        $this->assertEquals($qrCode->getId(), $qrpRequest->getQrCodeId());
        $this->assertEquals('ExtraRndmNpciRefId', $qrpRequest->getTransactionReference());
        $this->assertEmpty($qrpRequest->getFailureReason());

        $this->assertFalse($qrPayment->expected);
        $this->assertEquals($qrCode->getId(), $qrPayment->qrCode->getId());
        $this->assertEquals('ExtraRndmNpciRefId', $qrPayment->getProviderReferenceId());
        $this->assertEquals('upi_yesbank', $qrPayment->getGateway());
        $this->assertEquals($payment->getId(), $qrPayment->getPaymentId());
        $this->assertEquals(100, $qrPayment->getAmount());
        $this->assertEquals($qrCode->getId() . 'qrv2', $qrPayment->getMerchantReference());
        $this->assertEquals('payervpa@upi', $qrPayment->getAttribute('payer_vpa'));
        $this->assertNotNull($qrPayment->getTransactionTime());
        $this->assertEquals('Payment made on closed QR code', $qrPayment->getAttribute('unexpected_reason'));

        $this->assertEquals('ExtraRndmNpciRefId', $payment->getReference16());
        $this->assertEquals('qr_code', $payment->getReceiverType());
        $this->assertEquals($qrCode->getId(), $payment->receiver->getId());
        $this->assertEquals('refunded', $payment->getStatus());
        $this->assertEquals('payervpa@upi', $payment->getVpa());
        $this->assertEquals('upi_yesbank', $payment->getGateway());
        $this->assertEquals('101YesDedTrmnl', $payment->getTerminalId());
        $this->assertEquals('in_person', $payment->getReference13());
        $this->assertEquals('bank_account', $payment->getReference2());
        $this->assertEquals(100, $payment->getAmount());

        $this->assertEquals('upi_yesbank', $upi->getGateway());
        $this->assertEquals($payment->getId(), $upi->getPaymentId());
        $this->assertEquals('authorize', $upi->getAction());
        $this->assertEquals('pay', $upi->getType());
        $this->assertEquals(100, $upi->getAmount());
        $this->assertEquals('payervpa@upi', $upi->getVpa());
        $this->assertEquals($qrCode->getId() . 'qrv2', $upi->getMerchantReference());
        $this->assertEquals('ExtraRndmNpciRefId', $upi->getNpciReferenceId());
        $this->assertEquals('mozart', $upi->getAttribute('acquirer'));
    }

    public function testCreateQrPaymentViaRefactorFlowWithAmountMismatch()
    {
        $this->fixtures->merchant->addFeatures(['omni_enabled'], 'LiveAccountMer');

        // These are used during assertions at the end of the test
        $count = 0;

        $this->mockMozartResponse(
            count: $count
        );

        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
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
                               'gateway'             => 'upi_yesbank',
                               'gateway_merchant_id' => 'RndmYesbnkGtwyMrchtId',
                               'vpa'                 => 'randomvpa@yesbank',
                           ],
                       ],
                   ]
        );

        $this->ba->directAuth();

        $response = $this->makeRequestAndGetContent(
            [
                'method' => 'POST',
                'url' => '/callback/upi_yesbank',
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
        $this->assertEquals(0, $qrpRequest->expected);
        $this->assertEquals($qrCode->getId(), $qrpRequest->getQrCodeId());
        $this->assertEquals('RndmNpciRefId', $qrpRequest->getTransactionReference());
        $this->assertEmpty($qrpRequest->getFailureReason());

        $this->assertFalse($qrPayment->expected);
        $this->assertEquals($qrCode->getId(), $qrPayment->qrCode->getId());
        $this->assertEquals('RndmNpciRefId', $qrPayment->getProviderReferenceId());
        $this->assertEquals('upi_yesbank', $qrPayment->getGateway());
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
        $this->assertEquals('upi_yesbank', $payment->getGateway());
        $this->assertEquals('101YesDedTrmnl', $payment->getTerminalId());
        $this->assertEquals('in_person', $payment->getReference13());
        $this->assertEquals('bank_account', $payment->getReference2());
        $this->assertEquals(150, $payment->getAmount());

        $this->assertEquals('upi_yesbank', $upi->getGateway());
        $this->assertEquals($payment->getId(), $upi->getPaymentId());
        $this->assertEquals('authorize', $upi->getAction());
        $this->assertEquals('pay', $upi->getType());
        $this->assertEquals(150, $upi->getAmount());
        $this->assertEquals('payervpa@upi', $upi->getVpa());
        $this->assertEquals($qrCode->getId() . 'qrv2', $upi->getMerchantReference());
        $this->assertEquals('RndmNpciRefId', $upi->getNpciReferenceId());
        $this->assertEquals('mozart', $upi->getAttribute('acquirer'));
    }

    public function testCreateNonQrPaymentViaRefactorFlow()
    {
        $this->markTestSkipped('test not working due to logPaymentRespawnEvent(), check with upi core');
        // These are used during assertions at the end of the test
        $count = 0;

        $this->mockMozartResponse(
            count: $count
        );

        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
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
                               'merchant_reference' => $qrCode->getId() . 'random',
                               'npci_reference_id'  => 'RndmNpciRefId',
                               'gateway_timestamp'  => 1722114963,
                           ],
                           'payment'  => [
                               'method' => 'upi',
                               'currency' => 'INR',
                               'amount_authorized'  => 100,
                               'payer_account_type' => 'bank_account',
                           ],
                           'terminal' => [
                               'gateway'             => 'upi_yesbank',
                               'gateway_merchant_id' => 'RndmYesbnkGtwyMrchtId',
                               'vpa'                 => 'randomvpa@yesbank',
                           ],
                       ],
                   ]
        );

        $this->ba->directAuth();

        $response = $this->makeRequestAndGetContent(
            [
                'method' => 'POST',
                'url' => '/callback/upi_yesbank',
                'content' => [
                    'data' => 'RandomEncryptedUnPreProcessedDataForUpiAxisUpiOrQrPaymentCallback',
                ]
            ]
        );

//        $this->assertEquals('SUCCESS', $response['status']);
//        $this->assertNull($response['error_message']);

        $qrpRequest = $this->getDbLastEntity('qr_payment_request', 'live');
        $qrPayment = $this->getDbLastEntity('qr_payment', 'live');

        // Payment and UPI entity are actually created in test mode in non prod ENVs
        $payment = $this->getDbLastEntity('payment');
        $qrCode = $this->getDbEntity('qr_code', ['id' => $qrCode->getId()], 'live');
        $upi = $this->getDbLastEntity('upi');

        $this->assertNull($qrpRequest);

        $this->assertNull($qrPayment);

        $this->assertEquals('RndmNpciRefId', $payment->getReference16());
        $this->assertEquals('qr_code', $payment->getReceiverType());
        $this->assertEquals($qrCode->getId(), $payment->receiver->getId());
        $this->assertEquals('captured', $payment->getStatus());
        $this->assertEquals('payervpa@upi', $payment->getVpa());
        $this->assertEquals('upi_yesbank', $payment->getGateway());
        $this->assertEquals('10LiveAccTrmnl', $payment->getTerminalId());
        $this->assertEquals('in_person', $payment->getReference13());
        $this->assertEquals('bank_account', $payment->getReference2());

        $this->assertEquals('upi_yesbank', $upi->getGateway());
        $this->assertEquals($payment->getId(), $upi->getPaymentId());
        $this->assertEquals('authorize', $upi->getAction());
        $this->assertEquals('pay', $upi->getType());
        $this->assertEquals(100, $upi->getAmount());
        $this->assertEquals('payervpa@upi', $upi->getVpa());
        $this->assertEquals($qrCode->getId(), $upi->getMerchantReference());
        $this->assertEquals('RndmNpciRefId', $upi->getNpciReferenceId());
        $this->assertEquals('mozart', $upi->getAttribute('acquirer'));
    }

    public function testCreateQrPaymentViaRefactorFlowForOldGateways()
    {
        $this->fixtures->merchant->addFeatures(['omni_enabled'], 'LiveAccountMer');

        // These are used during assertions at the end of the test
        $count = 0;

        $this->fixtures->on('live')->edit('terminal', '101YesDedTrmnl', ['vpa' => 'testvpa@yesb']);

        $this->mozartMock = \Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('mozart', $this->mozartMock);

        $this->mozartMock
            ->shouldReceive('sendRawRequest')
            ->andReturnUsing(
                function ($request) use (&$count) {
                    ++$count;

                    $reqArray = json_decode($request['content'], true);

                    return json_encode([
                                           'success' => true,
                                           'error'   => null,
                                           'data'    => [
                                               'qr_code' => [
                                                   'qr_string' => 'RandomQrString',
                                                   'reference' => 'RZPY'. $reqArray['qr_code']['id'] . 'qrv2',
                                               ],
                                           ],
                                       ]);
                }
            );

        $this->setMockSplitzTreatment(
            [
                $this->config->get('app.qr_code_create_refactor_gateway') => 'on',
                $this->config->get('app.qr_payment_refactor_existing_gateway')=> 'on',
            ]
        );

        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 100,
                'request_source' => 'ezetap',
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCode = $this->getDbLastEntity('qr_code', 'live');
        $qrCodeEntity = $this->getLastEntity('qr_code', true, 'live');

        $this->ba->directAuth();

        $this->makeUpiYesBankPayment(
            $qrCodeEntity,
            [
                'amount' => 100,
                'vpa'    => 'payervpa@upi',
            ],
            [
                'vpa' => 'randomvpa@yesbank',
                'merchant_reference' => 'RZPY'. str_after($qrCodeEntity['id'], 'qr_') . 'qrv2',
            ]
        );

        $qrpRequest = $this->getDbLastEntity('qr_payment_request', 'live');
        $qrPayment = $this->getDbLastEntity('qr_payment', 'live');
        $payment = $this->getDbLastEntity('payment', 'live');
        $qrCode = $this->getDbEntity('qr_code', ['id' => $qrCode->getId()], 'live');
        $upi = $this->getDbLastEntity('upi', 'live');

        $this->assertEquals(1, $qrpRequest->isCreated());
        $this->assertEquals(1, $qrpRequest->expected);
        $this->assertEquals($qrCode->getId(), $qrpRequest->getQrCodeId());
        $this->assertEquals('107611570997', $qrpRequest->getTransactionReference());
        $this->assertEmpty($qrpRequest->getFailureReason());

        $this->assertTrue($qrPayment->expected);
        $this->assertEquals($qrCode->getId(), $qrPayment->qrCode->getId());
        $this->assertEquals('107611570997', $qrPayment->getProviderReferenceId());
        $this->assertEquals('upi_yesbank', $qrPayment->getGateway());
        $this->assertEquals($payment->getId(), $qrPayment->getPaymentId());
        $this->assertEquals(100, $qrPayment->getAmount());
        $this->assertEquals('RZPY' . $qrCode->getId() . 'qrv2', $qrPayment->getMerchantReference());
        $this->assertEquals('payervpa@upi', $qrPayment->getAttribute('payer_vpa'));

        $this->assertEquals('107611570997', $payment->getReference16());
        $this->assertEquals('qr_code', $payment->getReceiverType());
        $this->assertEquals($qrCode->getId(), $payment->receiver->getId());
        $this->assertEquals('captured', $payment->getStatus());
        $this->assertEquals('payervpa@upi', $payment->getVpa());
        $this->assertEquals('upi_yesbank', $payment->getGateway());
        $this->assertEquals('101YesDedTrmnl', $payment->getTerminalId());
        $this->assertEquals('in_person', $payment->getReference13());
        $this->assertEquals(100, $payment->getAmount());

        $this->assertEquals('upi_yesbank', $upi->getGateway());
        $this->assertEquals($payment->getId(), $upi->getPaymentId());
        $this->assertEquals('authorize', $upi->getAction());
        $this->assertEquals('pay', $upi->getType());
        $this->assertEquals(100, $upi->getAmount());
        $this->assertEquals('payervpa@upi', $upi->getVpa());
        $this->assertEquals('RZPY' . $qrCode->getId() . 'qrv2', $upi->getMerchantReference());
        $this->assertEquals('107611570997', $upi->getNpciReferenceId());
        $this->assertEquals('mozart', $upi->getAttribute('acquirer'));

        $this->assertEquals('closed', $qrCode->getStatus());
        $this->assertEquals(100, $qrCode->getAttribute('payments_amount_received'));
        $this->assertEquals(1, $qrCode->getAttribute('payments_received_count'));
    }

    public function testQrCreateAndQrPaymentViaRefactorFlowForMerchantsWithStaticQrCodeConfig()
    {
        $count = 0;

        $this->mockMozartResponse(
            count: $count,
            res: [
                       'success' => true,
                       'error'   => null,
                       'data'    => [
                           'qr_code' => [
                               'qr_string' => 'upi://pay?ver=01&mode=19&pa=randomvpa@mairtel&pn=Mynote&tr=RandoReferenceqrv2&cu=INR&mc=4900&qrMedium=04&tn=YourNote&am=1.00',
                               'reference' => 'RandoReferenceqrv2',
                           ],
                       ],
                   ]
        );


        $this->setMockSplitzTreatment(
            [
                $this->config->get('app.qr_code_create_refactor_gateway') => 'on',
                $this->config->get('app.qr_payment_refactor_gateway')=> 'on',
                $this->config->get('app.qr_gateway_unrecognised_payment_process') => 'on',
            ]
        );

        $this->fixtures->create(
            'terminal:dedicated_upi_airtel_terminal',
            [
                'id'                   => '11LivAirtTrmnl',
                'merchant_id'          => 'LiveAccountMer',
                'gateway_merchant_id2' => 'randomvpa@mairtel',
                'gateway_merchant_id'  => 'RndmAirtelGtwyMrchtId',
                'type'                 => [
                    'pay'           => '1',
                    'non_recurring' => '1',
                    'offline'       => '1',
                    'collect'       => '1',
                ],
            ]
        );

        $this->createQrCode(
            [
                'type'           => 'upi_qr',
                'request_source' => 'ezetap',
                'vpa' => 'randomvpa@mairtel',
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
                               'merchant_reference' => 'RandomTrForSpecialStaticQr',
                               'npci_reference_id'  => 'RndmNpciRefId',
                               'gateway_timestamp'  => 1722114963,
                           ],
                           'payment'  => [
                               'currency' => 'INR',
                               'amount_authorized'  => 100,
                               'payer_account_type' => 'bank_account',
                           ],
                           'terminal' => [
                               'gateway'             => 'upi_airtel',
                               'gateway_merchant_id2' => 'randomvpa@mairtel',
                           ],
                       ],
                   ]
        );

        $this->ba->directAuth();

        $response = $this->makeRequestAndGetContent(
            [
                'method' => 'POST',
                'url' => '/callback/upi_airtel',
                'content' => [
                    'data' => 'RandomEncryptedUnPreProcessedDataForUpiAxisUpiOrQrPaymentCallback',
                ]
            ]
        );
    }

    public function testQrStatusCheckViaRefactorFlow()
    {
        $this->fixtures->merchant->addFeatures(['omni_enabled'], 'LiveAccountMer');

        $count = 0;

        $this->mozartMock = \Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('mozart', $this->mozartMock);

        $this->mozartMock
            ->shouldReceive('sendRawRequest')
            ->andReturnUsing(
                function ($request) use (&$count) {
                    ++$count;

                    $reqArray = json_decode($request['content'], true);

                    return json_encode([
                                           'success' => true,
                                           'error'   => null,
                                           'data'    => [
                                               'qr_code' => [
                                                   'reference' => $reqArray['qr_code']['id'] . 'qrv2',
                                                   'qr_string' => 'upi://pay?ver=01&mode=22&pa=randomvpa@yesbank&pn=K2KMarketing&tr=' .
                                                       $reqArray['qr_code']['id'] . 'qrv2' .
                                                       '&cu=INR&mc=5411&qrMedium=04&tn=PaymenttoK2KMarketing&am=1.00',
                                               ],
                                           ],
                                       ]);
                }
            );

        $this->mockRemindersRequestForStatusCheck();
        $this->setMockSplitzTreatment(
            [
                $this->config->get('app.qr_code_create_refactor_gateway') => 'on',
                $this->config->get('app.qr_payment_refactor_existing_gateway')=> 'on',
            ]
        );

        $qrCode = $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 100,
                'request_source' => 'ezetap',
            ],
            'live',
            'LiveAccountMer'
        );

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
                               'gateway'             => 'upi_yesbank',
                               'gateway_merchant_id' => 'RndmYesbnkGtwyMrchtId',
                               'vpa'                 => 'randomvpa@yesbank',
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
        $this->assertEquals('upi_yesbank', $qrPayment->getGateway());
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
        $this->assertEquals('upi_yesbank', $payment->getGateway());
        $this->assertEquals('101YesDedTrmnl', $payment->getTerminalId());
        $this->assertEquals('in_person', $payment->getReference13());
        $this->assertEquals('bank_account', $payment->getReference2());
        $this->assertEquals(100, $payment->getAmount());

        $this->assertEquals('upi_yesbank', $upi->getGateway());
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

    public function testCreateQrPaymentViaRefactorFlowForUpiRzpApb()
    {
        $this->fixtures->merchant->addFeatures(['omni_enabled'], 'LiveAccountMer');

        // These are used during assertions at the end of the test
        $count = 0;

        $this->fixtures->create(
            'terminal:dedicated_upi_rzpapb_offline_terminal',
            [
                'merchant_id' => 'LiveAccountMer',
                'gateway_merchant_id' => 'LiveAccountMer',
            ]
        );

        $this->fixtures->on('live')->edit('terminal', '11LiveAccTrmnl', ['status' => 'deactivated']);
        $this->fixtures->on('live')->edit('terminal', '101YesDedTrmnl', ['status' => 'deactivated']);

        $this->mozartMock = \Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('mozart', $this->mozartMock);

        $this->mozartMock
            ->shouldReceive('sendRawRequest')
            ->andReturnUsing(
                function ($request) use (&$count) {
                    ++$count;

                    $reqArray = json_decode($request['content'], true);

                    return json_encode([
                                           'data' => [
                                               'payment' => [
                                                   'currency' => 'INR',
                                               ],
                                               'status' => 'intent_inititated',
                                               'terminal' => [
                                                   'gateway' => 'upi_rzpapb',
                                                   'gateway_merchant_id' => 'LiveAccountMer',
                                                   'vpa' => 'testvpa@rxairtel',
                                               ],
                                               'upi' => [
                                                   'gateway_status_code' => 'created',
                                                   'merchant_reference' => $reqArray['payment']['id'],
                                               ],
                                           ],
                                           'error' => null,
                                           'next' => [
                                               'intent_url' => 'upi://pay?am=1.00&cu=INR&pa=' . 'testvpa@rxairtel' .
                                                   '&pn=Retail+Brand+Updated+Final&tn=Test+Intent+Payment&tr=' .
                                                   $reqArray['payment']['id'],
                                           ],
                                           'success' => true,
                                       ]);
                }
            );

        $this->setMockSplitzTreatment(
            [
                $this->config->get('app.qr_code_create_refactor_gateway') => 'off',
                $this->config->get('app.qr_payment_refactor_existing_gateway')=> 'on'
            ]
        );

        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 100,
                'request_source' => 'ezetap',
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCode = $this->getDbLastEntity('qr_code', 'live');
        $qrCodeEntity = $this->getLastEntity('qr_code', true, 'live');

        $this->ba->directAuth();

        $this->config['gateway.mock_upi_mozart'] = true;

        $this->makeUpiRzpapbPayment($qrCodeEntity);

        $qrpRequest = $this->getDbLastEntity('qr_payment_request', 'live');
        $qrPayment = $this->getDbLastEntity('qr_payment', 'live');
        $payment = $this->getDbLastEntity('payment', 'live');
        $qrCode = $this->getDbEntity('qr_code', ['id' => $qrCode->getId()], 'live');
        $upi = $this->getDbLastEntity('upi', 'live');

        $this->assertEquals(1, $qrpRequest->isCreated());
        $this->assertEquals(1, $qrpRequest->expected);
        $this->assertEquals($qrCode->getId(), $qrpRequest->getQrCodeId());
        $this->assertEquals('002002002002', $qrpRequest->getTransactionReference());
        $this->assertEmpty($qrpRequest->getFailureReason());

        $this->assertTrue($qrPayment->expected);
        $this->assertEquals($qrCode->getId(), $qrPayment->qrCode->getId());
        $this->assertEquals('002002002002', $qrPayment->getProviderReferenceId());
        $this->assertEquals('upi_rzpapb', $qrPayment->getGateway());
        $this->assertEquals($payment->getId(), $qrPayment->getPaymentId());
        $this->assertEquals(100, $qrPayment->getAmount());
        $this->assertEquals($qrCode->getId() . 'qrv2', $qrPayment->getMerchantReference());
        $this->assertEquals('payervpa@upi', $qrPayment->getAttribute('payer_vpa'));

        $this->assertEquals('002002002002', $payment->getReference16());
        $this->assertEquals('qr_code', $payment->getReceiverType());
        $this->assertEquals($qrCode->getId(), $payment->receiver->getId());
        $this->assertEquals('captured', $payment->getStatus());
        $this->assertEquals('payervpa@upi', $payment->getVpa());
        $this->assertEquals('upi_rzpapb', $payment->getGateway());
        $this->assertEquals('RzpApbOffTrmnl', $payment->getTerminalId());
        $this->assertEquals('in_person', $payment->getReference13());
        $this->assertEquals(100, $payment->getAmount());

        $this->assertEquals('upi_rzpapb', $upi->getGateway());
        $this->assertEquals($payment->getId(), $upi->getPaymentId());
        $this->assertEquals('authorize', $upi->getAction());
        $this->assertEquals('pay', $upi->getType());
        $this->assertEquals(100, $upi->getAmount());
        $this->assertEquals('payervpa@upi', $upi->getVpa());
        $this->assertEquals($qrCode->getId() . 'qrv2', $upi->getMerchantReference());
        $this->assertEquals('002002002002', $upi->getNpciReferenceId());
        $this->assertEquals('mozart', $upi->getAttribute('acquirer'));

        $this->assertEquals('closed', $qrCode->getStatus());
        $this->assertEquals(100, $qrCode->getAttribute('payments_amount_received'));
        $this->assertEquals(1, $qrCode->getAttribute('payments_received_count'));
    }

    public function testCreateQrPaymentViaRefactorFlowForUpiRzpApbWithOffer()
    {
        $this->fixtures->merchant->addFeatures(['omni_enabled'], 'LiveAccountMer');

        // These are used during assertions at the end of the test
        $count = 0;

        $this->fixtures->create(
            'terminal:dedicated_upi_rzpapb_offline_terminal',
            [
                'merchant_id' => 'LiveAccountMer',
                'gateway_merchant_id' => 'LiveAccountMer',
            ]
        );

        $this->fixtures->on('live')->edit('terminal', '11LiveAccTrmnl', ['status' => 'deactivated']);
        $this->fixtures->on('live')->edit('terminal', '101YesDedTrmnl', ['status' => 'deactivated']);

        $this->mozartMock = \Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('mozart', $this->mozartMock);

        $this->mozartMock
            ->shouldReceive('sendRawRequest')
            ->andReturnUsing(
                function ($request) use (&$count) {
                    ++$count;

                    $reqArray = json_decode($request['content'], true);

                    return json_encode([
                                           'data' => [
                                               'payment' => [
                                                   'currency' => 'INR',
                                               ],
                                               'status' => 'intent_inititated',
                                               'terminal' => [
                                                   'gateway' => 'upi_rzpapb',
                                                   'gateway_merchant_id' => 'LiveAccountMer',
                                                   'vpa' => 'testvpa@rxairtel',
                                               ],
                                               'upi' => [
                                                   'gateway_status_code' => 'created',
                                                   'merchant_reference' => $reqArray['payment']['id'],
                                               ],
                                           ],
                                           'error' => null,
                                           'next' => [
                                               'intent_url' => 'upi://pay?am=1.00&cu=INR&pa=' . 'testvpa@rxairtel' .
                                                   '&pn=Retail+Brand+Updated+Final&tn=Test+Intent+Payment&tr=' .
                                                   $reqArray['payment']['id'],
                                           ],
                                           'success' => true,
                                       ]);
                }
            );

        $this->setMockSplitzTreatment(
            [
                $this->config->get('app.qr_code_create_refactor_gateway') => 'off',
                $this->config->get('app.qr_payment_refactor_existing_gateway')=> 'on',
            ]
        );

        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 100,
                'request_source' => 'ezetap',
                'notes'          => [
                    'payment_context' => 'offer',
                ],
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCode = $this->getDbLastEntity('qr_code', 'live');
        $qrCodeEntity = $this->getLastEntity('qr_code', true, 'live');

        $this->ba->directAuth();

        $this->config['gateway.mock_upi_mozart'] = true;

        $this->makeUpiRzpapbPaymentWithOffer($qrCodeEntity);

        $qrpRequest = $this->getDbLastEntity('qr_payment_request', 'live');
        $qrPayment = $this->getDbLastEntity('qr_payment', 'live');
        $payment = $this->getDbLastEntity('payment', 'live');
        $qrCode = $this->getDbEntity('qr_code', ['id' => $qrCode->getId()], 'live');
        $upi = $this->getDbLastEntity('upi', 'live');

        $this->assertEquals(1, $qrpRequest->isCreated());
        $this->assertEquals(1, $qrpRequest->expected);
        $this->assertEquals($qrCode->getId(), $qrpRequest->getQrCodeId());
        $this->assertEquals('002002002002', $qrpRequest->getTransactionReference());
        $this->assertEmpty($qrpRequest->getFailureReason());

        $this->assertTrue($qrPayment->expected);
        $this->assertEquals($qrCode->getId(), $qrPayment->qrCode->getId());
        $this->assertEquals('002002002002', $qrPayment->getProviderReferenceId());
        $this->assertEquals('upi_rzpapb', $qrPayment->getGateway());
        $this->assertEquals($payment->getId(), $qrPayment->getPaymentId());
        $this->assertEquals(90, $qrPayment->getAmount());
        $this->assertEquals($qrCode->getId() . 'qrv2', $qrPayment->getMerchantReference());
        $this->assertEquals('payervpa@upi', $qrPayment->getAttribute('payer_vpa'));

        $this->assertEquals('002002002002', $payment->getReference16());
        $this->assertEquals('qr_code', $payment->getReceiverType());
        $this->assertEquals($qrCode->getId(), $payment->receiver->getId());
        $this->assertEquals('captured', $payment->getStatus());
        $this->assertEquals('payervpa@upi', $payment->getVpa());
        $this->assertEquals('upi_rzpapb', $payment->getGateway());
        $this->assertEquals('RzpApbOffTrmnl', $payment->getTerminalId());
        $this->assertEquals('in_person', $payment->getReference13());
        $this->assertEquals(90, $payment->getAmount());

        $this->assertEquals('upi_rzpapb', $upi->getGateway());
        $this->assertEquals($payment->getId(), $upi->getPaymentId());
        $this->assertEquals('authorize', $upi->getAction());
        $this->assertEquals('pay', $upi->getType());
        $this->assertEquals(90, $upi->getAmount());
        $this->assertEquals('payervpa@upi', $upi->getVpa());
        $this->assertEquals($qrCode->getId() . 'qrv2', $upi->getMerchantReference());
        $this->assertEquals('002002002002', $upi->getNpciReferenceId());
        $this->assertEquals('mozart', $upi->getAttribute('acquirer'));

        $this->assertEquals('closed', $qrCode->getStatus());
        $this->assertEquals(90, $qrCode->getAttribute('payments_amount_received'));
        $this->assertEquals(1, $qrCode->getAttribute('payments_received_count'));
    }

    public function testCreateQrPaymentViaRefactorFlowForUpiRzpApbWithEmi()
    {
        $this->fixtures->merchant->addFeatures(['omni_enabled'], 'LiveAccountMer');

        // These are used during assertions at the end of the test
        $count = 0;

        $this->fixtures->create(
            'terminal:dedicated_upi_rzpapb_offline_terminal',
            [
                'merchant_id' => 'LiveAccountMer',
                'gateway_merchant_id' => 'LiveAccountMer',
            ]
        );

        $this->fixtures->on('live')->edit('terminal', '11LiveAccTrmnl', ['status' => 'deactivated']);
        $this->fixtures->on('live')->edit('terminal', '101YesDedTrmnl', ['status' => 'deactivated']);

        $this->mozartMock = \Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('mozart', $this->mozartMock);

        $this->mozartMock
            ->shouldReceive('sendRawRequest')
            ->andReturnUsing(
                function ($request) use (&$count) {
                    ++$count;

                    $reqArray = json_decode($request['content'], true);

                    return json_encode([
                                           'data' => [
                                               'payment' => [
                                                   'currency' => 'INR',
                                               ],
                                               'status' => 'intent_inititated',
                                               'terminal' => [
                                                   'gateway' => 'upi_rzpapb',
                                                   'gateway_merchant_id' => 'LiveAccountMer',
                                                   'vpa' => 'testvpa@rxairtel',
                                               ],
                                               'upi' => [
                                                   'gateway_status_code' => 'created',
                                                   'merchant_reference' => $reqArray['payment']['id'],
                                               ],
                                           ],
                                           'error' => null,
                                           'next' => [
                                               'intent_url' => 'upi://pay?am=1.00&cu=INR&pa=' . 'testvpa@rxairtel' .
                                                   '&pn=Retail+Brand+Updated+Final&tn=Test+Intent+Payment&tr=' .
                                                   $reqArray['payment']['id'],
                                           ],
                                           'success' => true,
                                       ]);
                }
            );

        $this->setMockSplitzTreatment(
            [
                $this->config->get('app.qr_code_create_refactor_gateway') => 'off',
                $this->config->get('app.qr_payment_refactor_existing_gateway')=> 'on',
            ]
        );

        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 100,
                'request_source' => 'ezetap',
                'notes'          => [
                    'payment_context' => 'emi',
                ],
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCode = $this->getDbLastEntity('qr_code', 'live');
        $qrCodeEntity = $this->getLastEntity('qr_code', true, 'live');

        $this->ba->directAuth();

        $this->config['gateway.mock_upi_mozart'] = true;

        $this->makeUpiRzpapbPaymentWithEmi($qrCodeEntity);

        $qrpRequest = $this->getDbLastEntity('qr_payment_request', 'live');
        $qrPayment = $this->getDbLastEntity('qr_payment', 'live');
        $payment = $this->getDbLastEntity('payment', 'live');
        $qrCode = $this->getDbEntity('qr_code', ['id' => $qrCode->getId()], 'live');
        $upi = $this->getDbLastEntity('upi', 'live');

        $this->assertEquals(1, $qrpRequest->isCreated());
        $this->assertEquals(1, $qrpRequest->expected);
        $this->assertEquals($qrCode->getId(), $qrpRequest->getQrCodeId());
        $this->assertEquals('002002002002', $qrpRequest->getTransactionReference());
        $this->assertEmpty($qrpRequest->getFailureReason());

        $this->assertTrue($qrPayment->expected);
        $this->assertEquals($qrCode->getId(), $qrPayment->qrCode->getId());
        $this->assertEquals('002002002002', $qrPayment->getProviderReferenceId());
        $this->assertEquals('upi_rzpapb', $qrPayment->getGateway());
        $this->assertEquals($payment->getId(), $qrPayment->getPaymentId());
        $this->assertEquals(90, $qrPayment->getAmount());
        $this->assertEquals($qrCode->getId() . 'qrv2', $qrPayment->getMerchantReference());
        $this->assertEquals('payervpa@upi', $qrPayment->getAttribute('payer_vpa'));

        $this->assertEquals('002002002002', $payment->getReference16());
        $this->assertEquals('qr_code', $payment->getReceiverType());
        $this->assertEquals($qrCode->getId(), $payment->receiver->getId());
        $this->assertEquals('captured', $payment->getStatus());
        $this->assertEquals('payervpa@upi', $payment->getVpa());
        $this->assertEquals('upi_rzpapb', $payment->getGateway());
        $this->assertEquals('RzpApbOffTrmnl', $payment->getTerminalId());
        $this->assertEquals('in_person', $payment->getReference13());
        $this->assertEquals(90, $payment->getAmount());

        $this->assertEquals('upi_rzpapb', $upi->getGateway());
        $this->assertEquals($payment->getId(), $upi->getPaymentId());
        $this->assertEquals('authorize', $upi->getAction());
        $this->assertEquals('pay', $upi->getType());
        $this->assertEquals(90, $upi->getAmount());
        $this->assertEquals('payervpa@upi', $upi->getVpa());
        $this->assertEquals($qrCode->getId() . 'qrv2', $upi->getMerchantReference());
        $this->assertEquals('002002002002', $upi->getNpciReferenceId());
        $this->assertEquals('mozart', $upi->getAttribute('acquirer'));

        $this->assertEquals('closed', $qrCode->getStatus());
        $this->assertEquals(90, $qrCode->getAttribute('payments_amount_received'));
        $this->assertEquals(1, $qrCode->getAttribute('payments_received_count'));
    }

    public function testCreateQrPaymentViaRefactorFlowForUpiRzpApbWithFailedStatus()
    {
        $this->fixtures->merchant->addFeatures(['omni_enabled'], 'LiveAccountMer');

        // These are used during assertions at the end of the test
        $count = 0;

        $this->fixtures->create(
            'terminal:dedicated_upi_rzpapb_offline_terminal',
            [
                'merchant_id' => 'LiveAccountMer',
                'gateway_merchant_id' => 'LiveAccountMer',
            ]
        );

        $this->fixtures->on('live')->edit('terminal', '11LiveAccTrmnl', ['status' => 'deactivated']);
        $this->fixtures->on('live')->edit('terminal', '101YesDedTrmnl', ['status' => 'deactivated']);

        $this->mozartMock = \Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('mozart', $this->mozartMock);

        $this->mozartMock
            ->shouldReceive('sendRawRequest')
            ->andReturnUsing(
                function ($request) use (&$count) {
                    ++$count;

                    $reqArray = json_decode($request['content'], true);

                    return json_encode([
                                           'data' => [
                                               'payment' => [
                                                   'currency' => 'INR',
                                               ],
                                               'status' => 'intent_inititated',
                                               'terminal' => [
                                                   'gateway' => 'upi_rzpapb',
                                                   'gateway_merchant_id' => 'LiveAccountMer',
                                                   'vpa' => 'testvpa@rxairtel',
                                               ],
                                               'upi' => [
                                                   'gateway_status_code' => 'created',
                                                   'merchant_reference' => $reqArray['payment']['id'],
                                               ],
                                           ],
                                           'error' => null,
                                           'next' => [
                                               'intent_url' => 'upi://pay?am=1.00&cu=INR&pa=' . 'testvpa@rxairtel' .
                                                   '&pn=Retail+Brand+Updated+Final&tn=Test+Intent+Payment&tr=' .
                                                   $reqArray['payment']['id'],
                                           ],
                                           'success' => true,
                                       ]);
                }
            );


        $this->setMockSplitzTreatment(
            [
                $this->config->get('app.qr_code_create_refactor_gateway') => 'off',
                $this->config->get('app.qr_payment_refactor_existing_gateway')=> 'on',
            ]
        );

        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 100,
                'request_source' => 'ezetap',
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCode = $this->getDbLastEntity('qr_code', 'live');
        $qrCodeEntity = $this->getLastEntity('qr_code', true, 'live');

        $this->ba->directAuth();

        $this->config['gateway.mock_upi_mozart'] = true;

        $this->makeUpiRzpapbPaymentForFailedStatus($qrCodeEntity);

        $qrpRequest = $this->getDbLastEntity('qr_payment_request', 'live');
        $qrPayment = $this->getDbLastEntity('qr_payment', 'live');
        $payment = $this->getDbLastEntity('payment', 'live');
        $qrCode = $this->getDbEntity('qr_code', ['id' => $qrCode->getId()], 'live');
        $upi = $this->getDbLastEntity('upi', 'live');

        $this->assertEquals(0, $qrpRequest->isCreated());
        $this->assertEquals(0, $qrpRequest->expected);
        $this->assertEquals($qrCode->getId(), $qrpRequest->getQrCodeId());
        $this->assertEquals('002002002002', $qrpRequest->getTransactionReference());
        $this->assertEquals('failed callback', $qrpRequest->getFailureReason());

        $this->assertNull($qrPayment);

        $this->assertNull($payment);

        $this->assertNull($upi);
    }

    public function testQrStatusCheckViaRefactorFlowForFailedStatus()
    {
        $this->fixtures->merchant->addFeatures(['omni_enabled'], 'LiveAccountMer');

        $count = 0;

        $this->mozartMock = \Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('mozart', $this->mozartMock);

        $this->mozartMock
            ->shouldReceive('sendRawRequest')
            ->andReturnUsing(
                function ($request) use (&$count) {
                    ++$count;

                    $reqArray = json_decode($request['content'], true);

                    return json_encode([
                                           'success' => true,
                                           'error'   => null,
                                           'data'    => [
                                               'qr_code' => [
                                                   'reference' => $reqArray['qr_code']['id'] . 'qrv2',
                                                   'qr_string' => 'upi://pay?ver=01&mode=22&pa=randomvpa@yesbank&pn=K2KMarketing&tr=' .
                                                       $reqArray['qr_code']['id'] . 'qrv2' .
                                                       '&cu=INR&mc=5411&qrMedium=04&tn=PaymenttoK2KMarketing&am=1.00',
                                               ],
                                           ],
                                       ]);
                }
            );

        $this->mockRemindersRequestForStatusCheck();
        $this->setMockSplitzTreatment(
            [
                $this->config->get('app.qr_code_create_refactor_gateway') => 'on',
                $this->config->get('app.qr_payment_refactor_existing_gateway')=> 'on',
            ]
        );

        $qrCode = $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 100,
                'request_source' => 'ezetap',
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCodeId = $qrCode['id'];

        $this->testData[__FUNCTION__]['request']['url'] = $this->testData[__FUNCTION__]['request']['url'] . $qrCodeId;

        putenv("IS_WORKER_POD=true");

        $this->ba->reminderAppAuth();

        $this->mockMozartResponse(
            count: $count,
            res  : [
                       'success' => false,
                       'error'   => [
                           'code' => 'FAILED',
                           'description' => 'Payment failed',
                       ],
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
                               'gateway'             => 'upi_yesbank',
                               'gateway_merchant_id' => 'RndmYesbnkGtwyMrchtId',
                               'vpa'                 => 'randomvpa@yesbank',
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

        $this->assertEquals(0, $qrpRequest->isCreated());
        $this->assertEquals(0, $qrpRequest->expected);
        $this->assertEquals($qrCode->getId(), $qrpRequest->getQrCodeId());
        $this->assertEquals('RndmNpciRefId', $qrpRequest->getTransactionReference());
        $this->assertEquals('failed callback', $qrpRequest->getFailureReason());

        $this->assertNull($qrPayment);

        $this->assertNull($payment);

        $this->assertNull($upi);
    }

    public function testQrStatusCheckViaRefactorFlowForFailedStatusThroughIntegrationError()
    {
        $this->fixtures->merchant->addFeatures(['omni_enabled'], 'LiveAccountMer');

        $count = 0;

        $this->mozartMock = \Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('mozart', $this->mozartMock);

        $this->mozartMock
            ->shouldReceive('sendRawRequest')
            ->andReturnUsing(
                function ($request) use (&$count) {
                    ++$count;

                    $reqArray = json_decode($request['content'], true);

                    return json_encode([
                                           'success' => true,
                                           'error'   => null,
                                           'data'    => [
                                               'qr_code' => [
                                                   'reference' => $reqArray['qr_code']['id'] . 'qrv2',
                                                   'qr_string' => 'upi://pay?ver=01&mode=22&pa=randomvpa@yesbank&pn=K2KMarketing&tr=' .
                                                       $reqArray['qr_code']['id'] . 'qrv2' .
                                                       '&cu=INR&mc=5411&qrMedium=04&tn=PaymenttoK2KMarketing&am=1.00',
                                               ],
                                           ],
                                       ]);
                }
            );

        $this->mockRemindersRequestForStatusCheck();
        $this->setMockSplitzTreatment(
            [
                $this->config->get('app.qr_code_create_refactor_gateway') => 'on',
                $this->config->get('app.qr_payment_refactor_existing_gateway')=> 'on',
            ]
        );

        $qrCode = $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 100,
                'request_source' => 'ezetap',
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCodeId = $qrCode['id'];

        $this->testData[__FUNCTION__]['request']['url'] = $this->testData[__FUNCTION__]['request']['url'] . $qrCodeId;

        putenv("IS_WORKER_POD=true");

        $this->ba->reminderAppAuth();

        $this->mozartMock = \Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('mozart', $this->mozartMock);

        $this->mozartMock
            ->shouldReceive('sendRawRequest')
            ->andReturnUsing(
                function ($request) use (&$count, $qrCodeId) {
                    ++$count;

                    $reqArray = json_decode($request['content'], true);

                    throw new IntegrationException(
                        'Response status: 500',
                        ErrorCode::SERVER_ERROR_MOZART_SERVICE_ERROR,
                        [
                            'status_code' => 500,
                            'body'        => json_encode([
                                                             'success' => false,
                                                             'error'   => [
                                                                 'code'        => null,
                                                                 'description' => null,
                                                             ],
                                                             'data'    => [
                                                                 '_raw'     => 'RandomRawData',
                                                                 'error'   => [
                                                                     'code'        => 'FAILED',
                                                                     'description' => 'Payment failed',
                                                                 ],
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
                                                                     'gateway'             => 'upi_yesbank',
                                                                     'gateway_merchant_id' => 'RndmYesbnkGtwyMrchtId',
                                                                     'vpa'                 => 'randomvpa@yesbank',
                                                                 ],
                                                             ],
                                                         ]),
                        ]
                    );
                }
            );

        $this->startTest();

        // To unset the env key variable
        putenv("IS_WORKER_POD");

        $qrpRequest = $this->getDbLastEntity('qr_payment_request', 'live');
        $qrPayment  = $this->getDbLastEntity('qr_payment', 'live');
        $payment    = $this->getDbLastEntity('payment', 'live');
        $qrCode     = $this->getDbEntity('qr_code', ['id' => str_after($qrCodeId, 'qr_')], 'live');
        $upi        = $this->getDbLastEntity('upi', 'live');

        $this->assertEquals(0, $qrpRequest->isCreated());
        $this->assertEquals(0, $qrpRequest->expected);
        $this->assertEquals($qrCode->getId(), $qrpRequest->getQrCodeId());
        $this->assertEquals('RndmNpciRefId', $qrpRequest->getTransactionReference());
        $this->assertEquals('failed callback', $qrpRequest->getFailureReason());

        $this->assertNull($qrPayment);

        $this->assertNull($payment);

        $this->assertNull($upi);
    }

    public function testQrStatusCheckViaRefactorFlowForUpiRzpapb()
    {
        $this->fixtures->merchant->addFeatures(['omni_enabled'], 'LiveAccountMer');

        $count = 0;

        $this->fixtures->create(
            'terminal:dedicated_upi_rzpapb_offline_terminal',
            [
                'merchant_id' => 'LiveAccountMer',
                'gateway_merchant_id' => 'LiveAccountMer',
            ]
        );

        $this->fixtures->on('live')->edit('terminal', '11LiveAccTrmnl', ['status' => 'deactivated']);
        $this->fixtures->on('live')->edit('terminal', '101YesDedTrmnl', ['status' => 'deactivated']);

        $this->mozartMock = \Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('mozart', $this->mozartMock);

        $this->mozartMock
            ->shouldReceive('sendRawRequest')
            ->andReturnUsing(
                function ($request) use (&$count) {
                    ++$count;

                    $reqArray = json_decode($request['content'], true);

                    return json_encode([
                                           'data' => [
                                               'payment' => [
                                                   'currency' => 'INR',
                                               ],
                                               'status' => 'intent_inititated',
                                               'terminal' => [
                                                   'gateway' => 'upi_rzpapb',
                                                   'gateway_merchant_id' => 'LiveAccountMer',
                                                   'vpa' => 'testvpa@rxairtel',
                                               ],
                                               'upi' => [
                                                   'gateway_status_code' => 'created',
                                                   'merchant_reference' => $reqArray['payment']['id'],
                                               ],
                                           ],
                                           'error' => null,
                                           'next' => [
                                               'intent_url' => 'upi://pay?am=1.00&cu=INR&pa=' . 'testvpa@rxairtel' .
                                                   '&pn=Retail+Brand+Updated+Final&tn=Test+Intent+Payment&tr=' .
                                                   $reqArray['payment']['id'],
                                           ],
                                           'success' => true,
                                       ]);
                }
            );

        $this->setMockSplitzTreatment(
            [
                $this->config->get('app.qr_code_create_refactor_gateway') => 'off',
                $this->config->get('app.qr_payment_refactor_existing_gateway')=> 'on',
            ]
        );

        $this->mockRemindersRequestForStatusCheck();
        $this->mockSplitzTreatmentForStatusCheck();

        $qrCode = $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 100,
                'request_source' => 'ezetap',
            ],
            'live',
            'LiveAccountMer'
        );

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
                               'gateway'             => 'upi_rzpapb',
                               'gateway_merchant_id' => 'LiveAccountMer',
                               'vpa'                 => 'testvpa@rxairtel',
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
        $this->assertEquals('upi_rzpapb', $qrPayment->getGateway());
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
        $this->assertEquals('upi_rzpapb', $payment->getGateway());
        $this->assertEquals('RzpApbOffTrmnl', $payment->getTerminalId());
        $this->assertEquals('in_person', $payment->getReference13());
        $this->assertEquals('bank_account', $payment->getReference2());
        $this->assertEquals(100, $payment->getAmount());

        $this->assertEquals('upi_rzpapb', $upi->getGateway());
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

    public function testQrStatusCheckViaRefactorFlowForUpiRzpapbWhenPaymentExists()
    {
        $this->mockRemindersRequestForStatusCheck();
        $this->mockSplitzTreatmentForStatusCheck();

        $this->testCreateQrPaymentViaRefactorFlowForUpiRzpApb();

        $qrCode = $this->getDbLastEntity('qr_code', 'live');

        // force QR opening for the purpose of this test
        $this->fixtures->edit('qr_code', $qrCode->getId(), ['status' => 'active', 'payments_received_count' => 0]);

        $this->testData[__FUNCTION__]['request']['url'] = $this->testData[__FUNCTION__]['request']['url'] .
            $qrCode->getId();

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
                               'merchant_reference' => $qrCode->getReference(),
                               'npci_reference_id'  => '002002002002',
                               'gateway_timestamp'  => 1722114963,
                           ],
                           'payment'  => [
                               'amount_authorized'  => 100,
                               'payer_account_type' => 'bank_account',
                           ],
                           'terminal' => [
                               'gateway'             => 'upi_rzpapb',
                               'gateway_merchant_id' => 'LiveAccountMer',
                               'vpa'                 => 'testvpa@rxairtel',
                           ],
                       ],
                   ]
        );

        $countOfQrPaymentBefore = count($this->getDbEntities(entity: 'qr_payment', mode: 'live'));
        $countOfQrPaymentRequestBefore = count($this->getDbEntities(entity: 'qr_payment_request', mode: 'live'));
        $countOfPaymentBefore = count($this->getDbEntities(entity: 'payment', mode: 'live'));
        $countOfUpiEntityBefore = count($this->getDbEntities(entity: 'upi', mode: 'live'));

        $this->startTest();

        $countOfQrPaymentAfter = count($this->getDbEntities(entity: 'qr_payment', mode: 'live'));
        $countOfQrPaymentRequestAfter = count($this->getDbEntities(entity: 'qr_payment_request', mode: 'live'));
        $countOfPaymentAfter = count($this->getDbEntities(entity: 'payment', mode: 'live'));
        $countOfUpiEntityAfter = count($this->getDbEntities(entity: 'upi', mode: 'live'));

        // To unset the env key variable
        putenv("IS_WORKER_POD");

        $this->assertEquals($countOfQrPaymentRequestBefore + 1, $countOfQrPaymentRequestAfter);
        $this->assertEquals($countOfQrPaymentBefore, $countOfQrPaymentAfter);
        $this->assertEquals($countOfPaymentBefore, $countOfPaymentAfter);
        $this->assertEquals($countOfUpiEntityBefore, $countOfUpiEntityAfter);

        $qrpRequest = $this->getDbLastEntity('qr_payment_request', 'live');
        $qrCode = $this->getDbEntity('qr_code', ['id' => $qrCode->getId()], 'live');

        $this->assertEquals(0, $qrpRequest->isCreated());
        $this->assertEquals(0, $qrpRequest->expected);
        $this->assertEquals('QR_PAYMENT_DUPLICATE_NOTIFICATION', $qrpRequest->getFailureReason());
        $this->assertEquals($qrCode->getId(), $qrpRequest->getQrCodeId());
        $this->assertEquals('002002002002', $qrpRequest->getTransactionReference());
    }

    public function testQrPaymentReconForUpiRzpapbWhenPaymentDoesNotExist()
    {
        $this->fixtures->merchant->addFeatures(['omni_enabled'], 'LiveAccountMer');

        $this->fixtures->create(
            'terminal:dedicated_upi_rzpapb_offline_terminal',
            [
                'merchant_id' => 'LiveAccountMer',
                'gateway_merchant_id' => 'LiveAccountMer',
            ]
        );

        $this->fixtures->on('live')->edit('terminal', '11LiveAccTrmnl', ['status' => 'deactivated']);
        $this->fixtures->on('live')->edit('terminal', '101YesDedTrmnl', ['status' => 'deactivated']);

        $this->mozartMock = \Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('mozart', $this->mozartMock);

        $this->mozartMock
            ->shouldReceive('sendRawRequest')
            ->andReturnUsing(
                function ($request) use (&$count) {
                    ++$count;

                    $reqArray = json_decode($request['content'], true);

                    return json_encode([
                                           'data' => [
                                               'payment' => [
                                                   'currency' => 'INR',
                                               ],
                                               'status' => 'intent_inititated',
                                               'terminal' => [
                                                   'gateway' => 'upi_rzpapb',
                                                   'gateway_merchant_id' => 'LiveAccountMer',
                                                   'vpa' => 'testvpa@rxairtel',
                                               ],
                                               'upi' => [
                                                   'gateway_status_code' => 'created',
                                                   'merchant_reference' => $reqArray['payment']['id'],
                                               ],
                                           ],
                                           'error' => null,
                                           'next' => [
                                               'intent_url' => 'upi://pay?am=1.00&cu=INR&pa=' . 'testvpa@rxairtel' .
                                                   '&pn=Retail+Brand+Updated+Final&tn=Test+Intent+Payment&tr=' .
                                                   $reqArray['payment']['id'],
                                           ],
                                           'success' => true,
                                       ]);
                }
            );


        $this->setMockSplitzTreatment(
            [
                $this->config->get('app.qr_code_create_refactor_gateway') => 'off',
                $this->config->get('app.qr_payment_refactor_existing_gateway')=> 'on',
            ]
        );

        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 100,
                'request_source' => 'ezetap',
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCode = $this->getDbLastEntity('qr_code', 'live');

        $this->ba->appAuth('rzp_live');

        $this->testData[__FUNCTION__]['request']['content']['data']['upi']['merchant_reference']
            = $qrCode->getId() . 'qrv2';

        $resp = $this->makeRequestAndGetContent($this->testData[__FUNCTION__]['request']);

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
        $this->assertEquals('upi_rzpapb', $qrPayment->getGateway());
        $this->assertEquals($payment->getId(), $qrPayment->getPaymentId());
        $this->assertEquals(100, $qrPayment->getAmount());
        $this->assertEquals($qrCode->getId() . 'qrv2', $qrPayment->getMerchantReference());
        $this->assertEquals('payervpa@upi', $qrPayment->getAttribute('payer_vpa'));

        $this->assertEquals('RndmNpciRefId', $payment->getReference16());
        $this->assertEquals('qr_code', $payment->getReceiverType());
        $this->assertEquals($qrCode->getId(), $payment->receiver->getId());
        $this->assertEquals('captured', $payment->getStatus());
        $this->assertEquals('payervpa@upi', $payment->getVpa());
        $this->assertEquals('upi_rzpapb', $payment->getGateway());
        $this->assertEquals('RzpApbOffTrmnl', $payment->getTerminalId());
        $this->assertEquals('in_person', $payment->getReference13());
        $this->assertEquals('bank_account', $payment->getReference2());
        $this->assertEquals(100, $payment->getAmount());

        $this->assertEquals('upi_rzpapb', $upi->getGateway());
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

    public function testQrPaymentReconForUpiRzpapbWhenPaymentExists()
    {
        $this->testCreateQrPaymentViaRefactorFlowForUpiRzpApb();

        $qrCode = $this->getDbLastEntity('qr_code', 'live');

        $this->ba->appAuth('rzp_live');

        $this->testData[__FUNCTION__]['request']['content']['data']['upi']['merchant_reference']
            = $qrCode->getId() . 'qrv2';

        $countOfQrPaymentBefore = count($this->getDbEntities(entity: 'qr_payment', mode: 'live'));
        $countOfQrPaymentRequestBefore = count($this->getDbEntities(entity: 'qr_payment_request', mode: 'live'));
        $countOfPaymentBefore = count($this->getDbEntities(entity: 'payment', mode: 'live'));
        $countOfUpiEntityBefore = count($this->getDbEntities(entity: 'upi', mode: 'live'));

        $resp = $this->makeRequestAndGetContent($this->testData[__FUNCTION__]['request']);

        $countOfQrPaymentAfter = count($this->getDbEntities(entity: 'qr_payment', mode: 'live'));
        $countOfQrPaymentRequestAfter = count($this->getDbEntities(entity: 'qr_payment_request', mode: 'live'));
        $countOfPaymentAfter = count($this->getDbEntities(entity: 'payment', mode: 'live'));
        $countOfUpiEntityAfter = count($this->getDbEntities(entity: 'upi', mode: 'live'));

        $this->assertEquals($countOfQrPaymentRequestBefore, $countOfQrPaymentRequestAfter);
        $this->assertEquals($countOfQrPaymentBefore, $countOfQrPaymentAfter);
        $this->assertEquals($countOfPaymentBefore, $countOfPaymentAfter);
        $this->assertEquals($countOfUpiEntityBefore, $countOfUpiEntityAfter);

        $qrpRequest = $this->getDbLastEntity('qr_payment_request', 'live');
        $qrCode = $this->getDbEntity('qr_code', ['id' => $qrCode->getId()], 'live');

        $this->assertEquals(1, $qrpRequest->isCreated());
        $this->assertEquals(1, $qrpRequest->expected);
        $this->assertEquals('', $qrpRequest->getFailureReason());
        $this->assertEquals($qrCode->getId(), $qrpRequest->getQrCodeId());
        $this->assertEquals('002002002002', $qrpRequest->getTransactionReference());
    }

    public function testCreateQrCreateViaRefactorFlowForUpiRzpApbWithPaymentContextNotes()
    {
        $this->fixtures->merchant->addFeatures(['omni_enabled'], 'LiveAccountMer');

        // These are used during assertions at the end of the test
        $count = 0;
        $isPaymentContext = false;
        $this->fixtures->create(
            'terminal:dedicated_upi_rzpapb_offline_terminal',
            [
                'merchant_id' => 'LiveAccountMer',
                'gateway_merchant_id' => 'LiveAccountMer',
            ]
        );

        $this->fixtures->on('live')->edit('terminal', '11LiveAccTrmnl', ['status' => 'deactivated']);
        $this->fixtures->on('live')->edit('terminal', '101YesDedTrmnl', ['status' => 'deactivated']);

        $this->mozartMock = \Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('mozart', $this->mozartMock);

        $this->mozartMock
            ->shouldReceive('sendRawRequest')
            ->andReturnUsing(
                function ($request) use (&$count,&$isPaymentContext) {
                    ++$count;

                    $reqArray = json_decode($request['content'], true);
                    if(isset($reqArray['metadata']['payment_context']) === true)
                    {
                        $isPaymentContext = true;
                    }
                    return json_encode([
                                           'data' => [
                                               'payment' => [
                                                   'currency' => 'INR',
                                               ],
                                               'status' => 'intent_inititated',
                                               'terminal' => [
                                                   'gateway' => 'upi_rzpapb',
                                                   'gateway_merchant_id' => 'LiveAccountMer',
                                                   'vpa' => 'testvpa@rxairtel',
                                               ],
                                               'upi' => [
                                                   'gateway_status_code' => 'created',
                                                   'merchant_reference' => $reqArray['payment']['id'],
                                               ],
                                           ],
                                           'error' => null,
                                           'next' => [
                                               'intent_url' => 'upi://pay?am=1.00&cu=INR&pa=' . 'testvpa@rxairtel' .
                                                               '&pn=Retail+Brand+Updated+Final&tn=Test+Intent+Payment&tr=' .
                                                               $reqArray['payment']['id'],
                                           ],
                                           'success' => true,
                                       ]);
                }
            );

        $this->setMockSplitzTreatment(
            [
                $this->config->get('app.qr_code_create_refactor_gateway') => 'off',
                $this->config->get('app.qr_payment_refactor_existing_gateway')=> 'on',
            ]
        );

        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 100,
                'request_source' => 'ezetap',
                'notes'          => [
                    'payment_context' => 'offer',
                ],
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCode = $this->getDbLastEntity('qr_code', 'live');
        $this->assertEquals(100, $qrCode->getAmount());
        $this->assertEquals('LiveAccountMer', $qrCode->getMerchantId());
        $this->assertEquals('upi_qr', $qrCode->getProvider());
        $this->assertEquals('single_use', $qrCode->getUsageType());
        $this->assertEquals('active', $qrCode->getStatus());
        $this->assertEquals(true, $isPaymentContext);

    }

    public function testCreateQrCreateViaRefactorFlowForUpiRzpApbWithoutNotes()
    {
        $this->fixtures->merchant->addFeatures(['omni_enabled'], 'LiveAccountMer');

        // These are used during assertions at the end of the test
        $count = 0;
        $isPaymentContext = false;
        $this->fixtures->create(
            'terminal:dedicated_upi_rzpapb_offline_terminal',
            [
                'merchant_id' => 'LiveAccountMer',
                'gateway_merchant_id' => 'LiveAccountMer',
            ]
        );

        $this->fixtures->on('live')->edit('terminal', '11LiveAccTrmnl', ['status' => 'deactivated']);
        $this->fixtures->on('live')->edit('terminal', '101YesDedTrmnl', ['status' => 'deactivated']);

        $this->mozartMock = \Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('mozart', $this->mozartMock);

        $this->mozartMock
            ->shouldReceive('sendRawRequest')
            ->andReturnUsing(
                function ($request) use (&$count,&$isPaymentContext) {
                    ++$count;

                    $reqArray = json_decode($request['content'], true);
                    if(isset($reqArray['metadata']['payment_context']) === true)
                    {
                        $isPaymentContext = true;
                    }
                    return json_encode([
                                           'data' => [
                                               'payment' => [
                                                   'currency' => 'INR',
                                               ],
                                               'status' => 'intent_inititated',
                                               'terminal' => [
                                                   'gateway' => 'upi_rzpapb',
                                                   'gateway_merchant_id' => 'LiveAccountMer',
                                                   'vpa' => 'testvpa@rxairtel',
                                               ],
                                               'upi' => [
                                                   'gateway_status_code' => 'created',
                                                   'merchant_reference' => $reqArray['payment']['id'],
                                               ],
                                           ],
                                           'error' => null,
                                           'next' => [
                                               'intent_url' => 'upi://pay?am=1.00&cu=INR&pa=' . 'testvpa@rxairtel' .
                                                               '&pn=Retail+Brand+Updated+Final&tn=Test+Intent+Payment&tr=' .
                                                               $reqArray['payment']['id'],
                                           ],
                                           'success' => true,
                                       ]);
                }
            );

        $this->setMockSplitzTreatment(
            [
                $this->config->get('app.qr_code_create_refactor_gateway') => 'off',
                $this->config->get('app.qr_payment_refactor_existing_gateway')=> 'on',
            ]
        );

        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
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
        $this->assertEquals('upi_qr', $qrCode->getProvider());
        $this->assertEquals('single_use', $qrCode->getUsageType());
        $this->assertEquals('active', $qrCode->getStatus());
        $this->assertEquals(false, $isPaymentContext);
    }

    public function testJKBankSQRCreationViaMerchantQRCreateRoute()
    {
        $this->config['applications.ezetap-notification.mock'] = true;
        $this->fixtures->merchant->addFeatures(['omni_enabled'], 'LiveAccountMer');
        $this->getDedicatedTerminalSplitzResponseForVariantON();
        $this->setMockSplitzTreatment(
            [
                $this->config->get('app.qr_code_create_refactor_gateway') => 'on',
                $this->config->get('app.qr_payment_refactor_gateway')=> 'on',
            ]
        );
        $this->fixtures->create('terminal:dedicated_upi_jk_terminal');

        $qrCode = $this->createMerchantQrCode(
            [
                'merchant_id'        => 'LiveAccountMer',
                'request_source'     => 'ezetap',
                'vpa'                => 'rzp.qrTest@jkb',
                'device_id'          => '12345Test',
                'qrString'         => 'upi://pay?ver=01&pa=rzp.qrTest@jkb&tr=bankTr&pn=TestJk2&cu=INR&mc=5817&qrMedium=04&tn=PaymenttoTest',
            ]);

        $this->assertNotNull($qrCode);
        $qrCodeEntity = $this->getDbLastEntity('qr_code','live');
        $intentParam = $this->getIntentParamsFromQRString($qrCodeEntity['qr_string']);
        $this->assertEquals('rzp.qrTest@jkb', $intentParam['pa']);
        $this->assertEquals('bankTr', $intentParam['tr']);
        $this->assertEquals('bankTr', $qrCodeEntity['reference']);
        $this->assertEquals('12345Test', $qrCodeEntity['device_id']);
    }

    public function testCreateQrPaymentViaRefactorFlowForJKBankStaticQr()
    {
        // These are used during assertions at the end of the test
        $count = 0;

        $this->mockMozartResponse(
            count: $count
        );

        $this->config['applications.ezetap-notification.mock'] = true;
        $this->fixtures->merchant->addFeatures(['omni_enabled'], 'LiveAccountMer');
        $this->getDedicatedTerminalSplitzResponseForVariantON();
        $this->setMockSplitzTreatment(
            [
                $this->config->get('app.qr_code_create_refactor_gateway') => 'on',
                $this->config->get('app.qr_payment_refactor_gateway')=> 'on',
            ]
        );
        $this->fixtures->create('terminal:dedicated_upi_jk_terminal');

        $qrCode = $this->createMerchantQrCode(
            [
                'merchant_id'        => 'LiveAccountMer',
                'request_source'     => 'ezetap',
                'vpa'                => 'rzp.qrTest@jkb',
                'device_id'          => '12345Test',
                'qrString'         => 'upi://pay?ver=01&pa=rzp.qrTest@jkb&tr=bankTr&pn=TestJk2&cu=INR&mc=5817&qrMedium=04&tn=PaymenttoTest',
            ]);


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
                               'merchant_reference' => 'bankTr',
                               'npci_reference_id'  => 'RndmNpciRefId',
                               'gateway_timestamp'  => 1722114963,
                               'gateway_amount'     => 100,
                           ],
                           'payment'  => [
                               'currency' => 'INR',
                               'amount_authorized'  => 100,
                               'payer_account_type' => 'bank_account',
                               'method' => 'upi'
                           ],
                           'terminal' => [
                               'gateway'             => 'upi_jkbank',
                               'vpa'                 => 'rzp.qrtest@jkb',
                           ],
                       ],
                   ]
        );

        $this->ba->directAuth();

        $response = $this->makeRequestAndGetContent(
            [
                'method' => 'POST',
                'url' => '/callback/upi_jkbank',
                'content' => [
                    'data' => 'RandomPreProcessedDataForUpiJKUpiQrPaymentCallback',
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
        $this->assertEquals('upi_jkbank', $qrPayment->getGateway());
        $this->assertEquals($payment->getId(), $qrPayment->getPaymentId());
        $this->assertEquals(100, $qrPayment->getAmount());
        $this->assertEquals('bankTr', $qrPayment->getMerchantReference());
        $this->assertEquals('payervpa@upi', $qrPayment->getAttribute('payer_vpa'));
        $this->assertNotNull($qrPayment->getTransactionTime());

        $this->assertEquals('RndmNpciRefId', $payment->getReference16());
        $this->assertEquals('qr_code', $payment->getReceiverType());
        $this->assertEquals($qrCode->getId(), $payment->receiver->getId());
        $this->assertEquals('captured', $payment->getStatus());
        $this->assertEquals('payervpa@upi', $payment->getVpa());
        $this->assertEquals('upi_jkbank', $payment->getGateway());
        $this->assertEquals('102JkbanDedTml', $payment->getTerminalId());
        $this->assertEquals('in_person', $payment->getReference13());
        $this->assertEquals('bank_account', $payment->getReference2());
        $this->assertEquals(100, $payment->getAmount());

        $this->assertEquals('upi_jkbank', $upi->getGateway());
        $this->assertEquals($payment->getId(), $upi->getPaymentId());
        $this->assertEquals('authorize', $upi->getAction());
        $this->assertEquals('pay', $upi->getType());
        $this->assertEquals(100, $upi->getAmount());
        $this->assertEquals('payervpa@upi', $upi->getVpa());
        $this->assertEquals('bankTr', $upi->getMerchantReference());
        $this->assertEquals('RndmNpciRefId', $upi->getNpciReferenceId());
        $this->assertEquals('mozart', $upi->getAttribute('acquirer'));

        $this->assertEquals('active', $qrCode->getStatus());
        $this->assertEquals(100, $qrCode->getAttribute('payments_amount_received'));
        $this->assertEquals(1, $qrCode->getAttribute('payments_received_count'));
    }


    public function testJKBankSQRCreationViaMerchantQRCreateRouteWithOutTr()
    {
        $this->config['applications.ezetap-notification.mock'] = true;
        $this->fixtures->merchant->addFeatures(['omni_enabled'], 'LiveAccountMer');
        $this->fixtures->create('terminal:dedicated_upi_jk_terminal');

        $qrCode = $this->createMerchantQrCode(
            [
                'merchant_id'        => 'LiveAccountMer',
                'request_source'     => 'ezetap',
                'vpa'                => 'rzp.qrTest@jkb',
                'qrString'         => 'upi://pay?ver=01&pa=rzp.qrTest@jkb&pn=TestJk2&cu=INR&mc=5817&qrMedium=04&tn=PaymenttoTest',
            ]);

        $this->assertNotNull($qrCode);
        $qrCodeEntity = $this->getDbLastEntity('qr_code','live');
        $intentParam = $this->getIntentParamsFromQRString($qrCodeEntity['qr_string']);
        $this->assertEquals('rzp.qrTest@jkb', $intentParam['pa']);
        $this->assertEquals($qrCodeEntity->getId().'qrv2', $qrCodeEntity['reference']);

    }

    public function testJKBankSQRDeviceIdAdd()
    {
        $this->config['applications.ezetap-notification.mock'] = true;
        $this->fixtures->merchant->addFeatures(['omni_enabled'], 'LiveAccountMer');
        $this->getDedicatedTerminalSplitzResponseForVariantON();
        $this->setMockRazorxTreatment(
            [
                RazorxTreatment::QR_CODE_CREATE_REFACTOR_GATEWAY => 'on',
                RazorxTreatment::QR_PAYMENT_REFACTOR_GATEWAY => 'on',
            ]
        );
        $this->fixtures->create('terminal:dedicated_upi_jk_terminal');

        $qrCode = $this->createMerchantQrCode(
            [
                'merchant_id'        => 'LiveAccountMer',
                'request_source'     => 'ezetap',
                'vpa'                => 'rzp.qrTest@jkb',
                'qrString'         => 'upi://pay?ver=01&pa=rzp.qrTest@jkb&tr=bankTr&pn=TestJk2&cu=INR&mc=5817&qrMedium=04&tn=PaymenttoTest',
            ]);

        $this->assertNotNull($qrCode);
        $qrCodeEntity = $this->getDbLastEntity('qr_code','live');
        $intentParam = $this->getIntentParamsFromQRString($qrCodeEntity['qr_string']);
        $this->assertEquals('rzp.qrTest@jkb', $intentParam['pa']);
        $this->assertEquals('bankTr', $intentParam['tr']);
        $this->assertEquals('bankTr', $qrCodeEntity['reference']);
        $this->assertNull($qrCodeEntity['device_id']);


        $qrCode = $this->updateDeviceIdForQrCode(
            [
                "identifier"=>[
                    "trId"=>null,
                    'merchant_id' => 'LiveAccountMer',
                    'qr_code_id'  => $qrCode['id'],
                ],
                'device_id'   => '123456Test',
            ]);

        $qrCodeEntity1 = $this->getDbLastEntity('qr_code', 'live');

        $this->assertEquals('123456Test', $qrCodeEntity1['device_id']);
    }

    public function testJKBankSQRDeviceIdUpdate()
    {
        $this->config['applications.ezetap-notification.mock'] = true;
        $this->fixtures->merchant->addFeatures(['omni_enabled'], 'LiveAccountMer');
        $this->getDedicatedTerminalSplitzResponseForVariantON();
        $this->setMockRazorxTreatment(
            [
                RazorxTreatment::QR_CODE_CREATE_REFACTOR_GATEWAY => 'on',
                RazorxTreatment::QR_PAYMENT_REFACTOR_GATEWAY => 'on',
            ]
        );
        $this->fixtures->create('terminal:dedicated_upi_jk_terminal');

        $qrCode = $this->createMerchantQrCode(
            [
                'merchant_id'        => 'LiveAccountMer',
                'request_source'     => 'ezetap',
                'device_id'          => '12345Test',
                'vpa'                => 'rzp.qrTest@jkb',
                'qrString'         => 'upi://pay?ver=01&pa=rzp.qrTest@jkb&tr=bankTr&pn=TestJk2&cu=INR&mc=5817&qrMedium=04&tn=PaymenttoTest',
            ]);

        $this->assertNotNull($qrCode);
        $qrCodeEntity = $this->getDbLastEntity('qr_code','live');
        $intentParam = $this->getIntentParamsFromQRString($qrCodeEntity['qr_string']);
        $this->assertEquals('rzp.qrTest@jkb', $intentParam['pa']);
        $this->assertEquals('bankTr', $intentParam['tr']);
        $this->assertEquals('bankTr', $qrCodeEntity['reference']);
        $this->assertEquals('12345Test', $qrCodeEntity['device_id']);


        $qrCode = $this->unMapDeviceIdforQrCode(
            [
                'merchant_id' => 'LiveAccountMer',
                'device_id'   => "12345Test",
            ]);

        $qrCode = $this->updateDeviceIdForQrCode(
            [
                'identifier'=>[
                    'trId'=>null,
                    'merchant_id' => 'LiveAccountMer',
                    'qr_code_id'  => $qrCode['id'],
                ],
                'device_id'   => 'testi12',
            ]);

        $qrCodeEntity1 = $this->getDbLastEntity('qr_code', 'live');

        $this->assertEquals('testi12', $qrCodeEntity1['device_id']);
    }

    public function testJKBankSQRDeviceIdDelete()
    {
        $this->config['applications.ezetap-notification.mock'] = true;
        $this->fixtures->merchant->addFeatures(['omni_enabled'], 'LiveAccountMer');
        $this->getDedicatedTerminalSplitzResponseForVariantON();
        $this->setMockRazorxTreatment(
            [
                RazorxTreatment::QR_CODE_CREATE_REFACTOR_GATEWAY => 'on',
                RazorxTreatment::QR_PAYMENT_REFACTOR_GATEWAY => 'on',
            ]
        );
        $this->fixtures->create('terminal:dedicated_upi_jk_terminal');

        $qrCode = $this->createMerchantQrCode(
            [
                'merchant_id'        => 'LiveAccountMer',
                'request_source'     => 'ezetap',
                'vpa'                => 'rzp.qrTest@jkb',
                'qrString'         => 'upi://pay?ver=01&pa=rzp.qrTest@jkb&tr=bankTr&pn=TestJk2&cu=INR&mc=5817&qrMedium=04&tn=PaymenttoTest',
            ]);

        $this->assertNotNull($qrCode);
        $qrCodeEntity = $this->getDbLastEntity('qr_code','live');
        $intentParam = $this->getIntentParamsFromQRString($qrCodeEntity['qr_string']);
        $this->assertEquals('rzp.qrTest@jkb', $intentParam['pa']);
        $this->assertEquals('bankTr', $intentParam['tr']);
        $this->assertEquals('bankTr', $qrCodeEntity['reference']);

        $this->updateDeviceIdForQrCode(
            [
                "identifier"=>[
                    "trId"=>null,
                    'merchant_id' => 'LiveAccountMer',
                    'qr_code_id'  => $qrCode['id'],
                ],
                'device_id'   => "12345Test",
            ]);

        $qrCodeEntity = $this->getDbLastEntity('qr_code', 'live');
        $this->assertEquals('12345Test', $qrCodeEntity['device_id']);


        $qrCode = $this->unMapDeviceIdforQrCode(
            [
                'merchant_id' => 'LiveAccountMer',
                'device_id'   => "12345Test",
            ]);


        $qrCodeEntity1 = $this->getDbLastEntity('qr_code', 'live');

        $this->assertEquals(null, $qrCodeEntity1['device_id']);
    }

}
