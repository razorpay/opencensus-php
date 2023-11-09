<?php

namespace Functional\QrCode;

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Models\Pricing\Fee;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\LogicException;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Tests\Functional\Helpers\QrCode\NonVirtualAccountQrCodeTrait;

class UpiKotakQRCodeTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;
    use NonVirtualAccountQrCodeTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NonVirtualAccountQrCodeTestData.php';

        parent::setUp();

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->addFeatures(['qr_codes']);

        $this->fixtures->merchant->createAccount('LiveAccountMer');

        $this->fixtures->on('live')->merchant->edit('LiveAccountMer', ['activated' => true, 'live' => true]);

        $this->fixtures->on('live')->merchant->addFeatures(['qr_codes'], 'LiveAccountMer');

        $this->fixtures->on('live')->merchant->enableMethod('LiveAccountMer', 'upi');

        $this->fixtures->on('live')->merchant->edit('LiveAccountMer', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        $this->fixtures->create('terminal:dedicated_upi_kotak_terminal');

        $this->setMockRazorxTreatment([RazorxTreatment::DISABLE_QR_CODE_ON_DEMAND_CLOSE => RazorxTreatment::RAZORX_VARIANT_ON]);

        $this->getDedicatedTerminalSplitzResponseForVariantON();

        $this->config['gateway.mock_upi_mozart'] = true;
    }


    public function testCreateStaticKotakQRWithoutAnyTerminal(): void
    {
        $this->expectException(LogicException::class);

        $this->expectExceptionCode(ErrorCode::SERVER_ERROR_NO_TERMINAL_FOUND);

        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ]);
    }

    public function testCreateStaticKotakQrWithTerminal(): void
    {
        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ],
            'live',
            'LiveAccountMer');

        $this->runQrCodeEntityAssertions();
    }

    private function runQrCodeEntityAssertions(): void
    {
        $qrCodeEntity = $this->getLastEntity('qr_code', true, 'live');

        $intentParam = $this->getIntentParamsFromQRString($qrCodeEntity['qr_string']);
        $tr          = $intentParam['tr'];
        $tid         = $intentParam['tid'];
        $pa          = $intentParam['pa'];

        $this->assertStringContainsString('@kotak', $pa);
        $this->assertEquals($qrCodeEntity['reference'] . 'qrv2', $tr);

        if ($qrCodeEntity['usage'] === 'single_use')
        {
            $this->assertEquals($qrCodeEntity['reference'], $tid);
            $amount = $qrCodeEntity['amount'] / 100.00;
            $this->assertStringContainsString('am=' . $amount, $qrCodeEntity['qr_string']);
        }
        else
        {
            $this->assertNull($tid);

            if ($qrCodeEntity['fixed_amount'] === true)
            {
                $amount = $qrCodeEntity['amount'] / 100.00;
                $this->assertStringContainsString('am=' . $amount, $qrCodeEntity['qr_string']);
            }
        }
    }

    public function testCreateStaticKotakQrWithFixedAmount(): void
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

        $this->runQrCodeEntityAssertions();
    }

    public function testCreateDynamicKotakQrCode(): void
    {
        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
            ],
            'live',
            'LiveAccountMer');

        $this->runQrCodeEntityAssertions();
    }

    public function testCreateDynamicKotakQrWithoutTerminal(): void
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

    public function testCreateKotakQrWithExpiry(): void
    {
        //If close_by is passed in request, QR should not be created via Kotak terminal

        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage(PublicErrorDescription::BAD_REQUEST_QR_CODE_CREATE_KOTAK);

        $days = 3;

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

    public function testCreateKotakQrWithCloseQrOnDemandFlagEnabled(): void
    {
        //If CLOSE_QR_ON_DEMAND is enabled for merchant, QR should not be created via Kotak terminal

        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage(PublicErrorDescription::BAD_REQUEST_QR_CODE_CREATE_KOTAK);

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

    public function testCloseKotakQrWithCloseQrOnDemandFlagEnabled(): void
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

    public function testCloseKotakQrWithCloseQrOnDemandFlagDisabled(): void
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
    public function runQrPaymentEntityAssertions($expected = true, $paymentRequestEntity = [], $upiRequestEntity = [], $mode = 'test'): void
    {
        $qrPayment        = $this->getLastEntity('qr_payment', true, $mode);
        $payment          = $this->getLastEntity('payment', true, $mode);
        $qrPaymentRequest = $this->getLastEntity('qr_payment_request', true, $mode);
        $qrCodeEntity     = $this->getLastEntity('qr_code', true, $mode);
        $upi              = $this->getLastEntity('upi', true, $mode);
        $intentParam      = $this->getIntentParamsFromQRString($qrCodeEntity['qr_string']);

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals(300, $payment['amount']);
        $this->assertEquals('107611570997', $payment['reference16']);

        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals($qrCodeEntity['reference'], $qrPayment['qr_code_id']);
        $this->assertEquals($qrCodeEntity['reference'], $qrPayment['merchant_reference']);
        $this->assertEquals($paymentRequestEntity['description'], $qrPayment['notes']);
        $this->assertEquals($upi['merchant_reference'], $intentParam['tr']);
        $this->assertEquals('pullak@okhdfcbank', $upi['vpa']);
        $this->assertEquals('107611570997', $upi['npci_reference_id']);

        if ($qrCodeEntity['usage'] === 'single_use')
        {
            $this->assertEquals('closed', $qrCodeEntity['status']);
        }
        else
        {
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

    public function testQrPaymentOnStaticQrCode()
    {
        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true, 'live');

        $response = $this->makeUpiKotakPayment($qrCodeEntity);

        $this->runQrPaymentEntityAssertions(mode: 'live');

        $this->assertTrue($response['success']);
    }

    public function testQrPaymentOnDynamicQrCode()
    {
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

        $response = $this->makeUpiKotakPayment($qrCodeEntity);

        $this->runQrPaymentEntityAssertions(mode: 'live');

        $this->assertTrue($response['success']);
    }

    public function testQrPaymentOnIntentSubType()
    {
        $this->setMockRazorxTreatment([RazorxTreatment::MAKE_QR_PAYMENT_OF_TYPE_INTENT => RazorxTreatment::RAZORX_VARIANT_ON]);

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

        $response = $this->makeUpiKotakPayment($qrCodeEntity);

        $this->runQrPaymentEntityAssertions(mode: 'live');
        $upi_metadata     = $this->getLastEntity('upi_metadata', true, 'live');

        $this->assertEquals('intent', $upi_metadata['flow']);
        $this->assertTrue($response['success']);
    }

    public function testQrPaymentOnInvalidQrCode()
    {
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
        $qrCodeEntity['id'] = '1000RandomQrId';

        $countOfQrPaymentRequestsBefore = count($this->getDbEntities(entity: 'qr_payment_request', mode: 'live'));
        $countOfQrPaymentsBefore        = count($this->getDbEntities(entity: 'qr_payment', mode: 'live'));
        $countOfPaymentsBefore          = count($this->getDbEntities(entity: 'payment', mode: 'live'));
        $countOfUpiEntitiesBefore       = count($this->getDbEntities(entity: 'upi', mode: 'live'));

        $response = $this->makeUpiKotakPayment($qrCodeEntity);

        $countOfQrPaymentRequestsAfter = count($this->getDbEntities(entity: 'qr_payment_request', mode: 'live'));
        $countOfQrPaymentsAfter        = count($this->getDbEntities(entity: 'qr_payment', mode: 'live'));
        $countOfPaymentsAfter          = count($this->getDbEntities(entity: 'payment', mode: 'live'));
        $countOfUpiEntitiesAfter       = count($this->getDbEntities(entity: 'upi', mode: 'live'));

        $this->assertEquals($countOfUpiEntitiesBefore, $countOfUpiEntitiesAfter);
        $this->assertEquals($countOfQrPaymentsBefore, $countOfQrPaymentsAfter);
        $this->assertEquals($countOfQrPaymentRequestsBefore, $countOfQrPaymentRequestsAfter);
        $this->assertEquals($countOfPaymentsBefore, $countOfPaymentsAfter);

        $this->assertFalse($response['success']);
    }

    public function testMultipleQrPaymentsOnDynamicQrCode()
    {
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

        $response = $this->makeUpiKotakPayment($qrCodeEntity, ['rrn' => '107611570998',]);

        $this->assertTrue($response['success']);

        $response = $this->makeUpiKotakPayment($qrCodeEntity);

        $this->runQrPaymentEntityAssertions(expected: false, mode: 'live');

        $this->assertTrue($response['success']);
    }

    public function testQrPaymentFailedCallback()
    {
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

        $countOfQrPaymentRequestsBefore = count($this->getDbEntities(entity: 'qr_payment_request', mode: 'live'));
        $countOfQrPaymentsBefore        = count($this->getDbEntities(entity: 'qr_payment', mode: 'live'));
        $countOfPaymentsBefore          = count($this->getDbEntities(entity: 'payment', mode: 'live'));
        $countOfUpiEntitiesBefore       = count($this->getDbEntities(entity: 'upi', mode: 'live'));

        $response = $this->makeUpiKotakPayment($qrCodeEntity, ['statusCode' => '11', 'status' => 'FAILED']);

        $countOfQrPaymentRequestsAfter = count($this->getDbEntities(entity: 'qr_payment_request', mode: 'live'));
        $countOfQrPaymentsAfter        = count($this->getDbEntities(entity: 'qr_payment', mode: 'live'));
        $countOfPaymentsAfter          = count($this->getDbEntities(entity: 'payment', mode: 'live'));
        $countOfUpiEntitiesAfter       = count($this->getDbEntities(entity: 'upi', mode: 'live'));

        $this->assertEquals($countOfUpiEntitiesBefore, $countOfUpiEntitiesAfter);
        $this->assertEquals($countOfQrPaymentsBefore, $countOfQrPaymentsAfter);
        $this->assertEquals($countOfQrPaymentRequestsBefore + 1, $countOfQrPaymentRequestsAfter);
        $this->assertEquals($countOfPaymentsBefore, $countOfPaymentsAfter);

        $qrPaymentRequest = $this->getLastEntity('qr_payment_request', true, 'live');

        $this->assertEquals(str_after($qrCodeEntity['id'], 'qr_'), $qrPaymentRequest['qr_code_id']);
        $this->assertEquals('failed callback', $qrPaymentRequest['failure_reason']);
        $this->assertArraySelectiveEquals(['source' => 'callback', 'request_from' => 'bank'], json_decode($qrPaymentRequest['request_source'], true));
        $this->assertEquals('107611570997', $qrPaymentRequest['transaction_reference']);
        $this->assertEquals(0, $qrPaymentRequest['is_created']);

        // We return success as true in this case
        $this->assertTrue($response['success']);
    }

    public function testProcessKotakQrReconInternalWithoutPayment(): void
    {
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

        $request = $this->testData['testProcessKotakQrPaymentInternal'];
        $request['content']['data']['upi']['merchant_reference'] = $qrCodeEntity['reference'] . 'qrv2';
        $request['content']['data']['upi']['npci_reference_id'] = (string) random_int(100000000000, 999999999999);

        $response = $this->makeUpiPaymentInternal($request);

        $payment = $this->getDbLastEntity('payment', 'live');

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(300, $payment['amount']);
        $this->assertEquals('upi_kotak', $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);
        $this->assertEquals($response['payment']['id'], 'pay_' . $payment['id']);
        $this->assertEquals('captured', $response['payment']['status']);
    }

    public function testProcessKotakQrReconInternalWithExistingPayment(): void
    {
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
        $this->makeUpiKotakPayment($qrCodeEntity);
        $existingPayment = $this->getDbLastEntity('payment', 'live');

        $request = $this->testData['testProcessKotakQrPaymentInternal'];
        $request['content']['data']['upi']['merchant_reference'] = $qrCodeEntity['reference'] . 'qrv2';
        $request['content']['data']['upi']['npci_reference_id'] = $existingPayment['reference16'];

        $response = $this->makeUpiPaymentInternal($request);

        $payment = $this->getDbLastEntity('payment', 'live');

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(300, $payment['amount']);
        $this->assertEquals('upi_kotak', $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);
        $this->assertEquals($response['payment']['id'], 'pay_' . $payment['id']);
        $this->assertEquals('captured', $response['payment']['status']);
        $this->assertEquals($existingPayment['id'], $payment['id']);
    }
}
