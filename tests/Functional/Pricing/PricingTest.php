<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Models\Transaction;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class PricingTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/PricingData.php';

        parent::setUp();

        $this->ba->appAuth();
    }

    public function testAddPricingPlanRule()
    {
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testBulkPricingPlan()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testEmptyBulkPricingPlan()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testDuplicateBulkPricingPlan()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testCreatePricingPlanWithMinAndMaxFee()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testCreatePricingPlanWithInvalidMinAndMaxFee()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }


    public function testAddPricingPlanNBRule()
    {
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'.$content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanNBNoNetworkRule()
    {
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'.$content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanWalletRule()
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

    public function testAddPricingPlanRuleWithMaxFee()
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

    public function testGetPricingNetworks()
    {
        $this->ba->appAuth();

        $response = $this->startTest();

        $this->assertNotNull($response['bank']);
        $this->assertNotNull($response['card']);
        $this->assertNotNull($response['wallet']);

        $this->assertNotEquals(count($response['bank']), 0);
        $this->assertNotEquals(count($response['card']), 0);
        $this->assertNotEquals(count($response['wallet']), 0);

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

    public function testGetPricingPlansGrouping()
    {
        $content = $this->createPricingPlan();
        $this->createPricingPlan2();

        $this->addPricingPlanRule($content['id']);

        $this->ba->appAuth('rzp_test');
        $this->startTest();

        $this->ba->appAuth('rzp_live');
        $this->startTest();
    }

    public function testMerchantAssignPricingPlanDefault()
    {
        $id = $this->createPricingPlan()['id'];
        $testData['request']['content']['pricing_plan_id'] = $id;

        // The default pricing plan has only card enabled. In
        //   case, the merchant has any other method enabled,
        //   disable it to pass the validation test. Else,
        //   the validation test might not succeed.

        $this->setDefaultMerchantMethods();

        $this->startTest($testData);
    }

    public function testMerchantAssignPricingPlanWithInternational()
    {
        $id = $this->createPricingPlan()['id'];

        // Test with the default pricing plan with netbanking
        //   enabled. Disable existing methods except card
        //   and only test for international. Default pricing does not
        //   have international

        $this->setDefaultMerchantMethods();

        $this->fixtures->merchant->enableInternational();

        $testData['request']['content']['pricing_plan_id'] = $id;

        $this->startTest($testData);
    }

    public function testMerchantAssignPricingPlanMerchantDefault()
    {
        // This test case is for handling errors where
        //   a specific method is not enabled for pricing
        //   but is enabled for the merchant. e.g. merchant has
        //   netbanking enabled but the pricing does not have it.

        $id = $this->createPricingPlan()['id'];

        $testData['request']['content']['pricing_plan_id'] = $id;

        $this->startTest($testData);
    }

    public function testMerchantWithAmexEnabled()
    {
        $this->markTestSkipped('Mobikwik temporarily disabled.');

        $id = $this->createPricingPlan()['id'];

        $this->setDefaultMerchantMethods();

        $this->fixtures->merchant->enableMethod('10000000000000', 'amex');

        $testData['request']['content']['pricing_plan_id'] = $id;

        $this->startTest($testData);
    }


    public function testMerchantAssignAndGetPricingPlan()
    {
        $this->markTestSkipped('Mobikwik temporarily disabled.');

        $content = $this->assignPricingPlanToMerchant();

        $testData['response']['content']['id'] = $content['id'];

        $this->ba->adminAuth('test', null, 'org_' . Org::RZP_ORG);

        $this->startTest($testData);
    }

    public function testMerchantReplacePricingPlan()
    {
        $this->markTestSkipped('Mobikwik temporarily disabled.');

        $this->testMerchantAssignPricingPlanDefault();

        $id = $this->createPricingPlan2()['id'];

        $testData['request']['content']['pricing_plan_id'] = $id;

        $this->startTest($testData);
    }

    public function testMerchantGetPricingPlanNoPlanAssigned()
    {
        $this->ba->adminAuth('test', null, 'org_' . Org::RZP_ORG);

        $content = $this->startTest();

        // No plan assigned, so it should be empty array
        $this->assertEquals(count($content), 0);
    }

    public function testMerchantGetPricingPlan()
    {
        $this->fixtures->create('pricing');

        $merchant2 = $this->fixtures->create(
            'merchant',
            array(
                'id' => '1FcXNxsHt5dOPI',
                'pricing_plan_id' => '1ycviEdCgurrFI'));

        $this->ba->adminAuth('test', null, 'org_' . Org::RZP_ORG);

        $this->startTest();
    }

    public function testAddInternationalPricingPlanRule()
    {
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        return $this->startTest($testData);
    }

    public function testAddAmountRangePricingPlanRule()
    {
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        return $this->startTest($testData);
    }

    public function testAddAmountRangePricingPlanRuleOverlap()
    {
        $content = $this->createAmountRangePricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        return $this->startTest($testData);
    }

    public function testAddAmountRangePricingPlanRuleDuplicate()
    {
        $content = $this->createAmountRangePricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddDuplicateInternationalPricingPlanRule()
    {
        $content = $this->testAddInternationalPricingPlanRule();

        $testData['request']['url'] = '/pricing/'. $content['plan_id'] . '/rule';

        $this->startTest($testData);

        $this->startTest($testData);
    }

    public function testAddInternationalPricingPlanRuleForNonCardMethod()
    {
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        return $this->startTest($testData);
    }

    public function testAddInternationalPricingPlanRuleWithExtraFields()
    {
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        return $this->startTest($testData);
    }

    public function testAddDuplicateWalletPricingRule()
    {
        $testData['request']['url'] = '/pricing/'. '1hDYlICobzOCYt' . '/rule';

        $this->startTest($testData);
    }

    public function testDeletePricingPlanRule()
    {
        $content = $this->startTest();
    }

    public function testDeletePricingPlanRuleForce()
    {
        $content = $this->startTest();
    }

    public function testDeleteUsedPricingPlanRule()
    {
        $payment = $this->doAuthAndCapturePayment();

        $txn = $this->getLastEntity('transaction', true);

        $transactionId = (new Transaction\Entity)->verifyIdAndSilentlyStripSign($txn['id']);

        $input = [
                    'transaction_id' => $transactionId,
                ];

        $feesSplit = $this->getEntities('fee_breakup', $input, true);

        $index = array_search('payment', array_column($feesSplit['items'], 'name'));

        $ruleId = $feesSplit['items'][$index]['pricing_rule_id'];

        $pricing = $this->getEntityById('pricing', $ruleId, true);

        $this->testData[__FUNCTION__]['request']['url'] =
                '/pricing/'.$pricing['plan_id'].'/rule/'.$ruleId;

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

    protected function setDefaultMerchantMethods()
    {
        // Disable all methods and only enable card.
        // The default pricing plan has only card enabled

        $this->fixtures->merchant->disableAllMethods();

        $this->fixtures->merchant->enableCard();
    }

    protected function assignPricingPlanToMerchant()
    {
        $id = $this->createPricingPlan()['id'];

        $this->setDefaultMerchantMethods();

        return $this->merchantAssignPricingPlan($id, '10000000000000');
    }

    protected function createPricingPlan($pricingPlan = [])
    {
        $defaultPricingPlan = [
            'plan_name'           => 'TestPlan1',
            'payment_method'      => 'card',
            'payment_method_type' => 'credit',
            'payment_network'     => 'DICL',
            'payment_issuer'      => 'HDFC',
            'percent_rate'        => 1000,
            'fixed_rate'          => 0,
        ];

        $pricingPlan = array_merge($defaultPricingPlan, $pricingPlan);

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $plan = $plan->toArray();

        $plan['id'] = $plan['plan_id'];

        return $plan;
    }

    protected function addPricingPlanRule($id)
    {
        $rule = array(
                'payment_method' => 'card',
                'payment_method_type'  => 'credit',
                'payment_network' => 'MAES',
                'payment_issuer' => 'HDFC',
                'percent_rate' => 1000,
                'international' => 0,
                'amount_range_active' => '0',
                'amount_range_min' => null,
                'amount_range_max' => null,
        );

        $request = array(
            'method' => 'POST',
            'url' => '/pricing/'.$id.'/rule',
            'content' => $rule);

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }


    protected function createPricingPlan2()
    {
        $planData = [
            'plan_name'           => 'TestPlan2',
            'payment_method'      => 'card',
            'plan_id'             => '1ycviEdCgurrFJ',
            'payment_method_type' => 'credit',
            'payment_network'     => 'DICL',
            'payment_issuer'      => 'SBIN',
            'percent_rate'        => '275',
            'fixed_rate'          => 0,
            ];

        $pricingData = [
                [
                    'payment_method'      => 'card',
                    'payment_method_type' => 'credit',
                    'payment_network'     => 'DICL',
                    'payment_issuer'      => 'ICIC',
                    'percent_rate'        => 250,
                ],
                [
                    'payment_method'      => 'card',
                    'payment_method_type' => 'debit',
                    'payment_network'     => 'MAES',
                    'payment_issuer'      => 'PUNB',
                    'percent_rate'        => 250,
                ],
                [
                    'payment_method'      => 'card',
                    'payment_method_type' => 'credit',
                    'payment_network'     => 'MC',
                    'payment_issuer'      => 'AXIS',
                    'fixed_rate'          => 3000,
                ],
            ];

        $plan = $this->createPricingPlan($planData);

        $pricingPlanId = $plan['id'];

        foreach ($pricingData as $data)
        {
            $request = array(
                'method' => 'POST',
                'url' => '/pricing/' . $pricingPlanId . '/rule',
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

    protected function createAmountRangePricingPlan()
    {
        $pricingPlan = array(
            'plan_name' => 'AmountRangePlan',
            'payment_method' => 'card',
            'payment_method_type'  => 'debit',
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 1500,
            'amount_range_active' => true,
            'amount_range_min' => 100,
            'amount_range_max' => 25000);

        $plan = $this->createPricingPlan($pricingPlan);

        return $plan;
    }

    public function testAddPricingPlanRuleWithFeature()
    {
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

}
