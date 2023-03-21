<?php

namespace Functional\QrCode;

use RZP\Models\Pricing\Fee;
use RZP\Models\QrCode\Type;
use RZP\Services\RazorXClient;
use RZP\Exception\LogicException;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\RuntimeException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Exception\GatewayErrorException;
use RZP\Models\QrPayment\UnexpectedPaymentReason;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\QrCode\NonVirtualAccountQrCodeTrait;

class UpiYesBankQRCodeTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;
    use NonVirtualAccountQrCodeTrait;


    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NonVirtualAccountQrCodeTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['qr_codes']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->edit('10000000000000', ['billing_label' => 'more-megastore-account']);

        $this->fixtures->merchant->activate();

        $this->fixtures->on('test')->merchant->edit('10000000000000', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        $this->fixtures->merchant->createAccount('LiveAccountMer');

        $this->fixtures->on('live')->merchant->edit('LiveAccountMer', ['activated' => true, 'live' => true]);

        $this->fixtures->on('live')->merchant->addFeatures(['qr_codes'], 'LiveAccountMer');

        $this->fixtures->on('live')->merchant->enableMethod('LiveAccountMer', 'upi');

        $this->fixtures->on('live')->merchant->edit('LiveAccountMer', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        $this->fixtures->create('terminal:dedicated_upi_yesbank_terminal');

    }

    private function runEntityAssertions($response) :void
    {
        $qrCodeEntity = $this->getLastEntity('qr_code', true);
        $tr = 'RZP' . substr($response['id'], 3, 14) . 'qrv2';
        $this->assertStringContainsString($tr, $qrCodeEntity['qr_string']);
        $this->assertStringContainsString('@yesbank', $qrCodeEntity['qr_string']);

        if ($qrCodeEntity['fixed_amount'] === true)
        {
            $amount = $qrCodeEntity['amount'] / 100;

            $this->assertStringContainsString('am=' . $amount, $qrCodeEntity['qr_string']);
        }

        if ($response['type'] === Type::BHARAT_QR)
        {
            $this->assertStringContainsString('0518' . substr($response['id'], 3) . 'qrv2', $qrCodeEntity['qr_string']);
        }
    }

    protected function enableRazorXTreatmentForQrDedicatedTerminal() :void
    {
        $razorx = \Mockery::mock(RazorXClient::class)->makePartial();

        $this->app->instance('razorx', $razorx);

        $razorx->allows('getTreatment')
            ->andReturnUsing(function (string $id, string $featureFlag, string $mode)
            {
                if ($featureFlag === (RazorxTreatment::DEDICATED_TERMINAL_QR_CODE))
                {
                    return 'on';
                }
                return 'control';
            });
    }

    public function testCreateStaticQrWithoutTerminal() :void
    {
        $this->expectException(LogicException::class);

        $this->expectExceptionMessage('No Terminal applicable.');

        $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ],
            'live',
            'LiveAccountMer');
    }

    public function testCreateStaticQrWithTerminal() :void
    {
        $this->enableRazorXTreatmentForQrDedicatedTerminal();

        $response = $this->createQrCode(
            [
                'usage' => 'multiple_use',
                'type'  => 'upi_qr',
            ],
        );

        $this->runEntityAssertions($response);
    }

}
