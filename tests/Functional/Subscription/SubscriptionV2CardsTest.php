<?php

namespace RZP\Tests\Functional\Subscription;

use Carbon\Carbon;
use Requests_Session;
use Requests_Response;

use RZP\Modules\Subscriptions;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Subscription\SubscriptionTrait;

class SubscriptionV2CardsTest extends TestCase
{
    use PaymentTrait;
    use SubscriptionTrait;
    use DbEntityFetchTrait;

    public function setUp()
    {
        // This is set to 10 Jan 2018
        // Because in test cases subsription start date is set
        // to 20 Jan 2018 and it should always be in future
        Carbon::setTestNow("10-1-2018 3:00:00");

        $this->testDataFilePath = __DIR__ . '/Helpers/SubscriptionTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['subscriptions']);

        $this->fixtures->create('terminal:shared_cybersource_hdfc_recurring_terminals');

        $this->gateway = 'cybersource';

        $this->mockTokenex();
    }

    public function testSubscriptionV2PaymentFirst2FaLocalSavedCard()
    {
        $subscription = $this->createSubscription(
            true, [], ['customer_id' => 'cust_100000customer'], false, false, false
        );

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription, null, 'token_100000custcard');

        $requestMock = $this->createMock(Requests_Session::class);

        $subscription = $this->getDbLastEntity('subscription');
        $subscription->setGlobalCustomer(true);

        $mockSuccessResponse = new Requests_Response;

        $mockSuccessResponse->status_code = 200;
        $mockSuccessResponse->success = true;
        $mockSuccessResponse->body = json_encode($subscription->attributesToArray());

        $requestMock->expects($this->exactly(1))->method('request')->will($this->returnValue($mockSuccessResponse));

        $this->registerMockedClient($requestMock);

        $this->fixtures->base->editEntity('card', '100000000lcard', ["type" => 'credit']);

        $response = $this->doAuthPayment($paymentRequest);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        $subscription = $this->getLastEntity('subscription', true);

        $this->assertEquals($subscription['id'], $payment['subscription_id']);
        $this->assertEquals('token_100000custcard', $payment['token_id']);
        $this->assertNull($payment['global_token_id']);
        $this->assertEquals('cust_100000customer', $payment['customer_id']);
        $this->assertNull($payment['global_customer_id']);
    }

    public function testSubscriptionV2PaymentFirst2FaLocalNewCard()
    {
        $subscription = $this->createSubscription(
            true, [], ['customer_id' => 'cust_100000customer'], false, false, false);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);

        $requestMock = $this->createMock(Requests_Session::class);

        $subscription = $this->getDbLastEntity('subscription');
        $subscription->setGlobalCustomer(true);

        $mockSuccessResponse = new Requests_Response;

        $mockSuccessResponse->status_code = 200;
        $mockSuccessResponse->success = true;
        $mockSuccessResponse->body = json_encode($subscription->attributesToArray());

        $requestMock->expects($this->exactly(2))->method('request')->will($this->returnValue($mockSuccessResponse));

        $this->registerMockedClient($requestMock);

        $response = $this->doAuthPayment($paymentRequest);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        $subscription = $this->getLastEntity('subscription', true);

        $token = $this->getDbLastEntityPublic('token');

        $this->assertEquals($subscription['id'], $payment['subscription_id']);
        $this->assertNull($payment['global_token_id']);
        $this->assertEquals($token['id'], $payment['token_id']);
        $this->assertEquals('cust_100000customer', $payment['customer_id']);
        $this->assertNull($payment['global_customer_id']);
    }

    public function testSubscriptionV2PaymentSecond2FaLocalSavedCard()
    {
        $subscription = $this->createSubscription(
            false, [], ['customer_id' => 'cust_100000customer'], false, false, false);

        // The item amount is 2k. It's modified while creating plan via fixtures.
        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription, 2000);

        $requestMock = $this->createMock(Requests_Session::class);

        $subscription = $this->getDbLastEntity('subscription');
        $subscription->setGlobalCustomer(true);

        $mockSuccessResponse = new Requests_Response;

        $mockSuccessResponse->status_code = 200;
        $mockSuccessResponse->success = true;
        $mockSuccessResponse->body = json_encode($subscription->attributesToArray());

        $requestMock->expects($this->exactly(3))->method('request')->will($this->returnValue($mockSuccessResponse));

        $this->registerMockedClient($requestMock);

        $response = $this->doAuthPayment($paymentRequest);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        $subscription = $this->getLastEntity('subscription', true);

        $token = $this->getLastEntity('token', true);

        // ------------

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription, null, 'token_100000custcard');
        $this->fixtures->base->editEntity('card', '100000000lcard', ["type" => 'credit']);
        $paymentRequest['subscription_card_change'] = 1;

        $response2 = $this->doAuthPayment($paymentRequest);

        $payment2 = $this->getEntityById('payment', $response2['razorpay_payment_id'], true);

        $subscription2 = $this->getLastEntity('subscription', true);

        $token2 = 'token_100000custcard';

        // ---------------

        $this->assertNotEquals($token['id'], $payment2['token_id']);
        $this->assertNotEquals($payment['id'], $payment2['id']);
        $this->assertEquals($subscription['id'], $subscription2['id']);

        $this->assertEquals($token2, $payment2['token_id']);
        $this->assertEquals($subscription['customer_id'], $subscription2['customer_id']);
        $this->assertEquals($payment['customer_id'], $payment2['customer_id']);

        $this->assertNull($payment2['global_token_id']);
        $this->assertEquals(500, $payment2['amount']);
    }

    public function testSubscriptionV2PaymentSecond2FaLocalNewCard()
    {
        $subscription = $this->createSubscription(
            false, [], ['customer_id' => 'cust_100000customer'], false, false, false);

        // The item amount is 2k. It's modified while creating plan via fixtures.
        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription, 2000);

        $requestMock = $this->createMock(Requests_Session::class);

        $subscription = $this->getDbLastEntity('subscription');
        $subscription->setGlobalCustomer(true);

        $mockSuccessResponse = new Requests_Response;

        $mockSuccessResponse->status_code = 200;
        $mockSuccessResponse->success = true;
        $mockSuccessResponse->body = json_encode($subscription->attributesToArray());

        $requestMock->expects($this->exactly(4))->method('request')->will($this->returnValue($mockSuccessResponse));

        $this->registerMockedClient($requestMock);

        $response = $this->doAuthPayment($paymentRequest);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        $subscription = $this->getLastEntity('subscription', true);

        $token = $this->getLastEntity('token', true);

        // ------------

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);
        $paymentRequest['card']['number'] = '4000000000000002';
        $paymentRequest['subscription_card_change'] = 1;

        $response2 = $this->doAuthPayment($paymentRequest);

        $payment2 = $this->getEntityById('payment', $response2['razorpay_payment_id'], true);

        $subscription2 = $this->getLastEntity('subscription', true);

        $token2 = $this->getDbLastEntityPublic('token');

        // ---------------

        $this->assertNotEquals($token['id'], $token2['id']);
        $this->assertNotEquals($payment['id'], $payment2['id']);
        $this->assertEquals($subscription['id'], $subscription2['id']);

        $this->assertEquals($token2['id'], $payment2['token_id']);

        $this->assertEquals($subscription['customer_id'], $subscription2['customer_id']);
        $this->assertEquals($payment['customer_id'], $payment2['customer_id']);

        $this->assertNull($payment2['global_token_id']);
        $this->assertEquals(500, $payment2['amount']);
        $this->assertEquals('authorized', $payment2['status']);
        $this->assertEquals($subscription['id'], $payment2['subscription_id']);
    }

    public function testSubscriptionV2ChargeLocalSavedCard()
    {
        $subscription = $this->createSubscription(
            false, [], ['customer_id' => 'cust_100000customer'], false, false, false);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription, 2000, 'token_100000custcard');

        $this->fixtures->base->editEntity('card', '100000000lcard', ["type" => 'credit']);

        $requestMock = $this->createMock(Requests_Session::class);

        $subscription = $this->getDbLastEntity('subscription');
        $subscription->setGlobalCustomer(true);

        $mockSuccessResponse = new Requests_Response;

        $mockSuccessResponse->status_code = 200;
        $mockSuccessResponse->success = true;
        $mockSuccessResponse->body = json_encode($subscription->attributesToArray());

        $requestMock->expects($this->exactly(1))->method('request')->will($this->returnValue($mockSuccessResponse));

        $this->registerMockedClient($requestMock);

        $response = $this->doAuthPayment($paymentRequest);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        $subscription = $this->getLastEntity('subscription', true);

        $token = $this->getEntityById('token', 'token_100000custcard', true);

        $this->assertEquals($token['id'], $payment['token_id']);
        $this->assertNull($payment['global_token_id']);
        $this->assertEquals(true, $token['recurring']);
    }

    public function testSubscriptionV2First2FaGlobalSavedCard()
    {
        $subscription = $this->createSubscription(true, [], [], false, false, false);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription, null, 'token_10000custgcard');
        unset($paymentRequest['card']);
        $paymentRequest['card'] = ['cvv' => 111];

        $this->mockSession();

        $requestMock = $this->createMock(Requests_Session::class);

        $subscription = $this->getDbLastEntity('subscription');
        $subscription->setGlobalCustomer(true);

        $mockSuccessResponse = new Requests_Response;

        $mockSuccessResponse->status_code = 200;
        $mockSuccessResponse->success = true;
        $mockSuccessResponse->body = json_encode($subscription->attributesToArray());

        $requestMock->expects($this->exactly(1))->method('request')->will($this->returnValue($mockSuccessResponse));

        $this->registerMockedClient($requestMock);

        $response = $this->doAuthPayment($paymentRequest);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        $subscription = $this->getLastEntity('subscription', true);

        $globalToken = $this->getEntityById('token', 'token_10000custgcard', true);

        $customer = $this->getDbLastEntityPublic('customer');
        $globalCust = $this->getEntityById('customer', '10000gcustomer', true);

        $this->assertEquals($subscription['id'], $payment['subscription_id']);
        $this->assertEquals($globalToken['id'], 'token_' . $payment['global_token_id']);
        $this->assertArrayNotHasKey('token_id', $payment);
        $this->assertEquals($customer['id'], $payment['customer_id']);
        $this->assertEquals($globalCust['id'], 'cust_' . $payment['global_customer_id']);

        $this->assertEquals($globalCust['id'], 'cust_' . $customer['global_customer_id']);
        $this->assertEquals($globalCust['email'], $customer['email']);
        $this->assertEquals($globalCust['contact'], $customer['contact']);

        Carbon::setTestNow();
    }

    public function testSubscriptionV2PaymentFirst2FaGlobalNewCard()
    {
        $subscription = $this->createSubscription(true, [], [], false, false, false);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);

        $this->mockSession();

        $requestMock = $this->createMock(Requests_Session::class);

        $subscription = $this->getDbLastEntity('subscription');
        $subscription->setGlobalCustomer(true);

        $mockSuccessResponse = new Requests_Response;

        $mockSuccessResponse->status_code = 200;
        $mockSuccessResponse->success = true;
        $mockSuccessResponse->body = json_encode($subscription->attributesToArray());

        $requestMock->expects($this->exactly(2))->method('request')->will($this->returnValue($mockSuccessResponse));

        $this->registerMockedClient($requestMock);

        $response = $this->doAuthPayment($paymentRequest);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        $subscription = $this->getLastEntity('subscription', true);

        $token = $this->getDbLastEntityPublic('token');

        $customer = $this->getDbLastEntityPublic('customer');
        $globalCust = $this->getEntityById('customer', '10000gcustomer', true);

        $this->assertEquals($subscription['id'], $payment['subscription_id']);
        $this->assertEquals($token['id'], 'token_' . $payment['global_token_id']);
        $this->assertArrayNotHasKey('token_id', $payment);
        $this->assertEquals($customer['id'], $payment['customer_id']);
        $this->assertEquals($globalCust['id'], 'cust_' . $payment['global_customer_id']);

        $this->assertEquals($globalCust['id'], 'cust_' . $customer['global_customer_id']);
        $this->assertEquals($globalCust['email'], $customer['email']);
        $this->assertEquals($globalCust['contact'], $customer['contact']);

        Carbon::setTestNow();
    }

    public function testSubscriptionV2PaymentSecond2FaGlobalSavedCard()
    {
        $subscription = $this->createSubscription(false, [], [], false, false, false);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription, 2000);

        $this->mockSession();

        $requestMock = $this->createMock(Requests_Session::class);

        $subscription = $this->getDbLastEntity('subscription');
        $subscription->setGlobalCustomer(true);

        $mockSuccessResponse = new Requests_Response;

        $mockSuccessResponse->status_code = 200;
        $mockSuccessResponse->success = true;
        $mockSuccessResponse->body = json_encode($subscription->attributesToArray());

        $requestMock->expects($this->exactly(3))->method('request')->will($this->returnValue($mockSuccessResponse));

        $this->registerMockedClient($requestMock);

        $response = $this->doAuthPayment($paymentRequest);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        $subscription = $this->getLastEntity('subscription', true);

        $token = $this->getDbLastEntityPublic('token');

        // ------------

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription, null, 'token_10000custgcard');
        unset($paymentRequest['card']);
        $paymentRequest['card'] = ['cvv' => 111];
        $paymentRequest['subscription_card_change'] = 1;

        $this->mockSession();

        $response2 = $this->doAuthPayment($paymentRequest);

        $payment2 = $this->getEntityById('payment', $response2['razorpay_payment_id'], true);

        $subscription2 = $this->getLastEntity('subscription', true);

        $token2 = $this->getEntityById('token', 'token_10000custgcard', true);

        $customer = $this->getDbLastEntityPublic('customer');
        $globalCust = $this->getEntityById('customer', '10000gcustomer', true);

        // ---------------

        $this->assertNotEquals($token['id'], $token2['id']);
        $this->assertEquals($token['customer_id'], $token2['customer_id']);
        $this->assertEquals('100000000gcard', $token2['card_id']);

        $this->assertArrayNotHasKey('token_id', $payment2);
        $this->assertEquals($token2['id'], 'token_' . $payment2['global_token_id']);
        $this->assertEquals($globalCust['id'], 'cust_' . $payment2['global_customer_id']);
        $this->assertEquals($customer['id'], $payment2['customer_id']);
        $this->assertEquals($subscription['id'], $payment2['subscription_id']);
        $this->assertEquals($payment['customer_id'], $payment2['customer_id']);
        $this->assertEquals($payment['global_customer_id'], $payment2['global_customer_id']);
        $this->assertNull($payment2['invoice_id']);
        $this->assertEquals(500, $payment2['amount']);
        $this->assertEquals('authorized', $payment2['status']);

        Carbon::setTestNow();
    }

    public function testSubscriptionV2PaymentSecond2FaGlobalNewCard()
    {
        $subscription = $this->createSubscription(false, [], [], false, false, false);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription, 2000);

        $requestMock = $this->createMock(Requests_Session::class);

        $subscription = $this->getDbLastEntity('subscription');
        $subscription->setGlobalCustomer(true);

        $mockSuccessResponse = new Requests_Response;

        $mockSuccessResponse->status_code = 200;
        $mockSuccessResponse->success = true;
        $mockSuccessResponse->body = json_encode($subscription->attributesToArray());

        $requestMock->expects($this->exactly(4))->method('request')->will($this->returnValue($mockSuccessResponse));

        $this->registerMockedClient($requestMock);

        $this->mockSession();

        $response = $this->doAuthPayment($paymentRequest);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        $subscription = $this->getLastEntity('subscription', true);

        $token = $this->getDbLastEntityPublic('token');

        $customer = $this->getDbLastEntityPublic('customer');

        // ------------

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);
        $paymentRequest['card']['number'] = '4000000000000002';
        $paymentRequest['subscription_card_change'] = 1;

        $this->mockSession();

        $response2 = $this->doAuthPayment($paymentRequest);

        $payment2 = $this->getEntityById('payment', $response2['razorpay_payment_id'], true);

        $subscription2 = $this->getLastEntity('subscription', true);

        $token2 = $this->getDbLastEntityPublic('token');

        $customer2 = $this->getEntityById('customer', '10000gcustomer', true);

        // ---------------

        $this->assertNotEquals($token['id'], $token2['id']);
        $this->assertEquals($token['customer_id'], $token2['customer_id']);
        $this->assertEquals('10000gcustomer', $token2['customer_id']);

        $this->assertArrayNotHasKey('token_id', $payment2);
        $this->assertEquals($token2['id'], 'token_' . $payment2['global_token_id']);
        $this->assertEquals($customer2['id'], 'cust_' . $payment2['global_customer_id']);
        $this->assertEquals($customer['id'], $payment2['customer_id']);
        $this->assertEquals($subscription['id'], $payment2['subscription_id']);
        $this->assertEquals($payment['customer_id'], $payment2['customer_id']);
        $this->assertEquals($payment['global_customer_id'], $payment2['global_customer_id']);
        $this->assertNull($payment2['invoice_id']);
        $this->assertEquals(500, $payment2['amount']);
        $this->assertEquals('authorized', $payment2['status']);
        $this->assertNotEquals($payment['global_token_id'], $payment2['global_token_id']);

        $this->assertEquals($customer2['id'], 'cust_' . $customer['global_customer_id']);

        Carbon::setTestNow();
    }

    public function testSubscriptionV2GlobalFlowDifferentCardsAndAuths()
    {
        // --- 1st 2FA with global token

        $subscription = $this->createSubscription(false, [], [], false, false, false);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription, 2000);

        $requestMock = $this->createMock(Requests_Session::class);

        $subscriptionEntity = $this->getDbLastEntity('subscription');
        $subscriptionEntity->setGlobalCustomer(true);

        $mockSuccessResponse = new Requests_Response;

        $mockSuccessResponse->status_code = 200;
        $mockSuccessResponse->success = true;
        $mockSuccessResponse->body = json_encode($subscriptionEntity->attributesToArray());

        $requestMock->expects($this->exactly(5))->method('request')->will($this->returnValue($mockSuccessResponse));

        $this->registerMockedClient($requestMock);

        $this->mockSession();

        $response = $this->doAuthPayment($paymentRequest);

        $subscription1 = $this->getLastEntity('subscription', true);

        $gatewayToken1 = $this->getLastEntity('gateway_token', true);

        // --- the above should have created a gateway token entity.
        // --- the second 2FA on this should go through successfully.
        //     a different gateway_token entity is created for this.

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);
        $paymentRequest['card']['number'] = '4111111111111111';

        $paymentRequest['subscription_card_change'] = 1;

        $response = $this->doAuthPayment($paymentRequest);

        $subscription2 = $this->getLastEntity('subscription', true);

        $gatewayToken2 = $this->getLastEntity('gateway_token', true);

        $payment = $this->getLastEntity('payment', true);

        $this->assertNotEquals($gatewayToken1['token_id'], $gatewayToken2['token_id']);

        $this->assertEquals('authorized', $payment['status']);

        // --- The above 2 2FAs would have created two different tokens.
        // --- Now, on the same token, attempt a normal payment. Not a subscription one.
        //     It should go through successfully without any issue.

        $paymentRequest = $this->getDefaultPaymentArray();
        $paymentRequest['save'] = 1;
        $paymentRequest['token'] = 'token_' . $gatewayToken2['token_id'];

        $paymentRequest['subscription_card_change'] = 1;

        $response = $this->doAuthPayment($paymentRequest);

        // --- the third 2FA should go through successfully.
        // --- but this 2FA is done with an existing recurring token only.
        // --- earlier, the logic was that if there's a second recurring
        //     on the same token, then it should be on private auth only.

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest(
            $subscription, null, 'token_' . $gatewayToken1['token_id']);
        unset($paymentRequest['card']);
        $paymentRequest['card'] = ['cvv' => 111];
        $paymentRequest['subscription_card_change'] = 1;

        // Doing this so that a different terminal is picked for this 2FA.
        $this->fixtures->terminal->disableTerminal();
        $this->doAuthPayment($paymentRequest);
        $this->fixtures->terminal->enableTerminal();

        $payment = $this->getLastEntity('payment', true);

        // Should go through 2FA.
        $this->assertEquals('passed', $payment['two_factor_auth']);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals($gatewayToken1['token_id'], $payment['global_token_id']);
        $this->assertEquals('3RecurringTerm', $payment['terminal_id']);
    }

    public function testSubscriptionV2LocalFlowDifferentCardsAndAuths()
    {
        // --- 1st 2FA with local token

        Carbon::setTestNow();

        $subscription = $this->createSubscription(
            false, [], ['customer_id' => 'cust_100000customer'], false, false, false);

        $requestMock = $this->createMock(Requests_Session::class);

        $subscriptionEntity = $this->getDbLastEntity('subscription');
        $subscriptionEntity->setGlobalCustomer(true);

        $mockSuccessResponse = new Requests_Response;

        $mockSuccessResponse->status_code = 200;
        $mockSuccessResponse->success = true;
        $mockSuccessResponse->body = json_encode($subscriptionEntity->attributesToArray());

        $requestMock->expects($this->exactly(3))->method('request')->will($this->returnValue($mockSuccessResponse));

        $this->registerMockedClient($requestMock);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription, 2000, 'token_100000custcard');

        $this->fixtures->base->editEntity('card', '100000000lcard', ["type" => 'credit']);

        $response = $this->doAuthPayment($paymentRequest);

        $subscription1 = $this->getLastEntity('subscription', true);

        $gatewayToken1 = $this->getLastEntity('gateway_token', true);

        // --- the above should have created a gateway token entity.
        // --- the second 2FA on this should go through successfully.
        //     a different gateway_token entity is created for this.

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription, null, 'token_100001custcard');

        $this->fixtures->base->editEntity('card', '100000001lcard', ['type' => 'credit']);
        $paymentRequest['subscription_card_change'] = 1;
        $response = $this->doAuthPayment($paymentRequest);

        $subscription2 = $this->getLastEntity('subscription', true);

        $gatewayToken2 = $this->getLastEntity('gateway_token', true);

        $payment = $this->getLastEntity('payment', true);

        $this->assertNotEquals($gatewayToken1['token_id'], $gatewayToken2['token_id']);

        $this->assertEquals('authorized', $payment['status']);

         // --- The above 2 2FAs would have created two different tokens.
        // --- Now, on the same token, attempt a normal payment. Not a subscription one.
        //     It should go through successfully without any issue.

        $paymentRequest = $this->getDefaultPaymentArray();
        $paymentRequest['save'] = 1;
        $paymentRequest['token'] = 'token_' . $gatewayToken2['token_id'];
        $paymentRequest['subscription_card_change'] = 1;

        $response = $this->doAuthPayment($paymentRequest);

        // --- the third 2FA should go through successfully.
        // --- but this 2FA is done with an existing recurring token only.
        // --- earlier, the logic was that if there's a second recurring
        //     on the same token, then it should be on private auth only.
        //

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest(
            $subscription, null, 'token_' . $gatewayToken1['token_id']);
        unset($paymentRequest['card']);
        $paymentRequest['card'] = ['cvv' => 111];

        // Doing this so that a different terminal is picked for this 2FA.
        $this->fixtures->terminal->disableTerminal();

        $paymentRequest['subscription_card_change'] = 1;
        $this->doAuthPayment($paymentRequest);

        $this->fixtures->terminal->enableTerminal();

        $payment = $this->getLastEntity('payment', true);

        // Should go through 2FA.
        $this->assertEquals('not_applicable', $payment['two_factor_auth']);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals('token_' . $gatewayToken1['token_id'], $payment['token_id']);
        $this->assertEquals('3RecurringTerm', $payment['terminal_id']);

        $gatewayTokens = $this->getEntities('gateway_token', [], true);

        // No new gateway token should be created. Use an existing one
        // and update its terminal.
        $this->assertEquals(2, $gatewayTokens['count']);

        $gatewayToken1Refreshed = $this->getEntityById('gateway_token', $gatewayToken1['id'], true);

        // Since we are using the same old token.
        $this->assertEquals($gatewayToken1['token_id'], $gatewayToken1Refreshed['token_id']);

        $this->assertNotEquals($gatewayToken1['terminal_id'], $gatewayToken1Refreshed['terminal_id']);

        $this->assertEquals('3RecurringTerm', $gatewayToken1Refreshed['terminal_id']);
    }

    protected function registerMockedClient($requestMock)
    {
        $this->app['module']->extend('subscription', function () use ($requestMock)
        {
            $mockedSubsriptionExternal = new Subscriptions\Mock\External($requestMock);

            return $mockedSubsriptionExternal;
        });
    }
}
