<?php

namespace Functional\QrCode;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Error\ErrorCode;
use RZP\Models\Payment\Gateway;
use RZP\Models\Pricing\Fee;
use RZP\Models\QrCode\Type;
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
        $this->fixtures->create('terminal:dedicated_upi_mindgate_terminal');

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
        $this->fixtures->create('terminal:dedicated_upi_mindgate_terminal');

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
        $this->fixtures->create('terminal:dedicated_upi_mindgate_terminal');

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

    public function testSingleUseQrCodeWithCloseBy()
    {
        $this->fixtures->create('terminal:dedicated_upi_mindgate_terminal');

        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage(PublicErrorDescription::BAD_REQUEST_QR_CODE_CREATE_HDFC);

        $days =3;
        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
                'close_by'       => Carbon::now()->getTimestamp() + ($days * 24 * 60 * 60),
            ],
            'live',
            'LiveAccountMer');
    }

    public function testCreateQrWithCloseQrOnDemandFlagEnabled(): void
    {
        $this->fixtures->create('terminal:dedicated_upi_mindgate_terminal');

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
        $this->fixtures->create('terminal:dedicated_upi_mindgate_terminal');

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
        $this->fixtures->create('terminal:dedicated_upi_mindgate_terminal');

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
                $this->assertStringContainsString( $intentParam['tr'], 'STQ' . $qrCodeEntity['reference'] . 'qrv2');
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
        $request['content']['data']['upi']['merchant_reference'] = 'STQ' . $qrCodeId . 'qrv2';

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
        $payerAccountType = 'CREDIT!123456';

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
        $this->assertEquals('credit_card', $payment['reference2']);
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

        $this->expectException(ServerErrorException::class);

        $this->expectExceptionCode(ErrorCode::SERVER_ERROR_QR_PAYMENT_PROCESSING_FAILED);

        $this->expectExceptionMessage('Terminal should not be null here');

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

}
