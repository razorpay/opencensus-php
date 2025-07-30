<?php


namespace Functional\QrCode;

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Services\Mozart;
use RZP\Models\Pricing\Fee;
use RZP\Models\Payment\Method;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestException;
use RZP\Exception\IntegrationException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Services\UpiPayment\Service as upiPaymentService;
use RZP\Tests\Functional\PaymentsUpi\Service\UpiPaymentServiceBase;
use RZP\Tests\Functional\Helpers\QrCode\NonVirtualAccountQrCodeTrait;

class UpiIndianBankQRCodeTest extends TestCase
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
            'terminal:dedicated_upi_indian_bank_terminal',
            [
                'merchant_id' => 'LiveAccountMer',
                'gateway_merchant_id' => 'LiveAccountMer',
            ]
        );

        $this->getDedicatedTerminalSplitzResponseForVariantON();

        // Mock razorx to return 'on' for qr code create refactor experiment
        $this->setMockSplitzTreatment(
            [
                $this->config->get('app.qr_code_create_refactor_gateway') => 'off',
                $this->config->get('app.qr_payment_refactor_gateway') => 'on',
            ]
        );

        $this->config['applications.mozart.mock'] = true;

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

    public function testCreateQrCodeForUpiIndianBankWithGstDetails()
    {
        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type' => 'upi_qr',
                'fixed_amount' => true,
                'payment_amount' => 100,
                'request_source' => 'ezetap',
                'tax_invoice' => [
                    'number' => 'INV001',
                    'date' => 1589994898,
                    'customer_name' => 'Gaurav Kumar',
                    'business_gstin' => '06AABCU9605R1ZR',
                    'gst_amount' => 4000,
                    'cess_amount' => 0,
                    'supply_type' => 'intrastate',
                ],
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCode = $this->getDbLastEntity('qr_code', 'live');

        $this->assertEquals(100, $qrCode->getAmount());
        $this->assertEquals('LiveAccountMer', $qrCode->getMerchantId());
        $this->assertEquals('upi_qr', $qrCode->getProvider());
        $this->assertEquals('multiple_use', $qrCode->getUsageType());
        $this->assertEquals('active', $qrCode->getStatus());

        // Since a random qr string is generated every time, checking only if it's not null
        $this->assertNotNull($qrCode->getQrString());
    }

    public function testCreateOnlineStaticQrCodeForUpiIndianBank()
    {
        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type' => 'upi_qr',
                'fixed_amount' => true,
                'payment_amount' => 100,
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCode = $this->getDbLastEntity('qr_code', 'live');

        $this->assertEquals(100, $qrCode->getAmount());
        $this->assertEquals('LiveAccountMer', $qrCode->getMerchantId());
        $this->assertEquals('upi_qr', $qrCode->getProvider());
        $this->assertEquals('multiple_use', $qrCode->getUsageType());
        $this->assertEquals('active', $qrCode->getStatus());

        // Since a random qr string is generated every time, checking only if it's not null
        $this->assertNotNull($qrCode->getQrString());
    }


    public function testCreateQrPaymentViaRefactorFlowForUpiIndianBank()
    {
        $this->createQrCode(
            [
                'usage' => 'single_use',
                'type' => 'upi_qr',
                'fixed_amount' => true,
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

        $this->makeUpiIndianBankPayment($qrCodeEntity);

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
        $this->assertEquals('upi_indianbank', $qrPayment->getGateway());
        $this->assertEquals($payment->getId(), $qrPayment->getPaymentId());
        $this->assertEquals(100, $qrPayment->getAmount());
        $this->assertEquals($qrCode->getId() . 'qrv2', $qrPayment->getMerchantReference());
        $this->assertEquals('payervpa@upi', $qrPayment->getAttribute('payer_vpa'));

        $this->assertEquals('002002002002', $payment->getReference16());
        $this->assertEquals('qr_code', $payment->getReceiverType());
        $this->assertEquals($qrCode->getId(), $payment->receiver->getId());
        $this->assertEquals('captured', $payment->getStatus());
        $this->assertEquals('payervpa@upi', $payment->getVpa());
        $this->assertEquals('upi_indianbank', $payment->getGateway());
        $this->assertEquals('101IndbkTermnl', $payment->getTerminalId());
        $this->assertEquals('in_person', $payment->getReference13());
        $this->assertEquals(100, $payment->getAmount());

        $this->assertEquals('upi_indianbank', $upi->getGateway());
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

    public function testCreateQrPaymentViaRefactorFlowForUpiIndianBankOnStaticQr()
    {
        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type' => 'upi_qr',
                'fixed_amount' => true,
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

        $this->makeUpiIndianBankPayment($qrCodeEntity);

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
        $this->assertEquals('upi_indianbank', $qrPayment->getGateway());
        $this->assertEquals($payment->getId(), $qrPayment->getPaymentId());
        $this->assertEquals(100, $qrPayment->getAmount());
        $this->assertEquals($qrCode->getId() . 'qrv2', $qrPayment->getMerchantReference());
        $this->assertEquals('payervpa@upi', $qrPayment->getAttribute('payer_vpa'));

        $this->assertEquals('002002002002', $payment->getReference16());
        $this->assertEquals('qr_code', $payment->getReceiverType());
        $this->assertEquals($qrCode->getId(), $payment->receiver->getId());
        $this->assertEquals('captured', $payment->getStatus());
        $this->assertEquals('payervpa@upi', $payment->getVpa());
        $this->assertEquals('upi_indianbank', $payment->getGateway());
        $this->assertEquals('101IndbkTermnl', $payment->getTerminalId());
        $this->assertEquals('in_person', $payment->getReference13());
        $this->assertEquals(100, $payment->getAmount());

        $this->assertEquals('upi_indianbank', $upi->getGateway());
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

    public function testCreateQrPaymentViaRefactorFlowForUpiIndianBankWithFailedStatus()
    {
        $this->createQrCode(
            [
                'usage' => 'single_use',
                'type' => 'upi_qr',
                'fixed_amount' => true,
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

        $this->makeUpiIndianBankPaymentForFailedStatus($qrCodeEntity);

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

    public function testQrPaymentReconForUpiIndianBankWhenPaymentDoesNotExist()
    {
        $this->createQrCode(
            [
                'usage' => 'single_use',
                'type' => 'upi_qr',
                'fixed_amount' => true,
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

        $this->makeUpiIndianBankPayment($qrCodeEntity);

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
        $this->assertEquals('upi_indianbank', $qrPayment->getGateway());
        $this->assertEquals($payment->getId(), $qrPayment->getPaymentId());
        $this->assertEquals(100, $qrPayment->getAmount());
        $this->assertEquals($qrCode->getId() . 'qrv2', $qrPayment->getMerchantReference());
        $this->assertEquals('payervpa@upi', $qrPayment->getAttribute('payer_vpa'));

        $this->assertEquals('002002002002', $payment->getReference16());
        $this->assertEquals('qr_code', $payment->getReceiverType());
        $this->assertEquals($qrCode->getId(), $payment->receiver->getId());
        $this->assertEquals('captured', $payment->getStatus());
        $this->assertEquals('payervpa@upi', $payment->getVpa());
        $this->assertEquals('upi_indianbank', $payment->getGateway());
        $this->assertEquals('101IndbkTermnl', $payment->getTerminalId());
        $this->assertEquals('in_person', $payment->getReference13());
        $this->assertEquals('bank_account', $payment->getReference2());
        $this->assertEquals(100, $payment->getAmount());

        $this->assertEquals('upi_indianbank', $upi->getGateway());
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

    public function testQrPaymentReconForUpiIndianBankWhenPaymentExists()
    {
        $this->testCreateQrPaymentViaRefactorFlowForUpiIndianBank();

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
