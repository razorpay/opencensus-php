<?php

use Carbon\Carbon;
use RZP\Constants\Entity;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\UpiMetadata;
use RZP\Models\Payment\Entity as Payment;
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

        $this->fixtures->create('terminal:dedicated_mindgate_recurring_terminal');

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);

        // use old autopay pricing for old test cases
        $this->mockSplitzTreatmentForAutopayPricing('variant_on');
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
        $this->terminal = $this->fixtures->create('terminal:dedicated_upi_icici_intent_recurring_terminal');

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

    public function testCreatePayuUpiMandatePayment()
    {
        $orderId = $this->createUpiRecurringOrder();

        $terminal = $this->fixtures->create('terminal:payu_upi_recurring_terminal');

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $this->getDefaultUpiRecurringPaymentArray(),
        ];

        $request['content']['force_terminal_id'] = 'term_' . $terminal->getId();

        $request['content']['order_id'] = $orderId;

        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures(['raas', 'allow_force_terminal_id']);

        $this->mockSplitzTreatmentForAutopayOptimizerUpi('variant_on','control','enabled');

        $request['content']['description'] = 'success_recurring_collect';

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNotNull($response['payment_id'] ?? null);

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $token = $this->getDbLastEntity('token');

        $this->assertEquals($upiMandate['token_id'], $token['id']);

        $this->assertEquals($upiMandate['customer_id'], $token['customer_id']);

        $this->assertEquals('created', $upiMandate['status']);

        $this->fixtures->merchant->removeFeatures(['raas', 'allow_force_terminal_id']);
    }

    public function testCreatePayuUpiMandateIntentPayment()
    {
        $orderId = $this->createUpiRecurringOrder();

        $terminal = $this->fixtures->create('terminal:payu_upi_recurring_terminal');

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $this->getDefaultUpiRecurringPaymentArray(),
        ];

        $request['content']['force_terminal_id'] = 'term_' . $terminal->getId();

        $request['content']['order_id'] = $orderId;

        unset($request['content']['vpa']);

        $request['content']['_']['flow'] = 'intent';

        $request['content']['description'] = 'success_recurring_intent';

        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures(['raas', 'allow_force_terminal_id']);

        $this->mockSplitzTreatmentForAutopayOptimizerUpi('variant_on','control','enabled');

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNotNull($response['payment_id'] ?? null);

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $token = $this->getDbLastEntity('token');

        $this->assertEquals($upiMandate['token_id'], $token['id']);
        $this->assertEquals($response['type'], 'intent');

        $this->assertEquals($upiMandate['customer_id'], $token['customer_id']);

        $this->assertEquals('created', $upiMandate['status']);

        $this->fixtures->merchant->removeFeatures(['raas', 'allow_force_terminal_id']);
    }

    protected function setMockRazorxTreatment(array $razorxTreatment, string $defaultBehaviour = 'off')
    {
        // Mock Razorx
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode) use ($razorxTreatment, $defaultBehaviour)
                {
                    if (array_key_exists($feature, $razorxTreatment) === true)
                    {
                        return $razorxTreatment[$feature];
                    }

                    return strtolower($defaultBehaviour);
                }));
    }

    public function testPayuUpiAutopayCallbackSuccess()
    {
        $orderId = $this->createUpiRecurringOrder();

        $terminal = $this->fixtures->create('terminal:payu_upi_recurring_terminal');

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $this->getDefaultUpiRecurringPaymentArray(),
        ];

        $request['content']['force_terminal_id'] = 'term_' . $terminal->getId();

        $request['content']['order_id'] = $orderId;

        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures(['raas', 'allow_force_terminal_id']);

        $this->mockSplitzTreatmentForAutopayOptimizerUpi('variant_on','control','enabled');

        $request['content']['description'] = 'success_recurring_collect';

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNotNull($response['payment_id'] ?? null);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $input = [
            'description' => '',
        ];

        $this->fixtures->base->editEntity(Entity::PAYMENT, $payment['id'], $input);

        $txnid = substr($payment['id'], 4);

        // Immediate webhooks are rejected, add buffer
        $testTime = Carbon::now()->addMinutes(4);
        Carbon::setTestNow($testTime);

        $response = $this->mockWebhookFromGateway($txnid, ['old_callback' => true]);
        $this->assertEquals(true, $response['success']);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);
        $this->assertEquals('captured', $payment[Payment::STATUS]);
        $this->assertTrue($payment[Payment::CAPTURED]);

        $upi_mandate = $this->getLastEntity(Entity::UPI_MANDATE, true);
        $this->assertEquals('confirmed', $upi_mandate['status']);
    }

    public function testPayuUpiAutopayCallbackAmountMismatch()
    {
        $orderId = $this->createUpiRecurringOrder();

        $terminal = $this->fixtures->create('terminal:payu_upi_recurring_terminal');

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $this->getDefaultUpiRecurringPaymentArray(),
        ];

        $request['content']['force_terminal_id'] = 'term_' . $terminal->getId();

        $request['content']['order_id'] = $orderId;

        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures(['raas', 'allow_force_terminal_id']);

        $this->mockSplitzTreatmentForAutopayOptimizerUpi('variant_on','control','enabled');

        $request['content']['description'] = 'success_recurring_collect';

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNotNull($response['payment_id'] ?? null);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $input = [
            'description' => 'paymentAmountFailed',
        ];

        $this->fixtures->base->editEntity(Entity::PAYMENT, $payment['id'], $input);

        $txnid = substr($payment['id'], 4);

        // Immediate webhooks are rejected, add buffer
        $testTime = Carbon::now()->addMinutes(4);
        Carbon::setTestNow($testTime);

        $response = $this->mockWebhookFromGateway($txnid, ['old_callback' => true]);
        $this->assertEquals(false, $response['success']);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);
        $this->assertEquals('failed', $payment[Payment::STATUS]);

        $upi_mandate = $this->getLastEntity(Entity::UPI_MANDATE, true);
        $this->assertEquals('created', $upi_mandate['status']);
    }

    public function testPayuUpiAutopayCallbackFailure()
    {
        $orderId = $this->createUpiRecurringOrder();

        $terminal = $this->fixtures->create('terminal:payu_upi_recurring_terminal');

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $this->getDefaultUpiRecurringPaymentArray(),
        ];

        $request['content']['force_terminal_id'] = 'term_' . $terminal->getId();

        $request['content']['order_id'] = $orderId;

        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures(['raas', 'allow_force_terminal_id']);

        $this->mockSplitzTreatmentForAutopayOptimizerUpi('variant_on','control','enabled');

        $request['content']['description'] = 'success_recurring_collect';

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNotNull($response['payment_id'] ?? null);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $input = [
            'description' => 'paymentCreateFailed',
        ];

        $this->fixtures->base->editEntity(Entity::PAYMENT, $payment['id'], $input);

        $txnid = substr($payment['id'], 4);

        // Immediate webhooks are rejected, add buffer
        $testTime = Carbon::now()->addMinutes(4);
        Carbon::setTestNow($testTime);

        $response = $this->mockWebhookFromGateway($txnid, ['old_callback' => true]);
        $this->assertEquals(false, $response['success']);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);
        $this->assertEquals('failed', $payment[Payment::STATUS]);

        $upi_mandate = $this->getLastEntity(Entity::UPI_MANDATE, true);
        $this->assertEquals('created', $upi_mandate['status']);
    }

    protected function mockWebhookFromGateway($paymentId, $details = [], $recurring = false)
    {
        $content = [
            'mihpayid' => '403993715527148090',
            'mode' => 'UPI',
            'status' => 'success',
            'key' => '4039937',
            'txnid' => $paymentId,
            'amount' => '10.00',
            'productinfo' => 'abcd',
            'email' => '',
            'phone' => 'ENACH514668605404891575',
            'hash' => '2451471f3b2e8cf5fbebf255b0034cd433274ab1fba20bebcb34c7d36d060d82d37327eae07c7eff7141d470f00aeb142987ac5746087de01a2d692a953da0e7',
            'error' => 'E000',
            'bankcode' => 'hdfc',
            'bank_ref_num' => 'ENACH514668605404891575',
            'payment_source' => 'sist',
        ];

        if ($recurring == true)
        {
            $content['payment_source'] = 'sist';
            $content['amount'] = $details['amount'] ?? $content['amount'];
        }

        $request = [
            'content' => $content,
            'url' => '/callback/payu',
            'method' => 'post'
        ];

        return $this->makeRequestAndGetContent($request);
    }


    protected function mockSession($appToken = 'capp_1000000custapp')
    {
        $data = ['test_app_token' => $appToken];

        $this->session($data);
    }

    /**
     * returns a mock response of the razorx request
     *
     * @param string $inputFeature
     * @param string $expectedFeature
     * @param string $variant
     * @return string
     */
    protected function getRazoxVariant(string $inputFeature, string $expectedFeature, string $variant): string
    {
        if ($expectedFeature === $inputFeature)
        {
            return $variant;
        }

        return 'control';
    }

    /**
     * sets the razox mock
     *
     * @param [type] $closure
     * @return void
     */
    protected function setRazorxMock($closure)
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx
            ->method('getTreatment')
            ->will($this->returnCallback($closure));
    }

    public function testPayuAutoRecurringPreDebitInitiationFailure()
    {
        $this->gateway = 'mozart';
        Carbon::setTestNow(Carbon::parse('first day of this month', 'UTC'));
        $terminal = $this->fixtures->create('terminal:payu_upi_recurring_terminal');

        $this->terminalId = $terminal->getId();
        $this->fixtures->merchant->addFeatures(['raas', 'allow_force_terminal_id']);

        $this->mockSplitzTreatmentForAutopayOptimizerUpi('variant_on','control','enabled');

        $this->createDbUpiMandate(['frequency' => 'as_presented']);

        $this->createDbUpiToken();

        $input = $this->getDbUpiAutoRecurringPayment();

        // The request which we have sent to create the reminder
        $this->assertReminderRequest('createReminder', $createReminder, $pending);

        $input['amount'] = '100';
        $this->expectExceptionMessage(
            'Your payment amount is different from your order amount. To pay successfully, please try using right amount.');

        $response = $this->doS2SRecurringPayment($input);

        $this->assertUpiDbLastEntity('payment', [
            'status'        => 'failed',
            'reference1'    => null,
            'reference16'   => null,
        ], false);

    }

    public function testPayuAutoRecurringPreDebitInitiationSuccess()
    {
        $this->gateway = 'mozart';
        Carbon::setTestNow(Carbon::parse('first day of this month', 'UTC'));
        $terminal = $this->fixtures->create('terminal:payu_upi_recurring_terminal');

        $this->terminalId = $terminal->getId();

        $this->fixtures->merchant->addFeatures(['raas', 'allow_force_terminal_id']);

        $this->mockSplitzTreatmentForAutopayOptimizerUpi('variant_on','control','enabled');

        $this->createDbUpiMandate();

        $this->createDbUpiToken();

        $input = $this->getDbUpiAutoRecurringPayment();

        // The request which we have sent to create the reminder
        $this->assertReminderRequest('createReminder', $createReminder, $pending);

        $response = $this->doS2SRecurringPayment($input);

        $payment = $this->assertUpiDbLastEntity('payment', [
            'gateway' => 'payu',
            'cps_route' => 0,
        ]);

        $this->assertArraySubset([
            'razorpay_payment_id'   => $payment->getPublicId(),
            'razorpay_order_id'     => $this->order->getPublicId(),
        ], $response);

        $this->assertArrayHasKey('razorpay_signature', $response);

        // The first reminder call will trigger an update reminder
        $this->assertReminderRequest('updateReminder', $updateReminder, $pending);

        $requestAsserted = [
            'notify'    => false,
            'pay_init'  => false,
        ];

        // Gateway request will be sent in next step
        $this->mockServerRequestFunction(function (& $content, $action) use (& $requestAsserted)
        {
            if ($action === 'notify')
            {
                $requestAsserted['notify'] = true;

                $paymentId = $content['payment']['id'];
                $paymentCreatedAt = $content['payment']['created_at'];

                $this->assertArraySubset([
                    'act'   => 'notify',
                    'ano'   => 1,
                    'ext'   => $paymentCreatedAt + 176400,
                    'sno'   => 2,
                    'id'    => $paymentId . '0notify' . 1,
                ], $content['upi']['gateway_data']);

                return;
            }
        });

        $metadata = $payment->getUpiMetadata();
        $metadata->setInternalStatus(UpiMetadata\InternalStatus::REMINDER_IN_PROGRESS_FOR_PRE_DEBIT);
        $newMetadata = (new UpiMetadata\Core)->update($metadata);
        $payment->setMetadata($newMetadata);

        $this->sendReminderRequest($createReminder);

        $this->assertTrue($requestAsserted['notify']);

        $this->assertUpiDbLastEntity('upi', [
            'status_code'       => 'pending',
            'gateway_data'      => [
                'act'   => 'execte',
                'ano'   => 1,
                'sno'   => 2,
            ],
        ]);

    }

    public function testPayuAutoRecurringPreDebitInitiationSuccessCapture()
    {
        $this->gateway = 'mozart';
        Carbon::setTestNow(Carbon::parse('first day of this month', 'UTC'));
        $terminal = $this->fixtures->create('terminal:payu_upi_recurring_terminal');

        $this->terminalId = $terminal->getId();

        $this->fixtures->merchant->addFeatures(['raas', 'allow_force_terminal_id']);

        $this->mockSplitzTreatmentForAutopayOptimizerUpi('variant_on','control','enabled');

        $this->createDbUpiMandate();

        $this->createDbUpiToken();

        $input = $this->getDbUpiAutoRecurringPayment();

        // The request which we have sent to create the reminder
        $this->assertReminderRequest('createReminder', $createReminder, $pending);

        $response = $this->doS2SRecurringPayment($input);

        $payment = $this->assertUpiDbLastEntity('payment', [
            'gateway' => 'payu',
            'cps_route' => 0,
        ]);

        $this->assertArraySubset([
            'razorpay_payment_id'   => $payment->getPublicId(),
            'razorpay_order_id'     => $this->order->getPublicId(),
        ], $response);

        $this->assertArrayHasKey('razorpay_signature', $response);

        // The first reminder call will trigger an update reminder
        $this->assertReminderRequest('updateReminder', $updateReminder, $pending);

        $requestAsserted = [
            'notify'    => false,
            'pay_init'  => false,
        ];

        // Gateway request will be sent in next step
        $this->mockServerRequestFunction(function (& $content, $action) use (& $requestAsserted)
        {
            if ($action === 'notify')
            {
                $requestAsserted['notify'] = true;

                $paymentId = $content['payment']['id'];
                $paymentCreatedAt = $content['payment']['created_at'];

                $this->assertArraySubset([
                    'act'   => 'notify',
                    'ano'   => 1,
                    'ext'   => $paymentCreatedAt + 176400,
                    'sno'   => 2,
                    'id'    => $paymentId . '0notify' . 1,
                ], $content['upi']['gateway_data']);

                return;
            }
        });

        $metadata = $payment->getUpiMetadata();
        $metadata->setInternalStatus(UpiMetadata\InternalStatus::REMINDER_IN_PROGRESS_FOR_PRE_DEBIT);
        $newMetadata = (new UpiMetadata\Core)->update($metadata);
        $payment->setMetadata($newMetadata);

        $this->sendReminderRequest($createReminder);

        $this->assertTrue($requestAsserted['notify']);

        $this->assertUpiDbLastEntity('upi', [
            'status_code'       => 'pending',
            'gateway_data'      => [
                'act'   => 'execte',
                'ano'   => 1,
                'sno'   => 2,
            ],
        ]);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);

        $input = [
            'description' => '',
        ];

        $this->fixtures->base->editEntity(Entity::PAYMENT, $payment['id'], $input);

        $txnid = substr($payment['id'], 4);

        // Immediate webhooks are rejected, add buffer
        $testTime = Carbon::now()->addMinutes(4);
        Carbon::setTestNow($testTime);

        $response = $this->mockWebhookFromGateway($txnid, ['old_callback' => true]);
        $this->assertEquals(true, $response['success']);

        $payment = $this->getLastEntity(Entity::PAYMENT, true);
        $this->assertEquals('captured', $payment[Payment::STATUS]);
        $this->assertTrue($payment[Payment::CAPTURED]);

    }
}
