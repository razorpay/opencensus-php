<?php

use Carbon\Carbon;

use RZP\Tests\Functional\TestCase;
use RZP\Models\Feature\Constants as Feature;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\PaymentsUpiRecurringTrait;
use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;

class UpiRecurringPaymentCreateTest extends TestCase
{
    use PaymentTrait;
    use InteractsWithSession;
    use PaymentsUpiRecurringTrait;

    protected function setUp(): void
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

        $payment = $this->getDefaultUpiRecurringPaymentArray();

        $payment['order_id'] = $orderId;

        $payment['customer_id'] = 'cust_100000customer';

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

        $payment = $this->getDefaultUpiRecurringPaymentArray();

        $payment['order_id'] = $orderId;
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

        $payment['customer_id'] = 'cust_100000customer';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment) {

            $this->doAuthPayment($payment);
        });
    }

    public function testCreateFirstUpiRecurringPaymentWithAmountMismatch()
    {
        $orderId = $this->createUpiRecurringOrder();

        $payment = $this->getDefaultUpiRecurringPaymentArray();

        $payment['order_id'] = $orderId;

        $payment['customer_id'] = 'cust_100000customer';

        $payment['amount'] = 200;

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment) {

            $this->doAuthPayment($payment);
        });
    }

    public function testCreateFirstUpiRecurringPaymentWithZeroAmount()
    {
        $orderId = $this->createUpiRecurringOrder();

        $payment = $this->getDefaultUpiRecurringPaymentArray();

        $payment['order_id'] = $orderId;

        $payment['customer_id'] = 'cust_100000customer';

        $payment['amount'] = 0;

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment) {

            $this->doAuthPayment($payment);
        });
    }

    public function testCreateIntentRecurringPayment()
    {
        $this->terminal = $this->fixtures->create('terminal:shared_icici_recurring_intent_terminal');

        $orderId = $this->createUpiRecurringOrder();

        $payment = $this->getDefaultUpiRecurringPaymentArray();

        $payment['order_id'] = $orderId;

        unset($payment['vpa']);

        $payment['_']['flow'] = 'intent';

        $response = $this->doAuthPayment($payment);

        // Just to validate that a proper coproto is being send
        $this->assertArraySubset([
            'type'      => 'intent',
            'request'   => [
                'method' => 'get'
            ],
        ], $response);

        $this->assertFalse(empty($response['data']['intent_url']), 'Intent URL not set in the response');

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $upiMetadata = $this->getDbLastEntity('upi_metadata');

        $token = $this->getDbLastEntity('token');

        $this->assertEquals($upiMandate['token_id'], $token['id']);

        $this->assertEquals($upiMandate['customer_id'], $token['customer_id']);

        $this->assertEquals('intent', $upiMetadata['flow']);

        $this->assertEquals('created', $upiMandate['status']);

    }

    public function testCreateRecurringWithInvalidOrder()
    {
        $orderId = $this->createUpiOrder();

        $payment = $this->getDefaultUpiRecurringPaymentArray();

        $payment['order_id'] = $orderId;

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment) {

            $this->doAuthPayment($payment);
        });
    }

    protected function mockSession($appToken = 'capp_1000000custapp')
    {
        $data = ['test_app_token' => $appToken];

        $this->session($data);
    }
}
