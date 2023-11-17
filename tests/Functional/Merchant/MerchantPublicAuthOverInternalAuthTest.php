<?php

namespace Functional\Merchant;
use Closure;
use Generator;
use Mockery;
use RZP\Models\Merchant\Account;
use RZP\Modules\Manager as ModuleManager;
use RZP\Modules\Subscriptions\Mock\External as SubscriptionsExternalMock;
use RZP\Services\PayoutLinks;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;

class MerchantPublicAuthOverInternalAuthTest extends TestCase
{
    use PartnerTrait, RequestResponseFlowTrait, TestsBusinessBanking;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/MerchantPublicAuthOverInternalAuthTestData.php';

        parent::setUp();
    }

    public function testPublicAuthInternal(): void
    {
        $this->ba->checkoutServiceInternalAuth();

        $this->startTest();
    }

    public function testPartnerAuthInternal(): void
    {
        $client = $this->setUpPartnerMerchantAppAndGetClient('dev');

        $this->fixtures->create(
            'merchant_access_map',
            [
                'entity_id'   => $client->getApplicationId(),
                'merchant_id' => '100000Razorpay'
            ]
        );

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['merchant_public_key'] = 'rzp_test_partner_' . $client->getId();

        $testData['request']['content']['merchant_account_id'] = 'acc_100000Razorpay';

        $this->ba->checkoutServiceInternalAuth();

        $this->startTest($testData);
    }

    /**
     * @dataProvider providePublicAuthInternalKeyless
     */
    public function testPublicAuthInternalKeyless(string $queryParam, Closure $publicIdGenerator): void
    {
        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content'][$queryParam] = $publicIdGenerator($this);

        $this->ba->checkoutServiceInternalAuth();

        $this->startTest($testData);
    }

    public function providePublicAuthInternalKeyless(): Generator
    {
        $orderIdGenerator = static function ($testObject): string {
            return $testObject->fixtures->create('order', [
                'amount' => 100,
                'merchant_id' => Account::TEST_ACCOUNT,
            ])->getPublicId();
        };

        yield 'Test KeyLess Auth Works With Just order_id' => ['order_id', $orderIdGenerator];
        yield 'Test KeyLess Auth Works With order_id In x_entity_id' => ['x_entity_id', $orderIdGenerator];

        $invoiceIdGenerator = static function ($testObject): string {
            $order = $testObject->fixtures->create('order', [
                'amount' => 100,
                'merchant_id' => Account::TEST_ACCOUNT,
            ]);

            return $testObject->fixtures->create('invoice', [
                'amount' => 100,
                'order_id' => $order->getId(),
            ])->getPublicId();
        };

        yield 'Test KeyLess Auth Works With Just invoice_id' => ['invoice_id', $invoiceIdGenerator];
        yield 'Test KeyLess Auth Works With invoice_id In x_entity_id' => ['x_entity_id', $invoiceIdGenerator];

        $paymentIdGenerator = static function ($testObject): string {
            return $testObject->fixtures->create('payment')->getPublicId();
        };

        yield 'Test KeyLess Auth Works With Just payment_id' => ['payment_id', $paymentIdGenerator];
        yield 'Test KeyLess Auth Works With payment_id In x_entity_id' => ['x_entity_id', $paymentIdGenerator];

        $contactIdGenerator = static function ($testObject): string {
            return $testObject->fixtures->create('contact')->getPublicId();
        };

        yield 'Test KeyLess Auth Works With Just contact_id' => ['contact_id', $contactIdGenerator];
        yield 'Test KeyLess Auth Works With contact_id In x_entity_id' => ['x_entity_id', $contactIdGenerator];

        $customerIdGenerator = static function ($testObject): string {
            return $testObject->fixtures->create('customer')->getPublicId();
        };

        yield 'Test KeyLess Auth Works With Just customer_id' => ['customer_id', $customerIdGenerator];
        yield 'Test KeyLess Auth Works With customer_id In x_entity_id' => ['x_entity_id', $customerIdGenerator];

        $subscriptionIdGenerator = static function ($testObject): string {
            $subscription = $testObject->fixtures->create('subscription', [
                'plan_id' => '1000000000plan',
                'schedule_id' => '100000schedule',
            ]);

            $subscriptionMock = $testObject->getMockBuilder(SubscriptionsExternalMock::class)
                ->setConstructorArgs([$testObject->app])
                ->onlyMethods(['fetchMerchantIdAndMode'])
                ->getMock();

            $merchantIdAndMode = [
                'mode' => 'test',
                'merchant_id' => Account::TEST_ACCOUNT,
            ];

            $subscriptionMock->method('fetchMerchantIdAndMode')
                ->willReturnMap([
                    [$subscription->getId(), $merchantIdAndMode],
                    [$subscription->getPublicId(), $merchantIdAndMode],
                ]);

            $moduleManagerMock = $testObject->getMockBuilder(ModuleManager::class)
                ->setConstructorArgs([$testObject->app])
                ->onlyMethods(['createSubscriptionDriver'])
                ->getMock();

            $moduleManagerMock->method('createSubscriptionDriver')
                ->willReturnCallback(static function () use ($subscriptionMock) {
                    return $subscriptionMock;
                });

            $testObject->app->instance('module', $moduleManagerMock);

            return $subscription->getPublicId();
        };

        yield 'Test KeyLess Auth Works With Just subscription_id' => ['subscription_id', $subscriptionIdGenerator];
        yield 'Test KeyLess Auth Works With subscription_id In x_entity_id' => [
            'x_entity_id',
            $subscriptionIdGenerator
        ];

        $paymentLinkIdGenerator = static function ($testObject): string {
            return $testObject->fixtures->create('payment_link')->getPublicId();
        };

        yield 'Test KeyLess Auth Works With Just payment_link_id' => ['payment_link_id', $paymentLinkIdGenerator];
        yield 'Test KeyLess Auth Works With payment_link_id In x_entity_id' => ['x_entity_id', $paymentLinkIdGenerator];

        $optionsIdGenerator = static function ($testObject): string {
            return $testObject->fixtures->create('options', [
                'options_json' => '{"checkout":{"label":{"min_amount":"Some first amount"}}}',
                'merchant_id' => Account::TEST_ACCOUNT,
            ])->getPublicId();
        };

        yield 'Test KeyLess Auth Works With Just options_id' => ['options_id', $optionsIdGenerator];
        yield 'Test KeyLess Auth Works With options_id In x_entity_id' => ['x_entity_id', $optionsIdGenerator];

        $payoutLinkIdGenerator = static function ($testObject): string {
            $contact = $testObject->fixtures->create('contact');

            $plMock = Mockery::mock(PayoutLinks::class);

            $plMock->shouldReceive('getModeAndMerchant')->andReturn(['test', Account::TEST_ACCOUNT]);

            $testObject->app->instance('payout-links', $plMock);

            $testObject->setUpMerchantForBusinessBanking(false, 10000000);

            return $testObject->fixtures->create('payout_link', [
                'contact_id' => $contact->getId(),
                'balance_id' => $testObject->bankingBalance->getId(),
            ])->getPublicId();
        };

        yield 'Test KeyLess Auth Works With Just payout_link_id' => ['payout_link_id', $payoutLinkIdGenerator];
        yield 'Test KeyLess Auth Works With payout_link_id In x_entity_id' => ['x_entity_id', $payoutLinkIdGenerator];
    }
}
