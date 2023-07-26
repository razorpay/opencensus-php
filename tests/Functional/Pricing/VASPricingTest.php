<?php

namespace RZP\Tests\Functional\Merchant;

use Event;

use RZP\Exception;
use RZP\Models\Pricing\Entity;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;

class VASPricingTest extends TestCase
{
    use PaymentTrait;
    use HeimdallTrait;
    use DbEntityFetchTrait;

    protected $authToken = null;

    protected $org = null;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/VASPricingTestData.php';

        parent::setUp();

        $this->ba->adminAuth();
    }

    protected function createPricingPlan($pricingPlan = [])
    {
        $defaultPricingPlan = [
            'plan_name'           => 'TestPlan1',
            'payment_method'      => 'card',
            'payment_method_type' => 'credit',
            'payment_network'     => null,
            'payment_issuer'      => null,
            'percent_rate'        => 0,
            'fixed_rate'          => 0,
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
        ];

        $pricingPlan = array_merge($defaultPricingPlan, $pricingPlan);

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $plan = $plan->toArray();

        $plan['id'] = $plan['plan_id'];

        return $plan;
    }

    protected function addPricingPlanRule($id, $rule = [])
    {
        $defaultRule = [
            'payment_method' => 'card',
            'payment_method_type'  => 'credit',
            'payment_network' => 'MAES',
            'payment_issuer' => 'HDFC',
            'percent_rate' => 1000,
            'international' => 0,
            'amount_range_active' => '0',
            'amount_range_min' => null,
            'amount_range_max' => null,
        ];

        $rule = array_merge($defaultRule, $rule);

        $request = array(
            'method' => 'POST',
            'url' => '/pricing/'.$id.'/rule',
            'content' => $rule);

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    public function testAddPricingPlanRuleAffordabilityWidgetNullValidation()
    {
        // verifies that affordability widget null frequency VAS type pricing rules can be added
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'.$content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanRuleAffordabilityWidgetDailyValidation()
    {
        // verifies that affordability widget dailVAS type pricing rules can be added
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'.$content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanRuleAffordabilityWidgetMonthlyValidation()
    {
        // verifies that affordability widget VAS type pricing rules can be added
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'.$content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanRuleAffordabilityEligibilityValidation()
    {
        // verifies that affordability eligibility VAS type pricing rules can be added
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'.$content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanRuleSMSValidation()
    {
        // verifies that SMS VAS type pricing rules can be added
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'.$content['id'] . '/rule';

        $this->startTest($testData);
    }

    private function getRazorpayPricingPlan(){
        return [
            'plan_name' => 'TestPlan1',
            'procurer' => 'merchant',
            'payment_method' => 'card',
            'payment_method_type' => null,
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 0,
            'fixed_rate' => 10,
            'org_id' => '100000razorpay',
            'type' => 'pricing',
            'international' => 0,
            'amount_range_active' => '0',
            'amount_range_min' => null,
            'amount_range_max' => null,
        ];
    }

    private function getAffordabilityPricingPlan($frequency, $fixed, $method = 'widget'){
        return [
            'plan_name' => 'TestPlan1',
            'feature'   => 'affordability',
            'payment_method' => $method,
            'payment_method_type' => $frequency,
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 0,
            'fixed_rate' => $fixed,
            'type' => 'pricing',
            'international' => 0,
            'amount_range_active' => '0',
            'amount_range_min' => null,
            'amount_range_max' => null,
        ];
    }

    private function testPreReq($planId){
        $this->setDefaultMerchantMethods();

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => $planId]);

        $collectionsServiceConfig = \Config::get('applications.pricing');
        $pwd = $collectionsServiceConfig['secret'];

        $this->ba->appAuth('rzp_'.'test', $pwd);
    }

    public function testGetVASPricingAffordabilityWidgetDaily()
    {
        $this->ba->adminAuth();

        $this->mockCardVault();

        $affordabilityDailyPricingPlan = $this->getAffordabilityPricingPlan('daily', 1000);

        $razorpayPricingPlan = $this->getRazorpayPricingPlan();

        $planId = $this->createPricingPlan($razorpayPricingPlan)['id'];

        $ruleId = $this->addPricingPlanRule($planId, $affordabilityDailyPricingPlan)['id'];

        $this->testPreReq($planId);
        $testData['request']['url'] = '/pricing/vas/fetch';
        $testData['response']['content'][2][0]['pricing_rule']['id'] = $ruleId;

        $this->startTest($testData);
    }

    public function testGetVASPricingAffordabilityWidgetMonthly()
    {
        $this->ba->adminAuth();

        $this->mockCardVault();

        $affordabilityDailyPricingPlan = $this->getAffordabilityPricingPlan('daily', 1000);
        $affordabilityMonthlyPricingPlan = $this->getAffordabilityPricingPlan('monthly', 1200);
        $affordabilityNullPricingPlan = $this->getAffordabilityPricingPlan(null, 800);
        $affordabilityYearlyPricingPlan = $this->getAffordabilityPricingPlan('yearly', 600);

        $razorpayPricingPlan = $this->getRazorpayPricingPlan();

        $planId = $this->createPricingPlan($razorpayPricingPlan)['id'];

        $this->addPricingPlanRule($planId, $affordabilityDailyPricingPlan);
        $ruleId = $this->addPricingPlanRule($planId, $affordabilityMonthlyPricingPlan)['id'];
        $this->addPricingPlanRule($planId, $affordabilityNullPricingPlan);
        $this->addPricingPlanRule($planId, $affordabilityYearlyPricingPlan);

        $this->testPreReq($planId);
        $testData['request']['url'] = '/pricing/vas/fetch';
        $testData['response']['content'][2][0]['pricing_rule']['id'] = $ruleId;

        $this->startTest($testData);
    }

    public function testGetVASPricingAffordabilityEligibility()
    {
        $this->ba->adminAuth();

        $this->mockCardVault();

        $affordabilityDailyPricingPlan = $this->getAffordabilityPricingPlan('daily', 1000);
        $affordabilityEligibilityPricingPlan = $this->getAffordabilityPricingPlan(null, 1500, 'eligibility_api');

        $razorpayPricingPlan = $this->getRazorpayPricingPlan();

        $planId = $this->createPricingPlan($razorpayPricingPlan)['id'];

        $this->addPricingPlanRule($planId, $affordabilityDailyPricingPlan);
        $ruleId = $this->addPricingPlanRule($planId, $affordabilityEligibilityPricingPlan)['id'];


        $this->testPreReq($planId);
        $testData['request']['url'] = '/pricing/vas/fetch';
        $testData['response']['content'][2][0]['pricing_rule']['id'] = $ruleId;

        $this->startTest($testData);
    }

    public function testGetVASPricingSMS()
    {
        $this->ba->adminAuth();

        $this->mockCardVault();

        $smsPricingPlan = [
            'plan_name' => 'TestPlan1',
            'feature'   => 'sms',
            'payment_method' => null,
            'payment_method_type' => null,
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 0,
            'fixed_rate' => 700,
            'type' => 'pricing',
            'international' => 0,
            'amount_range_active' => '0',
            'amount_range_min' => null,
            'amount_range_max' => null,
        ];;

        $razorpayPricingPlan = $this->getRazorpayPricingPlan();

        $planId = $this->createPricingPlan($razorpayPricingPlan)['id'];

        $ruleId = $this->addPricingPlanRule($planId, $smsPricingPlan)['id'];

        $this->testPreReq($planId);
        $testData['request']['url'] = '/pricing/vas/fetch';
        $testData['response']['content'][2][0]['pricing_rule']['id'] = $ruleId;

        $this->startTest($testData);
    }

    public function testGetVASPricingTokenHQ()
    {
        $this->ba->adminAuth();

        $this->mockCardVault();

        $tokenHQPricingPlan = [
            'plan_name' => 'TestPlan1',
            'feature'   => 'token_hq',
            'payment_method' => 'fetch_cryptogram',
            'payment_method_type' => null,
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 200,
            'fixed_rate' => 500,
            'type' => 'pricing',
            'international' => 0,
            'amount_range_active' => '0',
            'amount_range_min' => null,
            'amount_range_max' => null,
        ];;

        $razorpayPricingPlan = $this->getRazorpayPricingPlan();

        $planId = $this->createPricingPlan($razorpayPricingPlan)['id'];

       $ruleId =  $this->addPricingPlanRule($planId, $tokenHQPricingPlan)['id'];

        $this->testPreReq($planId);
        $testData['request']['url'] = '/pricing/vas/fetch';
        $testData['response']['content'][2][0]['pricing_rule']['id'] = $ruleId;

        $this->startTest($testData);
    }

    public function testGetVASPricingMissingUnits()
    {
        $this->ba->adminAuth();

        $this->mockCardVault();

        $smsPricingPlan = [
            'plan_name' => 'TestPlan1',
            'feature'   => 'sms',
            'payment_method' => null,
            'payment_method_type' => null,
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 0,
            'fixed_rate' => 700,
            'type' => 'pricing',
            'international' => 0,
            'amount_range_active' => '0',
            'amount_range_min' => null,
            'amount_range_max' => null,
        ];;

        $razorpayPricingPlan = $this->getRazorpayPricingPlan();

        $planId = $this->createPricingPlan($razorpayPricingPlan)['id'];

        $ruleId = $this->addPricingPlanRule($planId, $smsPricingPlan)['id'];

        $this->testPreReq($planId);
        $testData['request']['url'] = '/pricing/vas/fetch';
        $testData['response']['content'][2][0]['pricing_rule']['id'] = $ruleId;

        $this->startTest($testData);
    }

    public function testGetVASPricingMissingFeature()
    {
        $this->ba->adminAuth();

        $this->mockCardVault();

        $tokenHQPricingPlan = [
            'plan_name' => 'TestPlan1',
            'feature'   => 'token_hq',
            'payment_method' => 'fetch_cryptogram',
            'payment_method_type' => null,
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 200,
            'fixed_rate' => 500,
            'type' => 'pricing',
            'international' => 0,
            'amount_range_active' => '0',
            'amount_range_min' => null,
            'amount_range_max' => null,
        ];;

        $razorpayPricingPlan = $this->getRazorpayPricingPlan();

        $planId = $this->createPricingPlan($razorpayPricingPlan)['id'];

        $this->addPricingPlanRule($planId, $tokenHQPricingPlan);

        $this->testPreReq($planId);
        $testData['request']['url'] = '/pricing/vas/fetch';

        $this->startTest($testData);
    }

    public function testGetVASPricingMissingMethod()
    {
        $this->ba->adminAuth();

        $this->mockCardVault();

        $tokenHQPricingPlan = [
            'plan_name' => 'TestPlan1',
            'feature'   => 'token_hq',
            'payment_method' => 'fetch_cryptogram',
            'payment_method_type' => null,
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 200,
            'fixed_rate' => 500,
            'type' => 'pricing',
            'international' => 0,
            'amount_range_active' => '0',
            'amount_range_min' => null,
            'amount_range_max' => null,
        ];;

        $razorpayPricingPlan = $this->getRazorpayPricingPlan();

        $planId = $this->createPricingPlan($razorpayPricingPlan)['id'];

        $this->addPricingPlanRule($planId, $tokenHQPricingPlan);

        $this->testPreReq($planId);
        $testData['request']['url'] = '/pricing/vas/fetch';

        $this->startTest($testData);
    }

    public function testGetVASPricingMissingMID()
    {
        $this->ba->adminAuth();

        $this->mockCardVault();

        $tokenHQPricingPlan = [
            'plan_name' => 'TestPlan1',
            'feature'   => 'token_hq',
            'payment_method' => 'fetch_cryptogram',
            'payment_method_type' => null,
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 200,
            'fixed_rate' => 500,
            'type' => 'pricing',
            'international' => 0,
            'amount_range_active' => '0',
            'amount_range_min' => null,
            'amount_range_max' => null,
        ];;

        $razorpayPricingPlan = $this->getRazorpayPricingPlan();

        $planId = $this->createPricingPlan($razorpayPricingPlan)['id'];

        $this->addPricingPlanRule($planId, $tokenHQPricingPlan);

        $this->testPreReq($planId);
        $testData['request']['url'] = '/pricing/vas/fetch';

        $this->startTest($testData);
    }

    public function testGetVASPricingAffordabilityWrongMethod()
    {
        $this->ba->adminAuth();

        $this->mockCardVault();

        $affordabilityEligibilityPricingPlan = $this->getAffordabilityPricingPlan(null, 1500, 'eligibility_api');

        $razorpayPricingPlan = $this->getRazorpayPricingPlan();

        $planId = $this->createPricingPlan($razorpayPricingPlan)['id'];

        $this->addPricingPlanRule($planId, $affordabilityEligibilityPricingPlan);


        $this->testPreReq($planId);
        $testData['request']['url'] = '/pricing/vas/fetch';

        $this->startTest($testData);
    }

    public function testGetVASPricingInvalidFeature()
    {
        $this->ba->adminAuth();

        $this->mockCardVault();

        $affordabilityEligibilityPricingPlan = $this->getAffordabilityPricingPlan(null, 1500, 'eligibility_api');

        $razorpayPricingPlan = $this->getRazorpayPricingPlan();

        $planId = $this->createPricingPlan($razorpayPricingPlan)['id'];

        $this->addPricingPlanRule($planId, $affordabilityEligibilityPricingPlan);


        $this->testPreReq($planId);
        $testData['request']['url'] = '/pricing/vas/fetch';

        $this->startTest($testData);
    }
}
