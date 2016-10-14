<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class PaymentCreateConvenienceFeeTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/PaymentCreateConvenienceFeeTestData.php';

        parent::setUp();

        $this->ba->publicAuth();

        $this->fixtures->merchant->enableConvenienceFeeModel();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
    }

    public function testFees($payment = null)
    {
        if ($payment === null)
        {
            $payment = $this->getDefaultPaymentArray();
        }

        $feesArray = $this->createAndGetFeesForPayment($payment);

        if ($payment['amount'] === 50000)
        {
            $this->assertEquals($feesArray['input']['fee'], 1173);

            $this->assertEquals($feesArray['display']['service_tax'], 1.49);
        }

        return $feesArray;
    }

    public function testPaymentWithConvenienceFees()
    {
        $payment   = $this->getDefaultPaymentArray();

        $feesArray = $this->testFees($payment);

        $amount = $payment['amount'];

        $payment['amount'] = $payment['amount'] + $feesArray['input']['fee'];

        $payment['fee']    = $feesArray['input']['fee'];

        // This is for simulating capture with the
        // original amount

        $this->doAuthAndCapturePayment($payment, $amount);

        $payment = $this->getLastPayment();

        $this->assertEquals($payment['fee'], 1150);

        $this->assertEquals($payment['service_tax'], 154);
    }

    public function testInvalidCaptureAmount()
    {
        $payment = $this->getDefaultPaymentArray();

        $feesArray = $this->testFees($payment);

        $amount = $payment['amount'];

        $payment['amount'] = $payment['amount'] + $feesArray['input']['fee'];

        // Fee is correct
        $payment['fee'] = $feesArray['input']['fee'];

        // Amount is wrong
        $invalidCaptureAmount = $amount + $payment['fee'] * 2;

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment, $invalidCaptureAmount)
            {
                $this->doAuthAndCapturePayment($payment, $invalidCaptureAmount);
            });

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['amount'], $amount + $payment['fee']);
    }

    public function testCaptureAmountWithFees()
    {
        $data = $this->testData['testInvalidCaptureAmount'];

        $payment = $this->getDefaultPaymentArray();

        $feesArray = $this->testFees($payment);

        $amount = $payment['amount'];

        // Amount is incorrect, it contains fees also.
        $payment['amount'] = $payment['amount'] + $feesArray['input']['fee'];

        $payment['fee'] = $feesArray['input']['fee'];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment, $amount)
            {
                $this->doAuthAndCapturePayment($payment, ($amount + $payment['fee']));
            });

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['amount'], $amount + $payment['fee']);
    }

    public function testAmountMismatch()
    {
        $payment = $this->getDefaultPaymentArray();

        $feesArray = $this->testFees($payment);

        $feesArray['input']['fee'] = 0;

        $payment['amount'] = $payment['amount'] + $feesArray['input']['fee'];

        $payment['fee']    = $feesArray['input']['fee'];

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doAuthAndCapturePayment($payment);
            });
    }

    // TODO Add tests for create with order
    public function testPaymentWithOrder()
    {
        $orderInput = $this->testData['testCreateOrder'];

        $this->ba->privateAuth();

        $order = $this->runRequestResponseFlow($orderInput);

        $this->ba->publicAuth();

        $payment   = $this->getDefaultPaymentArray();

        $feesArray = $this->testFees($payment);

        $amount = $payment['amount'];

        $payment['order_id'] = $order['id'];

        $payment['amount'] = $payment['amount'] + $feesArray['input']['fee'];

        $payment['fee']    = $feesArray['input']['fee'];

        $this->doAuthAndCapturePayment($payment, $amount);

        $payment = $this->getLastPayment();

        $this->assertEquals($payment['order_id'], $order['id']);

        $this->assertEquals($payment['fee'], 1150);

        $this->assertEquals($payment['service_tax'], 154);
    }

    // TODO Fail tests for create with order
    public function testFailedPaymentWithOrder()
    {
        $orderInput = $this->testData['testCreateOrder'];

        $this->ba->privateAuth();

        $order = $this->runRequestResponseFlow($orderInput);

        $data = $this->testData['testAmountMismatch'];

        $this->ba->publicAuth();

        $payment   = $this->getDefaultPaymentArray();

        $payment['amount'] = 30000;

        $feesArray = $this->testFees($payment);

        $payment['order_id'] = $order['id'];

        $payment['amount'] = $payment['amount'] + $feesArray['input']['fee'];

        $payment['fee']    = $feesArray['input']['fee'] + 100;

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->doAuthAndCapturePayment($payment);
        });
    }
}
