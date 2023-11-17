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

//        $this->fixtures->merchant->edit('10000000000000', ['billing_label' => 'more-megastore-account']);

//        $this->fixtures->merchant->activate();

//        $this->fixtures->on('test')->merchant->edit('10000000000000', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

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
}
