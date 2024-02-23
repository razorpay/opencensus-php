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
        $this->assertEquals($qrCodeEntity['reference'] . 'qrv2', $tr);

        if ($qrCodeEntity['usage'] === 'single_use')
        {
            $amount = $qrCodeEntity['amount'] / 100.00;
            $this->assertStringContainsString('am=' . $amount, $qrCodeEntity['qr_string']);
        }
        else
        {

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
}
