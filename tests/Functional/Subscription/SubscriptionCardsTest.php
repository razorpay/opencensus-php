<?php

namespace RZP\Tests\Functional\Subscription;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use Mockery;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Item;
use RZP\Models\Plan\Subscription\Addon;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Subscription\SubscriptionTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class SubscriptionCardsTest extends TestCase
{
    use PaymentTrait;
    use SubscriptionTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/SubscriptionTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['subscriptions']);

        $this->fixtures->create('terminal:shared_cybersource_hdfc_recurring_terminals');

        $this->gateway = 'cybersource';

        $this->mockTokenex();
    }

    // ----------------------- Preferences Start ----------------------------

    public function testPreferencesWithCustomerIdInInput()
    {
        $exceptionDetails = [
            'error' => [
                'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                'description' => 'customer_id should not be sent in the input for subscription payment'
            ],
            'exception' => [
                    'class'               => 'RZP\Exception\BadRequestException',
                    'internal_error_code' => ErrorCode::BAD_REQUEST_SUBSCRIPTION_CUSTOMER_ID_SENT_IN_INPUT,
            ],
            'status_code' => 400
        ];

        $response = $this->makePreferencesCall(['customer_id' => 'justCheckinBro'], $exceptionDetails);
    }

    public function testPreferencesWithLocalCustomerHasTokens()
    {
        $response = $this->makePreferencesCall();

        $subscription = $this->getLastEntity('subscription', true);

        $customer = $this->getEntityById('customer', $subscription['customer_id'], true);

        $this->assertGreaterThanOrEqual(1, $response['customer']['tokens']['count']);
        $this->assertEquals($customer['id'], $response['customer']['customer_id']);
        $this->assertEquals(2000, $response['subscription']['amount']);
        $this->assertTrue($response['options']['remember_customer']);
    }

    public function testPreferencesWithGlobalCustomerNoFlashCheckoutEnabled()
    {
        $this->fixtures->merchant->addFeatures(['noflashcheckout']);

        $exceptionDetails = [
            'error' => [
                'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                'description' => 'Subscription payment cannot be made with Flash Checkout disabled'
            ],
            'exception' => [
                'class'               => 'RZP\Exception\BadRequestException',
                'internal_error_code' => ErrorCode::BAD_REQUEST_SUBSCRIPTION_SAVE_CARD_DISABLED,
            ],
            'status_code' => 400
        ];

        $response = $this->makePreferencesCall([], $exceptionDetails, [], false);
    }

    public function testPreferencesWithGlobalCustomerHasTokens()
    {
        $response = $this->makePreferencesCall(['app_token' => 'capp_1000000custapp'], [], [], false);

        $this->assertGreaterThanOrEqual(1, $response['customer']['tokens']['count']);
        $this->assertArrayNotHasKey('customer_id', $response['customer']);
        $this->assertEquals(2000, $response['subscription']['amount']);
        $this->assertTrue($response['options']['remember_customer']);
    }

    public function testPreferencesCardChangeGlobalCustomer()
    {
        $localCustomer = $this->fixtures->create('customer', ['global_customer_id' => '10000gcustomer']);

        $response = $this->makePreferencesCall(
            [
                'app_token' => 'capp_1000000custapp',
            ],
            [],
            [
                'customer_id' => $localCustomer->getPublicId()
            ],
            false);

        $this->assertEquals(1, $response['customer']['tokens']['count']);
        $this->assertEquals('token_10000custgcard', $response['customer']['tokens']['items'][0]['id']);
        $this->assertEquals(2000, $response['subscription']['amount']);
    }

    public function testPreferencesCardChangeGlobalCustomerNoAppToken()
    {
        $localCustomer = $this->fixtures->create('customer', ['global_customer_id' => '10000gcustomer']);

        $response = $this->makePreferencesCall(
            [],
            [],
            [
                'customer_id' => $localCustomer->getPublicId()
            ],
            false);

        $this->assertArrayNotHasKey('customer', $response);
        $this->assertEquals(2000, $response['subscription']['amount']);
        $this->assertTrue($response['options']['remember_customer']);
    }

    // ----------------------- Preferences End ----------------------------

    // ----------------------- Payment Flow Start ----------------------------

    public function testPaymentCustomerIdInInput()
    {
        $subscription = $this->createSubscription(true);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);
        $paymentRequest['customer_id'] = 'cust_abfghijklmn111';

        try
        {
            $this->doAuthPayment($paymentRequest);
        }
        catch (BadRequestValidationFailureException $ex)
        {
            $this->assertEquals('customer_id is not required and should not be sent', $ex->getMessage());

            return;
        }

        $this->assertTrue(false);
    }

    public function testPaymentFirst2FaLocalSavedCard()
    {
        $subscription = $this->createSubscription(
            true, [], ['customer_id' => 'cust_100000customer'], false, false, false);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription, null, 'token_100000custcard');

        $this->fixtures->base->editEntity('card', '100000000lcard', ["type" => 'credit']);

        $response = $this->doAuthPayment($paymentRequest);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        $subscription = $this->getLastEntity('subscription', true);

        $this->assertEquals($subscription['id'], $payment['subscription_id']);
        $this->assertEquals('token_100000custcard', $payment['token_id']);
        $this->assertNull($payment['global_token_id']);
        $this->assertEquals('cust_100000customer', $payment['customer_id']);
        $this->assertNull($payment['global_customer_id']);
        $this->assertEquals('100000custcard', $subscription['token_id']);
    }

    public function testPaymentFirst2FaLocalNewCard()
    {
        $subscription = $this->createSubscription(
            true, [], ['customer_id' => 'cust_100000customer'], false, false, false);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);

        $response = $this->doAuthPayment($paymentRequest);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        $subscription = $this->getLastEntity('subscription', true);

        $token = $this->getLastEntity('token', true);

        $this->assertEquals($subscription['id'], $payment['subscription_id']);
        $this->assertNull($payment['global_token_id']);
        $this->assertEquals($token['id'], $payment['token_id']);
        $this->assertEquals('cust_100000customer', $payment['customer_id']);
        $this->assertNull($payment['global_customer_id']);
        $this->assertEquals($token['id'], 'token_' . $subscription['token_id']);
    }

    public function testPaymentSecond2FaLocalSavedCard()
    {
        $subscription = $this->createSubscription(
            false, [], ['customer_id' => 'cust_100000customer'], false, false, false);

        // The item amount is 2k. It's modified while creating plan via fixtures.
        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription, 2000);

        $response = $this->doAuthPayment($paymentRequest);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        $subscription = $this->getLastEntity('subscription', true);

        $token = $this->getLastEntity('token', true);

        // ------------

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription, null, 'token_100000custcard');
        $this->fixtures->base->editEntity('card', '100000000lcard', ["type" => 'credit']);

        $response2 = $this->doAuthPayment($paymentRequest);

        $payment2 = $this->getEntityById('payment', $response2['razorpay_payment_id'], true);

        $subscription2 = $this->getLastEntity('subscription', true);

        $token2 = 'token_100000custcard';

        // ---------------

        $this->assertNotEquals($token['id'], $payment2['token_id']);
        $this->assertNotEquals($payment['id'], $payment2['id']);
        $this->assertEquals($subscription['id'], $subscription2['id']);

        $this->assertEquals($token2, $payment2['token_id']);
        $this->assertEquals($token2, 'token_' . $subscription2['token_id']);

        $this->assertEquals($subscription['customer_id'], $subscription2['customer_id']);
        $this->assertEquals($payment['customer_id'], $payment2['customer_id']);

        $this->assertNull($payment2['global_token_id']);
        $this->assertEquals(500, $payment2['amount']);
        $this->assertEquals('refunded', $payment2['status']);
        $this->assertEquals($subscription['id'], $payment2['subscription_id']);

        $this->assertEquals(1, $subscription2['paid_count']);
        $this->assertEquals('active', $subscription2['status']);
    }

    public function testPaymentSecond2FaLocalNewCard()
    {
        $subscription = $this->createSubscription(
            false, [], ['customer_id' => 'cust_100000customer'], false, false, false);

        // The item amount is 2k. It's modified while creating plan via fixtures.
        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription, 2000);

        $response = $this->doAuthPayment($paymentRequest);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        $subscription = $this->getLastEntity('subscription', true);

        $token = $this->getLastEntity('token', true);

        // ------------

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);
        $paymentRequest['card']['number'] = '4000000000000002';

        $response2 = $this->doAuthPayment($paymentRequest);

        $payment2 = $this->getEntityById('payment', $response2['razorpay_payment_id'], true);

        $subscription2 = $this->getLastEntity('subscription', true);

        $token2 = $this->getLastEntity('token', true);

        // ---------------

        $this->assertNotEquals($token['id'], $token2['id']);
        $this->assertNotEquals($payment['id'], $payment2['id']);
        $this->assertEquals($subscription['id'], $subscription2['id']);

        $this->assertEquals($token2['id'], $payment2['token_id']);
        $this->assertEquals($token2['id'], 'token_' . $subscription2['token_id']);

        $this->assertEquals($subscription['customer_id'], $subscription2['customer_id']);
        $this->assertEquals($payment['customer_id'], $payment2['customer_id']);

        $this->assertNull($payment2['global_token_id']);
        $this->assertEquals(500, $payment2['amount']);
        $this->assertEquals('refunded', $payment2['status']);
        $this->assertEquals($subscription['id'], $payment2['subscription_id']);

        $this->assertEquals(1, $subscription2['paid_count']);
        $this->assertEquals('active', $subscription2['status']);
    }

    public function testPaymentChargeLocalSavedCard()
    {
        $subscription = $this->createSubscription(
            false, [], ['customer_id' => 'cust_100000customer'], false, false, false);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription, 2000, 'token_100000custcard');

        $this->fixtures->base->editEntity('card', '100000000lcard', ["type" => 'credit']);

        $response = $this->doAuthPayment($paymentRequest);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        $subscription = $this->getLastEntity('subscription', true);

        $token = $this->getEntityById('token', 'token_100000custcard', true);

        $this->assertEquals($token['id'], $payment['token_id']);
        $this->assertNull($payment['global_token_id']);
        $this->assertEquals(true, $token['recurring']);

        // --------------------------------------------------------------------

        $chargeAt = Carbon::createFromTimestamp($subscription['charge_at'] + 1, Timezone::IST);

        Carbon::setTestNow($chargeAt);

        $result = $this->makeSubscriptionChargeCronRequest();

        $this->assertEquals(1, $result['total']);

        $payment2 = $this->getLastEntity('payment', true);
        $subscription2 = $this->getLastEntity('subscription', true);

        $this->assertNotEquals($payment['id'], $payment2['id']);
        $this->assertEquals($payment['token_id'], $payment2['token_id']);
        $this->assertNull($payment2['global_token_id']);
        $this->assertEquals($payment['customer_id'], $payment2['customer_id']);
        $this->assertEquals($payment['global_customer_id'], $payment2['global_customer_id']);
        $this->assertEquals($token['id'], $payment['token_id']);
        $this->assertNull($payment2['app_token']);
        $this->assertEquals($subscription2['id'], $payment['subscription_id']);
        $this->assertEquals($payment['card_id'], $payment2['card_id']);

        $this->assertEquals($subscription['token_id'], $subscription2['token_id']);
        $this->assertEquals($subscription['customer_id'], $subscription2['customer_id']);
        $this->assertEquals('cust_100000customer', $subscription['customer_id']);
        $this->assertEquals(2, $subscription2['paid_count']);

        Carbon::setTestNow();
    }

    public function testPaymentFirst2FaGlobalSavedCard()
    {
        $subscription = $this->createSubscription(true, [], [], false, false, false);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription, null, 'token_10000custgcard');
        unset($paymentRequest['card']);
        $paymentRequest['card'] = ['cvv' => 111];

        $this->mockSession();

        // $this->fixtures->base->editEntity('card', '100000000gcard', ["type" => 'credit']);

        $response = $this->doAuthPayment($paymentRequest);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        $subscription = $this->getLastEntity('subscription', true);

        $globalToken = $this->getEntityById('token', 'token_10000custgcard', true);

        $customer = $this->getLastEntity('customer', true);
        $globalCust = $this->getEntityById('customer', '10000gcustomer', true);

        $this->assertEquals($subscription['id'], $payment['subscription_id']);
        $this->assertEquals($globalToken['id'], 'token_' . $payment['global_token_id']);
        $this->assertArrayNotHasKey('token_id', $payment);
        $this->assertEquals($customer['id'], $payment['customer_id']);
        $this->assertEquals($globalCust['id'], 'cust_' . $payment['global_customer_id']);

        $this->assertEquals($globalToken['id'], 'token_' . $subscription['token_id']);
        $this->assertEquals($customer['id'], $subscription['customer_id']);

        $this->assertEquals($globalCust['id'], 'cust_' . $customer['global_customer_id']);
        $this->assertEquals($globalCust['email'], $customer['email']);
        $this->assertEquals($globalCust['contact'], $customer['contact']);

        Carbon::setTestNow();
    }

    public function testPaymentFirst2FaGlobalNewCard()
    {
        $subscription = $this->createSubscription(true, [], [], false, false, false);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);

        $this->mockSession();

        $response = $this->doAuthPayment($paymentRequest);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        $subscription = $this->getLastEntity('subscription', true);

        $token = $this->getLastEntity('token', true);

        $customer = $this->getLastEntity('customer', true);
        $globalCust = $this->getEntityById('customer', '10000gcustomer', true);

        $this->assertEquals($subscription['id'], $payment['subscription_id']);
        $this->assertEquals($token['id'], 'token_' . $payment['global_token_id']);
        $this->assertArrayNotHasKey('token_id', $payment);
        $this->assertEquals($customer['id'], $payment['customer_id']);
        $this->assertEquals($globalCust['id'], 'cust_' . $payment['global_customer_id']);
        $this->assertEquals($token['id'], 'token_' . $subscription['token_id']);

        $this->assertEquals($globalCust['id'], 'cust_' . $customer['global_customer_id']);
        $this->assertEquals($globalCust['email'], $customer['email']);
        $this->assertEquals($globalCust['contact'], $customer['contact']);

        Carbon::setTestNow();
    }

    public function testPaymentSecond2FaGlobalSavedCard()
    {
        $subscription = $this->createSubscription(false, [], [], false, false, false);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription, 2000);

        $this->mockSession();

        $response = $this->doAuthPayment($paymentRequest);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        $subscription = $this->getLastEntity('subscription', true);

        $token = $this->getLastEntity('token', true);

        // ------------

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription, null, 'token_10000custgcard');
        unset($paymentRequest['card']);
        $paymentRequest['card'] = ['cvv' => 111];

        $this->mockSession();

        $response2 = $this->doAuthPayment($paymentRequest);

        $payment2 = $this->getEntityById('payment', $response2['razorpay_payment_id'], true);

        $subscription2 = $this->getLastEntity('subscription', true);

        $token2 = $this->getEntityById('token', 'token_10000custgcard', true);

        $customer = $this->getLastEntity('customer', true);
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
        $this->assertEquals('refunded', $payment2['status']);

        $this->assertEquals($token2['id'], 'token_' . $subscription2['token_id']);
        $this->assertEquals($customer['id'], $subscription2['customer_id']);
        $this->assertEquals($customer['id'], $subscription['customer_id']);
        $this->assertEquals(1, $subscription2['paid_count']);
        $this->assertEquals('active', $subscription2['status']);

        Carbon::setTestNow();
    }

    public function testPaymentSecond2FaGlobalNewCard()
    {
        $subscription = $this->createSubscription(false, [], [], false, false, false);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription, 2000);

        $this->mockSession();

        $response = $this->doAuthPayment($paymentRequest);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        $subscription = $this->getLastEntity('subscription', true);

        $token = $this->getLastEntity('token', true);

        $customer = $this->getLastEntity('customer', true);

        // ------------

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);
        $paymentRequest['card']['number'] = '4000000000000002';

        $this->mockSession();

        $response2 = $this->doAuthPayment($paymentRequest);

        $payment2 = $this->getEntityById('payment', $response2['razorpay_payment_id'], true);

        $subscription2 = $this->getLastEntity('subscription', true);

        $token2 = $this->getLastEntity('token', true);

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
        $this->assertEquals('refunded', $payment2['status']);
        $this->assertNotEquals($payment['global_token_id'], $payment2['global_token_id']);

        $this->assertEquals($token2['id'], 'token_' . $subscription2['token_id']);
        $this->assertEquals($customer['id'], $subscription2['customer_id']);
        $this->assertEquals(1, $subscription2['paid_count']);
        $this->assertEquals('active', $subscription2['status']);

        $this->assertEquals($customer2['id'], 'cust_' . $customer['global_customer_id']);

        Carbon::setTestNow();
    }

    public function testPaymentChargeGlobalSavedCard()
    {
        $subscription = $this->createSubscription(false, [], [], false, false, false);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription, 2000);

        $this->mockSession();

        $response = $this->doAuthPayment($paymentRequest);

        $payment = $this->getEntityById('payment', $response['razorpay_payment_id'], true);

        $subscription = $this->getLastEntity('subscription', true);

        $token = $this->getLastEntity('token', true);

        $customer = $this->getLastEntity('customer', true);

        $this->assertEquals($token['id'], 'token_' . $payment['global_token_id']);
        $this->assertEquals(true, $token['recurring']);

        // --------------------------------------------------------------------

        $chargeAt = Carbon::createFromTimestamp($subscription['charge_at'] + 1, Timezone::IST);

        Carbon::setTestNow($chargeAt);

        $this->flushSession();

        $result = $this->makeSubscriptionChargeCronRequest();

        $this->assertEquals(1, $result['total']);

        $payment2 = $this->getLastEntity('payment', true);
        $subscription2 = $this->getLastEntity('subscription', true);

        $this->assertNotEquals($payment['id'], $payment2['id']);
        $this->assertEquals($payment['global_token_id'], $payment2['global_token_id']);
        $this->assertArrayNotHasKey('token_id', $payment2);
        $this->assertEquals($payment['customer_id'], $payment2['customer_id']);
        $this->assertEquals($payment['global_customer_id'], $payment2['global_customer_id']);
        $this->assertEquals($token['id'], 'token_' . $payment['global_token_id']);
        $this->assertNull($payment2['app_token']);
        $this->assertEquals($subscription2['id'], $payment['subscription_id']);
        // In case of global, we always create a new duplicate local token and card.
        $this->assertNotEquals($payment['card_id'], $payment2['card_id']);

        $this->assertEquals($subscription['token_id'], $subscription2['token_id']);
        $this->assertEquals($subscription['customer_id'], $subscription2['customer_id']);
        $this->assertEquals($customer['id'], $subscription['customer_id']);
        $this->assertEquals(2, $subscription2['paid_count']);

        Carbon::setTestNow();
    }

    public function testPaymentFirst2FaGlobalNoAppToken()
    {
        $subscription = $this->createSubscription(true, [], [], false, false, false);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);

        try
        {
            $this->doAuthPayment($paymentRequest);
        }
        catch (BadRequestException $ex)
        {
            $this->assertEquals(ErrorCode::BAD_REQUEST_SUBSCRIPTION_PAYMENT_WITHOUT_SAVING, $ex->getCode());

            return;
        }

        $this->assertTrue(false);
    }

    public function testPaymentSecond2FaGlobalNoAppToken()
    {
        $subscription = $this->createSubscription(false, [], [], false, false, false);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription, 2000);

        $this->mockSession();

        $response = $this->doAuthPayment($paymentRequest);

        $this->flushSession();

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);
        $paymentRequest['card']['number'] = '4000000000000002';

        try
        {
            $this->doAuthPayment($paymentRequest);
        }
        catch (BadRequestException $ex)
        {
            $this->assertEquals(ErrorCode::BAD_REQUEST_APP_TOKEN_ABSENT, $ex->getCode());

            return;
        }

        $this->assertTrue(false);
    }

    public function testGlobalFlowDifferentCardsAndAuths()
    {
        // --- 1st 2FA with global token

        $subscription = $this->createSubscription(false, [], [], false, false, false);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription, 2000);

        $this->mockSession();

        $response = $this->doAuthPayment($paymentRequest);

        $subscription1 = $this->getLastEntity('subscription', true);

        $gatewayToken1 = $this->getLastEntity('gateway_token', true);

        $this->assertEquals($subscription1['token_id'], $gatewayToken1['token_id']);

        // --- the above should have created a gateway token entity.
        // --- the second 2FA on this should go through successfully.
        //     a different gateway_token entity is created for this.

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);
        $paymentRequest['card']['number'] = '4111111111111111';

        $response = $this->doAuthPayment($paymentRequest);

        $subscription2 = $this->getLastEntity('subscription', true);

        $gatewayToken2 = $this->getLastEntity('gateway_token', true);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($subscription2['token_id'], $gatewayToken2['token_id']);

        $this->assertNotEquals($gatewayToken1['token_id'], $gatewayToken2['token_id']);

        $this->assertEquals('refunded', $payment['status']);

        // --- The above 2 2FAs would have created two different tokens.
        // --- Now, on the same token, attempt a normal payment. Not a subscription one.
        //     It should go through successfully without any issue.

        $paymentRequest = $this->getDefaultPaymentArray();
        $paymentRequest['save'] = 1;
        $paymentRequest['token'] = 'token_' . $gatewayToken2['token_id'];

        $response = $this->doAuthPayment($paymentRequest);

        // --- the third 2FA should go through successfully.
        // --- but this 2FA is done with an existing recurring token only.
        // --- earlier, the logic was that if there's a second recurring
        //     on the same token, then it should be on private auth only.

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest(
            $subscription, null, 'token_' . $gatewayToken1['token_id']);
        unset($paymentRequest['card']);
        $paymentRequest['card'] = ['cvv' => 111];

        // Doing this so that a different terminal is picked for this 2FA.
        $this->fixtures->terminal->disableTerminal();
        $this->doAuthPayment($paymentRequest);
        $this->fixtures->terminal->enableTerminal();

        $payment = $this->getLastEntity('payment', true);

        // Should go through 2FA.
        $this->assertEquals('passed', $payment['two_factor_auth']);
        $this->assertEquals('refunded', $payment['status']);
        $this->assertEquals($gatewayToken1['token_id'], $payment['global_token_id']);
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

        // --- a subsequent charge on the subscription's current token is
        //     made on private auth. This should also be successful without
        //     any issue.

        $subscription = $this->getLastEntity('subscription', true);

        $chargeAt = Carbon::createFromTimestamp($subscription['charge_at'] + 1, Timezone::IST);

        Carbon::setTestNow($chargeAt);

        $this->flushSession();

        $result = $this->makeSubscriptionChargeCronRequest();

        $this->assertEquals(1, $result['total']);

        $subscription = $this->getLastEntity('subscription', true);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('skipped', $payment['two_factor_auth']);

        $this->assertEquals(2, $subscription['paid_count']);
        $this->assertEquals('active', $subscription['status']);
        // Different token is associated with the subscription now.
        $this->assertEquals($gatewayToken1Refreshed['token_id'], $subscription['token_id']);
    }

    public function testLocalFlowDifferentCardsAndAuths()
    {
        // --- 1st 2FA with local token

        $subscription = $this->createSubscription(
            false, [], ['customer_id' => 'cust_100000customer'], false, false, false);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription, 2000, 'token_100000custcard');

        $this->fixtures->base->editEntity('card', '100000000lcard', ["type" => 'credit']);

        $response = $this->doAuthPayment($paymentRequest);

        $subscription1 = $this->getLastEntity('subscription', true);

        $gatewayToken1 = $this->getLastEntity('gateway_token', true);

        $this->assertEquals($subscription1['token_id'], $gatewayToken1['token_id']);

        // --- the above should have created a gateway token entity.
        // --- the second 2FA on this should go through successfully.
        //     a different gateway_token entity is created for this.

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription, null, 'token_100001custcard');

        $this->fixtures->base->editEntity('card', '100000001lcard', ["type" => 'credit']);

        $response = $this->doAuthPayment($paymentRequest);

        $subscription2 = $this->getLastEntity('subscription', true);

        $gatewayToken2 = $this->getLastEntity('gateway_token', true);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($subscription2['token_id'], $gatewayToken2['token_id']);

        $this->assertNotEquals($gatewayToken1['token_id'], $gatewayToken2['token_id']);

        $this->assertEquals('refunded', $payment['status']);

        // --- The above 2 2FAs would have created two different tokens.
        // --- Now, on the same token, attempt a normal payment. Not a subscription one.
        //     It should go through successfully without any issue.

        $paymentRequest = $this->getDefaultPaymentArray();
        $paymentRequest['save'] = 1;
        $paymentRequest['token'] = 'token_' . $gatewayToken2['token_id'];

        $response = $this->doAuthPayment($paymentRequest);

        // --- the third 2FA should go through successfully.
        // --- but this 2FA is done with an existing recurring token only.
        // --- earlier, the logic was that if there's a second recurring
        //     on the same token, then it should be on private auth only.

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest(
            $subscription, null, 'token_' . $gatewayToken1['token_id']);
        unset($paymentRequest['card']);
        $paymentRequest['card'] = ['cvv' => 111];

        // Doing this so that a different terminal is picked for this 2FA.
        $this->fixtures->terminal->disableTerminal();
        $this->doAuthPayment($paymentRequest);
        $this->fixtures->terminal->enableTerminal();

        $payment = $this->getLastEntity('payment', true);

        // Should go through 2FA.
        $this->assertEquals('not_applicable', $payment['two_factor_auth']);
        $this->assertEquals('refunded', $payment['status']);
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

        // --- a subsequent charge on the subscription's current token is
        //     made on private auth. This should also be successful without
        //     any issue.

        $subscription = $this->getLastEntity('subscription', true);

        $chargeAt = Carbon::createFromTimestamp($subscription['charge_at'] + 1, Timezone::IST);

        Carbon::setTestNow($chargeAt);

        $this->flushSession();

        $result = $this->makeSubscriptionChargeCronRequest();

        $this->assertEquals(1, $result['total']);

        $subscription = $this->getLastEntity('subscription', true);

        $this->assertEquals(2, $subscription['paid_count']);
        $this->assertEquals('active', $subscription['status']);
        $this->assertEquals($gatewayToken1['token_id'], $subscription['token_id']);
    }

    public function testPaymentFail2FaGlobalSavedCard()
    {
        $subscription = $this->createSubscription(false, [], [], false, false, false);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription, 2000);

        $this->mockSession();

        $this->failOnCapture();

        $this->doAuthPayment($paymentRequest);

        $subscription = $this->getLastEntity('subscription', true);
        $payment = $this->getLastEntity('payment', true);

        $this->assertNull($subscription['token_id']);
        $this->assertNull($subscription['customer_id']);
        $this->assertNull($subscription['start_at']);
        $this->assertEquals('created', $subscription['status']);
    }

    // ----------------------- Payment Flow End ----------------------------

    protected function makePreferencesCall(
        $requestParams = [], $exceptionDetails = [], $subscriptionRequestParams = [], $createCustomer = true)
    {
        $subscription = $this->createSubscription(false, [], $subscriptionRequestParams, false, false, $createCustomer);

        $this->ba->publicAuth();

        $this->fixtures->merchant->activate('10000000000000');

        $requestContent = $this->testData[__FUNCTION__];

        $requestContent['request']['content']['subscription_id'] = $subscription['id'];

        if (empty($requestParams) === false)
        {
            $requestContent['request']['content'] = array_merge(
                $requestContent['request']['content'], $requestParams);
        }

        if (empty($exceptionDetails) === false)
        {
            $requestContent['response']['content']['error'] = $exceptionDetails['error'];
            $requestContent['response']['status_code'] = $exceptionDetails['status_code'];
            $requestContent['exception'] = $exceptionDetails['exception'];
        }

        $response = $this->startTest($requestContent);

        return $response;
    }

    protected function mockSession($appToken = 'capp_1000000custapp')
    {
        $data = [ 'test_app_token' => $appToken ];

        $this->session($data);
    }

    protected function resetSession()
    {
        $this->flushSession();
    }
}
