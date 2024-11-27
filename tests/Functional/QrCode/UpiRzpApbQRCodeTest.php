<?php

namespace Functional\QrCode;

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Exception\IntegrationException;
use RZP\Services\Mozart;
use RZP\Models\Pricing\Fee;
use RZP\Models\Payment\Method;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\QrCode\NonVirtualAccountQrCodeTrait;

class UpiRzpApbQRCodeTest extends TestCase
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
        $this->fixtures->on('live')->merchant->addFeatures(['qr_codes', 'omni_enabled'], 'LiveAccountMer');
        $this->fixtures->on('live')->merchant->enableMethod('LiveAccountMer', 'upi');
        $this->fixtures->on('live')->merchant->edit('LiveAccountMer', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);
        $this->fixtures->on('live')->create('merchant_detail:sane', ['merchant_id' => 'LiveAccountMer']);

        $this->fixtures->create(
            'terminal:dedicated_upi_rzpapb_offline_terminal',
            [
                'merchant_id'         => 'LiveAccountMer',
                'gateway_merchant_id' => 'LiveAccountMer',
            ]
        );

        $this->fixtures->create(
            'terminal:dedicated_upi_rzpapb_online_terminal',
            [
                'merchant_id'         => 'LiveAccountMer',
                'gateway_merchant_id' => 'LiveAccountMer',
            ]
        );

        $this->getDedicatedTerminalSplitzResponseForVariantON();

        // Mock razorx to return 'on' for qr code create refactor experiment
        $this->setMockRazorxTreatment(
            [
                RazorxTreatment::QR_CODE_CREATE_REFACTOR_GATEWAY => 'off',
                RazorxTreatment::QR_PAYMENT_REFACTOR_EXISTING_GATEWAY => 'on',
            ]
        );

        //WARN: Remember to mock Mozart in each test, or else we shall start making network calls!!
        $this->config['applications.mozart.mock'] = false;
        $this->mockMozartResponse();

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
                                                   $reqArray['payment']['id'] . '&mode=' .
                                                   $reqArray['upi']['mode'],
                                           ],
                                           'success' => true,
                                       ]);
                }
            );
    }

    public function testCreateQrCodeForUpiRzpApbWithGstDetails()
    {
        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 100,
                'request_source' => 'ezetap',
                'tax_invoice'    => [
                    'number'         => 'INV001',
                    'date'           => 1589994898,
                    'customer_name'  => 'Gaurav Kumar',
                    'business_gstin' => '06AABCU9605R1ZR',
                    'gst_amount'     => 4000,
                    'cess_amount'    => 0,
                    'supply_type'    => 'intrastate',
                ],
            ],
            'live',
            'LiveAccountMer'
        );
    }

    public function testCreateOnlineStaticQrCodeForUpiRzpApb()
    {
        $this->fixtures->on('live')->edit('terminal', 'RzpApbOffTrmnl', ['status' => 'deactivated']);

        $this->createQrCode(
            [
                'usage'          => 'multiple_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 100,
            ],
            'live',
            'LiveAccountMer'
        );
    }

    public function testCreateOnlineDynamicQrCodeForUpiRzpApbWithExpiry()
    {
        $this->fixtures->on('live')->edit('terminal', 'RzpApbOffTrmnl', ['status' => 'deactivated']);

        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 100,
                'close_by'       => Carbon::now()->addMinutes(3)->timestamp,
            ],
            'live',
            'LiveAccountMer'
        );
    }

    public function testCreateQrPaymentViaRefactorFlowForUpiRzpApb()
    {
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

    public function testCreateQrPaymentViaRefactorFlowForUpiRzpApbOnStaticQr()
    {
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

        $this->assertEquals('active', $qrCode->getStatus());
        $this->assertEquals(100, $qrCode->getAttribute('payments_amount_received'));
        $this->assertEquals(1, $qrCode->getAttribute('payments_received_count'));
    }

    public function testCreateQrPaymentViaRefactorFlowForUpiRzpApbWithOffer()
    {
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

    public function testQrStatusCheckViaRefactorFlowForUpiRzpapb()
    {
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

        $this->testData[__FUNCTION__]['request']['content']['upi']['merchant_reference']
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
}
