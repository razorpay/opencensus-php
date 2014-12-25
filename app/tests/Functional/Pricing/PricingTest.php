<?php

namespace Tests\Functional\Merchant;

use Tests\Functional\TestCase;
use Tests\Functional\RequestResponseFlowTrait;

class PricingTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/PricingData.php';

        parent::setUp();

        $this->ba->appAuth();
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

    public function testAddPricingPlanNBRule()
    {
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'.$content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddDuplicatePricingPlanRule()
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

        $this->ba->appAuth('rzp_test');
        $this->startTest($testData);

        $this->ba->appAuth('rzp_live');
        $this->startTest($testData);
    }

    public function testGetPricingPlans()
    {
        $this->createPricingPlan();
        $this->createPricingPlan2();

        $this->ba->appAuth('rzp_test');
        $this->startTest();

        $this->ba->appAuth('rzp_live');
        $this->startTest();
    }

    public function testMerchantAssignPricingPlan()
    {
        $id = $this->createPricingPlan()['id'];

        $testData['request']['content']['pricing_plan_id'] = $id;

        $this->startTest($testData);
    }

    public function testMerchantAssignAndGetPricingPlan()
    {
        $content = $this->assignPricingPlanToMerchant();

        $testData['response']['content']['id'] = $content['id'];

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
        $this->fixtures->createEntity('pricing');

        $merchant2 = $this->fixtures->createEntity(
            'merchant',
            array(
                'id' => '1FcXNxsHt5dOPI',
                'pricing_plan_id' => '1ycviEdCgurrFI'));

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

    protected function assignPricingPlanToMerchant()
    {
        $id = $this->createPricingPlan()['id'];

        $request = array(
            'url' => '/merchants/10000000000000/pricing',
            'method' => 'POST',
            'content' => ['pricing_plan_id' => $id]);

        return $this->makeRequestAndGetContent($request);
    }

    protected function createPricingPlan()
    {
        $pricingPlan = array(
            'plan_name' => 'TestPlan1',
            'payment_mode' => 'card',
            'payment_mode_type'  => 'credit',
            'payment_network' => 'DICL',
            'payment_issuer' => 'HDFC',
            'percent_rate' => 1000);

        $request = array(
            'method' => 'POST',
            'url' => '/pricing',
            'content' => $pricingPlan);

        $content = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('rules', $content);
        $this->assertArraySelectiveEquals($pricingPlan, $content['rules'][0]);

        return $content;
    }

    protected function createPricingPlan2()
    {
        $planData = array(
            'plan_name' => 'TestPlan2',
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
