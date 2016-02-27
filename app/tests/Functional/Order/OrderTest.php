<?php

namespace Tests\Functional\Order;

use Tests\Functional\TestCase;
use Tests\Functional\Helpers\Payment\PaymentTrait;

class OrderTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/OrderTestData.php';

        parent::setUp();

        $this->ba->privateAuth();
    }

    public function testCreateOrder()
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
        $collection = new \Models\Base\PublicCollection($createdOrders);
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
        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $this->capturePayment($rzpPayment['razorpay_payment_id'], $payment['amount']);

        $order = $this->getLastEntity('order');
        $this->assertEquals($order['status'], 'paid');

        // If a payment is requested for an already paid order
        // That will fail with a BadRequestValidationFailureException
        $testData = $this->testData[__FUNCTION__];
        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
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
}
