<?php

namespace Tests\Functional\Payment;

use Tests\Functional\TestCase;
use Tests\Functional\Helpers\Payment\PaymentTrait;

class PaymentMiscTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
//        $this->testDataFilePath = __DIR__.'/helpers/authorize.php';

        parent::setUp();

        $this->ba->publicAuth();

        $this->payment = $this->getDefaultPaymentArray();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
    }

    public function testPaymentMetadataRoute()
    {
        $payment = $this->getDefaultPaymentArray();
        $cardIin = substr($payment['card']['number'], 0, 6);

        $payment = $this->doAuthPayment();

        $content = ['otp_read' => '1'];

        $content = $this->addPaymentMetadata($payment['razorpay_payment_id'], $content);

        $iin = $this->getEntityById('iin', $cardIin, true);
        $this->assertEquals($iin['otp_read'], true);
    }
}