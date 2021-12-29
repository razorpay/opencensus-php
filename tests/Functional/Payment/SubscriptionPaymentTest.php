<?php

namespace Functional\Payment;

use RZP\Modules;
use RZP\Models\Offer;
use RZP\Models\Order;
use RZP\Models\Invoice;
use RZP\Models\Customer;
use RZP\Constants\Entity;
use RZP\Models\Customer\Token;
use RZP\Models\Plan\Subscription;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\FeeBearer;
use RZP\Modules\Subscriptions\Mock;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Exception\BadRequestException;
use RZP\Models\Feature\Constants as Feature;
use RZP\Models\Payment\Method as PaymentMethod;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\UpiMandate\Frequency as UPIMandateFrequency;

class SubscriptionPaymentTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected $subscriptionMock;

    /**
     * @var Subscription\Entity
     */
    protected $subscription;

    /**
     * @var Customer\Entity
     */
    protected $customer;

    /**
     * @var array
     */
    protected $cardPayment;

    /**
     * @var array
     */
    protected $upiPayment;

    /**
     * @var array
     */
    protected $eMandatePayment;

    /**
     * @var Offer\Entity
     */
    protected $offer;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/SubscriptionPaymentTestData.php';

        parent::setUp();

        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures(['subscriptions']);

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->mockOffer();

        $this->subscriptionMock = $this->mockSubscription();

        $this->cardPayment = array_merge($this->getDefaultRecurringPaymentArray(), [
            'amount' => 99900,
            'subscription_id' => 'sub_FVOmqpnh3WyQ09',
        ]);
        unset($this->cardPayment['customer_id']);

        $this->upiPayment = array_merge($this->getDefaultUpiRecurringPaymentArray(), [
            'amount' => 99900,
            'subscription_id' => 'sub_FVOmqpnh3WyQ09',
        ]);
        unset($this->upiPayment['customer_id']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        // -- Add eMandate payment details BEGINS---
        $this->eMandatePayment = array_merge($this->getEmandatePaymentArray('HDFC', 'netbanking', 0), [
            'amount'          => 0,
            'subscription_id' => 'sub_FVOmqpnh3WyQ09',
        ]);

        unset($this->eMandatePayment['customer_id']);

        $this->eMandatePayment['bank_account'] = [
            'account_number' => '914010009305862',
            'ifsc'           => 'HDFC0000123',
            'name'           => 'Test account',
            'account_type'   => 'savings',
        ];

        $this->fixtures->merchant->enableMethod('10000000000000', 'emandate');
        // -- Add eMandate payment details END---
    }

    public function testCreateInitialPaymentCard()
    {
        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $this->cardPayment,
        ];

        $this->ba->publicAuth();

        $this->makeRequestAndGetContent($request);

        $payment = $this->getDbLastEntity(Entity::PAYMENT);

        $this->assertEquals($this->subscription->getId(), $payment->getSubscriptionId());
        $this->assertTrue($payment->isAuthorized());
        $this->assertFalse(empty($payment->getTokenId()));
        $this->assertFalse(empty($payment->getCardId()));
        $this->assertEquals($this->subscription->getCustomerId(), $payment->customer_id);
        $this->assertTrue($payment->isRecurringTypeInitial());

        $token = $this->getDbLastEntity(Entity::TOKEN);

        $this->assertEquals($payment->getTokenId(), $token->getId());
        $this->assertTrue($token->isCard());
        $this->assertEquals($this->subscription->getCustomerId(), $token->customer_id);
        $this->assertEquals($this->subscription->getId(), $token->getEntityId());
        $this->assertEquals(Entity::SUBSCRIPTION, $token->getEntityType());
        $this->assertEquals($payment->getCardId(), $token->getCardId());
        $this->assertEquals(Token\RecurringStatus::CONFIRMED, $token->getRecurringStatus());
        $this->assertTrue($token->isRecurring());
    }

    public function testAutoPaymentCardInternational()
    {
        $this->fixtures->iin->create([
            'iin'       => '555555',
            'country'   => 'US',
            'type'      => 'credit',
            'recurring' => 1,
        ]);

        $payment = $this->cardPayment;
        $payment['card']['number'] = '5555555555554444';
        $payment['currency'] = 'USD';

        $payment['order_id'] = $this->fixtures->create(
            'order',
            ['amount' => $this->cardPayment['amount'],
                'currency' => 'USD'])->getPublicId();

        $this->ba->publicAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $payment,
        ];

        $this->makeRequestAndGetContent($request);

        $token = $this->getDbLastEntity(Entity::TOKEN);

        $this->subscription->setStatus(Subscription\Status::AUTHENTICATED);
        $this->subscription->recurring_type = 'auto';
        $this->subscription->global_customer = true;
        $this->subscription->status = 'halted';

        $this->ba->subscriptionsAuth();

        $order = $this->fixtures->create(
            'order',
            ['amount' => $this->cardPayment['amount'],
                'currency' => 'USD']);

        $paymentArray = array_merge($this->cardPayment, [
            'token' => $token->getPublicId(),
            'order_id' => $order->getPublicId(),
            'currency' => 'USD'
        ]);

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/subscriptions',
            'content' => $paymentArray,
        ];

        $this->makeRequestAndGetContent($request);

        $payment = $this->getDbLastEntity(Entity::PAYMENT);

        $this->assertEquals($this->subscription->getId(), $payment->getSubscriptionId());
        $this->assertTrue($payment->isAuthorized());
        $this->assertFalse(empty($payment->getTokenId()));
        $this->assertFalse(empty($payment->getCardId()));
        $this->assertEquals($this->customer->getId(), $payment->customer_id);
        $this->assertEquals($payment->getTokenId(), $token->getId());
        $this->assertTrue($payment->isRecurringTypeAuto());
    }

    public function testFetchPaymentWithSubscriptionEmailAndContactNotNull()
    {
        $request = [
            'method'  => 'GET',
            'url'     => '/payments/data_fix/subscriptions/GTSXI0raxv1G2U',
            'content' => [],
        ];

        $this->ba->subscriptionsAppAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($response, []);
    }

    public function testAutoPaymentCard()
    {
        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $this->cardPayment,
        ];

        $this->ba->publicAuth();

        $this->makeRequestAndGetContent($request);

        $token = $this->getDbLastEntity(Entity::TOKEN);

        $this->subscription->setStatus(Subscription\Status::AUTHENTICATED);
        $this->subscription->recurring_type = 'auto';

        $this->ba->subscriptionsAuth();

        $order = $this->fixtures->create(
            'order',
            ['amount' => $this->cardPayment['amount']]);

        $paymentArray = array_merge($this->cardPayment, [
            'token' => $token->getPublicId(),
            'order_id' => $order->getPublicId(),
        ]);

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/subscriptions',
            'content' => $paymentArray,
        ];

        $this->makeRequestAndGetContent($request);

        $payment = $this->getDbLastEntity(Entity::PAYMENT);

        $this->assertEquals($this->subscription->getId(), $payment->getSubscriptionId());
        $this->assertTrue($payment->isAuthorized());
        $this->assertFalse(empty($payment->getTokenId()));
        $this->assertFalse(empty($payment->getCardId()));
        $this->assertEquals($this->customer->getId(), $payment->customer_id);
        $this->assertEquals($payment->getTokenId(), $token->getId());
        $this->assertTrue($payment->isRecurringTypeAuto());
    }

    public function testCardChangePaymentCardWithoutOtp()
    {
        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $this->cardPayment,
        ];

        $this->ba->publicAuth();

        $content = $this->makeRequestAndGetContent($request);

        $this->capturePayment($content['razorpay_payment_id'], $this->cardPayment['amount']);

        $this->subscription->setStatus(Subscription\Status::AUTHENTICATED);

        $paymentArray = array_merge($this->cardPayment, [
            'amount' => 500,
            'subscription_card_change' => true,
        ]);
        $paymentArray['card']['number'] = '41476700000006';

        $this->ba->publicAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $paymentArray,
        ];

        try
        {
            $this->makeRequestAndGetContent($request);
        }
        catch (BadRequestException $e)
        {
            $this->assertEquals('BAD_REQUEST_APP_TOKEN_ABSENT', $e->getCode());
        }
    }

    public function testCreateInitialPaymentUpi()
    {
        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $this->upiPayment,
        ];

        $this->ba->publicAuth();

        $this->makeRequestAndGetContent($request);

        $payment = $this->getDbLastEntity(Entity::PAYMENT);

        $this->assertEquals($this->subscription->getId(), $payment->getSubscriptionId());
        $this->assertTrue($payment->isAuthorized());
        $this->assertFalse(empty($payment->getTokenId()));
        $this->assertEquals($this->subscription->getCustomerId(), $payment->customer_id);
        $this->assertTrue($payment->isRecurringTypeInitial());

        $token = $this->getDbLastEntity(Entity::TOKEN);

        $this->assertEquals($payment->getTokenId(), $token->getId());
        $this->assertTrue($token->isUpiRecurringToken());
        $this->assertEquals($this->subscription->getCustomerId(), $token->customer_id);
        $this->assertEquals($this->subscription->getId(), $token->getEntityId());
        $this->assertEquals(Entity::SUBSCRIPTION, $token->getEntityType());
        $this->assertEquals(Token\RecurringStatus::CONFIRMED, $token->getRecurringStatus());

        $this->assertEquals($this->upiPayment['vpa'], $token->vpa->getAddress());
        $this->assertEquals($this->upiPayment['vpa'], $payment->getVpa());

        $upiMandate = $this->getDbLastEntity(Entity::UPI_MANDATE);

        $this->assertEquals(UPIMandateFrequency::AS_PRESENTED, $upiMandate->getFrequency());
    }

    public function testAutoPaymentUpi()
    {
        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $this->upiPayment,
        ];

        $this->ba->publicAuth();

        $this->makeRequestAndGetContent($request);

        $token = $this->getDbLastEntity(Entity::TOKEN);

        $this->subscription->setStatus(Subscription\Status::AUTHENTICATED);
        $this->subscription->recurring_type = 'auto';

        $this->ba->subscriptionsAuth();

        $order = $this->fixtures->create(
            'order',
            ['amount' => $this->upiPayment['amount']]);

        $paymentArray = array_merge($this->upiPayment, [
            'token' => $token->getPublicId(),
            'order_id' => $order->getPublicId(),
        ]);

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/subscriptions',
            'content' => $paymentArray,
        ];

        $this->makeRequestAndGetContent($request);

        $payment = $this->getDbLastEntity(Entity::PAYMENT);

        $this->assertEquals($this->subscription->getId(), $payment->getSubscriptionId());
        $this->assertFalse(empty($payment->getTokenId()));
        $this->assertEquals($this->customer->getId(), $payment->customer_id);
        $this->assertEquals($payment->getTokenId(), $token->getId());
        $this->assertTrue($payment->isRecurringTypeAuto());
        $this->assertFalse($payment->isFailed());
        $this->assertEquals($this->upiPayment['vpa'], $payment->getVpa());
    }

    public function testCreateInitialPaymentWithoutCustomer()
    {
        $this->subscription->customer_id = null;

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $this->cardPayment,
        ];

        $this->ba->publicAuth();

        $this->makeRequestAndGetContent($request);

        $payment = $this->getDbLastEntity(Entity::PAYMENT);

        $this->assertEquals($this->subscription->getId(), $payment->getSubscriptionId());
        $this->assertTrue($payment->isAuthorized());
        $this->assertFalse(empty($payment->getTokenId()));
        $this->assertFalse(empty($payment->getCardId()));
        $this->assertTrue(empty($payment->customer_id));
        $this->assertTrue($payment->isRecurringTypeInitial());

        $token = $this->getDbLastEntity(Entity::TOKEN);

        $this->assertEquals($payment->getTokenId(), $token->getId());
        $this->assertTrue($token->isCard());
        $this->assertTrue(empty($token->getCustomerId()));
        $this->assertEquals($this->subscription->getId(), $token->getEntityId());
        $this->assertEquals(Entity::SUBSCRIPTION, $token->getEntityType());
        $this->assertEquals($payment->getCardId(), $token->getCardId());
        $this->assertEquals(Token\RecurringStatus::CONFIRMED, $token->getRecurringStatus());
        $this->assertTrue($token->isRecurring());
    }

    public function testAutoPaymentWithoutCustomer()
    {
        $this->subscription->customer_id = null;

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $this->cardPayment,
        ];

        $this->ba->publicAuth();

        $this->makeRequestAndGetContent($request);

        $token = $this->getDbLastEntity(Entity::TOKEN);

        $this->subscription->setStatus(Subscription\Status::AUTHENTICATED);
        $this->subscription->recurring_type = 'auto';

        $this->ba->subscriptionsAuth();

        $order = $this->fixtures->create(
            'order',
            ['amount' => $this->cardPayment['amount']]);

        $paymentArray = array_merge($this->cardPayment, [
            'token' => $token->getPublicId(),
            'order_id' => $order->getPublicId(),
        ]);

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/subscriptions',
            'content' => $paymentArray,
        ];

        $this->makeRequestAndGetContent($request);

        $payment = $this->getDbLastEntity(Entity::PAYMENT);

        $this->assertEquals($this->subscription->getId(), $payment->getSubscriptionId());
        $this->assertTrue($payment->isAuthorized());
        $this->assertFalse(empty($payment->getTokenId()));
        $this->assertFalse(empty($payment->getCardId()));
        $this->assertTrue(empty($payment->customer_id));
        $this->assertEquals($payment->getTokenId(), $token->getId());
        $this->assertTrue($payment->isRecurringTypeAuto());
    }

    public function testAutoPaymentWithGlobalCustomer()
    {
        $this->fixtures->create('customer', [
            'id' => '100002customer',
            'global_customer_id' => '10000gcustomer',
        ]);

        $cardGlobalTokenAttributes = [
            'id'               => '100000custgupi',
            'token'            => '10000card1234',
            'customer_id'      => '10000gcustomer',
            'merchant_id'      => '100000Razorpay',
            'method'           => 'card',
            'card_id'          => '100000000lcard',
            'recurring_status' => 'confirmed',
            'recurring'        => true,
        ];

        $token = $this->fixtures->create('token', $cardGlobalTokenAttributes);

        $this->subscription->setStatus(Subscription\Status::AUTHENTICATED);
        $this->subscription->recurring_type = 'auto';
        $this->subscription->customer_id = '100002customer';
        $this->subscription->global_customer = true;

        $this->ba->subscriptionsAuth();

        $order = $this->fixtures->create(
            'order',
            ['amount' => $this->cardPayment['amount']]);

        $paymentArray = array_merge($this->cardPayment, [
            'token' => $token->getPublicId(),
            'order_id' => $order->getPublicId(),
        ]);

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/subscriptions',
            'content' => $paymentArray,
        ];

        $this->makeRequestAndGetContent($request);

        $payment = $this->getDbLastEntity(Entity::PAYMENT);

        $this->assertEquals($this->subscription->getId(), $payment->getSubscriptionId());
        $this->assertTrue($payment->isAuthorized());
        $this->assertFalse(empty($payment->getCardId()));
        $this->assertTrue($payment->isRecurringTypeAuto());
    }

    public function testAutoPaymentLocalCustomerWithGlobalCustomerLink()
    {
        $this->fixtures->create('customer', [
            'id' => '100002customer',
            'global_customer_id' => '10000gcustomer',
        ]);

        $cardGlobalTokenAttributes = [
            'id'               => '100000custcar1',
            'token'            => '10000card1234',
            'customer_id'      => '100002customer',
            'merchant_id'      => '10000000000000',
            'method'           => 'card',
            'card_id'          => '100000000lcard',
            'recurring_status' => 'confirmed',
            'recurring'        => true,
            'entity_id'  => '1000000000012',
            'entity_type'  => 'subscription',
        ];

        $token = $this->fixtures->create('token', $cardGlobalTokenAttributes);

        $this->subscription->setStatus(Subscription\Status::ACTIVE);
        $this->subscription->recurring_type = 'auto';
        $this->subscription->customer_id = '100002customer';
        $this->subscription->global_customer = true;
        $this->subscription->setTokenId('100000custcar1');

        $this->ba->subscriptionsAuth();

        $order = $this->fixtures->create(
            'order',
            [
                'amount' => $this->cardPayment['amount'],
            ]);

        $paymentArray = array_merge($this->cardPayment, [
            'token' => $token->getPublicId(),
            'order_id' => $order->getPublicId(),
        ]);

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/subscriptions',
            'content' => $paymentArray,
        ];

        $this->makeRequestAndGetContent($request);

        $payment = $this->getDbLastEntity(Entity::PAYMENT);

        $this->assertEquals($this->subscription->getId(), $payment->getSubscriptionId());
        $this->assertTrue($payment->isAuthorized());
        $this->assertFalse(empty($payment->getCardId()));
        $this->assertTrue($payment->isRecurringTypeAuto());
    }

    public function testAutoPaymentWithGlobalCustomerEmandate()
    {
        $this->fixtures->create('customer', [
            'id' => '100002customer',
            'global_customer_id' => '10000gcustomer',
        ]);

        $GlobalTokenAttributes = [
            'id'               => '100000custgupi',
            'token'            => '10000card1234',
            'customer_id'      => '10000gcustomer',
            'merchant_id'      => '100000Razorpay',
            'method'           => 'emandate',
            'recurring_status' => 'confirmed',
            'recurring'        => true,
        ];

        $token = $this->fixtures->create('token', $GlobalTokenAttributes);

        $this->subscription->setStatus(Subscription\Status::AUTHENTICATED);
        $this->subscription->recurring_type = 'auto';
        $this->subscription->customer_id = '100002customer';
        $this->subscription->global_customer = true;

        $this->ba->subscriptionsAuth();

        $order = $this->fixtures->create(
            'order',
            ['amount' => $this->cardPayment['amount']]);

        $paymentArray = array_merge($this->eMandatePayment, [
            'token' => $token->getPublicId(),
            'order_id' => $order->getPublicId(),
        ]);

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/subscriptions',
            'content' => $paymentArray,
        ];
        try
        {
            $this->makeRequestAndGetContent($request);
        }
        catch(\Exception $e)
        {
            $this->assertEquals($e->getMessage() ,'The id provided does not exist' );
        }
    }

    public function testCreateInitialPaymentUpiWithoutCustomer()
    {
        $this->subscription->customer_id = null;

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $this->upiPayment,
        ];

        $this->ba->publicAuth();

        $this->makeRequestAndGetContent($request);

        $payment = $this->getDbLastEntity(Entity::PAYMENT);

        $this->assertEquals($this->subscription->getId(), $payment->getSubscriptionId());
        $this->assertTrue($payment->isAuthorized());
        $this->assertFalse(empty($payment->getTokenId()));
        $this->assertEquals($this->subscription->getCustomerId(), $payment->customer_id);
        $this->assertTrue($payment->isRecurringTypeInitial());

        $token = $this->getDbLastEntity(Entity::TOKEN);

        $this->assertEquals($payment->getTokenId(), $token->getId());
        $this->assertTrue($token->isUpiRecurringToken());
        $this->assertEquals($this->subscription->getCustomerId(), $token->customer_id);
        $this->assertEquals($this->subscription->getId(), $token->getEntityId());
        $this->assertEquals(Entity::SUBSCRIPTION, $token->getEntityType());
        $this->assertEquals(Token\RecurringStatus::CONFIRMED, $token->getRecurringStatus());

        $this->assertEquals($this->upiPayment['vpa'], $token->vpa->getAddress());
        $this->assertEquals($this->upiPayment['vpa'], $payment->getVpa());
    }

    public function testAutoPaymentUpiWithoutCustomer()
    {
        $this->subscription->customer_id = null;

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $this->upiPayment,
        ];

        $this->ba->publicAuth();

        $this->makeRequestAndGetContent($request);

        $token = $this->getDbLastEntity(Entity::TOKEN);

        $this->subscription->setStatus(Subscription\Status::AUTHENTICATED);
        $this->subscription->recurring_type = 'auto';

        $this->ba->subscriptionsAuth();

        $order = $this->fixtures->create(
            'order',
            ['amount' => $this->upiPayment['amount']]);

        $paymentArray = array_merge($this->upiPayment, [
            'token' => $token->getPublicId(),
            'order_id' => $order->getPublicId(),
        ]);

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/subscriptions',
            'content' => $paymentArray,
        ];

        $this->makeRequestAndGetContent($request);

        $payment = $this->getDbLastEntity(Entity::PAYMENT);

        $this->assertEquals($this->subscription->getId(), $payment->getSubscriptionId());
        $this->assertTrue(empty($payment->customer_id));
        $this->assertEquals($payment->getTokenId(), $token->getId());
        $this->assertTrue($payment->isRecurringTypeAuto());
        $this->assertFalse($payment->isFailed());

        $this->assertEquals($this->upiPayment['vpa'], $payment->getVpa());
    }

    public function testCreateInitialPaymentWithOtp()
    {
        $this->subscription->customer_id = null;

        $this->mockSession();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $this->cardPayment,
        ];

        $this->ba->publicAuth();

        $this->makeRequestAndGetContent($request);

        $payment = $this->getDbLastEntity(Entity::PAYMENT);

        $this->assertEquals($this->subscription->getId(), $payment->getSubscriptionId());
        $this->assertTrue($payment->isAuthorized());
        $this->assertFalse(empty($payment->getCardId()));
        $this->assertTrue($payment->isRecurringTypeInitial());

        $token = $this->getDbLastEntity(Entity::TOKEN);

        $this->assertTrue($token->isCard());
        $this->assertEquals($this->subscription->getId(), $token->getEntityId());
        $this->assertEquals(Entity::SUBSCRIPTION, $token->getEntityType());
        $this->assertEquals(Token\RecurringStatus::CONFIRMED, $token->getRecurringStatus());
        $this->assertTrue($token->isRecurring());
    }

    public function testCardChangePaymentWithOtp()
    {
        $this->subscription->customer_id = null;

        $this->mockSession();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $this->cardPayment,
        ];

        $this->ba->publicAuth();

        $content = $this->makeRequestAndGetContent($request);

        $this->capturePayment($content['razorpay_payment_id'], $this->cardPayment['amount']);

        $this->subscription->setStatus(Subscription\Status::AUTHENTICATED);

        $token = $this->getDbLastEntity(Entity::TOKEN);

        $paymentArray = array_merge($this->cardPayment, [
            'amount' => 500,
            'subscription_card_change' => true,
        ]);
        $paymentArray['card']['number'] = '41476700000006';

        $this->ba->publicAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $paymentArray,
        ];

        $this->makeRequestAndGetContent($request);

        $payment = $this->getDbLastEntity(Entity::PAYMENT);

        $this->assertEquals($this->subscription->getId(), $payment->getSubscriptionId());
        $this->assertTrue($payment->isAuthorized());
        $this->assertFalse(empty($payment->getCardId()));
        $this->assertFalse(empty($payment->customer_id));
        $this->assertTrue($payment->isRecurringTypeInitial());

        $cardChangeToken = $this->getDbLastEntity(Entity::TOKEN);

        $this->assertTrue($token->isCard());
        $this->assertEquals($this->subscription->getId(), $cardChangeToken->getEntityId());
        $this->assertEquals(Entity::SUBSCRIPTION, $cardChangeToken->getEntityType());
        $this->assertEquals(Token\RecurringStatus::CONFIRMED, $cardChangeToken->getRecurringStatus());
        $this->assertTrue($cardChangeToken->isRecurring());

        $this->assertNotEquals($token->getId(), $cardChangeToken->getId());
    }

    public function testCreateInitialPaymentCardWithOffer()
    {
        $paymentBody = $this->cardPayment;

        $paymentBody['offer_id'] = $this->offer->getPublicId();

        $this->mockApplyOffer();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $paymentBody,
        ];

        $this->ba->publicAuth();

        $this->makeRequestAndGetContent($request);

        $invoice = $this->getDbLastEntity(Entity::INVOICE);

        $this->assertEquals($this->subscription->getId(), $invoice->getSubscriptionId());
        $this->assertNotNull($invoice->getOfferAmount());
        $this->assertNotNull($invoice->getComment());
    }

    public function testCreateInitialPaymentEMandate()
    {
        $this->createInitialPaymentEMandate();

        $payment = $this->getDbLastEntity(Entity::PAYMENT);

        $this->assertEquals($this->subscription->getId(), $payment->getSubscriptionId());
        $this->assertTrue($payment->isAuthorized());
        $this->assertFalse(empty($payment->getTokenId()));
        $this->assertEquals($this->subscription->getCustomerId(), $payment->customer_id);
        $this->assertTrue($payment->isRecurringTypeInitial());

        $token = $this->getDbLastEntity(Entity::TOKEN);

        $this->assertEquals($payment->getTokenId(), $token->getId());
        $this->assertEquals($token->getMethod(), PaymentMethod::EMANDATE);
        $this->assertEquals($this->subscription->getCustomerId(), $token->customer_id);
        $this->assertEquals($this->subscription->getId(), $token->getEntityId());
        $this->assertEquals(Entity::SUBSCRIPTION, $token->getEntityType());

        //$this->assertEquals(Token\RecurringStatus::CONFIRMED, $token->getRecurringStatus());
    }

    public function testCreateDebitPaymentEMandate()
    {
        $this->addPricingPlanRule('10000000000000');

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $this->eMandatePayment,
        ];

        $this->ba->publicAuth();
        $this->makeRequestAndGetContent($request);
        $token = $this->getDbLastEntity(Entity::TOKEN);
        $token->setRecurringStatus('confirmed');
        $token->saveOrFail();
        $order = $this->fixtures->create('order', [
            Order\Entity::AMOUNT          => 30000,
            Order\Entity::PAYMENT_CAPTURE => true
        ]);
        $debitPayment = array_merge($this->eMandatePayment, [
            'token'     => $token->getPublicId(),
            'order_id'  => $order->getPublicId(),
            'amount'    => $order->getAmount(),
            'recurring' => '1'
        ]);
        $this->subscription->recurring_type = 'auto';
        $this->fixtures->edit(
            'token',
            $token->getPublicId(),
            [
                Token\Entity::GATEWAY_TOKEN    => 'HDFC6000000005844847',
                Token\Entity::RECURRING        => 1,
                Token\Entity::RECURRING_STATUS => Token\RecurringStatus::CONFIRMED,
            ]);
        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/subscriptions',
            'content' => $debitPayment,
        ];
        $this->ba->subscriptionsAuth();
        $this->makeRequestAndGetContent($request);
        $payment = $this->getDbLastEntity(Entity::PAYMENT);
        $this->assertEquals($this->subscription->getId(), $payment->getSubscriptionId());
        $this->assertTrue($payment->isAuthorized());
        $this->assertFalse(empty($payment->getTokenId()));
        $this->assertTrue($payment->isRecurringTypeAuto());

        //$this->assertEquals('750', $payment['fee']);
        // remove comment after debugging payment issue
    }

    public function testSubscriptionEmandateToken()
    {
        $this->createInitialPaymentEMandate();

        $token = $this->getDbLastEntity(Entity::TOKEN);

        $request = [
            'method'  => 'GET',
            'url'     => '/tokens/' . $token->getPublicId() . '/emandate_detail',
            'content' => []
        ];

        $this->ba->subscriptionsAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('netbanking', $response['auth_type']);

        $this->assertEquals('HDFC', $response['bank']);
    }

    protected function createInitialPaymentEMandate()
    {
        $this->addPricingPlanRule('10000000000000');

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $this->eMandatePayment,
        ];

        $this->ba->publicAuth();

        $this->makeRequestAndGetContent($request);
    }

    protected function mockSubscription()
    {
        $subscriptionMock = $this->getMockBuilder(Mock\External::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['fetchSubscriptionInfo', 'paymentProcess'])
            ->getMock();

        $this->subscription = $this->createSubscriptionEntity();

        $subscriptionMock->method('fetchSubscriptionInfo')
            ->will($this->returnCallback(
                function ()
                {
                    return $this->subscription;
                }));

        $subscriptionMock->method('paymentProcess')
            ->will($this->returnCallback(
                function ()
                {
                    return null;
                }));

        $moduleManagerMock = $this->getMockBuilder(Modules\Manager::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['createSubscriptionDriver'])
            ->getMock();

        $moduleManagerMock->method('createSubscriptionDriver')
            ->will($this->returnCallback(
                function () use ($subscriptionMock)
                {
                    return $subscriptionMock;
                }));

        $this->app->instance('module', $moduleManagerMock);

        return $subscriptionMock;
    }

    protected function createSubscriptionEntity(array $subscriptionData = [])
    {
        $subscriptionData = array_merge($this->testData['sample_subscription_data'], $subscriptionData);

        $subscription = new Subscription\Entity;

        $subscription->forceFill($subscriptionData);

        $subscription->setExternal(true);

        $invoice = $this->createInvoiceForSubscription($subscription);

        $subscription->current_invoice_id = $invoice->getId();

        $this->customer =  $this->fixtures->create('customer');

        $subscription->customer_id = $this->customer->getId();

        return $subscription;
    }

    protected function createInvoiceForSubscription(Subscription\Entity $subscription, $overrideWith = []): Invoice\Entity
    {
        $invoiceId = UniqueIdEntity::generateUniqueId();

        $order = $this->createOrderForSubscription($subscription, ['id' => '100000000order']);

        $invoice = $this->fixtures
            ->create(
                'invoice',
                array_merge(
                    [
                        'id'              => $invoiceId,
                        'order_id'        => $order->getId(),
                        'amount'          => $subscription->getCurrentInvoiceAmount(),
                        'subscription_id' => $subscription->getId()
                    ],
                    $overrideWith));

        return $invoice;
    }

    protected function createOrderForSubscription(Subscription\Entity $subscription, $overrideWith = []): Order\Entity
    {
        $orderId = UniqueIdEntity::generateUniqueId();

        return $this->fixtures
            ->create(
                'order',
                array_merge(
                    [
                        'id'              => $orderId,
                        'amount'          => $subscription->getCurrentInvoiceAmount(),
                    ],
                    $overrideWith));
    }

    protected function mockSession()
    {
        $data = array(
            'test_app_token'   => 'capp_1000000custapp',
            'test_checkcookie' => '1'
        );

        $this->session($data);
    }

    protected function mockOffer()
    {
        $this->offer = $this->fixtures->create('offer:live_card', [
            'payment_method' => 'card',
            'min_amount'     => 1000,
            'flat_cashback'  => 600,
            'type'           => 'instant',
            'default_offer'  => 1,
            'iins'           => ['401200'],
            'product_type'   => 'subscription',
            'display_text'   => 'subcription 1 rs discount description',
            'name'           => 'subcription 1 rs discount name',
        ]);

        $subOffer = $this->fixtures->create('subscription_offers_master', [
            'redemption_type' => 'cycle',
            'applicable_on'   => 'both',
            'no_of_cycles'    => 10,
            'offer_id'        => $this->offer->getId(),
        ]);
    }

    protected function mockApplyOffer()
    {
        // create entity_offer
        $this->fixtures->create('entity_offer', [
            'entity_id'   => '100000000order',
            'entity_type' => 'order',
            'offer_id'    => $this->offer->getId(),
        ]);
    }

    protected function addPricingPlanRule($merchantId)
    {
        $autoRule = [
            'payment_method'      => 'emandate',
            'product'             => 'primary',
            'feature'             => 'payment',
            'payment_method_type' => null,
            'payment_issuer'      => 'auto',
            'percent_rate'        => 250,
            'fixed_rate'          => 0,
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'fee_bearer'          => 'platform',
            'international'       => '0',
            'procurer'            => null,
        ];

        $initialRule = [
            'payment_issuer'      => 'initial',
            'percent_rate'        => 0,
            'fixed_rate'          => 500
        ];

        $initialRule = array_merge($autoRule, $initialRule);

        $planInitial = $this->fixtures->create('pricing', $initialRule);

        $planInitial = $planInitial->toArray();

        $planAuto = $this->fixtures->create('pricing', array_merge($autoRule, ['plan_id' => $planInitial['plan_id']]));

        $this->fixtures->merchant->edit($merchantId, [
            'pricing_plan_id' => $planInitial['plan_id'],
            'fee_bearer'      => FeeBearer::PLATFORM,
        ]);
    }
}
