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

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testGetPricingPlan()
    {
        $id = $this->createPricingPlan2()['id'];

        $testData['request']['url'] = '/pricing/'.$id;
        $testData['request']['method'] = 'GET';

        $this->startTest($testData);
    }

    public function testGetPricingPlans()
    {
        $this->createPricingPlan();

        $this->createPricingPlan2();

        $this->startTest();
    }

    public function testMerchantAssignPricingPlan()
    {
        $id = $this->createPricingPlan()['id'];

        $testData['request']['content']['pricing_plan_id'] = $id;

        $this->startTest($testData);
    }

    public function testMerchantReplacePricingPlan()
    {
        $this->testMerchantAssignPricingPlan();

        $id = $this->createPricingPlan2()['id'];

        $testData['request']['content']['pricing_plan_id'] = $id;

        $this->startTest($testData);
    }

    public function testMerchantGetPricingPlanNoPlanAssigned()
    {
        $content = $this->startTest();

        // No plan assigned, so it should be empty array
        $this->assertEquals(count($content), 0);
    }

    public function testMerchantGetPricingPlan()
    {
        $merchant2 = $this->createEntity('merchant',
            ['id' => '543cdc2e93ae13f61f52b3eb',
             'pricing_plan_id' => '5053edf267a4a6d1d26b43df']);

        $this->startTest();
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

        $this->assertArraySelectiveEquals($pricingPlan, $content['rules'][0]);

        return $content;
    }

    protected function createPricingPlan2()
    {
        $planData = array(
            'plan_name' => 'testPlan',
            'payment_mode' => 'card',
            'payment_mode_type' => 'credit',
            'payment_network' => 'DICL',
            'payment_issuer' => 'SBIN',
            'percent_rate' => '275');

        $pricingData =
            array(
                array(
                    'payment_mode' => 'card',
                    'payment_mode_type' => 'credit',
                    'payment_network' => 'DICL',
                    'payment_issuer' => 'ICIC',
                    'percent_rate' => 250),
                array(
                    'payment_mode' => 'card',
                    'payment_mode_type' => 'debit',
                    'payment_network' => 'MAES',
                    'payment_issuer' => 'PUNB',
                    'percent_rate' => 250),
                array(
                    'payment_mode' => 'card',
                    'payment_mode_type' => 'credit',
                    'payment_network' => 'MC',
                    'payment_issuer' => 'AXIS',
                    'fixed_rate' => 3000)
                );

        $request = array(
            'method' => 'POST',
            'url' => '/pricing',
            'content' => $planData);

        $content = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('id', $content);
        $pricingPlanId = $content['id'];

        foreach ($pricingData as $data)
        {
            $request = array(
                'method' => 'POST',
                'url' => '/pricing/'.$pricingPlanId.'/rule',
                'content' => $data);

            $content = $this->makeRequestAndGetContent($request);

            $this->assertArraySelectiveEquals($data, $content);
        }

        $request = array(
            'method' => 'GET',
            'url' => '/pricing/'.$pricingPlanId);

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }
}
