<?php

namespace RZP\Tests\Functional\Gateway\UPI\ICICI;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class UPIGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/UPIGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_upi_terminal');

        $this->gateway = 'upi_icici';

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->payment = $this->getDefaultPaymentArrayUpi();
    }

    public function testPayment()
    {
        $res = $this->doAuthPayment($this->payment);
        $paymentId = $res['payment_id'];

        // Co Proto must be working
        $this->assertEquals('async', $res['type']);

        $this->testPaymentStatus($paymentId, 'created');

        return $paymentId;
    }

    public function testPaymentWithS2S($assert = true)
    {
        $paymentId = $this->testPayment();

        $upiEntity = $this->getLastEntity('upi_icici', true);
        $payment = $this->getEntityById('payment', $paymentId, true);

        $mockServer = $this->mockServer();

        $content = $mockServer->makeS2SRequest($upiEntity, $payment);

        $request = [
            'raw'      => $content,
            'url'       => '/callback/upi_icici',
            'method'    => 'post'
        ];

        $response = $this->makeRequestAndGetContent($request);

        if ($assert)
        {
            $this->assertEquals($response, ['success' => true]);

            $this->testPaymentStatus($paymentId, 'authorized');
        }

        return $payment;
    }

    protected function testPaymentStatus($id, $expectedStatus)
    {
        $request = [
            'url'       => "/payments/$id/status",
            'method'    => 'get'
        ];

        $this->ba->publicAuth();

        $data = $this->makeRequestAndGetContent($request);

        $status = $data['status'];

        $this->assertEquals($expectedStatus, $status);
    }

    public function testPaymentRefund()
    {
        $payment = $this->testPaymentWithS2S();

        $this->capturePayment($payment['id'], 50000);

        $this->expectException('RZP\Exception\GatewayErrorException', 'Refund is currently not supported for this payment method');

        $this->refundPayment($payment['id']);
    }
}
