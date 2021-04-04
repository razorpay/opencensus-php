<?php

namespace RZP\Tests\Functional\Gateway\Wallet\Openwallet;

use RZP\Exception;
use RZP\Http\Route;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

class OpenwalletGatewayTest extends TestCase
{
    use PaymentTrait;

    const WALLET = 'openwallet';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/OpenwalletGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_openwallet_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'wallet_openwallet';

        $this->fixtures->merchant->enableWallet('10000000000000', 'openwallet');
    }

    public function testRefundPayment()
    {
        $customerBalance = $this->fixtures->create('customer_balance', ['customer_id' => '100000customer', 'balance' => 200]);

        $payment = $this->getDefaultOpenwalletPaymentArray('cust_100000customer', 200);

        $input = ['amount' => $payment['amount']];

        $authPayment = $this->doAuthPayment($payment);

        $this->refundAuthorizedPayment($authPayment['razorpay_payment_id'], $input);

        $refund = $this->getLastEntity('refund', true);

        $this->testData['testAuthPaymentRefund']['payment_id'] = $authPayment['razorpay_payment_id'];

        $this->assertTestResponse($refund, 'testAuthPaymentRefund');

        $balance = $this->getEntityById('customer_balance', '100000customer', true);

        $this->assertEquals(200, $balance['balance']);
    }

     public function testPartialRefundPayment()
    {
        $customerBalance = $this->fixtures->create('customer_balance', ['customer_id' => '100000customer', 'balance' => 3000]);

        $payment = $this->getDefaultOpenwalletPaymentArray('cust_100000customer', 1500);

        $authPayment = $this->doAuthPayment($payment);

        $input = ['amount' => 500];

        $this->refundAuthorizedPayment($authPayment['razorpay_payment_id'], $input);

        $refund = $this->getLastEntity('refund', true);

        $this->testData[__FUNCTION__]['payment_id'] = $authPayment['razorpay_payment_id'];

        $this->assertTestResponse($refund, __FUNCTION__);

        $balance = $this->getEntityById('customer_balance', '100000customer', true);

        $this->assertEquals(2000, $balance['balance']);
    }

    public function testPaymentInsufficientBalance()
    {
        $customerBalance = $this->fixtures->create('customer_balance', ['customer_id' => '100000customer', 'balance' => 100]);

        $payment = $this->getDefaultOpenwalletPaymentArray('cust_100000customer', 200);

        $response = $this->runRequestResponseFlow($this->testData[__FUNCTION__], function() use ($payment)
        {
            return $this->doAuthPayment($payment);
        });
    }
}
