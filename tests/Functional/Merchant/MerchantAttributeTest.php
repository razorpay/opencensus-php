<?php

namespace Functional\Merchant;

use App;
use RZP\Constants\Product;
use RZP\Exception;
use RZP\Diag\EventCode;
use RZP\Models\Merchant;
use RZP\Services\DiagClient;
use RZP\Services\RazorXClient;
use RZP\Services\SalesForceClient;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Feature\Constants as Features;
use RZP\Tests\Functional\Fixtures\Entity\User;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;

class MerchantAttributeTest extends TestCase
{
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;
    use HeimdallTrait;

    const DEFAULT_MERCHANT_ID = '10000000000000';

    protected $repo;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantAttributeTestData.php';

        parent::setUp();

        $this->app = App::getFacadeRoot();
        $this->repo = $this->app['repo'];

        $this->setUpMerchantForBusinessBanking(false, 100000);
    }

    public function testSwitchProductScenario()
    {
        $user = (new User())->createUserForMerchant();

        $this->fixtures->edit('merchant',
            '10000000000000',
            ['activated' => false, 'business_banking' => false, 'category2' => 'school']);

        $this->fixtures->create('merchant_detail',
            [
                'merchant_id' => '10000000000000',
                'activation_status' => 'pending'
            ]);

//        $this->fixtures->create('terminal:bank_account_terminal_for_business_banking',
//            ['merchant_id' => '100000Razorpay']);

        // To create a virtual account we need to enable bank transfer
        $this->fixtures->edit('methods', '10000000000000', ['bank_transfer' => true]);

        $liveBankingAccount = $this->getDbEntity('banking_account',
            [
                'merchant_id' => '10000000000000',
            ],
            'live');

        $featuresArray = $this->getDbEntity('feature',
                                            [
                                                'entity_id' => '10000000000000',
                                                'entity_type' => 'merchant'
                                            ])->pluck('name')->toArray();

        $this->assertNotContains(Features::NEW_BANKING_ERROR, $featuresArray);

        $this->assertNull($liveBankingAccount);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id'], 'owner');

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $this->startTest();

        $featuresArray = $this->getDbEntity('feature',
                                            [
                                                'entity_id' => '10000000000000',
                                                'entity_type' => 'merchant'
                                            ])->pluck('name')->toArray();

        $this->assertContains(Features::NEW_BANKING_ERROR, $featuresArray);
    }

    public function testSignupScenario()
    {
        $this->ba->adminAuth();

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $this->startTest();
    }

    public function mockLumberjackEventTracked(string $methodName, bool &$methodCalled, array $eventCode)
    {
        $calledWithCorrectEventCode = function (array $eventData,
                                                Merchant\Entity $merchant = null,
                                                \Throwable $ex = null,
                                                array $customProperties = []) use(&$methodCalled, $eventCode)
        {
            if($eventData === $eventCode)
            {
                $methodCalled = true;
            }
            // return true for the mock object to consider the input valid
            return true;
        };

        $lumberjackMock = $this->getMockBuilder(DiagClient::class)
                               ->setConstructorArgs([$this->app])
                               ->setMethods([$methodName])
                               ->getMock();

        $this->app->instance('diag', $lumberjackMock);

        $lumberjackMock->expects($this->atLeastOnce())
                       ->method($methodName)
                       ->will($this->returnCallback($calledWithCorrectEventCode));
    }

    public function mockSalesforceEventTracked(string $methodName)
    {
        $salesforceClientMock = $this->getMockBuilder(SalesForceClient::class)
                                     ->setConstructorArgs([$this->app])
                                     ->setMethods([$methodName])
                                     ->getMock();

        $this->app->instance('salesforce', $salesforceClientMock);

        if (in_array($methodName, ['captureInterestOfPrimaryMerchantInBanking', 'sendPreSignupDetails']))
        {
            $salesforceClientMock->expects($this->exactly(1))
                                 ->method($methodName)
                                 ->will($this->returnCallback(function(Merchant\Entity $merchant){
                                            $merchantOnboardingCategory = $merchant->getBankingOnboardingCategory();

                                            $this->assertNotNull($merchantOnboardingCategory);
                                        }));
        }
        else
        {
            $salesforceClientMock->expects($this->exactly(1))
                                 ->method($methodName);
        }
    }

    public function testMerchantAddingNewPreferences()
    {
        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testPostPreferencesForDashboardSeenType()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testGetPreferencesForDashboardSeenType()
    {
        $this->createMerchantAttribute('10000000000000', 'banking', 'x_merchant_preferences', 'explore_dashboard_button_at_welcome_page_clicked', 'true');

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testMerchantPreferencesViaAdminAuth()
    {
        $this->ba->adminAuth();

        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => 'update_merchant_preference']);

        $role->permissions()->attach($perm->getId());

        $this->startTest();
    }

    public function testMerchantPreferencesBulkViaAdminAuth()
    {
        $this->ba->adminAuth();

        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => 'view_activation_form']);

        $role->permissions()->attach($perm->getId());

        $this->startTest();
    }

    public function testMerchantAddingNewPreferencesForIntent()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testMerchantAddingNewPreferencesForMob()
    {
        $this->ba->mobAppAuthForProxyRoutes();

        $this->startTest();

        $this->assertFeaturePresence(Features::CAPITAL_CARDS_ELIGIBLE);
    }

    public function testMerchantAddingNewPreferencesForIntentLos()
    {
        $this->ba->proxyAuth();

        $this->startTest();

        $this->assertFeaturePresence(Features::ALLOW_ES_AMAZON);

        $this->assertFeaturePresence(Features::CAPITAL_CARDS_ELIGIBLE);
    }

    public function testMerchantRemovingPreferencesForIntentLosExisting()
    {
        $this->ba->proxyAuth();

        $this->createMerchantAttribute(
            self::DEFAULT_MERCHANT_ID,
            'banking',
            'x_merchant_intent',
            'marketplace_is',
            'true');

        $this->createMerchantAttribute(
            self::DEFAULT_MERCHANT_ID,
            'banking',
            'x_merchant_intent',
            'corporate_cards',
            'true');

        $this->fixtures->create('feature', [
            'name'        => Features::ALLOW_ES_AMAZON,
            'entity_id'   => self::DEFAULT_MERCHANT_ID,
            'entity_type' => 'merchant',
        ]);

        $this->fixtures->create('feature', [
            'name'        => Features::CAPITAL_CARDS_ELIGIBLE,
            'entity_id'   => self::DEFAULT_MERCHANT_ID,
            'entity_type' => 'merchant',
        ]);

        $this->startTest();

        $this->assertFeaturePresence(Features::ALLOW_ES_AMAZON);

        $this->assertFeaturePresence(Features::CAPITAL_CARDS_ELIGIBLE);
    }

    public function testMerchantRemovingPreferencesForIntentLosNonExisting()
    {
        $this->ba->proxyAuth();

        $this->assertFeatureAbsence(Features::ALLOW_ES_AMAZON);

        $this->assertFeatureAbsence(Features::CAPITAL_CARDS_ELIGIBLE);

        $this->startTest();

        $this->assertFeatureAbsence(Features::ALLOW_ES_AMAZON);

        $this->assertFeatureAbsence(Features::CAPITAL_CARDS_ELIGIBLE);
    }

    public function testMerchantAddingNewPreferencesForSource()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testMerchantUpsertingPreferences()
    {
        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testMerchantUpsertingPreferencesForCa()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testMerchantUpsertingForXTransactions()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testMerchantUpsertingPreferencesForIntent()
    {
        $this->createMerchantAttribute('10000000000000', 'banking', 'x_merchant_intent', 'current_account', 'true');

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testMerchantUpsertingPreferencesForSource()
    {
        $this->createMerchantAttribute('10000000000000', 'banking', 'x_merchant_source', 'pg', 'true');

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testMerchantPreferencesWithWrongGroup()
    {
        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testMerchantPreferencesWithWrongType()
    {
        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testMerchantPreferencesWithWrongTypeForIntent()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testMerchantPreferencesWithWrongTypeForSource()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testMerchantPreferencesMissingType()
    {
        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testMerchantPreferencesMissingValue()
    {
        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testMerchantGetPreferencesByGroup()
    {
        $this->createMerchantAttribute('10000000000000', 'banking', 'x_merchant_preferences', 'business_category', 'School');
        $this->createMerchantAttribute('10000000000000', 'banking', 'x_merchant_preferences', 'nft_project', 'true');
        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testMerchantGetPreferencesByGroupForIntent()
    {
        $this->createMerchantAttribute('10000000000000', 'banking', 'x_merchant_intent', 'current_account', 'false');

        $this->createMerchantAttribute('10000000000000', 'banking', 'x_merchant_intent', 'tax_payments', 'true');

        $this->createMerchantAttribute('10000000000000', 'banking', 'x_merchant_intent', 'demo_onboarding', 'true');

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testMerchantGetPreferencesByGroupForIntentOrderByCreation()
    {
        $this->createMerchantAttribute('10000000000000', 'banking', 'x_merchant_intent', 'tax_payments', 'true');

        sleep(1);

        $this->createMerchantAttribute('10000000000000', 'banking', 'x_merchant_intent', 'current_account', 'false');

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testMerchantGetUndoPayoutPreferencesByGroup()
    {
        $this->createMerchantAttribute('10000000000000', 'banking', 'x_merchant_preferences', 'undo_payouts', 'true');
        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);
        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testMerchantGetPreferencesByGroupForSource()
    {
        $this->createMerchantAttribute('10000000000000', 'banking', 'x_merchant_source', 'pg', 'false');

        $this->createMerchantAttribute('10000000000000', 'banking', 'x_merchant_source', 'website', 'true');

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testMerchantGetPreferencesByGroupAndType()
    {
        $this->createMerchantAttribute('10000000000000', 'banking', 'x_merchant_preferences', 'business_category', 'School');
        $this->createMerchantAttribute('10000000000000', 'banking', 'x_merchant_preferences', 'monthly_payout_count', '1000');
        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);
        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testMerchantGetPreferencesByGroupAndTypeAdmin()
    {
        $this->createMerchantAttribute('10000000000000', 'banking', 'x_merchant_current_accounts', 'ca_allocated_bank', 'ICICI');
        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);
        $this->ba->adminAuth();
        $this->startTest();
    }

    public function createMerchantAttribute(string $merchant_id, string $product, string $group, string $type, string $value)
    {
        $this->fixtures->create('merchant_attribute',
            [
                'merchant_id'   => $merchant_id,
                'product'       => $product,
                'group'         => $group,
                'type'          => $type,
                'value'         => $value,
                'updated_at'    => time(),
                'created_at'    => time()
            ]);
    }

    protected function assertFeaturePresence(
        string $featureName,
        string $merchantId = self::DEFAULT_MERCHANT_ID)
    {
        $featuresArray = $this->getFeatures($merchantId);

        $this->assertContains($featureName, $featuresArray);
    }

    protected function assertFeatureAbsence(
        string $featureName,
        string $merchantId = self::DEFAULT_MERCHANT_ID)
    {
        $featuresArray = $this->getFeatures($merchantId);

        $this->assertNotContains($featureName, $featuresArray);
    }

    protected function getFeatures(string $merchantId)
    {
        return $this->getDbEntity(
            'feature',
            [
                'entity_id' => $merchantId,
                'entity_type' => 'merchant'
            ])->pluck('name')->toArray();
    }

    public function testOnboardMerchantOnNetworkBulkWithLimit()
    {
        $this->ba->cronAuth();

        $this->startTest();
    }

    public function testOnboardMerchantOnNetworkBulkWithArray()
    {
        $this->ba->cronAuth();

        $this->startTest();
    }

    protected function mockMozartForMasterCard()
    {
        $mozartServiceMock = $this->getMockBuilder(\RZP\Services\Mock\Mozart::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['sendMozartRequest'])
            ->getMock();

        $mozartServiceMock->method('sendMozartRequest')
            ->will($this->returnCallback(
                function ($namespace,$gateway,$action,$data)
                {
                    if($action === 'merchant_enrollment')
                    {
                        return [
                            'data' => [
                                "merchantData" =>[
                                    [
                                        "status"     => "Successful",
                                        "merchantID" => $data['merchantData']['merchantID'],
                                    ]
                                ]
                            ]
                        ];
                    }
                }
                ));

    }
}
