<?php

namespace Tests\Functional\Merchant;

use Tests\Functional\TestCase;
use Tests\Functional\RequestResponseFlowTrait;

class PricingTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        parent::setUp();

        $this->setupAppBasicAuthParams();

        //
        // load test data
        //
        $this->testData = include(__DIR__.'/helpers/PricingData.php');
    }

    public function testCreatePricingPlan()
    {
        $this->startTest();
    }

    public function testAddPricingPlanRule()
    {
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['plan_id'] . '/rule';

        $this->startTest($testData);
    }

    public function startTest($testDataToReplace = array())
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        $this->replaceValuesRecursively($testData, $testDataToReplace);

        return $this->runRequestResponseFlow($testData);
    }

    protected function createPricingPlan()
    {
        $pricingPlan = array(
            'plan_name' => 'haha',
            'payment_mode' => 'card',
            'payment_mode_type'  => 'credit',
            'payment_network' => 'DICL',
            'payment_issuer' => 'HDFC',
            'percent_rate' => 1000);

        $request = array(
            'method' => 'POST',
            'url' => '/pricing',
            'content' => $pricingPlan);

        $response = $this->makeRequest($request);

        $content = $response->getContent();

        $this->assertJson($content);
        $content = json_decode($content, true);

        $this->assertArraySelectiveEquals($pricingPlan, $content);

        return $content;
    }
}
