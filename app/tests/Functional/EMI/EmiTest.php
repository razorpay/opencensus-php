<?php

namespace Tests\Functional\Payment;

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

    public function testAddEmiOptions()
    {
        $emiOption = $this->startTest();
        
        return $emiOption;
    }

    public function testFetchAllEmiOptions()
    {
        $this->testAddEmiOptions();

        $this->ba->publicAuth();

        $options = $this->startTest();

        $this->assertEquals($options['entity'], 'collection');
    } 

    public function testFetchEmiUsingPlanId()
    {
        $emi = $this->testAddEmiOptions();

        $request = &$this->testData['testFetchEmiUsingPlanId']['request'];
        $request['url'] = '/emi/'.$emi['id'];

        $this->ba->publicAuth();
        $this->startTest();
    }
}