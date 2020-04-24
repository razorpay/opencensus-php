<?php

use Carbon\Carbon;

use RZP\Tests\Functional\TestCase;
use RZP\Models\Feature\Constants as Feature;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;

class UpiRecurringPaymentCreateTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;
    use InteractsWithSession;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/UpiRecurringPaymentTestData.php';

        parent::setUp();

        $this->fixtures->create('terminal:shared_mindgate_recurring_terminal');

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);
    }

    public function testCreateFirstUpiRecurringPayment()
    {
        $orderId = $this->createUpiRecurringOrder();

        $payment = $this->getDefaultUpiRecurringPaymentArray($orderId);

        $this->doAuthPayment($payment);

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $token = $this->getDbLastEntity('token');

        $this->assertEquals($upiMandate['token_id'], $token['id']);

        $this->assertEquals($upiMandate['customer_id'], $token['customer_id']);

        $this->assertEquals('created', $upiMandate['status']);
    }

    public function testCreateFirstUpiRecurringPaymentGlobal()
    {
        $this->mockSession();

        $orderId = $this->createUpiRecurringOrder(['customer_id' => 'cust_10000gcustomer']);

        $payment = $this->getDefaultUpiRecurringPaymentArray($orderId);

        unset($payment['customer_id']);

        $this->doAuthPayment($payment);

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $token = $this->getDbLastEntity('token');

        $this->assertEquals($upiMandate['token_id'], $token['id']);

        $this->assertEquals($upiMandate['customer_id'], $token['customer_id']);

        $this->assertEquals('created', $upiMandate['status']);
    }

    public function testCreateFirstRecurringPaymentWithoutOrder()
    {
        $payment = $this->getDefaultUpiRecurringPaymentArray();

        unset ($payment['order_id']);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment) {

            $this->doAuthPayment($payment);
        });
    }

    public function testCreateFirstUpiRecurringPaymentWithAmountMismatch()
    {
        $orderId = $this->createUpiRecurringOrder();

        $payment = $this->getDefaultUpiRecurringPaymentArray($orderId);

        $payment['amount'] = 200;

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment) {

            $this->doAuthPayment($payment);
        });
    }

    public function testCreateFirstUpiRecurringPaymentWithZeroAmount()
    {
        $orderId = $this->createUpiRecurringOrder();

        $payment = $this->getDefaultUpiRecurringPaymentArray($orderId);

        $payment['amount'] = 0;

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment) {

            $this->doAuthPayment($payment);
        });
    }

    public function testCreateIntentRecurringPayment()
    {
        $orderId = $this->createUpiRecurringOrder();

        $payment = $this->getDefaultUpiRecurringPaymentArray($orderId);

        unset($payment['vpa']);

        $payment['_']['flow'] = 'intent';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment) {

            $this->doAuthPayment($payment);
        });
    }

    public function testCreateRecurringWithInvalidOrder()
    {
        $orderId = $this->createUpiOrder();

        $payment = $this->getDefaultUpiRecurringPaymentArray($orderId);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment) {

            $this->doAuthPayment($payment);
        });
    }

    protected function createUpiRecurringOrder(array $override = [])
    {
        $this->ba->privateAuth();

        $content =  [
            'amount'          => 50000,
            'currency'        => 'INR',
            'method'          => 'upi',
            'customer_id'     => 'cust_100000customer',
            'payment_capture' => 1,
            'token'           => [
                'max_amount'      => 150000,
                'frequency'       => 'monthly',
                'recurring_type'  => 'before',
                'recurring_value' => 30,
                'start_time'      => Carbon::now()->addDay(1)->getTimestamp(),
                'end_time'        => Carbon::now()->addDay(60)->getTimestamp(),
            ]
        ];

        $content = array_merge($content, $override);

        $request = [
            'method'  => 'POST',
            'content' => $content,
            'url' => '/orders',
        ];

        $order = $this->makeRequestAndGetContent($request);

        return $order['id'];
    }

    protected function createUpiOrder(array $override = [])
    {
        $this->ba->privateAuth();

        $content =  [
            'amount'          => 50000,
            'currency'        => 'INR',
            'method'          => 'upi',
            'customer_id'     => 'cust_100000customer',
            'payment_capture' => 1,
        ];

        $content = array_merge_recursive($content, $override);

        $request = [
            'method'  => 'POST',
            'content' => $content,
            'url' => '/orders',
        ];

        $order = $this->makeRequestAndGetContent($request);

        return $order['id'];
    }

    protected function mockSession($appToken = 'capp_1000000custapp')
    {
        $data = ['test_app_token' => $appToken];

        $this->session($data);
    }
}
