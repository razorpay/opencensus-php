<?php

namespace RZP\Tests\Functional\Invoice;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class InvoiceCountTest extends TestCase
{
    use InvoiceTestTrait;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/InvoiceCountTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();
    }


    public function testCountForSubscriptionIdWithNoInvoice()
    {

        $this->startTest();

    }

    public function testCountForSubscriptionIdWithInvoice()
    {

        $subscriptionAttributes = [
            'id'          => '1000000subscri',
            'total_count' => 3,
            'notes'       => [],
            'plan_id'     => '1000000000plan',
            'schedule_id' => '100000schedule'
        ];

        $schedule     = $this->fixtures->create('schedule', ['id' => '100000schedule']);
        $plan         = $this->fixtures->plan->create();
        $subscription = $this->fixtures->create('subscription',$subscriptionAttributes);

        $this->createDraftInvoice(['subscription_id' => $subscription->getId()]);

        $this->startTest();

    }


}
