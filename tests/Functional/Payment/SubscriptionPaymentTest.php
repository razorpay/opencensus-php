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
use RZP\Modules\Subscriptions\Mock;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Exception\BadRequestException;
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

        $this->assertEquals(UPIMandateFrequency::MONTHLY, $upiMandate->getFrequency());
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
        $this->assertFalse(empty($payment->getGlobalTokenId()));
        $this->assertFalse(empty($payment->getCardId()));
        $this->assertFalse(empty($payment->customer_id));
        $this->assertEquals('10000gcustomer', $payment->getGlobalCustomerId());
        $this->assertTrue($payment->isRecurringTypeInitial());

        $token = $this->getDbLastEntity(Entity::TOKEN);

        $this->assertEquals($payment->getGlobalTokenId(), $token->getId());
        $this->assertTrue($token->isCard());
        $this->assertEquals('10000gcustomer', $token->customer_id);
        $this->assertEquals('100000Razorpay', $token->merchant_id);
        $this->assertEquals($this->subscription->getId(), $token->getEntityId());
        $this->assertEquals(Entity::SUBSCRIPTION, $token->getEntityType());
        $this->assertEquals($payment->card->globalCard->getId(), $token->getCardId());
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
        $this->assertFalse(empty($payment->getGlobalTokenId()));
        $this->assertFalse(empty($payment->getCardId()));
        $this->assertFalse(empty($payment->customer_id));
        $this->assertEquals('10000gcustomer', $payment->getGlobalCustomerId());
        $this->assertTrue($payment->isRecurringTypeInitial());

        $cardChangeToken = $this->getDbLastEntity(Entity::TOKEN);

        $this->assertEquals($payment->getGlobalTokenId(), $cardChangeToken->getId());
        $this->assertTrue($token->isCard());
        $this->assertEquals('10000gcustomer', $cardChangeToken->customer_id);
        $this->assertEquals('100000Razorpay', $cardChangeToken->merchant_id);
        $this->assertEquals($this->subscription->getId(), $cardChangeToken->getEntityId());
        $this->assertEquals(Entity::SUBSCRIPTION, $cardChangeToken->getEntityType());
        $this->assertEquals($payment->card->globalCard->getId(), $cardChangeToken->getCardId());
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
}
