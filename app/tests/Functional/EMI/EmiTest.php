<?php

namespace Tests\Functional\EMI;

use Tests\Functional\TestCase;
use Tests\Functional\RequestResponseFlowTrait;

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

        $plans = $this->startTest();

        $this->assertEquals($plans['entity'], 'collection');
    } 

    public function testFetchEmiPlanUsingPlanId()
    {
        $emi = $this->testAddEmiPlans();

        $request = &$this->testData['testFetchEmiPlanUsingPlanId']['request'];
        $request['url'] = '/emi/'.$emi['id'];

        $this->ba->publicAuth();
        $this->startTest();
    }
}