<?php

namespace RZP\Tests\Functional\Subscription;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use Mockery;

class SubscriptionTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/SubscriptionTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->fixtures->merchant->editFeatures('recurring');
    }

    public function testCreatePlan()
    {
        $this->startTest();
    }
}