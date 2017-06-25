<?php

namespace RZP\Tests\Functional\Subscription;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Subscription\SubscriptionTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use Mockery;
use Carbon\Carbon;

class SubscriptionCancelTest extends TestCase
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

    public function testSubscriptionChargeAfterCancel()
    {
        $this->doAuthTxnForNewSubscription();

        $subscription = $this->getLastEntity('subscription', true);

        $chargeAt = Carbon::createFromTimestamp($subscription['charge_at'] + 1, 'Asia/Kolkata');

        Carbon::setTestNow($chargeAt);

        $this->makeSubscriptionChargeCronRequest();

        $this->makeCancelRequest($subscription['id']);

        $subscription = $this->getLastEntity('subscription', true);

        $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);

        $this->assertEquals(0, $result['invoices_created']);
    }

    protected function makeCancelRequest(string $subscriptionId)
    {
        $testData = $this->testData['testSubscriptionCancel'];

        $testData['request']['url'] = '/subscriptions/' . $subscriptionId . '/cancel';

        $this->ba->privateAuth();

        return $this->startTest($testData);
    }
}
