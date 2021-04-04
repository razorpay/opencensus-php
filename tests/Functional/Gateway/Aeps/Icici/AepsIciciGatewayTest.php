<?php

namespace RZP\Tests\Functional\Gateway\Aeps\Icici;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Gateway\Aeps\Icici;

class AepsIciciGatewayTest extends TestCase
{
    use PaymentTrait;

    protected $payment;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/AepsIciciGatewayTestData.php';

        parent::setUp();

        $this->fixtures->create('terminal:shared_aeps_icici_terminal');

        $this->gateway = 'aeps_icici';

        $this->fixtures->merchant->enableMethod('10000000000000', 'aeps');

        $this->payment = $this->getDefaultAepsPaymentArray();
    }

    public function testPayment()
    {
        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment');

        $this->assertTestResponse($payment);
    }

    public function testPaymentFailure()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'auth')
            {
                $content[Icici\ResponseConstants::AUTH_RESPONSE_CODE] = '01';
            }
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->doAuthPayment($this->payment);
            }
        );
    }

    public function testRefund()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $response = $this->refundPayment($payment['id']);

        $this->assertTestResponse($response);
    }

    public function testRefundFailure()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'refund')
            {
                $content[Icici\ResponseConstants::REFUND_RESPONSE] = '11';
            }
        });

        $data = $this->testData[__FUNCTION__];

        $this->refundPayment($payment['id']);
    }
}
