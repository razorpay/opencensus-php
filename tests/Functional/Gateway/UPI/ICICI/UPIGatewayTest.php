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

        $payment = $this->getEntityById('payment', $paymentId, true);

        $this->assertEquals('upi', $payment['method']);
        $this->assertEquals('created', $payment['status']);

        $upiEntity = $this->getLastEntity('upi_icici', true);

        $this->assertNotNull($upiEntity);

        return [$payment, $upiEntity];
    }

    public function testPaymentWithS2S()
    {
        list($payment, $upiEntity) = $this->testPayment();

        $mockServer = $this->mockServer();

        $content = $mockServer->makeS2SRequest($upiEntity, $payment);

        $request = [
            'body'      => $content,
            'url'       => '/callback/upi_icici',
            'method'    => 'post'
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(['success' => true], $response);
    }
}
