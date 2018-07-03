<?php

namespace RZP\Tests\Functional\Gateway\Mpi\Enstage;

use RZP\Gateway\Mpi\Enstage\Field;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Mpi\Base\Enrolled;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class EnstageGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/EnstageGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_enstage_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'mpi_enstage';

        $this->fixtures->merchant->addFeatures(['otpelf']);

        $this->mockTokenex();
    }

    public function testSuccessful1yEnrolledCard()
    {
        $this->authorizePayment();

        $gatewayEntity = $this->getLastEntity('mpi_blade', true);

        $this->assertArraySelectiveEquals($this->testData[__FUNCTION__], $gatewayEntity);
    }

    public function testCardNotEnrolledfor3dSecure()
    {
        $this->mockServerContentFunction(
            function(& $content, $action)
            {
                if ($action === 'otp_generate')
                {
                    $content[Field::RESPONSE_CODE] = '016';
                    $content[Field::RES_DESC] = 'CARD NOT PARTICITIPATING IN 3ds';
                    unset($content[Field::MESSAGE_HASH]);
                }
            },
            $this->gateway
        );

        $this->authorizePayment();

        $gatewayEntity = $this->getLastEntity('mpi_blade', true);

        $this->assertNotNull(Enrolled::N, $gatewayEntity['enrolled']);
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
        $this->fixtures->edit('iin', '411146', ['flows' => ['otp' => '1']]);

        $payment = $this->defaultAuthPayment([
           'card' => [
               'number'       => '4111466126747568',
               'expiry_month' => '02',
               'expiry_year'  => '21',
               'cvv'          => 123,
               'name'         => 'Test Card',
           ],
            'auth_type' => 'otp',
        ]);
        
        return $payment;
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
