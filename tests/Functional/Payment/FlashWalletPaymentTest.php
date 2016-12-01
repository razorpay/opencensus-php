<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class FlashWalletPaymentTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/FlashWalletPaymentTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_flashwallet_terminal');
    }

    public function testCustomerIdNotSent()
    {
        $payment = $this->getDefaultFlashWalletPaymentArray(null);

        return $this->runTestForAuthPayment($payment);
    }

    public function testCustomerDoesNotExistForMerchant()
    {
        $payment = $this->getDefaultFlashWalletPaymentArray('cust_dummycustomer1');

        return $this->runTestForAuthPayment($payment);
    }

    public function testPaymentLoadB2BWalletExistingCustomer()
    {

    }

    public function testPayFromWalletNewCustomer()
    {

    }

}
