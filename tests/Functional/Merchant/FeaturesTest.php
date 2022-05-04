<?php

namespace RZP\Tests\Functional\Merchant;

use Mail;
use Event;
use Mockery;

use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Models\Feature\Entity;
use RZP\Models\Admin;
use RZP\Services\RazorXClient;
use RZP\Models\Feature\Constants;
use RZP\Models\Terminal;
use RZP\Tests\Traits\MocksRazorx;
use RZP\Mail\Loc\CashAdvanceEligible;
use RZP\Error\PublicErrorDescription;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Cache\Events\CacheMissed;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Mail\Merchant\FullES as FullESMail;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Mail\Merchant\MerchantDashboardEmail;
use RZP\Tests\Functional\Helpers\FileUploadTrait;
use RZP\Models\Merchant\Request as MerchantRequest;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Helpers\Org\CustomBrandingTrait;
use RZP\Models\Merchant\Detail\Entity as MerchantDetails;
use RZP\Models\Merchant\Store\ConfigKey as StoreConfigKey;
use RZP\Models\Base\QueryCache\Constants as CacheConstants;
use RZP\Mail\Merchant\FeatureEnabled as FeatureEnabledEmail;
use RZP\Models\Admin\Org\Entity as OrgEntity;

use RZP\Tests\Functional\Helpers\VirtualAccount\VirtualAccountTrait;
use function Clue\StreamFilter\fun;

class FeaturesTest extends OAuthTestCase
{
    use MocksRazorx;
    use FileUploadTrait;
    use DbEntityFetchTrait;
    use VirtualAccountTrait;
    use CustomBrandingTrait;
    use PaymentTrait;
    use TestsBusinessBanking;

    const DEFAULT_MERCHANT_ID    = '10000000000000';
    const ONBOARDING_MERCHANT_ID = '10000000001017';
    const LIVE_AUTH_KEY          = 'rzp_live_TheLiveAuthKey';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/FeaturesTestData.php';

        parent::setUp();

        //
        // testOnboardingRequestStatus creates an action_state in the live mode which requires an admin to
        // exist due to a foreign key constraint. Admin fixture is created in test and not in live, hence creating it
        // here.
        //
        $this->fixtures->on('live')->create('admin', [
            'id'     => Org::SUPER_ADMIN,
            'org_id' => Org::RZP_ORG,
        ]);

        $this->ba->adminAuth();
    }

    public function testAddInvalidFeatureToMerchant()
    {
        $this->startTest();
    }

    public function testAddDuplicateFeatureToMerchant()
    {
        $this->addFeatures(Mode::TEST);

        $this->startTest();
    }

    public function testMswipeFeaturesAdd()
    {
        $this->fixtures->create('merchant', ['id' => '10000000000001']);

        $this->fixtures->create('terminal', ['id' => 'C7EW8LggSH7FnY']);
        $this->fixtures->create('terminal', ['id' => 'CXjvHPZlPnqWBX']);
        $this->fixtures->create('terminal', ['id' => 'CNqL80h9pI0hsI']);
        $this->fixtures->create('terminal', ['id' => 'CHYaN0FnjkG5ni']);
        $this->fixtures->create('terminal', ['id' => 'CWybuzsFqa9KDz']);

        $this->startTest();

        $dt = Terminal\Entity::findOrFail('C7EW8LggSH7FnY');
        $this->assertEquals(1, $dt->merchants->count());

        $dt = Terminal\Entity::findOrFail('CXjvHPZlPnqWBX');
        $this->assertEquals(1, $dt->merchants->count());

        $dt = Terminal\Entity::findOrFail('CNqL80h9pI0hsI');
        $this->assertEquals(1, $dt->merchants->count());

        $dt = Terminal\Entity::findOrFail('CHYaN0FnjkG5ni');
        $this->assertEquals(1, $dt->merchants->count());

        $dt = Terminal\Entity::findOrFail('CWybuzsFqa9KDz');
        $this->assertEquals(1, $dt->merchants->count());
    }

    public function testApplicationFeatures()
    {
        $merchantId='10000000000000';

        $appId = '1000000DemoApp';

        $dummy = 'dummy';

        $this->createMerchantApplication(
            $merchantId,
            'referred',
            $appId
        );

        $this->addFeatures(
            Mode::TEST,
            true,
            [$dummy],
            Constants::APPLICATION,
            $appId);

        $this->verifyFeaturePresenceForEntity(Mode::TEST, Constants::APPLICATION, $appId, [$dummy]);

        $testData = $this->getDataToDeleteFeaturesFromEntity(Mode::TEST,
            true,
            $dummy,
            Constants::APPLICATION,
            $appId);

        $this->startTest($testData);
    }

    public function testOrgFeatures()
    {
        $org = $this->fixtures->org->createHdfcOrg();

        $orgId = $org->getId();

        $dummy = 'dummy';

        $this->addFeatures(
            Mode::TEST,
            true,
            [$dummy],
            Constants::ORG,
            $orgId);

        $org = $this->getDbEntityById('org', $orgId);

        $features = $org->getEnabledFeatures();

        $this->assertTrue(in_array($dummy, $features, true));

        $this->assertFalse(in_array('dummy1', $features, true));
    }

    public function testAccountFeatures()
    {
        $accountId = '10000000000000';

        $noflashcheckout = 'noflashcheckout';

        $testData = $this->getDataToAddAccountFeatures(Mode::TEST,
            true,
            [$noflashcheckout],
            $accountId);

        $this->startTest($testData);

        $this->verifyFeaturePresenceForAccounts(Mode::TEST, $accountId, [$noflashcheckout]);

        $testData = $this->getDataToDeleteFeaturesFromEntity(Mode::TEST,
            true,
            $noflashcheckout,
            Constants::ACCOUNT,
            $accountId);

        $this->startTest($testData);
    }

    public function testDeleteNonExistentFeatureFromMerchant()
    {
        $this->ba->adminAuth('test', null, 'org_100000razorpay');

        $this->startTest();
    }

    public function testMultiAssignFeature()
    {
        $this->fixtures->create('merchant', ['id' => '10000000000001']);
        $this->fixtures->create('merchant', ['id' => '10000000000002']);
        $this->fixtures->create('merchant', ['id' => '10000000000003']);

        $this->startTest();
    }

    public function testMultiAssignFeatures()
    {
        $this->fixtures->create('merchant', ['id' => '10000000000001']);
        $this->fixtures->create('merchant', ['id' => '10000000000002']);
        $this->fixtures->create('merchant', ['id' => '10000000000003']);

        $this->startTest();
    }

    public function testMultiRemoveFeature()
    {
        $this->fixtures->create(
            'feature',
            [
                'entity_id' => '10000000000001',
                'name' => 'dummy'
            ]);
        $this->fixtures->create(
            'feature',
            [
                'entity_id' => '10000000000002',
                'name' => 'dummy'
            ]);
        $this->fixtures->create(
            'feature',
            [
                'entity_id' => '10000000000003',
                'name' => 'dummy'
            ]);

        $this->startTest();
    }

    public function testMultiRemoveFeatureFailure()
    {
        $this->fixtures->create(
            'feature',
            [
                'entity_id' => '10000000000001',
                'name'      => 'dummy'
            ]);
        $this->fixtures->create(
            'feature',
            [
                'entity_id' => '10000000000003',
                'name'      => 'dummy'
            ]);

        $this->startTest();
    }

    public function testMultiRemoveFeatureApplicationId()
    {
        $this->fixtures->create(
            'feature',
            [
                'entity_type' => 'application',
                'entity_id' => '10000000000001',
                'name' => 'dummy'
            ]);
        $this->fixtures->create(
            'feature',
            [
                'entity_type' => 'application',
                'entity_id' => '10000000000002',
                'name' => 'dummy'
            ]);
        $this->fixtures->create(
            'feature',
            [
                'entity_type' => 'application',
                'entity_id' => '10000000000003',
                'name' => 'dummy'
            ]);

        $this->startTest();
    }

    public function testMultiRemoveFeatureApplicationIdFailure()
    {
        $this->fixtures->create(
            'feature',
            [
                'entity_type' => 'application',
                'entity_id' => '10000000000001',
                'name' => 'dummy'
            ]);
        $this->fixtures->create(
            'feature',
            [
                'entity_type' => 'application',
                'entity_id' => '10000000000003',
                'name' => 'dummy'
            ]);

        $this->startTest();
    }

    public function testDummyFeatureRouteWithAccess()
    {
        $this->fixtures->merchant->addFeatures(['dummy']);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testDummyFeatureRouteWithoutAccess()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    /**
     * Add a feature to test
     * Verify - Any feature added to test should not be added to live
     */
    public function testAddFeatureToTestVerifyAbsenceInLive()
    {
        $this->addFeatures(Mode::TEST);

        $this->verifyFeaturePresence(Mode::TEST);

        $this->verifyFeatureAbsence(Mode::LIVE);
    }

    /**
     * Add a feature to live
     * Verify - Any feature added to live should not be added to test
     */
    public function testAddFeatureToLiveVerifyAbsenceInTest()
    {
        $this->addFeatures(Mode::LIVE);

        $this->verifyFeaturePresence(Mode::LIVE);

        $this->verifyFeatureAbsence(Mode::TEST);
    }

    /**
     * Add a feature to the test database and sync it to live
     * Verify - Any feature added to test with the should_sync
     * flag, should be synced to live
     */
    public function testAddFeatureToTestSyncedToLive()
    {
        $this->addFeatures(Mode::TEST,true);

        $this->verifyFeaturePresence(Mode::TEST);

        $this->verifyFeaturePresence(Mode::LIVE);
    }

    /**
     * Add a feature to the live database and sync it to test
     * Verify - Any feature added to live with the should_sync
     * flag, should be synced to test
     */
    public function testAddFeatureToLiveSyncedToTest()
    {
        $this->addFeatures(Mode::LIVE, true);

        $this->verifyFeaturePresence(Mode::LIVE);

        $this->verifyFeaturePresence(Mode::TEST);
    }

    /**
     * Add a feature to the test database
     * Add a feature to the live database and sync it to test
     * Verify - Any feature added to live with the should_sync flag,
     * should not fail even if it is already present in test
     */
    public function testAddFeatureToTestAddFeatureToLiveSyncedToTest()
    {
        $this->addFeatures(Mode::TEST);

        $this->verifyFeaturePresence(Mode::TEST);

        $this->addFeatures(Mode::LIVE, true);

        $this->verifyFeaturePresence(Mode::LIVE);
    }

    /**
     * Add a feature to the live database
     * Add a feature to the test database and sync it to live
     * Verify - Any feature added to test with the should_sync flag,
     * should not fail even if it is already present in live
     */
    public function testAddFeatureToLiveAddFeatureToTestSyncedToLive()
    {
        $this->addFeatures(Mode::LIVE);

        $this->verifyFeaturePresence(Mode::LIVE);

        $this->addFeatures(Mode::TEST, true);

        $this->verifyFeaturePresence(Mode::TEST);
    }

    /**
     * Add a feature to the live database
     * Add a feature to the live database and sync it to test
     * Verify - Any feature added to live with the should_sync flag, should not
     * fail even if it is already present in test
     */
    public function testAddFeatureToLiveAddFeatureToLiveSyncedToTest()
    {
        $this->addFeatures(Mode::LIVE);

        $this->verifyFeaturePresence(Mode::LIVE);

        $this->addFeatures(Mode::LIVE, true);

        $this->verifyFeaturePresence(Mode::TEST);
    }

    /**
     * Add a feature to the test database
     * Add a feature to the test database and sync it to live
     * Verify - Any feature added to live with the should_sync flag, should not
     * fail even if it is already present in live
     */
    public function testAddFeatureToTestAddFeatureToTestSyncedToLive()
    {
        config(['app.query_cache.mock' => true]);

        $this->addFeatures(Mode::TEST);

        $this->verifyFeaturePresence(Mode::TEST);

        $this->addFeatures(Mode::TEST, true);

        $this->verifyFeaturePresence(Mode::LIVE);
    }

    /**
     * Add a feature to live and sync it to test
     * Delete the feature from the test database
     * Verify - Any feature deleted from test should not be deleted from live
     */
    public function testDeleteFeatureFromTestAndVerifyPresenceInLive()
    {
        config(['app.query_cache.mock' => true]);

        $this->addFeatures(Mode::LIVE, true);

        $this->verifyFeaturePresence(Mode::TEST);

        $this->verifyFeaturePresence(Mode::LIVE);

        $this->deleteFeature(Mode::TEST);

        $this->verifyFeatureAbsence(Mode::TEST);

        $this->verifyFeaturePresence(Mode::LIVE);
    }

    /**
     * Add a feature to live and sync it to test
     * Delete the feature from the live database
     * Verify - Any feature deleted from live should not be deleted from test
     */
    public function testDeleteFeatureFromLiveAndVerifyPresenceInTest()
    {
        $this->addFeatures(Mode::LIVE, true);

        $this->verifyFeaturePresence(Mode::TEST);

        $this->verifyFeaturePresence(Mode::LIVE);

        $this->deleteFeature(Mode::LIVE);

        $this->verifyFeatureAbsence(Mode::LIVE);

        $this->verifyFeaturePresence(Mode::TEST);
    }

    /**
     * Add a feature to live and sync it to test
     * Delete the feature from the test database and sync it to live
     * Verify - Any feature deleted from test and synced to live,
     * should be deleted from live as well
     */
    public function testDeleteFeatureFromTestSyncedToLive()
    {
        $this->addFeatures(Mode::LIVE, true);

        $this->verifyFeaturePresence(Mode::TEST);

        $this->verifyFeaturePresence(Mode::LIVE);

        $this->deleteFeature(Mode::TEST, true);

        $this->verifyFeatureAbsence(Mode::TEST);

        $this->verifyFeatureAbsence(Mode::LIVE);
    }

    /**
     * Add a feature to live and sync it to test
     * Delete the feature from the live database and sync it to test
     * Verify - Any feature deleted from live and synced to test,
     * should be deleted from test as well
     */
    public function testDeleteFeatureFromLiveSyncedToTest()
    {
        $this->addFeatures(Mode::LIVE, true);

        $this->verifyFeaturePresence(Mode::TEST);

        $this->verifyFeaturePresence(Mode::LIVE);

        $this->deleteFeature(Mode::LIVE, true);

        $this->verifyFeatureAbsence(Mode::TEST);

        $this->verifyFeatureAbsence(Mode::LIVE);
    }

    /**
     * Add a feature to live and sync it to test
     * Delete the feature from the live database.
     * Delete the feature from the test database and sync it to live
     * Verify - Deleting the feature from test with sync, should not
     * fail even if the feature does not exist on live
     */
    public function testDeleteFeatureFromLiveDeleteFeatureFromTestSyncedToLive()
    {
        $this->addFeatures(Mode::LIVE, true);

        $this->verifyFeaturePresence(Mode::TEST);

        $this->verifyFeaturePresence(Mode::LIVE);

        $this->deleteFeature(Mode::LIVE);

        $this->verifyFeatureAbsence(Mode::LIVE);

        $this->deleteFeature(Mode::TEST, true);

        $this->verifyFeatureAbsence(Mode::TEST);
    }

    /**
     * Add a feature to live and sync it to test
     * Delete the feature from the test database.
     * Delete the feature from the live database and sync it to test
     * Verify - Deleting the feature from test with sync, should not
     * fail even if the feature does not exist on test.
     */
    public function testDeleteFeatureFromTestDeleteFeatureFromLiveSyncedToTest()
    {
        $this->addFeatures(Mode::LIVE, true);

        $this->verifyFeaturePresence(Mode::TEST);

        $this->verifyFeaturePresence(Mode::LIVE);

        $this->deleteFeature(Mode::TEST);

        $this->verifyFeatureAbsence(Mode::TEST);

        $this->deleteFeature(Mode::LIVE, true);

        $this->verifyFeatureAbsence(Mode::LIVE);
    }

    /**
     * Adds subscriptions feature to the live database
     * Since the route is accessed by an admin, it should
     * allow even when should sync is sent as 1.
     */
    public function testAddFeatureNonEditableByAdminOnLive()
    {
        $merchantId = $this->createMerchantDetails(self::ONBOARDING_MERCHANT_ID);

        $this->addFeatures(
            Mode::LIVE,
            true,
            ['subscriptions'],
            'merchant',
            $merchantId);

        $this->verifyFeaturePresence(Mode::TEST, ['subscriptions'], self::ONBOARDING_MERCHANT_ID);

        $this->verifyFeaturePresence(Mode::LIVE, ['subscriptions'], self::ONBOARDING_MERCHANT_ID);
    }

    /**
     * Fetch all onboarding questions
     */
    public function testGetOnboardingQuestions()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    /**
     * Post a request for subscriptions activation
     */
    public function testPostSubscriptionsOnboardingResponses()
    {
        $this->createMerchantDetails(self::ONBOARDING_MERCHANT_ID);

        $testData = $this->testData[__FUNCTION__];

        $request = $testData['request'];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertTrue($response);

        $this->verifyMerchantRequest(
            Constants::SUBSCRIPTIONS,
            MerchantRequest\Type::PRODUCT,
            MerchantRequest\Status::UNDER_REVIEW);
    }

    /**
     * For Backward Compatibility : Assert that Merchant Request is also created and that the status, name, type is
     * as expected
     */
    public function verifyMerchantRequest($featureName, $featureType, $requestStatus, $mode = Mode::TEST)
    {
        $merchantRequest = $this->getDbLastEntityToArray('merchant_request', $mode);

        $this->assertNotEmpty($merchantRequest);

        $this->assertEquals($featureName, $merchantRequest[MerchantRequest\Entity::NAME]);

        $this->assertEquals($featureType, $merchantRequest[MerchantRequest\Entity::TYPE]);

        $this->assertEquals(
            $requestStatus,
            $merchantRequest[MerchantRequest\Entity::STATUS]
        );
    }

    /**
     * Enable a non-notifyFeature on Live mode
     */
    public function testFeatureEnabledEmailNonNotify()
    {
        Mail::fake();

        $this->addFeatures(Mode::LIVE, true);

        Mail::assertNotQueued(FeatureEnabledEmail::class);
    }

    public function testSkipFeatureEnableNotification()
    {
        Mail::fake();

        $this->createMerchantDetails(self::ONBOARDING_MERCHANT_ID);

        $this->addFeatures(
            Mode::LIVE,
            true,
            ['subscriptions'],
            'merchant',
            self::ONBOARDING_MERCHANT_ID);

        Mail::assertNotQueued(FeatureEnabledEmail::class);
    }

    public function testProductFeatureEnabledEmailNotifyCustomBrandingOrg()
    {
        Mail::fake();

        $merchantId = $this->createMerchantDetails(self::ONBOARDING_MERCHANT_ID);

        $org = $this->createCustomBrandingOrgAndAssignMerchant(self::ONBOARDING_MERCHANT_ID);

        $this->addFeatures(
            Mode::LIVE,
            false,
            ['subscriptions'],
            'merchant',
            self::ONBOARDING_MERCHANT_ID);

        Mail::assertNotQueued(FeatureEnabledEmail::class, function ($mail) use ($org)
        {
            $this->assertCustomBrandingMailViewData($org, $mail->viewData);

            return true;
        });
    }

    public function testEsEligibleEmailNotify()
    {
        Mail::fake();

        $this->addFeatures(Mode::LIVE, true, [Constants::ES_ON_DEMAND]);

        Mail::assertQueued(FullESMail::class);
    }

    public function testEsEligibleEmailShouldNotNotify()
    {
        Mail::fake();

        $this->fixtures->feature->create([
            'entity_type' => 'merchant', 'entity_id'  => '10000000000000', 'name' => 'es_automatic']);

        $this->addFeatures(Mode::LIVE, true, [Constants::ES_ON_DEMAND], 'merchant', '10000000000000');

        Mail::assertNotQueued(EsEligibleMail::class);
    }

    /*
     * Test cases for feature routes accessible from the merchant
     * dashboard begin from here.
     */

    /**
     * Fetches the features using the route used by the
     * merchant dashboard
     */
    public function testGetFeaturesAsMerchant()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    /**
     * Checks if the feature is active for the merchant
     * merchant dashboard
     */
    public function testCheckFeatureStatus()
    {
        $this->fixtures->create(
            'feature',
            [
                'entity_id' => '10000000000000',
                'name' => 'subscriptions'
            ]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    /**
     * Checks if the feature is active for the merchant
     * merchant dashboard
     */
    public function testCheckFeatureAllProxyAuth()
    {
        $this->fixtures->create(
            'feature',
            [
                'entity_id' => '10000000000000',
                'name' => 'subscriptions'
            ]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    /**
     * This function tests updating of a visible merchant feature: noflashcheckout
     */
    public function testUpdateMerchantFeatures()
    {
        $this->ba->proxyAuth();

        $this->startTest();

        $this->verifyFeaturePresence(Mode::TEST, ['noflashcheckout']);
    }

    /**
     * This function tests updating of a visible merchant feature: es_automatic.
     * Should fail if feature es_on_demand is not added to the merchant.
     */
    public function testEnableEsAutomaticFeaturesFailure()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    /**
     * This function tests updating of a merchant feature with should_sync parameter
     */
    public function testAddMerchantFeaturesWithSyncOnLive()
    {
        $this->updateFeatureAsMerchant(
            'add',
            Mode::LIVE,
            'noflashcheckout',
            false,
            true);

        $this->verifyFeaturePresence(Mode::TEST, ['noflashcheckout']);

        $this->verifyFeaturePresence(Mode::LIVE, ['noflashcheckout']);
    }

    /**
     * This function tests updating of a merchant feature with should_sync parameter
     */
    public function testAddMerchantFeaturesWithSyncOnTest()
    {
        $this->updateFeatureAsMerchant(
            'add',
            Mode::TEST,
            'noflashcheckout',
            false,
            true);

        $this->verifyFeaturePresence(Mode::TEST, ['noflashcheckout']);

        $this->verifyFeaturePresence(Mode::LIVE, ['noflashcheckout']);
    }

    /**
     * This function tests updating of a non visble merchant feature: dummy
     */
    public function testUpdateMerchantProductFeatures()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    /**
     * This function tests updating of a merchant feature that
     * can be updated on test but not live mode: marketplace.
     * This test also checks the format of the response received
     * to the merchant.
     */
    public function testAddMerchantEditableFeaturesOnTest()
    {
        $this->ba->proxyAuthTest();

        $this->startTest();
    }

    /**
     * This function tests updating of merchant feature es_automatic.
     * It will delete feature es_on_demand in the process.
     */
    public function testAddMerchantEsAutomaticFeatureOnTest()
    {
        $this->addFeatures(Mode::TEST, false, [Constants::ES_ON_DEMAND]);

        $this->ba->proxyAuthTest();

        $this->startTest();
    }

    public function testAddRTBFeatureMerchantNotActivatedForFourMonths()
    {
        $this->startTest();
    }

    public function testAddRTBFeatureMerchantWithDisputes()
    {
        $this->fixtures->create('merchant', [
            'id' => 'mer12345678900',
        ]);

        $this->fixtures->create('dispute',
            [
                'id' => '1000000dispute',
                'merchant_id' => 'mer12345678900',
                'status' => 'lost',
            ]
        );


        $this->startTest();
    }

    public function testAddRTBFeatureMerchantLendingCategory()
    {
        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID,
        [
            'category2' => 'lending',
        ]);

        $this->startTest();
    }

    public function testAddRTBFeatureMerchantDifferentOrg()
    {
        $org = $this->fixtures->create('org');

        $this->fixtures->create('merchant', [
            'id' => 'mer12345678900',
            'org_id' => $org->getId(),
        ]);

        $this->ba->adminAuth('test');

        $this->startTest();
    }

    public function testAddRTBFeature()
    {
        $this->startTest();
    }
    public function testAddM2MReferral()
    {
        $this->fixtures->create('merchant_detail', [
            'merchant_id' => '10000000000000'
        ]);

        $this->ba->adminAuth(MODE::TEST);

        $this->startTest();
    }

    public function testGetNotExistingM2MReferralFeatureStatus()
    {

        $this->fixtures->on('live')->create('merchant_detail:valid_fields', [
            'merchant_id' => '10000000000000',
        ]);
        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->startTest();
    }

    public function testGetM2MReferralStatusReferralCountNotCrossedLimit()
    {
        $this->fixtures->on('live')->create('feature', [
            'name'        => 'm2m_referral',
            'entity_id'   => '10000000000000',
            'entity_type' => 'merchant'
        ]);

        $this->fixtures->on('live')->create('merchant_detail:valid_fields', [
            'merchant_id' => '10000000000000',
        ]);

        $this->mockExperiment();

        $data = [
            \RZP\Models\Merchant\Store\Constants::NAMESPACE => StoreConfigKey::ONBOARDING_NAMESPACE,
            StoreConfigKey::REFERRED_COUNT              => 0
        ];

        (new \RZP\Models\Merchant\Store\Core())->updateMerchantStore('10000000000000', $data, \RZP\Models\Merchant\Store\Constants::INTERNAL);

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->startTest();
    }

    public function testGetM2MReferralStatusReferralCountCrossedLimit()
    {
        $this->fixtures->on('live')->create('feature', [
            'name'        => 'm2m_referral',
            'entity_id'   => '10000000000000',
            'entity_type' => 'merchant'
        ]);

        $this->fixtures->on('live')->create('merchant_detail:valid_fields', [
            'merchant_id' => '10000000000000',
        ]);

        $this->mockExperiment();

        $data = [
            \RZP\Models\Merchant\Store\Constants::NAMESPACE => StoreConfigKey::ONBOARDING_NAMESPACE,
            StoreConfigKey::REFERRED_COUNT              => 5
        ];

        (new \RZP\Models\Merchant\Store\Core())->updateMerchantStore('10000000000000', $data,\RZP\Models\Merchant\Store\Constants::INTERNAL);

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->startTest();
    }

    public function mockExperiment()
    {

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    if ($feature === RazorxTreatment::SHOW_FRIENDBUY_WIDGET)
                    {
                        return 'on';
                    }
                    else
                    {
                        return 'off';
                    }

                }) );
    }

    /**
     * This function tests updating of merchant feature loc_stage_2.
     * It will fail because loc_stage_1 is not present
     */
    public function testAddMerchantLocStage2FeatureFailure()
    {
        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->startTest();
    }

    /**
     * This function tests updating of merchant feature loc_stage_2.
     * It will pass because loc_stage_1 is present
     */
    public function testAddMerchantLocStage2FeatureSuccess()
    {
        $this->addFeatures(Mode::LIVE, false, [Constants::LOC_STAGE_1]);

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $response = $this->startTest();
        $features = $response['features'];

        $check = false;

        foreach ($features as $feature)
        {
            switch ($feature['feature'])
            {
                case Constants::LOC_STAGE_2:
                    $this->assertTrue($feature['value']);

                    $this->assertEquals(
                        Constants::$visibleFeaturesMap[Constants::LOC_STAGE_2]['display_name'],
                        $feature['display_name']
                    );

                    $check = true;
                    break;

                case Constants::LOC_STAGE_1:
                    $this->assertTrue($feature['value']);

                    $this->assertEquals(
                        Constants::$visibleFeaturesMap[Constants::LOC_STAGE_1]['display_name'],
                        $feature['display_name']
                    );

                    break;

                default:
                    $this->assertFalse($feature['value']);
            }
        }
        $this->assertTrue($check);

    }

    /**
     * This function tests updating of merchant feature loc_stage_2.
     * It will pass even without loc_stage_1 because admin sent the request
     */
    public function testAddMerchantLocStage2FeatureAdminAuth()
    {
        $this->ba->adminAuth(Mode::LIVE, null, 'org_100000razorpay');

        $this->startTest();
    }

    /**
     * This function tests updating of merchant feature loc_stage_1.
     */
    public function testAddMerchantLocStage1FeatureAdminAuth()
    {
        Mail::fake();
        $this->ba->adminAuth(Mode::LIVE, null, 'org_100000razorpay');

        $this->startTest();
    }
    /**
     * This function tests updating of merchant feature card_transaction_limit_1.
     */
    public function testAddMerchantCardTransactionLimit1FeatureAdminAuth()
    {
        Mail::fake();
        $this->ba->adminAuth(Mode::LIVE, null, 'org_100000razorpay');

        $this->startTest();
    }

    /**
     * This function tests updating of merchant feature card_transaction_limit_2.
     */
    public function testAddMerchantCardTransactionLimit2FeatureAdminAuth()
    {
        Mail::fake();
        $this->ba->adminAuth(Mode::LIVE, null, 'org_100000razorpay');

        $this->startTest();
    }

    /**
     * This function tests updating of merchant feature disable_ondemand_for_loc.
     */
    public function testAddMerchantDisableOnDemandForLocFeatureInternalAuth()
    {
        $collectionsServiceConfig = \Config::get('applications.capital_collections_client');
        $pwd = $collectionsServiceConfig['secret'];

        $this->ba->appAuth('rzp_'.'test', $pwd);
        $this->startTest();
    }

     /**
     * This function tests updating of merchant feature disable_ondemand_for_loc.
     */
    public function testFailureAddMerchantDisableOnDemandForLocFeatureAdminAuth()
    {
        $this->ba->adminAuth(Mode::LIVE, null, 'org_100000razorpay');
        $this->startTest();
    }

    /**
     * This function tests updating of merchant feature disable_ondemand_for_loan.
     */
    public function testFailureAddMerchantDisableOnDemandForLoanFeatureAdminAuth()
    {
        $this->ba->adminAuth(Mode::LIVE, null, 'org_100000razorpay');
        $this->startTest();
    }

    /**
     * This function tests updating of merchant feature disable_ondemand_for_card.
     */
    public function testFailureAddMerchantDisableOnDemandForCardFeatureAdminAuth()
    {
        $this->ba->adminAuth(Mode::LIVE, null, 'org_100000razorpay');
        $this->startTest();
    }

    /**
     * This function tests updating of merchant feature disable_ondemand_for_loan.
     */
    public function testAddMerchantDisableOnDemandForLoanFeatureInternalAuth()
    {
        $collectionsServiceConfig = \Config::get('applications.capital_collections_client');
        $pwd = $collectionsServiceConfig['secret'];

        $this->ba->appAuth('rzp_'.'test', $pwd);
        $this->startTest();
    }

    /**
     * This function tests updating of merchant feature disable_ondemand_for_card.
     */
    public function testAddMerchantDisableOnDemandForCardFeatureInternalAuth()
    {
        $collectionsServiceConfig = \Config::get('applications.capital_collections_client');
        $pwd = $collectionsServiceConfig['secret'];

        $this->ba->appAuth('rzp_'.'test', $pwd);
        $this->startTest();
    }

    /**
     * This function tests updating of merchant feature disable_loc_post_dpd.
     */
    public function testAddMerchantDisableLocPostDpdFeatureInternalAuth()
    {
        $collectionsServiceConfig = \Config::get('applications.capital_collections_client');
        $pwd = $collectionsServiceConfig['secret'];

        $this->ba->appAuth('rzp_'.'test', $pwd);
        $this->startTest();
    }

    /**
     * This function tests updating of merchant feature disable_loc_post_dpd.
     */
    public function testFailureAddMerchantDisableLocPostDpdFeatureAdminAuth()
    {
        $this->ba->adminAuth(Mode::LIVE, null, 'org_100000razorpay');
        $this->startTest();
    }

    /**
     * This function tests updating of merchant feature disable_amazon_is_post_dpd.
     */
    public function testAddDisableAmazonISPostDpdInternal()
    {
        $collectionsServiceConfig = \Config::get('applications.capital_collections_client');
        $pwd = $collectionsServiceConfig['secret'];

        $this->ba->appAuth('rzp_'.'test', $pwd);
        $this->startTest();
    }

    /**
     * This function tests updating of merchant feature disable_amazon_is_post_dpd.
     */
    public function testFailAddDisableAmazonISPostDpdAdmin()
    {
        $this->ba->adminAuth(Mode::LIVE, null, 'org_100000razorpay');
        $this->startTest();
    }

    /**
     * This function tests updating of merchant feature disable_loans_post_dpd.
     */
    public function testFailureAddMerchantDisableLoansPostDpdFeatureAdminAuth()
    {
        $this->ba->adminAuth(Mode::LIVE, null, 'org_100000razorpay');
        $this->startTest();
    }

    /**
     * This function tests updating of merchant feature disable_cards_post_dpd.
     */
    public function testFailureAddMerchantDisableCardsPostDpdFeatureAdminAuth()
    {
        $this->ba->adminAuth(Mode::LIVE, null, 'org_100000razorpay');
        $this->startTest();
    }

    /**
     * This function tests updating of merchant feature disable_loans_post_dpd.
     */
    public function testAddMerchantDisableLoansPostDpdFeatureInternalAuth()
    {
        $collectionsServiceConfig = \Config::get('applications.capital_collections_client');
        $pwd = $collectionsServiceConfig['secret'];

        $this->ba->appAuth('rzp_'.'test', $pwd);
        $this->startTest();
    }

    /**
     * This function tests updating of merchant feature disable_cards_post_dpd.
     */
    public function testAddMerchantDisableCardsPostDpdFeatureInternalAuth()
    {
        $collectionsServiceConfig = \Config::get('applications.capital_collections_client');
        $pwd = $collectionsServiceConfig['secret'];

        $this->ba->appAuth('rzp_'.'test', $pwd);
        $this->startTest();
    }

    /**
     * This function tests getting the features using internal auth
     */
    public function testGetMultipleFeaturesInternalAuth()
    {
        $collectionsServiceConfig = \Config::get('applications.capital_collections_client');

        $pwd = $collectionsServiceConfig['secret'];

        $this->ba->appAuth('rzp_'.'test', $pwd);

        $content = $this->startTest();

        $featuresWithValues = count(Constants::$featureValueMap);

        $featuresInResponse = count($content['all_features']);

        $this->assertEquals($featuresWithValues, $featuresInResponse);

    }

      /**
      * Add Disable ondemand for loc feature to live and sync it to test
      * Delete the feature from the live database via internal auth
      * Verify - Any feature deleted from live should not be deleted from test
      */
    public function testDeleteDisableOndemandForLocFeatureUsingInternalAuth()
    {

        $this->testAddMerchantDisableOnDemandForLocFeatureInternalAuth();

        $this->verifyFeaturePresence(Mode::TEST,[Constants::DISABLE_ONDEMAND_FOR_LOC]);

        $this->verifyFeaturePresence(Mode::LIVE,[Constants::DISABLE_ONDEMAND_FOR_LOC]);

        $this->deleteFeatureInternal(Mode::LIVE, true,Constants::DISABLE_ONDEMAND_FOR_LOC);

        $this->verifyFeatureAbsence(Mode::LIVE,[Constants::DISABLE_ONDEMAND_FOR_LOC]);

        $this->verifyFeatureAbsence(Mode::TEST,[Constants::DISABLE_ONDEMAND_FOR_LOC]);
    }

    /**
    * Add Disable ondemand for loc feature to live and sync it to test
    * Fails deleting the feature from the live database via admin auth
    */
    public function testFailureDeleteDisableOndemandForLocFeatureUsingAdminAuth()
    {

        $this->testAddMerchantDisableOnDemandForLocFeatureInternalAuth();

        $this->verifyFeaturePresence(Mode::TEST,[Constants::DISABLE_ONDEMAND_FOR_LOC]);

        $this->verifyFeaturePresence(Mode::LIVE,[Constants::DISABLE_ONDEMAND_FOR_LOC]);

        $this->deleteFeatureFailure(Mode::LIVE, true,Constants::DISABLE_ONDEMAND_FOR_LOC);


    }

    /**
    * Add Disable ondemand for loan feature to live and sync it to test
    * Fails deleting the feature from the live database via admin auth
    */
    public function testFailureDeleteDisableOndemandForLoanFeatureUsingAdminAuth()
    {

        $this->testAddMerchantDisableOnDemandForLoanFeatureInternalAuth();

        $this->verifyFeaturePresence(Mode::TEST,[Constants::DISABLE_ONDEMAND_FOR_LOAN]);

        $this->verifyFeaturePresence(Mode::LIVE,[Constants::DISABLE_ONDEMAND_FOR_LOAN]);

        $this->deleteFeatureFailure(Mode::LIVE, true,Constants::DISABLE_ONDEMAND_FOR_LOAN);


    }

    /**
    * Add Disable ondemand for card feature to live and sync it to test
    * Fails deleting the feature from the live database via admin auth
    */
    public function testSuccessDeleteDisableOndemandForCardFeatureUsingAdminAuth()
    {

        $this->testAddMerchantDisableOnDemandForCardFeatureInternalAuth();

        $this->verifyFeaturePresence(Mode::TEST,[Constants::DISABLE_ONDEMAND_FOR_CARD]);

        $this->verifyFeaturePresence(Mode::LIVE,[Constants::DISABLE_ONDEMAND_FOR_CARD]);

        $this->deleteFeature(Mode::LIVE, true,Constants::DISABLE_ONDEMAND_FOR_CARD);

    }

    /**
     * Add Disable loc post dpd feature to live and sync it to test
     * Delete the feature from the live database via internal auth
     * Verify - Any feature deleted from live should not be deleted from test
     */
    public function testDeleteDisableLocPostDpdUsingInternalAuth()
    {

        $this->testAddMerchantDisableLocPostDpdFeatureInternalAuth();

        $this->verifyFeaturePresence(Mode::TEST,[Constants::DISABLE_LOC_POST_DPD]);

        $this->verifyFeaturePresence(Mode::LIVE,[Constants::DISABLE_LOC_POST_DPD]);

        $this->deleteFeatureInternal(Mode::LIVE, true,Constants::DISABLE_LOC_POST_DPD);

        $this->verifyFeatureAbsence(Mode::LIVE,[Constants::DISABLE_LOC_POST_DPD]);

        $this->verifyFeatureAbsence(Mode::TEST,[Constants::DISABLE_LOC_POST_DPD]);
    }

    /**
     * Add Disable AmazonIS post dpd feature to live and sync it to test
     * Delete the feature from the live database via internal auth
     * Verify - Any feature deleted from live should not be deleted from test
     */
    public function testDelDisableAmazonISPostDpdInternal()
    {

        $this->testAddDisableAmazonISPostDpdInternal();

        $this->verifyFeaturePresence(Mode::TEST,[Constants::DISABLE_AMAZON_IS_POST_DPD]);

        $this->verifyFeaturePresence(Mode::LIVE,[Constants::DISABLE_AMAZON_IS_POST_DPD]);

        $this->deleteFeatureInternal(Mode::LIVE, true,Constants::DISABLE_AMAZON_IS_POST_DPD);

        $this->verifyFeatureAbsence(Mode::LIVE,[Constants::DISABLE_AMAZON_IS_POST_DPD]);

        $this->verifyFeatureAbsence(Mode::TEST,[Constants::DISABLE_AMAZON_IS_POST_DPD]);
    }

    /**
     * Add Disable loans post dpd feature to live and sync it to test
     * Delete the feature from the live database via internal auth
     * Verify - Any feature deleted from live should not be deleted from test
     */
    public function testDeleteDisableLoansPostDpdUsingInternalAuth()
    {

        $this->testAddMerchantDisableLoansPostDpdFeatureInternalAuth();

        $this->verifyFeaturePresence(Mode::TEST,[Constants::DISABLE_LOANS_POST_DPD]);

        $this->verifyFeaturePresence(Mode::LIVE,[Constants::DISABLE_LOANS_POST_DPD]);

        $this->deleteFeatureInternal(Mode::LIVE, true,Constants::DISABLE_LOANS_POST_DPD);

        $this->verifyFeatureAbsence(Mode::LIVE,[Constants::DISABLE_LOANS_POST_DPD]);

        $this->verifyFeatureAbsence(Mode::TEST,[Constants::DISABLE_LOANS_POST_DPD]);
    }

    /**
     * Add Disable cards post dpd feature to live and sync it to test
     * Delete the feature from the live database via internal auth
     * Verify - Any feature deleted from live should not be deleted from test
     */
    public function testDeleteDisableCardsPostDpdUsingInternalAuth()
    {

        $this->testAddMerchantDisableCardsPostDpdFeatureInternalAuth();

        $this->verifyFeaturePresence(Mode::TEST,[Constants::DISABLE_CARDS_POST_DPD]);

        $this->verifyFeaturePresence(Mode::LIVE,[Constants::DISABLE_CARDS_POST_DPD]);

        $this->deleteFeatureInternal(Mode::LIVE, true,Constants::DISABLE_CARDS_POST_DPD);

        $this->verifyFeatureAbsence(Mode::LIVE,[Constants::DISABLE_CARDS_POST_DPD]);

        $this->verifyFeatureAbsence(Mode::TEST,[Constants::DISABLE_CARDS_POST_DPD]);
    }

    /**
     * Add Disable loc post dpd feature to live and sync it to test
     * Fails deleting the feature from the live database via admin auth
     */
    public function testFailureDeleteDisableLocPostDpdUsingAdminAuth()
    {

        $this->testAddMerchantDisableLocPostDpdFeatureInternalAuth();

        $this->verifyFeaturePresence(Mode::TEST,[Constants::DISABLE_LOC_POST_DPD]);

        $this->verifyFeaturePresence(Mode::LIVE,[Constants::DISABLE_LOC_POST_DPD]);

        $this->deleteFeatureFailure(Mode::LIVE, true,Constants::DISABLE_LOC_POST_DPD);


    }

    /**
     * Add Disable AmazonIS post dpd feature to live and sync it to test
     * Fails deleting the feature from the live database via admin auth
     */
    public function testFailDelDisableAmazonISPostDpdAdmin()
    {

        $this->testAddDisableAmazonISPostDpdInternal();

        $this->verifyFeaturePresence(Mode::TEST,[Constants::DISABLE_AMAZON_IS_POST_DPD]);

        $this->verifyFeaturePresence(Mode::LIVE,[Constants::DISABLE_AMAZON_IS_POST_DPD]);

        $this->deleteFeatureFailure(Mode::LIVE, true,Constants::DISABLE_AMAZON_IS_POST_DPD);


    }

    /**
     * Add Disable loans post dpd feature to live and sync it to test
     * Fails deleting the feature from the live database via admin auth
     */
    public function testFailureDeleteDisableLoansPostDpdUsingAdminAuth()
    {

        $this->testAddMerchantDisableLoansPostDpdFeatureInternalAuth();

        $this->verifyFeaturePresence(Mode::TEST,[Constants::DISABLE_LOANS_POST_DPD]);

        $this->verifyFeaturePresence(Mode::LIVE,[Constants::DISABLE_LOANS_POST_DPD]);

        $this->deleteFeatureFailure(Mode::LIVE, true,Constants::DISABLE_LOANS_POST_DPD);


    }

    /**
     * Add Disable cards post dpd feature to live and sync it to test
     * Fails deleting the feature from the live database via admin auth
     */
    public function testFailureDeleteDisableCardsPostDpdUsingAdminAuth()
    {

        $this->testAddMerchantDisableCardsPostDpdFeatureInternalAuth();

        $this->verifyFeaturePresence(Mode::TEST,[Constants::DISABLE_CARDS_POST_DPD]);

        $this->verifyFeaturePresence(Mode::LIVE,[Constants::DISABLE_CARDS_POST_DPD]);

        $this->deleteFeatureFailure(Mode::LIVE, true,Constants::DISABLE_CARDS_POST_DPD);


    }

    /**
     * Deletes a feature via internal auth
     * the params received
     *
     * @param string $deleteFromMode
     * @param bool   $shouldSync
     * @param string $featureName
     * @deprecated by getDataToDeleteFeaturesFromEntity
     */
    protected function deleteFeatureInternal(
        string $deleteFromMode,
        bool $shouldSync = false,
        string $featureName = 'dummy'
    ) {
        $collectionsServiceConfig = \Config::get('applications.capital_collections_client');

        $pwd = $collectionsServiceConfig['secret'];

        $this->ba->appAuth('rzp_'.'test', $pwd);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/internal/features/' . self::DEFAULT_MERCHANT_ID . '/' . $featureName;

        if ($shouldSync === true) {
            $testData['request']['content']['should_sync'] = 1;
        }

        $this->startTest($testData);
    }

    /**
     * Fails deleting a feature as an admin based on
     * the params received
     *
     * @param string $deleteFromMode
     * @param bool   $shouldSync
     * @param string $featureName
     * @deprecated by getDataToDeleteFeaturesFromEntity
     */
    protected function deleteFeatureFailure(
        string $deleteFromMode,
        bool $shouldSync = false,
        string $featureName = 'dummy'
    ) {
        $this->ba->adminAuth($deleteFromMode, null, 'org_100000razorpay');

        $testData = $this->testData[__FUNCTION__];


        $testData['request']['url'] = '/features/' . self::DEFAULT_MERCHANT_ID . '/' . $featureName;

        if ($shouldSync === true) {
            $testData['request']['content']['should_sync'] = 1;
        }

        $this->startTest($testData);
    }

    protected function expectStorkSendSmsRequest($storkMock, $templateName, $destination, $expectedParms = [])
    {
        $storkMock->shouldReceive('sendSms')
                  ->times(2)
                  ->with(
                      Mockery::on(function ($mockInMode)
                      {
                          return true;
                      }),
                      Mockery::on(function ($actualPayload) use ($templateName, $destination, $expectedParms)
                      {

                          // We are sending null in contentParams in the payload if there is no SMS_TEMPLATE_KEYS present for that event
                          // Reference: app/Notifications/Dashboard/SmsNotificationService.php L:99
                          if(isset($actualPayload['contentParams']) === true)
                          {
                              $this->assertArraySelectiveEquals($expectedParms, $actualPayload['contentParams']);
                          }

                          if (($templateName !== $actualPayload['templateName']) or
                              ($destination !== $actualPayload['destination']))
                          {
                              return false;
                          }

                          return true;
                      }))
                  ->andReturnUsing(function ()
                  {
                      return ['success' => true];
                  });
    }

    protected function expectStorkWhatsappRequest($storkMock, $text, $destination): void
    {
        $storkMock->shouldReceive('sendWhatsappMessage')
                  ->times(2)
                  ->with(
                      Mockery::on(function ($mode)
                      {
                          return true;
                      }),
                      Mockery::on(function ($actualText) use($text)
                      {
                          $actualText = trim(preg_replace('/\s+/', ' ', $actualText));

                          $text = trim(preg_replace('/\s+/', ' ', $text));

                          if ($actualText !== $text)
                          {
                              return false;
                          }

                          return true;
                      }),
                      Mockery::on(function ($actualReceiver) use($destination)
                      {
                          if ($actualReceiver !== $destination)
                          {
                              return false;
                          }
                          return true;
                      }),
                      Mockery::on(function ($input)
                      {
                          return true;
                      }))
                  ->andReturnUsing(function ()
                  {
                      $response = new \Requests_Response;

                      $response->body = json_encode(['key' => 'value']);

                      return $response;
                  });
    }

    public function mockStorkForFeatureEnabledVirtualAccounts()
    {
        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $expectedStorkParametersForTemplate = [
            'feature' => 'Smart Collect'
        ];

        $this->expectStorkSendSmsRequest($storkMock,'sms.dashboard.feature_enabled', '1234567890', $expectedStorkParametersForTemplate);

        $this->expectStorkWhatsappRequest($storkMock,
                                          'Hi,
Smart Collect has been enabled on your Razorpay account. You can now start using Smart Collect for live transactions.
Regards,
-Team Razorpay',
                                          '1234567890'
        );
    }

    private function setupMerchantWithMerchantDetails()
    {
        $this->setMockRazorxTreatment(['whatsapp_notifications' => 'on']);

        $merchantId = self::ONBOARDING_MERCHANT_ID;

        $attributes = ['id' => $merchantId, 'org_id' => Org::RZP_ORG];

        $detailsAttributes = ['merchant_id' => $merchantId, 'contact_email' => 'test@gmail.com'];

        $this->fixtures->on(Mode::LIVE)->create('merchant', $attributes);

        $this->fixtures->on(Mode::TEST)->create('merchant_detail:sane', $detailsAttributes);

        $this->fixtures->on(Mode::LIVE)->create('merchant_detail:sane', $detailsAttributes);

        $this->fixtures->user->createUserForMerchant($merchantId, [
            'contact_mobile'          => '1234567890',
            'contact_mobile_verified' => true,
        ], 'owner', 'live');

        return $merchantId;
    }

    /**
     * This function tests updating of merchant feature loc_stage_1.
     */
    public function testAddMerchantVirtualAccountsFeatureAdminAuthNotify()
    {
        Mail::fake();

        $merchantId = $this->setupMerchantWithMerchantDetails();

        $this->ba->adminAuth(Mode::LIVE, null, 'org_100000razorpay');

        $this->mockStorkForFeatureEnabledVirtualAccounts();

        $this->bulkUpdateFeatureActivationStatus(Constants::VIRTUAL_ACCOUNTS, $merchantId , 'approved');

        Mail::assertQueued(MerchantDashboardEmail::class, function ($mail)
        {
            $this->assertEquals('Smart Collect', $mail->viewData['feature']);
            return true;
        });
    }

    /**
     * This function tests updating of merchant feature loc_stage_1.
     */
    public function testAddMerchantLocFeatureAdminAuth()
    {
        Mail::fake();
        $this->ba->adminAuth(Mode::LIVE, null, 'org_100000razorpay');

        $this->startTest();
        Mail::assertQueued(CashAdvanceEligible::class);
    }

    /**
     * This function tests updating of a merchant feature that can be
     * updated by the merchant on test but not live mode: marketplace.
     */
    public function testAddMerchantProductFeatures()
    {
        /*
         * $input[0] - $action
         * $input[1] - $addToMode
         * $input[2] - $featureName
         * $input[3] - $expectBadRequestException
         * $input[4] - $shouldSync
         */

        $this->fixtures->create('merchant_detail:sane',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $inputs = [
            ['add', Mode::LIVE, 'marketplace', true, false],
            ['add', Mode::TEST, 'marketplace', true, true],
            ['add', Mode::LIVE, 'marketplace', true, true],
        ];

        foreach($inputs as $input)
        {
            $this->updateFeatureAsMerchant(
                $input[0],
                $input[1],
                $input[2],
                $input[3],
                $input[4]);
        }

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $inputs = [
            ['remove', Mode::LIVE, 'marketplace', true, false],
            ['remove', Mode::TEST, 'marketplace', true, true],
            ['remove', Mode::LIVE, 'marketplace', true, true],
        ];

        foreach($inputs as $input)
        {
            $this->updateFeatureAsMerchant(
                $input[0],
                $input[1],
                $input[2],
                $input[3],
                $input[4]);
        }
    }

    /**
     * This function tests adding a non visible feature to a merchant account through a private auth
     */
    public function testAddNonVisibleFeatureToAccount()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testAddNonEditableFeatureToAccount()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    /**
     * Asserts that a product feature cannot be added in the live mode by a merchant using a private auth
     */
    public function testAddProductFeatureToAccountInLive()
    {
        $this->ba->privateAuth(self::LIVE_AUTH_KEY);

        $this->fixtures->merchant->activate(self::DEFAULT_MERCHANT_ID);

        $this->startTest();
    }

    public function testUpdateOnboardingResponses()
    {
        $liveMode = $this->app['basicauth']->getLiveConnection();

        $merchantId = $this->createMerchantDetails(self::ONBOARDING_MERCHANT_ID);

        $filestoreEntityId = $this->postOnboardingResponses($merchantId);

        $this->ba->adminAuth($liveMode, null, 'org_100000razorpay');

        $this->updateMarketplaceOnboardingResponse();

        $user = $this->fixtures->user->createUserForMerchant($merchantId, [], 'owner', $liveMode);

        $this->ba->proxyAuth('rzp_' . $liveMode . '_' . $merchantId, $user->getId());

        $testData = $this->testData[__FUNCTION__];

        // tests that if while updating the submission response, the file is not updated,
        // the previously stored file details are preserved.
        $testData['response']['content'][Constants::VENDOR_AGREEMENT] = "api/$merchantId/marketplace.vendor_agreement.pdf";

        $this->startTest($testData);
    }

    public function testResendOnboardingResponses()
    {
        $merchantId = $this->createMerchantDetails(self::ONBOARDING_MERCHANT_ID);

        $this->createMarketplaceOnboardingResponse($merchantId);

        $this->createMarketplaceOnboardingResponse($merchantId, true);
    }

    public function testOnboardingRequestStatus()
    {
        Mail::fake();

        $merchantId = $this->createMerchantDetails(self::ONBOARDING_MERCHANT_ID);

        $this->createMarketplaceOnboardingResponse($merchantId);

        // Test update status API
        $this->updateMarketplaceOnboardingResponseStatus($merchantId, 'rejected');

        $this->addFeatures(
            Mode::LIVE,
            false,
            [Constants::MARKETPLACE],
            Constants::MERCHANT,
            $merchantId);

        $this->ba->adminAuth(Mode::LIVE, null, 'org_100000razorpay');

        // Test fetch by status and product API
        // Test auto approving of a request, when added in Live mode
        $this->verifyProductOnboardingSubmissionStatus('approved', Constants::MARKETPLACE);

        // Test the fetch status route
        $this->getMarketplaceOnboardingResponseStatus();

        Mail::assertQueued(MerchantDashboardEmail::class, function ($mail)
        {
            $this->assertEquals('Route', $mail->viewData['feature']);

            $documentation = 'route';
            $this->assertEquals($documentation, $mail->viewData['documentation']);

            return true;
        });
    }

    public function testOnboardingRequestStatusUpdateLeadingToMerchantRequestCreation()
    {
        $merchantId = $this->createMerchantDetails(self::ONBOARDING_MERCHANT_ID);

        // Test update status API
        $this->updateMarketplaceOnboardingResponseStatus($merchantId, 'rejected');
    }

    /**
     * The feature onboarding request should not be approved if the feature is enabled on Test mode
     * Also, email should not be sent to the merchant
     */
    public function testOnboardingRequestStatusForTestMode()
    {
        Mail::fake();

        $merchantId = $this->createMerchantDetails(self::ONBOARDING_MERCHANT_ID);

        $this->createMarketplaceOnboardingResponse($merchantId);

        $this->ba->adminAuth(Mode::LIVE, null, 'org_100000razorpay');

        $this->addFeatures(
            Mode::TEST,
            false,
            [Constants::MARKETPLACE],
            Constants::MERCHANT,
            $merchantId);

        $this->ba->adminAuth(Mode::LIVE, null, 'org_100000razorpay');

        $this->verifyProductOnboardingSubmissionStatus('pending');

        // Tests the bulk update route
        $this->bulkUpdateFeatureActivationStatus(Constants::MARKETPLACE, $merchantId, 'rejected');

        Mail::assertNotSent(FeatureEnabledEmail::class);
    }

    public function testFetchMerchantFeatures()
    {
        $content = $this->startTest();
    }

    public function testFetchMerchantFeaturesCheckBulkApproval()
    {
        $content = $this->startTest();
        $this->assertContains(Constants::API_BULK_APPROVALS, $content['all_features']);
    }

    /*
     * Helpers
     */

    public function createMarketplaceOnboardingResponse(string $merchantId, bool $expectError = false)
    {
        $user = $this->fixtures->user->createUserForMerchant($merchantId, [], 'owner', 'live');
        $this->ba->proxyAuth('rzp_live_' . $merchantId, $user->getId());

        $testData = $this->testData[__FUNCTION__];

        if ($expectError === true)
        {
            $errorDesc = PublicErrorDescription::BAD_REQUEST_MERCHANT_FEATURE_ACTIVATION_FORM_ALREADY_SUBMITTED;
            $testData['response'] = [
                'content' => [
                    'error' => [
                        'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => $errorDesc,
                    ],
                ],
                'status_code' => 400,
            ];

            $testData['exception'] = [
                'class' => 'RZP\Exception\BadRequestException',
                'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_ACTIVATION_FORM_ALREADY_SUBMITTED,
            ];

            $this->startTest($testData);
        }
        else
        {
            $testData['response'] = [
                'content' => true
            ];

            $response = $this->makeRequestAndGetContent($testData['request']);

            $this->assertTrue($response);
        }
    }

    public function testPostOnboardingResponses()
    {
        $merchantId = $this->createMerchantDetails(self::ONBOARDING_MERCHANT_ID);

        $this->postOnboardingResponses($merchantId);
    }

    public function updateMarketplaceOnboardingResponse()
    {
        $this->ba->adminAuth();

        $testData = $this->testData[__FUNCTION__];

        $request = $testData['request'];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertTrue($response);

        $this->verifyMerchantRequest(
            Constants::MARKETPLACE,
            MerchantRequest\Type::PRODUCT,
            MerchantRequest\Status::UNDER_REVIEW);
    }

    public function testRestrictedAccessFeatureEnabledAndAccessedByMerchant()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal');

        $this->fixtures->create(
            'feature',
            [
                'entity_id' => '10000000000000',
                'name' => 'virtual_accounts'
            ]);

        $this->ba->privateAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content'] = $this->getDefaultVirtualAccountRequestArray();

        $this->startTest($testData);
    }

    /**
     *  Application has feature s2s / s2s_json enabled.
     *  Submerchant and partner does not have feature enabled.
     */
    public function testS2SFeatureEnabledOnApplication()
    {
        $this->mockCardVault();

        $client = $this->setUpNonPurePlatformPartnerAndSubmerchant();

        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->create('feature',
                                [
                                    'entity_id'   => $client->getApplicationId(),
                                    'entity_type' => 'application',
                                    'name'        => 's2s_json'
                                ]);

        $this->fixtures->create('feature',
                                [
                                    'entity_id'   => $client->getApplicationId(),
                                    'entity_type' => 'application',
                                    'name'        => 's2s'
                                ]);

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/json',
            'content' => $payment,
            'server'  => [
                'HTTP_X-Razorpay-Account' => '100submerchant',
            ],
        ];

        $this->ba->privateAuth('rzp_test_partner_' . $client->getId(), $client->getSecret());

        $content = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('razorpay_payment_id', $content);
    }

    /**
     *   Managed Partner can access the submerchant for Restricted Access Feature enabled on Submerchant.
     *   Restricted Access Feature is not enable on partner / application.
     */
    public function testRestrictedAccessFeatureEnabledOnSubmerchantAndAccessedByPartner()
    {
        $client = $this->setUpNonPurePlatformPartnerAndSubmerchant();

        $this->fixtures->merchant->enableMethod('100submerchant', 'bank_transfer');

        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal');

        $this->fixtures->create(
            'feature',
            [
                'entity_id' => '100submerchant',
                'name'      => 'virtual_accounts'
            ]);

        $this->ba->privateAuth('rzp_test_partner_' . $client->getId(), $client->getSecret());

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content'] = $this->getDefaultVirtualAccountRequestArray();

        $this->startTest($testData);
    }

    public function testRestrictedAccessFeatureDisabledAndAccessedByMerchant()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->ba->privateAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content'] = $this->getDefaultVirtualAccountRequestArray();

        $this->startTest($testData);
    }

    /**
     * Post a request for product activation
     */
    protected function postOnboardingResponses(string $merchantId)
    {
        $liveMode = $this->app['basicauth']->getLiveConnection();

        $user = $this->fixtures->user->createUserForMerchant($merchantId, [], 'owner', $liveMode);

        $this->ba->proxyAuth('rzp_' . $liveMode . '_' . $merchantId, $user->getId());

        $url = storage_path("files/" . Constants::ONBOARDING .  "/" . Constants::VENDOR_AGREEMENT . ".pdf");

        $uploadedFile = $this->createUploadedFile($url);

        $testData = $this->testData[__FUNCTION__];

        $request = $testData['request'];

        $request['files'][Constants::VENDOR_AGREEMENT] = $uploadedFile;

        $response = $this->makeRequestAndGetContent($request);

        $this->assertTrue($response);

        $testData = $this->testData['getOnboardingResponses'];

        $request = $testData['request'];

        $expectedResponse = $testData['response']['content'];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $fileStoreData = $this->getDbLastEntityPublic('file_store', $liveMode);

        $testData = $this->testData['testFileStoreData'];

        $expectedOutput = $testData['response']['content'];

        $this->assertArraySelectiveEquals($expectedOutput, $fileStoreData);

        $fileStoreId = $fileStoreData['id'];

        $this->fixtures->stripSign($fileStoreId);

        $this->verifyMerchantRequest(
            Constants::MARKETPLACE,
            MerchantRequest\Type::PRODUCT,
            MerchantRequest\Status::UNDER_REVIEW);

        return $fileStoreId;
    }

    protected function verifyProductOnboardingSubmissionStatus(string $status, string $product = null)
    {
        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['status'] = $status;

        $testData['response']['content'][0]['status'] = $status;

        if ($product !== null)
        {
            $testData['request']['content']['product'] = $product;

            $testData['response']['content'][0]['product'] = $product;
        }

        $this->startTest($testData);
    }

    protected function updateMarketplaceOnboardingResponseStatus(string $merchantId, string $status)
    {
        $liveMode = $this->app['basicauth']->getLiveConnection();

        $this->ba->adminAuth($liveMode, null, 'org_100000razorpay');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['merchant_id'] = $merchantId;

        $testData['request']['content']['status'] = $status;

        $testData['response']['content']['marketplace_activation_status'] = $status;

        $this->startTest($testData);

        $this->verifyMerchantRequest(
            Constants::MARKETPLACE,
            MerchantRequest\Type::PRODUCT,
            MerchantRequest\Constants::getRequestStatusForOnboardingStatus($status),
            $liveMode);
    }

    /**
     * Simulates merchant behavior based on the
     * params received
     *
     * @param string $action
     * @param string $mode
     * @param string $featureName
     * @param bool   $expectBadRequestException
     * @param bool   $shouldSync
     */
    protected function updateFeatureAsMerchant(
        string $action,
        string $mode,
        string $featureName = 'noflashcheckout',
        bool $expectBadRequestException = false,
        bool $shouldSync = true)
    {
        $authMethod = 'proxyAuth' . studly_case($mode);

        $this->ba->$authMethod();

        $testData = $this->testData[__FUNCTION__];
        if ($action === 'add')
        {
            $testData['request']['content']['features'][$featureName] = '1';
        }
        else
        {
            $testData['request']['content']['features'][$featureName] = '0';
        }

        if ($expectBadRequestException === true)
        {
            $testData['response'] = [
                'content' => [
                    'error' => [
                        'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_FEATURE_UNEDITABLE_IN_LIVE
                    ],
                ],
                'status_code' => 400,
            ];

            $testData['exception'] = [
                'class' => 'RZP\Exception\BadRequestException',
                'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_UNEDITABLE_IN_LIVE,
            ];
        }

        if ($shouldSync === true)
        {
            $testData['request']['content']['should_sync'] = 1;
        }

        $this->startTest($testData);
    }

    protected function createMerchantDetails(string $merchantId)
    {
        $attributes = ['id' => $merchantId, 'org_id' => Org::RZP_ORG];

        $detailsAttributes = ['merchant_id' => $merchantId, 'contact_email' => 'test@gmail.com'];

        $this->fixtures->on(Mode::LIVE)->create('merchant', $attributes);

        $this->fixtures->on(Mode::TEST)->create('merchant_detail:sane', $detailsAttributes);

        $this->fixtures->on(Mode::LIVE)->create('merchant_detail:sane', $detailsAttributes);

        $user = $this->fixtures->user->createUserForMerchant($merchantId, [], 'owner', 'live');

        $this->ba->proxyAuth('rzp_live_' . $merchantId, $user->getId());

        return $merchantId;
    }

    protected function getMarketplaceOnboardingResponseStatus()
    {
        $this->startTest();
    }

    protected function bulkUpdateFeatureActivationStatus($featureName, $merchantId, $status)
    {
        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content'] = [
            $featureName => [
                $merchantId => $status
            ]
        ];

        $testData['response']['content'] = [
            'success'       => 1,
            'failed'        => 0,
            'failed_ids'    => []
        ];

        $this->startTest($testData);
    }

    /**
     * Adds a feature as an admin [admin auth].
     * This function tests the route POST /features
     *
     * @param string      $addToMode
     * @param bool        $shouldSync
     * @param array       $featureNames
     * @param string|null $entityType
     * @param string|null $entityId
     */
    protected function addFeatures(
        string $addToMode,
        bool $shouldSync = false,
        array $featureNames = ['dummy'],
        string $entityType = 'merchant',
        string $entityId = null)
    {
        $this->ba->adminAuth($addToMode);

        $testData = $this->testData[__FUNCTION__];

        if (empty($featureNames) === false)
        {
            $testData['request']['content']['names'] = $featureNames;
        }

        if ($shouldSync !== false)
        {
            $testData['request']['content']['should_sync'] = 1;
        }

        $testData['request']['content']['entity_type'] = $entityType;

        if ($entityId !== null)
        {
            $testData['request']['content']['entity_id'] = $entityId;
        }

        $this->startTest($testData);
    }

    /**
     * Adds a feature to an account as a merchant/application [private auth]
     * This function tests the route: POST /accounts/id/features
     *
     * @param string      $addToMode
     * @param bool        $shouldSync
     * @param array       $featureNames
     * @param string      $entityId
     */
    protected function getDataToAddAccountFeatures(
        string $addToMode,
        bool $shouldSync,
        array $featureNames,
        string $entityId)
    {
        if ($addToMode === Mode::LIVE)
        {
            $this->ba->privateAuth(self::LIVE_AUTH_KEY);
        }
        else
        {
            $this->ba->privateAuth();
        }

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['names'] = $featureNames;

        $testData['request']['content']['should_sync'] = (int) $shouldSync;

        $testData['request']['url'] = '/accounts/me/features';

        $testData['response']['content'][0]['entity_id'] = $entityId;

        $testData['response']['content'][0]['name'] = $featureNames[0];

        $testData['response']['content'][0]['entity_type'] = Constants::MERCHANT;

        return $testData;
    }

    /**
     * This function tests fetching features through the route:
     * GET /features/entity_id which has been deprecated by GET /features/entity_type/id
     *
     * This function ensures Backward compatibility is maintained
     *
     * @param string $mode
     * @param array  $featureNames
     * @param string $merchantId
     * @deprecated by verifyFeaturePresenceForEntity
     */
    protected function verifyFeaturePresence(
        string $mode,
        array $featureNames = ['dummy'],
        string $merchantId = self::DEFAULT_MERCHANT_ID)
    {
        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/features/' . $merchantId;

        $this->ba->adminAuth($mode);

        $response = $this->startTest($testData);

        $assignedFeatures = array_map(function ($feature)
        {
            return $feature["name"];
        }, $response["assigned_features"]);

        $assignedFeaturesInResponse = array_intersect($assignedFeatures, $featureNames);

        // Check if all the featureNames requested, are present in the assignedFeatures array
        $this->assertEquals(count($featureNames), count($assignedFeaturesInResponse));
    }

    /**
     * Performs a GET request based on the mode received and verifies the
     * absence of the features received as arguments
     *
     * @todo: Update the route to the new route. Ref: verifyFeaturePresenceForEntity()
     *
     * @param string $mode
     * @param array  $featureNames
     */
    protected function verifyFeatureAbsence(
        string $mode,
        array $featureNames = ['dummy'])
    {
        $this->ba->adminAuth($mode);

        $response = $this->startTest();

        $assignedFeatures = array_map(function ($feature)
        {
            return $feature["name"];
        }, $response["assigned_features"]);

        $assignedFeaturesInResponse = array_intersect($assignedFeatures, $featureNames);

        $this->assertEquals(0, count($assignedFeaturesInResponse));
    }

    /**
     * This function tests fetching features through the route:
     * GET /features/entity_type/id which can be used only by the admins [admin auth]
     *
     * @param string $mode
     * @param string $entityType
     * @param string $entityId
     * @param array  $featureNames
     */
    protected function verifyFeaturePresenceForEntity(
        string $mode,
        string $entityType,
        string $entityId,
        array $featureNames)
    {
        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/features/'. $entityType . 's/' . $entityId;

        $this->ba->adminAuth($mode, null, 'org_100000razorpay');

        $response = $this->startTest($testData);

        $assignedFeatures = array_map(function ($feature)
        {
            return $feature["name"];
        }, $response["assigned_features"]);

        $assignedFeaturesInResponse = array_intersect($assignedFeatures, $featureNames);

        // Check if all the featureNames requested, are present in the assignedFeatures array
        $this->assertEquals(count($featureNames), count($assignedFeaturesInResponse));
    }

    /**
     * This function specifically tests fetching features through
     * the route: GET /accounts/id/features which can be used by
     * accounts/applications [private auth]
     *
     * @param string $mode
     * @param string $entityId
     * @param array  $featureNames
     */
    protected function verifyFeaturePresenceForAccounts(
        string $mode,
        string $entityId,
        array $featureNames)
    {
        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/accounts/me/features';

        if ($mode === Mode::LIVE)
        {
            $this->ba->privateAuth(self::LIVE_AUTH_KEY);
        }
        else
        {
            $this->ba->privateAuth();
        }

        $response = $this->startTest($testData);

        $assignedFeatures = array_map(function ($feature)
        {
            return $feature["name"];
        }, $response["assigned_features"]);

        $assignedFeaturesInResponse = array_intersect($assignedFeatures, $featureNames);

        // Check if all the featureNames requested, are present in the assignedFeatures array
        $this->assertEquals(count($featureNames), count($assignedFeaturesInResponse));
    }

    /**
     * Deletes a feature as an admin based on
     * the params received
     *
     * @param string $deleteFromMode
     * @param bool   $shouldSync
     * @param string $featureName
     * @deprecated by getDataToDeleteFeaturesFromEntity
     */
    protected function deleteFeature(
        string $deleteFromMode,
        bool $shouldSync = false,
        string $featureName = 'dummy')
    {
        $this->ba->adminAuth($deleteFromMode, null, 'org_100000razorpay');

        $testData = $this->testData[__FUNCTION__];

        if ($featureName !== null)
        {
            $testData['request']['url'] = '/features/' . self::DEFAULT_MERCHANT_ID . '/' . $featureName;
        }

        if ($shouldSync === true)
        {
            $testData['request']['content']['should_sync'] = 1;
        }

        $this->startTest($testData);
    }

    protected function getDataToDeleteFeaturesFromEntity(
        string $deleteFromMode,
        bool $shouldSync,
        string $featureName,
        string $entityType,
        string $entityId): array
    {
        $this->ba->adminAuth($deleteFromMode, null, 'org_100000razorpay');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/' . $entityType . 's/' . $entityId . '/features/' . $featureName;

        $testData['request']['content']['should_sync'] = (int) $shouldSync;

        return $testData;
    }


    public function testQueryCacheHitForFeature()
    {
        config(['app.query_cache.mock' => false]);

        Event::fake();

        $this->addFeatures(
            MODE::TEST,
            true,
            ['noflashcheckout'],
            Constants::MERCHANT,
            self::DEFAULT_MERCHANT_ID
        );

        //
        // Asserts that key is not present initially in cache
        //
        Event::assertDispatched(CacheMissed::class, function ($e)
        {
            foreach ($e->tags as $tag)
            {
                if (starts_with($tag, 'key') === true)
                {
                    $this->assertEquals('key_TheTestAuthKey', $tag);
                }
                else if (starts_with($tag, 'feature') === true)
                {
                    $this->assertEquals('feature_merchant_10000000000000', $tag);
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
                if (starts_with($tag, 'key') === true)
                {
                    $this->assertEquals('key_TheTestAuthKey', $tag);

                    $this->assertEquals('TheTestAuthKey', $e->value[0]->id);
                }
                else if (starts_with($tag, 'feature') === true)
                {
                    $this->assertEquals('feature_merchant_10000000000000', $tag);

                }
            }

            return true;
        });

        //
        // Asserts cache should not have been hit the first time
        //
        Event::assertNotDispatched(CacheHit::class);

        $this->verifyFeatureAbsence(Mode::TEST);
        //
        // Asserts that key is found in cache on subsequent attempts
        //
        Event::assertDispatched(CacheHit::class, function ($e)
        {
            foreach ($e->tags as $tag)
            {
                if (starts_with($tag, 'key') === true)
                {
                    $this->assertEquals('key_TheTestAuthKey', $tag);

                    $this->assertEquals('TheTestAuthKey', $e->value[0]->id);
                }
                else if (starts_with($tag, 'feature_merchant_10000000000000') === true)
                {
                    $this->assertStringContainsString(
                        implode(':', [
                                       CacheConstants::QUERY_CACHE_PREFIX,
                                       CacheConstants::DEFAULT_QUERY_CACHE_VERSION,
                                       Entity::FEATURE
                                   ]
                        ),
                        $e->key
                    );
                }
            }

            return true;
        });
    }

    public function testQueryCacheFlushForFeature()
    {
        config(['app.query_cache.mock' => false]);

        Event::fake(false);

        //
        // Rewrites value of key
        //
        $this->testAccountFeatures();

        //
        // Repeats the sequence of assertions, to test that new key is properly
        // read from cache
        //
        Event::assertDispatched(CacheMissed::class, function ($e)
        {
            foreach ($e->tags as $tag)
            {
                if (starts_with($tag, 'feature_names') === true)
                {
                    $this->assertEquals('feature_names_merchant_10000000000000', $tag);
                }

                if (starts_with($tag, 'feature_merchant') === true)
                {
                    $this->assertEquals('feature_merchant_10000000000000', $tag);
                }
            }

            return true;
        });

        Event::assertDispatched(KeyWritten::class, function ($e)
        {
            foreach ($e->tags as $tag)
            {
                if (starts_with($tag, 'feature_names_merchant_10000000000000') === true)
                {
                    $this->assertStringContainsString(
                        implode(':', [
                                       CacheConstants::QUERY_CACHE_PREFIX,
                                       CacheConstants::DEFAULT_QUERY_CACHE_VERSION,
                                       Entity::FEATURE
                                   ]
                        ),
                        $e->key
                    );
                }
            }

            return true;
        });
    }

    public function testQueryCacheHitForRoute()
    {
        config(['app.query_cache.mock' => false]);

        Event::fake(false);

        //
        // Cache miss on first access
        //
        $this->testDummyFeatureRouteWithoutAccess();

        //
        // Repeats the sequence of assertions, to test that new key is properly
        // read from cache
        //
        Event::assertDispatched(CacheMissed::class, function ($e)
        {
            foreach ($e->tags as $tag)
            {
                if (starts_with($tag, 'feature_names') === true)
                {
                    $this->assertEquals('feature_names_merchant_10000000000000', $tag);
                }
            }

            return true;
        });

        $this->testDummyFeatureRouteWithAccess();
        //
        // Asserts that key is found in cache on subsequent attempts
        //
        Event::assertDispatched(CacheHit::class, function ($e)
        {
            $hash = hash('sha256', implode('_', [Entity::FEATURE, Constants::MERCHANT, self::DEFAULT_MERCHANT_ID]));

            foreach ($e->tags as $tag)
            {
                if (starts_with($tag, 'key') === true)
                {
                    $this->assertEquals('key_TheTestAuthKey', $tag);

                    $this->assertEquals('TheTestAuthKey', $e->value[0]->id);
                }
                else if (starts_with($tag, 'feature_names_merchant_10000000000000') === true)
                {
                    $this->assertEquals(
                        implode(':', [
                                CacheConstants::QUERY_CACHE_PREFIX,
                                CacheConstants::DEFAULT_QUERY_CACHE_VERSION,
                                Entity::FEATURE,
                                $hash
                            ]
                        ),
                        $e->key
                    );
                }
            }

            return true;
        });
    }

    public function testAddFeatureSkipWorkflowPayoutSpecificAsMerchantTreatmentNotEnabled()
    {
        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $this->startTest();
    }

    public function testAddRestrictedFeatureSkipWFAtPayouts()
    {
        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $this->startTest();
    }

    public function testAddRestrictedFeatureSkipWFForPayroll()
    {
        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $this->startTest();
    }

    public function testAddRestrictedFeatureNewBankingError()
    {
        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $this->startTest();
    }

    public function testAddFeatureSkipWorkflowPayoutSpecificAsMerchantTreatmentEnabled()
    {
        $razorx = \Mockery::mock(RazorXClient::class)->makePartial();

        $this->app->instance('razorx', $razorx);

        $razorx->shouldReceive('getTreatment')
               ->andReturnUsing(function (string $id, string $featureFlag, string $mode)
               {
                   if ($featureFlag === (RazorxTreatment::SKIP_WORKFLOW_PAYOUT_SPECIFIC_FEATURE))
                   {
                       return 'on';
                   }
                   return 'control';
               });

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $this->startTest();
    }

    public function testAddSkipWorkflowPayoutSpecificFeatureToMerchant()
    {
        $razorx = \Mockery::mock(RazorXClient::class)->makePartial();

        $this->app->instance('razorx', $razorx);

        $razorx->shouldReceive('getTreatment')
            ->andReturnUsing(function (string $id, string $featureFlag, string $mode)
            {
                if ($featureFlag === (RazorxTreatment::SKIP_WORKFLOW_PAYOUT_SPECIFIC_FEATURE))
                {
                    return 'on';
                }
                return 'control';
            });

        $this->startTest();
    }

    /**
     * This function tests updating of merchant feature rx_block_report_download.
     */
    public function testAddMerchantRxBlockReportDownloadFeatureAdminAuth()
    {
        $this->ba->adminAuth(Mode::LIVE, null, 'org_100000razorpay');

        $this->startTest();
    }


    /**
     * This function tests updating of merchant feature rx_show_payout_source.
     */
    public function testAddMerchantRxShowPayoutSourceFeatureAdminAuth()
    {
        $this->ba->adminAuth(Mode::LIVE, null, 'org_100000razorpay');

        $this->startTest();
    }

    public function testP2pUpiFeature()
    {
        $this->startTest();
    }

    public function testDisableTpvFlowFeature()
    {
        $this->startTest();
    }

    public function testAddCovidFeatureRazorXOff()
    {
        $this->mockRazorxTreatmentV2(RazorxTreatment::COVID_19_DONATION_SHOW, 'off');

        $this->startTest();
    }

    public function testAddCovidFeatureForMerchantWithNoBusinessType()
    {
        $this->mockRazorxTreatmentV2(RazorxTreatment::COVID_19_DONATION_SHOW, 'on');

        $this->startTest();
    }

    public function testAddCovidFeatureNgoMerchant()
    {
        $this->fixtures->create('merchant_detail',[
            MerchantDetails::MERCHANT_ID => self::DEFAULT_MERCHANT_ID,
            MerchantDetails::BUSINESS_TYPE => '7'
        ]);

        $this->mockRazorxTreatmentV2(RazorxTreatment::COVID_19_DONATION_SHOW, 'on');

        $this->startTest();
    }

    public function testAddCovidFeatureForMerchant()
    {
        $this->mockRazorxTreatmentV2(RazorxTreatment::COVID_19_DONATION_SHOW, 'on');

        $this->fixtures->create('merchant_detail',[
            MerchantDetails::MERCHANT_ID => self::DEFAULT_MERCHANT_ID,
            MerchantDetails::BUSINESS_TYPE => '1'
        ]);

        $this->startTest();
    }

    public function testAddPayoutFeatureWhenAlreadyAssignedExceptionThrown()
    {
        $response = $this->invokeAddFeaturesWrapper(new \RZP\Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_ALREADY_ASSIGNED));

        $this->assertNull($response);
    }

    public function testAddPayoutFeatureWhenNonAlreadyAssignedExceptionThrown()
    {
        $this->expectException(\RZP\Exception\BadRequestException::class);

        $this->expectExceptionMessage(PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED);

        $this->invokeAddFeaturesWrapper(new \RZP\Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_UNAUTHORIZED));
    }

    public function testAddPayoutFeatureWhenServerExceptionThrown()
    {
        $this->expectException(\RZP\Exception\ServerErrorException::class);

        $this->expectExceptionMessage('Socket Creation Failed');

        $this->invokeAddFeaturesWrapper(new \RZP\Exception\ServerErrorException(
        'Socket Creation Failed',
        ErrorCode::SERVER_ERROR));
    }

    public function invokeAddFeaturesWrapper(\Throwable $exception)
    {
        $merchantActivateMock = $this->getMockBuilder(\RZP\Models\Merchant\Activate::class)
            ->setMethods(['addFeatures'])
            ->getMock();

        $merchantActivateMock->method('addFeatures')
            ->will($this->throwException($exception));

        $merchantActivateMockReflectionObj = new \ReflectionObject($merchantActivateMock);

        $method = $merchantActivateMockReflectionObj->getMethod('addFeatureWhileHandlingStaleRead');

        $featureParams = [
            \RZP\Models\Feature\Entity::ENTITY_ID   => self::DEFAULT_MERCHANT_ID,
            \RZP\Models\Feature\Entity::ENTITY_TYPE => 'merchant',
            \RZP\Models\Feature\Entity::NAMES       => ['payout'],
            \RZP\Models\Feature\Entity::SHOULD_SYNC => false
        ];

        $method->invoke($merchantActivateMock, $featureParams);
    }

    public function testMerchantsWithFeatures()
    {
        $this->fixtures->merchant->addFeatures(['raas']);
        $this->ba->appAuth();
        $this->startTest();
    }

    public function testFeatureStatus($featureToBeChecked = null): void
    {
        if(isset($featureToBeChecked) === false)
        {
            return;
        }

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $features = $response['features'];

        $this->assertContains($featureToBeChecked, $features);
    }

    /**
     * Get all features, check if disable collect consent feature is present with false
     * Enable the feature
     * Get all features and check if feature is present with true
     */
    public function testTokenisationCollectConsentFeatureFlag(): void
    {
        $featureToBeChecked = [
            'feature'       => 'disable_collect_consent',
            'value'         => false,
            'display_name'  => 'Disable tokenisation consent collection by Razorpay',
        ];

        $this->testFeatureStatus($featureToBeChecked);

        $this->updateFeatureAsMerchant(
            'add',
            Mode::LIVE,
            'disable_collect_consent'
        );

        $featureToBeChecked['value'] = true;

        $this->testFeatureStatus($featureToBeChecked);
    }

    public function testAcceptOnly3dsPayments()
    {
        $this->ba->adminAuth(Mode::LIVE, null, 'org_100000razorpay');

        $this->startTest();
    }

    public function testOrderReceiptUniqueFeatureFlag()
    {
        $this->fixtures->create('merchant', ['id' => '10000000000001']);

        $this->ba->hostedProxyAuth('rzp_test');

        $this->startTest();
    }

    public function testOrderReceiptUniqueFeatureFlagFailedInvalidMerchantId()
    {
        $this->ba->hostedProxyAuth('rzp_test');

        $this->startTest();
    }

    public function testMFNFeatureAddition()
    {
        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $this->fixtures->merchant->addFeatures(['payout','payouts_batch']);

        $this->ba->adminAuth();

        // Setting webhook URL for MFN,
        $this->makeRequestAndGetContent([
            'method'  => 'PUT',
            'url'     => '/config/keys',
            'content' => [
                Admin\ConfigKey::RX_WEBHOOK_URL_FOR_MFN => "https://razorpay.com",
            ],
        ]);

        $this->startTest();
    }

    public function testPayoutServiceFeatureAdditionWhenLedgerReverseShadowIsEnabled()
    {
        $this->fixtures->merchant->addFeatures(['ledger_reverse_shadow']);

        $this->ba->adminAuth(Mode::LIVE, null, 'org_100000razorpay');

        $this->startTest();
    }

    public function testLedgerReverseShadowFeatureAdditionWhenPayoutServiceFeatureIsEnabled()
    {
        $this->fixtures->merchant->addFeatures(['payout_service_enabled']);

        $this->ba->adminAuth(Mode::LIVE, null, 'org_100000razorpay');

        $this->startTest();
    }

    public function testPayoutServiceFeatureAdditionWhenLedgerJournalReadsIsEnabled()
    {
        $this->fixtures->merchant->addFeatures(['ledger_journal_reads']);

        $this->ba->adminAuth(Mode::LIVE, null, 'org_100000razorpay');

        $this->startTest();
    }

    public function testLedgerJournalReadsFeatureAdditionWhenPayoutServiceFeatureIsEnabled()
    {
        $this->fixtures->merchant->addFeatures(['payout_service_enabled']);

        $this->ba->adminAuth(Mode::LIVE, null, 'org_100000razorpay');

        $this->startTest();
    }

    public function testDualCheckoutFeature()
    {
        $this->ba->adminAuth(Mode::LIVE, null, 'org_100000razorpay');

        $this->startTest();
    }

    public function test1CCReportingTestFeature()
    {
        $this->ba->adminAuth(Mode::LIVE, null, 'org_100000razorpay');

        $this->startTest();
    }
}
