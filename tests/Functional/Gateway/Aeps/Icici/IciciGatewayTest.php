<?php

namespace RZP\Tests\Functional\Gateway\Aeps\Icici;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class IciciGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/IciciGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_aeps_icici_terminal');

        $this->gateway = 'aeps_icici';

        $this->fixtures->merchant->enableMethod('10000000000000', 'aeps');

        $this->payment = $this->getDefaultAepsPaymentArray();
    }

    public function testPayment()
    {
        unset($this->payment['description']);

        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        sd($payment);

        // $response = $this->doAuthPaymentViaAjaxRoute($this->payment);
        // $paymentId = $response['payment_id'];

        // // Co Proto must be working
        // $this->assertEquals('async', $response['type']);

        // $this->checkPaymentStatus($paymentId, $status);

        // return $paymentId;
    }
}
