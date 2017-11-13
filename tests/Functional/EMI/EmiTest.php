<?php

namespace RZP\Tests\Functional\EMI;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class EmiTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/EMITestData.php';

        parent::setUp();

        $this->ba->appAuth();
    }

    public function testAddEmiPlans()
    {
        $this->startTest();
    }

    /**
     * By default all tests are run on app auth. On public auth
     * there is one route where we return massaged data in different format
     * for checkout to use.
     */
    public function testFetchAllEmiPlansOnPublicAuth()
    {
        $this->fixtures->create('emi_plan');

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testFetchEmiPlanUsingPlanId()
    {
        $this->fixtures->create('emi_plan');

        $this->startTest();
    }

    public function testFetchEmiPlanUsingPlanIdAndAssertIssuerNameForNetwork()
    {
        $this->fixtures->create(
            'emi_plan',
            [
                'bank'    => null,
                'network' => 'AMEX',
            ]);

        $this->startTest();
    }

    public function testDeleteEmiPlan()
    {
        $this->fixtures->create('emi_plan');

        $this->startTest();
    }
}
