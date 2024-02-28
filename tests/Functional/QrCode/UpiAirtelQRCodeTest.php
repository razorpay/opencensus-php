<?php

namespace Functional\QrCode;

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Models\Pricing\Fee;
use RZP\Exception\LogicException;
use RZP\Tests\Functional\TestCase;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Status;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\QrCode\NonVirtualAccountQrCode\CloseReason;
use RZP\Tests\Functional\Helpers\QrCode\NonVirtualAccountQrCodeTrait;

class UpiAirtelQRCodeTest extends TestCase
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


        $this->getDedicatedTerminalSplitzResponseForVariantON();

        $this->config['gateway.mock_upi_mozart'] = true;

        $this->setMockRazorxTreatment(
            [
                'api_upi_airtel_pre_process_v1' => 'upi_airtel',
                RazorxTreatment::DISABLE_QR_CODE_ON_DEMAND_CLOSE => RazorxTreatment::RAZORX_VARIANT_ON
            ]
        );
    }

    private function runQrCodeEntityAssertions(): void
    {
        $qrCodeEntity = $this->getLastEntity('qr_code', true, 'live');
        $intentParam = $this->getIntentParamsFromQRString($qrCodeEntity['qr_string']);
        $tr          = $intentParam['tr'];
        $pa          = $intentParam['pa'];
        if($qrCodeEntity['request_source'] === 'ezetap')
        {
            $this->assertEquals('testvpaOffline@mairtel', $pa);
        }
        $this->assertStringContainsString('@mairtel', $pa);

        if ($qrCodeEntity['usage'] === 'single_use')
        {
            $this->assertEquals($qrCodeEntity['reference'] . 'qrv2', $tr);

            $amount = $qrCodeEntity['amount'] / 100.00;
            $this->assertStringContainsString('am=' . $amount, $qrCodeEntity['qr_string']);
        }
        else
        {

            $mode  = $intentParam['mode'];
            $this->assertNull($mode,'mode attribute should not be present in static QR String');
            $this->assertNull($tr,'tr attribute should not be present in static QR String');
            if ($qrCodeEntity['fixed_amount'] === true)
            {
                $amount = $qrCodeEntity['amount'] / 100.00;
                $this->assertStringContainsString('am=' . $amount, $qrCodeEntity['qr_string']);
            }
        }
    }
    public function testCreateStaticAirtelQRWithoutAnyTerminal(): void
    {
        $this->expectException(LogicException::class);

        $this->expectExceptionCode(ErrorCode::SERVER_ERROR_NO_TERMINAL_FOUND);

        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ]);
    }
    public function testCreateStaticAirtelQrWithTerminal(): void
    {
        $this->fixtures->create('terminal:dedicated_upi_airtel_terminal');
        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ],
            'live',
            'LiveAccountMer');

        $this->runQrCodeEntityAssertions();
    }
    public function testCreateStaticAirtelQrWithFixedAmount(): void
    {
        $this->fixtures->create('terminal:dedicated_upi_airtel_terminal');
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

    public function testCreateDynamicAPBQrCode(): void
    {
        $this->fixtures->create('terminal:dedicated_upi_airtel_terminal');
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

    public function testCreateDynamicAPBQrWithoutTerminal(): void
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

    public function testCreateAPBQrWithExpiry(): void
    {
        //If close_by is passed in request, QR should be created via APB terminal
        $this->fixtures->create('terminal:dedicated_upi_airtel_terminal');
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
        $this->runQrCodeEntityAssertions();
    }

    public function testCreateAPBQrWithCloseQrOnDemandFlagEnabled(): void
    {
        //If CLOSE_QR_ON_DEMAND is enabled for merchant, QR should be created via APB terminal
        $this->fixtures->create('terminal:dedicated_upi_airtel_terminal');
        $this->fixtures->on('live')->merchant->addFeatures([FeatureConstants::CLOSE_QR_ON_DEMAND], 'LiveAccountMer');
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
        $this->runQrCodeEntityAssertions();
    }

    public function testCloseAPBQrWithCloseQrOnDemandFlagEnabled(): void
    {
        $this->fixtures->create('terminal:dedicated_upi_airtel_terminal');
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
        $this->assertEquals(Status::ACTIVE, $qrCode['status']);
        $closeResponse = $this->closeQrCode($qrCode['id'], 'live', 'LiveAccountMer');

    }
    public function testCloseAPBQrWithCloseQrOnDemandFlagEnabledPos(): void
    {
        $this->fixtures->create('terminal:dedicated_upi_airtel_offline_terminal');
        $qrCode = $this->createQrCode(
                    [
                        'usage'          => 'single_use',
                        'type'           => 'upi_qr',
                        'fixed_amount'   => true,
                        'payment_amount' => 300,
                    ],
                    'live',
                    'LiveAccountMer',
            headers:[
                        'X-Razorpay-Request-Source' => 'ezetap'
                    ]);
        $this->fixtures->on('live')->merchant->addFeatures([FeatureConstants::CLOSE_QR_ON_DEMAND], 'LiveAccountMer');
        $this->assertEquals(Status::ACTIVE, $qrCode['status']);
        $closeResponse = $this->closeQrCode($qrCode['id'], 'live', 'LiveAccountMer');
        $this->assertEquals(Status::CLOSED, $closeResponse['status']);
        $this->assertEquals(CloseReason::ON_DEMAND, $closeResponse['close_reason']);
    }

    public function testCloseAPBQrWithCloseQrOnDemandFlagDisabled(): void
    {
        $this->fixtures->create('terminal:dedicated_upi_airtel_terminal');
        $this->fixtures->on('live')->merchant->removeFeatures([FeatureConstants::CLOSE_QR_ON_DEMAND], 'LiveAccountMer');
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

        $closeResponse = $this->closeQrCode($qrCode['id'], 'live', 'LiveAccountMer');

    }

    public function testCreateStaticAirtelQrWithOfflineTerminal(): void
    {
        $this->fixtures->create('terminal:dedicated_upi_airtel_offline_terminal');
        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ],
            'live',
            'LiveAccountMer',
            headers:[
                'X-Razorpay-Request-Source' => 'ezetap'
            ]);
        $this->runQrCodeEntityAssertions();
    }

    public function testCreateDynamicAPBQrCodeWithOfflineTerminal(): void
    {
        $this->fixtures->create('terminal:dedicated_upi_airtel_offline_terminal');
        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
            ],
            'live',
            'LiveAccountMer',
            headers:[
                'X-Razorpay-Request-Source' => 'ezetap'
            ]);
        $this->runQrCodeEntityAssertions();
    }


    public function testCreateAPBQrWithExpiryAndOfflineTerminal(): void
    {
        //If close_by is passed in request, QR should be created via APB terminal
        $this->fixtures->create('terminal:dedicated_upi_airtel_offline_terminal');
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
            'LiveAccountMer',
            headers:[
                'X-Razorpay-Request-Source' => 'ezetap'
            ]);
        $this->runQrCodeEntityAssertions();
    }

    public function testCreateAPBQrWithCloseQrOnDemandFlagEnabledAndOfflineTerminal(): void
    {
        //If CLOSE_QR_ON_DEMAND is enabled for merchant, QR should be created via APB terminal
        $this->fixtures->create('terminal:dedicated_upi_airtel_offline_terminal');
        $this->fixtures->on('live')->merchant->addFeatures([FeatureConstants::CLOSE_QR_ON_DEMAND], 'LiveAccountMer');
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
            'LiveAccountMer',
            headers:[
                'X-Razorpay-Request-Source' => 'ezetap'
            ]);
        $this->runQrCodeEntityAssertions();
    }

    public function runQrPaymentEntityAssertions($expected = true, $paymentRequestEntity = [], $upiRequestEntity = [], $mode = 'test', $amount = 300, $amountMisMatchFlag = false ): void
    {
        $qrPayment        = $this->getLastEntity('qr_payment', true, $mode);
        $payment          = $this->getLastEntity('payment', true, $mode);
        $qrPaymentRequest = $this->getLastEntity('qr_payment_request', true, $mode);
        $qrCodeEntity     = $this->getLastEntity('qr_code', true, $mode);
        $upi              = $this->getLastEntity('upi', true, $mode);
        $intentParam      = $this->getIntentParamsFromQRString($qrCodeEntity['qr_string']);

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals($amount, $payment['amount']);
        $this->assertEquals('107611570997', $payment['reference16']);

        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        $this->assertEquals($qrCodeEntity['reference'], $qrPayment['qr_code_id']);
        $this->assertEquals($qrCodeEntity['reference'], $qrPayment['merchant_reference']);
        $this->assertEquals($paymentRequestEntity['description'], $qrPayment['notes']);
        $this->assertEquals('pullak@okhdfcbank', $upi['vpa']);
        $this->assertEquals('107611570997', $upi['npci_reference_id']);

        if ($qrCodeEntity['usage'] === 'single_use')
        {
            $this->assertEquals($upi['merchant_reference'], $intentParam['tr']);
            if ($amountMisMatchFlag == true)
            {
                $this->assertEquals('active', $qrCodeEntity['status']);
            }
            else{
                $this->assertEquals('closed', $qrCodeEntity['status']);
            }
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
        $this->fixtures->create('terminal:dedicated_upi_airtel_terminal');
        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true, 'live');

        $response = $this->makeUpiAirtelPayment($qrCodeEntity);

        $this->runQrPaymentEntityAssertions(mode: 'live');

        $this->assertTrue($response['success']);
    }
    public function testQrPaymentOnDynamicQrCode()
    {
        $this->fixtures->create('terminal:dedicated_upi_airtel_terminal');
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

        $response = $this->makeUpiAirtelPayment($qrCodeEntity);

        $this->runQrPaymentEntityAssertions(mode: 'live');

        $this->assertTrue($response['success']);
    }

    public function testQrPaymentOnInvalidQrCode()
    {
        $this->fixtures->create('terminal:dedicated_upi_airtel_terminal');
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

        $response = $this->makeUpiAirtelPayment($qrCodeEntity);

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
        // for testing unexpected payment case
        $this->fixtures->create('terminal:dedicated_upi_airtel_terminal');
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

        $response = $this->makeUpiAirtelPayment($qrCodeEntity, ['rrn' => '107611570998',]);

        $this->assertTrue($response['success']);

        $response = $this->makeUpiAirtelPayment($qrCodeEntity);

        $this->runQrPaymentEntityAssertions(expected: false, mode: 'live');

        $this->assertTrue($response['success']);
    }
    public function testPaymentOnClosedQr(): void
    {
        $this->fixtures->create('terminal:dedicated_upi_airtel_offline_terminal');
        $posQRPricingPlan = [
            'plan_id'             => '1hDYlICobzOCYt',
            'plan_name'           => 'TestMerchantPosUPIPricingPlan1',
            'payment_method'      => 'upi',
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'feature'             => 'payment',
            'receiver_type'       => 'offline',
            'fee_bearer'          => 'platform',
            'percent_rate'        => 0,
            'fixed_rate'          => 0,
            'channel'             => 'in_person',
        ];

        $this->fixtures->create('pricing', $posQRPricingPlan);
        $qrCode = $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 300,
            ],
            'live',
            'LiveAccountMer',
            headers:[
                'X-Razorpay-Request-Source' => 'ezetap'
            ]
        );
        $this->fixtures->on('live')->merchant->addFeatures([FeatureConstants::CLOSE_QR_ON_DEMAND], 'LiveAccountMer');
        $this->assertEquals(Status::ACTIVE, $qrCode['status']);
        $closeResponse = $this->closeQrCode($qrCode['id'], 'live', 'LiveAccountMer');
        $this->assertEquals(Status::CLOSED, $closeResponse['status']);
        $this->assertEquals(CloseReason::ON_DEMAND, $closeResponse['close_reason']);

        $qrCodeEntity = $this->getLastEntity('qr_code', true, 'live');

        $response = $this->makeUpiAirtelPayment($qrCodeEntity,['payeeVPA' => 'testvpaOffline@mairtel']);
        $this->runQrPaymentEntityAssertions(expected: false, mode: 'live');

        $this->assertTrue($response['success']);

    }

    public function testQrPaymentAmountMisMatchOnDynamicQrCode()
    {
        $this->fixtures->create('terminal:dedicated_upi_airtel_terminal');
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
        $response = $this->makeUpiAirtelPayment($qrCodeEntity,['amount' => '4']);
        $this->runQrPaymentEntityAssertions(expected: false, mode: 'live', amount: 400, amountMisMatchFlag: true);

        $this->assertTrue($response['success']);
    }

    public function testQrPaymentFailedCallback()
    {
        $this->fixtures->create('terminal:dedicated_upi_airtel_terminal');
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

        $response = $this->makeUpiAirtelPayment($qrCodeEntity, ['code' => '1','errorCode' => '1','messageText' => 'FAILED', 'txnStatus' => 'FAILED']);

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
    public function testCreateAPBStaticQr(): void
    {
        $this->fixtures->create('terminal:dedicated_upi_airtel_offline_terminal');
        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ],
            'live',
            'LiveAccountMer'
        );

        $this->runQrCodeEntityAssertions();
    }

    public function testCreateAPBStaticQrWithEzetapRequestSource(): void
    {
        $this->fixtures->create('terminal:dedicated_upi_airtel_offline_terminal');
        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ],
            'live',
            'LiveAccountMer',
            headers:[
                'X-Razorpay-Request-Source' => 'ezetap'
            ]
        );

        $this->runQrCodeEntityAssertions();
    }

}
