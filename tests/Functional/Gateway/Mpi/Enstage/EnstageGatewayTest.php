<?php

namespace RZP\Tests\Functional\Gateway\Mpi\Enstage;

use RZP\Gateway\Mpi\Enstage\Field;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Mpi\Base\Enrolled;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class EnstageGatewayTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/EnstageGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_enstage_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'mpi_enstage';

        $this->fixtures->merchant->addFeatures(['axis_express_pay']);

        $this->mockCardVault();
    }

    public function testSuccessfullyEnrolledCard()
    {
        $response = $this->authorizePayment();

        self::assertTrue($this->otpFlow);

        $gatewayEntity = $this->getLastEntity('mpi', true);

        self::assertArraySelectiveEquals($this->testData[__FUNCTION__], $gatewayEntity);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        self::assertEquals('otp', $payment['auth_type']);
        self::assertEquals('mpi_enstage', $payment['gateway']);
    }

    public function testCardNotEnrolledfor3dSecure()
    {
        $this->mockServerContentFunction(
            function(& $content, $action)
            {
                if ($action === 'otp_generate')
                {
                    $content = [];
                    $content[Field::RESPONSE_CODE] = '016';
                    $content[Field::RES_DESC] = 'CARD NOT PARTICITIPATING IN 3ds';
                }
            },
            $this->gateway
        );

        $this->authorizePayment();

        $gatewayEntity = $this->getLastEntity('mpi', true);

        $this->assertNotNull(Enrolled::N, $gatewayEntity['enrolled']);

        $this->assertEquals('mpi_enstage', $gatewayEntity['gateway']);
    }

    public function testAuthenticationError()
    {
        $this->mockServerContentFunction(
            function(& $content, $action)
            {
                if ($action === 'otp_submit')
                {
                    $content[Field::RESPONSE_CODE] = '008';
                    $content[Field::RES_DESC] = 'ISSUER found PAN to be invalid';
                }
            },
            $this->gateway
        );

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->authorizePayment();
            });
    }

    public function testInvalidCheckSum()
    {
        $this->mockServerContentFunction(
            function(& $content, $action)
            {
                if ($action === 'otp_submit')
                {
                    $content[Field::SECRET] = 'randomd';
                }
            },
            $this->gateway
        );

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->authorizePayment();
            });
    }

    protected function authorizePayment()
    {
        $this->fixtures->edit('iin', '411146', [
            'issuer' => 'UTIB',
            'flows' => [
                'otp' => '1'
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4111466126747568';
        $payment['auth_type'] = 'otp';

        $this->setOtp('123456');

        return $this->doAuthPayment($payment);
    }

    public function testAuthenticationFailed()
    {
        $this->mockServerContentFunction(
            function(& $content, $action)
            {
                if ($action === 'otp_submit')
                {
                    $content[Field::MESSAGE_HASH] = '232323232323';

                    $content[Field::RESPONSE_CODE] = '001';

                    $content[Field::RES_DESC] = 'WRONG OTP';

                    unset($content[Field::MESSAGE_HASH]);

                }
            },
            $this->gateway
        );

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->authorizePayment();
            });
    }
}
