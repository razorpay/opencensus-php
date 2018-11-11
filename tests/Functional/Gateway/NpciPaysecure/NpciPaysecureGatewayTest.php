<?php

namespace RZP\Tests\Functional\Gateway\NpciPaysecure;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NpciPaysecureGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    public function setUp()
    {
        parent::setUp();

        $this->fixtures->terminal->disableTerminal('1n25f6uN5S1Z5a');

        $this->fixtures->create('terminal:shared_npci_paysecure_terminal');

        $this->gateway = 'npci_paysecure';

        $this->setMockGatewayTrue();

        $this->payment = $this->getDefaultPaymentArray();
    }

    public function testPaymentAuthViaRedirect()
    {
//        sd($this->payment);
        $authResponse = $this->doAuthPayment($this->payment);
        sd($this->getDbLastEntityToArray('payment'));
        sd($authResponse);
    }

    /**
     * Error response from CheckBin request.
     * Verify the same and make sure verify responds with action finish
     */
    public function testUnqualifiedPin()
    {
        $authResponse = $this->doAuthPayment($this->payment);
    }

    public function testPaymentAuthViaPinPad()
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
