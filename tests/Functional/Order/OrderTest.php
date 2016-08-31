<?php

namespace RZP\Tests\Functional\Order;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class OrderTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/OrderTestData.php';

        parent::setUp();

        $this->ba->privateAuth();
    }

    public function setUpBillDeskGateway()
    {
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_billdesk_tpv_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'billdesk';

        $this->setMockGatewayTrue();
    }

    public function testCreateOrder()
    {
        $order = $this->startTest();

        return $order;
    }

    public function testCreateAutoCaptureOrder()
    {
        $order = $this->startTest();

        return $order;
    }

    public function testCreateTPVOrder()
    {
        $order = $this->startTest();

        return $order;
    }

    public function testGetOrder()
    {
        $order = $this->testCreateOrder();

        $order = $this->getEntityById('order', $order['id']);

        $this->assertArraySelectiveEquals($this->testData[__FUNCTION__], $order);
    }

    public function testGetMultipleOrders()
    {
        $createdOrders = $this->fixtures->times(2)->create('order');
        $createdOrders = array_reverse($createdOrders);
        $collection = new \RZP\Models\Base\PublicCollection($createdOrders);
        $array = $collection->toArrayPublic();

        $this->testData[__FUNCTION__]['response']['content'] = $array;

        $this->startTest();
    }

    public function testStatusAfterPayment()
    {
        $order = $this->testCreateOrder();
        $order = $this->getLastEntity('order');
        $this->assertEquals($order['status'], 'created');

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];
        $rzpPayment = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment');
        $this->assertEquals($order['id'], $payment['order_id']);

        $order = $this->getLastEntity('order', true);
        $this->assertEquals($order['status'], 'attempted');
        $this->assertEquals($order['authorized'], true);

        // If a payment is requested for an already authorised order
        // That will fail with a BadRequestValidationFailureException
        $testData = $this->testData[__FUNCTION__];
        $payment1 = $this->getDefaultPaymentArray();
        $payment1['order_id'] = $order['id'];
        $this->runRequestResponseFlow($testData, function() use ($payment1)
        {
            $this->doAuthPayment($payment1);
        });

        $this->capturePayment($rzpPayment['razorpay_payment_id'], $payment['amount']);

        $order = $this->getLastEntity('order');
        $this->assertEquals($order['status'], 'paid');

        // If a payment is requested for an already paid order
        // That will fail with a BadRequestValidationFailureException
        $testData = $this->testData[__FUNCTION__];
        $this->runRequestResponseFlow($testData, function() use ($payment1)
        {
            $this->doAuthPayment($payment1);
        });
    }

    public function testStatusAfterAutoCapturePayment()
    {
        $order = $this->testCreateAutoCaptureOrder();
        $order = $this->getLastEntity('order');
        $this->assertEquals($order['status'], 'created');

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];
        $response = $this->doAuthPayment($payment);

        $actualSignature = $response['signature'];

        unset($response['signature']);

        ksort($response);
        $exceptedSignature = $this->getSignature($response, 'TheKeySecretForTests');

        $this->assertEquals($actualSignature, $exceptedSignature);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($order['id'], $payment['order_id']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(true, $payment['auto_captured']);

        $order = $this->getLastEntity('order', true);
        $this->assertEquals($order['status'], 'paid');
        $this->assertEquals($order['authorized'], true);
    }

    public function testOrderAndPaymentAmountMismatch()
    {
        $order = $this->testCreateOrder();

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];
        $payment['amount'] = '1000';

        $testData = $this->testData[__FUNCTION__];
        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testPaymentForTPVMerchantWithoutOrder()
    {
        $this->fixtures->merchant->enableTPV();

        $this->setUpBillDeskGateway();

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment['bank'] = 'ANDB';

        // Not adding order_id in payment

        $this->runRequestResponseFlow(
            $this->testData[__FUNCTION__],
            function () use ($payment)
            {
                $this->doAuthPayment($payment);
            });

        $this->fixtures->merchant->disableTPV();
    }

    public function testPaymentWithIncorrectBankForTPVMerchantWithOrder()
    {
        $this->fixtures->merchant->enableTPV();

        $this->setUpBillDeskGateway();

        $order = $this->testCreateTPVOrder();

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment['bank'] = 'CORP';

        $payment['order_id'] = $order['id'];

        $this->runRequestResponseFlow(
            $this->testData[__FUNCTION__],
            function () use ($payment)
            {
                $this->doAuthPayment($payment);
            });

        $this->fixtures->merchant->disableTPV();
    }

    public function testPaymentForTPVMerchantWithOrder()
    {
        $this->fixtures->merchant->enableTPV();

        $this->setUpBillDeskGateway();

        $order = $this->testCreateTPVOrder();

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment['bank'] = 'ANDB';

        $payment['order_id'] = $order['id'];

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment');

        $this->assertEquals($order['id'], $payment['order_id']);

        $this->fixtures->merchant->disableTPV();
    }

    public function testPreferencesForTPVMerchants()
    {
        $this->fixtures->merchant->enableTPV();

        $this->setUpBillDeskGateway();

        $this->testCreateTPVOrder();

        $order = $this->getLastEntity('order', true);

        $this->ba->publicAuth();

        $testData['request']['content'] = ['key_id' => $this->ba->getKey(), 'order_id' => $order['id']];

        $preferences = $this->startTest($testData);

        $this->fixtures->merchant->disableTPV();
    }
}
