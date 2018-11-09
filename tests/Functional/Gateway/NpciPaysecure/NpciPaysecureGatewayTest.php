<?php

namespace RZP\Tests\Functional\Gateway\NpciPaysecure;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NpciPaysecureGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        parent::setUp();

        $this->fixtures->create('terminal:shared_npci_paysecure_terminal');

        $this->gateway = 'npci_paysecure';

        $this->setMockGatewayTrue();

        $this->payment = $this->getDefaultPaymentArray();
    }

    public function testPaymentAuthAndCapture()
    {
        $authResponse = $this->doAuthPayment($this->payment);
        sd($authResponse);
    }

    protected function getDefaultPaymentArray()
    {
        $payment = $this->getDefaultPaymentArrayNeutral();

        $payment['card'] = array(
            'number'            => '6073849700004947',
            'name'              => 'Praveen',
            'expiry_month'      => '12',
            'expiry_year'       => '2024',
            'cvv'               => '566',
        );

        return $payment;
    }
}
