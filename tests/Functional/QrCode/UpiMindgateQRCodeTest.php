<?php

namespace Functional\QrCode;

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Models\Payment\Gateway;
use RZP\Models\Pricing\Fee;
use RZP\Models\QrCode\Type;
use RZP\Models\QrCode;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\LogicException;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Tests\Functional\Helpers\QrCode\NonVirtualAccountQrCodeTrait;

class UpiMindgateQRCodeTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;
    use NonVirtualAccountQrCodeTrait;

    private $terminal;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NonVirtualAccountQrCodeTestData.php';

        parent::setUp();

        $this->config['gateway.mock_upi_mozart'] = true;

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->addFeatures(['qr_codes']);

        $this->fixtures->merchant->edit('10000000000000', ['billing_label' => 'more-megastore-account']);

        $this->fixtures->merchant->activate();

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        $this->fixtures->merchant->createAccount('LiveAccountMer');

        $this->fixtures->on('live')->merchant->edit('LiveAccountMer', ['activated' => true, 'live' => true]);

        $this->fixtures->on('live')->merchant->addFeatures(['qr_codes'], 'LiveAccountMer');

        $this->fixtures->on('live')->merchant->enableMethod('LiveAccountMer', 'upi');

        $this->fixtures->on('live')->merchant->edit('LiveAccountMer', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        $this->terminal = $this->fixtures->create('terminal:dedicated_upi_mindgate_terminal');

        $this->setMockRazorxTreatment([RazorxTreatment::DISABLE_QR_CODE_ON_DEMAND_CLOSE => RazorxTreatment::RAZORX_VARIANT_ON]);

        $this->getDedicatedTerminalSplitzResponseForVariantON();
    }

    public function testCreateStaticQRWithoutAnyTerminal(): void
    {
        $this->expectException(LogicException::class);

        $this->expectExceptionCode(ErrorCode::SERVER_ERROR_NO_TERMINAL_FOUND);

        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ]);
    }

    public function testCreateDynamicQrWithoutTerminal(): void
    {
        $this->expectException(LogicException::class);

        $this->expectExceptionCode(ErrorCode::SERVER_ERROR_NO_TERMINAL_FOUND);

        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
            ]);
    }

    public function testCreateStaticQrDedicatedTerminal()
    {
        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type' => 'upi_qr',
            ],
            'live',
            'LiveAccountMer');

        $this->runEntityAssertionsForDedicatedTerminalQr();
    }

    public function testCreateStaticQrWithFixedAmount(): void
    {
        $this->createQrCode(
            [
                'usage'          => 'multiple_use',
                'payment_amount' => '300',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
            ],
            'live',
            'LiveAccountMer');

        $this->runEntityAssertionsForDedicatedTerminalQr();
    }

    public function testCreateDynamicQrDedicatedTerminal()
    {
        $this->createQrCode(
            [
                'usage'         => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 10000
            ],
            'live',
            'LiveAccountMer');

        $this->runEntityAssertionsForDedicatedTerminalQr();

    }

    public function testSingleUseQrCodeWithCloseByWithExperimentOn()
    {
        $this->setMockRazorxTreatment(
            [
                RazorxTreatment::DISABLE_QR_CODE_ON_DEMAND_CLOSE => RazorxTreatment::RAZORX_VARIANT_ON,
                RazorxTreatment::HDFC_QR_EXPIRY => RazorxTreatment::RAZORX_VARIANT_ON,
            ]
        );

        $days = 3;

        $expiryTime = Carbon::now()->getTimestamp() + ($days * 24 * 60 * 60);

        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
                'close_by'       => $expiryTime,
            ],
            'live',
            'LiveAccountMer');

        $this->runEntityAssertionsForDedicatedTerminalQr();

        $qrCode = $this->getLastEntity('qr_code', true, 'live');

        $this->assertEquals($expiryTime, $qrCode['close_by']);
        $this->assertStringContainsString('@hdfcbank', $qrCode['qr_string']);
    }

    public function testSingleUseQrCodeWithCloseByWithExperimentOff()
    {
        $this->setMockRazorxTreatment(
            [
                RazorxTreatment::DISABLE_QR_CODE_ON_DEMAND_CLOSE => RazorxTreatment::RAZORX_VARIANT_ON,
                RazorxTreatment::HDFC_QR_EXPIRY => 'control',
            ]
        );

        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage(PublicErrorDescription::BAD_REQUEST_QR_CODE_CREATE_HDFC);

        $days = 3;

        $expiryTime = Carbon::now()->getTimestamp() + ($days * 24 * 60 * 60);

        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
                'close_by'       => $expiryTime,
            ],
            'live',
            'LiveAccountMer');
    }

    // If the expiry is greater than 7 days, then an HDFC terminal is not picked up at all
    // Thus, different tests for this for different states of the experiment are not needed
    public function testSingleUseQrCodeWithCloseByGreaterThan7Days()
    {
        $days = 8;

        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage(PublicErrorDescription::BAD_REQUEST_QR_CODE_CREATE_HDFC);

        $expiryTime = Carbon::now()->getTimestamp() + ($days * 24 * 60 * 60);

        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
                'close_by'       => $expiryTime,
            ],
            'live',
            'LiveAccountMer');
    }

    public function testCreateQrWithCloseQrOnDemandFlagEnabled(): void
    {
        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage(PublicErrorDescription::BAD_REQUEST_QR_CODE_CREATE_HDFC);

        $this->fixtures->on('live')->merchant->addFeatures([FeatureConstants::CLOSE_QR_ON_DEMAND], 'LiveAccountMer');

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

    public function testCloseMindgateQrWithCloseQrOnDemandFlagEnabled(): void
    {
        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage(PublicErrorDescription::BAD_REQUEST_ON_DEMAND_QR_CODE_DISABLED);

        $qrCode = $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
            ],
            'live',
            'LiveAccountMer');

        $this->fixtures->on('live')->merchant->addFeatures([FeatureConstants::CLOSE_QR_ON_DEMAND], 'LiveAccountMer');

        $this->closeQrCode($qrCode['id']);
    }

    public function testCloseMindgateQrWithCloseQrOnDemandFlagDisabled(): void
    {
        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage(PublicErrorDescription::BAD_REQUEST_ON_DEMAND_QR_CODE_DISABLED);

        $qrCode = $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
            ],
            'live',
            'LiveAccountMer');

        $this->closeQrCode($qrCode['id']);
    }

    private function runEntityAssertionsForDedicatedTerminalQr()
    {
        $qrCodeEntity = $this->getLastEntity('qr_code', true, 'live');

        if ($qrCodeEntity['provider'] === 'upi_qr')
        {
            $intentParam = $this->getIntentParamsFromQRString($qrCodeEntity['qr_string']);
            $this->assertStringContainsString('@hdfcbank', $intentParam['pa']);

            if($qrCodeEntity['usage']==='single_use')
            {
                $this->assertStringContainsString($intentParam['tr'], $qrCodeEntity['reference'] . 'qrv2');
            }
            else
            {
                $this->assertStringContainsString( $intentParam['tr'], QrCode\Constants::QR_CODE_V2_HDFC_PREFIX . $qrCodeEntity['reference'] . 'qrv2');
            }
        }

        if($qrCodeEntity['usage'] === 'single_use')
        {
            $amount = $qrCodeEntity['amount'] / 100;
            $this->assertStringContainsString('am=' . $amount, $qrCodeEntity['qr_string']);
        }
        else
            if ($qrCodeEntity['fixed_amount'] === true)
            {
                $amount = $qrCodeEntity['amount'] / 100;
                $this->assertStringContainsString('am=' . $amount, $qrCodeEntity['qr_string']);
            }
    }

    public function testPaymentForStaticQrCode(): void
    {
        $this->setMockRazorxTreatment(['api_upi_mindgate_pre_process_v1' => 'upi_mindgate']);

        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true,'live');

        $response = $this->makeUpiMindgatePayment($qrCodeEntity, $this->terminal);

        $this->runQrPaymentEntityAssertions();

        $this->assertTrue($response['success']);
    }

    public function testPaymentOnDynamicQrCode() :void
    {
        $this->setMockRazorxTreatment(['api_upi_mindgate_pre_process_v1' => 'upi_mindgate']);

        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true,'live');

        $response = $this->makeUpiMindgatePayment($qrCodeEntity, $this->terminal);

        $this->runQrPaymentEntityAssertions();

        $this->assertTrue($response['success']);
    }

    public function testQrPaymentOnInvalidQrCode()
    {
        $this->setMockRazorxTreatment(['api_upi_mindgate_pre_process_v1' => 'upi_mindgate']);

        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true, 'live');

        // Changing the ID of the QR to make the request invalid
        $qrCodeEntity['id'] = 'qr_ABCEDF12345678';
        $qrCodeEntity['reference'] = 'ABCEDF12345678';

        $countOfQrPaymentRequestsBefore = count($this->getDbEntities(entity: 'qr_payment_request', mode: 'live'));
        $countOfQrPaymentsBefore        = count($this->getDbEntities(entity: 'qr_payment', mode: 'live'));
        $countOfPaymentsBefore          = count($this->getDbEntities(entity: 'payment', mode: 'live'));
        $countOfUpiEntitiesBefore       = count($this->getDbEntities(entity: 'upi', mode: 'live'));

        $response = $this->makeUpiMindgatePayment($qrCodeEntity,$this->terminal);

        $countOfQrPaymentRequestsAfter = count($this->getDbEntities(entity: 'qr_payment_request', mode: 'live'));
        $countOfQrPaymentsAfter        = count($this->getDbEntities(entity: 'qr_payment', mode: 'live'));
        $countOfPaymentsAfter          = count($this->getDbEntities(entity: 'payment', mode: 'live'));
        $countOfUpiEntitiesAfter       = count($this->getDbEntities(entity: 'upi', mode: 'live'));

        $this->assertEquals($countOfUpiEntitiesBefore, $countOfUpiEntitiesAfter);
        $this->assertEquals($countOfQrPaymentsBefore, $countOfQrPaymentsAfter);
        $this->assertEquals($countOfQrPaymentRequestsBefore, $countOfQrPaymentRequestsAfter);
        $this->assertEquals($countOfPaymentsBefore, $countOfPaymentsAfter);
    }

    public function testMultipleQrPaymentsOnDynamicQrCode()
    {
        $this->setMockRazorxTreatment(['api_upi_mindgate_pre_process_v1' => 'upi_mindgate']);

        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true, 'live');

        $response = $this->makeUpiMindgatePayment($qrCodeEntity,$this->terminal, ['rrn' => '107611570998',]);

        $this->assertTrue($response['success']);

        $response = $this->makeUpiMindgatePayment($qrCodeEntity, $this->terminal);

        $this->runQrPaymentEntityAssertions( false);

        $this->assertTrue($response['success']);
    }

    public function runQrPaymentEntityAssertions($expected = true, $paymentRequestEntity = [], $upiRequestEntity = []): void
    {
        $qrPayment        = $this->getLastEntity('qr_payment', true,'live');
        $payment          = $this->getLastEntity('payment', true,'live');
        $qrPaymentRequest = $this->getLastEntity('qr_payment_request', true,'live');
        $qrCodeEntity     = $this->getLastEntity('qr_code', true,'live');
        $upi              = $this->getLastEntity('upi', true,'live');
        $intentParam = $this->getIntentParamsFromQRString($qrCodeEntity['qr_string']);

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals(300, $payment['amount']);
        $this->assertEquals('107611570997', $payment['reference16']);

        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals($qrCodeEntity['reference'], $qrPayment['qr_code_id']);
        $this->assertEquals($qrCodeEntity['reference'], $qrPayment['merchant_reference']);
        $this->assertEquals($paymentRequestEntity['description'], $qrPayment['notes']);
        $this->assertEquals('107611570997', $upi['npci_reference_id']);
        $this->assertNotNull($qrPayment['transaction_time']);

        if ($qrCodeEntity['usage'] === 'single_use')
        {
            $this->assertEquals($upi['merchant_reference'] , $intentParam['tr']);
            $this->assertEquals('closed', $qrCodeEntity['status']);
        }
        else
        {
            $this->assertEquals($upi['merchant_reference'] , $intentParam['tr'] . '!' . $upi['gateway_payment_id']);
            $this->assertEquals('active', $qrCodeEntity['status']);
        }

        if ($expected === true)
        {
            $this->assertEquals(null, $qrPaymentRequest['failure_reason']);
            $this->assertEquals(true, $qrPaymentRequest['expected']);
            $this->assertEquals('captured', $payment['status']);
            $this->assertEquals(true, $qrPayment['expected']);
        }
        else
        {
            $this->assertEquals(false, $qrPaymentRequest['expected']);
            $this->assertEquals('refunded', $payment['status']);
            $this->assertEquals(false, $qrPayment['expected']);
        }
    }

    // payment is not found against qr code data so create a new payment and capture it
    public function testProcessMindgateQrPaymentInternal()
    {
        $this->fixtures->create('terminal:dedicated_upi_mindgate_terminal_test_merchant');

        $qrCode = $this->createQrCode([
                'usage'          => 'multiple_use',
                'type'           => 'upi_qr',
            ]);

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData[__FUNCTION__];

        $rrn = '000011100101';
        $request['content']['data']['upi']['npci_reference_id'] = $rrn;
        $request['content']['data']['upi']['merchant_reference'] = 'STQ' . $qrCodeId . 'qrv2!84872094692';

        $response = $this->makeUpiPaymentInternal($request);

        $payment     = $this->getDbLastEntity('payment');

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals(Gateway::UPI_MINDGATE, $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);
        $this->assertEquals($response['payment']['id'], 'pay_' . $payment['id']);
        $this->assertEquals('captured', $response['payment']['status']);
    }

    // payment is not found against single use qr code data so create a new payment
    // but qr code amount is not matching so initiate refund during recon
    public function testProcessMindgateQrPaymentInternalWithRefund()
    {
        $this->fixtures->create('terminal:dedicated_upi_mindgate_terminal_test_merchant');

        $qrCode = $this->createQrCode([
            'usage'          => 'single_use',
            'type'           => 'upi_qr',
            'fixed_amount'   => true,
            'payment_amount' => 1000
        ]);

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData["testProcessMindgateQrPaymentInternal"];

        $rrn = '000011100101';
        $request['content']['data']['upi']['npci_reference_id'] = $rrn;
        $request['content']['data']['upi']['merchant_reference'] = $qrCodeId . 'qrv2';

        $response = $this->makeUpiPaymentInternal($request);

        $payment     = $this->getDbLastEntity('payment');

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('refunded', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals(Gateway::UPI_MINDGATE, $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);
        $this->assertEquals($response['payment']['id'], 'pay_' . $payment['id']);
        $this->assertEquals('refunded', $response['payment']['status']);

        $this->assertArrayHasKey('refunds', $response);

        $this->assertEquals($response['payment']['id'], $response['refunds'][0]['payment_id']);
        $this->assertEquals($response['payment']['amount'], $response['refunds'][0]['amount']);
    }

    // payment is not found against qr code data so create a new payment and capture it
    public function testProcessMindgateQrPaymentInternalWithPayerAccountType()
    {
        $this->fixtures->create('terminal:dedicated_upi_mindgate_terminal_test_merchant');

        $qrCode = $this->createQrCode([
            'usage'          => 'single_use',
            'type'           => 'upi_qr',
            'fixed_amount'   => true,
            'payment_amount' => 4000
        ]);

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData["testProcessMindgateQrPaymentInternal"];

        $rrn = '000011100101';
        $payerAccountType = 'bank_account';

        $request['content']['data']['upi']['npci_reference_id'] = $rrn;
        $request['content']['data']['upi']['merchant_reference'] = $qrCodeId . 'qrv2';
        $request['content']['data']['payment']['payer_account_type'] = $payerAccountType;

        $response = $this->makeUpiPaymentInternal($request);

        $payment     = $this->getDbLastEntity('payment');

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals(Gateway::UPI_MINDGATE, $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);
        $this->assertEquals($response['payment']['id'], 'pay_' . $payment['id']);
        $this->assertEquals('captured', $response['payment']['status']);
        $this->assertEquals('bank_account', $payment['reference2']);
    }

    // payment is not found against qr code data so create a new payment and capture it
    public function testProcessMindgateQrPaymentInternalWithInvalidPayerAccountType()
    {
        $this->fixtures->create('terminal:dedicated_upi_mindgate_terminal_test_merchant');

        $qrCode = $this->createQrCode([
            'usage'          => 'single_use',
            'type'           => 'upi_qr',
            'fixed_amount'   => true,
            'payment_amount' => 4000
        ]);

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData["testProcessMindgateQrPaymentInternal"];

        $rrn = '000011100101';
        $payerAccountType = 'INVALIDTYPE';

        $request['content']['data']['upi']['npci_reference_id'] = $rrn;
        $request['content']['data']['upi']['merchant_reference'] = $qrCodeId . 'qrv2';
        $request['content']['data']['payment']['payer_account_type'] = $payerAccountType;

        $response = $this->makeUpiPaymentInternal($request);

        $payment     = $this->getDbLastEntity('payment');

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals(Gateway::UPI_MINDGATE, $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);
        $this->assertEquals($response['payment']['id'], 'pay_' . $payment['id']);
        $this->assertEquals('captured', $response['payment']['status']);
        $this->assertNull($payment['reference2']);
    }

    // invalid qr code id is passed in payload
    // so create a payment against dummy qr code and refund it
    public function testProcessMindgateQrPaymentInternalQrNotFound()
    {
        $this->fixtures->create('terminal:dedicated_upi_mindgate_terminal_test_merchant');

        $requestInternal = $this->testData['testProcessMindgateQrPaymentInternal'];

        $request['content']['data']['upi']['merchant_reference'] = 'qwertyuiop1234qrv2';

        $response = $this->makeUpiPaymentInternal($requestInternal);

        $payment = $this->getDbLastEntity('payment', 'live');

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('refunded', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals(Gateway::UPI_MINDGATE, $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);
        $this->assertEquals($response['payment']['id'], 'pay_' . $payment['id']);
        $this->assertEquals('refunded', $response['payment']['status']);

        $this->assertArrayHasKey('refunds', $response);

        $this->assertEquals($response['payment']['id'], $response['refunds'][0]['payment_id']);
        $this->assertEquals($response['payment']['amount'], $response['refunds'][0]['amount']);
    }

    // terminal is not found against qr code data so throw error
    public function testProcessMindgateQrPaymentInternalTerminalNotFound()
    {
        $this->fixtures->create('terminal:dedicated_upi_mindgate_terminal_test_merchant');

        $requestInternal = $this->testData['testProcessMindgateQrPaymentInternal'];

        $requestInternal['content']['data']['terminal']['gateway_merchant_id'] = '1234567';

        $this->expectException(BadRequestException::class);

        $this->expectExceptionCode(ErrorCode::SERVER_ERROR_QR_PAYMENT_PROCESSING_FAILED);

        $this->expectExceptionMessage('Qr payment processing failed');

        $this->makeUpiPaymentInternal($requestInternal);

    }

    // payment is already created so no need to create payment during recon on 2nd time
    public function testProcessMindgateQrPaymentInternalDuplicate()
    {
        $this->fixtures->create('terminal:dedicated_upi_mindgate_terminal_test_merchant');

        $qrCode = $this->createQrCode([
            'usage'          => 'single_use',
            'type'           => 'upi_qr',
            'fixed_amount'   => true,
            'payment_amount' => 4000
        ]);

        $qrCodeId = $qrCode['id'];

        $this->fixtures->stripSign($qrCodeId);

        $request = $this->testData["testProcessMindgateQrPaymentInternal"];

        $rrn = '000011100101';
        $request['content']['data']['upi']['npci_reference_id'] = $rrn;
        $request['content']['data']['upi']['merchant_reference'] = $qrCodeId . 'qrv2';

        // Todo: make first payment via callback not internal, once hdfc qr v2 migration  is completed
        $this->makeUpiPaymentInternal($request);

        $response = $this->makeUpiPaymentInternal($request);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(4000, $payment['amount']);
        $this->assertEquals(Gateway::UPI_MINDGATE, $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);
        $this->assertEquals($response['payment']['id'], 'pay_' . $payment['id']);
        $this->assertEquals('captured', $response['payment']['status']);
    }

    public function testSingleUseQrCodeWithCloseByForEzetap()
    {
        $days =1;
        $this->setMockRazorxTreatment(
            [
                RazorxTreatment::HDFC_QR_EXPIRY => RazorxTreatment::RAZORX_VARIANT_ON,
            ]
        );
        $this->fixtures->on('live')->merchant->addFeatures([FeatureConstants::CLOSE_QR_ON_DEMAND], 'LiveAccountMer');
        $qrCode = $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 100,
                'close_by'       => Carbon::now()->getTimestamp() + ($days * 24 * 60 * 60),
            ],
            'live',
            'LiveAccountMer',
            [
                'X-Razorpay-Request-Source' => 'ezetap'
            ]
        );
        $this->runEntityAssertionsForDedicatedTerminalQr();
    }


}
