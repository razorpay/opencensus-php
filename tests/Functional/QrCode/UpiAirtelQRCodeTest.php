<?php

namespace Functional\QrCode;

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Models\Pricing\Fee;
use RZP\Models\Payment\Method;
use RZP\Models\Merchant\Account;
use RZP\Exception\LogicException;
use RZP\Models\Terminal\Type;
use RZP\Tests\Functional\TestCase;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestException;
use RZP\Reconciliator\Base\Reconciliate;
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

        $this->fixtures->merchant->activate();

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        $this->fixtures->merchant->createAccount('LiveAccountMer');

        $this->config['applications.ezetap-notification.mock'] = true;

        $this->fixtures->on('live')->merchant->edit('LiveAccountMer', ['activated' => true, 'live' => true]);

        $this->fixtures->on('live')->merchant->addFeatures(['qr_codes'], 'LiveAccountMer');

        $this->fixtures->on('live')->merchant->enableMethod('LiveAccountMer', 'upi');

        $this->fixtures->on('live')->merchant->edit('LiveAccountMer', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);


        $this->getDedicatedTerminalSplitzResponseForVariantON();

        $this->config['gateway.mock_upi_mozart'] = true;

        $this->setMockRazorxTreatment(
            [
                'api_upi_airtel_pre_process_v1' => 'upi_airtel',
                RazorxTreatment::DISABLE_QR_CODE_ON_DEMAND_CLOSE => RazorxTreatment::RAZORX_VARIANT_ON,
                RazorxTreatment::QRV2_STATIC_QR_UNRECOGNISED_PAYMENT_PROCESS => RazorxTreatment::RAZORX_VARIANT_ON,
                RazorxTreatment::QRV2_STATIC_QR_UNRECOGNISED_PAYMENT_RAMP => RazorxTreatment::RAZORX_VARIANT_ON,
                RazorxTreatment::QR_GATEWAY_UNRECOGNISED_PAYMENT_PROCESS => RazorxTreatment::RAZORX_VARIANT_ON
            ]
        );
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

    private function runQrCodeEntityAssertions($mode='live'): void
    {
        $qrCodeEntity = $this->getLastEntity('qr_code', true, $mode);
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

            $qrMode  = $intentParam['mode'];
            $this->assertNull($qrMode,'mode attribute should not be present in static QR String');
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

    public function testCreateStaticAirtelQrWithTerminalWithUnrecognisedPaymentProcessExperimentsDisabled(): void
    {
        $this->fixtures->create('terminal:dedicated_upi_airtel_terminal');

        $this->setMockRazorxTreatment(
            [
                'api_upi_airtel_pre_process_v1'                  => 'upi_airtel',
                RazorxTreatment::DISABLE_QR_CODE_ON_DEMAND_CLOSE => RazorxTreatment::RAZORX_VARIANT_ON,
            ]
        );

        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ],
            'live',
            'LiveAccountMer');

        $this->runQrCodeEntityAssertions();

        $qrCodeConfig = $this->getLastEntity('qr_code_config', true, 'live');
        $this->assertNull($qrCodeConfig);

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
        $this->fixtures->merchant->addFeatures(['omni_enabled'], 'LiveAccountMer');

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
        $terminal = $this->fixtures->create('terminal:dedicated_upi_airtel_offline_terminal');
        $qrCode   = $this->createQrCode(
                     [
                         'usage' => 'multiple_use',
                         'type'  => 'upi_qr',
                         'vpa'   => 'testvpaOffline@mairtel',
                     ],
                     'live',
                     'LiveAccountMer',
            headers: [
                         'X-Razorpay-Request-Source' => 'ezetap'
                     ]);
        $this->runQrCodeEntityAssertions();

        $qrCodeConfig = $this->getLastEntity('qr_code_config', true, 'live');
        $qrCodeId     = substr($qrCode['id'], 3, 14);

        $this->assertStringContainsString($qrCodeId, $qrCodeConfig['config_value']);
        $this->assertStringContainsString($terminal['id'], $qrCodeConfig['config_value']);
    }

    public function testCreateDynamicAPBQrCodeWithOfflineTerminal(): void
    {
        $this->fixtures->merchant->addFeatures(['omni_enabled'], 'LiveAccountMer');

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
        $this->fixtures->merchant->addFeatures(['omni_enabled'], 'LiveAccountMer');

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
        $this->fixtures->merchant->addFeatures(['omni_enabled'], 'LiveAccountMer');

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

    public function runQrPaymentEntityAssertions($expected = true, $paymentRequestEntity = [], $upiRequestEntity = [], $mode = 'test', $amount = 300, $amountMisMatchFlag = false, $rrn = '107611570997', $fallbackQrCode = false): void
    {
        $qrPayment        = $this->getLastEntity('qr_payment', true, $mode);
        $payment          = $this->getLastEntity('payment', true, $mode);
        $qrPaymentRequest = $this->getLastEntity('qr_payment_request', true, $mode);
        $qrCodeEntity     = $this->getLastEntity('qr_code', true, $mode);
        $upi              = $this->getLastEntity('upi', true, $mode);
        $intentParam      = $this->getIntentParamsFromQRString($qrCodeEntity['qr_string']);

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals($amount, $payment['amount']);
        $this->assertEquals($rrn, $payment['reference16']);

        $this->assertEquals('pay_' . $qrPayment['payment_id'], $payment['id']);
        if($fallbackQrCode === true)
        {
            $this->assertEquals('FallbackQrCode', $qrPayment['qr_code_id']);
            $this->assertEquals('RandomQrId', $qrPayment['merchant_reference']);
        }
        else
        {
            $this->assertEquals($qrCodeEntity['reference'], $qrPayment['qr_code_id']);
            $this->assertEquals($qrCodeEntity['reference'], $qrPayment['merchant_reference']);
        }
        $this->assertEquals($paymentRequestEntity['description'], $qrPayment['notes']);
        $this->assertEquals('pullak@okhdfcbank', $upi['vpa']);
        $this->assertEquals($rrn, $upi['npci_reference_id']);

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
        $this->fixtures->merchant->addFeatures(['omni_enabled'], 'LiveAccountMer');

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

    public function testOfflineStaticQrWithRandomMerchantReference(): void
    {

        $this->fixtures->merchant->addFeatures(['omni_enabled']);

        $terminal = $this->fixtures->create(
            'terminal:dedicated_upi_airtel_offline_terminal',
            [
                'merchant_id' => '10000000000000'
            ]
        );

        $this->createPricingForOffline();

        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type' => 'upi_qr',
                'vpa'   => 'testvpaOffline@mairtel',
            ],
            headers: [
                'X-Razorpay-Request-Source' => 'ezetap'
            ]
        );

        $this->runQrCodeEntityAssertions('test');

        $qrCodeEntity = $this->getLastEntity('qr_code', true);

        $this->makeUpiAirtelPayment($qrCodeEntity, ['payeeVPA' => 'testvpaOffline@mairtel', 'hdnOrderID' => 'Random78']);

        $existingPayment = $this->getDbLastEntity('payment');

        $this->assertEquals('upi', $existingPayment['method']);
        $this->assertEquals('captured', $existingPayment['status']);
        $this->assertEquals(300, $existingPayment['amount']);
        $this->assertEquals('upi_airtel', $existingPayment['gateway']);
        $this->assertEquals('qr_code', $existingPayment['receiver_type']);
    }

    public function testCreateAPBStaticQr(): void
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
        $this->runQrCodeEntityAssertions();
    }

    public function testStaticQRPaymentsForOfflineWithoutQrCodeConfig(): void
    {

        $this->fixtures->create(
            'terminal:dedicated_upi_airtel_offline_terminal',
            [
                'merchant_id' => '10000000000000'
            ]
        );

        $this->createPricingForOfflineUnexpected();
        $this->unexpectedPaymentSetUp();

        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type' => 'upi_qr',
                'vpa'   => 'testvpaOffline@mairtel',
            ],
            headers:[
                'X-Razorpay-Request-Source' => 'ezetap'
            ]
        );

        $qrCodeConfig = $this->getLastEntity('qr_code_config', true);

        $this->fixtures->on('test')->edit('qr_code_config', $qrCodeConfig['id'], [
            'merchant_id'  => '10000000000000',
            'config_key'   => 'static_qr',
            'config_value' => '{" "}',
            'deleted_at'   => 1709193726
        ]);

        $this->runQrCodeEntityAssertions('test');

        $qrCodeEntity = $this->getLastEntity('qr_code', true);

        $this->makeUpiAirtelPayment($qrCodeEntity, ['payeeVPA' => 'testvpaOffline@mairtel', 'hdnOrderID' => 'Random90']);

        $paymentEntity = $this->getLastEntity('payment', true);

        $authorizeUpiEntity = $this->getLastEntity('upi', true);

        $this->assertNotNull($authorizeUpiEntity['merchant_reference']);

        $paymentTransactionEntity = $this->getLastEntity('transaction', true);

        $this->assertNotEquals($authorizeUpiEntity['merchant_reference'], $paymentEntity['id']);

        $assertEqualsMap = [
            'authorized'                              => $paymentEntity['status'],
            'authorize'                               => $authorizeUpiEntity['action'],
            'pay'                                     => $authorizeUpiEntity['type'],
            $paymentEntity['id']                      => 'pay_' .$authorizeUpiEntity['payment_id'],
            $paymentTransactionEntity['id']           => 'txn_' . $paymentEntity['transaction_id'],
            $paymentTransactionEntity['entity_id']    => $paymentEntity['id'],
            $paymentTransactionEntity['type']         => 'payment',
            $paymentTransactionEntity['amount']       => $paymentEntity['amount'],
            Account::DEMO_ACCOUNT                     => $paymentEntity['merchant_id'],
            $authorizeUpiEntity['gateway']            => $paymentEntity['gateway'],
            $authorizeUpiEntity['amount']             => $paymentEntity['amount'],
            $paymentEntity['amount']                  => 300,
            $authorizeUpiEntity['merchant_reference'] => 'Random90'
        ];

        foreach ($assertEqualsMap as $matchLeft => $matchRight)
        {
            $this->assertEquals($matchLeft, $matchRight);
        }

    }

    protected function unexpectedPaymentSetUp()
    {
        $this->fixtures->merchant->createAccount(Account::DEMO_ACCOUNT);

        $this->fixtures->merchant->enableMethod(Account::DEMO_ACCOUNT, Method::UPI);

        $this->fixtures->merchant->activate();
    }

    public function testCreateAPBStaticOfflineQr(): void
    {
        $this->fixtures->merchant->addFeatures(['omni_enabled'], 'LiveAccountMer');

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

    public function testStaticQRPaymentsForOfflineWithQRIdAsMerchantReference(): void
    {

        $this->fixtures->merchant->addFeatures(['omni_enabled']);

        $this->fixtures->create(
            'terminal:dedicated_upi_airtel_offline_terminal',
            [
                'merchant_id' => '10000000000000'
            ]
        );

        $this->createPricingForOffline();

        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type' => 'upi_qr',
            ],
            headers: [
                'X-Razorpay-Request-Source' => 'ezetap'
            ]
        );

        $this->runQrCodeEntityAssertions('test');

        $qrCodeEntity = $this->getLastEntity('qr_code', true);

        $this->makeUpiAirtelPayment($qrCodeEntity, ['payeeVPA' => 'testvpaOffline@mairtel']);

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(300, $payment['amount']);
        $this->assertEquals('upi_airtel', $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);
    }

    public function testOfflineStaticQRPaymentsWithUnrecognisedPaymentProcessExperimentsDisabled(): void
    {
        $this->fixtures->merchant->addFeatures(['omni_enabled']);

        $terminal =  $this->fixtures->create(
            'terminal:dedicated_upi_airtel_offline_terminal',
            [
                'merchant_id' => '10000000000000'
            ]
        );

        $this->createPricingForOffline();

        $this->setMockRazorxTreatment(
            [
                'api_upi_airtel_pre_process_v1' => 'upi_airtel',
                RazorxTreatment::DISABLE_QR_CODE_ON_DEMAND_CLOSE => RazorxTreatment::RAZORX_VARIANT_ON,
            ]
        );

        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type' => 'upi_qr',
            ],
            headers: [
                'X-Razorpay-Request-Source' => 'ezetap'
            ]
        );

        $this->runQrCodeEntityAssertions('test');

        $qrCodeEntity = $this->getLastEntity('qr_code', true);

        $this->makeUpiAirtelPayment($qrCodeEntity, ['payeeVPA' => 'testvpaOffline@mairtel', 'hdnOrderID' => 'Random90']);

        $payment = $this->getDbLastEntity('payment');

        $this->assertNull($payment);
    }

    public function testCreateStaticAirtelQrWithVpaInput(): void
    {
        // don't ignore vpa even if request source is not ezetap
        $this->fixtures->create('terminal:dedicated_upi_airtel_terminal');
        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
                'vpa'   => 'testvpa@mairtel',
            ],
            'live',
            'LiveAccountMer');

        $this->runQrCodeEntityAssertions();

    }

    public function testCreateOfflineStaticAirtelQrWithValidVpaInput(): void
    {

        $this->fixtures->create('terminal:dedicated_upi_airtel_offline_terminal');

        $this->fixtures->create('terminal:dedicated_upi_airtel_terminal',
                                ['gateway_merchant_id2' => 'testvpaOfflineVpa@mairtel',
                                 'type'                 => [
                                     Type::PAY           => '1',
                                     Type::NON_RECURRING => '1',
                                     Type::OFFLINE       => '1',
                                     Type::COLLECT       => '1',
                                 ],
                                ]);

        $this->createQrCode(
                     [
                         'usage' => 'multiple_use',
                         'type'  => 'upi_qr',
                         'vpa'   => 'testvpaOfflineVpa@mairtel',
                     ],
                     'live',
                     'LiveAccountMer',
            headers: [
                         'X-Razorpay-Request-Source' => 'ezetap'
                     ]
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true, 'live');

        $intentParam = $this->getIntentParamsFromQRString($qrCodeEntity['qr_string']);

        $pa = $intentParam['pa'];

        $this->assertEquals('testvpaOfflineVpa@mairtel', $pa);
    }

    public function testCreateOfflineStaticAirtelQrWithWrongGatewayVpaInput(): void
    {
        $this->expectException(BadRequestException::class);

        $this->fixtures->create('terminal:dedicated_upi_airtel_offline_terminal');

        $this->fixtures->create('terminal:dedicated_upi_airtel_terminal',
                                ['gateway_merchant_id2' => 'testvpaOfflineVpa@mairtel',
                                 'type'                 => [
                                     Type::PAY           => '1',
                                     Type::NON_RECURRING => '1',
                                     Type::OFFLINE       => '1',
                                     Type::COLLECT       => '1',
                                 ],
                                ]);

        $this->createQrCode(
                     [
                         'usage' => 'multiple_use',
                         'type'  => 'upi_qr',
                         'vpa'   => 'testvpaOffline@hdfc',
                     ],
                     'live',
                     'LiveAccountMer',
            headers: [
                         'X-Razorpay-Request-Source' => 'ezetap'
                     ]
        );

    }

    public function testCreateOfflineStaticAirtelQrWithInvalidTerminalVpaInput(): void
    {
        $this->expectException(BadRequestException::class);

        $this->createQrCode(
                     [
                         'usage' => 'multiple_use',
                         'type'  => 'upi_qr',
                         'vpa'   => 'testvpaOfflineVpa@mairtel',
                     ],
                     'live',
                     'LiveAccountMer',
            headers: [
                         'X-Razorpay-Request-Source' => 'ezetap'
                     ]
        );

    }

    public function testCreateStaticAirtelQrWithOnlineTerminalVpaInput(): void
    {


        $this->fixtures->create('terminal:dedicated_upi_airtel_terminal');

        $this->createQrCode(
                     [
                         'usage' => 'multiple_use',
                         'type'  => 'upi_qr',
                         'vpa'   => 'testvpa@mairtel',
                     ],
                     'live',
                     'LiveAccountMer',
            headers: [
                         'X-Razorpay-Request-Source' => 'ezetap'
                     ]
        );
        $qrCodeEntity = $this->getLastEntity('qr_code', true, 'live');
        $intentParam = $this->getIntentParamsFromQRString($qrCodeEntity['qr_string']);
        $this->assertEquals('testvpa@mairtel', $intentParam['pa']);
    }

    public function testProcessAirtelQrReconInternalWithoutPayment(): void
    {
        $this->terminal = $this->fixtures->create('terminal:dedicated_upi_airtel_terminal');

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

        $request = $this->testData['testProcessAirtelQrPaymentInternal'];
        $request['content']['data']['upi']['merchant_reference'] = $qrCodeEntity['reference'] . 'qrv2';
        $request['content']['data']['upi']['npci_reference_id'] = (string) random_int(100000000000, 999999999999);
        $request['content']['data']['upi']['gateway_payment_id'] = (string) random_int(100000000000, 999999999999);
        $request['content']['data']['terminal']['gateway_merchant_id2'] = $this->terminal['gateway_merchant_id2'];
        $request['content']['data']['terminal']['gateway_merchant_id'] = $this->terminal['gateway_merchant_id'];

        $response = $this->makeUpiPaymentInternal($request);

        $payment = $this->getDbLastEntity('payment', 'live');
        $rrn = $request['content']['data']['upi']['npci_reference_id'];

        $this->runQrPaymentEntityAssertions(true, [], [], 'live', 300, false, $rrn);

    }

    public function testProcessAirtelQrReconInternalWithExistingPayment(): void
    {
        $this->terminal = $this->fixtures->create('terminal:dedicated_upi_airtel_terminal');

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
        $this->makeUpiAirtelPayment($qrCodeEntity);
        $existingPayment = $this->getDbLastEntity('payment', 'live');

        $request = $this->testData['testProcessAirtelQrPaymentInternal'];
        $request['content']['data']['upi']['merchant_reference'] = $qrCodeEntity['reference'] . 'qrv2';
        $request['content']['data']['upi']['npci_reference_id'] = $existingPayment['reference16'];
        $request['content']['data']['terminal']['gateway_merchant_id2'] = $this->terminal['gateway_merchant_id2'];

        $response = $this->makeUpiPaymentInternal($request);

        $payment = $this->getDbLastEntity('payment', 'live');

        $rrn = $request['content']['data']['upi']['npci_reference_id'];

        $this->runQrPaymentEntityAssertions(true, [], [], 'live', 300, false, $rrn);

        $this->assertEquals($existingPayment['id'], $payment['id']);
    }

    public function testProcessAirtelQrReconInternalWithoutPaymentForStaticQR(): void
    {

        $this->fixtures->merchant->addFeatures(['omni_enabled']);

        $terminal = $this->fixtures->create(
            'terminal:dedicated_upi_airtel_offline_terminal',
            [
                'merchant_id' => '10000000000000'
            ]
        );
        $this->createPricingForOffline();
        $this->createQrCode(
                     [
                         'usage' => 'multiple_use',
                         'type'  => 'upi_qr',
                         'vpa'   => 'testvpaOffline@mairtel',
                     ],
            headers: [
                         'X-Razorpay-Request-Source' => 'ezetap'
                     ]
        );

        $this->runQrCodeEntityAssertions('test');

        $qrCodeEntity = $this->getLastEntity('qr_code', true);


        $request = $this->testData['testProcessAirtelQrPaymentInternalStaticQR'];

        $request['content']['data']['upi']['npci_reference_id'] = (string) random_int(100000000000, 999999999999);
        $request['content']['data']['upi']['gateway_payment_id'] = (string) random_int(100000000000, 999999999999);
        $request['content']['data']['terminal']['gateway_merchant_id2'] = $terminal['gateway_merchant_id2'];

        $response = $this->makeUpiPaymentInternal($request);
        $rrn = $request['content']['data']['upi']['npci_reference_id'];

        $this->runQrPaymentEntityAssertions(true, [], [], 'test', 300, false, $rrn);

        $upi = $this->getDbLastEntity('upi');
        $this->assertEquals($upi['merchant_reference'],  $request['content']['data']['upi']['merchant_reference']);
        $qr_payment = $this->getDbLastEntity('qr_payment', 'test');
        $qrCodeId     = substr($qrCodeEntity['id'], 3, 14);
        $this->assertEquals($qr_payment['merchant_reference'],  $qrCodeId);
    }

    public function testProcessAirtelQrReconInternalSQRDuplicate(): void
    {

        $this->fixtures->merchant->addFeatures(['omni_enabled']);

        $terminal = $this->fixtures->create(
            'terminal:dedicated_upi_airtel_offline_terminal',
            [
                'merchant_id' => '10000000000000'
            ]
        );
        $this->createPricingForOffline();

        $this->createQrCode(
                     [
                         'usage' => 'multiple_use',
                         'type'  => 'upi_qr',
                         'vpa'   => 'testvpaOffline@mairtel',
                     ],
            headers: [
                         'X-Razorpay-Request-Source' => 'ezetap'
                     ]
        );

        $this->runQrCodeEntityAssertions('test');

        $qrCodeEntity = $this->getLastEntity('qr_code', true);


        $request = $this->testData['testProcessAirtelQrPaymentInternalStaticQR'];

        $request['content']['data']['upi']['npci_reference_id'] = (string) random_int(100000000000, 999999999999);
        $request['content']['data']['upi']['gateway_payment_id'] = (string) random_int(100000000000, 999999999999);
        $request['content']['data']['terminal']['gateway_merchant_id2'] = $terminal['gateway_merchant_id2'];

        $this->makeUpiPaymentInternal($request);
        //2nd call
        $response = $this->makeUpiPaymentInternal($request);
        $rrn = $request['content']['data']['upi']['npci_reference_id'];

        $this->runQrPaymentEntityAssertions(true, [], [], 'test', 300, false, $rrn);

        $upi = $this->getDbLastEntity('upi');
        $this->assertEquals($upi['merchant_reference'],  $request['content']['data']['upi']['merchant_reference']);
        $qr_payment = $this->getDbLastEntity('qr_payment');
        $qrCodeId     = substr($qrCodeEntity['id'], 3, 14);
        $this->assertEquals($qr_payment['merchant_reference'],  $qrCodeId);
    }

    // invalid qr code id is passed in payload
    // so create a payment against dummy qr code and refund it
    public function testProcessAirtelQrReconInternalStaticQRWithUnrecognisedPaymentProcessExperimentsDisabled(): void
    {
        $this->fixtures->merchant->addFeatures(['omni_enabled'], 'LiveAccountMer');

        $terminal = $this->fixtures->create('terminal:dedicated_upi_airtel_offline_terminal');
        $this->setMockRazorxTreatment(
            [
                'api_upi_airtel_pre_process_v1' => 'upi_airtel',
                RazorxTreatment::DISABLE_QR_CODE_ON_DEMAND_CLOSE => RazorxTreatment::RAZORX_VARIANT_ON,
            ]
        );
        $this->createPricingForOffline();
        $this->createQrCode(
                     [
                         'usage' => 'multiple_use',
                         'type'  => 'upi_qr',
                     ],
                     'live',
                     'LiveAccountMer',
            headers: [
                         'X-Razorpay-Request-Source' => 'ezetap'
                     ]
        );

        $this->runQrCodeEntityAssertions();

        $qrCodeEntity = $this->getLastEntity('qr_code', true, 'live');


        $request = $this->testData['testProcessAirtelQrPaymentInternalStaticQR'];

        $request['content']['data']['upi']['npci_reference_id'] = (string) random_int(100000000000, 999999999999);
        $request['content']['data']['upi']['gateway_payment_id'] = (string) random_int(100000000000, 999999999999);
        $request['content']['data']['terminal']['gateway_merchant_id2'] = $terminal['gateway_merchant_id2'];

        $response = $this->makeUpiPaymentInternal($request);
        $rrn = $request['content']['data']['upi']['npci_reference_id'];

        $this->runQrPaymentEntityAssertions(false, [], [], 'live', 300, false, $rrn, true);

        $this->assertNotNull($response['payment']['id']);
        $this->assertNotNull($response['payment']['amount']);
    }

    // payment is not found against single use qr code data so create a new payment
    // but qr code amount is not matching so initiate refund during recon
    public function testProcessAirtelQrPaymentInternalWithRefund(): void
    {
        $this->terminal = $this->fixtures->create('terminal:dedicated_upi_airtel_terminal');

        $this->createQrCode(
            [
                'usage'          => 'single_use',
                'type'           => 'upi_qr',
                'fixed_amount'   => true,
                'payment_amount' => 3000,
            ],
            'live',
            'LiveAccountMer'
        );

        $qrCodeEntity = $this->getLastEntity('qr_code', true, 'live');

        $request = $this->testData['testProcessAirtelQrPaymentInternal'];
        $request['content']['data']['upi']['merchant_reference'] = $qrCodeEntity['reference'] . 'qrv2';
        $request['content']['data']['upi']['npci_reference_id'] = (string) random_int(100000000000, 999999999999);
        $request['content']['data']['upi']['gateway_payment_id'] = (string) random_int(100000000000, 999999999999);
        $request['content']['data']['terminal']['gateway_merchant_id2'] = $this->terminal['gateway_merchant_id2'];

        $response = $this->makeUpiPaymentInternal($request);
        $rrn = $request['content']['data']['upi']['npci_reference_id'];

        $this->runQrPaymentEntityAssertions(false, [], [], 'live', 300, true, $rrn);

        $this->assertNotNull($response['payment']['id']);
        $this->assertNotNull($response['payment']['amount']);
    }


    public function testPaymentCreationViaReconForAPBStaticQr()
    {
        $this->fixtures->merchant->addFeatures(['omni_enabled'], 'LiveAccountMer');

        $this->setMockRazorxTreatment(
            [
                RazorxTreatment::QRV2_STATIC_QR_UNRECOGNISED_PAYMENT_PROCESS => RazorxTreatment::RAZORX_VARIANT_ON,
                RazorxTreatment::QR_GATEWAY_UNRECOGNISED_PAYMENT_PROCESS     => RazorxTreatment::RAZORX_VARIANT_ON,
                RazorxTreatment::QRV2_STATIC_QR_UNRECOGNISED_PAYMENT_RAMP    => RazorxTreatment::RAZORX_VARIANT_ON,
                RazorxTreatment::RECON_UNEXPECTED_QR_PAYMENT_VIA_UPI_ROUTE   => RazorxTreatment::RAZORX_VARIANT_ON,
                'api_upi_airtel_pre_process_v1' => 'upi_airtel',
                RazorxTreatment::DISABLE_QR_CODE_ON_DEMAND_CLOSE => RazorxTreatment::RAZORX_VARIANT_ON,
            ]
        );

        $terminal = $this->fixtures->create('terminal:dedicated_upi_airtel_offline_terminal');
        $this->createPricingForOffline();
        $qrCode = $this->createQrCode(
                     [
                         'usage'        => 'multiple_use',
                         'type'         => 'upi_qr',
                         'vpa'   => 'testvpaOffline@mairtel',
                     ],
                     'live',
                     'LiveAccountMer',
            headers: [
                         'X-Razorpay-Request-Source' => 'ezetap'
                     ]);

        $qrCodeId = $qrCode['id'];
        $this->fixtures->stripSign($qrCodeId);


        $qcc   = $this->getDbLastEntity('qr_code_config','live');
        $this->assertNotNull($qcc);

        $content = $this->buildQRUnexpectedPaymentRequest($terminal);
        $content['upi']['gateway_merchant_id'] = null;
        unset($content['terminal']['gateway_merchant_id']);
        $content['terminal']['gateway_merchant_id2'] = 'testvpaOffline@mairtel';

        $response = $this->makeUnexpectedLivePaymentAndGetContent($content);
        $this->assertTrue($response['success']);

        $qrPayment   = $this->getDbLastEntity('qr_payment','live');
        $payment     = $this->getDbLastEntity('payment','live');
        $upi         = $this->getDbLastEntity('upi','live');

        $this->assertEquals($content['upi']['npci_reference_id'], $upi['npci_reference_id']);
        $this->assertEquals($content['upi']['merchant_reference'], $upi['merchant_reference']);
        $this->assertEquals($payment['reference16'], $upi['npci_reference_id']);
        $this->assertEquals($qrCodeId, $qrPayment['merchant_reference']);
        $this->assertEquals(1, $qrPayment['expected']);
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(50000, $payment['amount']);
        $this->assertEquals('upi_airtel', $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);
        $this->assertEquals($response['payment_id'], $payment['id']);
    }

    public function testPaymentCreationViaReconForAPBStaticQrDuplicateCall()
    {
        $this->fixtures->merchant->addFeatures(['omni_enabled'], 'LiveAccountMer');

        $this->expectException(BadRequestException::class,);

        $this->expectExceptionMessage('Duplicate Unexpected payment with same amount');

        $this->setMockRazorxTreatment(
            [
                RazorxTreatment::QRV2_STATIC_QR_UNRECOGNISED_PAYMENT_PROCESS => RazorxTreatment::RAZORX_VARIANT_ON,
                RazorxTreatment::QR_GATEWAY_UNRECOGNISED_PAYMENT_PROCESS     => RazorxTreatment::RAZORX_VARIANT_ON,
                RazorxTreatment::QRV2_STATIC_QR_UNRECOGNISED_PAYMENT_RAMP    => RazorxTreatment::RAZORX_VARIANT_ON,
                RazorxTreatment::RECON_UNEXPECTED_QR_PAYMENT_VIA_UPI_ROUTE   => RazorxTreatment::RAZORX_VARIANT_ON,
                'api_upi_airtel_pre_process_v1' => 'upi_airtel',
                RazorxTreatment::DISABLE_QR_CODE_ON_DEMAND_CLOSE => RazorxTreatment::RAZORX_VARIANT_ON,
            ]
        );

        $terminal = $this->fixtures->create('terminal:dedicated_upi_airtel_offline_terminal');
        $this->createPricingForOffline();
        $qrCode = $this->createQrCode(
                     [
                         'usage'        => 'multiple_use',
                         'type'         => 'upi_qr',
                         'vpa'   => 'testvpaOffline@mairtel',
                     ],
                     'live',
                     'LiveAccountMer',
            headers: [
                         'X-Razorpay-Request-Source' => 'ezetap'
                     ]);

        $qrCodeId = $qrCode['id'];
        $this->fixtures->stripSign($qrCodeId);


        $qcc   = $this->getDbLastEntity('qr_code_config','live');
        $this->assertNotNull($qcc);

        $content = $this->buildQRUnexpectedPaymentRequest($terminal);
        $content['upi']['gateway_merchant_id'] = null;
        unset($content['terminal']['gateway_merchant_id']);
        $content['terminal']['gateway_merchant_id2'] = 'testvpaOffline@mairtel';

        $response = $this->makeUnexpectedLivePaymentAndGetContent($content);
        $this->assertTrue($response['success']);

        $qrPayment   = $this->getDbLastEntity('qr_payment','live');
        $payment     = $this->getDbLastEntity('payment','live');
        $upi         = $this->getDbLastEntity('upi','live');

        $this->assertEquals($content['upi']['npci_reference_id'], $upi['npci_reference_id']);
        $this->assertEquals($content['upi']['merchant_reference'], $upi['merchant_reference']);
        $this->assertEquals($payment['reference16'], $upi['npci_reference_id']);
        $this->assertEquals($qrCodeId, $qrPayment['merchant_reference']);
        $this->assertEquals(1, $qrPayment['expected']);
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(50000, $payment['amount']);
        $this->assertEquals('upi_airtel', $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);
        $this->assertEquals($response['payment_id'], $payment['id']);

        $this->makeUnexpectedLivePaymentAndGetContent($content);

    }

    public function testDQRUnexpectedPaymentsForOffline(): void
    {
        $this->fixtures->merchant->addFeatures(['omni_enabled']);

        $this->fixtures->create(
            'terminal:dedicated_upi_airtel_offline_terminal',
            [
                'merchant_id' => '10000000000000'
            ]
        );

        $this->createPricingForOfflineUnexpected();
        $this->unexpectedPaymentSetUp();

        $this->createQrCode(
                     [
                         'usage'          => 'single_use',
                         'type'           => 'upi_qr',
                         'fixed_amount'   => true,
                         'payment_amount' => 300,
                     ],
            headers: [
                         'X-Razorpay-Request-Source' => 'ezetap'
                     ]
        );

        $this->runQrCodeEntityAssertions('test');

        $qrCodeEntity = $this->getLastEntity('qr_code', true);

        $this->makeUpiAirtelPayment($qrCodeEntity, ['payeeVPA' => 'testvpaOffline@mairtel', 'hdnOrderID' => 'Random90']);

        $paymentEntity = $this->getLastEntity('payment', true);

        $authorizeUpiEntity = $this->getLastEntity('upi', true);

        $this->assertNotNull($authorizeUpiEntity['merchant_reference']);

        $paymentTransactionEntity = $this->getLastEntity('transaction', true);

        $this->assertNotEquals($authorizeUpiEntity['merchant_reference'], $paymentEntity['id']);

        $assertEqualsMap = [
            'authorized'                              => $paymentEntity['status'],
            'authorize'                               => $authorizeUpiEntity['action'],
            'pay'                                     => $authorizeUpiEntity['type'],
            $paymentEntity['id']                      => 'pay_' .$authorizeUpiEntity['payment_id'],
            $paymentTransactionEntity['id']           => 'txn_' . $paymentEntity['transaction_id'],
            $paymentTransactionEntity['entity_id']    => $paymentEntity['id'],
            $paymentTransactionEntity['type']         => 'payment',
            $paymentTransactionEntity['amount']       => $paymentEntity['amount'],
            Account::DEMO_ACCOUNT                     => $paymentEntity['merchant_id'],
            $authorizeUpiEntity['gateway']            => $paymentEntity['gateway'],
            $authorizeUpiEntity['amount']             => $paymentEntity['amount'],
            $paymentEntity['amount']                  => 300,
            $authorizeUpiEntity['merchant_reference'] => 'Random90'
        ];

        foreach ($assertEqualsMap as $matchLeft => $matchRight)
        {
            $this->assertEquals($matchLeft, $matchRight);
        }

        $this->assertEquals($paymentEntity['reference13'], 'in_person');
    }

    public function testDQRUnexpectedPaymentsForOnline(): void
    {

        $this->fixtures->create(
            'terminal:dedicated_upi_airtel_terminal',
            [
                'merchant_id' => '10000000000000'
            ]
        );

        $this->createPricingForOffline();
        $this->unexpectedPaymentSetUp();

        $this->createQrCode(
                    [
                        'usage'          => 'single_use',
                        'type'           => 'upi_qr',
                        'fixed_amount'   => true,
                        'payment_amount' => 300,
                    ]
        );


        $this->runQrCodeEntityAssertions('test');

        $qrCodeEntity = $this->getLastEntity('qr_code', true);

        $this->makeUpiAirtelPayment($qrCodeEntity, ['payeeVPA' => 'testvpa@mairtel', 'hdnOrderID' => 'Random90']);

        $paymentEntity = $this->getLastEntity('payment', true);

        $authorizeUpiEntity = $this->getLastEntity('upi', true);

        $this->assertNotNull($authorizeUpiEntity['merchant_reference']);

        $paymentTransactionEntity = $this->getLastEntity('transaction', true);

        $this->assertNotEquals($authorizeUpiEntity['merchant_reference'], $paymentEntity['id']);

        $assertEqualsMap = [
            'authorized'                              => $paymentEntity['status'],
            'authorize'                               => $authorizeUpiEntity['action'],
            'pay'                                     => $authorizeUpiEntity['type'],
            $paymentEntity['id']                      => 'pay_' .$authorizeUpiEntity['payment_id'],
            $paymentTransactionEntity['id']           => 'txn_' . $paymentEntity['transaction_id'],
            $paymentTransactionEntity['entity_id']    => $paymentEntity['id'],
            $paymentTransactionEntity['type']         => 'payment',
            $paymentTransactionEntity['amount']       => $paymentEntity['amount'],
            Account::DEMO_ACCOUNT                     => $paymentEntity['merchant_id'],
            $authorizeUpiEntity['gateway']            => $paymentEntity['gateway'],
            $authorizeUpiEntity['amount']             => $paymentEntity['amount'],
            $paymentEntity['amount']                  => 300,
            $authorizeUpiEntity['merchant_reference'] => 'Random90'
        ];

        foreach ($assertEqualsMap as $matchLeft => $matchRight)
        {
            $this->assertEquals($matchLeft, $matchRight);
        }

        $this->assertEquals($paymentEntity['reference13'], 'online');
    }

    public function testOfflineUPIUnexpectedPaymentCreationViaReconForAPB()
    {
        $this->setMockRazorxTreatment(
            [
                RazorxTreatment::QRV2_STATIC_QR_UNRECOGNISED_PAYMENT_PROCESS => RazorxTreatment::RAZORX_VARIANT_ON,
                RazorxTreatment::QR_GATEWAY_UNRECOGNISED_PAYMENT_PROCESS     => RazorxTreatment::RAZORX_VARIANT_ON,
                RazorxTreatment::QRV2_STATIC_QR_UNRECOGNISED_PAYMENT_RAMP    => RazorxTreatment::RAZORX_VARIANT_ON,
                RazorxTreatment::RECON_UNEXPECTED_QR_PAYMENT_VIA_UPI_ROUTE   => RazorxTreatment::RAZORX_VARIANT_ON,
                'api_upi_airtel_pre_process_v1' => 'upi_airtel',
                RazorxTreatment::DISABLE_QR_CODE_ON_DEMAND_CLOSE => RazorxTreatment::RAZORX_VARIANT_ON,
            ]
        );

        Reconciliate::$isReconRunning = true;

        $terminal = $this->fixtures->create('terminal:dedicated_upi_airtel_offline_terminal');

        $this->createPricingForOfflineUnexpected();
        $content = $this->buildQRUnexpectedPaymentRequest($terminal);
        $this->fixtures->merchant->activate();

        $content['upi']['gateway_merchant_id'] = null;
        unset($content['terminal']['gateway_merchant_id']);
        $content['terminal']['gateway_merchant_id2'] = 'testvpaOffline@mairtel';

        $response = $this->makeUnexpectedLivePaymentAndGetContent($content);
        $this->assertTrue($response['success']);


        $payment     = $this->getDbLastEntity('payment','test');
        $upi         = $this->getDbLastEntity('upi','test');

        $this->assertEquals($content['upi']['npci_reference_id'], $upi['npci_reference_id']);
        $this->assertEquals($content['upi']['merchant_reference'], $upi['merchant_reference']);
        $this->assertEquals($payment['reference16'], $upi['npci_reference_id']);


        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals(50000, $payment['amount']);
        $this->assertEquals('upi_airtel', $payment['gateway']);
        $this->assertEquals('in_person', $payment['reference13']);
        $this->assertEquals($response['payment_id'], $payment['id']);
    }

    public function testOnlineUPIUnexpectedPaymentCreationViaReconForAPB()
    {
        $this->setMockRazorxTreatment(
            [
                RazorxTreatment::QRV2_STATIC_QR_UNRECOGNISED_PAYMENT_PROCESS => RazorxTreatment::RAZORX_VARIANT_ON,
                RazorxTreatment::QR_GATEWAY_UNRECOGNISED_PAYMENT_PROCESS     => RazorxTreatment::RAZORX_VARIANT_ON,
                RazorxTreatment::QRV2_STATIC_QR_UNRECOGNISED_PAYMENT_RAMP    => RazorxTreatment::RAZORX_VARIANT_ON,
                RazorxTreatment::RECON_UNEXPECTED_QR_PAYMENT_VIA_UPI_ROUTE   => RazorxTreatment::RAZORX_VARIANT_ON,
                'api_upi_airtel_pre_process_v1' => 'upi_airtel',
                RazorxTreatment::DISABLE_QR_CODE_ON_DEMAND_CLOSE => RazorxTreatment::RAZORX_VARIANT_ON,
            ]
        );

        Reconciliate::$isReconRunning = true;

        $terminal = $this->fixtures->create('terminal:dedicated_upi_airtel_terminal');

        $this->createPricingForOffline();
        $content = $this->buildQRUnexpectedPaymentRequest($terminal);
        $this->fixtures->merchant->activate();

        $content['upi']['gateway_merchant_id'] = null;
        unset($content['terminal']['gateway_merchant_id']);
        $content['terminal']['gateway_merchant_id2'] = 'testvpa@mairtel';

        $response = $this->makeUnexpectedLivePaymentAndGetContent($content);
        $this->assertTrue($response['success']);


        $payment     = $this->getDbLastEntity('payment','test');
        $upi         = $this->getDbLastEntity('upi','test');

        $this->assertEquals($content['upi']['npci_reference_id'], $upi['npci_reference_id']);
        $this->assertEquals($content['upi']['merchant_reference'], $upi['merchant_reference']);
        $this->assertEquals($payment['reference16'], $upi['npci_reference_id']);


        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals(50000, $payment['amount']);
        $this->assertEquals('upi_airtel', $payment['gateway']);
        $this->assertEquals('online', $payment['reference13']);
        $this->assertEquals($response['payment_id'], $payment['id']);
    }

    public function testPOSDeviceDetailOnDashboardForOfflineQRCode()
    {
        $this->fixtures->merchant->addFeatures(['omni_enabled']);

        $this->fixtures->create(
            'terminal:dedicated_upi_airtel_offline_terminal',
            [
                'merchant_id' => '10000000000000'
            ]
        );

        $this->createPricingForOffline();

        $this->createQrCode(
                     [
                         'usage' => 'multiple_use',
                         'type' => 'upi_qr',
                         'vpa'   => 'testvpaOffline@mairtel',
                         'device_id' => '873648ABCD6',
                     ],
            headers: [
                         'X-Razorpay-Request-Source' => 'ezetap'
                     ]
        );

        $this->runQrCodeEntityAssertions('test');
        $qrCodeEntity = $this->getLastEntity('qr_code', true);

        $this->makeUpiAirtelPayment($qrCodeEntity, ['payeeVPA' => 'testvpaOffline@mairtel', 'hdnOrderID' => 'Random78']);
        $this->runQrPaymentEntityAssertions();
        $payment = $this->getDbLastEntity('payment');

        $user = $this->fixtures->user->createUserForMerchant();
        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);
        $this->testData[__FUNCTION__]['request']['url'] = '/payments/pay_' . $payment['id'];

        $response = $this->makeRequestAndGetContent($this->testData[__FUNCTION__]['request']);

        $this->assertEquals('873648ABCD6', $response['device_detail']);
        $this->assertEquals('testvpaOffline@mairtel', $response['payee_vpa']);
    }

    public function testOnDashboardForOfflineQRCodeIfPOSDeviceDetailIsNull(): void
    {
        $this->fixtures->merchant->addFeatures(['omni_enabled']);

        $this->fixtures->create(
            'terminal:dedicated_upi_airtel_offline_terminal',
            [
                'merchant_id' => '10000000000000'
            ]
        );

        $this->createPricingForOffline();

        $this->createQrCode(
                     [
                         'usage' => 'multiple_use',
                         'type' => 'upi_qr',
                     ],
            headers: [
                         'X-Razorpay-Request-Source' => 'ezetap'
                     ]
        );

        $this->runQrCodeEntityAssertions('test');

        $qrCodeEntity = $this->getLastEntity('qr_code', true);

        $this->makeUpiAirtelPayment($qrCodeEntity, ['payeeVPA' => 'testvpaOffline@mairtel']);

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(300, $payment['amount']);
        $this->assertEquals('upi_airtel', $payment['gateway']);
        $this->assertEquals('qr_code', $payment['receiver_type']);

        $user = $this->fixtures->user->createUserForMerchant();

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $this->testData['testPOSDeviceDetailOnDashboardForOfflineQRCode']['request']['url'] = '/payments/pay_' . $payment['id'];

        $response = $this->makeRequestAndGetContent($this->testData['testPOSDeviceDetailOnDashboardForOfflineQRCode']['request']);

        $this->assertEquals(null, $response['device_detail']);
        $this->assertEquals('testvpaOffline@mairtel', $response['payee_vpa']);
    }

    public function testEzetapNotificationOfflineQRCode()
    {
        $this->mockSplitzTreatmentForEzetapNotification();
        $this->fixtures->merchant->addFeatures(['omni_enabled']);

        $this->fixtures->create(
            'terminal:dedicated_upi_airtel_offline_terminal',
            [
                'merchant_id' => '10000000000000'
            ]
        );
        $actualEzetapNotificationCallCount =0 ;
        $eventList =[] ;
        $expectedEventList = ['qr_code.created',
                              'qr_code.credited',
                              'payment.captured',];
        $this->mockEzetapNotification($actualEzetapNotificationCallCount,$eventList);
        $this->createPricingForOffline();

        $this->createQrCode(
                     [
                         'usage' => 'multiple_use',
                         'type' => 'upi_qr',
                         'vpa'   => 'testvpaOffline@mairtel',
                     ],
            headers: [
                         'X-Razorpay-Request-Source' => 'ezetap'
                     ]
        );
        $this->assertEquals(1, $actualEzetapNotificationCallCount);
        $this->runQrCodeEntityAssertions('test');
        $qrCodeEntity = $this->getLastEntity('qr_code', true);
        $this->makeUpiAirtelPayment($qrCodeEntity, ['payeeVPA' => 'testvpaOffline@mairtel', 'hdnOrderID' => 'Random78']);

        $this->runQrPaymentEntityAssertions();
        $this->assertEqualsCanonicalizing($eventList,$expectedEventList);
        $this->assertEquals(3, $actualEzetapNotificationCallCount);
    }

    public function testEzetapNotificationOfflineQRCodeWithoutExperiment()
    {

        $this->fixtures->merchant->addFeatures(['omni_enabled']);
        $this->mockSplitzTreatmentForEzetapNotification('off');
        $this->fixtures->create(
            'terminal:dedicated_upi_airtel_offline_terminal',
            [
                'merchant_id' => '10000000000000'
            ]
        );
        $actualEzetapNotificationCallCount =0 ;
        $eventList =[] ;
        $expectedEventList = [];
        $this->mockEzetapNotification($actualEzetapNotificationCallCount,$eventList);
        $this->createPricingForOffline();

        $this->createQrCode(
                     [
                         'usage' => 'multiple_use',
                         'type' => 'upi_qr',
                         'vpa'   => 'testvpaOffline@mairtel',
                     ],
            headers: [
                         'X-Razorpay-Request-Source' => 'ezetap'
                     ]
        );
        $this->assertEquals(0, $actualEzetapNotificationCallCount);
        $this->runQrCodeEntityAssertions('test');
        $qrCodeEntity = $this->getLastEntity('qr_code', true);

        $this->makeUpiAirtelPayment($qrCodeEntity, ['payeeVPA' => 'testvpaOffline@mairtel', 'hdnOrderID' => 'Random78']);

        $this->runQrPaymentEntityAssertions();
        $this->assertEqualsCanonicalizing($eventList,$expectedEventList);
        $this->assertEquals(0, $actualEzetapNotificationCallCount);
    }
}
