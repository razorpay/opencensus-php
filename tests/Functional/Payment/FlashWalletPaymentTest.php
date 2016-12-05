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

        $this->fixtures->merchant->enableWallet('10000000000000', 'flashwallet');

        $this->gateway = 'wallet_flashwallet';
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

    public function testPayFromWallet()
    {
        $this->doAuthPaymentFromWallet(3000, 2000);

        $payment = $this->getLastEntity('payment', true);

        $customerBalance = $this->getLastEntity('customer_balance', true);

        $customerTransaction = $this->getLastEntity('customer_transactions', true);

        $expected = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expected['payment'], $payment);

        $this->assertArraySelectiveEquals($expected['customerBalance'], $customerBalance);

        $this->assertArraySelectiveEquals($expected['customerTransaction'], $customerTransaction);
    }

    public function testCaptureWalletPayment()
    {
        $paymentId = $this->doAuthPaymentFromWallet(3000, 1000);

        $this->capturePayment($paymentId, 1000);

        $payment = $this->getEntityById('payment', $paymentId, true);

        $this->assertEquals($payment['status'], 'captured');

        // Assert - merchant balance/ nodal account ?
    }

    protected function doAuthPaymentFromWallet($customerBalance, $paymentAmount)
    {
        $customerBalance = $this->fixtures->create('customer:customer_balance', ['balance' => $customerBalance]);

        $payment = $this->getDefaultFlashWalletPaymentArray($customerBalance->customer->getPublicId(), $paymentAmount);

        return $this->doAuthPayment($payment)['razorpay_payment_id'];
    }


    public function testPayFromWalletNewCustomer()
    {

    }

}
