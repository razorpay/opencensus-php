<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Merchant\FeeBearer;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class PaymentCreateConvenienceFeeTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/PaymentCreateConvenienceFeeTestData.php';

        parent::setUp();

        $this->ba->publicAuth();

        $this->fixtures->merchant->enableConvenienceFeeModel();

        $this->fixtures->pricing->editDefaultPlan(['fee_bearer' => FeeBearer::CUSTOMER]);

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

        if ($payment['amount'] === '50000')
        {
            $this->assertEquals(1000, $feesArray['input']['fee']);

            $this->assertEquals(0, $feesArray['display']['tax']);
        }

        return $feesArray;
    }

    public function testFeesS2S()
    {
        $this->ba->privateAuth();

        $payment = $this->getDefaultPaymentArray();

        $feesArray = $this->createAndGetFeesForPaymentS2S($payment);

        if ($payment['amount'] === '50000')
        {
            $this->assertEquals(0, $feesArray['tax']);

            $this->assertEquals(1000, $feesArray['fees']);

            $this->assertEquals(50000, $feesArray['original_amount']);

            // Checkout utilizes originalAmount but not this route
            // TODO : Remove this when checkout stops using originalAmount
            $this->assertArrayNotHasKey('originalAmount', $feesArray);
        }

        return $feesArray;
    }

    public function testFeesRouteOnPlatformFeeBearer()
    {
        $this->fixtures->merchant->disableConvenienceFeeModel();

        $payment = $this->getDefaultPaymentArray();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function () use ($payment)
        {
            $this->createAndGetFeesForPayment($payment);
        });

        $this->fixtures->merchant->enableConvenienceFeeModel();
    }

    public function testPaymentCreateRouteOnCustomerFeeBearer()
    {
        $this->fixtures->merchant->enableConvenienceFeeModel();

        $payment = $this->getDefaultPaymentArray();

        $request = $this->buildAuthPaymentRequest($payment);

        $this->ba->publicAuth();

        $response = $this->makeRequestParent($request);

        $response->assertViewIs('gateway.gatewayFeesForm');
        $response->assertViewHas(['data', 'input', 'url']);
    }

    public function testPaymentCreateRouteOnDynamicFeeBearer()
    {
        $this->fixtures->merchant->enableDynamicFeeModel();

        $payment = $this->getDefaultPaymentArray();

        $request = $this->buildAuthPaymentRequest($payment);

        $this->ba->publicAuth();

        $response = $this->makeRequestParent($request);

        $response->assertViewIs('gateway.gatewayFeesForm');
        $response->assertViewHas(['data', 'input', 'url']);
    }

    public function testPaymentCreateRouteOnPlatformFeeBearer()
    {
        $this->fixtures->merchant->disableConvenienceFeeModel();

        $this->fixtures->pricing->editDefaultPlan(['fee_bearer' => FeeBearer::PLATFORM]);

        $payment = $this->getDefaultPaymentArray();

        $request = $this->buildAuthPaymentRequest($payment);

        $this->ba->publicAuth();

        $response = $this->makeRequestParent($request);

        $response->assertViewIs('gateway.gatewayPostForm');
        $response->assertViewHas(['data']);
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

        $this->assertEquals($payment['fee'], 1000);

        $this->assertEquals($payment['tax'], 0);
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

        $this->assertEquals($payment['fee'], 1000);

        $this->assertEquals($payment['tax'], 0);
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
