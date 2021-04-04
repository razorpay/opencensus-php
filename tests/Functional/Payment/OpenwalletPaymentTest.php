<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class OpenwalletPaymentTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/OpenwalletPaymentTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_openwallet_terminal');

        $this->fixtures->merchant->enableWallet('10000000000000', 'openwallet');

        $this->gateway = 'wallet_openwallet';
    }

    public function testCustomerIdNotSent()
    {
        $payment = $this->getDefaultOpenwalletPaymentArray(null);

        return $this->runTestForAuthPayment($payment);
    }

    public function testCustomerDoesNotExistForMerchant()
    {
        $payment = $this->getDefaultOpenwalletPaymentArray('cust_dummycustomer1');

        return $this->runTestForAuthPayment($payment);
    }

    public function testPayFromWallet()
    {
        $this->doAuthPaymentFromWallet(3000, 2000);

        $payment = $this->getLastEntity('payment', true);

        $customerBalance = $this->getLastEntity('customer_balance', true);

        $customerTransaction = $this->getLastEntity('customer_transaction', true);

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

        $txn =  $this->getLastEntity('transaction', true);

        $this->assertNotNull($txn['reconciled_at']);

        $this->assertNotNull($txn['reconciled_type']);

        // Assert - merchant balance/ nodal account ?
    }

    public function testRefundWalletPayment()
    {
        $paymentId = $this->doAuthPaymentFromWallet(3000, 1000);

        $this->capturePayment($paymentId, 1000);

        $this->refundPayment($paymentId, 1000);

        $customerBalance = $this->getLastEntity('customer_balance', true);

        $customerTransaction = $this->getLastEntity('customer_transaction', true);

        $expected = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expected['customerBalance'], $customerBalance);

        $this->assertArraySelectiveEquals($expected['customerTransaction'], $customerTransaction);
    }

    protected function doAuthPaymentFromWallet($customerBalance, $paymentAmount)
    {
        $customerBalance = $this->fixtures->create('customer:customer_balance', ['balance' => $customerBalance]);

        $payment = $this->getDefaultOpenwalletPaymentArray($customerBalance->customer->getPublicId(), $paymentAmount);

        return $this->doAuthPayment($payment)['razorpay_payment_id'];
    }


    public function testPayFromWalletNewCustomer()
    {

    }

}
