<?php

namespace RZP\Tests\Functional\Merchant;

use Event;
use Carbon\Carbon;

use RZP\Exception;
use RZP\Models\Pricing;
use RZP\Error\ErrorCode;
use RZP\Models\Transaction;
use RZP\Error\PublicErrorCode;
use RZP\Constants\Entity as E;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\Batch\BatchTestTrait;
use RZP\Tests\Functional\Helpers\TerminalTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\FeeBearer;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Models\Base\QueryCache\Constants as CacheConstants;

class PricingTest extends TestCase
{
    use HeimdallTrait;
    use TerminalTrait;
    use BatchTestTrait;

    protected $authToken = null;

    protected $terminalsServiceMock;

    protected $org = null;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/PricingData.php';

        parent::setUp();

        $this->app['config']->set('applications.smart_routing.mock', true);

        $this->ba->adminAuth();
    }

    /**
     * RZP admin (cross org feature enabled) is able to add rules to pricing plan belonging to other organisations too.
     * Here, we are testing the case where RZP admin is adding a rule to pricing plan of SBI organisation.
     */
    public function testAddPricingPlanRuleByRZPAdmin()
    {
        $content = $this->createPricingPlan(['org_id' => Org::SBIN_ORG]);

        $testData['request']['url'] = '/pricing/' . $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddBuyPricingPlanRule()
    {
        $plan = $this->createBuyPricingPlan();

        $testData['request']['url'] = '/buy_pricing/'. $plan['id'] .'/grouped_rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanRuleFeeBearerValidation()
    {
        $content = $this->createPricingPlan([]);

        $merchantAttributes = [
            'fee_bearer'        => 'customer',
            'pricing_plan_id'   => $content['id'],
        ];

        $this->fixtures->create('merchant', $merchantAttributes);

        $testData['request']['url'] = '/pricing/' . $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanRuleFeeBearerValidationMerchantAssociatedIsDynamicFeeBearer()
    {
        $this->testData[__FUNCTION__] = $this->testData['testAddPricingPlanRuleFeeBearerValidation'];

        $content = $this->createPricingPlan([]);

        $merchantAttributes = [
            'fee_bearer'        => 'dynamic',
            'pricing_plan_id'   => $content['id'],
        ];

        $this->fixtures->create('merchant', $merchantAttributes);

        $testData['request']['url'] = '/pricing/' . $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanRuleFeeBearerValidationFailure()
    {
        $content = $this->createPricingPlan([]);

        $merchantAttributes = [
            'fee_bearer'        => 'platform',
            'pricing_plan_id'   => $content['id'],
        ];

        $this->fixtures->create('merchant', $merchantAttributes);

        $testData['request']['url'] = '/pricing/' . $content['id'] . '/rule';

        $this->expectException(\RZP\Exception\BadRequestValidationFailureException::class);

        $this->startTest($testData);
    }

    /**
     * SBI admin (cross org feature disabled) can add rules to pricing plans belonging to SBI organisation only.
     * Here, we are testing the case where SBI admin is trying to add a rule to pricing plan of RZP organisation.
     */
    public function testAddPricingPlanRuleBySBIAdmin()
    {
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->org = $this->getDbEntityById('org', Org::SBIN_ORG);

        $this->authToken = $this->getAuthTokenForOrg($this->org);

        $this->ba->adminAuth('test', $this->authToken, 'org_' . Org::SBIN_ORG);

        $this->startTest($testData);
    }

    public function testAddPricingPlanRuleWithDebitPinFeature()
    {
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanRuleForCoDMethod()
    {
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanRuleForOfflineMethod()
    {
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanRuleForOfflineMethodWithNetworkSpecified()
    {
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanRuleWithReceiver()
    {
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanRuleWithProcurer()
    {
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanRuleWithProcurerMerchantAndMethodNull()
    {
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanRuleWithProcurerRazorpayAndMethodNull()
    {
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanRuleWithFeatureOptimizerAndMethodNull()
    {
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanRuleWithFeatureOptimizerAndProcurerNotNull()
    {
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testDuplicateReceiverRule()
    {
        $content = $this->createPricingPlan(['receiver_type' => 'qr_code']);

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testBulkPricingPlan()
    {
        // also asserts if multiple rules of same type can be added without error
        $this->startTest();
    }

    public function testStringifiedPricingPlan()
    {
        // assertion are inherent
        // verifies the flow when stringified json data is sent for processing
        $this->startTest();
    }

    public function testBulkPricingPlanOfMultipleTypes()
    {
        $this->startTest();
    }

    public function testEmptyBulkPricingPlan()
    {
        $this->startTest();
    }

    public function testEmptyPlanWithNoRules()
    {
        $this->startTest();
    }

    public function testDuplicateBulkPricingPlan()
    {
        $this->startTest();
    }

    /**
     * RZP admin due to cross org feature enabled can create pricing plan for all orgs like SBI and HDFC.
     * Here, we are testing the case where RZP admin is creating a pricing plan for SBI organisation.
     */
    public function testCreatePricingPlanByRZPAdmin()
    {
        $this->ba->adminAuth('test', null, 'org_' . Org::SBIN_ORG);

        $this->startTest();
    }

    public function testCreateBuyPricingPlan()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testCreatePricingPlanWithMinAndMaxFee()
    {
        $this->startTest();
    }

    public function testCreatePricingPlanWithInvalidMinAndMaxFee()
    {
        $this->startTest();
    }

    public function testAddPricingPlanBankTransferRule()
    {
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'.$content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanBankTransferRuleWithoutMaxFee()
    {
        $this->markTestSkipped('Skipped the validation to allow 0-pricing for bank transfers.');

        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'.$content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanBankTransferRuleWithoutPercentRate()
    {
        $this->markTestSkipped('Skipped the validation to allow 0-pricing for bank transfers.');

        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'.$content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanNBRule()
    {
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'.$content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddingPricingPlanNBRuleDifferentTypes()
    {
        // verifies that commission type pricing rules can be added
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'.$content['id'] . '/rule';

        $this->startTest($testData);
    }

    /**
     * check that commission plan can be added to rzp org
     */
    public function testAddCommissionPlanNBRule()
    {
        $content = $this->createPricingPlan(['type' => 'commission']);

        $testData['request']['url'] = '/pricing/'.$content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanNBRuleWithReceiver()
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

    public function testAddPricingPlanEmi()
    {
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'.$content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanEmiDebit()
    {
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'.$content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanFundAccountValidationRule()
    {
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'.$content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanEmandateRule()
    {
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'.$content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanEmandateRegistrationRule()
    {
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'.$content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanNachRegistrationRule()
    {
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'.$content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanEmandateDebitAadhaarRule()
    {
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'.$content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanNachDebitRule()
    {
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'.$content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanEmandatePercentageRateRule()
    {
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'.$content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanEmandatePercentageRateRuleForSubscription()
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

    /*
     * in this test we create two merchants who are customer fee bearer
     * and share a pricing plan. this pricing plan has all rules fee_bearer=customer
     * we try to add a rule in which fee_bearer=platform
     * we assert that that this addition of rule is not allowed
     */
    public function testAddPricingRuleWithFeeBearerMismatch()
    {
        $plan = $this->createPricingPlan();

        $merchantAttributes = [
            'fee_bearer'        => 'customer',
            'pricing_plan_id'   => $plan['id'],
        ];

        $this->fixtures->create('merchant', $merchantAttributes);

        $this->fixtures->create('merchant', $merchantAttributes);

        $testData['request']['url'] = '/pricing/'. $plan['id'] . '/rule';

        $this->ba->adminAuth();

        $response = $this->startTest($testData);

        $this->assertStringContainsString('Unable to add rule to plan', $response['error']['description']);
    }

    public function testAddPricingRuleEarlySalary()
    {
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingRuleWalnut369()
    {
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testUpdatePricingPlanRule()
    {
        $content = $this->createPricingPlan2();

        $rule = $this->getEntityById('pricing', $content['rules']['0']['id'], true);

        $this->assertEquals($rule['deleted_at'], null);

        $testData['request']['url'] = '/pricing/' . $content['id'] . '/rule/' . $rule['id'];

        $this->startTest($testData);

        $rule = Pricing\Entity::withTrashed()->findOrFail($rule['id']);

        $this->assertNotNull($rule['deleted_at']);
    }

    public function testUpdateBuyPricingPlanRule()
    {
        $content = $this->createBuyPricingPlan();

        $rule = $content['rules']['0'];

        $this->assertEquals($rule['deleted_at'], null);

        $testData['request']['url'] = '/buy_pricing/' . $content['id'] . '/rule/' . $rule['id'];

        $this->startTest($testData);

        $rule = Pricing\Entity::withTrashed()->findOrFail($rule['id']);

        $this->assertNotNull($rule['deleted_at']);
    }

    public function testUpdatePricingPlanRuleEmptyProcurer()
    {
        $content = $this->createPricingPlan2();

        $rule = $this->getEntityById('pricing', $content['rules']['0']['id'], true);

        $this->assertEquals($rule['deleted_at'], null);

        $testData['request']['url'] = '/pricing/' . $content['id'] . '/rule/' . $rule['id'];

        $this->startTest($testData);

        $rule = Pricing\Entity::withTrashed()->findOrFail($rule['id']);

        $this->assertNull($rule['deleted_at']);
    }

    public function testUpdateCommissionRule()
    {
        $content = $this->createPricingPlan2(['type' => 'commission']);

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule/' . $content['rules'][0]['id'];

        $this->startTest($testData);
    }

    /** We are removing channel from free payout pricing rules for current account */
    public function testUpdateFreePayoutRule()
    {
        $pricingPlanData = [
            'plan_name'           => 'Banking default plan 2',
            'payment_method'      => 'fund_transfer',
            'plan_id'             => '1To98voDY05ueB',
            'payouts_filter'      => 'free_payout',
            'account_type'        => 'direct',
            'percent_rate'        => '275',
            'max_fee'             => 0,
            'type'                => 'pricing',
            'product'             => 'banking',
            'auth_type'           => 'proxy',
            'channel'             => 'rbl',
            'min_fee'             => 0,
            'percent_rate'        => 0,
        ];

        $this->ba->adminAuth();

        $plan = $this->createPricingPlan($pricingPlanData);

        $testData['request']['url'] = '/pricing/' . $plan['id'] . '/rule';

        $testData['request']['content'] =  [
            'product'             => 'banking',
            'feature'             => 'payout',
            'payment_method'      => 'fund_transfer',
            'payouts_filter'      => 'free_payout',
            'account_type'        => 'direct',
            'type'                => 'pricing',
            'auth_type'           => 'proxy',
            'channel'             => 'rbl',
            'percent_rate'        => 0,
        ];

        $this->startTest($testData);

        $pricingRule = $this->getDbLastEntity('pricing')->toArray();

        $this->ba->adminAuth();

        $testData['request']['url'] = '/pricing/' . $plan['id'] . '/rule/' . $pricingRule['id'];

        $testData['request']['content'] = [
            'channel'       => null,
            'percent_rate'  => 0,
        ];

        $testData['request']['method'] = 'patch';

        $this->startTest($testData);

        $pricingRule = $this->getDbLastEntity('pricing')->toArray();

        $this->assertEquals('', $pricingRule['channel']);
    }

    /*
     * in this test we create two merchants who are customer fee bearer
     * and share a pricing plan. this pricing plan has all rules fee_bearer=customer
     * we try to update a rule in which fee_bearer=platform
     * we assert that that this addition of rule is not allowed
     */
    public function testUpdatePricingPlanFeeBearerMismatch()
    {
        $content = $this->createPricingPlan2(['fee_bearer' => 'customer']);

        $merchantAttributes = [
            'fee_bearer'        => 'customer',
            'pricing_plan_id'   => $content['id'],
        ];

        $this->fixtures->create('merchant', $merchantAttributes);

        $this->fixtures->create('merchant', $merchantAttributes);

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule/' . $content['rules'][0]['id'];

        $this->startTest($testData);


    }
    /**
     * RZP admin will be able to update the pricing plan for SBI or any other organisation.
     * Here, we are testing the case where RZP admin is updating SBI pricing plan rule.
     */
    public function testUpdatePricingPlanRuleByRZPAdmin()
    {
        $content = $this->createPricingPlan2(
            [
                'org_id' => Org::SBIN_ORG,
            ],
            [
                'X-Cross-Org-Id' => 'org_' . Org::SBIN_ORG
            ]
        );

        $rule = $this->getEntityById('pricing', $content['rules']['0']['id'], true);

        $this->assertEquals($rule['deleted_at'], null);

        $testData['request']['url'] = '/pricing/' . $content['id'] . '/rule/' . $rule['id'];

        $this->startTest($testData);

        $rule = Pricing\Entity::withTrashed()->findOrFail($rule['id']);

        $this->assertNotNull($rule['deleted_at']);
    }

    public function testUpdatePricingPlanRuleChannelFailure()
    {
        $content = $this->createPricingPlan2(
            [
                'org_id' => Org::SBIN_ORG,
            ],
            [
                'X-Cross-Org-Id' => 'org_' . Org::SBIN_ORG
            ]
        );

        $rule = $this->getEntityById('pricing', $content['rules']['0']['id'], true);

        $this->assertEquals($rule['deleted_at'], null);

        $testData['request']['url'] = '/pricing/' . $content['id'] . '/rule/' . $rule['id'];

        $this->startTest($testData);
    }

    /**
     * Asserts that commission plan cannot be added for non-rzp org
     */
    public function testCreateCommissionPlanBySBIOrg()
    {
        $this->ba->adminAuth('test', null, null, null, 'org_' . Org::SBIN_ORG);

        $this->startTest();
    }

    /**
     * SBI admin will not be able to update any pricing plan rule belonging to any other org.
     * Here, we are testing the case where SBI admin is trying to update the pricing plan rule of RZP organisation.
     */
    public function testUpdatePricingPlanRuleBySBIAdmin()
    {
        $content = $this->createPricingPlan2();

        $rule = $this->getEntityById('pricing', $content['rules']['0']['id'], true);

        $this->assertEquals($rule['deleted_at'], null);

        $testData['request']['url'] = '/pricing/' . $content['id'] . '/rule/' . $rule['id'];

        $this->org = $this->getDbEntityById('org', Org::SBIN_ORG);

        $this->authToken = $this->getAuthTokenForOrg($this->org);

        $this->ba->adminAuth('test', $this->authToken, 'org_' . Org::SBIN_ORG);

        $this->startTest($testData);
    }

    public function testGetPricingPlan()
    {
        $id = $this->createPricingPlan2()['id'];

        $testData['request']['url']    = '/pricing/' . $id;
        $testData['request']['method'] = 'GET';

        $this->ba->adminAuth('test');
        $this->startTest($testData);

        $this->ba->adminAuth('live');
        $this->startTest($testData);
    }

    /**
     * RZP admin has access to pricing plans of all orgs due to cross route feature enabled.
     */
    public function testGetPricingPlanByRZPAdmin()
    {
        $id = $this->createPricingPlan2(
            [
                'org_id'  => Org::SBIN_ORG,
                'plan_id' => '1ycviEdCgurrFY'
            ],
            [
                'X-Cross-Org-Id' => "org_" . Org::SBIN_ORG
            ]
        )['id'];

        $testData['request']['url']    = '/pricing/' . $id;
        $testData['request']['method'] = 'GET';

        $this->ba->adminAuth('test');
        $this->startTest($testData);

        $this->ba->adminAuth('live');
        $this->startTest($testData);
    }

    public function testGetBuyPricingPlan()
    {
        $id = $this->createBuyPricingPlan()['id'];

        $testData['request']['url']    = '/buy_pricing/'. $id;
        $testData['request']['method'] = 'GET';

        $this->ba->adminAuth('test');
        $this->startTest($testData);

        $this->ba->adminAuth('live');

        $this->startTest($testData);
    }

    /**
     * SBI admin has access to pricing plans of only SBI organisation due to cross route feature disabled.
     */
    public function testGetPricingPlanBySBIAdmin()
    {
        $id = $this->createPricingPlan2()['id'];

        $testData['request']['url']    = '/pricing/' . $id;
        $testData['request']['method'] = 'GET';

        $this->org = $this->getDbEntityById('org', Org::SBIN_ORG);

        $this->authToken = $this->getAuthTokenForOrg($this->org);

        $this->ba->adminAuth('test', $this->authToken, 'org_' . Org::SBIN_ORG);
        $this->startTest($testData);

        $this->ba->adminAuth('live', $this->authToken, 'org_' . Org::SBIN_ORG);
        $this->startTest($testData);
    }

    public function testGetPricingNetworks()
    {
        $this->ba->adminAuth();

        $response = $this->startTest();

        $this->assertNotNull($response['bank']);
        $this->assertNotNull($response['card']);
        $this->assertNotNull($response['wallet']);
        $this->assertNotNull($response['emandate']);
        $this->assertNotNull($response['upi']);
        $this->assertNotNull($response['emi']);
        $this->assertNotNull($response['nach']);
        $this->assertNotNull($response['paylater']);
        $this->assertNotNull($response['cardless_emi']);

        $this->assertNotEquals(count($response['bank']), 0);
        $this->assertNotEquals(count($response['card']), 0);
        $this->assertNotEquals(count($response['wallet']), 0);
        $this->assertNotEquals(count($response['emandate']), 0);
        $this->assertNotEquals(count($response['upi']), 0);
        $this->assertNotEquals(count($response['emi']), 0);
        $this->assertNotEquals(count($response['nach']), 0);
        $this->assertNotEquals(count($response['paylater']), 0);
        $this->assertNotEquals(count($response['cardless_emi']), 0);

        $this->assertEquals($response['wallet']['boost'], "Boost");
        $this->assertEquals($response['wallet']['mcash'], "MCash");
        $this->assertEquals($response['wallet']['touchngo'], "TouchNGo");
        $this->assertEquals($response['wallet']['grabpay'], "GrabPay");
    }

    public function testGetPricingPlans()
    {
        $this->createPricingPlan();
        $this->createPricingPlan2();

        $this->ba->adminAuth('test');
        $this->startTest();

        $this->ba->adminAuth('live');
        $this->startTest();
    }

    public function testGetPricingPlansTypeFilter()
    {
        $this->createPricingPlan();
        $this->createPricingPlan2(['type' => 'commission']);

        $this->ba->adminAuth('test');
        $this->startTest();

        $this->ba->adminAuth('live');
        $this->startTest();
    }

    /**
     * RZP admin has access to pricing plans of all orgs due to cross route feature enabled.
     */
    public function testGetPricingPlansByRZPAdmin()
    {
        $this->createPricingPlan();
        $this->createPricingPlan2(
            [
                'org_id'  => Org::SBIN_ORG,
                'plan_id' => '1ycviEdCgurrFY'
            ],
            [
                'X-Cross-Org-Id' => "org_" . Org::SBIN_ORG
            ]
        );

        $this->ba->adminAuth('test');
        $this->startTest();

        $this->ba->adminAuth('live');
        $this->startTest();
    }

    /**
     * SBI admin will have access to pricing plans of only SBI organisation because cross org feature is disabled for
     * SBI organisation.
     */
    public function testGetPricingPlansBySBIAdmin()
    {
        // Pricing plan Belonging to RZP organisation
        $this->createPricingPlan();

        // Pricing plan belonging to SBI organisation
        $this->createPricingPlan2(
            [
                'org_id'  => Org::SBIN_ORG,
                'plan_id' => '1ycviEdCgurrFY'
            ],
            [
                'X-Cross-Org-Id' => "org_" . Org::SBIN_ORG
            ]
        );

        $this->org = $this->getDbEntityById('org', Org::SBIN_ORG);

        $this->authToken = $this->getAuthTokenForOrg($this->org);

        $this->ba->adminAuth('test', $this->authToken, 'org_' . Org::SBIN_ORG);

        $this->startTest();

        $this->ba->adminAuth('live', $this->authToken, 'org_' . Org::SBIN_ORG);
        $this->startTest();
    }

    public function testGetPricingPlansGrouping()
    {
        $this->ba->adminAuth();

        $content = $this->createPricingPlan();
        $this->createPricingPlan2();

        $this->addPricingPlanRule($content['id']);

        $this->ba->adminAuth('test');
        $this->startTest();

        $this->ba->adminAuth('live');
        $this->startTest();
    }

    public function testGetBuyPricingPlansGrouping()
    {
        $this->ba->adminAuth();

        $id = $this->createBuyPricingPlan()['id'];

        $this->terminalsServiceMock = $this->getTerminalsServiceMock();

        $this->mockTerminalsServiceSendRequest(function() use ($id)
        {
            $response =  new \WpOrg\Requests\Response;

            $data['plan_id'] = $id;

            $data['count'] = 10;

            $responseData = ['data' => [$data]];

            $response->body = json_encode($responseData);

            return $response;
        });

        $this->ba->adminAuth('test');
        $this->startTest();

        $this->ba->adminAuth('live');
        $this->startTest();
    }

    public function testGetPricingPlansGroupingPagination()
    {
        $this->ba->adminAuth();

        $this->createPricingPlan();
        $this->createPricingPlan2();

        $request = [
            'method' => 'get',
            'url'    => '/pricing/merchants?count=5&skip=8',
        ];

        $response = $this->makeRequestAndGetContent($request);

        /* the unpaginated result has 12 rows. we are doing count=5 and skip=8 and expect just 4 rows*/

        $this->assertEquals(4, count($response));
    }

    public function testGetPricingPlansGroupingPlanNameParam()
    {
        $this->ba->adminAuth();

        $this->createPricingPlan();
        $this->createPricingPlan2();

        // found case
        $request = [
            'method' => 'get',
            'url'    => '/pricing/merchants?plan_name=TestPlan1',
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(1, count($response));
        $this->assertEquals('TestPlan1', $response['0']['plan_name']);

        //not found case
        $request['url'] = '/pricing/merchants?plan_name=InvalidPlanName';

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(0, count($response));

    }

    public function testGetPricingPlansGroupingPlanIdParam()
    {
        $this->ba->adminAuth();

        $this->createPricingPlan();
        $this->createPricingPlan2();

        // found case
        $request = [
            'method' => 'get',
            'url'    => '/pricing/merchants?plan_id=1ycviEdCgurrFI',
        ];

        $response = $this->makeRequestAndGetContent($request);

        //in db plan_id=1ycviEdCgurrFI corresponds to plan_name='TestPlan1'
        $this->assertEquals(1, count($response));
        $this->assertEquals('TestPlan1', $response['0']['plan_name']);

        //not found case
        $request['url'] = '/pricing/merchants?plan_id=0123456789abcd';

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(0, count($response));

    }


    public function testGetMerchantPlansWithFilters()
    {
        $this->createPricingPlan();

        $this->createCommissionPlan();

        $this->ba->adminAuth();

        $this->startTest();
    }

    /**
     * RZP admin has access to pricing plans of all orgs due to cross route feature enabled.
     */
    public function testGetPricingPlansGroupingByRZPAdmin()
    {
        $this->ba->adminAuth();

        $this->createPricingPlan();

        $this->createPricingPlan2(
            [
                'org_id'  => Org::SBIN_ORG,
            ],
            [
                'X-Cross-Org-Id' => "org_" . Org::SBIN_ORG
            ]
        );

        $this->ba->adminAuth('test');
        $this->startTest();

        $this->ba->adminAuth('live');
        $this->startTest();
    }

    /**
     * SBI admin will have access to pricing plans of only SBI organisation because cross org feature is disabled for
     * SBI organisation.
     */
    public function testGetPricingPlansGroupingBySBIAdmin()
    {
        $this->ba->adminAuth();

        $this->createPricingPlan();

        $this->createPricingPlan2(
            [
                'org_id'  => Org::SBIN_ORG,
                'plan_id' => '1ycviEdCgurrFY'
            ],
            [
                'X-Cross-Org-Id' => 'org_' . Org::SBIN_ORG
            ]
        );

        $this->org = $this->getDbEntityById('org', Org::SBIN_ORG);

        $this->authToken = $this->getAuthTokenForOrg($this->org);

        $this->ba->adminAuth('test', $this->authToken, 'org_' . Org::SBIN_ORG);
        $this->startTest();

        $this->ba->adminAuth('live', $this->authToken, 'org_' . Org::SBIN_ORG);
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

    public function testAssignPricingPlanFeeBearerMismatch()
    {
        /*
         * default merchant is platform fee bearer
         * we are creating a plan whose fee_bearer is customer
         * we are trying to assign above plan to default merchant
         * this is expected to fail
         */

        $id = $this->createPricingPlan(['fee_bearer' => 'customer'])['id'];

        $this->setDefaultMerchantMethods();

        $testData['request']['content']['pricing_plan_id'] = $id;

        $this->startTest($testData);

        /*
         * now the other way round
         */
        $id = $this->createPricingPlan(['fee_bearer' => 'customer'])['id'];

        $this->setDefaultMerchantMethods();

        $this->fixtures->merchant->setFeeBearer('platform');

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
                'pricing_plan_id' => '1ycviEdCgurrFI',
                'org_id' => '100000razorpay'));

        $this->ba->adminAuth('test', null, 'org_' . Org::RZP_ORG);

        $this->startTest();
    }
    //
    public function testAddInternationalPricingPlanRule()
    {
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->ba->adminAuth();

        return $this->startTest($testData);
    }

    public function testAddAmountRangePricingPlanRule()
    {
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->ba->adminAuth();

        return $this->startTest($testData);
    }

    public function testAddAmountRangePricingPlanRuleOverlap()
    {
        $content = $this->createAmountRangePricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->ba->adminAuth();

        return $this->startTest($testData);
    }

    public function testAddAmountRangePricingPlanRuleDuplicate()
    {
        $content = $this->createAmountRangePricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->ba->adminAuth();

        $this->startTest($testData);
    }

    public function testAddDuplicateInternationalPricingPlanRule()
    {
        $content = $this->testAddInternationalPricingPlanRule();

        $testData['request']['url'] = '/pricing/'. $content['plan_id'] . '/rule';

        $this->ba->adminAuth();

        $this->startTest($testData);

        $this->startTest($testData);
    }

    public function testAddInternationalPricingPlanRuleForNonCardMethod()
    {
        $this->ba->adminAuth();

        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->ba->adminAuth();

        return $this->startTest($testData);
    }

    public function testAddInternationalPricingPlanRuleWithExtraFields()
    {
        $this->ba->adminAuth();

        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        return $this->startTest($testData);
    }

    public function testAddDuplicateWalletPricingRule()
    {
        $testData['request']['url'] = '/pricing/'. '1hDYlICobzOCYt' . '/rule';

        $this->ba->adminAuth();

        $this->startTest($testData);
    }

    public function testAddBulkPlanRules()
    {
        $content = $this->assignPricingPlanToMerchant();

        $this->ba->batchAppAuth();

        $response = $this->startTest();

        $esAutomaticPricingRules = $this->getDbEntities('pricing', [
                                                            'feature'        => 'esautomatic',
                                                            'product'        => 'primary',
                                                            'plan_id'        => $content['id']
                                                        ])->toArray();

        foreach ($esAutomaticPricingRules as $esAutomaticPricingRule)
        {
            $this->assertEquals(FeeBearer::PLATFORM, $esAutomaticPricingRule['fee_bearer']);
        }

        $this->assertEquals($response['items'][0]['plan_id'],$content['id']);
    }

    public function testCalculateBuyPricingCost()
    {
        $plan = $this->createBuyPricingPlan();

        $payment = $this->getDefaultPaymentArray();

        $payment = array_merge($payment, [
            'id'     => 'fourteenDigits',
            'method' => 'card',
            'card'   => array_merge($payment['card'], [
                'network' => 'Visa',
                'type'    => 'debit',
            ])
        ]);

        $testData['request']['content'] = [
            'terminals' => [
                [
                    'terminal_id' => 'fourteenDigits',
                    'plan_id'     => $plan['id'],
                    'gateway'     => 'hdfc',
                ],
                [
                    'terminal_id' => 'fourteenDigits',
                    'plan_id'     => $plan['id'],
                    'gateway'     => 'fulcrum',
                ],
            ],
            'payment' => $payment,
        ];

        $testData['response']['content']['terminals'][0]['plan_id'] = $plan['id'];

        $testData['response']['content']['terminals'][1]['plan_id'] = $plan['id'];

        $this->ba->appAuth();

        $this->startTest($testData);
    }

    public function testGetBuyPricingPlansByIds()
    {
        $this->markTestSkipped();

        config(['app.query_cache.mock' => false]);

        Event::fake(false);

        $plan = $this->createBuyPricingPlan();

        $planId = $plan['id'];

        $pricingRepo = new Pricing\Repository();

        Event::assertDispatched(KeyForgotten::class);

        // setting cache.
        $currentPlans = $pricingRepo->getBuyPricingPlansByIds([$planId])->toArray();

        //
        // Asserts that key is not present initially in cache
        //
        Event::assertDispatched(CacheMissed::class, function ($e) use ($planId)
        {
            foreach ($e->tags as $tag)
            {
                if (starts_with($tag, E::PRICING) === true)
                {
                    $this->assertEquals('pricing_' . $planId . '_buy_pricing', $tag);
                }
            }
            return true;
        });

        //
        // Asserts that key is not present initially in cache
        //
        Event::assertDispatched(KeyWritten::class, function ($e) use ($planId)
        {
            foreach ($e->tags as $tag)
            {
                if (starts_with($tag, E::PRICING) === true)
                {
                    $this->assertEquals('pricing_' . $planId . '_buy_pricing', $tag);
                }
            }
            return true;
        });

        //
        // Asserts cache should not have been hit the first time
        //
        Event::assertNotDispatched(CacheHit::class, function ($e) use ($planId)
        {
            foreach ($e->tags as $tag)
            {
                $this->assertNotEquals($tag, 'pricing_' . $planId . '_buy_pricing');
            }
            return false;
        });

        $this->assertEquals($currentPlans[0]['percent_rate'], 10);

        $pricingRepo->getBuyPricingPlansByIds([$planId])->toArray();

        //
        // Asserts that key is found in cache on subsequent attempts
        //
        Event::assertDispatched(CacheHit::class, function ($e) use ($planId)
        {
            foreach ($e->tags as $tag)
            {
                if (starts_with($tag, E::PRICING) === true)
                {
                    $this->assertEquals('pricing_' . $planId . '_buy_pricing', $tag);
                }
            }
            return true;
        });

        (new Pricing\Core())->editPlanRule($planId, $plan['rules']['0']['id'], ['percent_rate' => 450]);

        $updatedPlan = $pricingRepo->getBuyPricingPlansByIds([$planId])->toArray();

        $this->assertEquals($updatedPlan[0]['percent_rate'], 450);

        //
        // Asserts that key is cleared in cache after update
        //
        Event::assertDispatched(CacheMissed::class, function ($e) use ($planId)
        {
            foreach ($e->tags as $tag)
            {
                if (starts_with($tag, E::PRICING) === true)
                {
                    $this->assertStringContainsString($planId, $tag);
                }
            }
            return true;
        });
    }

    public function testAddBulkBuyPlanRules()
    {
        $this->ba->batchAppAuth();

        $this->startTest();
    }

    public function testValidateBulkBuyPlanRulesBatchSuccess()
    {
        $this->ba->adminAuth();

        $entries = $this->getDefaultFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    public function testValidateBulkBuyPlanRulesBatchFailure()
    {
        $this->ba->adminAuth();

        $entries = $this->getDefaultFileEntries();

        // Setting max to make range incomplete.
        $entries[0]['amount_range_max'] = '1000';

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $response = $this->startTest();

        $mai = json_decode($response['error']['description'])[0];

        foreach ($mai as $key => $value)
        {
            $this->assertEquals($entries[0][$key], $value);
        }
    }

    public function testAddBulkEsPlanRulesForCustomerFeeBearerMerchant()
    {
        $content = $this->assignPricingPlanToMerchant();

        $this->ba->batchAppAuth();

        $this->fixtures->merchant->setFeeBearer(FeeBearer::CUSTOMER);

        $response = $this->startTest();

        $esAutomaticPricingRules = $this->getDbEntities('pricing', [
                                                            'feature'        => 'esautomatic',
                                                            'product'        => 'primary',
                                                            'plan_id'        => $content['id']
                                                        ])->toArray();

        foreach ($esAutomaticPricingRules as $esAutomaticPricingRule)
        {
            $this->assertEquals(FeeBearer::CUSTOMER, $esAutomaticPricingRule['fee_bearer']);
        }

        $this->assertEquals($response['items'][0]['plan_id'], $content['id']);
    }

    public function testAddBulkPlanRulesReplicatePlan()
    {
        $content = $this->assignPricingPlanToMerchant();

        $this->fixtures->merchant->edit('1ApiFeeAccount', ['pricing_plan_id' => $content['id']]);

        $this->ba->batchAppAuth();

        $response = $this->startTest();

        $this->assertNotEquals($response['items'][0]['plan_id'],$content['id']);
        $this->assertEquals($response['items'][0]['plan_id'],$response['items'][1]['plan_id']);
    }

    // this test makes sure that pricing plan isn't replicated if the pricing rule that we're adding is same as before.
    public function testAddBulkPlanRulesNoReplication()
    {
        $content = $this->assignPricingPlanToMerchant();

        $this->fixtures->merchant->edit('1ApiFeeAccount', ['pricing_plan_id' => $content['id']]);

        $this->ba->batchAppAuth();

        $response = $this->startTest();
    }

    public function testDeletePricingPlanRuleForce()
    {

        $content = $this->startTest();
    }

    public function testDeleteBuyPricingPlanRuleForce()
    {
        $plan = $this->createBuyPricingPlan();

        $testData['request']['url'] = '/buy_pricing/'. $plan['id'] .'/rule/'. $plan['rules'][0]['id'] . '/force';

        $this->startTest($testData);
    }

    public function startTest($testDataToReplace = array())
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        $this->replaceValuesRecursively($testData, $testDataToReplace);

        //s("running request flow");
        return $this->runRequestResponseFlow($testData);
    }

    public function testOrgIdPricing()
    {
        $fetchTestData = [
            'request' => [
                'content' => [
                ],
            ],
            'response' => [
                'content' => [
                ]
            ],
        ];
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';
        $this->startTest($testData);

        $fetchTestData['request']['url'] = '/pricing/'.$content['id'];
        $fetchTestData['request']['method'] = 'GET';

        $response = $this->runRequestResponseFlow($fetchTestData);

        $this->assertNotEmpty($response);

        $org = $this->fixtures->org->createHdfcOrg();
        $this->ba->adminAuth('test', 'SuperSecretTokenForRazorpaySuprHdfcbToken', $org->getPublicId(), 'hdfcbank.com');

         // fetching the same entity
        $response = $this->runRequestResponseFlow($fetchTestData);
         // no data;
        $this->assertNotEmpty($response);

        $content = $this->createPricingPlan(['org_id' => $org->getId(), 'plan_id' => '1ycviEdCguraFI']);

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';
        $this->startTest($testData);

        $fetchTestData['request']['url'] = '/pricing/'.$content['id'];
        $fetchTestData['request']['method'] = 'GET';
        $response = $this->runRequestResponseFlow($fetchTestData);
        $this->assertNotEmpty($response);
        // org changed for the admin
        $this->ba->adminAuth();
        // fetching the same entity
        $response = $this->runRequestResponseFlow($fetchTestData);
         // no data;
        $this->assertNotEmpty($response);
    }

    protected function assignPricingPlanToMerchant()
    {
        $id = $this->createPricingPlan()['id'];

        $this->setDefaultMerchantMethods();

        return $this->merchantAssignPricingPlan($id, '10000000000000');
    }

    protected function createCommissionPlan($pricingPlan = [])
    {
        $defaultPricingPlan = [
            'plan_name'           => 'TestPlan9',
            'payment_method'      => 'card',
            'payment_method_type' => 'credit',
            'payment_network'     => 'DICL',
            'payment_issuer'      => 'HDFC',
            'percent_rate'        => 1000,
            'fixed_rate'          => 0,
            'org_id'              => '100000razorpay',
            'type'                => 'commission',
        ];

        $pricingPlan = array_merge($defaultPricingPlan, $pricingPlan);

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $plan = $plan->toArray();

        $plan['id'] = $plan['plan_id'];

        return $plan;
    }

    protected function getDefaultFileEntries()
    {
        return [
            [
                'plan_name'             => 'testName',
                'payment_method'        => 'card',
                'payment_method_type'   => 'credit',
                'payment_method_subtype'=> 'business',
                'receiver_type'         => '',
                'gateway'               => 'hdfc',
                'payment_issuer'        => 'hdfc',
                'payment_network'       => 'Visa,MasterCard',
                'percent_rate'          => '10',
                'international'         => '0',
                'emi_duration'          => '',
                'amount_range_min'      => '0',
                'amount_range_max'      => '',
                'fixed_rate'            => '5',
                'min_fee'               => '2',
                'max_fee'               => '20',
                'procurer'              => 'razorpay',
            ],
        ];
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

    protected function createPricingPlan2(array $pricingPlanData = [], array $adminHeaders = null)
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
            'type'                => 'pricing',
            ];

        $planData = array_merge($planData, $pricingPlanData);

        $type = $planData['type'];

        $pricingData = [
                [
                    'payment_method'      => 'card',
                    'payment_method_type' => 'credit',
                    'payment_network'     => 'DICL',
                    'payment_issuer'      => 'ICIC',
                    'percent_rate'        => 250,
                    'type'                => $type,
                ],
                [
                    'payment_method'      => 'card',
                    'payment_method_type' => 'debit',
                    'payment_network'     => 'MAES',
                    'payment_issuer'      => 'PUNB',
                    'percent_rate'        => 250,
                    'type'                => $type,
                ],
                [
                    'payment_method'      => 'card',
                    'payment_method_type' => 'credit',
                    'payment_network'     => 'MC',
                    'payment_issuer'      => 'AXIS',
                    'fixed_rate'          => 3000,
                    'type'                => $type,
                ],
            ];

        $plan = $this->createPricingPlan($planData);

        $pricingPlanId = $plan['id'];

        $this->ba->adminAuth();

        if ($adminHeaders != null)
        {
            $this->ba->setAdminHeaders($adminHeaders);
        }

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
        $this->ba->adminAuth();

        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanRuleWithFeatureESAutomatic()
    {
        $this->ba->adminAuth();

        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanRuleWithFeatureCred()
    {
        $this->ba->adminAuth();

        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testQueryCacheHitForPricingPlan()
    {
        config(['app.query_cache.mock' => false]);

        Event::fake();

        $payment = $this->getDefaultPaymentArray();

        $this->doAuthPayment($payment);

        //
        // Asserts that key is not present initially in cache
        //
        Event::assertDispatched(CacheMissed::class, function ($e)
        {
            foreach ($e->tags as $tag)
            {
                if (starts_with($tag, E::PRICING) === true)
                {
                    $this->assertStringContainsString(E::PRICING, $tag);
                }
            }
            return true;
        });
        //
        // Asserts that key is inserted into cache
        //
        Event::assertDispatched(KeyWritten::class, function ($e)
        {
            foreach ($e->tags as $tag)
            {
                if (starts_with($tag, E::PRICING) === true)
                {
                    $this->assertStringContainsString(E::PRICING, $tag);
                }
            }
            return true;
        });

        $this->doAuthPayment($payment);

        //
        // Asserts that key is found in cache on subsequent attempts
        //
        Event::assertDispatched(CacheHit::class, function ($e)
        {
            foreach ($e->tags as $tag)
            {
                if (starts_with($tag, E::PRICING) === true)
                {
                    $this->assertStringContainsString(
                        implode(':', [
                            CacheConstants::QUERY_CACHE_PREFIX,
                            CacheConstants::DEFAULT_QUERY_CACHE_VERSION,
                            E::PRICING]),
                        $e->key
                    );
                }
            }
            return true;
        });
    }

    public function testAddPricingPlanRuleWithFeatureRefund()
    {
        $this->ba->adminAuth();

        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testCreatePaymentEmiMethodTypePricing()
    {
        $this->mockCardVault();

        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->iin->create([
            'iin'      => '555555',
            'country'  => 'IN',
            'network'  => 'MasterCard',
            'type'     => 'credit',
            'sub_type' => 'business',
            'issuer'   => 'HDFC',
            'emi'      => 1,
        ]);

        $defaultPricingPlan = [
            'plan_name'      => 'TestPlan1',
            'payment_method' => 'emi',
            'percent_rate'   => 1000,
            'fixed_rate'     => 0,
            'payment_issuer' => 'HDFC',
            'payment_network' => 'MC',
            'emi_duration'   => 9,
            'org_id'         => '10000000000000',
            'type'           => 'pricing',
        ];

        $plan = $this->createPricingPlan($defaultPricingPlan);

        $this->fixtures->create('emi_plan:default_emi_plans');

        $this->fixtures->create('terminal:shared_hdfc_emi_terminal');

        $this->fixtures->merchant->enableEmi();

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => $plan['id']]);

        $payment['card']['number'] = '555555555555558';
        $payment['amount']         = 500000;
        $payment['method']         = 'emi';
        $payment['emi_duration']   = 9;

        $this->doAuthAndCapturePayment($payment);

        $paymentObj = $this->getDbLastEntityToArray('payment');

        $this->assertEquals('50000', ($paymentObj['fee'] - $paymentObj['tax']));
    }

    public function testCreatePaymentEmiMethodTypePricingDebit()
    {
        $this->mockCardVault();

        $this->gateway = 'mozart';

        $this->ba->publicAuth();

        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->iin->create([
            'iin'      => '555555',
            'country'  => 'IN',
            'network'  => 'MasterCard',
            'type'     => 'debit',
            'issuer'   => 'HDFC',
            'emi'      => 1,
        ]);

        $defaultPricingPlan = [
            'plan_name'           => 'TestPlan1',
            'payment_method'      => 'emi',
            'payment_method_type' => 'debit',
            'percent_rate'        => 1000,
            'fixed_rate'          => 0,
            'payment_issuer'      => 'HDFC',
            'payment_network'     => 'MC',
            'emi_duration'        => 9,
            'org_id'              => '10000000000000',
            'type'                => 'pricing',
        ];

        $plan = $this->createPricingPlan($defaultPricingPlan);

        $this->fixtures->emiPlan->create(
            [
                'merchant_id' => '10000000000000',
                'bank'        => 'HDFC',
                'type'        => 'debit',
                'rate'        => 1200,
                'min_amount'  => 300000,
                'duration'    => 9,
            ]);

        $this->fixtures->create('terminal:hdfc_debit_emi');

        $this->fixtures->merchant->enableEmi();

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => $plan['id']]);

        $payment['card']['number'] = '555555555555558';
        $payment['amount']         = 500000;
        $payment['method']         = 'emi';
        $payment['emi_duration']   = 9;

        $this->doAuthPayment($payment);
        $payment = $this->getDbLastEntity('payment');
        $data = $this->testData[__FUNCTION__];

        $url = $this->getOtpSubmitUrl($payment);

        $data['request']['url'] = $url;

        $this->runRequestResponseFlow($data);

        $this->capturePayment(
            'pay_' .$payment['id'],
            $payment['amount'],
            'INR');

        $paymentObj = $this->getDbLastEntityToArray('payment');

        $this->assertEquals('50000', ($paymentObj['fee'] - $paymentObj['tax']));
    }

    public function testCreatePaymentCardSubTypePricing()
    {
        $this->mockCardVault();

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '555555555555558';

        $this->fixtures->iin->create([
            'iin' => '555555',
            'country' => 'IN',
            'network' => 'MasterCard',
            'type'    => 'credit',
            'sub_type'=> 'business',
        ]);

        $defaultPricingPlan = [
            'plan_name'                 => 'TestPlan1',
            'payment_method'            => 'card',
            'payment_method_type'       => 'credit',
            'payment_method_subtype'    => 'business',
            'percent_rate'              => 1000,
            'fixed_rate'                =>  0,
            'payment_network'           => 'MC',
            'payment_issuer'            => 'SBIN',
            'org_id'                    => '10000000000000',
            'type'                      => 'pricing',
        ];

        $plan = $this->createPricingPlan($defaultPricingPlan);

        $this->setDefaultMerchantMethods();

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => $plan['id']]);

        $this->doAuthAndCapturePayment($payment);

        $paymentObj = $this->getLastEntity('payment', true);

        $this->assertEquals('5000', $paymentObj['fee']);
    }

    public function testCreatePaymentCardWithoutSubTypePricing()
    {
        $this->mockCardVault();

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '555555555555558';

        $this->fixtures->iin->create([
            'iin' => '555555',
            'country' => 'IN',
            'network' => 'MasterCard',
            'type'    => 'credit',
        ]);

        $defaultPricingPlan = [
            'plan_name'                 => 'TestPlan1',
            'payment_method'            => 'card',
            'payment_method_type'       => 'credit',
            'payment_method_subtype'    => 'business',
            'percent_rate'              => 1000,
            'fixed_rate'                =>  0,
            'payment_network'           => 'MC',
            'payment_issuer'            =>  null,
            'org_id'                    => '10000000000000',
            'type'                      => 'pricing',
        ];

        $defaultPricingPlan2 = [
            'plan_name'                 => 'TestPlan1',
            'payment_method'            => 'card',
            'payment_method_type'       => 'credit',
            'percent_rate'              => 2000,
            'fixed_rate'                =>  0,
            'payment_network'           => 'MC',
            'payment_issuer'            =>  null,
            'org_id'                    => '10000000000000',
            'type'                      => 'pricing',
        ];

        $plan = $this->createPricingPlan($defaultPricingPlan);

        $plan = $this->createPricingPlan($defaultPricingPlan2);

        $this->setDefaultMerchantMethods();

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => $plan['id']]);

        $this->doAuthAndCapturePayment($payment);

        $paymentObj = $this->getLastEntity('payment', true);

        $this->assertEquals('10000', $paymentObj['fee']);
    }


    public function testUpdatePricingSubType()
    {

        $defaultPricingPlan2 = [
            'plan_name'                 => 'TestPlan1',
            'payment_method'            => 'card',
            'payment_method_type'       => 'credit',
            'percent_rate'              => 2000,
            'fixed_rate'                =>  0,
            'payment_network'           => 'MC',
            'payment_issuer'            =>  null,
            'org_id'                    => '10000000000000',
            'type'                      => 'pricing',
            'feature'                   => 'payment',
        ];

        $plan2 = $this->createPricingPlan($defaultPricingPlan2);

        $this->setDefaultMerchantMethods();

        $merchant = $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => $plan2['plan_id'], 'activated' => 1]);

        $this->ba->cronAuth();
       // $testData['request']['content']['plan_ids'] = [$plan['id']];


        $this->startTest();

        $rules = $this->getDbEntities('pricing', [
            'feature'        => 'payment',
            'product'        => 'primary',
            'plan_id'        => $plan2['id']
        ])->toArray();

        // card credit null MC
        // card credit business MC
        // card null business null

        // but not // card credit null null

        $this->assertEquals(3, count($rules));

        $corporateRules = 0;

        foreach($rules as $rule)
        {
            if($rule['payment_method_subtype'] === 'business')
            {
                $corporateRules++;
            }
        }

        $this->assertEquals(2, $corporateRules);
    }

    public function testUpdatePricingSubTypeWithCorporateRuleAlreadyPresent()
    {

        $defaultPricingPlan2 = [
            'plan_name'                 => 'TestPlan1',
            'payment_method'            => 'card',
            'payment_method_type'       => 'credit',
            'percent_rate'              => 2000,
            'fixed_rate'                =>  0,
            'payment_network'           => 'MC',
            'payment_issuer'            =>  null,
            'org_id'                    => '10000000000000',
            'type'                      => 'pricing',
            'feature'                   => 'payment',
            'payment_method_subtype'    => 'business'
        ];

        $plan2 = $this->createPricingPlan($defaultPricingPlan2);

        $this->setDefaultMerchantMethods();

        $merchant = $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => $plan2['plan_id'], 'activated' => 1]);

        $this->ba->cronAuth();
        // $testData['request']['content']['plan_ids'] = [$plan['id']];


        $this->startTest();

        $rules = $this->getDbEntities('pricing', [
            'feature'        => 'payment',
            'product'        => 'primary',
            'plan_id'        => $plan2['id']
        ])->toArray();

        $this->assertEquals(1, count($rules));

        $corporateRules = 0;

        foreach($rules as $rule)
        {
            if($rule['payment_method_subtype'] === 'business')
            {
                $corporateRules++;
            }
        }

        $this->assertEquals(1, $corporateRules);
    }

    public function testAddPricingPlanRuleForBankingPayoutWithoutAccountType()
    {
        $this->ba->adminAuth();

        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanRuleForBankingPayoutWithoutChannel()
    {
        $this->ba->adminAuth();

        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanRuleForBankingPayoutWithInvalidChannel()
    {
        $this->ba->adminAuth();

        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanRuleForPrimaryPayoutWithAccountType()
    {
        $this->ba->adminAuth();

        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testCreatePaymentCardTypePrepaidWithPrepaidPricing()
    {
        $this->mockCardVault();

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '4573921038488884';

        $this->fixtures->iin->create([
            'iin' => '457392',
            'country' => 'IN',
            'network' => 'Visa',
            'type'    => 'prepaid'
        ]);

        $defaultPricingPlan = [
            'plan_name'           => 'TestPlan1',
            'payment_method'      => 'card',
            'payment_method_type' => 'prepaid',
            'percent_rate'        => 1000,
            'fixed_rate'          => 0,
            'payment_network'     => 'VISA',
            'payment_issuer'      => 'SBIN',
            'org_id'              => '10000000000000',
            'type'                => 'pricing',
        ];

        $plan = $this->createPricingPlan($defaultPricingPlan);

        $this->setDefaultMerchantMethods();

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => $plan['id']]);

        $this->doAuthAndCapturePayment($payment);

        $paymentObj = $this->getLastEntity('payment', true);

        $this->assertEquals('5000', $paymentObj['fee']);

    }

    public function testCreatePaymentCardTypePrepaidWithNoPrepaidPricing()
    {
        $this->mockCardVault();

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '4573921038488884';

        $this->fixtures->iin->create([
            'iin' => '457392',
            'country' => 'IN',
            'network' => 'Visa',
            'type'    => 'prepaid'
        ]);

        $defaultPricingPlan = [
            'plan_name'           => 'TestPlan1',
            'payment_method'      => 'card',
            'payment_method_type' => 'credit',
            'percent_rate'        => 2000,
            'fixed_rate'          => 0,
            'payment_network'     => 'VISA',
            'payment_issuer'      => 'SBIN',
            'org_id'              => '10000000000000',
            'type'                => 'pricing',
        ];

        $plan = $this->createPricingPlan($defaultPricingPlan);

        $this->setDefaultMerchantMethods();

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => $plan['id']]);

        $this->doAuthAndCapturePayment($payment);

        $paymentObj = $this->getLastEntity('payment', true);

        $this->assertEquals('10000', $paymentObj['fee']);

    }

    public function testCreatePaymentCardWithProcurer()
    {
        $this->ba->adminAuth();

        $this->mockCardVault();

        $merchantPricingPlan = [
            'plan_name'           => 'TestPlan1',
            'procurer'            => 'merchant',
            'payment_method'      => 'card',
            'payment_method_type' => null,
            'payment_network'     => null,
            'payment_issuer'      => null,
            'percent_rate'        => 0,
            'fixed_rate'          => 20,
            'type'                => 'pricing',
            'international'       => 0,
            'amount_range_active' => '0',
            'amount_range_min'    => null,
            'amount_range_max'    => null,
        ];

        $razorpayPricingPlan = [
            'plan_name'           => 'TestPlan1',
            'procurer'            => 'razorpay',
            'payment_method'      => 'card',
            'payment_method_type' => null,
            'payment_network'     => null,
            'payment_issuer'      => null,
            'percent_rate'        => 0,
            'fixed_rate'          => 10,
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'international'       => 0,
            'amount_range_active' => '0',
            'amount_range_min'    => null,
            'amount_range_max'    => null,
        ];

        $planId = $this->createPricingPlan($razorpayPricingPlan)['id'];

        $this->addPricingPlanRule($planId, $merchantPricingPlan);

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '4573921038488884';

        $this->fixtures->iin->create([
            'iin' => '457392',
            'country' => 'IN',
            'network' => 'Visa',
            'type'    => 'credit'
        ]);

        $this->setDefaultMerchantMethods();

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => $planId]);

        $this->fixtures->terminal->createDisableDefaultHdfcTerminal();
        $this->fixtures->terminal->createSharedHdfcTerminal(['id' => '1HDFCProRazorP', 'procurer' => 'razorpay']);

        $this->doAuthAndCapturePayment($payment);

        $paymentObj = $this->getLastEntity('payment', true);

        $this->assertEquals('10', $paymentObj['fee']);

        $this->fixtures->terminal->disableTerminal('1HDFCProRazorP');
        $this->fixtures->terminal->createSharedHdfcTerminal(['id' => '1HDFCProMercht', 'procurer' => 'merchant']);

        $this->doAuthAndCapturePayment($payment);

        $paymentObj = $this->getLastEntity('payment', true);

        $this->assertEquals('20', $paymentObj['fee']);
    }

    public function testCreatePaymentCardWithDefaultTerminalProcurer()
    {
        $this->ba->adminAuth();

        $this->mockCardVault();

        $merchantPricingPlan = [
            'plan_name'           => 'TestPlan1',
            'procurer'            => 'merchant',
            'payment_method'      => 'card',
            'payment_method_type' => null,
            'payment_network'     => null,
            'payment_issuer'      => null,
            'percent_rate'        => 0,
            'fixed_rate'          => 20,
            'type'                => 'pricing',
            'international'       => 0,
            'amount_range_active' => '0',
            'amount_range_min'    => null,
            'amount_range_max'    => null,
        ];

        $razorpayPricingPlan = [
            'plan_name'           => 'TestPlan1',
            'procurer'            => 'razorpay',
            'payment_method'      => 'card',
            'payment_method_type' => null,
            'payment_network'     => null,
            'payment_issuer'      => null,
            'percent_rate'        => 0,
            'fixed_rate'          => 10,
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'international'       => 0,
            'amount_range_active' => '0',
            'amount_range_min'    => null,
            'amount_range_max'    => null,
        ];

        $planId = $this->createPricingPlan($razorpayPricingPlan)['id'];

        $this->addPricingPlanRule($planId, $merchantPricingPlan);

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '4573921038488884';

        $this->fixtures->iin->create([
            'iin' => '457392',
            'country' => 'IN',
            'network' => 'Visa',
            'type'    => 'credit'
        ]);

        $this->setDefaultMerchantMethods();

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => $planId]);

        $this->fixtures->terminal->createDisableDefaultHdfcTerminal();
        $this->fixtures->terminal->createSharedHdfcTerminal(['id' => '1HDFCProRazorP']);

        $this->doAuthAndCapturePayment($payment);

        $paymentObj = $this->getLastEntity('payment', true);

        $this->assertEquals('10', $paymentObj['fee']);
    }

    public function testCreatePaymentCardWithDefaultPricingAndMerchantPricing()
    {
        $this->ba->adminAuth();

        $this->mockCardVault();

        $merchantPricingPlan = [
            'plan_name'           => 'TestPlan1',
            'payment_method'      => 'card',
            'payment_method_type' => null,
            'payment_network'     => null,
            'payment_issuer'      => null,
            'percent_rate'        => 0,
            'fixed_rate'          => 20,
            'type'                => 'pricing',
            'international'       => 0,
            'amount_range_active' => '0',
            'amount_range_min'    => null,
            'amount_range_max'    => null,
        ];

        $razorpayPricingPlan = [
            'plan_name'           => 'TestPlan1',
            'procurer'            => 'merchant',
            'payment_method'      => 'card',
            'payment_method_type' => null,
            'payment_network'     => null,
            'payment_issuer'      => null,
            'percent_rate'        => 0,
            'fixed_rate'          => 10,
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'international'       => 0,
            'amount_range_active' => '0',
            'amount_range_min'    => null,
            'amount_range_max'    => null,
        ];

        $planId = $this->createPricingPlan($razorpayPricingPlan)['id'];

        $this->addPricingPlanRule($planId, $merchantPricingPlan);

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '4573921038488884';

        $this->fixtures->iin->create([
            'iin' => '457392',
            'country' => 'IN',
            'network' => 'Visa',
            'type'    => 'credit'
        ]);

        $this->setDefaultMerchantMethods();

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => $planId]);

        $this->fixtures->terminal->createDisableDefaultHdfcTerminal();
        $this->fixtures->terminal->createSharedHdfcTerminal(['id' => '1HDFCProRazorP']);

        $this->doAuthAndCapturePayment($payment);

        $paymentObj = $this->getLastEntity('payment', true);

        $this->assertEquals('20', $paymentObj['fee']);
    }

    public function testAddPricingPlanRuleWithVpaReceiver()
    {
        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanRuleWithFeatureRefundWithPercentRate()
    {
        $this->ba->adminAuth();

        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanRuleWithFeatureRefundAndPaymentMethodNullValid()
    {
        $this->ba->adminAuth();

        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanRuleWithFeatureRefundAndPaymentMethodAbsentValid()
    {
        $this->ba->adminAuth();

        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingWithPaymentMethodNullInvalid()
    {
        // Payment method should not be null for any feature except refund

        $this->ba->adminAuth();

        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        foreach (Pricing\Feature::FEATURE_LIST as $feature)
        {
            if ($feature !== 'refund' and $feature !== 'optimizer')
            {
                $testData['request']['content']['feature'] = $feature;

                if ($feature === 'payment')
                {
                    $message = "The payment method field is required for feature payment if procurer is not merchant.";
                }
                else
                {
                    $message =  "The payment method field is required unless feature is in refund, optimizer, payment, affordability_widget.";

                }
                $testData['response']['content']['error']['description'] = $message;

                $this->startTest($testData);
            }
        }
    }

    public function testAddPricingRefundModes()
    {
        $allModes = [
            'IMPS',
            'RTGS',
            'UPI',
            'IFT',
            'NEFT',
            'invalid',
            'test',
            'CT',
        ];

        $validModesMap = [
            null   => [
                'NEFT',
                'IMPS',
                'RTGS',
                'UPI',
                'IFT',
                'CT',
            ],
            'card' => [
                'UPI',
                'NEFT',
                'IMPS',
                'CT',
            ],
            'upi' => [
                'UPI',
            ],
            'netbanking' => [
                'NEFT',
                'IMPS',
                'RTGS',
                'IFT',
            ],
        ];

        $validResponse = [
            'response' => [
                'content' => [
                    'fixed_rate'          => 100,
                    'percent_rate'        => 0,
                    'amount_range_active' => false,
                    'amount_range_min'    => null,
                    'amount_range_max'    => null,
                    'feature'             => 'refund',
                ],
            ],
        ];

        $invalidResponse = [
            'response' => [
                'content' => [
                    'error' => [
                        'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'The payment method field is required unless feature is in refund.'
                    ],
                ],
                'status_code' => 400,
            ],
            'exception' => [
                'class'               => Exception\BadRequestValidationFailureException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ],
        ];

        $this->ba->adminAuth();

        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        foreach ($allModes as $mode)
        {
            foreach ($validModesMap as $method => $validModes)
            {
                $testData['request']['content']['payment_method'] = $method;

                $testData['request']['content']['payment_method_type'] = $mode;

                if (in_array($mode, $validModes, true) === true)
                {
                    $testData['response'] = $validResponse['response'];

                    if (empty($method) === false)
                    {
                        $testData['response']['content']['payment_method'] = $method;
                    }
                    else
                    {
                        unset($testData['response']['content']['payment_method']);
                    }

                    $testData['response']['content']['payment_method_type'] = $mode;
                }
                else
                {
                    $testData['response'] = $invalidResponse['response'];

                    $testData['exception'] = $invalidResponse['exception'];

                    $testData['response']['content']['error']['description'] = 'Refund mode should be ' . implode('/', $validModes);
                }

                $this->startTest($testData);

                unset($testData['response']);
                unset($testData['exception']);
            }
        }
    }

    public function testAddPricingPlanRulesForBankingProductWithBothSupportedAccountTypes()
    {
        $this->ba->adminAuth();

        $pricingPlan = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/' . $pricingPlan['id'] . '/rule';

        $this->startTest($testData);

        $sharedAccountPricingRule = $this->getDbLastEntity('pricing')->toArray();

        $testData['request']['content']['account_type'] = 'direct';
        $testData['request']['content']['channel']      = 'rbl';

        $testData['response']['content']['account_type']  = 'direct';
        $testData['response']['content']['channel'] = 'rbl';

        $this->startTest($testData);

        $directAccountPricingRule = $this->getDbLastEntity('pricing')->toArray();

        //Reload shared account rule to verify that it wasn't replaced
        $sharedAccountPricingRule = $this->getDbEntityById('pricing', $sharedAccountPricingRule['id'])->toArray();

        // Unset created_at and updated_at so that during array_diff, these 2 values are not taken into consideration.
        // They can be same or different and we aren't concerned about them.
        unset($sharedAccountPricingRule['created_at']);
        unset($sharedAccountPricingRule['updated_at']);

        unset($directAccountPricingRule['created_at']);
        unset($directAccountPricingRule['updated_at']);

        $differenceBetweenTwoRules = array_diff($sharedAccountPricingRule, $directAccountPricingRule);

        // Assert that the two new rules have only two fields that have different value (id and account_type)
        $this->assertEquals(2, count($differenceBetweenTwoRules));

        // Assert that the two new rules have their respected account_types
        $this->assertEquals('shared', $sharedAccountPricingRule['account_type']);
        $this->assertEquals('direct', $directAccountPricingRule['account_type']);

        // Assert that the two new rules have their respected channels
        $this->assertEquals(null, $sharedAccountPricingRule['channel']);
        $this->assertEquals('rbl', $directAccountPricingRule['channel']);

        // Assert that the two new rules have different ids
        $this->assertNotEquals($directAccountPricingRule['id'], $sharedAccountPricingRule['id']);
    }

    public function testAddDuplicatePricingPlanRulesForBankingProduct()
    {
        $this->ba->adminAuth();

        $pricingPlan = $this->createPricingPlan();

        $request = [
            'method'  => 'POST',
            'url'     => '/pricing/' . $pricingPlan['id'] . '/rule',
            'content' => [
                'product'             => 'banking',
                'feature'             => 'payout',
                'payment_method'      => 'fund_transfer',
                'percent_rate'        => 0,
                'international'       => 0,
                'amount_range_active' => 1,
                'amount_range_max'    => 1500,
                'amount_range_min'    => 0,
                'account_type'        => 'shared',
            ],
        ];

        $this->makeRequestAndGetContent($request);

        $testData['request']['url'] = '/pricing/' . $pricingPlan['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddDuplicatePricingRuleWithDifferentAppName()
    {
        $this->ba->adminAuth();

        $pricingPlan = $this->createPricingPlan();

        $this->testData[__FUNCTION__]['request']['url'] = '/pricing/' . $pricingPlan['id'] . '/rule';

        $request = $this->testData[__FUNCTION__]['request'];

        unset($request['content']['app_name']);

        $this->makeRequestAndGetContent($request);

        $this->startTest();

        $dbPricingRule = $this->getDbLastEntity('pricing')->toArray();

        $this->assertEquals($pricingPlan['id'], $dbPricingRule['plan_id']);

        $this->assertEquals('xpayroll', $dbPricingRule['app_name']);
    }

    public function testAddDuplicatePricingRulesWithSameAppName()
    {
        $this->ba->adminAuth();

        $pricingPlan = $this->createPricingPlan();

        $this->testData[__FUNCTION__]['request']['url'] = '/pricing/' . $pricingPlan['id'] . '/rule';

        $request = $this->testData[__FUNCTION__]['request'];

        $this->makeRequestAndGetContent($request);

        $this->startTest();
    }

    public function testAddPricingPlanRuleForBankingPayoutWithCorrectAuth()
    {
        $this->ba->adminAuth();

        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanRuleForBankingPayoutWithIncorrectAuth()
    {
        $this->ba->adminAuth();

        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanRuleForBankingPayoutWithoutAuth()
    {
        $this->ba->adminAuth();

        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanRuleForPaymentMethodTypeDebitWithoutAuth()
    {
        $this->ba->adminAuth();

        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanRuleForPaymentMethodTypeDebitWithIncorrectAuth()
    {
        $this->ba->adminAuth();

        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanRuleForProductPrimaryAndPaymentMethodTypeCredit()
    {
        $this->ba->adminAuth();

        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanRulesForBankingPayoutWithBothSupportedAuths()
    {
        $this->ba->adminAuth();

        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/' . $content['id'] . '/rule';

        $this->startTest($testData);

        $privateAuthPricingRule = $this->getDbLastEntity('pricing')->toArray();

        $testData['request']['content']['auth_type']  = 'proxy';
        $testData['response']['content']['auth_type'] = 'proxy';

        $this->startTest($testData);

        $proxyAuthPricingRule = $this->getDbLastEntity('pricing')->toArray();

        //Reload private auth rule to verify that it wasn't replaced
        $privateAuthPricingRule = $this->getDbEntityById('pricing', $privateAuthPricingRule['id'])->toArray();

        // Unset created_at and updated_at so that during array_diff, these 2 values are not taken into consideration.
        // They can be same or different and we aren't concerned about them.
        unset($privateAuthPricingRule['created_at']);
        unset($privateAuthPricingRule['updated_at']);

        unset($proxyAuthPricingRule['created_at']);
        unset($proxyAuthPricingRule['updated_at']);

        $differenceBetweenTwoRules = array_diff($privateAuthPricingRule, $proxyAuthPricingRule);

        // Assert that the two new rules have only two fields that have different value
        $this->assertEquals(2, count($differenceBetweenTwoRules));

        // Assert that the two new rules have their respected auth_types
        $this->assertEquals('private', $privateAuthPricingRule['auth_type']);
        $this->assertEquals('proxy', $proxyAuthPricingRule['auth_type']);

        // Assert that the two new rules have different ids
        $this->assertNotEquals($proxyAuthPricingRule['id'], $privateAuthPricingRule['id']);
    }

    public function testAddDuplicatePricingPlanRulesForBankingProductWithAccountTypeChannelAndAuthType()
    {
        $this->ba->adminAuth();

        $pricingPlan = $this->createPricingPlan();

        $request = [
            'method'  => 'POST',
            'url'     => '/pricing/' . $pricingPlan['id'] . '/rule',
            'content' => [
                'product'             => 'banking',
                'feature'             => 'payout',
                'payment_method'      => 'fund_transfer',
                'percent_rate'        => 0,
                'international'       => 0,
                'amount_range_active' => 1,
                'amount_range_max'    => 1500,
                'amount_range_min'    => 0,
                'account_type'        => 'direct',
                'channel'             => 'rbl'
            ],
        ];

        $this->makeRequestAndGetContent($request);

        $testData['request']['url'] = '/pricing/' . $pricingPlan['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanRuleForBankingPayoutWithPayoutsFilter()
    {
        $this->ba->adminAuth();

        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanRuleForBankingPayoutWithNullPayoutsFilter()
    {
        $this->ba->adminAuth();

        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanRuleForNotBankingPayoutWithNullPayoutsFilter()
    {
        $this->ba->adminAuth();

        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanRuleForNotBankingPayoutWithPayoutsFilter()
    {
        $this->ba->adminAuth();

        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanRuleWithFeaturePayoutCardModeBankingProduct()
    {
        $this->ba->adminAuth();

        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanRuleWithFeaturePayoutInvalidModeBankingProduct()
    {
        $this->ba->adminAuth();

        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingPlanRuleWithFeaturePayoutAmazonpayModeBankingProduct()
    {
        $this->ba->adminAuth();

        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testCreateUpiOneTimePlanWithoutAmountRange()
    {
        // assertions and test is run as per the helper file PricingData.php
        $this->startTest();
    }

    public function testCreateUpiOneTimePlanWithAmountRange()
    {
        // assertions and test is run as per the helper file PricingData.php
        $this->startTest();
    }

    public function testCreateUpiAutopayPlanWithoutAmountRange()
    {
        // assertions and test is run as per the helper file PricingData.php
        $this->startTest();
    }

    public function testCreateUpiAutopayPlanWithAmountRange()
    {
        // assertions and test is run as per the helper file PricingData.php
        $this->startTest();
    }

    public function testCreateUpiAutopayPlanWithInvalidSubtypeRule()
    {
        // assertions and test is run as per the helper file PricingData.php
        $this->startTest();
    }

    public function testAddPricingPlanRuleForAffordabilityWidget()
    {
        $this->ba->adminAuth();

        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testAddPricingRuleForWidgetMaxFixedRate()
    {
        $this->ba->adminAuth();

        $content = $this->createPricingPlan();

        $testData['request']['url'] = '/pricing/'. $content['id'] . '/rule';

        $this->startTest($testData);
    }

    public function testCreateUpiInAppPlanWithoutAmountRange()
    {
        // assertions and test is run as per the helper file PricingData.php
        $this->startTest();
    }

    public function testCreateUpiInAppPlanWithAmountRange()
    {
        // assertions and test is run as per the helper file PricingData.php
        $this->startTest();
    }
}
