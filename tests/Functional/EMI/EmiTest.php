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
        $emiPlan = $this->startTest();

        return $emiPlan;
    }

    public function testFetchAllEmiPlans()
    {
        $this->testAddEmiPlans();

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testFetchEmiPlanUsingPlanId()
    {
        $this->testAddEmiPlans();

        $emi = $this->getLastEntity('emi_plan', true);

        $request = &$this->testData['testFetchEmiPlanUsingPlanId']['request'];

        $request['url'] = '/emi/'.$emi['id'];

        $this->startTest();
    }

    public function testDeleteEmiPlan()
    {
        $this->testAddEmiPlans();

        $emi = $this->getLastEntity('emi_plan', true);

        $request = &$this->testData['testDeleteEmiPlan']['request'];
        $request['url'] = '/emi/'.$emi['id'];

        $this->ba->appAuth();
        $emiPlan = $this->startTest();
    }
}