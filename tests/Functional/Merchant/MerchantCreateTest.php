<?php

namespace RZP\Tests\Functional\Merchant;

use DB;
use App;
use Mail;
use Queue;
use Mockery;
use RZP\Constants;
use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Models\User\Role;
use RZP\Constants\Product;
use RZP\Constants\Timezone;
use WpOrg\Requests\Response;
use RZP\Models\Card\Network;
use RZP\Models\Batch\Header;
use RZP\Models\Merchant\Core;
use RZP\Services\RazorXClient;
use Razorpay\OAuth\Application;
use RZP\Mail\User\MappedToAccount;
use RZP\Models\Settlement\Channel;
use RZP\Models\Admin\Permission;
use RZP\Tests\Functional\TestCase;
use Illuminate\Support\Facades\Bus;
use RZP\Models\Pricing\DefaultPlan;
use RZP\Jobs\SubMerchantTaggingJob;
use Illuminate\Support\Facades\Redis;
use RZP\Models\Partner\RateLimitBatch;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Methods\Entity;
use RZP\Services\Mock\ApachePinotClient;
use Illuminate\Database\Eloquent\Factory;
use RZP\Models\Partner\RateLimitConstants;
use RZP\Mail\User\LinkedAccountUserAccess;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\Batch\BatchTestTrait;
use RZP\Jobs\SubmerchantFirstTransactionEvent;
use RZP\Models\Partner\Config as PartnerConfig;
use RZP\Tests\Functional\Helpers\TerminalTrait;
use RZP\Models\User\Repository as UserRepository;
use RZP\Tests\Functional\Fixtures\Entity\Pricing;
use Razorpay\OAuth\Application\Entity as OAuthApp;
use RZP\Services\Mock\LOSService as MockLOSService;
use RZP\Models\Partner\Constants as PartnerConstants;
use RZP\Mail\User\PasswordReset as PasswordResetMail;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Partner\PartnershipsRateLimiter;
use RZP\Models\Merchant\Attribute as MerchantAttribute;
use RZP\Models\Merchant\Constants as MerchantConstants;
use RZP\Models\Merchant\Detail\Entity as MerchantDetail;
use RZP\Models\Merchant\Repository as MerchantRepository;
use RZP\Models\Merchant\Detail\Status as MerchantDetailStatus;
use RZP\Tests\Functional\Fixtures\Entity\Pricing as TestPricing;
use RZP\Mail\Merchant\CreateSubMerchant as CreateSubMerchantMail;
use RZP\Mail\Merchant\CreateSubMerchantPartner as CreateSubMerchantPartnerMail;
use RZP\Mail\Merchant\CreateSubMerchantAffiliate as CreateSubMerchantAffiliateMail;
use RZP\Mail\Merchant\RazorpayX\CreateSubMerchantPartner as CreateSubMerchantPartnerForX;
use RZP\Mail\Merchant\RazorpayX\CreateSubMerchantAffiliate as CreateSubMerchantAffiliateMailForX;
use RZP\Mail\Merchant\Capital\LineOfCredit\CreateSubMerchantPartner as CreateSubMerchantPartnerForLOC;
use RZP\Mail\Merchant\Capital\LineOfCredit\CreateSubMerchantAffiliate as CreateSubMerchantAffiliateForLOC;
use RZP\Tests\Traits\MocksSplitz;

class MerchantCreateTest extends TestCase
{
    use PartnerTrait;
    use MocksSplitz;
    use TerminalTrait;
    use BatchTestTrait;

    protected mixed $repo;

    const DEFAULT_MERCHANT_ID = '10000000000000';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantCreateTestData.php';

        parent::setUp();

        $this->app = App::getFacadeRoot();
        $this->repo = $this->app['repo'];

        $this->mockApachePinot();

    }

    private function mockApachePinot()
    {
        $pinotService = $this->getMockBuilder(ApachePinotClient::class)
                             ->setConstructorArgs([$this->app])
                             ->onlyMethods(['getDataFromPinot'])
                             ->getMock();

        $this->app->instance('apache.pinot', $pinotService);

        $pinotService->method('getDataFromPinot')
                     ->willReturn(null);
    }

    public function testCreateMerchantWithDuplicateEmail()
    {
        $this->ba->adminAuth(Mode::TEST);

        $this->startTest();
    }

    public function testCreateMerchantWithDuplicateId()
    {
        $this->ba->adminAuth(Mode::TEST);

        $this->startTest();
    }

    public function testCreateMerchantAndRelationsHdfcOrg()
    {
        $org = $this->fixtures->org->createHdfcOrg();

        $this->ba->adminAuth('test', 'SuperSecretTokenForRazorpaySuprHdfcbToken', $org->getPublicId(), 'hdfcbank.com');

        $this->merchantId = '1X4hRFHFx4UiXt';

        $testData = $this->testData['testCreateMerchant'];

        $testData['request']['content']['org_id'] = $org->getPublicId();

        $testData['response']['content']['pricing_plan_id'] = 'BAJq6FJDNJ4ZqD';

        $this->runRequestResponseFlow($testData);

        $this->checkDisableNativeCurrencyDefaultFeature($org->id);
    }

    public function testCreateMerchantAndRelations()
    {
        $this->ba->adminAuth();

        $this->merchantId = '1X4hRFHFx4UiXt';

        $content = $this->createMerchant();

        $this->assertEquals($content['convert_currency'], false);

        $this->assertSame($content['activated'], false);

        $this->checkSettlementSchedule($content);

        $this->checkTerminals();

        $this->checkBalances();

        $this->checkBalanceConfigs();

        $this->checkNetbankingBanks();

        $this->checkMethods();

        $this->checkMerchantDetails();

        $this->checkOTPAuthDefaultFeature();

        $this->checkDisableNativeCurrencyDefaultFeature(Org::RZP_ORG);
    }

    public function testCreateMerchantAndRelationsWithNewLedgerService()
    {
        // mock razorx, enabled the razorx experiment for ledger onboarding
        $this->mockRazorxTreatment();

        // During merchant onboarding, there has been push to SNS topic for onbaording in Ledger service.
        $this->mockLedgerSnsPush();

        $this->ba->adminAuth();

        $this->merchantId = '1X4hRFHFx4UiXt';

        $content = $this->createMerchant();

        $this->assertEquals($content['convert_currency'], false);

        $this->assertSame($content['activated'], false);

        $this->checkSettlementSchedule($content);

        $this->checkTerminals();

        $this->checkBalances();

        $this->checkBalanceConfigs();

        $this->checkNetbankingBanks();

        $this->checkMethods();

        $this->checkMerchantDetails();

        $this->checkOTPAuthDefaultFeature();

        $this->checkDisableNativeCurrencyDefaultFeature(Org::RZP_ORG);
    }

    protected function createMerchant()
    {
        $testData = $this->testData['testCreateMerchant'];

        return $this->runRequestResponseFlow($testData);
    }

    protected function checkTerminals()
    {
        $this->ba->adminAuth('test', null, 'org_' . Org::RZP_ORG);

        $testData = $this->testData['testGetTerminalsInTestForCreatedMerchant'];

        $content = $this->runRequestResponseFlow($testData);

        $this->ba->adminAuth('live', null, 'org_' . Org::RZP_ORG);

        $testData = $this->testData['testGetTerminalsInLiveForCreatedMerchant'];

        $content = $this->runRequestResponseFlow($testData);
    }

    protected function checkBalances()
    {
        $user = $this->fixtures->user->createUserForMerchant('1X4hRFHFx4UiXt');

        $this->ba->proxyAuth('rzp_test_1X4hRFHFx4UiXt', $user->getId());

        $this->runRequestResponseFlow($this->testData['testBalanceInTestAfterCreatedMerchant']);

        $user = $this->fixtures->user->createUserForMerchant('1X4hRFHFx4UiXt', [], 'owner', 'live');

        $this->ba->proxyAuth('rzp_live_1X4hRFHFx4UiXt', $user->getId());

        $this->runRequestResponseFlow($this->testData['testBalanceInLiveAfterCreatedMerchant']);
    }

    protected function checkBalanceConfigs()
    {
        $user = $this->fixtures->user->createUserForMerchant('1X4hRFHFx4UiXt');

        $this->ba->proxyAuth('rzp_test_1X4hRFHFx4UiXt', $user->getId());

        $this->runRequestResponseFlow($this->testData['testBalanceConfigInTestAfterCreatedMerchant']);
    }


    protected function checkNetbankingBanks()
    {
        $this->checkNetbankingBanksInMode(Mode::TEST);

        $this->checkNetbankingBanksInMode(Mode::LIVE);
    }

    protected function checkMethods()
    {

        $methods = $this->getEntityById('methods', '1X4hRFHFx4UiXt', true);

        $expectedMethods = [
            'amex'          => false,
            'mobikwik'      => true,
            'paytm'         => false,
            'jiomoney'      => true,
            'airtelmoney'   => true,
            'paylater'      => true,
            'phonepeswitch' => true,
            'olamoney'      => true,
            'card_subtype'  => 3 // consumer + business
        ];

        $this->assertArraySelectiveEquals($expectedMethods, $methods);

        $cardNetworks = $methods[Entity::CARD_NETWORKS];

        $expectedCardNetworks =  [
            Network::BAJAJ  =>  0,
            Network::RUPAY  =>  1,
            Network::JCB    =>  0,
            Network::VISA   =>  1,
            Network::MAES   =>  1,
            Network::MC     =>  1,
            Network::DICL   =>  0,
            Network::AMEX   =>  0,
        ];

        $this->assertArraySelectiveEquals($expectedCardNetworks, $cardNetworks);
    }

    protected function checkMerchantDetails()
    {

        $merchantDetails = $this->getEntityById('merchant_detail', '1X4hRFHFx4UiXt', true);

        $this->assertEquals($merchantDetails['contact_email'], 'test@localhost.com');
    }

    protected function checkSettlementSchedule($merchant)
    {
        $scheduledTasks = $this->getEntities('schedule_task', ['count' => 2 ], true);

        foreach ($scheduledTasks['items'] as $scheduledTask)
        {
            $schedule = $this->getEntityById('schedule', $scheduledTask['schedule_id'], true);

            $delay = $scheduledTask['international'] ? 7 : 2;

            $this->assertEquals($merchant['id'], $scheduledTask['merchant_id']);
            $this->assertEquals($schedule['period'], 'daily');
            $this->assertEquals($schedule['delay'], $delay);
        }
    }

    protected function checkNetbankingBanksInMode($mode)
    {
        $this->ba->adminAuth($mode);

        $testData = $this->testData['testGetBankAccountsAfterCreatedMerchant'];

        $content = $this->runRequestResponseFlow($testData);

        $this->assertSame(count(Netbanking::DEFAULT_DISABLED_BANKS), count($content['disabled']));
    }

    public function testCheckSalesforceGroupForSubmerchantCreate()
    {
        $this->fixtures->merchant->addFeatures(['aggregator']);
        $this->fixtures->merchant->editPricingPlanId(TestPricing::DEFAULT_PRICING_PLAN_ID);

        $user = $this->createUserMerchantMapping('10000000000000', 'owner');

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $this->startTest();

        $merchantMap = \DB::connection('test')->table('merchant_map')
                          ->where('merchant_id', 'NewSubmerchant')
                          ->first();

        $this->assertEquals($merchantMap->entity_id, 'E15BhsdMSofcUJ');
        $this->assertEquals($merchantMap->entity_type, 'group');
        $this->assertEquals($merchantMap->merchant_id, 'NewSubmerchant');
    }

    public function  testCheckSalesforceGroupForMarketplaceLinkedAccount()
    {
        $user = $this->createUserMerchantMapping('10000000000000', 'owner');

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->ba->proxyAuth();

        $this->testData[__FUNCTION__]['request']['content']['user_id'] = $user['id'];

        $this->startTest();

        $merchantMap = \DB::connection('test')->table('merchant_map')
                          ->where('merchant_id', '7gcKngYfqyDMjN')
                          ->first();

        $this->assertEquals($merchantMap->entity_id, 'E15BhsdMSofcUJ');
        $this->assertEquals($merchantMap->entity_type, 'group');
        $this->assertEquals($merchantMap->merchant_id, '7gcKngYfqyDMjN');
    }

    /**
     * Test case for successful creation of subM from partner dashboard. Validates the increment of couter in the new ratelimiter
     */
    public function testCreateSubMerchant()
    {
        Mail::fake();

        $this->fixtures->merchant->addFeatures(['aggregator']);
        $this->fixtures->merchant->editPricingPlanId(TestPricing::DEFAULT_PRICING_PLAN_ID);

        $user = $this->createUserMerchantMapping('10000000000000', 'owner');

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $this->mockAllExperiments("enable");

        $terminalsServiceMock = $this->getTerminalsServiceMock();

        $terminalsServiceMock->shouldNotHaveReceived('handleRequestAndResponse');

        $this->startTest();

        $redis = Redis::connection('mutex_redis')->client();

        $redisKey = (new PartnershipsRateLimiter(PartnerConstants::ADD_ACCOUNT))->getRateLimitRedisKey('10000000000000');

        $this->assertEquals(1, $redis->get($redisKey));

        Mail::assertQueued(CreateSubMerchantMail::class, function ($mail)
        {
            return $mail->hasTo('test@razorpay.com', 'Submerchant');
        });

        list($testMapping, $liveMapping) = $this->getLastMappingForBothModes();

        $this->assertNull($testMapping);

        $this->assertNull($liveMapping);

        $testMerchantBalance = $this->getDbLastEntity('merchant_balance', 'test');
        $this->assertNotNull($testMerchantBalance);

        $testBalanceConfig = $this->getDbLastEntity('balance_config', 'test');
        $this->assertNotNull($testBalanceConfig);

        $testBalance = $this->getDbLastEntity('balance', 'test');
        $this->assertNotNull($testBalance);

        $testBankAccount = $this->getDbLastEntity('bank_account', 'test');
        $this->assertNotNull($testBankAccount);

        $testSchedule = $this->getDbLastEntity('schedule', 'test');
        $this->assertNotNull($testSchedule);

        $testScheduleTask = $this->getDbLastEntity('schedule_task', 'test');
        $this->assertNotNull($testScheduleTask);

    }

    /**
     * Test case for failed creation of subM from partner dashboard. validates the rate limit exceeded exception for new ratelimiter
     */
    public function testCreateSubMerchantWithRatelimitExceeded()
    {
        Mail::fake();

        $this->fixtures->merchant->addFeatures(['aggregator']);
        $this->fixtures->merchant->editPricingPlanId(TestPricing::DEFAULT_PRICING_PLAN_ID);

        $user = $this->createUserMerchantMapping('10000000000000', 'owner');

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $this->mockAllExperiments("enable");

        $redis = Redis::connection('mutex_redis')->client();

        $redisKey = (new PartnershipsRateLimiter(PartnerConstants::ADD_ACCOUNT))->getRateLimitRedisKey('10000000000000');

        $redis->set($redisKey, RateLimitConstants::RATELIMIT_CONFIG[PartnerConstants::ADD_ACCOUNT][RateLimitConstants::THRESHOLD]);

        $this->startTest();

    }

    /**
     * Test case for successful creation of subM from partner dashboard.
     * When ramp evaluation skips experiment for the partner and ratelimiter is not invoked
     */
    public function testCreateSubMerchantWithoutRateLimiting()
    {
        $this->markTestSkipped('Experiment is on 100% ramp');

        Mail::fake();

        $this->fixtures->merchant->addFeatures(['aggregator']);
        $this->fixtures->merchant->editPricingPlanId(TestPricing::DEFAULT_PRICING_PLAN_ID);

        $user = $this->createUserMerchantMapping('10000000000000', 'owner');

        $testData = $this->testData['testCreateSubMerchant'];

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $this->mockAllExperiments("disable");

        $response = $this->runRequestResponseFlow($testData);

        $redis = Redis::connection('mutex_redis')->client();

        $redisKey = (new PartnershipsRateLimiter(PartnerConstants::ADD_ACCOUNT))->getRateLimitRedisKey('10000000000000');

        $this->assertNull($redis->get($redisKey));

        Mail::assertQueued(CreateSubMerchantMail::class, function ($mail)
        {
            return $mail->hasTo('test@razorpay.com', 'Submerchant');
        });

        list($testMapping, $liveMapping) = $this->getLastMappingForBothModes();

        $this->assertNull($testMapping);

        $this->assertNull($liveMapping);
    }

    protected function mockRazorxTreatment()
    {
        // Mock Razorx
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                              function ($mid, $feature, $mode)
                              {
                                  if ($feature === 'settlement_service_ramp')
                                  {
                                      return 'off';
                                  }
                                  return 'on';
                              }));
    }

    public function testCreateSubMerchantWithoutFeatureMarketplaceOrPartner()
    {
        $user = $this->createUserMerchantMapping('10000000000000', 'owner');

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $this->startTest();
    }

    public function testCreateSubMerchantWithoutName()
    {
        $this->fixtures->merchant->addFeatures(['aggregator']);

        $user = $this->createUserMerchantMapping('10000000000000', 'owner');

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $this->startTest();
    }

    public function testCreateSubMerchantWrongUserRole()
    {
        $this->fixtures->merchant->addFeatures(['aggregator']);

        $user = $this->createUserMerchantMapping('10000000000000', 'finance');

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id'], 'finance');

        $this->startTest();
    }

    public function testCreateSubMerchantWithEmail()
    {
        $this->fixtures->merchant->addFeatures(['aggregator']);
        $this->fixtures->merchant->editPricingPlanId(TestPricing::DEFAULT_PRICING_PLAN_ID);

        $user = $this->createUserMerchantMapping('10000000000000', 'owner');

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $this->startTest();

        list($testMapping, $liveMapping) = $this->getLastMappingForBothModes();

        $this->assertNull($testMapping);

        $this->assertNull($liveMapping);
    }

    public function testSegmentEventSkipPartnerAddedFirstSubmerchant()
    {
        Mail::fake();

        $this->fixtures->merchant->addFeatures(['aggregator']);
        $this->fixtures->merchant->editPricingPlanId(TestPricing::DEFAULT_PRICING_PLAN_ID);

        $partnerId = '10000000000000';
        $app       = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'aggregator'], true);
        $partner   = (new MerchantRepository)->findOrFail($partnerId);
        $user      = $this->createUserMerchantMapping($partnerId, 'owner');
        $this->ba->proxyAuth('rzp_test_' . $partnerId, $user['id']);

        $submerchantId  = '101Submerchant';
        $submerchantId2 = '102Submerchant';

        $this->createSubMerchant($partner, $app, ['id' => $submerchantId]);
        $this->createSubMerchant($partner, $app, ['id' => $submerchantId2]);

        $segmentMock = $this->getMockBuilder(SegmentAnalyticsClient::class)
                            ->setMethods(['pushIdentifyAndTrackEvent'])
                            ->getMock();

        $this->app->instance('segment-analytics', $segmentMock);

        $segmentMock->expects($this->exactly(1))
                    ->method('pushIdentifyAndTrackEvent')
                    ->will($this->returnCallback(function($merchant, $properties, $eventName) {
                        $this->assertNotNull($properties);
                        $this->assertTrue(in_array($eventName, ["Affiliate Account Added"], true));
                    }));

        $this->startTest();
    }

    public function testSegmentEventPartnerAddedFirstSubmerchant()
    {
        Mail::fake();

        $this->fixtures->merchant->addFeatures(['aggregator']);
        $this->fixtures->merchant->editPricingPlanId(TestPricing::DEFAULT_PRICING_PLAN_ID);

        $partnerId = '10000000000000';
        $app       = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'aggregator'], true);
        $partner   = (new MerchantRepository)->findOrFail($partnerId);
        $user      = $this->createUserMerchantMapping($partnerId, 'owner');
        $this->ba->proxyAuth('rzp_test_' . $partnerId, $user['id']);

        $submerchantId = '101Submerchant';

        $this->createSubMerchant($partner, $app, ['id' => $submerchantId]);

        $segmentMock = $this->getMockBuilder(SegmentAnalyticsClient::class)
                            ->setMethods(['pushIdentifyAndTrackEvent'])
                            ->getMock();

        $this->app->instance('segment-analytics', $segmentMock);

        $segmentMock->expects($this->exactly(2))
                    ->method('pushIdentifyAndTrackEvent')
                    ->will($this->returnCallback(function($merchant, $properties, $eventName) {
                        $this->assertNotNull($properties);
                        $this->assertTrue(in_array($eventName, ["Affiliate Account Added", "Partner Added First Submerchant"], true));
                    }));

        $this->startTest();
    }

    public function testSegmentEventPushSubmerchantFirstTransaction()
    {
        $this->fixtures->merchant->addFeatures(['aggregator']);
        $this->fixtures->merchant->editPricingPlanId(TestPricing::DEFAULT_PRICING_PLAN_ID);

        $partnerId = '10000000000000';
        $app       = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'aggregator'], true);
        $partner   = (new MerchantRepository)->findOrFail($partnerId);
        $user      = $this->createUserMerchantMapping($partnerId, 'owner');
        $this->ba->proxyAuth('rzp_test_' . $partnerId, $user['id']);

        $submerchantId = '101Submerchant';

        $this->createSubMerchant($partner, $app, ['id' => $submerchantId]);

        $this->fixtures->payment->createAuthorized(['merchant_id'=> '101Submerchant']);

        $input = [
            "experiment_id" => "K8zmvNaQrRuz5g",
            "id"            => "101Submerchant",
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $segmentMock = $this->getMockBuilder(SegmentAnalyticsClient::class)
            ->setMethods(['pushIdentifyAndTrackEvent','buildRequestAndSend'])
            ->getMock();

        $this->app->instance('segment-analytics', $segmentMock);

        $segmentMock->expects($this->exactly(1))
            ->method('pushIdentifyAndTrackEvent')
            ->will($this->returnCallback(function($merchant, $properties, $eventName) {
                $this->assertNotNull($properties);
                $this->assertTrue(in_array($eventName, ["Submerchant First Transaction"], true));
            }));

        $test = new SubmerchantFirstTransactionEvent('test',[]);
        $test->handle();
    }

    public function testSegmentMultipleEventPushSubmerchantFirstTransaction()
    {
        $this->fixtures->merchant->addFeatures(['aggregator']);
        $this->fixtures->merchant->editPricingPlanId(TestPricing::DEFAULT_PRICING_PLAN_ID);

        $partnerId = '10000000000000';
        $app       = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'aggregator'], true);
        $partner   = (new MerchantRepository)->findOrFail($partnerId);
        $user      = $this->createUserMerchantMapping($partnerId, 'owner');
        $this->ba->proxyAuth('rzp_test_' . $partnerId, $user['id']);

        $submerchantId  = '101Submerchant';
        $submerchantId2 = '102Submerchant';

        $this->createSubMerchant($partner, $app, ['id' => $submerchantId]);
        $this->createSubMerchant($partner, $app, ['id' => $submerchantId2]);

        $this->fixtures->payment->createAuthorized(['merchant_id'=> $submerchantId]);
        $this->fixtures->payment->createAuthorized(['merchant_id'=> $submerchantId2]);

        $input = [
            "experiment_id" => "K8zmvNaQrRuz5g",
            "id"            => $submerchantId,
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $input = [
            "experiment_id" => "K8zmvNaQrRuz5g",
            "id"            => $submerchantId2,
        ];

        $this->mockSplitzTreatment($input, $output);

        $segmentMock = $this->getMockBuilder(SegmentAnalyticsClient::class)
            ->setMethods(['pushIdentifyAndTrackEvent','buildRequestAndSend'])
            ->getMock();

        $this->app->instance('segment-analytics', $segmentMock);

        $segmentMock->expects($this->exactly(2))
            ->method('pushIdentifyAndTrackEvent')
            ->will($this->returnCallback(function($merchant, $properties, $eventName) {
                $this->assertNotNull($properties);
                $this->assertTrue(in_array($eventName, ["Submerchant First Transaction"], true));
            }));

        $test = new SubmerchantFirstTransactionEvent('test',[]);
        $test->handle();
    }

    public function testSkipSegmentEventPushForFirstTransactionBeforeLastCronTime()
    {
        $this->fixtures->merchant->addFeatures(['aggregator']);
        $this->fixtures->merchant->editPricingPlanId(TestPricing::DEFAULT_PRICING_PLAN_ID);

        $partnerId = '10000000000000';
        $app       = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'aggregator'], true);
        $partner   = (new MerchantRepository)->findOrFail($partnerId);
        $user      = $this->createUserMerchantMapping($partnerId, 'owner');
        $this->ba->proxyAuth('rzp_test_' . $partnerId, $user['id']);

        $submerchantId = '101Submerchant';

        $this->createSubMerchant($partner, $app, ['id' => $submerchantId]);

        $this->fixtures->payment->createAuthorized(['merchant_id' => '101Submerchant', 'created_at' => '1660731670']);

        $segmentMock = $this->getMockBuilder(SegmentAnalyticsClient::class)
            ->setMethods(['pushIdentifyAndTrackEvent','buildRequestAndSend'])
            ->getMock();

        $this->app->instance('segment-analytics', $segmentMock);

        $segmentMock->expects($this->exactly(0))
            ->method('pushIdentifyAndTrackEvent')
            ->willReturn(false);

        $test = new SubmerchantFirstTransactionEvent('test',[]);
        $test->handle();
    }

    public function testSkipSegmentEventSubmerchantFirstTransaction()
    {
        $this->fixtures->merchant->addFeatures(['aggregator']);
        $this->fixtures->merchant->editPricingPlanId(TestPricing::DEFAULT_PRICING_PLAN_ID);

        $partnerId = '10000000000000';
        $app       = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'aggregator'], true);
        $partner   = (new MerchantRepository)->findOrFail($partnerId);
        $user      = $this->createUserMerchantMapping($partnerId, 'owner');
        $this->ba->proxyAuth('rzp_test_' . $partnerId, $user['id']);

        $submerchantId = '101Submerchant';

        $this->createSubMerchant($partner, $app, ['id' => $submerchantId]);

        $segmentMock = $this->getMockBuilder(SegmentAnalyticsClient::class)
            ->setMethods(['pushIdentifyAndTrackEvent'])
            ->getMock();

        $this->app->instance('segment-analytics', $segmentMock);

        $segmentMock->expects($this->exactly(0))
            ->method('pushIdentifyAndTrackEvent')
            ->willReturn(false);

        $test = new SubmerchantFirstTransactionEvent('test',[]);
        $test->handle();
    }

    public function testCreateSubMerchantWithEmailUserExists()
    {
        Mail::fake();

        $this->fixtures->merchant->addFeatures(['aggregator']);

        $this->fixtures->create('user', ['email' => 'submerchant@razorpay.com']);

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest();

        $submerchant = $this->getLastEntity('merchant', true);

        Mail::assertQueued(CreateSubMerchantMail::class, function ($mail)
        {
            return $mail->hasTo('submerchant@razorpay.com', 'SubmerchantTwo');
        });

        $mapping = $this->fixtures->user->getMerchantUserMapping($submerchant['id'], 'MerchantUser01');

        $this->assertEquals(1, count($mapping));
    }

    private function createUserMerchantMapping($merchantId, $role)
    {
        $user = $this->fixtures->create('user');

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => $merchantId,
            'role'        => $role,
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        return $user;

    }

    public function testCreateSubMerchantWithDuplicateEmail()
    {
        $user = $this->createUserMerchantMapping('10000000000000', 'owner');

        // Just to check email collisions are still errors
        $this->fixtures->create('merchant', ['id' => '10000000000002', 'email' => 'test2@razorpay.com']);

        $this->fixtures->merchant->addFeatures(['aggregator']);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $this->startTest();
    }

    public function testCreateSubMerchantByFullyManagedWOEmail()
    {
        Mail::fake();

        $app = $this->markPartnerAndCreateAppAndUserMapping('fully_managed');

        $configAttributes = [
            PartnerConfig\Entity::DEFAULT_PLAN_ID => Pricing::DEFAULT_PRICING_PLAN_ID,
        ];

        $this->createConfigForPartnerApp($app->getId(), null, $configAttributes);

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest();

        Mail::assertQueued(CreateSubMerchantPartnerMail::class, function ($mail)
        {
            return $mail->hasTo('test@razorpay.com');
        });

        Mail::assertNotQueued(CreateSubMerchantAffiliateMail::class);

        Mail::assertNotQueued(CreateSubMerchantMail::class);

        $submerchant = $this->getLastEntity('merchant', true);

        $mapping = $this->fixtures->user->getMerchantUserMapping($submerchant['id'],'MerchantUser01');

        $this->assertEquals(1, count($mapping));

        $this->verifyAccessMapEntries($app, $submerchant);
    }

    public function testCreateSubMerchantByFullyManagedWithEmail()
    {
        Mail::fake();

        $app = $this->markPartnerAndCreateAppAndUserMapping('fully_managed');

        $configAttributes = [
            PartnerConfig\Entity::DEFAULT_PLAN_ID => Pricing::DEFAULT_PRICING_PLAN_ID,
        ];

        $this->createConfigForPartnerApp($app->getId(), null, $configAttributes);

        $config = $this->createConfigForPartnerApp($app->getId());

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest();

        Mail::assertQueued(CreateSubMerchantPartnerMail::class, function ($mail)
        {
            return $mail->hasTo('test@razorpay.com');
        });

        Mail::assertQueued(CreateSubMerchantAffiliateMail::class, function ($mail)
        {
            $data = $mail->viewData;

            $this->assertEquals('org_100000razorpay', $data['org']['id']);

            return $mail->hasTo('testsub@razorpay.com', 'Submerchant');
        });

        Mail::assertNotSent(PasswordResetMail::class);

        $submerchant = $this->getLastEntity('merchant', true);

        $mapping = $this->fixtures->user->getMerchantUserMapping($submerchant['id'], 'MerchantUser01');

        $this->assertEquals(1, count($mapping));

        $this->verifyAccessMapEntries($app, $submerchant);
    }

    public function testCreateMalaysianSubMerchantByFullyManagedWithEmail()
    {
        Mail::fake();

        $app = $this->markPartnerAndCreateAppAndUserMapping('fully_managed', 10000000000000, 'MY');

        $configAttributes = [
            PartnerConfig\Entity::DEFAULT_PLAN_ID => Pricing::DEFAULT_PRICING_PLAN_ID,
        ];

        $this->createConfigForPartnerApp($app->getId(), null, $configAttributes);

        $config = $this->createConfigForPartnerApp($app->getId());

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest();

        Mail::assertQueued(CreateSubMerchantPartnerMail::class, function ($mail)
        {
            return $mail->hasTo('test@razorpay.com');
        });

        Mail::assertQueued(CreateSubMerchantAffiliateMail::class, function ($mail)
        {
            $data = $mail->viewData;

            $this->assertEquals('org_100000razorpay', $data['org']['id']);

            return $mail->hasTo('testsub@razorpay.com', 'Submerchant');
        });

        Mail::assertNotSent(PasswordResetMail::class);

        $submerchant = $this->getLastEntity('merchant', true);

        $mapping = $this->fixtures->user->getMerchantUserMapping($submerchant['id'], 'MerchantUser01');

        $this->assertEquals(1, count($mapping));

        $this->verifyAccessMapEntries($app, $submerchant);
    }

    public function testCreateSubMerchantForXByFullyManagedWithEmail()
    {
        Mail::fake();

        $app = $this->markPartnerAndCreateAppAndUserMapping('fully_managed');

        $configAttributes = [
            PartnerConfig\Entity::DEFAULT_PLAN_ID => Pricing::DEFAULT_PRICING_PLAN_ID,
        ];

        $this->createConfigForPartnerApp($app->getId(), null, $configAttributes);

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest();

        Mail::assertQueued(CreateSubMerchantAffiliateMailForX::class, function ($mail)
        {
            $data = $mail->viewData;

            $this->assertEquals('org_100000razorpay', $data['org']['id']);

            return $mail->hasTo('testsub@razorpay.com', 'Submerchant');
        });

        Mail::assertQueued(CreateSubMerchantPartnerForX::class, function ($mail)
        {
            return $mail->hasTo('test@razorpay.com');
        });

        Mail::assertNotSent(PasswordResetMail::class);

        $submerchant = $this->getLastEntity('merchant', true);

        $mapping = $this->fixtures->user->getMerchantUserMapping($submerchant['id'], 'MerchantUser01', 'banking');

        $this->assertEquals(1, count($mapping));

        $this->verifyAccessMapEntries($app, $submerchant);
    }

    public function testCreateSubMerchantByFullyManagedWithEmailUserExists()
    {
        Mail::fake();

        $app = $this->markPartnerAndCreateAppAndUserMapping('fully_managed');

        $configAttributes = [
            PartnerConfig\Entity::DEFAULT_PLAN_ID => Pricing::DEFAULT_PRICING_PLAN_ID,
        ];

        $this->createConfigForPartnerApp($app->getId(), null, $configAttributes);

        $user2 = $this->fixtures->create('user', ['email' => 'testsub@razorpay.com']);

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest();

        Mail::assertQueued(CreateSubMerchantPartnerMail::class, function ($mail)
        {
            return $mail->hasTo('test@razorpay.com');
        });

        Mail::assertQueued(CreateSubMerchantAffiliateMail::class, function ($mail)
        {
            $data = $mail->viewData;

            $this->assertEquals('org_100000razorpay', $data['org']['id']);

            return $mail->hasTo('testsub@razorpay.com', 'Submerchant');
        });

        $submerchant = $this->getLastEntity('merchant', true);

        $mapping = $this->fixtures->user->getMerchantUserMapping($submerchant['id'], 'MerchantUser01');

        $this->assertEquals(1, count($mapping));

        $mapping2 = $this->fixtures->user->getMerchantUserMapping($submerchant['id'], $user2['id']);

        $this->assertEquals(1, count($mapping2));

        $this->verifyAccessMapEntries($app, $submerchant);
    }

    public function testCreateSubMerchantForXByFullyManagedWithEmailUserExists()
    {
        Mail::fake();

        $app = $this->markPartnerAndCreateAppAndUserMapping('fully_managed');

        $configAttributes = [
            PartnerConfig\Entity::DEFAULT_PLAN_ID => Pricing::DEFAULT_PRICING_PLAN_ID,
        ];

        $this->createConfigForPartnerApp($app->getId(), null, $configAttributes);

        $user2 = $this->fixtures->create('user', ['email' => 'testsub@razorpay.com']);

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest();

        Mail::assertQueued(CreateSubMerchantAffiliateMailForX::class, function ($mail)
        {
            $data = $mail->viewData;

            $this->assertEquals('org_100000razorpay', $data['org']['id']);

            return $mail->hasTo('testsub@razorpay.com', 'Submerchant');
        });

        Mail::assertQueued(CreateSubMerchantPartnerForX::class, function ($mail)
        {
            return $mail->hasTo('test@razorpay.com');
        });

        $submerchant = $this->getLastEntity('merchant', true);

        $mapping = $this->fixtures->user->getMerchantUserMapping($submerchant['id'], 'MerchantUser01', 'banking');

        $this->assertCount(1, $mapping);

        $mapping2 = $this->fixtures->user->getMerchantUserMapping($submerchant['id'], $user2['id'], 'banking');

        $this->assertCount(1, $mapping2);

        $this->verifyAccessMapEntries($app, $submerchant);
    }

    public function testCreateSubMerchantByAggregatorWithEmail()
    {
        Mail::fake();

        $app = $this->markPartnerAndCreateAppAndUserMapping('aggregator');

        $this->fixtures->on('test')->create('merchant_detail:sane', [
            'merchant_id' => $app->merchant_id
        ]);

        $this->fixtures->on('live')->create('merchant_detail:sane', [
            'merchant_id' => $app->merchant_id
        ]);

        $configAttributes = [
            PartnerConfig\Entity::DEFAULT_PLAN_ID => Pricing::DEFAULT_PRICING_PLAN_ID,
        ];

        $this->createConfigForPartnerApp($app->getId(), null, $configAttributes);

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest();

        Mail::assertQueued(CreateSubMerchantPartnerMail::class, function($mail) {
            return $mail->hasTo('test@razorpay.com');
        });

        Mail::assertQueued(CreateSubMerchantAffiliateMail::class, function($mail) {
            $data = $mail->viewData;

            $this->assertEquals('org_100000razorpay', $data['org']['id']);

            return $mail->hasTo('testsub@razorpay.com', 'Submerchant');
        });

        $submerchant = $this->getLastEntity('merchant', true);

        $mapping = $this->fixtures->user->getMerchantUserMapping($submerchant['id'], 'MerchantUser01');

        // This should be empty once aggregator type's dashboard access is removed
        // in withEmail cases.
        $this->assertEquals(1, count($mapping));

        $this->verifyAccessMapEntries($app, $submerchant);
    }

    public function testCreateMalaysianSubMerchantByAggregatorWithEmailOnProdEnv()
    {
        $this->changeEnvToNonTest();

        Mail::fake();


        $org = $this->fixtures->create('org:curlec_org');

        $merchantAttributes = [
            'id' => "10000121212121",
            'live' => 1,
            'activated_at' => Carbon::now()->subDays(2)->getTimestamp(),
            'org_id' => $org->getId()
        ];

        $merchant = $this->fixtures->create('merchant', $merchantAttributes);

        $app = $this->markPartnerAndCreateAppAndUserMapping('aggregator', "10000121212121", 'MY');

        $this->fixtures->on('test')->create('merchant_detail:sane', [
            'merchant_id' => $app->merchant_id
        ]);

        $this->fixtures->on('live')->create('merchant_detail:sane', [
            'merchant_id' => $app->merchant_id
        ]);

        $configAttributes = [
            PartnerConfig\Entity::DEFAULT_PLAN_ID => Pricing::DEFAULT_PRICING_PLAN_ID,
        ];

        $this->createConfigForPartnerApp($app->getId(), null, $configAttributes);

        $merchantUser = $this->fixtures->user->createUserForMerchant(
            "10000121212121", [], 'owner', 'live');

        $this->ba->proxyAuth('rzp_live_' .   "10000121212121", $merchantUser['id']);

        $this->startTest();

        Mail::assertQueued(CreateSubMerchantPartnerMail::class, function($mail) {
            return $mail->hasTo('test@razorpay.com');
        });

        Mail::assertQueued(CreateSubMerchantAffiliateMail::class, function($mail) {
            $data = $mail->viewData;

            $this->assertEquals('org_100000razorpay', $data['org']['id']);

            return $mail->hasTo('success@curlec.com', 'Submerchant');
        });

        $submerchant = $this->getLastEntity('merchant', true);

        $mapping = $this->fixtures->user->getMerchantUserMapping($submerchant['id'], 'MerchantUser01');

        // This should be empty once aggregator type's dashboard access is removed
        // in withEmail cases.
        $this->assertEquals(1, count($mapping));

        $this->verifyAccessMapEntries($app, $submerchant);

        $this->assertEquals('MY', $submerchant['country_code']);
    }

    public function testCreateMalaysianSubMerchantByAggregatorWithEmail()
    {
        Mail::fake();

        $app = $this->markPartnerAndCreateAppAndUserMapping('aggregator', '10000000000000', 'MY');

        $this->fixtures->on('test')->create('merchant_detail:sane', [
            'merchant_id' => $app->merchant_id
        ]);

        $this->fixtures->on('live')->create('merchant_detail:sane', [
            'merchant_id' => $app->merchant_id
        ]);

        $configAttributes = [
            PartnerConfig\Entity::DEFAULT_PLAN_ID => Pricing::DEFAULT_PRICING_PLAN_ID,
        ];

        $this->createConfigForPartnerApp($app->getId(), null, $configAttributes);

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest();

        Mail::assertQueued(CreateSubMerchantPartnerMail::class, function($mail) {
            return $mail->hasTo('test@razorpay.com');
        });

        Mail::assertQueued(CreateSubMerchantAffiliateMail::class, function($mail) {
            $data = $mail->viewData;

            $this->assertEquals('org_100000razorpay', $data['org']['id']);

            return $mail->hasTo('success@curlec.com', 'Submerchant');
        });

        $submerchant = $this->getLastEntity('merchant', true);

        $mapping = $this->fixtures->user->getMerchantUserMapping($submerchant['id'], 'MerchantUser01');

        // This should be empty once aggregator type's dashboard access is removed
        // in withEmail cases.
        $this->assertEquals(1, count($mapping));

        $this->verifyAccessMapEntries($app, $submerchant);

        $this->assertEquals('MY', $submerchant['country_code']);
    }

    public function testCreateSubMerchantForXByAggregatorWithEmail()
    {
        Mail::fake();

        $this->mockSplitzEvaluation();

        $app = $this->markPartnerAndCreateAppAndUserMapping('aggregator');

        $configAttributes = [
            PartnerConfig\Entity::DEFAULT_PLAN_ID => Pricing::DEFAULT_PRICING_PLAN_ID,
        ];

        $this->createConfigForPartnerApp($app->getId(), null, $configAttributes);

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->mockAllExperiments("enable");

        $this->startTest();

        Mail::assertQueued(CreateSubMerchantAffiliateMailForX::class, function ($mail)
        {
            $data = $mail->viewData;

            $this->assertEquals('org_100000razorpay', $data['org']['id']);

            return $mail->hasTo('testsub@razorpay.com', 'Submerchant');
        });

        Mail::assertQueued(CreateSubMerchantPartnerForX::class, function ($mail)
        {
            return $mail->hasTo('test@razorpay.com');
        });

        $submerchant = $this->getLastEntity('merchant', true);

        $mapping = $this->fixtures->user->getMerchantUserMapping($submerchant['id'], 'MerchantUser01', 'banking');

        // This should be empty once aggregator type's dashboard access is removed
        // in withEmail cases.
        $this->assertEquals(1, count($mapping));
        $this->assertEquals($mapping->first()->role, Role::VIEW_ONLY);

        $this->verifyAccessMapEntries($app, $submerchant);
    }

    public function testCreateSubMerchantForXByResellerWithEmail()
    {
        Mail::fake();

        $app = $this->markPartnerAndCreateAppAndUserMapping('reseller');

        $configAttributes = [
            PartnerConfig\Entity::DEFAULT_PLAN_ID => Pricing::DEFAULT_PRICING_PLAN_ID,
        ];

        $this->createConfigForPartnerApp($app->getId(), null, $configAttributes);

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest();

        Mail::assertQueued(CreateSubMerchantAffiliateMailForX::class, function ($mail)
        {
            $data = $mail->viewData;

            $this->assertEquals('org_100000razorpay', $data['org']['id']);

            return $mail->hasTo('testsub@razorpay.com', 'Submerchant');
        });

        Mail::assertQueued(CreateSubMerchantPartnerForX::class, function ($mail)
        {
            return $mail->hasTo('test@razorpay.com');
        });

        $submerchant = $this->getLastEntity('merchant', true);

        $this->verifyAccessMapEntries($app, $submerchant);
    }

    public function testCreateSubMerchantByAggregatorBatch()
    {
        Mail::fake();

        $app = $this->markPartnerAndCreateAppAndUserMapping('aggregator');

        $configAttributes = [
            PartnerConfig\Entity::DEFAULT_PLAN_ID => Pricing::DEFAULT_PRICING_PLAN_ID,
        ];

        $this->createConfigForPartnerApp($app->getId(), null, $configAttributes);

        $this->ba->batchAppAuth();

        $redis = Redis::connection('mutex_redis')->client();

        $redisKey = (new PartnershipsRateLimiter(PartnerConstants::ADD_MULTIPLE_ACCOUNT))->getRateLimitRedisKey("10000000000000");

        $terminalsServiceMock = $this->getTerminalsServiceMock();

        $terminalsServiceMock->shouldNotHaveReceived('handleRequestAndResponse');

        $response = $this->startTest();

        $submerchant = $this->getLastEntity('merchant', true);

        $mapping = $this->fixtures->user->getMerchantUserMapping($submerchant['id'], 'MerchantUser01');

        $this->assertEquals(1, count($mapping));

        $this->verifyAccessMapEntries($app, $submerchant);

        $counter = $redis->get($redisKey);

        $this->assertEquals(1, $counter);

        $submerchantUser = $this->getLastEntity('user', true);

        $this->assertEquals('testsub@razorpay.com', $submerchantUser['email']);
    }

    public function testCreateSubMerchantWithMobileNoByAggregatorBatchForX()
    {
        Mail::fake();

        $app = $this->markPartnerAndCreateAppAndUserMapping('aggregator');

        $configAttributes = [
            PartnerConfig\Entity::DEFAULT_PLAN_ID => Pricing::DEFAULT_PRICING_PLAN_ID,
        ];

        $this->createConfigForPartnerApp($app->getId(), null, $configAttributes);

        $this->ba->batchAppAuth();

        $redis = Redis::connection('mutex_redis')->client();

        $redisKey = (new PartnershipsRateLimiter(PartnerConstants::ADD_MULTIPLE_ACCOUNT))->getRateLimitRedisKey("10000000000000");

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn('on');

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $expectedParams = [
            'subMerchantName' => 'Submerchant'
        ];

        (new MerchantTest())->expectStorkSmsRequest($storkMock,'sms.onboarding.partner_submerchant_invite_v2', '+919876543210', $expectedParams);

        $this->startTest();

        $submerchant = $this->getLastEntity('merchant', true);

        $mapping = $this->fixtures->user->getMerchantUserMapping($submerchant['id'], 'MerchantUser01', 'banking');

        $this->assertEquals(1, count($mapping));

        $this->verifyAccessMapEntries($app, $submerchant);

        $counter = $redis->get($redisKey);

        $this->assertEquals(1, $counter);

        $submerchantUser = $this->getLastEntity('user', true);

        // - For X sub-merchants, if a mobile number is sent in the bulk upload file, we send a password reset
        //   link to email as well as sms.
        // - When the email is sent, `(new User\Service())->getTokenWithExpiry` is called.
        // - The same thing happens when an SMS is to be sent.
        // - `getTokenWithExpiry` replaces the previously generated token.
        // = So, the password reset link sent in the email cannot be used to reset the password and log in.
        // - The sub-merchant must request a new link from forgot password flow.
        // - This assertion here checks that when SMS is sent, the password reset token sent in the email
        //   is the same as the one saved in user entity
        $user = (new UserRepository)->findOrFailPublic($submerchantUser['id']);

        $mail = Mail::queued(CreateSubMerchantAffiliateMailForX::class)->first();

        $this->assertEquals($user->getPasswordResetToken(), $mail->viewData["token"]);

        $this->assertEquals('testsub@razorpay.com', $submerchantUser['email']);

        $this->assertEquals('+919876543210', $submerchantUser['contact_mobile']);

        $this->assertEquals('banking', $mapping->first()->product);

        $submerchantDetail = $this->getLastEntity('merchant_detail', true);

        $this->assertEquals('+919876543210', $submerchantDetail['contact_mobile']);
    }

    public function testCreateSubMerchantByAggregatorBatchRatelimitExceeded()
    {
        Mail::fake();

        $app = $this->markPartnerAndCreateAppAndUserMapping('aggregator');

        $configAttributes = [
            PartnerConfig\Entity::DEFAULT_PLAN_ID => Pricing::DEFAULT_PRICING_PLAN_ID,
        ];

        $this->mockAllExperiments("disable");

        $this->createConfigForPartnerApp($app->getId(), null, $configAttributes);

        $this->ba->batchAppAuth();

        $redis = Redis::connection('mutex_redis')->client();

        $redisKey = (new PartnershipsRateLimiter(PartnerConstants::ADD_MULTIPLE_ACCOUNT))->getRateLimitRedisKey("10000000000000");

        $counter = $redis->set($redisKey, RateLimitBatch::THRESHOLD_RATE_LIMIT_COUNT+1);

        $this->startTest();
    }

    /**
     * Test case for failed creation of subM via batch. validates the rate limit exceeded exception for new ratelimiter
     */
    public function testCreateSubMByAggregatorBatchRatelimitExceededNewRatelimiter()
    {
        Mail::fake();

        $app = $this->markPartnerAndCreateAppAndUserMapping('aggregator');

        $configAttributes = [
            PartnerConfig\Entity::DEFAULT_PLAN_ID => Pricing::DEFAULT_PRICING_PLAN_ID,
        ];

        $this->mockAllExperiments("enable");

        $this->createConfigForPartnerApp($app->getId(), null, $configAttributes);

        $this->ba->batchAppAuth();

        $redis = Redis::connection('mutex_redis')->client();

        $redisKey = (new PartnershipsRateLimiter(PartnerConstants::ADD_MULTIPLE_ACCOUNT))->getRateLimitRedisKey("10000000000000");

        $counter = $redis->set($redisKey, RateLimitConstants::RATELIMIT_CONFIG[PartnerConstants::ADD_MULTIPLE_ACCOUNT][RateLimitConstants::THRESHOLD]+1);

        $this->startTest();
    }

    /**
     * Test case for successful creation of subM via batch. Validates the counter increment
     */
    public function testCreateSubMByAggregatorBatchNewRatelimiter()
    {
        Mail::fake();

        $app = $this->markPartnerAndCreateAppAndUserMapping('aggregator');

        $configAttributes = [
            PartnerConfig\Entity::DEFAULT_PLAN_ID => Pricing::DEFAULT_PRICING_PLAN_ID,
        ];

        $this->createConfigForPartnerApp($app->getId(), null, $configAttributes);

        $this->ba->batchAppAuth();

        $redis = Redis::connection('mutex_redis')->client();

        $request = $this->testData['testCreateSubMerchantByAggregatorBatch'];

        $this->runRequestResponseFlow($request);

        $redisKey = (new PartnershipsRateLimiter(PartnerConstants::ADD_MULTIPLE_ACCOUNT))->getRateLimitRedisKey("10000000000000");

        $counter = $redis->get($redisKey);

        $this->assertEquals(1, $counter);
    }

    private function mockAllExperiments(string $variant = 'enable')
    {
        $output = [
            "response" => [
                "variant" => [
                    "name" => $variant,
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);
    }

    public function testCreateSubMerchantByAdminForAggregatorBatch()
    {
        Mail::fake();

        $app = $this->markPartnerAndCreateAppAndUserMapping('aggregator');

        $configAttributes = [
            PartnerConfig\Entity::DEFAULT_PLAN_ID => Pricing::DEFAULT_PRICING_PLAN_ID,
        ];

        $this->createConfigForPartnerApp($app->getId(), null, $configAttributes);

        $this->ba->batchAppAuth();

        $this->startTest();

        $submerchantDetail = $this->getLastEntity('merchant_detail', true);
        $this->assertEquals($submerchantDetail[MerchantDetail::ACTIVATION_STATUS], MerchantDetailStatus::ACTIVATED);
    }

    public function testCreateSubMerchantWithAutoPricingPlanByAdminBatch()
    {
        $this->fixtures->create('feature', [
            'name' => FeatureConstants::SUB_MERCHANT_PRICING_AUTOMATION,
            'entity_id' => '100000razorpay',
            'entity_type' => 'org',
        ]);

        Mail::fake();

        $app = $this->markPartnerAndCreateAppAndUserMapping('fully_managed');

        $configAttributes = [
            PartnerConfig\Entity::DEFAULT_PLAN_ID => Pricing::DEFAULT_PRICING_PLAN_ID,
        ];

        $this->createConfigForPartnerApp($app->getId(), null, $configAttributes);

        $this->ba->batchAppAuth();

        $this->fixtures->pricing->createPricingPlanForICICISubMerchant();

        $this->startTest();

        $submerchant = $this->getLastEntity('merchant', true);

        $this->assertEquals('1ycviEdCgurrFI', $submerchant['pricing_plan_id']);
    }

    public function testCreateSubMerchantWithAutoFeeBearerByAdminBatch()
    {
        $this->fixtures->create('feature', [
            'name' => FeatureConstants::SUB_MERCHANT_PRICING_AUTOMATION,
            'entity_id' => '100000razorpay',
            'entity_type' => 'org',
        ]);

        Mail::fake();

        $app = $this->markPartnerAndCreateAppAndUserMapping('fully_managed');

        $configAttributes = [
            PartnerConfig\Entity::DEFAULT_PLAN_ID => Pricing::DEFAULT_PRICING_PLAN_ID,
        ];

        $this->createConfigForPartnerApp($app->getId(), null, $configAttributes);

        $this->ba->batchAppAuth();

        $this->fixtures->pricing->createPricingPlanForICICISubMerchant('customer');

        $this->startTest();

        $submerchant = $this->getLastEntity('merchant', true);

        $this->assertEquals('customer', $submerchant['fee_bearer']);
    }

    public function testCreateSubMerchantWithAutoFeeBearerDynamicByAdminBatch()
    {
        $this->fixtures->create('feature', [
            'name' => FeatureConstants::SUB_MERCHANT_PRICING_AUTOMATION,
            'entity_id' => '100000razorpay',
            'entity_type' => 'org',
        ]);

        Mail::fake();

        $app = $this->markPartnerAndCreateAppAndUserMapping('fully_managed');

        $configAttributes = [
            PartnerConfig\Entity::DEFAULT_PLAN_ID => Pricing::DEFAULT_PRICING_PLAN_ID,
        ];

        $this->createConfigForPartnerApp($app->getId(), null, $configAttributes);

        $this->ba->batchAppAuth();

        $this->fixtures->pricing->createPricingPlanForICICISubMerchant();

        $this->startTest();

        $submerchant = $this->getLastEntity('merchant', true);

        $this->assertEquals('platform', $submerchant['fee_bearer']);
    }

    public function testCaptchaValidationForCreateSubMerchantByAdminForAggregatorBatch()
    {
        Mail::fake();

        $testData = $this->testData['testCreateSubMerchantByAdminForAggregatorBatch'];

        $userValidator = \Mockery::mock('RZP\Models\User\Validator')->shouldAllowMockingProtectedMethods();

        $app = $this->markPartnerAndCreateAppAndUserMapping('aggregator');

        $configAttributes = [
            PartnerConfig\Entity::DEFAULT_PLAN_ID => Pricing::DEFAULT_PRICING_PLAN_ID,
        ];

        $this->createConfigForPartnerApp($app->getId(), null, $configAttributes);

        $this->ba->batchAppAuth();

        $this->startTest($testData);

        $userValidator->shouldAllowMockingProtectedMethods();

        $userValidator->shouldNotReceive('validateCaptcha');
    }

    /**
     * Given: A partner whitelisted under partnership for capital experiment
     * When: Partner uploads a batch file with 1 submerchant to be added for LOC
     * Then:
     *   - submerchant is added
     *   - submerchant user is created for banking
     *   - submerchant is mapped to partner's reseller application
     *   - submerchant receives email and sms with password reset link
     *   - partner receives no email since we are processing in batch
     *   - LOS Service should receive 1 request to create capital application
     *   - merchant_attribute for capital_loc_emi is created for submerchant
     *   - capital loc tag is added to submerchant
     *
     * @return void
     */
    public function testCreateSubMerchantByResellerBatchForLOC()
    {
        Mail::fake();

        $testData = $this->testData[__FUNCTION__];

        //Bus::fake();

        $app = $this->markPartnerAndCreateAppAndUserMapping(MerchantConstants::RESELLER);

        $this->ba->batchAppAuth();

        $this->mockCapitalPartnershipSplitzExperiment();

        // assert that LOS Service gets one request to get product list and one request to create capital application
        $losServiceMock = \Mockery::mock('RZP\Services\LOSService', [$this->app])
                                  ->makePartial()
                                  ->shouldAllowMockingProtectedMethods();

        $this->app->instance('losService', $losServiceMock);

        $this->mockCreateApplicationRequestOnLOSService($losServiceMock);

        $this->mockGetProductsRequestOnLOSService($losServiceMock);

        // assert that stork receives a sendSms request
        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])
                             ->makePartial()
                             ->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $expectedParams = [
            'subMerchantName' => 'Erebor Travels'
        ];

        (new MerchantTest())->expectStorkSmsRequest(
            $storkMock,
            'sms.partnership.new_LOC',
            '+91' . $testData['request']['content']['contact_mobile'],
            $expectedParams
        );

        // start test
        $this->startTest();

        // assert that submerchant received an email
        Mail::assertQueued(CreateSubMerchantAffiliateForLOC::class, function($mail) use ($testData) {
            return $mail->hasTo($testData['request']['content']['email']);
        });

        // assert that partner is not sent an email, since it's batch service
        Mail::assertNotQueued(CreateSubMerchantPartnerForLOC::class);

        $submerchant = $this->getLastEntity('merchant', true);

        // assert that partner's reseller app is mapped to submerchant in merchant_access_map
        $this->verifyAccessMapEntries($app, $submerchant);

        // assert that new submerchant's user is created and email/contact number match
        $submerchantUser = $this->getLastEntity('user', true);

        $this->assertEquals($testData['request']['content']['email'], $submerchantUser['email']);

        $this->assertEquals('+91' . $testData['request']['content']['contact_mobile'], $submerchantUser['contact_mobile']);

        // assert that submerchant user is given access to banking product
        $mapping = $this->fixtures->user->getMerchantUserMapping(
            $submerchant['id'],
            $submerchantUser['id'],
            'banking'
        );

        $this->assertEquals(1, count($mapping));

        // assert that contact mobile is also saved in merchant detail with a +91
        $submerchantDetail = $this->getLastEntity('merchant_detail', true);

        $this->assertEquals(
            '+91' . $testData['request']['content']['contact_mobile'],
            $submerchantDetail['contact_mobile']
        );

        // assert that merchant attribute for X_MERCHANT_INTENT:CAPITAL_LOC_EMI is added in live mode
        $res = $this->repo->merchant_attribute->connection(Mode::LIVE)
                                              ->getKeyValues(
                                                  $submerchant["id"],
                                                  Product::BANKING,
                                                  MerchantAttribute\Group::X_MERCHANT_INTENT,
                                                  [MerchantAttribute\Type::CAPITAL_LOC_EMI]
                                              );

        $this->assertNotEmpty($res);

        //// assert that submerchant tagging job is dispatched with capital loc prefix
        //// ToDo: This can be fixed if lqext BusDispatcher extends QueueingDispatcher instead of Dispatcher
        //Bus::assertDispatched(SubMerchantTaggingJob::class, function (SubMerchantTaggingJob $job) {
        //    return $job->getTagPrefix() === MerchantConstants::CAPITAL_LOC_PARTNERSHIP_TAG_PREFIX;
        //});
    }

    public function testCreateSubMerchantByResellerBatchForLOCInvalidBusinessType()
    {
        $this->markPartnerAndCreateAppAndUserMapping(MerchantConstants::RESELLER);

        $this->ba->batchAppAuth();

        $this->mockCapitalPartnershipSplitzExperiment();

        // start test
        $this->startTest();

    }

    public function testCreateSubMerchantWithInvalidEmailByAdminForAggregatorBatch()
    {
        Mail::fake();

        $app = $this->markPartnerAndCreateAppAndUserMapping('aggregator');

        $this->fixtures->merchant->edit('10000000000000', ['email' => 'merch1@razorpay.com']);

        $configAttributes = [
            PartnerConfig\Entity::DEFAULT_PLAN_ID => Pricing::DEFAULT_PRICING_PLAN_ID,
        ];

        $this->createConfigForPartnerApp($app->getId(), null, $configAttributes);

        $this->ba->batchAppAuth();

        $this->startTest();
    }

    public function testCreateSubMerchantByAggregatorWithDefaultPaymentMethods()
    {
        Mail::fake();

        $app = $this->markPartnerAndCreateAppAndUserMapping('aggregator');

        $configAttributes = [
            PartnerConfig\Entity::DEFAULT_PLAN_ID => Pricing::DEFAULT_PRICING_PLAN_ID,
            PartnerConfig\Entity::DEFAULT_PAYMENT_METHODS => [
                Entity::CREDIT_CARD => true,
                Entity::DEBIT_CARD => true,
                Entity::NETBANKING => true,
            ]
        ];

        $this->createConfigForPartnerApp($app->getId(), null, $configAttributes);

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest();

        $submerchant = $this->getLastEntity('merchant', true);

        $submerchantMethods = $this->getLastEntity('methods', true);

        $this->verifyAccessMapEntries($app, $submerchant);

        $this->assertEquals(true, $submerchantMethods[Entity::CREDIT_CARD]);
        $this->assertEquals(true, $submerchantMethods[Entity::DEBIT_CARD]);
        $this->assertEquals(true, $submerchantMethods[Entity::NETBANKING]);

        $arraySubtract = [Entity::CREDIT_CARD, Entity::DEBIT_CARD, Entity::NETBANKING, Entity::DISABLED_BANKS, Entity::CARD_SUBTYPE, Entity::EMI, Entity::ADDITIONAL_WALLETS, Entity::ADDON_METHODS];

        $arrayAssertFalse = array_diff(array_keys(Entity::$defaultPaymentMethodsForSubmerchantByPartner), $arraySubtract);

        foreach ($arrayAssertFalse as $item)
        {
            $this->assertEquals(false, $submerchantMethods[$item]);
        }
    }

    public function testCreateSubMerchantByAggregatorWithoutEmail()
    {
        $app = $this->markPartnerAndCreateAppAndUserMapping('aggregator');

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest();

        $submerchant = $this->getLastEntity('merchant', true);

        $mapping = $this->fixtures->user->getMerchantUserMapping($submerchant['id'], 'MerchantUser01');

        $this->assertEquals(1, count($mapping));

        list($testMapping, $liveMapping) = $this->getLastMappingForBothModes();

        $this->assertNull($testMapping);

        $this->assertNull($liveMapping);
    }

    public function testCreateMalaysianSubMerchantByAggregatorWithoutEmail()
    {
        $app = $this->markPartnerAndCreateAppAndUserMapping('aggregator', '10000000000000', 'MY');

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest();

        $submerchant = $this->getLastEntity('merchant', true);

        $mapping = $this->fixtures->user->getMerchantUserMapping($submerchant['id'], 'MerchantUser01');

        $this->assertEquals(1, count($mapping));

        list($testMapping, $liveMapping) = $this->getLastMappingForBothModes();

        $this->assertNull($testMapping);

        $this->assertNull($liveMapping);
    }

    public function testCreateSubMerchantByAggregatorWithoutApp()
    {
        $app = $this->markPartnerAndCreateAppAndUserMapping('aggregator');

        (new Application\Repository())->deleteOrFail($app);

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest();

        $submerchant = $this->getLastEntity('merchant', true);

        $mapping = $this->fixtures->user->getMerchantUserMapping($submerchant['id'], 'MerchantUser01');

        $this->assertEquals(1, count($mapping));

        list($testMapping, $liveMapping) = $this->getLastMappingForBothModes();

        $this->assertNull($testMapping);

        $this->assertNull($liveMapping);
    }

    public function testCreateSubMerchantByAggregatorExceptionWithoutEmail()
    {
        Mail::fake();

        $app = $this->markPartnerAndCreateAppAndUserMapping('aggregator');

        $configAttributes = [
            PartnerConfig\Entity::DEFAULT_PLAN_ID => Pricing::DEFAULT_PRICING_PLAN_ID,
        ];

        $this->createConfigForPartnerApp($app->getId(), null, $configAttributes);

        // TODO: Move to partner app post discussion on features in proxy auth
        $this->fixtures->merchant->addFeatures(['allow_sub_without_email']);

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest();

        Mail::assertQueued(CreateSubMerchantPartnerMail::class, function ($mail)
        {
            return $mail->hasTo('test@razorpay.com');
        });

        $submerchant = $this->getLastEntity('merchant', true);

        $mapping = $this->fixtures->user->getMerchantUserMapping($submerchant['id'], 'MerchantUser01');

        $this->assertEquals(1, count($mapping));

        $this->verifyAccessMapEntries($app, $submerchant);
    }

    public function testCreateMarketplaceLinkedAccount()
    {
        $user = $this->createUserMerchantMapping('10000000000000', 'owner');

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->ba->proxyAuth();

        $this->testData[__FUNCTION__]['request']['content']['user_id'] = $user['id'];

        $this->startTest();
    }

    public function testCreateMarketplaceLinkedAccountWithDashboardUser()
    {
        Mail::fake();

        $user = $this->createUserMerchantMapping('10000000000000', 'owner');

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->ba->proxyAuth();

        $this->testData[__FUNCTION__]['request']['content']['user_id'] = $user['id'];

        $account = $this->startTest();

        Mail::assertQueued(LinkedAccountUserAccess::class, function ($mail)
        {
            return $mail->hasTo('linkedaccount@razorpay.com');
        });

        $users = DB::table('merchant_users')->where('merchant_id', '=', $account['id'])->get();

        $this->assertEquals(1, $users->count());

        $this->assertEquals(Role::LINKED_ACCOUNT_OWNER, $users->first()->role);
    }

    public function testCreateMarketplaceLinkedAccountWithRefundAllowed()
    {
        $user = $this->createUserMerchantMapping('10000000000000', 'owner');

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->ba->proxyAuth();

        $this->testData[__FUNCTION__]['request']['content']['user_id'] = $user['id'];

        $this->startTest();
    }

    public function testUpdateLinkedAccountEmail()
    {
        Mail::fake();

        $this->createUserMerchantMapping('10000000000000', 'owner');

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $account = $this->fixtures->create('merchant', ['parent_id' => '10000000000000']);

        $account = $this->fixtures->edit('merchant', $account->getId(), ['category' => '1100']);

        $this->ba->proxyAuth();

        $account = $account->toArrayPublic();

        unset($account['created_at']);
        unset($account['updated_at']);

        $this->testData[__FUNCTION__]['response']['content'] = $account;
        $this->testData[__FUNCTION__]['response']['content']['email'] = 'testing@testing.com';

        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Razorpay-Account'] = 'acc_' . $account['id'];

        $account = $this->startTest();

        Mail::assertQueued(LinkedAccountUserAccess::class, function ($mail)
        {
            return $mail->hasTo('testing@testing.com');
        });

        $users = DB::table('merchant_users')->where('merchant_id', '=', $account['id'])->get();

        $this->assertEquals(1, $users->count());

        $this->assertEquals(Role::LINKED_ACCOUNT_OWNER, $users->first()->role);
    }

    public function testUpdateLinkedAccountEmailTeamUser()
    {
        $this->createUserMerchantMapping('10000000000000', 'owner');

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $account = $this->fixtures->create('merchant', ['parent_id' => '10000000000000']);

        $account = $this->fixtures->edit('merchant', $account->getId(), ['category' => '1100']);

        $user = $this->fixtures->create('user', ['email' => 'testing1@testing.com']);

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => $account['id'],
            'role'        => Role::LINKED_ACCOUNT_OWNER,
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $user = $this->fixtures->create('user', ['email' => 'testing2@testing.com']);

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => $account['id'],
            'role'        => Role::LINKED_ACCOUNT_ADMIN,
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->ba->proxyAuth();

        $account = $account->toArrayPublic();

        unset($account['created_at']);
        unset($account['updated_at']);

        $this->testData[__FUNCTION__]['response']['content'] = $account;
        $this->testData[__FUNCTION__]['response']['content']['email'] = 'testing2@testing.com';

        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Razorpay-Account'] = 'acc_' . $account['id'];

        $this->startTest();

        $users = DB::table('merchant_users')->where('merchant_id', '=', $account['id'])->get();

        $this->assertEquals(2, $users->count());
    }

    public function testUpdateLinkedAccountEmailMutualFundDistributorMerchant()
    {
        Mail::fake();

        $this->createUserMerchantMapping('10000000000000', 'owner');

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $account = $this->fixtures->create('merchant', ['parent_id' => '10000000000000']);

        $this->ba->proxyAuth();

        $account = $account->toArrayPublic();

        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Razorpay-Account'] = 'acc_' . $account['id'];

        $this->fixtures->merchant->edit('10000000000000', [
            'category' => MerchantConstants::LINKED_ACCOUNT_ACTIONS_BLOCKED['category'][0],
            'category2' =>  MerchantConstants::LINKED_ACCOUNT_ACTIONS_BLOCKED['category2'][0]]);

        $this->startTest();
    }

    public function testLinkedAccountFetchWithoutBusinessType()
    {
        $this->fixtures->merchant->addFeatures(['marketplace','route_no_doc_kyc']);

        $this->ba->proxyAuth();

        $tesData = $this->testData['testCreateMarketplaceLinkedAccount'];

        $linkedAccount = $this->makeRequestAndGetContent($tesData['request']);

        $tesData = $this->testData[__FUNCTION__];

        $tesData['request']['url'] = '/beta/accounts/acc_'.$linkedAccount['id'];

        $this->runRequestResponseFlow($tesData);
    }

    public function testUpdateLinkedAccountConfigMutualFundDistributorMerchant()
    {
        $this->createUserMerchantMapping('10000000000000', 'owner');

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $account = $this->fixtures->create('merchant', ['parent_id' => '10000000000000']);

        $this->fixtures->merchant->edit('10000000000000', [
            'category' => MerchantConstants::LINKED_ACCOUNT_ACTIONS_BLOCKED['category'][0],
            'category2' =>  MerchantConstants::LINKED_ACCOUNT_ACTIONS_BLOCKED['category2'][0]]);

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Razorpay-Account'] = 'acc_' . $account['id'];

        $this->startTest();
    }

    public function testUpdateLinkedAccountBankAccountForMutualFundDistributorMerchant()
    {
        $this->fixtures->merchant->addFeatures(['marketplace', 'la_bank_account_update']);

        $testData = $this->testData['testCreateLinkedAccountOnProxyAuth'];

        $this->ba->proxyAuth();

        $response = $this->runRequestResponseFlow($testData);

        $id = $response['id'];

        $this->fixtures->merchant->activate($id);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/beta/accounts/acc_' . $id . '/bank_account';

        $this->ba->privateAuth();

        $this->fixtures->merchant->edit('10000000000000', [
            'category' => MerchantConstants::LINKED_ACCOUNT_ACTIONS_BLOCKED['category'][0],
            'category2' =>  MerchantConstants::LINKED_ACCOUNT_ACTIONS_BLOCKED['category2'][0]]);

        $this->runRequestResponseFlow($testData);
    }

    public function testCreateAutomaticAmcLinkedAccountCreationForMutualFundDistributorMerchant()
    {
        $this->createLinkedAccountReferenceData();

        $this->fixtures->merchant->edit('10000000000000', [
            'category'  => MerchantConstants::AUTO_CREATE_AMC_LINKED_ACCOUNT_MCC['category'],
            'category2' => MerchantConstants::AUTO_CREATE_AMC_LINKED_ACCOUNT_MCC['category2']]);

        $request = [
            'url'     => '/merchants/me/features',
            'method'  => 'post',
            'content' => [
                "features"    => [
                     'marketplace' => 1
                ],
            ],
            'server'  => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'test@razorpay.com',
            ],
        ];

        $this->ba->proxyAuth();

        $response = $this->makeRequestAndGetContent($request);

        $linkedAccount = $this->getLastEntity(Constants\Entity::MERCHANT, true);

        $linkedAccountDetail = $this->getLastEntity(Constants\Entity::MERCHANT_DETAIL, true);

        $this->assertEquals("Test Asset Management Limited", $linkedAccount['name']);

        $this->assertEquals("test+1@gmail.com", $linkedAccount['email']);

        $this->assertEquals("Test Asset Management Limited", $linkedAccountDetail['business_name']);

        $this->assertEquals("123000000000000", $linkedAccountDetail['bank_account_number']);

        $this->assertEquals("UTIB0000004", $linkedAccountDetail['bank_branch_ifsc']);
    }

    public function testCreateMarketplaceLinkedAccountWithAlreadyExistingUser()
    {
        Mail::fake();

        $user = $this->createUserMerchantMapping('10000000000000', 'owner');

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->ba->proxyAuth();

        $this->testData[__FUNCTION__]['request']['content']['email'] = $user['email'];

        $account = $this->startTest();

        Mail::assertQueued(MappedToAccount::class, function ($mail) use ($user)
        {
            return $mail->hasTo($user['email']);
        });

        $users = DB::table('merchant_users')->where('merchant_id', '=', $account['id'])->get();

        $this->assertEquals(1, $users->count());

        $this->assertEquals($user['id'], $users->first()->user_id);

        $this->assertEquals(Role::LINKED_ACCOUNT_OWNER, $users->first()->role);
    }

    public function testCreateMarketplaceLinkedAccountWithoutEmail()
    {
        $user = $this->createUserMerchantMapping('10000000000000', 'owner');

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->ba->proxyAuth();

        $this->testData[__FUNCTION__]['request']['content']['user_id'] = $user['id'];

        $this->startTest();
    }

    public function testCreateLinkedAccountMaxPaymentLimit()
    {
        $user = $this->createUserMerchantMapping('10000000000000', 'owner');

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->fixtures->merchant->edit('10000000000000', ['max_payment_amount' => 6000]);

        $this->ba->proxyAuth();

        $this->testData[__FUNCTION__]['request']['content']['user_id'] = $user['id'];

        $this->startTest();
    }

    /**
     * Testing Marketplace LA addition while also marked as partner of type bank.
     */
    public function testCreateMarketplaceLAWithoutEmailWithPartnerBank()
    {
        $user = $this->createUserMerchantMapping('10000000000000', 'owner');

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'bank']);

        $this->ba->proxyAuth();

        $this->testData[__FUNCTION__]['request']['content']['user_id'] = $user['id'];

        $this->startTest();
    }

    /**
     * Testing Marketplace LA addition while also marked as partner of type fully managed.
     */
    public function testCreateMarketplaceLAWithoutEmailWithPartnerFM()
    {
        $this->createUserMerchantMapping('10000000000000', 'owner');

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $app = $this->markPartnerAndCreateAppAndUserMapping('fully_managed');

        $this->ba->proxyAuth();

        $this->testData[__FUNCTION__]['request']['content']['user_id'] = 'MerchantUser01';

        $this->startTest();
    }

    /**
     * Testing Partner sub merchant addition while also featured as marketplace
     */
    public function testCreateSubMerchantWithoutEmailWithPartnerFMAndMarketplace()
    {
        Mail::fake();

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $app = $this->markPartnerAndCreateAppAndUserMapping('fully_managed');

        $configAttributes = [
            PartnerConfig\Entity::DEFAULT_PLAN_ID => Pricing::DEFAULT_PRICING_PLAN_ID,
        ];

        $this->createConfigForPartnerApp($app->getId(), null, $configAttributes);

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest();

        Mail::assertQueued(CreateSubMerchantPartnerMail::class, function ($mail)
        {
            return $mail->hasTo('test@razorpay.com');
        });
    }

    public function testLinkedAccountDefaultSchedule()
    {
        $this->fixtures->create('merchant',
                                [
                                    'id' => '10000000000002',
                                    'email' => 'test2@razorpay.com'
                                ]);

        $user = $this->createUserMerchantMapping('10000000000002', 'owner');

        // Define T+2 cycle for new merchant
        $schedule = [
            'interval'          => 1,
            'delay'             => 2,
            'hour'              => 0
        ];

        $this->fixtures->create('merchant:schedule_task',
                                [
                                    'merchant_id' => '10000000000002',
                                    'schedule'    => $schedule
                                ]);

        $this->fixtures->merchant->addFeatures(['marketplace'], '10000000000002');

        $this->ba->proxyAuth('rzp_test_10000000000002', $user['id']);

        $this->testData[__FUNCTION__]['request']['content']['user_id'] = $user['id'];

        $linkedAcc = $this->startTest();

        // Check schedule entries for new linked account
        $scheduleTasks = $this->getEntities('schedule_task', [
            'merchant_id' => $linkedAcc['id']
        ], true);

        foreach ($scheduleTasks['items'] as $scheduleTask)
        {
            $schedule = $this->getEntityById('schedule', $scheduleTask['schedule_id'], true);

            $this->assertEquals($linkedAcc['id'], $scheduleTask['merchant_id']);

            // 7 is default delay for international schedule
            $delay =  $scheduleTask['international'] === 1 ? 7 : 2;

            $this->assertEquals($schedule['delay'], $delay);
        }
    }

    public function testCreateLinkedAccountBatch()
    {
        $this->markTestSkipped('intermittent failures, need to debug');

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $entries = $this->getLinkedAccountBatchFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $this->startTest();

        // Gets last entity (Post queue processing) and asserts attributes
        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals(2, $batch['success_count']);
        $this->assertEquals(0, $batch['failure_count']);

        $merchantDetail = $this->getLastEntity('merchant_detail', true);

        $this->assertEquals('Test Bank Account 2', $merchantDetail['bank_account_name']);

        $account = $this->getLastEntity('merchant', true);

        $this->assertEquals('test 2', $account['name']);
        $this->assertEquals(true, $account['activated']);
    }

    public function testCreateLinkedAccountFromBatch()
    {
        $this->fixtures->merchant->addFeatures('marketplace');

        $this->ba->proxyAuth();
        $this->startTest();

        $linkedAccount = $this->getDbLastEntity('merchant');
        $this->assertEquals('la.1@rzp.com', $linkedAccount['email']);
        $this->assertNotNull($linkedAccount['activated_at']);
    }

    public function testCreateLinkedAccountDashboardAccess()
    {
        Mail::fake();

        $this->createUserMerchantMapping('10000000000000', 'owner');

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $account = $this->fixtures->create('merchant', ['parent_id' => '10000000000000']);

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Razorpay-Account'] = 'acc_' . $account['id'];

        $this->startTest();

        Mail::assertQueued(LinkedAccountUserAccess::class, function ($mail) use ($account)
        {
            return $mail->hasTo($account['email']);
        });
    }

    public function testCreateLinkedAccountDashboardAccessNoEmail()
    {
        $this->createUserMerchantMapping('10000000000000', 'owner');

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $account = $this->fixtures->create('merchant', ['parent_id' => '10000000000000',
                                                                 'email' => 'test@razorpay.com']);

        $this->ba->proxyAuth();

        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Razorpay-Account'] = 'acc_' . $account['id'];

        $this->startTest();
    }

    public function testCreateLinkedAccountDashboardAccessRevoke()
    {
        $this->createUserMerchantMapping('10000000000000', 'owner');

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $account = $this->fixtures->create('merchant', ['parent_id' => '10000000000000']);

        $user = $this->fixtures->create('user', ['email' => 'testing1@testing.com']);

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => $account['id'],
            'role'        => Role::LINKED_ACCOUNT_OWNER,
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->ba->proxyAuth();

        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Razorpay-Account'] = 'acc_' . $account['id'];

        $this->startTest();

        $users = DB::table('merchant_users')->where('merchant_id', '=', $account['id'])->get();

        $this->assertEquals(0, $users->count());
    }

    public function testLinkedAccountDashboardAccessAlreadyGiven()
    {
        $this->createUserMerchantMapping('10000000000000', 'owner');

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $account = $this->fixtures->create('merchant', ['parent_id' => '10000000000000']);

        $user = $this->fixtures->create('user', ['email' => 'testing1@testing.com']);

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => $account['id'],
            'role'        => Role::LINKED_ACCOUNT_OWNER,
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->ba->proxyAuth();

        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Razorpay-Account'] = 'acc_' . $account['id'];

        $this->startTest();
    }

    public function testLinkedAccountDashboardAccessRevokeNoUsers()
    {
        $this->createUserMerchantMapping('10000000000000', 'owner');

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $account = $this->fixtures->create('merchant', ['parent_id' => '10000000000000']);

        $this->ba->proxyAuth();

        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Razorpay-Account'] = 'acc_' . $account['id'];

        $this->startTest();
    }

    public function testUpdateBankAccountForNotActivatedLinkedAccount()
    {
        $this->fixtures->merchant->addFeatures(['marketplace', 'la_bank_account_update']);

        $testData = $this->testData['testCreateLinkedAccountOnProxyAuth'];

        $this->ba->proxyAuth();

        $response = $this->runRequestResponseFlow($testData);

        $id = $response['id'];

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/beta/accounts/acc_' . $id . '/bank_account';

        $request = $testData['request'];

        $this->ba->privateAuth();

        $this->makeRequestAndCatchException(
            function() use ($request) {
                $response = $this->makeRequestAndGetContent($request);
                $this->assertEquals($response['error']['internal_error_code'],
                    'BAD_REQUEST_CANNOT_UPDATE_BANK_ACCOUNT_FOR_LINKED_ACCOUNT_NOT_ACTIVATED');
            },
            BadRequestException::class,
            'Something went wrong, please try again after sometime.'
        );
    }

    public function testUpdateBankAccountForActivatedLinkedAccount()
    {
        $this->fixtures->merchant->addFeatures(['marketplace', 'la_bank_account_update']);

        $testData = $this->testData['testCreateLinkedAccountOnProxyAuth'];

        $this->ba->proxyAuth();

        $response = $this->runRequestResponseFlow($testData);

        $id = $response['id'];

        // ToDo: Activate linked account properly by passing bank account details.
        $this->fixtures->merchant->activate($id);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/beta/accounts/acc_' . $id . '/bank_account';

        $this->ba->privateAuth();

        $this->runRequestResponseFlow($testData);

        $bankAccount = $this->getDbLastEntity('bank_account', 'live');

        $this->assertEquals('Bobby Fischer Junior', $bankAccount['beneficiary_name']);

        $this->assertEquals('987698769876', $bankAccount['account_number']);

        $this->assertEquals('SBIN0000003', $bankAccount['ifsc_code']);

        $linkedAccount = $this->getDbLastEntity('merchant');

        $this->assertFalse($linkedAccount['hold_funds']);
    }

    public function testUpdateBankAccountForLinkedAccountWithoutFeature()
    {
        $this->fixtures->merchant->addFeatures(['marketplace']);

        $testData = $this->testData['testCreateLinkedAccountOnProxyAuth'];

        $this->ba->proxyAuth();

        $response = $this->runRequestResponseFlow($testData);

        $id = $response['id'];

        // ToDo: Activate linked account properly by passing bank account details.
        $this->fixtures->merchant->activate($id);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/beta/accounts/acc_' . $id . '/bank_account';

        $request = $testData['request'];

        $this->ba->privateAuth();

        $this->makeRequestAndCatchException(
            function() use ($request) {
                $response = $this->makeRequestAndGetContent($request);
                $this->assertEquals($response['error']['internal_error_code'],
                    'BAD_REQUEST_LINKED_ACCOUNT_BANK_ACCOUNT_UPDATE_FEATURE_NOT_ENABLED');
            },
            BadRequestException::class,
            'Something went wrong, please try again after sometime.'
        );

    }

    public function testUpdateBankAccountForLinkedAccountWithPennyTesting()
    {
        $this->fixtures->merchant->addFeatures(['marketplace', 'la_bank_account_update', 'route_la_penny_testing']);

        $testData = $this->testData['testCreateLinkedAccountOnProxyAuth'];

        $this->ba->proxyAuth();

        $response = $this->runRequestResponseFlow($testData);

        $id = $response['id'];

        // ToDo: Activate linked account properly by passing bank account details.
        $this->fixtures->merchant->activate($id);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/beta/accounts/acc_' . $id . '/bank_account';

        $this->ba->privateAuth();

        $this->runRequestResponseFlow($testData);

        $bankAccount = $this->getDbLastEntity('bank_account', 'live');

        $this->assertEquals('Bobby Fischer Junior', $bankAccount['beneficiary_name']);

        $this->assertEquals('987698769876', $bankAccount['account_number']);

        $this->assertEquals('SBIN0000003', $bankAccount['ifsc_code']);

        $linkedAccount = $this->getDbLastEntity('merchant');

        $this->assertTrue($linkedAccount['hold_funds']);

        $this->assertEquals('linked_account_penny_testing', $linkedAccount['hold_funds_reason']);
    }

    public function testBackFillMerchantId()
    {
        $this->ba->adminAuth();

        $this->testData[__FUNCTION__]['request']['content']['limit'] = 1;
        $this->testData[__FUNCTION__]['response']['content']['total'] = 1;
        $this->testData[__FUNCTION__]['response']['content']['success'] = 1;

        $this->startTest();

        $this->testData[__FUNCTION__]['request']['content'] = [];
        $this->testData[__FUNCTION__]['response']['content']['total'] = 4;
        $this->testData[__FUNCTION__]['response']['content']['success'] = 4;

        $this->startTest();

        $this->testData[__FUNCTION__]['response']['content']['total'] = 0;
        $this->testData[__FUNCTION__]['response']['content']['success'] = 0;

        $this->startTest();
    }

    protected function getLinkedAccountBatchFileEntries(): array
    {
        return [
            [
                Header::BUSINESS_NAME       => 'test 1',
                Header::BANK_ACCOUNT_NUMBER => 'BANKACCNUMBEROF22CHARS',
                Header::BANK_BRANCH_IFSC    => 'SBIN0007105',
                Header::BANK_ACCOUNT_TYPE   => 'Current',
                Header::BANK_ACCOUNT_NAME   => 'Test Bank Account 1',
                Header::REFERENCE_ID        => 'REF001',
                Header::ACCOUNT_ID          => '',

            ],
            [
                Header::BUSINESS_NAME       => 'test 2',
                Header::BANK_ACCOUNT_NUMBER => '111000',
                Header::BANK_BRANCH_IFSC    => 'SBIN0007105',
                Header::BANK_ACCOUNT_TYPE   => 'Current',
                Header::BANK_ACCOUNT_NAME   => 'Test Bank Account 2',
                Header::REFERENCE_ID        => 'REF002',
                Header::ACCOUNT_ID          => '',
            ],
        ];
    }

    protected function getLastMappingForBothModes()
    {
        $test = $this->getDbLastEntity(
                Constants\Entity::MERCHANT_ACCESS_MAP,
                'test');

        $live = $this->getDbLastEntity(
                Constants\Entity::MERCHANT_ACCESS_MAP,
                'live');

        return [$test, $live];
    }

    protected function markPartnerAndCreateAppAndUserMapping(
        string $type = 'fully_managed',
        string $merchantId = '10000000000000',
        string $countryCode = 'IN')
    {
        $this->fixtures->merchant->edit($merchantId, ['partner_type' => $type, 'country_code' => $countryCode]);

        return $this->createOAuthApplication(['merchant_id' => $merchantId, 'type' => 'partner', 'partner_type' => $type]);
    }

    protected function verifyAccessMapEntries(OAuthApp $app, array $submerchant)
    {
        list($testMapping, $liveMapping) = $this->getLastMappingForBothModes();

        $this->assertEquals($submerchant['id'], $testMapping['merchant_id']);

        $this->assertEquals($app->getId(), $testMapping['entity_id']);

        $this->assertEquals('application', $testMapping['entity_type']);

        $this->assertEquals($submerchant['id'], $liveMapping['merchant_id']);

        $this->assertEquals($app->getId(), $liveMapping['entity_id']);

        $this->assertEquals('application', $liveMapping['entity_type']);
    }

    public function testLinkedAccountReversalFeature()
    {
        $account = $this->setUpMarketplaceAccounts();

        $this->setUpAuth(__FUNCTION__, $account);

        $this->startTest();

        $allowReversals = $account->isFeatureEnabled(FeatureConstants::ALLOW_REVERSALS_FROM_LA);

        $this->assertTrue($allowReversals);
    }

    public function testLinkedAccountReversalFeatureRevoke()
    {
        $account = $this->setUpMarketplaceAccounts();

        $this->setUpAuth(__FUNCTION__, $account);

        $this->fixtures->merchant->addFeatures([FeatureConstants::ALLOW_REVERSALS_FROM_LA], $account->id);

        $this->startTest();

        $allowReversals = $account->isFeatureEnabled(FeatureConstants::ALLOW_REVERSALS_FROM_LA);

        $this->assertFalse($allowReversals);
    }

    public function testLinkedAccountReversalFeatureAlreadyGiven()
    {
        $account = $this->setUpMarketplaceAccounts();

        $this->setUpAuth(__FUNCTION__, $account);

        $this->fixtures->merchant->addFeatures([FeatureConstants::ALLOW_REVERSALS_FROM_LA], $account->id);

        $this->startTest();
    }

    public function testLinkedAccountReversalFeatureAlreadyRemoved()
    {
        $account = $this->setUpMarketplaceAccounts();

        $this->setUpAuth(__FUNCTION__, $account);

        $this->startTest();
    }

    public function testLinkedAccountReversalFeatureNoUsers()
    {
        $account = $this->setUpMarketplaceAccounts(false);

        $this->setUpAuth(__FUNCTION__, $account);

        $this->startTest();
    }

    public function testLinkedAccountDisbaleReversalFeatureAndDashboardAccess()
    {
        $this->createUserMerchantMapping('10000000000000', 'owner');

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $account = $this->fixtures->create('merchant', ['parent_id' => '10000000000000']);

        $user = $this->fixtures->create('user', ['email' => 'testing1@testing.com']);

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => $account['id'],
            'role'        => Role::LINKED_ACCOUNT_OWNER,
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->ba->proxyAuth();

        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Razorpay-Account'] = 'acc_' . $account['id'];

        $this->fixtures->merchant->addFeatures([FeatureConstants::ALLOW_REVERSALS_FROM_LA], $account->id);

        $this->startTest();

        $users = DB::table('merchant_users')->where('merchant_id', '=', $account['id'])->get();

        $allowReversals = $account->isFeatureEnabled(FeatureConstants::ALLOW_REVERSALS_FROM_LA);

        $this->assertEquals(0, $users->count());

        $this->assertFalse($allowReversals);
    }

    public function testLinkedAccountEnableReversalFeatureAndDashboardAccess()
    {
        Mail::fake();

        $this->createUserMerchantMapping('10000000000000', 'owner');

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $account = $this->fixtures->create('merchant', ['parent_id' => '10000000000000']);

        $this->ba->proxyAuth();

        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Razorpay-Account'] = 'acc_' . $account['id'];

        $this->startTest();

        $users = DB::table('merchant_users')->where('merchant_id', '=', $account['id'])->get();

        $allowReversals = $account->isFeatureEnabled(FeatureConstants::ALLOW_REVERSALS_FROM_LA);

        $this->assertEquals(1, $users->count());

        $this->assertTrue($allowReversals);
    }

    protected function setUpMarketplaceAccounts($createUser = true)
    {
        $this->createUserMerchantMapping('10000000000000', 'owner');

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $account = $this->fixtures->create('merchant', ['parent_id' => '10000000000000']);

        if ($createUser === true)
        {
            $user = $this->fixtures->create('user', ['email' => 'testing1@testing.com']);

            $mappingData = [
                'user_id'     => $user['id'],
                'merchant_id' => $account['id'],
                'role'        => Role::LINKED_ACCOUNT_OWNER,
            ];

            $this->fixtures->create('user:user_merchant_mapping', $mappingData);
        }

        return $account;
    }

    protected function setUpAuth(string $testName, $account)
    {
        $this->testData[$testName]['request']['server']['HTTP_X-Razorpay-Account'] = 'acc_' . $account['id'];

        $this->ba->proxyAuth();
    }

    protected function checkOTPAuthDefaultFeature()
    {
        foreach (['test', 'live'] as $mode)
        {
            $otpAuthFeature = $this->getDbEntity('feature', ['name' => 'otp_auth_default'], $mode);

            $this->assertEquals(FeatureConstants::OTP_AUTH_DEFAULT, $otpAuthFeature->getName());
        }

    }

    protected function checkDisableNativeCurrencyDefaultFeature($orgId)
    {
        foreach (['test', 'live'] as $mode)
        {
            $disableNativeCurrencyFeature = $this->getDbEntity('feature', ['name' => 'disable_native_currency'], $mode);

            if($orgId === Org::RZP_ORG)
            {
                $this->assertNull($disableNativeCurrencyFeature);
            }

            else {
                $this->assertEquals(FeatureConstants::DISABLE_NATIVE_CURRENCY, $disableNativeCurrencyFeature->getName());
            }
        }
    }


    public function testCreateMerchantWithDefaultEmailReceiptDisabled()
    {
        $org = $this->fixtures->org->createHdfcOrg();

        $this->fixtures->org->addFeatures(['disable_def_email_receipt']);

        $this->ba->adminAuth('test', 'SuperSecretTokenForRazorpaySuprHdfcbToken', $org->getPublicId(), 'hdfcbank.com');

        $this->merchantId = '1X4hRFHFx4UiXt';

        $testData = $this->testData['testCreateMerchant'];

        $this->runRequestResponseFlow($testData);

        $liveConfig = $this->getDbLastEntity(
            Constants\Entity::FEATURE, 'live');

        $testConfig = $this->getDbLastEntity(
            Constants\Entity::FEATURE, 'test');

        $this->assertEquals($this->merchantId, $liveConfig['entity_id']);
        $this->assertEquals($this->merchantId, $testConfig['entity_id']);

    }

    public function testCreateLinkedAccountForExistingEmailsWithFeatureEnabledToDisallow()
    {
        $this->mockRazorxTreatment();

        $this->fixtures->merchant->addFeatures([
            FeatureConstants::MARKETPLACE,
            FeatureConstants::DISALLOW_LINKED_ACCOUNT_WITH_DUPLICATE_EMAILS
        ]);

        $existingLAMerchant = $this->fixtures->create('merchant', [
            'id' => '10000000000002',
            'email' => 'test2@razorpay.com',
            'parent_id' => '10000000000000'
        ]);

        $this->ba->proxyAuth();

        $this->testData[__FUNCTION__]['request']['content']['email'] = $existingLAMerchant['email'];

        $this->startTest();
    }

    /**
     * given: there are merchants with dashboard access for a given email (i.e. there is an associated user)
     * when: a merchant tries to add a linked account without dashboard access
     * then: Linked Account should be created
     */
    public function testCreateLinkedAccountForExistingEmailsWithoutDashboardAccess()
    {
        $this->fixtures->merchant->addFeatures(['marketplace']);

        $existingMerchant = $this->fixtures->create('merchant', ['id' => '10000000000002', 'email' => 'test2@razorpay.com']);

        $this->ba->proxyAuth();

        $this->testData[__FUNCTION__]['request']['content']['email'] = $existingMerchant['email'];
        $this->testData[__FUNCTION__]['response']['content']['email'] = $existingMerchant['email'];

        $account = $this->startTest();

        // no users have access to newly created linked account merchant
        $users = DB::table('merchant_users')->where('merchant_id', '=', $account['id'])->get();
        $this->assertEquals(0, $users->count());

        // check that there are two merchants with the same email
        $merchants = DB::table('merchants')->where('email', '=', $existingMerchant['email'])->get();
        $this->assertEquals(2, $merchants->count());
    }

    /**
     * given: there are merchants with dashboard access for a given email (i.e. there is an associated user)
     * when: a merchant tries to add a linked account with dashboard access
     * then: linked account should be created and MappedToAccount email should be sent to the user email
     */
    public function testCreateLinkedAccountForExistingEmailsWithDashboardAccess()
    {
        $this->fixtures->merchant->addFeatures(['marketplace']);
        Mail::fake();

        $existingMerchant = $this->fixtures->create('merchant', ['id' => '10000000000002', 'email' => 'test2@razorpay.com']);

        $existingUser = $this->fixtures->create('user', ['email' => 'test2@razorpay.com']);

        $this->fixtures->create('user:user_merchant_mapping', [
            'merchant_id' => $existingMerchant['id'],
            'user_id' => $existingUser['id'],
            'role' => Role::OWNER
        ]);

        $this->ba->proxyAuth();

        $this->testData[__FUNCTION__]['request']['content']['email'] = $existingMerchant['email'];
        $this->testData[__FUNCTION__]['response']['content']['email'] = $existingMerchant['email'];

        $account = $this->startTest();

        // exactly 1 user has access to newly created linked account merchant with LINKED_ACCOUNT_OWNER role
        $users = DB::table('merchant_users')->where('merchant_id', '=', $account['id'])->get();
        $this->assertEquals(1, $users->count());
        $this->assertEquals($existingUser['id'], $users->first()->user_id);
        $this->assertEquals(Role::LINKED_ACCOUNT_OWNER, $users->first()->role);

        // check that there are two merchants with the same email
        $merchants = DB::table('merchants')->where('email', '=', $existingMerchant['email'])->get();
        $this->assertEquals(2, $merchants->count());

        // Check that the MappedToAccount email is sent
        Mail::assertQueued(MappedToAccount::class, function ($mail) use ($existingUser)
        {
            return $mail->hasTo($existingUser['email']);
        });
    }

    /**
     * given: there are linked account merchants without dashboard access for a given email
     * when: a merchant tries to add a linked account without dashboard access
     * then: i. linked account should be created
     */
    public function testCreateLinkedAccountForExistingEmailWithoutDashboardAccessHavingExistingLinkedAccount()
    {
        $this->fixtures->merchant->addFeatures(['marketplace']);

        $existingParentMerchant = $this->fixtures->create('merchant', [
            'id' => '10000000000001',
            'email' => 'test1@razorpay.com'
        ]);

        $existingLAMerchant = $this->fixtures->create('merchant', [
            'id' => '10000000000002',
            'email' => 'test2@razorpay.com',
            'parent_id' => $existingParentMerchant['id']
        ]);

        $this->ba->proxyAuth();
        $this->testData[__FUNCTION__]['request']['content']['email'] = $existingLAMerchant['email'];
        $this->testData[__FUNCTION__]['response']['content']['email'] = $existingLAMerchant['email'];

        $account = $this->startTest();

        // no users have access to newly created linked account merchant
        $users = DB::table('merchant_users')->where('merchant_id', '=', $account['id'])->get();
        $this->assertEquals(0, $users->count());

        // check that there are two merchants with the same email
        $merchants = DB::table('merchants')->where('email', '=', $existingLAMerchant['email'])->get();
        $this->assertEquals(2, $merchants->count());
    }

    /**
     * given: there are linked account merchants without dashboard access for a given email
     * when: a merchant tries to add a linked account with dashboard access
     * then: i. linked account should be created
     *       ii. LinkedAccountUserAccess password reset email should be sent
     *       iii. Other linked account should not be accessible
     */
    public function testCreateLinkedAccountForExistingEmailWithDashboardAccessHavingExistingLinkedAccount()
    {
        $this->fixtures->merchant->addFeatures(['marketplace']);
        Mail::fake();

        $existingParentMerchant = $this->fixtures->create('merchant', [
            'id' => '10000000000001',
            'email' => 'test1@razorpay.com'
        ]);

        $existingLAMerchant = $this->fixtures->create('merchant', [
            'id' => '10000000000002',
            'email' => 'test2@razorpay.com',
            'parent_id' => $existingParentMerchant['id']
        ]);

        $this->ba->proxyAuth();
        $this->testData[__FUNCTION__]['request']['content']['email'] = $existingLAMerchant['email'];
        $this->testData[__FUNCTION__]['response']['content']['email'] = $existingLAMerchant['email'];

        $account = $this->startTest();

        // no users have access to newly created linked account merchant
        $users = DB::table('merchant_users')->where('merchant_id', '=', $account['id'])->get();
        $this->assertEquals(1, $users->count());

        // check that there are two merchants with the same email
        $merchants = DB::table('merchants')->where('email', '=', $existingLAMerchant['email'])->get();
        $this->assertEquals(2, $merchants->count());

        // password reset mail should be sent to newly created user
        Mail::assertQueued(LinkedAccountUserAccess::class, function ($mail) use ($account)
        {
            return $mail->hasTo($account['email']);
        });
    }

    /**
     * given: there are linked account merchants without dashboard access for a given email with some parent id
     * when: a merchant having MID same as the parent id tries to add a linked account for the same email
     * then: i. linked account should not be created
     */
    public function testCreateLinkedAccountForExistingEmailsOtherLinkedAccountExistsForSameParent()
    {
        $this->fixtures->merchant->addFeatures(['marketplace']);

        $existingLAMerchant = $this->fixtures->create('merchant', [
            'id' => '10000000000002',
            'email' => 'test2@razorpay.com',
            'parent_id' => '10000000000000'
        ]);

        $this->ba->proxyAuth();
        $this->testData[__FUNCTION__]['request']['content']['email'] = $existingLAMerchant['email'];

        $this->startTest();
    }

    /**
     * given: there are two linked accounts with the same email, both have dashboard access
     * when: dashboard access of one is revoked
     * then: it should not affect dashboard access for the other one
     */
    public function testLinkedAccountDashboardAccessRevokeDoesNotAffectOtherUsers()
    {
        $this->fixtures->merchant->addFeatures(['marketplace']);

        $user = $this->fixtures->create('user', ['email' => 'test2@razorpay.com']);

        $existingLAMerchant1 = $this->fixtures->create('merchant', [
            'id' => '10000000000002',
            'email' => 'test2@razorpay.com',
            'parent_id' => '10000000000000'
        ]);

        $existingLAMerchant2 = $this->fixtures->create('merchant', [
            'id' => '10000000000003',
            'email' => 'test2@razorpay.com',
            'parent_id' => '10000000000000'
        ]);

        $this->fixtures->create('user:user_merchant_mapping', [
            'user_id'     => $user['id'],
            'merchant_id' => $existingLAMerchant1['id'],
            'role'        => Role::LINKED_ACCOUNT_OWNER,
        ]);

        $this->fixtures->create('user:user_merchant_mapping', [
            'user_id'     => $user['id'],
            'merchant_id' => $existingLAMerchant2['id'],
            'role'        => Role::LINKED_ACCOUNT_OWNER,
        ]);

        $this->ba->proxyAuth();

        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Razorpay-Account'] = 'acc_' . $existingLAMerchant1['id'];

        $this->startTest();

        // access taken for one linked account
        $users = DB::table('merchant_users')->where('merchant_id', '=', $existingLAMerchant1['id'])->get();
        $this->assertEquals(0, $users->count());

        // access retained for another one
        $users = DB::table('merchant_users')->where('merchant_id', '=', $existingLAMerchant2['id'])->get();
        $this->assertEquals(1, $users->count());
    }

    /**
     * given: there are two linked accounts with the same email, both do not have dashboard access
     * when: dashboard access to one is granted
     * then:  it should not affect dashboard access for the other one
     */
    public function testLinkedAccountDashboardAccessAllowDoesNotAffectOtherUsers()
    {
        $this->fixtures->merchant->addFeatures(['marketplace']);

        $existingLAMerchant1 = $this->fixtures->create('merchant', [
            'id' => '10000000000002',
            'email' => 'test2@razorpay.com',
            'parent_id' => '10000000000000'
        ]);

        $existingLAMerchant2 = $this->fixtures->create('merchant', [
            'id' => '10000000000003',
            'email' => 'test2@razorpay.com',
            'parent_id' => '10000000000000'
        ]);

        $this->ba->proxyAuth();

        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Razorpay-Account'] = 'acc_' . $existingLAMerchant1['id'];

        $this->startTest();

        // access granted for one linked account
        $users = DB::table('merchant_users')->where('merchant_id', '=', $existingLAMerchant1['id'])->get();
        $this->assertEquals(1, $users->count());

        // no access for another one
        $users = DB::table('merchant_users')->where('merchant_id', '=', $existingLAMerchant2['id'])->get();
        $this->assertEquals(0, $users->count());
    }


    public function testCreateMerchantWithDefaultLateAuthConfig()
    {
        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $org = $this->fixtures->org->createHdfcOrg();

        $this->ba->adminAuth('test', 'SuperSecretTokenForRazorpaySuprHdfcbToken', $org->getPublicId(), 'hdfcbank.com');

        $this->merchantId = '1X4hRFHFx4UiXt';

        $testData = $this->testData['testCreateMerchant'];

        $testData['request']['content']['org_id'] = $org->getPublicId();

        $testData['response']['content']['pricing_plan_id'] = 'BAJq6FJDNJ4ZqD';

        $this->runRequestResponseFlow($testData);

        $liveConfig = $this->getDbLastEntity(
            Constants\Entity::CONFIG, 'live');

        $testConfig = $this->getDbLastEntity(
            Constants\Entity::CONFIG, 'test');

        $this->assertEquals('late_auth_'.$this->merchantId, $liveConfig['name']);
        $this->assertEquals('late_auth_'.$this->merchantId, $testConfig['name']);
    }

    public function testCreateLinkedAccountForMutualFundDistributorMerchant()
    {
        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->fixtures->merchant->edit('10000000000000', [
            'category' => MerchantConstants::LINKED_ACCOUNT_ACTIONS_BLOCKED['category'][0],
            'category2' =>  MerchantConstants::LINKED_ACCOUNT_ACTIONS_BLOCKED['category2'][0]]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCreateMarketplaceLinkedAccountForMutualFundDistributorMerchant()
    {
        $user = $this->createUserMerchantMapping('10000000000000', 'owner');

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->fixtures->merchant->edit('10000000000000', [
            'category' => MerchantConstants::LINKED_ACCOUNT_ACTIONS_BLOCKED['category'][0],
            'category2' =>  MerchantConstants::LINKED_ACCOUNT_ACTIONS_BLOCKED['category2'][0]]);

        $this->ba->proxyAuth();

        $this->testData[__FUNCTION__]['request']['content']['user_id'] = $user['id'];

        $this->startTest();
    }

    protected function mockLedgerSnsPush()
    {
        $sns = \Mockery::mock('RZP\Services\Aws\Sns');

        $this->app->instance('sns', $sns);

        $sns->shouldReceive('publish')
            ->zeroOrMoreTimes()
            ->with(\Mockery::type('string'), \Mockery::type('string'));

        $this->app->instance('sns', $sns);
    }

    private function mockSplitzEvaluation() {
        $input = [
            "experiment_id" => "JRWRysOmXFWZ9C",
            "id"            => "10000000000000",
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $input = [
            "experiment_id" => "JIRYzx7YtMuB18",
            "id"            => "10000000000000",
            'request_data'  => json_encode(
                [
                    'id' => "10000000000000",
                ]),
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => "enabled"
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $output = [
            "response" => [
                'variant' => [
                    'name' => 'SyncDeviation Enabled',
                    'variables' => [
                        [
                            'key' => 'enabled',
                            'value' => 'true',
                        ]
                    ]
                ]
            ]
        ];

        $splitzMock = $this->getSplitzMock();
        $splitzMock->shouldReceive('evaluateRequest')->zeroOrMoreTimes()->with(Mockery::hasKey('experiment_id'))->with(Mockery::hasValue('K1ZaAGS9JfAUHj'))->andReturn($output);

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];

        $splitzMock->shouldReceive('evaluateRequest')->zeroOrMoreTimes()->with(Mockery::hasKey('experiment_id'))->with(Mockery::hasValue('KIYvRvxbpMy7r1'))->andReturn($output);
    }

    public function testCreateLinkedAccountReferenceData()
    {
        $this->ba->adminAuth();

        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $upsertPerm = $this->fixtures->create(Constants\Entity::PERMISSION, [Permission\Entity::NAME => Permission\Name::LINKED_ACCOUNT_REFERENCE_DATA_CREATE]);

        $role->permissions()->attach($upsertPerm->getId());

        $this->startTest();
    }

    public function testUpdateLinkedAccountReferenceData()
    {
        $this->testCreateLinkedAccountReferenceData();

        $laRefData = $this->getDbLastEntity(Constants\Entity::LINKED_ACCOUNT_REFERENCE_DATA);

        $this->ba->adminAuth();

        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $upsertPerm = $this->fixtures->create(Constants\Entity::PERMISSION, [Permission\Entity::NAME => Permission\Name::LINKED_ACCOUNT_REFERENCE_DATA_UPDATE]);

        $role->permissions()->attach($upsertPerm->getId());

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/la_reference_data/'.$laRefData->getId();

        $this->runRequestResponseFlow($testData);
    }

    public function testUpdateLinkedAccountReferenceDataException()
    {
        $this->testCreateLinkedAccountReferenceData();

        $laRefData = $this->getDbLastEntity(Constants\Entity::LINKED_ACCOUNT_REFERENCE_DATA);

        $oldIfscCode = $laRefData->getAttribute('ifsc_code');

        $this->ba->adminAuth();

        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $upsertPerm = $this->fixtures->create(Constants\Entity::PERMISSION, [Permission\Entity::NAME => Permission\Name::LINKED_ACCOUNT_REFERENCE_DATA_UPDATE]);

        $role->permissions()->attach($upsertPerm->getId());

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/la_reference_data/'.$laRefData->getId();

        $this->runRequestResponseFlow($testData);

        $laRefData->refresh();

        $this->assertEquals($oldIfscCode, $laRefData->getAttribute('ifsc_code'));
    }


    public function testAmcLinkedAccountCreateForMutualFundDistributorMerchantAdminApi()
    {
        $this->createLinkedAccountReferenceData();

        $this->ba->adminAuth();

        $admin = $this->ba->getAdmin();

        $this->addPermissionToBaAdmin(Permission\Name::AMC_LINKED_ACCOUNT_CREATION);

        $this->createUserMerchantMapping('10000000000000', 'owner');

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->fixtures->merchant->edit('10000000000000', [
            'category' => MerchantConstants::LINKED_ACCOUNT_ACTIONS_BLOCKED['category'][0],
            'category2' =>  MerchantConstants::LINKED_ACCOUNT_ACTIONS_BLOCKED['category2'][0]]);

        $this->fixtures->merchant->activate('10000000000000');

        $merchantNotMarketplace = $this->fixtures->create('merchant');

        $this->testData[__FUNCTION__]['request']['content']['merchant_ids'] = ['10000000000000', $merchantNotMarketplace->getId()];

        $this->startTest();

        $linkedAccount = $this->getLastEntity('merchant', true);

        $this->assertEquals('test+1@gmail.com', $linkedAccount['email']);

        $this->assertEquals('10000000000000', $linkedAccount['parent_id']);
    }

    protected function createLinkedAccountReferenceData()
    {
        $input = [
            "account_name" => "ABC Mutual Fund - Online Collection Account",
            "account_number"=> "123000000000000",
            "account_email" => "test+1@gmail.com",
            "beneficiary_name"=> "ABC Mutual Fund - Funds Collection Account",
            "business_name"=> "Test Asset Management Limited",
            "business_type"=> "private_limited",
            "dashboard_access"=> 0,
            "customer_refund_access"=> 0,
            "ifsc_code" => "UTIB0000004",
            "category" => "amc_bank_account"
        ];

        return $this->fixtures->create('linked_account_reference_data', $input);
    }

    protected function addPermissionToBaAdmin(string $permissionName): void
    {
        $admin = $this->ba->getAdmin();

        if ($admin->hasPermission($permissionName) === true)
        {
            return;
        }

        $roleOfAdmin = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => $permissionName]);

        $roleOfAdmin->permissions()->attach($perm->getId());
    }

    public function testCreateSubmerchantAndVerifyDefaultPaymentConfig()
    {
        Mail::fake();

        $this->mockSplitzEvaluation();

        $app = $this->markPartnerAndCreateAppAndUserMapping('aggregator');

        $configAttributes = [
            PartnerConfig\Entity::DEFAULT_PLAN_ID => Pricing::DEFAULT_PRICING_PLAN_ID,
        ];

        $this->createConfigForPartnerApp($app->getId(), null, $configAttributes);

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->mockAllExperiments("enable");

        $testData = $this->testData['testCreateSubMerchantByAggregatorWithEmail'];

        $response = $this->startTest($testData);

        $merchantId = $response['id'];

        \RZP\Models\Merchant\Account\Entity::verifyIdAndStripSign($merchantId);

        $paymentConfig = $this->getDbEntity('config', ['merchant_id' => $merchantId]);

        $this->assertNotNull($paymentConfig);
    }
}
