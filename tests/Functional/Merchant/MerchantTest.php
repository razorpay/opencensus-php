<?php

namespace RZP\Tests\Functional\Merchant;

use DB;
use App;
use Mail;
use Event;
use Redis;
use Crypt;
use Mockery;
use Carbon\Carbon;
use RZP\Services\Mock;
use RZP\Models\Comment;
use RZP\Models\User\Role;
use RZP\Models\Base\EsDao;
use RZP\Services\Mock\Raven;
use RZP\Services\UfhService;
use RZP\Error\PublicErrorCode;
use Functional\Helpers\BvsTrait;
use Illuminate\Http\UploadedFile;
use RZP\Models\BulkWorkflowAction;
use RZP\Jobs\FundAccountValidation;
use Illuminate\Hashing\BcryptHasher;
use Illuminate\Encryption\Encrypter;
use Illuminate\Cache\Events\CacheHit;
use RZP\Error\PublicErrorDescription;
use RZP\Models\BankAccount\Repository;
use RZP\Models\FundAccount\Validation;
use RZP\Mail\Merchant as MerchantMail;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Database\Eloquent\Factory;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\Workflow\Action;
use RZP\Models\RiskWorkflowAction\Constants;
use RZP\Models\Workflow\Action\Differ\Entity;
use RZP\Models\User\Constants as UserConstants;
use Rzp\Credcase\Migrate\V1\RotateApiKeyRequest;
use Rzp\Credcase\Migrate\V1\MigrateApiKeyRequest;
use RZP\Mail\Merchant\RejectionReasonNotification;
use RZP\Models\Workflow\Observer\EmailChangeObserver;
use RZP\Models\Admin\Org\Repository as OrgRepository;
use RZP\Services\Mock\DruidService as MockDruidService;
use RZP\Tests\Functional\Fixtures\Entity\Permission as PermissionEntity;
use RZP\Tests\Functional\Helpers\MocksRedisTrait;
use RZP\Models\Admin\Permission\Name as PermissionName;
use RZP\Models\Admin\Permission\Repository as PermissionRepo;
use RZP\Models\Merchant\Constants as MerchantConstants;
use RZP\Models\Merchant\Detail\Entity as MerchantDetails;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Models\Workflow\Observer\MerchantActionObserver;
use RZP\Tests\Functional\Helpers\Org\CustomBrandingTrait;
use RZP\Tests\Functional\Helpers\Freshdesk\FreshdeskTrait;
use RZP\Models\Merchant\Detail\Status as ActivationStatus;
use RZP\Models\Workflow\Observer\PaymentMethodChangeObserver;
use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;
use \RZP\Models\Workflow\Observer\Constants as ObserverConstants;
use RZP\Models\Key;
use RZP\Jobs\EsSync;
use RZP\Models\Admin;
use RZP\Models\Pricing;
use RZP\Constants\Mode;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Models\Settings;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Models\Transaction;
use RZP\Models\Card\Network;
use RZP\Models\BankingAccount;
use RZP\Services\DiagClient;
use RZP\Services\HubspotClient;
use RZP\Services\RazorXClient;
use RZP\Mail\User\MappedToAccount;
use RZP\Models\Settlement\Channel;
use RZP\Services\SalesForceClient;
use RZP\Tests\Functional\TestCase;
use RZP\Models\User\Core as UserCore;
use Illuminate\Support\Facades\Queue;
use RZP\Exception\BadRequestException;
use RZP\Mail\Merchant\EsEnabledNotify;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Models\Merchant\Document\Source;
use RZP\Models\User\Entity as UserEntity;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Fixtures\Entity\User;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Models\BankAccount\Entity as BankAccount;
use RZP\Tests\P2p\Service\Base\Traits\EventsTrait;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Balance\Entity as Balance;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Settlement\SettlementTrait;
use RZP\Mail\User\PasswordReset as PasswordResetMail;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\Helpers\Schedule\ScheduleTrait;
use RZP\Tests\Unit\Models\Invoice\Traits\CreatesInvoice;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Models\Merchant\Methods\Repository as MethodRepo;
use RZP\Mail\Banking\BeneficiaryFile as BeneficiaryFileMail;
use RZP\Mail\InstrumentRequest\StatusNotify as StatusNotifyMail;
use RZP\Mail\User\PasswordAndEmailReset as PasswordAndEmailResetMail;


use RZP\Exception\GatewayErrorException;
use RZP\Exception\GatewayTimeoutException;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Pricing\Repository as PricingRepo;
use RZP\Models\BulkWorkflowAction\Constants as BulkActionConstants;
use function foo\func;

class MerchantTest extends TestCase
{
    use PaymentTrait;
    use ScheduleTrait;
    use SettlementTrait;
    use InteractsWithSession;
    use HeimdallTrait;
    use DbEntityFetchTrait;
    use CreatesInvoice;
    use PartnerTrait;
    use WorkflowTrait;
    use TestsWebhookEvents;
    use EventsTrait;
    use TestsBusinessBanking;
    use CustomBrandingTrait;
    use MocksRedisTrait;
    use FreshdeskTrait;
    use BvsTrait;

    const CAPITAL_SUPPORT_EMAIL = 'capital.support@razorpay.com';

    const USER_ROLES_UNAUTHORIZED_TO_ENABLE_ES_SCHEDULED = ['operations',
                                                            'finance'];

    const USER_ROLES_AUTHORIZED_TO_ENABLE_ES_SCHEDULED = ['owner',
                                                            'admin'];

    const USER_ROLES_UNAUTHORIZED_TO_RECEIVE_ES_SCHEDULED_MAILS = ['support',
                                                                    'manager',
                                                                    'agent',
                                                                    'operations'];

    const REQUEST                                               = 'request';
    const RESPONSE                                              = 'response';
    const EDIT_MERCHANT_DETAILS                                 = 'edit_merchant_detail';
    const CREATE_MERCHANT_DETAILS                               = 'create_merchant_detail';
    const ACTIVATE_MERCHANT                                     = 'activate_merchant';

    const EMAIL_EXPECTED_WORKFLOW_ES_DATA_WITH_OBSERVER               = 'EMAIL_EXPECTED_WORKFLOW_ES_DATA_WITH_OBSERVER';

    const METHODS_EXPECTED_WORKFLOW_ES_DATA_WITH_OBSERVER             = 'METHODS_EXPECTED_WORKFLOW_ES_DATA_WITH_OBSERVER';

    const METHODS_EXPECTED_WORKFLOW_ES_DATA_WITHOUT_OBSERVER          = 'METHODS_EXPECTED_WORKFLOW_ES_DATA_WITHOUT_OBSERVER';

    const EDIT_EMAIL_EXPECTED_WORKFLOW_CREATE_WITH_OBSERVER_DATA_RESPONSE   = 'EDIT_EMAIL_EXPECTED_WORKFLOW_CREATE_WITH_OBSERVER_DATA_RESPONSE';

    const HOLD_FUNDS_WORKFLOW_CREATE_WITH_OBSERVER_DATA_RESPONSE    = 'HOLD_FUNDS_WORKFLOW_CREATE_WITH_OBSERVER_DATA_RESPONSE';

    const HOLD_FUNDS_WORKFLOW_ES_DATA_WITH_OBSERVER                 = 'HOLD_FUNDS_WORKFLOW_ES_DATA_WITH_OBSERVER';

    const RELEASE_FUNDS_WORKFLOW_CREATE_WITH_OBSERVER_DATA_RESPONSE    = 'RELEASE_FUNDS_WORKFLOW_CREATE_WITH_OBSERVER_DATA_RESPONSE';

    const RELEASE_FUNDS_WORKFLOW_ES_DATA_WITH_OBSERVER                 = 'RELEASE_FUNDS_WORKFLOW_ES_DATA_WITH_OBSERVER';

    protected $esDao;

    protected $esClient;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantTestData.php';

        parent::setUp();

        $factoryPath = base_path() . '/vendor/razorpay/oauth/database/factories';

        $this->fixtures->create('org:hdfc_org');

        $this->app->make(Factory::class)->load($factoryPath);

        $this->esDao = new EsDao();

        $this->esClient =  $this->esDao->getEsClient()->getClient();

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);
    }

    public function testMerchantSupportOptionDedupeMerchant()
    {
        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                              function ($merchantId, $feature, $mode)
                              {
                                  if ($feature === "DEDUPE_FUNCTIONALITY" || $feature === "show_create_ticket_popup")
                                  {
                                      return 'on';
                                  }
                                  else
                                  {
                                      return "control";
                                  }

                              }) );

        $this->ba->proxyAuth();

        $this->fixtures->create('merchant_detail', [
            'merchant_id'                   => '10000000000000',
            'activation_flow'               => 'whitelist',
            'business_category'             => 'education',
            'business_subcategory'          => 'alcohol',
            'business_type'                 => 2,
            'activation_status'             => 'needs_clarification',
            'submitted_at'                  => 1539543931,
        ]);

        $this->mockMerchantImpersonated();

        $this->startTest();
    }

    public function testMerchantSupportOptionOldFlow()
    {
        $this->ba->proxyAuth();

        $this->fixtures->create('merchant_detail', [
            'merchant_id'                   => '10000000000000',
            'activation_flow'               => 'whitelist',
            'business_category'             => 'education',
            'business_subcategory'          => 'alcohol',
            'business_type'                 => 2,
            'activation_status'             => 'needs_clarification',
            'submitted_at'                  => 1539543931,
        ]);

        $this->fixtures->merchant->edit('10000000000000', ['activated' => 1]);

        $this->startTest();
    }

    public function testIfMerchantOrgAxisCC()
    {
        $this->ba->privateAuth();

        $org = $this->fixtures->create('org');

        $this->fixtures->create('org_hostname', ['org_id' => $org->getId()]);

        $org = $this->getLastEntity('org', true);

        $this->fixtures->org->edit($org['id'], ['custom_code' => 'axis_cc']);

        $org = $this->getLastEntity('org', true);

        $orgId = trim($org['id'],"org_");

        $this->fixtures->merchant->edit('10000000000000',['org_id' => $orgId]);

        $merchant = (new Merchant\Repository)->findOrFail('10000000000000');

        $this->app['basicauth']->setMerchant($merchant);

        $this->assertTrue($this->app['basicauth']->authCreds->checkIfOrgAxisCC());
    }


    public function testCreateKey()
    {
        $this->createMerchant();

        $user = $this->fixtures->user->createUserForMerchant('1X4hRFHFx4UiXt');

        $this->ba->proxyAuth('rzp_test_1X4hRFHFx4UiXt', $user->getId());

        $res = $this->startTest();
        $this->assertRegexp('/rzp_test_\w{14}/', $res['id']);
        $this->assertRegexp('/\w{24}/', $res['secret']);

        // Assert insertion of api key
        $this->assertCount(2, $this->getDbEntities('key'), 'key present in database');

        // assert that key got encrypted using rzp key
        $keyFromDb = $this->getDbEntityById('key', $res['id']);
        $decryptedSecret = Crypt::decrypt($keyFromDb['secret']);
        $this->assertEquals($decryptedSecret, $res['secret']);
        $this->assertEquals($decryptedSecret, $keyFromDb->getDecryptedSecret());
    }

    // Test that when we create key for axis org, it should be encrypted using axis key as BYOK is enabled on axis org
    public function testCreateKeyForAxisOrgMerchantShouldUseAxisKeyForEncryption()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
        ->setConstructorArgs([$this->app])
        ->setMethods(['getTreatment'])
        ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
        ->will($this->returnCallback(
            function ($actionId, $feature, $mode)
            {
                return 'on';
            }) );

        $merchantId = '1X4hRFHFx4UiXt';

        $this->createMerchant();

        $user = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_1X4hRFHFx4UiXt', $user->getId());

        $orgId = MerchantEntity::AXIS_ORG_ID; // axis orgId

        $this->fixtures->create('org', ['id' => $orgId]);

        $this->fixtures->edit('merchant', $merchantId, [
            'org_id' => $orgId,
        ]);

        $res = $this->startTest();

        $keyFromDb = $this->getDbEntityById('key', $res['id']);

        // assert that key got encrypted using axis key
        $orgKey = '5dlTd5lQhN56CkSrnyrRBtRMsXS9exWS'; // ENCRYPTION_KEY_AXIS
        $newEncrypter = new Encrypter($orgKey, 'AES-256-CBC');
        $decryptedSecret = $newEncrypter->decrypt($keyFromDb['secret'], true);
        $this->assertEquals($decryptedSecret, $res['secret']);

        $this->assertEquals($decryptedSecret, $keyFromDb->getDecryptedSecret());
    }

    public function testCreateKeyForNonActivatedMerchant()
    {
        $this->createMerchant();

        $this->fixtures->merchant->setHasKeyAccess(true, '1X4hRFHFx4UiXt');

        $user = $this->fixtures->create('user');

        $this->createUserMerchantMapping($user['id'], '1X4hRFHFx4UiXt', 'owner', 'live');

        $this->ba->proxyAuth('rzp_live_1X4hRFHFx4UiXt', $user['id']);

        $this->startTest();

        // Asserts NO keys persisted in api's db.
        $this->assertCount(1, $this->getDbEntities('key', [], 'test'));
    }

    public function testGetMerchant()
    {
        $this->createMerchant();

        $this->ba->adminAuth();
        $this->startTest();

        $this->ba->adminAuth('live');
        $result = $this->startTest();

        $methods = $result['methods'];

        $this->assertArrayHasKey('payumoney', $methods);
        $this->assertArrayHasKey('card', $methods);
        $this->assertArrayHasKey('disabled_banks', $methods);
        $this->assertArrayHasKey('debit_card', $methods);
        $this->assertCount(43, $methods['disabled_banks']);
    }

    public function testGetMerchantDefaultDccMarkup()
    {
        $this->createMerchant();

        $this->ba->adminAuth();
        $result = $this->startTest();

        $this->assertNotNull($result['dcc_markup_percentage']);
        $this->assertEquals(6, $result['dcc_markup_percentage']);
    }

    public function testGetMerchantDccMarkup()
    {
        $this->fixtures->create('config', ['type' => 'dcc', 'is_default' => false,
            'config'     => '{
                "dcc_markup_percentage": 2
                }']);

        $this->ba->adminAuth();
        $this->startTest();
    }

    public function testGetMerchantDccMarkupWithMultipleConfigs()
    {
        $this->fixtures->create('config');

        $this->fixtures->create('config', ['type' => 'locale', 'is_default' => '1', 'config' => '{"language_code" : "hi"}']);

        $this->fixtures->create('config', ['type' => 'dcc',
            'config'     => '{
                "dcc_markup_percentage": 2.13
                }']);

        $this->ba->adminAuth();
        $this->startTest();
    }

    public function testSetDefaultUnclaimedGroupIdForCreateMerchant()
    {
        $this->createMerchant();

        $merchantMap = \DB::connection('test')->table('merchant_map')
                          ->where('merchant_id', '1X4hRFHFx4UiXt')
                          ->first();

        $this->assertEquals($merchantMap->entity_id, 'E15BhsdMSofcUJ');
        $this->assertEquals($merchantMap->entity_type, 'group');
        $this->assertEquals($merchantMap->merchant_id, '1X4hRFHFx4UiXt');
    }

    public function testGetMerchantUsers()
    {
        $merchant = $this->fixtures->create('merchant');

        $user1 = $this->fixtures->create('user');
        $user2 = $this->fixtures->create('user');

        $this->createUserMerchantMapping($user1['id'], $merchant['id'], 'owner');

        $this->createUserMerchantMapping($user2['id'], $merchant['id'], 'manager');

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $user1->getId());

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/merchants-users';

        $response = $this->makeRequestAndGetContent($testData['request']);

        $roles = array_column($response, 'role');

        $this->assertEquals(count($roles), 2);

        $this->assertTrue(in_array('owner', $roles));

        $this->assertTrue(in_array('manager', $roles));
    }

    public function testGetMerchantUsersInternal()
    {
        $merchant = $this->fixtures->create('merchant');

        $user1 = $this->fixtures->create('user');
        $user2 = $this->fixtures->create('user');

        $this->createUserMerchantMapping($user1['id'], $merchant['id'], 'owner');

        $this->createUserMerchantMapping($user2['id'], $merchant['id'], 'manager');

        $collectionsServiceConfig = \Config::get('applications.capital_collections_client');
        $pwd = $collectionsServiceConfig['secret'];
        $this->ba->appAuth('rzp_'.'test', $pwd);
        $this->ba->setAppAuthHeaders(['x-product-name' => 'primary']);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/merchants/' . $merchant['id'] . '/internal-users';

        $response = $this->makeRequestAndGetContent($testData['request']);

        $roles = array_column($response, 'role');

        $this->assertEquals(count($roles), 2);

        $this->assertTrue(in_array('owner', $roles));

        $this->assertTrue(in_array('manager', $roles));
    }

    public function testGetBalance()
    {
        // The merchant and balances have been created in
        // fixtures already
        $this->ba->proxyAuthTest();
        $this->startTest();

        $user = $this->fixtures->user->createUserForMerchant('10000000000000', [], 'owner', 'live');

        $this->ba->proxyAuth('rzp_live_10000000000000', $user->getId());

        $this->testData[__FUNCTION__]['response']['content']['balance'] = 0;

        $this->startTest();
    }

    public function testMerchantFetchKeys()
    {
        $this->ba->proxyAuthTest();

        $this->startTest();
    }

    public function testGetBadgeDetailsForRTBNotEnabled()
    {
        $this->setupRedisMockWithOptions();
        $this->ba->proxyAuthTest();
        $response = $this->startTest();

        $this->assertArrayKeysExist($response, ['rtb_details']);
        $this->assertEquals(false, $response['rtb_details']);
    }

    public function testGetBadgeDetailsForRTBEnabled()
    {
        $this->fixtures->create('feature', [
            'name'      => 'rzp_trusted_badge',
            'entity_id' => '10000000000000',
        ]);

        $this->setupRedisMockWithOptions();
        $this->ba->proxyAuthTest();
        $response = $this->startTest();

        $this->assertArrayKeysExist($response, ['rtb_details']);
        $this->assertEquals(true, $response['rtb_details']);
    }

    public function testGetBadgeDetailsForRTBEnabledCustomRedis()
    {
        $this->fixtures->create('feature', [
            'name'      => 'rzp_trusted_badge',
            'entity_id' => '10000000000000',
        ]);

        $override = [
            'get'     => null,
        ];

        $this->setupRedisMockWithOptions($override);
        $this->ba->proxyAuthTest();
        $response = $this->startTest();

        $this->assertArrayKeysExist($response, ['rtb_details']);
        $this->assertEquals(true, $response['rtb_details']);
    }

    public function testMerchantFetchCardEnabled()
    {
        $merchants = $this->getEntities(
            'merchant', ['methods' => '{"card":true}'], true);

        $this->assertEquals($merchants['entity'], 'collection');
    }

    /**
     * Updates a key
     */
    public function testUpdateKeyExpireNow()
    {
        $this->ba->proxyAuthTest();

        $content = $this->startTest();

        $expired = time() + 1;

        $this->assertLessThan($expired, $content['old']['expired_at']);
    }

    public function testUpdateKeyExpireInFuture()
    {
        $this->ba->proxyAuthTest();

        $content = $this->startTest();

        $expired = time() + 10;

        $this->assertGreaterThan($expired, $content['old']['expired_at']);
    }

    public function testUpdateKeyTwice()
    {
        $this->ba->proxyAuthTest();

        $data = $this->testData[__FUNCTION__];

        //
        // Update key once
        //
        $content = $this->makeRequestAndGetContent($data['request']);

        $expired = time() + 1;

        $this->assertLessThan($expired, $content['old']['expired_at']);

        //
        // Update the same key second time
        //
        $content = $this->startTest();
    }

    public function testRollDemoKey()
    {
        $this->createMerchant();

        $this->fixtures->create(
            'key',
            ['merchant_id' => '1cXSLlUU8V9sXl',
                'id' => '1DP5mmOlF5G5ag']);

        $this->ba->proxyAuthTest();

        $this->startTest();

        // 1. Asserts NO dual write to credcase.
        $httpClient = $this->app['credcase_http_client'];
        $this->assertCount(0, $httpClient->getRequests());
    }

    public function testEditMerchant()
    {
        $this->createMerchant();

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, 'org_'.$this->org->id);

        $result = $this->startTest();

        $this->assertArrayNotHasKey('groups', $result);
    }

    public function testEditMerchantWithNullFeeCreditsThreshold()
    {
        $this->createMerchant();

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, 'org_' . $this->org->id);

        $result = $this->startTest();

        $this->assertArrayNotHasKey('groups', $result);
    }

    public function testEditMerchantWithNullAmountCreditsThreshold()
    {
        $this->createMerchant();

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, 'org_' . $this->org->id);

        $result = $this->startTest();

        $this->assertArrayNotHasKey('groups', $result);
    }

    public function testEditMerchantWithNullRefundCreditsThreshold()
    {
        $this->createMerchant();

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, 'org_' . $this->org->id);

        $result = $this->startTest();

        $this->assertArrayNotHasKey('groups', $result);
    }

    public function testEditMerchantWithHighRiskThreshold()
    {
        $this->createMerchant();

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, 'org_' . $this->org->id);

        $result = $this->startTest();

        $this->assertArrayNotHasKey('groups', $result);
    }

    public function testSuspendMerchantBulk()
    {
        $this->createMerchantsForSuspendMerchantBulkTest();

        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => 'edit_merchant_suspend_bulk']);

        $role->permissions()->attach($perm->getId());

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testSuspendMerchantBulkWithoutPermissionFail()
    {
        $this->createMerchantsForSuspendMerchantBulkTest();
        $this->removePermission(PermissionName::EDIT_MERCHANT_SUSPEND_BULK);

        $this->ba->adminAuth();

        $this->startTest();
    }

    protected function createMerchantsForSuspendMerchantBulkTest()
    {
        $this->createMerchant([
            'id'    => '10000000000044',
            'email' => 'test1@razorpay.com',
        ]);

        $this->createMerchant([
            'id'    => '10000000000055',
            'email' => 'test2@razorpay.com',
        ]);
    }

    private function removePermission($permission)
    {
        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $perm = (new PermissionRepo())->fetch(['name'=>$permission]);

        $role->permissions()->detach($perm->firstOrFail()->getId());
    }

    public function testEditBulkMerchantAttributes()
    {
        $this->createMerchant([
            'id'    => '10000000000044',
            'email' => 'test1@razorpay.com',
        ]);

        $this->createMerchant([
            'id'    => '10000000000055',
            'email' => 'test2@razorpay.com',
        ]);

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('live');

        $this->startTest();

        $merchant1 = $this->getDbEntityById('merchant', '10000000000044');
        $merchant2 = $this->getDbEntityById('merchant', '10000000000055');

        foreach ([$merchant1, $merchant2] as $merchant)
        {
            $this->assertEquals(1, $merchant['hold_funds']);
            $this->assertEquals(['1.1.1.1', '2.2.2.2'], $merchant['whitelisted_ips_live']);
        }
    }

    public function testEditBulkMerchantAction()
    {
        $this->createMerchant([
            'id'    => '10000000000044',
            'email' => 'test1@razorpay.com',
        ]);

        $this->createMerchant([
            'id'    => '10000000000055',
            'email' => 'test2@razorpay.com',
        ]);

        $this->setAdminForInternalAuth();

        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => 'edit_merchant_hold_funds_bulk']);

        $role->permissions()->attach($perm->getId());

        $this->ba->adminAuth('live');

        $this->startTest();

        $merchant1 = $this->getDbEntityById('merchant', '10000000000044');
        $merchant2 = $this->getDbEntityById('merchant', '10000000000055');

        foreach ([$merchant1, $merchant2] as $merchant)
        {
            $this->assertEquals(1, $merchant['hold_funds']);
        }
    }

    public function testUfhSignedUrlAccessValidationForSupportRolePass()
    {
        $this->app['config']->set('applications.ufh.mock', true);

        $merchant = $this->fixtures->create('merchant');

        $user = $this->fixtures->create('user');

        $merchantId = $merchant['id'];

        $userID = $user['id'];

        $this->createMerchantUserMapping($userID, $merchantId, 'support');

        $this->setMockUfhServiceResponseForGetSignedUrl([
            'id'          => 'file_DM6dXJfU4WzeAFb',
            'type'        => 'report',
            'signed_url'  => 'http:://random-url',
        ]);

        $this->enableRazorXTreatmentForFeature(
            UfhService::RAZORX_FLAG_UFH_VALIDATE_USER_ROLE_FOR_ACCESS, 'on');

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $userID);

        $this->startTest();
    }

    public function testUfhSignedUrlAccessValidationForNonSupportRolePass()
    {
        $this->testData[__FUNCTION__] = $this->testData['testUfhSignedUrlAccessValidationForSupportRolePass'];

        $this->app['config']->set('applications.ufh.mock', true);

        $merchant = $this->fixtures->create('merchant');

        $user = $this->fixtures->create('user');

        $merchantId = $merchant['id'];

        $userID = $user['id'];

        $this->createMerchantUserMapping($userID, $merchantId, 'owner');

        $this->setMockUfhServiceResponseForGetSignedUrl([
            'id'          => 'file_DM6dXJfU4WzeAFb',
            'type'        => 'aadhar_card',
            'signed_url'  => 'http:://random-url',
        ]);

        $this->enableRazorXTreatmentForFeature(
            UfhService::RAZORX_FLAG_UFH_VALIDATE_USER_ROLE_FOR_ACCESS, 'on');

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $userID);

        $this->startTest();
    }

    public function testUfhSignedUrlAccessValidationForSupportRoleFail()
    {
        $this->app['config']->set('applications.ufh.mock', true);

        $merchant = $this->fixtures->create('merchant');

        $user = $this->fixtures->create('user');

        $merchantId = $merchant['id'];

        $userID = $user['id'];

        $this->createMerchantUserMapping($userID, $merchantId, 'support');

        $this->setMockUfhServiceResponseForGetSignedUrl(['type' => 'aadhar_back']);

        $this->enableRazorXTreatmentForFeature(
            UfhService::RAZORX_FLAG_UFH_VALIDATE_USER_ROLE_FOR_ACCESS, 'on');

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $userID);

        $this->startTest();
    }

    public function testFailedBulkMerchant()
    {
        $this->createMerchant([
            'id'    => '10000000000044',
            'email' => 'test1@razorpay.com',
        ]);

        $this->createMerchant([
            'id'    => '10000000000055',
            'email' => 'test2@razorpay.com',
        ]);

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('live');

        $this->startTest();
    }

    public function testMerchantRestricted2faEnable()
    {
        $ownerUser = $this->fixtures->create('user', [
            'second_factor_auth'      => 0,
            'contact_mobile'          => '9012345678',
            'contact_mobile_verified' => 1,
            UserEntity::PASSWORD      => 'hello123',
        ]);

        $merchantIds = $ownerUser->merchants()->distinct()->get()->pluck('id')->toArray();
        $merchant    = $this->getDbEntityById('merchant', $merchantIds[0]);

        $this->fixtures->merchant->edit($merchant['id'], [
            MerchantEntity::RESTRICTED          => true,
            MerchantEntity::SECOND_FACTOR_AUTH  => 0,
        ]);

        $user = $this->fixtures->create('user',[
            UserEntity::SECOND_FACTOR_AUTH      => 1,
            UserEntity::CONTACT_MOBILE_VERIFIED => 1,
            UserEntity::CONTACT_MOBILE          => '9801234567',
        ]);

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => $merchant['id'],
            'role'        => 'ops',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $testData = & $this->testData[__FUNCTION__];

        $content =  [
            MerchantEntity::SECOND_FACTOR_AUTH => 1,
            UserEntity::PASSWORD               => 'hello123',
        ];

        $testData['request']['content'] = $content;

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $ownerUser['id']);

        $res = $this->startTest();

        $ownerUserEntity = $this->getDbEntityById('user', $ownerUser['id']);
        $userEntity = $this->getDbEntityById('user', $user['id']);
        $merchantEntity = $this->getDbEntityById('merchant', $merchant['id']);

        $this->assertTrue($merchantEntity->isSecondFactorAuth());
        $this->assertFalse($ownerUserEntity->isSecondFactorAuth());
        $this->assertTrue($ownerUserEntity->isSecondFactorAuthEnforced());
        $this->assertTrue($userEntity->isSecondFactorAuth());
        $this->assertTrue($userEntity->isSecondFactorAuthEnforced());
    }

    public function testMerchant2faEnableMailForCustomBrandingOrg()
    {
        Mail::fake();

        $this->testData[__FUNCTION__] = $this->testData['testMerchant2faEnable'];

        $merchant = $this->fixtures->create('merchant', [
            MerchantEntity::SECOND_FACTOR_AUTH      => 0,
        ]);

        $user = $this->createUserForMerchantFor2Fa($merchant['id'], 0);

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $user['id']);

        $org = $this->createCustomBrandingOrgAndAssignMerchant($merchant['id']);

        $this->startTest();

        Mail::assertSent(MerchantMail\SecondFactorAuth::class, function ($mail) use ($org)
        {
            $viewData = $mail->viewData;

            $this->assertCustomBrandingMailViewData($org, $viewData);

            return true;
        });
    }

    public function testMerchant2faEnable()
    {
        Mail::fake();

        $merchant = $this->fixtures->create('merchant', [
            MerchantEntity::SECOND_FACTOR_AUTH      => 0,
        ]);

        $user = $this->createUserForMerchantFor2Fa($merchant['id'], 0);

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $user['id']);

        $this->startTest();

        Mail::assertSent(MerchantMail\SecondFactorAuth::class, function ($mail)
        {
            $viewData = $mail->viewData;

            $this->assertRazorpayOrgMailData($viewData);

            return true;
        });

        $userEntity = $this->getDbEntityById('user', $user['id']);
        $merchantEntity = $this->getDbEntityById('merchant', $merchant['id']);

        $this->assertTrue($merchantEntity->isSecondFactorAuth());
        $this->assertTrue($userEntity->isSecondFactorAuthEnforced());
    }

    public function testFailedMerchant2faEnableInvalidPass()
    {
        $merchant = $this->fixtures->create('merchant', [
            MerchantEntity::SECOND_FACTOR_AUTH      => 0,
        ]);

        $user = $this->fixtures->user->createUserForMerchant($merchant['id'], [
            UserEntity::SECOND_FACTOR_AUTH      => 0,
            UserEntity::CONTACT_MOBILE_VERIFIED => 1,
            UserEntity::CONTACT_MOBILE          => '9999999999',
            UserEntity::PASSWORD                => 'hello123',
        ], 'owner');

        $testData = & $this->testData[__FUNCTION__];

        $content =  [
            MerchantEntity::SECOND_FACTOR_AUTH => 1,
            UserEntity::PASSWORD               => 'hello1234',
        ];

        $testData['request']['content'] = $content;

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $user['id']);

        $this->startTest();

        $userEntity = $this->getDbEntityById('user', $user['id']);
        $merchantEntity = $this->getDbEntityById('merchant', $merchant['id']);

        $this->assertFalse($merchantEntity->isSecondFactorAuth());
        $this->assertFalse($userEntity->isSecondFactorAuthEnforced());
    }

    public function testMerchant2faEnableAsCriticalAction()
    {
        $merchant = $this->fixtures->create('merchant', [
            MerchantEntity::SECOND_FACTOR_AUTH      => 0,
        ]);

        $user = $this->fixtures->user->createUserForMerchant($merchant['id'], [
            UserEntity::SECOND_FACTOR_AUTH      => 0,
            UserEntity::CONTACT_MOBILE_VERIFIED => 1,
            UserEntity::CONTACT_MOBILE          => '9999999999',
            UserEntity::PASSWORD                => 'hello123',
        ], 'owner');

        $this->enableRazorXTreatmentForFeature(
            Merchant\RazorxTreatment::VALIDATE_USER_2FA_STATUS, 'on');

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $user['id']);

        $this->startTest();

        $userEntity = $this->getDbEntityById('user', $user['id']);
        $merchantEntity = $this->getDbEntityById('merchant', $merchant['id']);

        $this->assertTrue($merchantEntity->isSecondFactorAuth());
        $this->assertTrue($userEntity->isSecondFactorAuthEnforced());
    }

    public function testMerchant2faDisableAsCriticalAction()
    {
        $merchant = $this->fixtures->create('merchant', [
            MerchantEntity::SECOND_FACTOR_AUTH      => 1,
        ]);

        $user = $this->fixtures->user->createUserForMerchant($merchant['id'], [
            UserEntity::SECOND_FACTOR_AUTH      => 0,
            UserEntity::CONTACT_MOBILE_VERIFIED => 1,
            UserEntity::CONTACT_MOBILE          => '9999999999',
            UserEntity::PASSWORD                => 'hello123',
        ], 'owner');

        $this->enableRazorXTreatmentForFeature(
            Merchant\RazorxTreatment::VALIDATE_USER_2FA_STATUS, 'on');

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $user['id']);

        $this->startTest();

        $userEntity = $this->getDbEntityById('user', $user['id']);
        $merchantEntity = $this->getDbEntityById('merchant', $merchant['id']);

        $this->assertFalse($merchantEntity->isSecondFactorAuth());
        $this->assertFalse($userEntity->isSecondFactorAuthEnforced());
    }

    public function testMerchant2faDisable()
    {
        Mail::fake();

        $merchant = $this->fixtures->create('merchant', [
            MerchantEntity::SECOND_FACTOR_AUTH      => 1,
        ]);

        $user = $this->createUserForMerchantFor2Fa($merchant['id'], 1);

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $user['id']);

        $this->startTest();

        Mail::assertSent(MerchantMail\SecondFactorAuth::class, function ($mail)
        {
            $viewData = $mail->viewData;

            $this->assertRazorpayOrgMailData($viewData);

            return true;
        });

        $userEntity = $this->getDbEntityById('user', $user['id']);
        $merchantEntity = $this->getDbEntityById('merchant', $merchant['id']);

        $this->assertTrue($userEntity->isSecondFactorAuth());
        $this->assertFalse($merchantEntity->isSecondFactorAuth());
        $this->assertFalse($userEntity->isSecondFactorAuthEnforced());
    }

    public function testMerchant2faDisableMailForCustomBrandingOrg()
    {
        Mail::fake();

        $this->testData[__FUNCTION__] = $this->testData['testMerchant2faDisable'];

        $merchant = $this->fixtures->create('merchant', [
            MerchantEntity::SECOND_FACTOR_AUTH      => 1,
        ]);

        $user = $this->createUserForMerchantFor2Fa($merchant['id'], 1);

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $user['id']);

        $org = $this->createCustomBrandingOrgAndAssignMerchant($merchant['id']);

        $this->startTest();

        Mail::assertSent(MerchantMail\SecondFactorAuth::class, function ($mail) use ($org)
        {
            $viewData = $mail->viewData;

            $this->assertCustomBrandingMailViewData($org, $viewData);

            return true;
        });
    }

    protected function createUserForMerchantFor2Fa($merchantId, $secondFactorAuth = 1)
    {
        $user = $this->fixtures->user->createUserForMerchant($merchantId, [
            UserEntity::SECOND_FACTOR_AUTH      => $secondFactorAuth,
            UserEntity::CONTACT_MOBILE_VERIFIED => 1,
            UserEntity::CONTACT_MOBILE          => '9999999999',
            UserEntity::PASSWORD                => 'hello123',
        ], 'owner');

        return $user;
    }

    public function testFailedMerchantEnable2faMobNotPresent()
    {
        $ownerUser = $this->fixtures->create('user',[
            UserEntity::SECOND_FACTOR_AUTH      => 1,
            UserEntity::CONTACT_MOBILE_VERIFIED => 1,
            UserEntity::CONTACT_MOBILE          => null,
            UserEntity::PASSWORD                => 'hello123',
        ]);

        $merchant = $this->fixtures->create('merchant', [
            MerchantEntity::SECOND_FACTOR_AUTH => 0,
        ]);

        $mappingData = [
            'user_id'     => $ownerUser['id'],
            'merchant_id' => $merchant['id'],
            'role'        => 'owner',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $testData = & $this->testData[__FUNCTION__];

        $content =  [
            MerchantEntity::SECOND_FACTOR_AUTH => 1,
            UserEntity::PASSWORD               => 'hello123',
        ];

        $testData['request']['content'] = $content;

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $ownerUser['id']);

        $res = $this->startTest();

        $ownerUserEntity = $this->getDbEntityById('user', $ownerUser['id']);
        $merchantEntity = $this->getDbEntityById('merchant', $merchant['id']);

        $this->assertTrue($ownerUserEntity->isSecondFactorAuth());
        $this->assertFalse($merchantEntity->isSecondFactorAuth());
        $this->assertFalse($ownerUserEntity->isSecondFactorAuthEnforced());
    }

    public function testFailedMerchantEnable2faMobNotVerified()
    {
        $ownerUser = $this->fixtures->create('user',[
            UserEntity::SECOND_FACTOR_AUTH      => 1,
            UserEntity::CONTACT_MOBILE_VERIFIED => 0,
            UserEntity::CONTACT_MOBILE          => null,
            UserEntity::PASSWORD                => 'hello123',
        ]);

        $merchant = $this->fixtures->create('merchant', [
            MerchantEntity::SECOND_FACTOR_AUTH => 0,
        ]);

        $mappingData = [
            'user_id'     => $ownerUser['id'],
            'merchant_id' => $merchant['id'],
            'role'        => 'owner',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $testData = & $this->testData[__FUNCTION__];

        $content =  [
            MerchantEntity::SECOND_FACTOR_AUTH => 1,
            UserEntity::PASSWORD               => 'hello123',
        ];

        $testData['request']['content'] = $content;

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $ownerUser['id']);

        $res = $this->startTest();

        $ownerUserEntity = $this->getDbEntityById('user', $ownerUser['id']);
        $merchantEntity = $this->getDbEntityById('merchant', $merchant['id']);

        $this->assertTrue($ownerUserEntity->isSecondFactorAuth());
        $this->assertFalse($merchantEntity->isSecondFactorAuth());
        $this->assertFalse($ownerUserEntity->isSecondFactorAuthEnforced());
    }

    public function testFailedMerchantEnable2faNotOwner()
    {
        $merchant = $this->fixtures->create('merchant', [
            MerchantEntity::SECOND_FACTOR_AUTH => 0,
        ]);

        $ownerUser = $this->fixtures->create('user',[
            UserEntity::SECOND_FACTOR_AUTH      => 1,
            UserEntity::CONTACT_MOBILE_VERIFIED => 0,
            UserEntity::CONTACT_MOBILE          => null,
        ]);

        $user = $this->fixtures->create('user',[
            UserEntity::SECOND_FACTOR_AUTH      => 1,
            UserEntity::CONTACT_MOBILE_VERIFIED => 0,
            UserEntity::CONTACT_MOBILE          => null,
            UserEntity::PASSWORD                => 'hello123',
        ]);

        $mappingData = [
            'user_id'     => $ownerUser['id'],
            'merchant_id' => $merchant['id'],
            'role'        => 'owner',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => $merchant['id'],
            'role'        => 'admin',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $testData = & $this->testData[__FUNCTION__];

        $content =  [
            MerchantEntity::SECOND_FACTOR_AUTH => 1,
            UserEntity::PASSWORD               => 'hello123',
        ];

        $testData['request']['content'] = $content;

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $user['id']);

        $res = $this->startTest();

        $userEntity = $this->getDbEntityById('user', $user['id']);
        $merchantEntity = $this->getDbEntityById('merchant', $merchant['id']);

        $this->assertTrue($userEntity->isSecondFactorAuth());
        $this->assertFalse($merchantEntity->isSecondFactorAuth());
        $this->assertFalse($userEntity->isSecondFactorAuthEnforced());
    }

    // owner mob present but one of the user of the merchant doesn't have a verifiedd mobile
    public function testFailedMerchantRestricted2faEnableUserMobNotVerified()
    {
        $ownerUser = $this->fixtures->create('user', [
            'second_factor_auth'      => 0,
            'contact_mobile'          => '9012345678',
            'contact_mobile_verified' => 1,
            UserEntity::PASSWORD      => 'hello123',
        ]);

        $merchantIds = $ownerUser->merchants()->distinct()->get()->pluck('id')->toArray();
        $merchant    = $this->getDbEntityById('merchant', $merchantIds[0]);

        $this->fixtures->merchant->edit($merchant['id'], [
            MerchantEntity::RESTRICTED          => true,
            MerchantEntity::SECOND_FACTOR_AUTH  => 0,
        ]);

        $user = $this->fixtures->create('user',[
            UserEntity::SECOND_FACTOR_AUTH      => 1,
            UserEntity::CONTACT_MOBILE_VERIFIED => 0,
            UserEntity::CONTACT_MOBILE          => '9801234567',
        ]);

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => $merchant['id'],
            'role'        => 'ops',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $testData = & $this->testData[__FUNCTION__];

        $content =  [
            MerchantEntity::SECOND_FACTOR_AUTH => 1,
            UserEntity::PASSWORD               => 'hello123',
        ];

        $testData['request']['content'] = $content;

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $ownerUser['id']);

        $res = $this->startTest();

        $ownerUserEntity = $this->getDbEntityById('user', $ownerUser['id']);
        $userEntity = $this->getDbEntityById('user', $user['id']);
        $merchantEntity = $this->getDbEntityById('merchant', $merchant['id']);

        $this->assertFalse($merchantEntity->isSecondFactorAuth());
        $this->assertFalse($ownerUserEntity->isSecondFactorAuth());
        $this->assertFalse($ownerUserEntity->isSecondFactorAuthEnforced());
        $this->assertTrue($userEntity->isSecondFactorAuth());
        $this->assertFalse($userEntity->isSecondFactorAuthEnforced());
    }

    public function testEditMerchantEditGroups()
    {
        $merchant = $this->createMerchant();

        $org = $this->fixtures->create('org');

        $orgId = $org->getId();

        // --------------------------

        // create two groups for the org
        $groups = $this->fixtures->times(2)->create('group', ['org_id' => $orgId]);

        foreach ($groups as $group)
        {
            $groupIds[] = $group->getPublicId();
        }

        // create request to add groups to merchant
        $request = $this->testData[__FUNCTION__]['request'];

        $request['url'] = sprintf($request['url'], $merchant['id']);

        $request['content']['groups'] = $groupIds;

        $this->testData[__FUNCTION__]['request'] = $request;

        $this->ba->adminAuth();

        $response = $this->startTest();

        // list of created group ids
        $createdGroupIds = array_column($response['groups'], 'id');

        // check total created groups against request groups
        $this->assertEquals(count($groupIds), count($createdGroupIds));

        // check if group ids in request match as those in respose
        foreach ($groupIds as $groupId)
        {
            $this->assertContains($groupId, $createdGroupIds);
        }

        // --------------------------

        // Create another group
        $newGroup = $this->fixtures->create('group', ['org_id' => $orgId]);

        // Assign the new group, and one of older groups,
        // such that the other older group gets deleted
        $newGroupIds = [$newGroup->getPublicId(), $groupIds[0]];

        $request['content']['groups'] = $newGroupIds;

        $this->testData[__FUNCTION__]['request'] = $request;

        $response = $this->startTest();

        // list of new group ids
        $createdGroupIds = array_column($response['groups'], 'id');

        $this->assertEquals(count($newGroupIds), count($createdGroupIds));

        // check if group ids in request match as those in respose
        foreach ($newGroupIds as $groupId)
        {
            $this->assertContains($groupId, $createdGroupIds);
        }
    }

    public function testEditTransactionEmailWithCsv()
    {
        $this->createMerchant();

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testEditTransactionEmailWithError()
    {
        $this->createMerchant();

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testCorrectMerchantOwnerForBanking()
    {
        $merchant = $this->fixtures->create('merchant');

        $primaryOwner = $this->fixtures->create('user');

        $bankingOwner = $this->fixtures->create('user');

        $this->fixtures->on('live')->user->createUserMerchantMapping([
            'merchant_id' => $merchant->getId(),
            'user_id'     => $primaryOwner->getId(),
            'product'     => 'primary',
            'role'        => 'owner',
        ], 'live');

        $this->fixtures->on('live')->user->createUserMerchantMapping([
            'merchant_id' => $merchant->getId(),
            'user_id'     => $bankingOwner->getId(),
            'product'     => 'banking',
            'role'        => 'owner',
        ], 'live');

        $request = $this->testData[__FUNCTION__]['request'];

        $request['url'] =  '/merchants/'.$merchant->getId().'/correct_owner';

        $this->testData[__FUNCTION__]['request'] = $request;

        $this->ba->adminAuth('live');

        $this->startTest();

        $newBankingOwner = $merchant->primaryOwner('banking');

        $newPrimaryOwner = $merchant->primaryOwner('primary');

        $this->assertEquals($newBankingOwner->getId(), $newPrimaryOwner->getId());
    }

    public function testCorrectMerchantOwnerForBankingWithSameOwner()
    {
        $merchant = $this->fixtures->create('merchant');

        $owner = $this->fixtures->create('user');

        $this->fixtures->on('live')->user->createUserMerchantMapping([
            'merchant_id' => $merchant->getId(),
            'user_id'     => $owner->getId(),
            'product'     => 'primary',
            'role'        => 'owner',
        ], 'live');

        $this->fixtures->on('live')->user->createUserMerchantMapping([
            'merchant_id' => $merchant->getId(),
            'user_id'     => $owner->getId(),
            'product'     => 'banking',
            'role'        => 'owner',
        ], 'live');

        $request = $this->testData[__FUNCTION__]['request'];

        $request['url'] =  '/merchants/'.$merchant->getId().'/correct_owner';

        $this->testData[__FUNCTION__]['request'] = $request;

        $this->ba->adminAuth('live');

        $this->startTest();

        $newBankingOwner = $merchant->primaryOwner('banking');

        $newPrimaryOwner = $merchant->primaryOwner('primary');

        $this->assertEquals($newBankingOwner->getId(), $newPrimaryOwner->getId());
    }

    public function testCorrectMerchantOwnerForBankingWherePrimaryOwnerHasAdminRole()
    {
        $merchant = $this->fixtures->create('merchant');

        $primaryOwner = $this->fixtures->create('user');

        $bankingOwner = $this->fixtures->create('user');

        $this->fixtures->on('live')->user->createUserMerchantMapping([
            'merchant_id' => $merchant->getId(),
            'user_id'     => $primaryOwner->getId(),
            'product'     => 'primary',
            'role'        => 'owner',
        ], 'live');

        $this->fixtures->on('live')->user->createUserMerchantMapping([
            'merchant_id' => $merchant->getId(),
            'user_id'     => $primaryOwner->getId(),
            'product'     => 'banking',
            'role'        => 'admin',
        ], 'live');

        $this->fixtures->on('live')->user->createUserMerchantMapping([
            'merchant_id' => $merchant->getId(),
            'user_id'     => $bankingOwner->getId(),
            'product'     => 'banking',
            'role'        => 'owner',
        ], 'live');

        $request = $this->testData[__FUNCTION__]['request'];

        $request['url'] =  '/merchants/'.$merchant->getId().'/correct_owner';

        $this->testData[__FUNCTION__]['request'] = $request;

        $beforeCount = $merchant->users()->where('product', 'banking')
            ->where('role','!=','owner')
            ->where('id', $primaryOwner->getId())
            ->count();

        $this->assertEquals(1, $beforeCount);

        $this->ba->adminAuth('live');

        $this->startTest();

        $newBankingOwner = $merchant->primaryOwner('banking');

        $newPrimaryOwner = $merchant->primaryOwner('primary');

        $afterCount = $merchant->users()->where('product', 'banking')
            ->where('role','!=','owner')
            ->where('id', $primaryOwner->getId())
            ->count();

        $this->assertEquals(0, $afterCount);

        $this->assertEquals($newBankingOwner->getId(), $newPrimaryOwner->getId());
    }

    public function testEditMerchantEmail()
    {
        config(['app.query_cache.mock' => false]);

        $content = $this->createMerchant();

        $this->fixtures->user->createUserForMerchant($content['id'], ['email' => $content['email']]);

        $this->ba->adminAuth();

        Event::fake(false);

        $this->startTest();

        Event::assertDispatched(KeyForgotten::class, function ($e) use ($content)
        {
            $expectedTags = [
                'merchant_' . $content['id'],
            ];

            $this->assertArraySelectiveEquals($expectedTags, $e->tags);

            return true;
        });

        $merchant = (new Merchant\Repository)->findOrFail($content['id']);

        $this->assertEquals('shake@razorpay.com', $merchant->primaryOwner('primary')->getEmail());

        $this->assertNull($merchant->primaryOwner('banking'));
    }

    public function testEditMerchantEmailWhenOwnerExistsOnBothPgAndX()
    {
        config(['app.query_cache.mock' => false]);

        $content = $this->createMerchant();

        $user = $this->fixtures->user->createUserForMerchant($content['id'], ['email' => $content['email']]);

        $this->fixtures->user->createUserMerchantMapping([
            'user_id'     => $user['id'],
            'merchant_id' => $content['id'],
            'role'        => 'owner',
            'product'     => 'banking',
        ]);

        $this->ba->adminAuth();

        Event::fake(false);

        $this->startTest();

        Event::assertDispatched(KeyForgotten::class, function ($e) use ($content)
        {
            $expectedTags = [
                'merchant_' . $content['id'],
            ];

            $this->assertArraySelectiveEquals($expectedTags, $e->tags);

            return true;
        });

        $merchant = (new Merchant\Repository)->findOrFail($content['id']);

        $this->assertEquals('shake@razorpay.com', $merchant->primaryOwner('primary')->getEmail());

        $this->assertEquals('shake@razorpay.com', $merchant->primaryOwner('banking')->getEmail());
    }

    public function testHoldFundsWithUpdateObserverData()
    {
        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($actionId, $feature, $mode)
                {
                    return 'on';
                }) );


        $this->ba->adminAuth();

        $this->setupWorkflow('Hold Funds',PermissionName::$actionMap["hold_funds"], "test");

        $response = $this->startTest();

        $this->assertStringStartsWith('w_action_', $response['id']);

        $this->esClient->indices()->refresh();

        $this->updateObserverData($response['id'],  [
            'ticket_id'     => '123',
            'fd_instance'   => 'rzp'
        ]);

        $this->esClient->indices()->refresh();

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $this->setUpFreshdeskClientMock();

        $this->expectFreshdeskRequestAndRespondWith('tickets/123/reply', 'post',
            [
                'body' => implode("<br><br>",(new MerchantActionObserver(
                    [
                        Entity::PAYLOAD => [
                            "action" => 'hold_funds'
                        ],
                        Entity::ENTITY_ID => '10000000000000']))
                    ->getTicketReplyContent(ObserverConstants::APPROVE,'10000000000000')),
            ],
            []);

        $this->expectFreshdeskRequestAndRespondWith('tickets/123?include=requester', 'GET',
            [],
            [
                'id'        => '123',
                'tags'      => null
            ]);

        $this->expectFreshdeskRequestAndRespondWith('tickets/123', 'PUT',
            [
                'status'    => 4,
                'tags'      => ['automated_workflow_response']
            ],
            [
                'id'            => '123',
            ]);

        $this->performWorkflowAction($workflowAction['id'], true);

        $merchant = $this->getDbEntityById('merchant', '10000000000000');

        $this->assertEquals(true, $merchant->getHoldFunds());;

    }

    public function testReleaseFundsWithUpdateObserverData()
    {
        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($actionId, $feature, $mode)
                {
                    return 'on';

                }) );

        $this->ba->adminAuth();

        $this->fixtures->merchant->edit('10000000000000', ['hold_funds' => 1]);

        $this->setupWorkflow('Release Funds',PermissionName::$actionMap["release_funds"], "test");

        $response = $this->startTest();

        $this->assertStringStartsWith('w_action_', $response['id']);

        $this->esClient->indices()->refresh();

        $this->updateObserverData($response['id'],  [
            'ticket_id'     => '123',
            'fd_instance'   => 'rzp'
        ]);

        $this->esClient->indices()->refresh();

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $this->setUpFreshdeskClientMock();

        $this->expectFreshdeskRequestAndRespondWith('tickets/123/reply', 'post',
            [
                'body' => implode("<br><br>",(new MerchantActionObserver(
                    [
                        Entity::PAYLOAD => [
                            "action" => 'release_funds'
                        ],
                        Entity::ENTITY_ID => '10000000000000']))
                    ->getTicketReplyContent(ObserverConstants::APPROVE, '10000000000000')),
            ],
            []);

        $this->expectFreshdeskRequestAndRespondWith('tickets/123?include=requester', 'GET',
            [],
            [
                'id'        => '123',
                'tags'      => ['xyz']
            ]);

        $this->expectFreshdeskRequestAndRespondWith('tickets/123', 'PUT',
            [
                'status'    => 4,
                'tags'      => ['xyz','automated_workflow_response']
            ],
            [
                'id'            => '123',
            ]);

        $this->performWorkflowAction($workflowAction['id'], true);

        $merchant = $this->getDbEntityById('merchant', '10000000000000');

        $this->assertEquals(false, $merchant->isFundsOnHold());;

    }

    public function testEditMerchantEmailWithUpdateObserverData()
    {
        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($actionId, $feature, $mode)
                {
                    return 'on';

                }) );

        $this->fixtures->create('merchant',[
            'id'     => '10000000000044',
            'name'   => 'Submerchant',
            'org_id' => '100000razorpay',
            'email'  => 'test@razorpay.com',
        ]);

        $this->ba->adminAuth('live');

        Event::fake(false);

        $this->setupWorkflow('Edit Email',PermissionName::MERCHANT_EMAIL_EDIT);

        $response = $this->startTest();

        $this->assertStringStartsWith('w_action_', $response['id']);

        $this->assertArrayNotHaskey('observer_data', $response);

        $this->esClient->indices()->refresh();

        $this->updateObserverData($response['id'],  [
            'ticket_id'     => '123',
            'fd_instance'   => 'rzp'
        ],'live');

        $workflowAction = $this->getLastEntity('workflow_action', true,'live');

        $this->esClient->indices()->refresh();

        $this->setUpFreshdeskClientMock();

        $this->expectFreshdeskRequestAndRespondWith('tickets/123/reply', 'post',
            [
                'body' => implode("<br><br>",(new EmailChangeObserver([
                    Entity::ENTITY_ID => '10000000000044',
                ]))->getTicketReplyContent(ObserverConstants::APPROVE, '10000000000044')),
            ],
            [
            ]);

        $this->expectFreshdeskRequestAndRespondWith('tickets/123?include=requester', 'GET',
            [],
            [
                'id'        => '123',
                'tags'      => ['xyz']
            ]);

        $this->expectFreshdeskRequestAndRespondWith('tickets/123', 'PUT',
            [
                'status'    => 4,
                'tags'      => ['xyz','automated_workflow_response']
            ],
            [
                'id'            => '123',
            ]);

        $this->performWorkflowAction($workflowAction['id'], true,'live');

        $merchant = (new Merchant\Repository)->findOrFail('10000000000044');

        $this->assertEquals('shake@razorpay.com', $merchant->getEmail());
    }

    public function testEditMerchantEmailUserExists()
    {
        config(['app.query_cache.mock' => false]);

        $content = $this->createMerchant();

        $this->fixtures->user->createUserForMerchant($content['id'], ['email' => $content['email']]);

        $existingUser = $this->fixtures->user->create(['email' => 'newemail@razorpay.com']);

        $this->ba->adminAuth();

        Event::fake(false);

        $this->startTest();

        Event::assertDispatched(KeyForgotten::class, function ($e) use ($content)
        {
            $expectedTags = [
                'merchant_' . $content['id'],
            ];

            $this->assertArraySelectiveEquals($expectedTags, $e->tags);

            return true;
        });

        $merchant = (new Merchant\Repository)->findOrFail($content['id']);

        $this->assertEquals('newemail@razorpay.com', $merchant->primaryOwner()->getEmail());

        $this->assertNull($merchant->primaryOwner('banking'));
    }

    public function testEditMerchantEmailUserExistsAndOwnerExistsOnBothPgAndX()
    {
        config(['app.query_cache.mock' => false]);

        $content = $this->createMerchant();

        $user = $this->fixtures->user->createUserForMerchant($content['id'], ['email' => $content['email']]);

        $this->fixtures->user->createUserMerchantMapping([
            'user_id'     => $user['id'],
            'merchant_id' => $content['id'],
            'role'        => 'owner',
            'product'     => 'banking',
        ]);

        $existingUser = $this->fixtures->user->create(['email' => 'newemail@razorpay.com']);

        $this->ba->adminAuth();

        Event::fake(false);

        $this->startTest();

        Event::assertDispatched(KeyForgotten::class, function ($e) use ($content)
        {
            $expectedTags = [
                'merchant_' . $content['id'],
            ];

            $this->assertArraySelectiveEquals($expectedTags, $e->tags);

            return true;
        });

        $merchant = (new Merchant\Repository)->findOrFail($content['id']);

        $this->assertEquals('newemail@razorpay.com', $merchant->primaryOwner()->getEmail());

        $this->assertEquals('newemail@razorpay.com', $merchant->primaryOwner('banking')->getEmail());

        $merchantUsers = DB::connection('test')->table('merchant_users')
                                               ->where('merchant_id', $content['id'])
                                               ->where('user_id', $user['id'])
                                               ->pluck('role', 'product')
                                               ->toArray();

        $this->assertEquals(2, count($merchantUsers));

        $this->assertEquals('manager', $merchantUsers['primary']);

        $this->assertEquals('finance_l1', $merchantUsers['banking']);
    }

    public function testEditMerchantWhitelistedIpsLive()
    {
        $this->createMerchant();

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testEditMerchantInvalidWhitelistedIpsLive()
    {
        $this->createMerchant();

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testEditMerchantWhitelistedIpsTest()
    {
        $this->createMerchant();

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testEditMerchantInvalidWhitelistedIpsTest()
    {
        $this->createMerchant();

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testMerchantWhitelistedIpsLive()
    {
        $this->fixtures->merchant->edit('10000000000000', ['live' => true, 'activated' => 1]);

        $this->fixtures->merchant->editWhitelistedIpsLive('10000000000000', ['1.1.1.1','2.2.2.2']);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testMerchantFailedWhitelistedIpsLive()
    {
        $this->fixtures->merchant->edit('10000000000000', ['live' => true, 'activated' => 1]);

        $this->fixtures->merchant->editWhitelistedIpsLive('10000000000000', ['1.1.1.1','2.2.2.2']);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testMerchantWhitelistedIpsTest()
    {
        $this->fixtures->merchant->editWhitelistedIpsTest('10000000000000', ['1.1.1.1','2.2.2.2']);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testMerchantFailedWhitelistedIpsTest()
    {
        $this->fixtures->merchant->editWhitelistedIpsTest('10000000000000', ['1.1.1.1','2.2.2.2']);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testMerchantWhitelistedIpsMode()
    {
        $this->fixtures->merchant->editWhitelistedIpsTest('10000000000000', ['3.3.3.3','4.4.4.4']);

        $this->fixtures->merchant->editWhitelistedIpsLive('10000000000000', ['1.1.1.1','2.2.2.2']);

        $this->fixtures->merchant->edit('10000000000000', ['live' => true, 'activated' => 1]);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testEditMerchantUppercaseEmail()
    {
        $this->createMerchant();

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testEditMerchantEmptyEmail()
    {
        $this->createMerchant();

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testEditTestAccountMerchantEmail()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testEditMerchantConfig()
    {
        $this->createMerchant();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testEditMerchantConfigSellerAppRoleFail()
    {
        $merchant = $this->fixtures->create('merchant');

        $user = $this->fixtures->create('user');

        $merchantId = $merchant['id'];

        $userID = $user['id'];

        $this->createMerchantUserMapping($userID, $merchantId, 'sellerapp');

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $userID);

        $this->startTest();
    }

    public function testEditMerchantInvalidBrandColor()
    {
        $this->createMerchant();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testEditMerchantFeeCreditsThresholdWithProxyAuth()
    {
        $this->createMerchant();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testEditMerchantAmountCreditsThresholdWithProxyAuth()
    {
        $this->createMerchant();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testEditMerchantRefundCreditsThresholdWithProxyAuth()
    {
        $this->createMerchant();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testEditMerchantInvalidInvoiceNameField()
    {
        $this->createMerchant();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testEditMerchantInvalidAutoRefundDelay()
    {
        $this->createMerchant();

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testEditMerchantInvalidDurationAutoRefundDelay()
    {
        $this->createMerchant();

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testEditMerchantAutoRefundDelay()
    {
        $this->createMerchant();

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testEditMerchantDefaultRefundSpeed()
    {
        $this->createMerchant();

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testGetBillingLabelSuggestions()
    {
        $this->fixtures->create('merchant_detail', [
            'merchant_id'      =>  '10000000000000',
            'business_name'    =>  'Test Name Private Limited ltd ltd. Liability partnership',
            'business_website' =>  'https://shopify.secondleveldomain.edu.in'
        ]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testGetBillingLabelSuggestionsWithoutWebsite()
    {
        $this->fixtures->create('merchant_detail', [
            'merchant_id'   => '10000000000000',
            'business_name' => 'Test Name liability company pvt pvt. llp llp. llc llc. '
        ]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testGetBillingLabelSuggestionsWebsiteUrlWithSubdomain()
    {
        $this->fixtures->create('merchant_detail', [
            'merchant_id'      => '10000000000000',
            'business_website' => 'http://a.amazon.mywebsite.com'
        ]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testGetBillingLabelSuggestionsWebsiteNotInFormat()
    {
        $this->fixtures->create('merchant_detail', [
            'merchant_id'      =>  '10000000000000',
            'business_name'    =>  'Test Private Limited Name',
            'business_website' =>  'www.test.com'
        ]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testBillingLabelUpdateInSuggestions()
    {
        $this->fixtures->create('merchant_detail', [
            'merchant_id'      =>  '10000000000000',
            'business_name'    =>  'Test Name Private Limited',
            'business_website' =>  'https://www.test.org'
        ]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testBillingLabelUpdateMatchesWithWebsiteNotHavingPath()
    {
        $this->fixtures->create('merchant_detail', [
            'merchant_id'      =>  '10000000000000',
            'business_website' =>  'https://www.test.com'
        ]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testBillingLabelUpdateMatchesWithWebsiteHavingPath()
    {
        $this->fixtures->create('merchant_detail', [
            'merchant_id'      =>  '10000000000000',
            'business_website' =>  'https://test.com/anypath/abc/'
        ]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testBillingLabelUpdateMatchesWithWebsiteHavePlayStoreLinkFail()
    {
        $this->fixtures->create('merchant_detail', [
            'merchant_id'      =>  '10000000000000',
            'business_website' =>  'https://play.google.com/store/apps/details?id=com.beseller.apps',
            'business_name'    => 'abc test pvt ltd'
        ]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testBillingLabelUpdateMatchesWithBusinessName()
    {
        $this->fixtures->create('merchant_detail', [
            'merchant_id'      =>  '10000000000000',
            'business_name'    =>  'make my trip pvt ltd private Limited llp'
        ]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testBillingLabelUpdateInvalidValue()
    {
        $this->fixtures->create('merchant_detail', [
            'merchant_id'      =>  '10000000000000',
            'business_name'    =>  'Test Name Private Limited',
            'business_website' =>  'https://www.test.com'
        ]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testGetMerchantDataForSegment()
    {
        config(['services.druid.mock' => true]);

        $this->fixtures->create('merchant_detail', [
            'merchant_id'       => '10000000000000',
            'activation_status' => 'activated',
            'business_category' => 'ecommerce'
        ]);

        $merchant = $this->fixtures->edit('merchant','10000000000000', [
            'category'      => '5399',
            'activated_at'  => 1614921159
        ]);

        $this->fixtures->create(
            'payment:authorized',[
                'merchant_id' => $merchant['id'],
                'created_at'  => 1614921180,
            ]);

        $user = $this->fixtures->create('user');

        $merchantId = $merchant['id'];

        $userID = $user['id'];

        $this->createMerchantUserMapping($userID, $merchantId, 'owner');

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $userID);

        $druidService = $this->getMockBuilder(MockDruidService::class)
            ->setConstructorArgs([$this->app])
            ->setMethods([ 'getDataFromDruid'])
            ->getMock();

        $this->app->instance('druid.service', $druidService);

        $dataFromDruid = [
            'user_days_till_last_transaction' => 30,
            'merchant_lifetime_gmv'           => 100.0,
            'average_monthly_gmv'             => 10,
            'primary_product_used'            => 'payment_links',
            'ppc'                             => 1,
            'mtu'                             => true,
            'average_monthly_transactions'    => 3,
            'pg_only'                         => false,
            'pl_only'                         => true,
            'pp_only'                         => false,
        ];

        $druidService->method( 'getDataFromDruid')
            ->willReturn([null, [$dataFromDruid]]);

        $this->startTest();
    }

    public function testAddCategory2()
    {
        $this->createMerchant();

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testAddInvalidCategory2()
    {
        $this->createMerchant();

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testGetAccountConfig()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testEditMerchantConfigWithEmail()
    {
        $this->createMerchant();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testEditMerchantConfigWithDefaultRefundSpeed()
    {
        $this->createMerchant();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testMerchantUpdateKeyAccess()
    {
        $attribute = ['business_website' => 'https://www.example.com'];

        $merchantDetail = $this->fixtures->create('merchant_detail', $attribute);

        $merchantId = $merchantDetail['merchant_id'];

        $testData = & $this->testData[__FUNCTION__];

        $url = $testData['request']['url'];

        $url = sprintf($url, $merchantId);

        $testData['request']['url'] = $url;

        $this->ba->adminAuth('test', null, Org::RZP_ORG_SIGNED);

        $this->startTest();
    }

    public function testMerchantEnableLive()
    {
        $this->ba->adminAuth('live');

        $this->testMerchantDisableLive();

        $this->startTest();
    }

    public function testMerchantDisableLive()
    {
        $this->ba->adminAuth('live');

        $this->fixtures->edit('merchant', '1cXSLlUU8V9sXl', ['activated' => 1, 'live' => 1]);

        $this->ba->adminAuth('live');

        $this->startTest();
    }

    public function setAdminForInternalAuth()
    {
        $this->org = $this->fixtures->create('org');

        $this->addAssignablePermissionsToOrg($this->org);

        $this->authToken = $this->getAuthTokenForOrg($this->org);
    }

    public function testMerchantArchive()
    {
        $merchant = $this->getLastEntity('merchant', true);

        $merchantDetail = $this->fixtures->create('merchant_detail',
            [
                'merchant_id' => $merchant['id'],
                'submitted'   => true,
                'locked'      => true
            ]);

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, 'org_'.$this->org->id);

        $url = sprintf($this->testData[__FUNCTION__]['request']['url'], $merchant['id']);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        $merchant = $this->getEntityById('merchant', $merchant['id'], true);

        $this->assertNotNull($merchant['archived_at']);
    }

    public function testMerchantForceActivate()
    {
        $merchant = $this->getLastEntity('merchant', true);

        $this->fixtures->create('merchant_detail',
            [
                'merchant_id' => $merchant['id'],
                'submitted'   => true,
                'locked'      => true
            ]);

        $this->ba->adminAuth();

        $url = sprintf($this->testData[__FUNCTION__]['request']['url'], $merchant['id']);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        $merchant = $this->getEntityById('merchant', $merchant['id'], true);

        $this->assertNotNull($merchant['activated_at']);
    }

    public function testMerchantEditReceiptEmailEventCapture()
    {
        $merchant = $this->getLastEntity('merchant', true);

        $this->fixtures->create('merchant_detail',
            [
                'merchant_id' => $merchant['id'],
                'submitted'   => true,
                'locked'      => true
            ]);

        $this->ba->adminAuth();

        $url = sprintf($this->testData[__FUNCTION__]['request']['url'], $merchant['id']);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        $merchant = $this->getEntityById('merchant', $merchant['id'], true);

        $this->assertNotNull($merchant['receipt_email_trigger_event'], 'captured');
    }

    public function testMerchantEditReceiptEmailEventAuthorized()
    {
        $merchant = $this->getLastEntity('merchant', true);

        $this->fixtures->edit('merchant',  $merchant['id'], ['receipt_email_trigger_event' => 'captured']);

        $this->fixtures->create('merchant_detail',
            [
                'merchant_id' => $merchant['id'],
                'submitted'   => true,
                'locked'      => true
            ]);

        $this->ba->adminAuth();

        $url = sprintf($this->testData[__FUNCTION__]['request']['url'], $merchant['id']);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        $merchant = $this->getEntityById('merchant', $merchant['id'], true);

        $this->assertNotNull($merchant['receipt_email_trigger_event'], 'authorized');
    }

    public function testMerchantArchiveWithNoMerchantDetails()
    {
        $merchant = $this->getLastEntity('merchant', true);

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, 'org_'.$this->org->id);

        $url = sprintf($this->testData[__FUNCTION__]['request']['url'], $merchant['id']);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testMerchantArchiveForAlreadyArchivedMerchant()
    {
        $merchant = $this->getLastEntity('merchant', true);

        $this->fixtures->base->editEntity('merchant', $merchant['id'], [ 'archived_at' => '123456789' ]);

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, 'org_'.$this->org->id);

        $url = sprintf($this->testData[__FUNCTION__]['request']['url'], $merchant['id']);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testMerchantUnarchive()
    {
        $merchant = $this->getLastEntity('merchant', true);

        $this->fixtures->base->editEntity('merchant', $merchant['id'], [ 'archived_at' => '123456789' ]);

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, 'org_'.$this->org->id);

        $url = sprintf($this->testData[__FUNCTION__]['request']['url'], $merchant['id']);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        $merchant = $this->getEntityById('merchant', $merchant['id'], true);

        $this->assertNull($merchant['archived_at']);
    }

    public function testMerchantUnarchiveForNonArchived()
    {
        $merchant = $this->getLastEntity('merchant', true);

        $this->fixtures->base->editEntity('merchant', $merchant['id'], ['archived_at' => null]);

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, 'org_'.$this->org->id);

        $this->startTest();
    }

    public function testMerchantSuspend()
    {
        $merchant = $this->getLastEntity('merchant', true);

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, 'org_'.$this->org->id);

        $url = sprintf($this->testData[__FUNCTION__]['request']['url'], $merchant['id']);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->expectWebhookEvent('account.suspended');

        $this->startTest();

        $merchant = $this->getEntityById('merchant', $merchant['id'], true);

        $this->assertNotNull($merchant['suspended_at']);
    }

    public function testMerchantSuspendForAlreadySuspendedMerchant()
    {
        $merchant = $this->getLastEntity('merchant', true);

        $this->fixtures->base->editEntity('merchant', $merchant['id'], [ 'suspended_at' => '123456789' ]);

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, 'org_'.$this->org->id);

        $url = sprintf($this->testData[__FUNCTION__]['request']['url'], $merchant['id']);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testMerchantUnSuspend()
    {
        $merchant = $this->getLastEntity('merchant', true);

        $this->fixtures->base->editEntity('merchant', $merchant['id'], [ 'suspended_at' => '123456789' ]);

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, 'org_'.$this->org->id);

        $url = sprintf($this->testData[__FUNCTION__]['request']['url'], $merchant['id']);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        $merchant = $this->getEntityById('merchant', $merchant['id'], true);

        $this->assertNull($merchant['suspended_at']);
    }

    public function testMerchantUnSuspendForAlreadyUnSuspendedMerchant()
    {
        $merchant = $this->getLastEntity('merchant', true);

        $this->fixtures->base->editEntity('merchant', $merchant['id'], ['suspended_at' => null]);

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, 'org_'.$this->org->id);

        $this->startTest();
    }

    public function testMerchantUndefinedAction()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testAttemptPaymentOnNonLiveMerchant()
    {
        $this->testMerchantDisableLive();

        $key = $this->fixtures->create('key', ['merchant_id' => '1cXSLlUU8V9sXl']);
        $key = $key->getKey();

        $this->ba->publicAuth('rzp_live_'.$key);

        $this->startTest();
    }

    public function testAddBankAccount()
    {
        Mail::fake();

        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest();

        Mail::assertQueued(MerchantMail\MerchantDashboardEmail::class, function ($mail)
        {
            $this->assertEquals($mail->originProduct, 'primary');

            $testData = $this->testData['testAddBankAccount']['response']['content'];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            return true;
        });
    }

    public function testAddBankAccountWithInvalidBeneficiaryNameInjection()
    {
        Mail::fake();

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest();
    }

    public function testAddBankAccountWithInvalidBeneficiaryName()
    {
        Mail::fake();

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest();
    }

    public function testAddBankAccountWithAccountType()
    {
        Mail::fake();

        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest();

        Mail::assertQueued(MerchantMail\MerchantDashboardEmail::class, function ($mail)
        {
            $testData = $this->testData['testAddBankAccount']['response']['content'];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            return true;
        });
    }

    public function testAddBankAccountWithInvalidAccountType()
    {
        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest();
    }

    public function testAddBankAccountWithInvalidAccountNumber()
    {
        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest();
    }

    public function testAddBankAccountWithMerchantIdInURL()
    {
        Mail::fake();

        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest();

        Mail::assertQueued(MerchantMail\MerchantDashboardEmail::class, function ($mail)
        {
            $testData = $this->testData['testAddBankAccountWithMerchantIdInURL']['response']['content'];

            $testDataURL = $this->testData['testAddBankAccountWithMerchantIdInURL']['request']['url'];

            $testDataURLParts = explode('/',$testDataURL);

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->assertEquals($testData['merchant_id'],$mail->viewData['merchant_id']);

            $this->assertNotEquals($testDataURLParts[2],$mail->viewData['merchant_id']);

            return true;
        });
    }

    public function testAddBankAccountWithMerchantDetail()
    {
        Mail::fake();

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->fixtures->create('merchant_detail',
            [
                'merchant_id' => '10000000000000',
            ]);

        $this->startTest();

        Mail::assertQueued(MerchantMail\MerchantDashboardEmail::class, function ($mail) {
            $testData = $this->testData['testAddBankAccount']['response']['content'];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            return true;
        });

        $detail = $this->getLastEntity('merchant_detail', true);

        //
        // TODO:
        // - Fix and uncomment following
        //

        // $this->assertEquals('0002020000304030434', $detail['bank_account_number']);

        // $this->assertEquals('Test R4zorpay', $detail['bank_account_name']);

        // $this->assertEquals('ICIC0001206', $detail['bank_branch_ifsc']);
    }

    public function testAddBankAccountWithInvalidIfsc()
    {
        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest();
    }

    public function testGetBankAccountChangeStatus()
    {
        $merchantId = $this->setupMerchantForBankAccountUpdateTestViaPennyTesting(__FUNCTION__, true);

        $this->assertFalse($this->getBankAccountChangeStatusForMerchant($merchantId));
    }

    public function testGetBankAccountChangeStatusWorkflowInProgress()
    {
        $merchantId = $this->setupMerchantForBankAccountUpdateTestViaPennyTesting(__FUNCTION__, true);

        $this->setupWorkflowForBankAccountUpdate();

        $this->makeRequestAndGetContent([
            'content' => [
                'ifsc_code'        => 'ICIC0001206',
                'account_number'   => '0002020000304030434',
                'beneficiary_name' => 'Test R4zorpay:',
            ],
            'url'     => '/merchants/bank_account',
            'method'  => 'POST'
        ]);

        $this->assertTrue($this->getBankAccountChangeStatusForMerchant($merchantId));
    }

    public function testUpdateBankAccountAdminProxyAuth()
    {
        $merchantId = $this->setupMerchantForBankAccountUpdateTestViaPennyTesting(__FUNCTION__, true);

        $this->setupWorkflowForBankAccountUpdate();

        $admin = $this->ba->getAdmin();

        $this->fixtures->admin->edit($admin['id'], ['allow_all_merchants' => true]);

        $this->ba->adminProxyAuth($merchantId, 'rzp_test_' . $merchantId);

        $this->makeRequestAndGetContent([
            'content' => [
                'ifsc_code'        => 'ICIC0001206',
                'account_number'   => '0002020000304030434',
                'beneficiary_name' => 'Test R4zorpay:',
            ],
            'url'     => '/merchants/bank_account',
            'method'  => 'POST'
        ]);

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $this->esClient->indices()->refresh();

        $action = $this->esDao->searchByIndexTypeAndActionId('workflow_action_test_testing', 'action',
            substr($workflowAction['id'], 9))[0]['_source'];

        $this->assertEquals('admin', $action['maker_type']);
        $this->assertEquals($admin['id'], $action['maker_id']);
        $this->assertEquals('test admin', $action['maker']);
    }

    public function testUpdateBankAccountViaPennyTestingInAdminProxyAuth()
    {
        $merchantId = $this->setupMerchantForBankAccountUpdateTestViaPennyTesting(__FUNCTION__, true);

        $this->runBankAccountUpdateRequestTestInAdminProxyAuthAndAssert($merchantId);
    }

    public function testUpdateBankAccountViaPennyTestingInAdminProxyAuthFundsOnHoldPass()
    {
        $merchantId = $this->setupMerchantForBankAccountUpdateTestViaPennyTesting(__FUNCTION__, true);

        $this->fixtures->merchant->edit($merchantId, ['hold_funds' => 1]);

        $this->runBankAccountUpdateRequestTestInAdminProxyAuthAndAssert($merchantId);
    }

    public function testUpdateBankAccountViaPennyTestingInAdminProxyAuthOrgBlockedRoutePass()
    {
        $merchantId = $this->setupMerchantForBankAccountUpdateTestViaPennyTesting(__FUNCTION__, true);

        // create feature for org to block route access
        $this->fixtures->create('feature', [
            'name'        => 'bank_account_update_ss',
            'entity_id'   => '100000razorpay',
            'entity_type' => 'org'
        ]);

        $this->runBankAccountUpdateRequestTestInAdminProxyAuthAndAssert($merchantId);
    }

    protected function runBankAccountUpdateRequestTestInAdminProxyAuthAndAssert($merchantId)
    {
        Config(['services.bvs.mock' => true]);

        $beforeCount = $this->getBankAccountsCount($merchantId);

        $admin = $this->ba->getAdmin();

        $this->fixtures->admin->edit($admin['id'], ['allow_all_merchants' => true]);

        $this->ba->adminProxyAuth($merchantId, 'rzp_test_' . $merchantId);

        $this->makeRequestAndGetContent([
            'content' => [
                'ifsc_code'        => 'ICIC0001206',
                'account_number'   => '0000009999999999999',
                'beneficiary_name' => 'Test R4zorpay:',
            ],
            'url'     => '/merchants/bank_account/update',
            'method'  => 'POST'
        ]);

        $this->assertBankAccountForMerchant($merchantId, [
            'entity'            => 'bank_account',
            'ifsc'              => 'RZPB0000000',
            'account_number'    => '10010101011',
        ]);

        Mail::assertQueued(MerchantMail\MerchantDashboardEmail::class, function ($mail) {

            $this->assertEquals('emails.merchant.bankaccount_change_request', $mail->view);

            return true;
        });

        $afterCount = $this->getBankAccountsCount($merchantId);

        $this->assertEquals($beforeCount, $afterCount);

        $this->assertTrue($this->getBankAccountChangeStatusForMerchant($merchantId));
    }

    public function testBankAccountUpdateAdminProxyAuthCreateWorkflow()
    {
        $this->testData[__FUNCTION__] = $this->testData['testUpdateBankAccountViaPennyTesting'];

        Config(['services.bvs.mock' => true]);

        $this->setupWorkflowForBankAccountUpdate();

        $merchantId = $this->setupMerchantForBankAccountUpdateTestViaPennyTesting(__FUNCTION__, true, [
            'promoter_pan_name' => 'pan_name'
        ]);

        $admin = $this->ba->getAdmin();

        $this->fixtures->admin->edit($admin['id'], ['allow_all_merchants' => true]);

        $this->ba->adminProxyAuth($merchantId, 'rzp_test_' . $merchantId);

        $this->startTest();

        $bvsValidationEntity = $this->getDbLastEntity('bvs_validation')->toArray();

        $bvsResponse = $this->getBvsResponse($bvsValidationEntity['validation_id'], 'failed', 'NO_PROVIDER_ERROR');

        $this->processBvsResponse($bvsResponse);

        // as a workflow is created, assert bank account is not changed for the merchant still
        $this->assertBankAccountForMerchant($merchantId, [
            'entity'            => 'bank_account',
            'ifsc'              => 'RZPB0000000',
            'account_number'    => '10010101011',
        ]);

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $this->esClient->indices()->refresh();

        $action = $this->esDao->searchByIndexTypeAndActionId('workflow_action_test_testing', 'action',
            substr($workflowAction['id'], 9))[0]['_source'];

        $this->assertEquals($admin->getId(), $action['maker_id']);
        $this->assertEquals('admin', $action['maker_type']);
        $this->assertEquals($admin->toArray()['name'], $action['maker']);
    }

    public function testUpdateBankAccountWithAddressProof()
    {
        $documentType = 'address_proof_url';

        $this->fixtures->create('merchant_detail',
                                [
                                    'merchant_id' => '10000000000000',
                                ]);

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->updateUploadDocumentData(__FUNCTION__, $documentType);

        $this->startTest();

        $this->assertFalse($this->getBankAccountChangeStatusForMerchant('10000000000000'));
    }

    public function testUpdateBankAccountLavbShouldFail()
    {
        $merchantId = $this->setupMerchantForBankAccountUpdateTestViaPennyTesting(__FUNCTION__, true);

        $this->startTest();
    }

    public function testUpdateBankAccountViaPennyTesting()
    {
        Config(['services.bvs.mock' => true]);

        $merchantId = $this->setupMerchantForBankAccountUpdateTestViaPennyTesting(__FUNCTION__, true);

        $beforeCount = $this->getBankAccountsCount($merchantId);

        $this->startTest();

        $this->assertBankAccountForMerchant($merchantId, [
            'entity'            => 'bank_account',
            'ifsc'              => 'RZPB0000000',
            'account_number'    => '10010101011',
        ]);

        Mail::assertQueued(MerchantMail\MerchantDashboardEmail::class, function ($mail) {

            $this->assertEquals('emails.merchant.bankaccount_change_request', $mail->view);

            return true;
        });

        $afterCount = $this->getBankAccountsCount($merchantId);

        $this->assertEquals($beforeCount, $afterCount);

        $this->assertTrue($this->getBankAccountChangeStatusForMerchant($merchantId));
    }

    public function testUpdateBankAccountViaPennyTestingWithoutExistingBankAccountFail()
    {
        $merchantId = $this->setupMerchantForBankAccountUpdateTestViaPennyTesting(__FUNCTION__, false);

        $this->startTest();

        Mail::assertNotQueued(MerchantMail\MerchantDashboardEmail::class, function ($mail) {

            $this->assertEquals('emails.merchant.bankaccount_change_request', $mail->view);

            return true;
        });

        $this->assertFalse($this->getBankAccountChangeStatusForMerchant($merchantId));
    }

    public function testUpdateBankAccountViaPennyTestingFundsOnHoldFail()
    {
        $merchantId = $this->setupMerchantForBankAccountUpdateTestViaPennyTesting(__FUNCTION__, false);

        $this->fixtures->merchant->edit($merchantId, ['hold_funds' => 1]);

        $this->startTest();

        Mail::assertNotQueued(MerchantMail\MerchantDashboardEmail::class, function ($mail) {

            $this->assertEquals('emails.merchant.bankaccount_change_request', $mail->view);

            return true;
        });

        $this->assertFalse($this->getBankAccountChangeStatusForMerchant($merchantId));
    }

    public function testUpdateBankAccountViaPennyTestingAlreadyInProgressFail()
    {
        $this->testUpdateBankAccountViaPennyTesting(); // to trigger a bank account update request via penny testing

        $this->startTest();
    }

    public function testUpdateBankAccountPennyTestingEvent()
    {
        Config(['services.bvs.mock' => true]);

        $merchantId = $this->setupMerchantForBankAccountUpdateTestViaPennyTesting(__FUNCTION__, true);

        $this->fixtures->edit('merchant', $merchantId, [
            'org_id'    => Org::RZP_ORG,
        ]);

        $beforeCount = $this->getBankAccountsCount($merchantId);

        $this->testData[__FUNCTION__] = $this->testData['testUpdateBankAccountViaPennyTesting'];

        $this->startTest();

        $bvsValidationEntity = $this->getDbLastEntity('bvs_validation')->toArray();

        $bvsResponse = $this->getBvsResponse($bvsValidationEntity['validation_id'], 'success');

        $this->processBvsResponse($bvsResponse);

        $this->assertBankAccountForMerchant($merchantId, [
            'ifsc'             => 'ICIC0001206',
            'account_number'   => '0000009999999999999',
            'name'             => 'Test R4zorpay:',
        ]);

        $this->assertBankAccountUpdateRequestAndAccountChangedMailQueued();

        $afterCount = $this->getBankAccountsCount($merchantId);

        $this->assertEquals($beforeCount, $afterCount);

        $this->assertFalse($this->getBankAccountChangeStatusForMerchant($merchantId));
    }

    public function testUpdateBankAccountPennyTestingEventAccountChangeMailsForCustomBrandinOrg()
    {
        Config(['services.bvs.mock' => true]);

        $merchantId = $this->setupMerchantForBankAccountUpdateTestViaPennyTesting(__FUNCTION__, true);

        $org = $this->createCustomBrandingOrgAndAssignMerchant($merchantId);

        $this->testData[__FUNCTION__] = $this->testData['testUpdateBankAccountViaPennyTesting'];

        $this->startTest();

        $bvsValidationEntity = $this->getDbLastEntity('bvs_validation')->toArray();

        $bvsResponse = $this->getBvsResponse($bvsValidationEntity['validation_id'], 'success');

        $this->processBvsResponse($bvsResponse);

        $this->assertBankAccountForMerchant($merchantId, [
            'ifsc'             => 'ICIC0001206',
            'account_number'   => '0000009999999999999',
            'name'             => 'Test R4zorpay:',
        ]);

       $this->assertBankAccountUpdateRequestAndAccountChangedMailQueued($org);
    }

    public function testUpdateBankAccountPennyTestingEventNameMismatch()
    {
        Config(['services.bvs.mock' => true]);

        $this->testData[__FUNCTION__] = $this->testData['testUpdateBankAccountViaPennyTesting'];

        $this->setupWorkflowForBankAccountUpdate();

        $merchantId = $this->setupMerchantForBankAccountUpdateTestViaPennyTesting(__FUNCTION__, true);

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $oldBankAccount = $this->getDbLastEntity('bank_account', 'test')->toArrayAdmin();

        $this->startTest();

        $bvsValidationEntity = $this->getDbLastEntity('bvs_validation')->toArray();

        $bvsResponse = $this->getBvsResponse($bvsValidationEntity['validation_id'], 'failed', 'RULE_EXECUTION_FAILED');

        $this->processBvsResponse($bvsResponse);

        // as a workflow is created, assert bank account is not changed for the merchant still
        $this->assertBankAccountForMerchant($merchantId, [
            'entity'            => 'bank_account',
            'ifsc'              => 'RZPB0000000',
            'account_number'    => '10010101011',
        ]);

        Mail::assertNotQueued(MerchantMail\MerchantDashboardEmail::class, function ($mail)
        {
            if ($mail->view === 'emails.merchant.bankaccount_change')
            {
                return true;
            }

            return false;
        });

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $this->esClient->indices()->refresh();

        $action = $this->esDao->searchByIndexTypeAndActionId('workflow_action_test_testing', 'action',
            substr($workflowAction['id'], 9))[0]['_source'];


        // see comments in setupWorkflowForBankAccountUpdate for why we are asserting maker id
        $this->assertEquals($merchantId, $action['maker_id']);
        $this->assertEquals('merchant', $action['maker_type']);
        $this->assertEquals($merchant->toArray()['name'], $action['maker']);

        $this->assertEquals('open', $action['state']);
        $this->assertEquals( 'POST', $action['method']);
        $this->assertEquals('RZP\Http\Controllers\MerchantController@putBankAccountUpdatePostPennyTestingWorkflow', $action['controller']);
        $this->assertEquals('merchant_bank_account_update', $action['route']);
        $this->assertEquals('edit_merchant_bank_detail', $action['permission']);
        $this->assertArraySelectiveEquals( [
            'input'        => [
                'ifsc_code'         => 'ICIC0001206',
                'account_number'    => '0000009999999999999',
                'beneficiary_name'  => 'Test R4zorpay:',
                'address_proof_url' => '1cXSLlUU8V9sXl',
            ],
            'merchant_id'           => $merchantId,
            'new_bank_account_array'=> [
                'entity'           => 'bank_account',
                'ifsc'             => 'ICIC0001206',
                'account_number'   => '0000009999999999999',
                'name'             => 'Test R4zorpay:',
                'bank_name'        => 'ICICI Bank',
                'address_proof_url'=> '1cXSLlUU8V9sXl', // updateUploadDocumentData always creates a file with this value
                'notes'            => [],
            ],
            'old_bank_account_array'=> [
                'id'               => $oldBankAccount['id'],
                'entity'           => 'bank_account',
                'ifsc'             => 'RZPB0000000',
                'name'             => $oldBankAccount['name'],
                'bank_name'        => 'Razorpay',
                'account_number'   => '10010101011',
                'address_proof_url'=> 'old_address_proof_file_url',
                'notes'            => [],

            ],

        ], $action['payload']);
        $this->assertEquals([], $action['route_params']);


        $this->assertArraySelectiveEquals( [
            'old' => [
                'id'                    => $oldBankAccount['id'],
                'ifsc'                  => 'RZPB0000000',
                'name'                  => $oldBankAccount['name'],
                'bank_name'             => 'Razorpay',
                'account_number'        => '10010101011',
                'address_proof_url'     => 'old_address_proof_file_url',
            ],
            'new' => [
                'ifsc'             => 'ICIC0001206',
                'account_number'   => '0000009999999999999',
                'name'             => 'Test R4zorpay:',
                'bank_name'        => 'ICICI Bank',
                'address_proof_url'=> '1cXSLlUU8V9sXl', // updateUploadDocumentData always creates a file with this value
            ],
        ], $action['diff']);


        $this->assertTrue($this->getBankAccountChangeStatusForMerchant($merchantId));

        Mail::assertQueued(MerchantMail\MerchantDashboardEmail::class, function($mail) {
            return true;
        });

        $this->assertBankAccountUpdateRequestAndPennyTestingFailedMailQueued();
    }

    public function testAddCommentForBankAccountUpdateWorkflow()
    {
        $this->testData[__FUNCTION__] = $this->testData['testUpdateBankAccountViaPennyTesting'];

        Config(['services.bvs.mock' => true]);

        $this->setupWorkflowForBankAccountUpdate();

        $this->enableRazorXTreatmentForFeature(RazorxTreatment::DEDUPE_FUNCTIONALITY, 'on');

        $this->mockMerchantImpersonated();

        $merchantId = $this->setupMerchantForBankAccountUpdateTestViaPennyTesting(__FUNCTION__, true, [
            'promoter_pan_name' => 'pan_name'
        ]);

        $this->startTest();

        $bvsValidationEntity = $this->getDbLastEntity('bvs_validation')->toArray();

        $bvsResponse = $this->getBvsResponse($bvsValidationEntity['validation_id'], 'failed', 'NO_PROVIDER_ERROR');

        $this->processBvsResponse($bvsResponse);

        // as a workflow is created, assert bank account is not changed for the merchant still
        $this->assertBankAccountForMerchant($merchantId, [
            'entity'            => 'bank_account',
            'ifsc'              => 'RZPB0000000',
            'account_number'    => '10010101011',
        ]);

        $workflowAction = $this->getLastEntity('workflow_action', true);

        // get workflow action details in Admin Auth
        $this->ba->adminAuth('test');

        $request = [
            'method' => 'GET',
            'url'    => '/w-actions/' . $workflowAction['id'] . '/details',
            'content' => []
        ];

        $this->addPermissionToBaAdmin(PermissionName::VIEW_WORKFLOW_REQUESTS);

        $res = $this->makeRequestAndGetContent($request);

        $this->assertEquals($res['id'], $workflowAction['id']);

        $expectedComment1 = 'verification_status : failed, account_status : active, account_holder_names : name 1,name 2';

        $this->assertEquals($res['comments'][0]['comment'], $expectedComment1);

        $expectedComment2 = 'dedupe_status: true, matchedMIDs = {10000000000}';

        $this->assertEquals($res['comments'][0]['comment'], $expectedComment1);

        $this->assertEquals($res['comments'][1]['comment'], $expectedComment2);
    }


    public function testUpdateBankAccountPennyTestingFailWorkflowApprove()
    {
        Config(['services.bvs.mock' => true]);

        $this->testData[__FUNCTION__] = $this->testData['testUpdateBankAccountViaPennyTesting'];

        $this->enableRazorXTreatmentForFeature('whatsapp_notifications');

        $ravenMock = Mockery::mock('RZP\Services\Raven', [$this->app])->makePartial();

        $this->app->instance('raven', $ravenMock);

        $this->expectRavenSendSmsRequest($ravenMock,'sms.dashboard.bank_account_change_request', '1234567890');

        $this->expectRavenSendSmsRequest($ravenMock,'sms.dashboard.bank_account_change_penny_testing_failure', '1234567890');

        $this->expectRavenSendSmsRequest($ravenMock,'sms.dashboard.bank_account_change_successful', '1234567890');

        $this->setupWorkflowForBankAccountUpdate();

        $merchantId = $this->setupMerchantForBankAccountUpdateTestViaPennyTesting(__FUNCTION__, true);

        $this->mockStorkForBankAccountUpdate($merchantId);

        $beforeCount = $this->getBankAccountsCount($merchantId);

        $this->startTest();

        $bvsValidationEntity = $this->getDbLastEntity('bvs_validation')->toArray();

        $bvsResponse = $this->getBvsResponse($bvsValidationEntity['validation_id'], 'failed', 'NO_PROVIDER_ERROR');

        $this->processBvsResponse($bvsResponse);

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $this->esClient->indices()->refresh();

        $this->performWorkflowAction($workflowAction['id'], true);

        $this->assertBankAccountForMerchant($merchantId, [
            'ifsc'             => 'ICIC0001206',
            'account_number'   => '0000009999999999999',
            'name'             => 'Test R4zorpay:',
        ]);

        $this->assertBankAccountUpdateAllMailQueued();

        $afterCount = $this->getBankAccountsCount($merchantId);

        $this->assertEquals($beforeCount, $afterCount);

        $this->assertFalse($this->getBankAccountChangeStatusForMerchant($merchantId));
    }

    protected function mockStorkForBankAccountUpdate($merchantId)
    {
        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $this->expectStorkWhatsappRequest($storkMock,
            'We have received a request for changing the bank account for testname. The details for the request are as follows :
Beneficiary Name Test R4zorpay:
Account Number 0000009999999999999
IFSC Code ICIC0001206
We will update you once the changes have been approved.
-Team Razorpay',
            '1234567890',
            $merchantId
        );

        $this->expectStorkWhatsappRequest($storkMock,
            'Thank you for raising a request from your dashboard to update your bank account details.
We checked and see that the penny drop testing for the mentioned bank account has failed. Our experts are looking into this and will get back to you with an update within the next 24 hours.
-Team Razorpay',
            '1234567890',
            $merchantId
        );

        $this->expectStorkWhatsappRequest($storkMock,
            'Your Bank Account details have been updated successfully. The details are provided below.
Beneficiary Name Test R4zorpay:
Account Number 0000009999999999999
IFSC Code  ICIC0001206
-Team Razorpay',
            '1234567890',
            $merchantId
        );
    }

    protected function expectStorkWhatsappRequest($storkMock, $text, $destination = '9876543210', $ownerId = '10000000000000'): void
    {
        $storkMock->shouldReceive('sendWhatsappMessage')
            ->times(1)
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
                Mockery::on(function ($input) use ($ownerId)
                {
                    if ($input['ownerId'] !== $ownerId)
                    {
                        return false;
                    }

                    return true;
                }))
            ->andReturnUsing(function ()
            {
                $response = new \Requests_Response;

                $response->body = json_encode(['key' => 'value']);

                return $response;
            });
    }

    protected function expectRavenSendSmsRequest($ravenMock, $templateName, $receiver)
    {
        $ravenMock->shouldReceive('sendSms')
            ->times(1)
            ->with(
                Mockery::on(function ($actualPayload) use ($templateName, $receiver)
                {
                    if (($templateName !== $actualPayload['template']) or
                        ($receiver !== $actualPayload['receiver']))
                    {
                        return false;
                    }

                    return true;
                }),  Mockery::on(function ($mockInTestMode)
            {
                if ($mockInTestMode === true)
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

    protected function assertBankAccountUpdateRequestAndPennyTestingFailedMailQueued($org = null)
    {
        $accountChangeRequestMailCount = 0;

        $pennyTestingFailMailCount     = 0;

        Mail::assertQueued(MerchantMail\MerchantDashboardEmail::class, function ($mail) use ($org, & $accountChangeRequestMailCount, & $pennyTestingFailMailCount)
        {
            $viewData = $mail->viewData;

            $view = $mail->view;

            $this->assertTrue(in_array($view, ['emails.merchant.bankaccount_change_request', 'emails.merchant.bankaccount_change_penny_testing_failure']));

            if ($view === 'emails.merchant.bankaccount_change_request')
            {
                $accountChangeRequestMailCount = $accountChangeRequestMailCount + 1;
            }
            elseif ($view === 'emails.merchant.bankaccount_change_penny_testing_failure')
            {
                $pennyTestingFailMailCount = $pennyTestingFailMailCount + 1;
            }

            $this->assertOrgDataForBankAccountUpdateMail($org, $viewData);

            return true;
        });

        $this->assertEquals(1, $accountChangeRequestMailCount);

        $this->assertEquals(1, $pennyTestingFailMailCount);
    }

    protected function assertBankAccountUpdateRequestAndAccountChangedMailQueued($org = null)
    {
        $accountChangeRequestMailCount = 0;

        $accountChangedMailCount       = 0;

        Mail::assertQueued(MerchantMail\MerchantDashboardEmail::class, function ($mail) use ($org, & $accountChangeRequestMailCount, & $accountChangedMailCount)
        {
            $viewData = $mail->viewData;

            $view = $mail->view;

            $this->assertTrue(in_array($view, ['emails.merchant.bankaccount_change_request', 'emails.merchant.bankaccount_change']));

            if ($view === 'emails.merchant.bankaccount_change_request')
            {
                $accountChangeRequestMailCount = $accountChangeRequestMailCount + 1;
            }
            elseif ($view === 'emails.merchant.bankaccount_change')
            {
                $accountChangedMailCount = $accountChangedMailCount + 1;
            }

            $this->assertOrgDataForBankAccountUpdateMail($org, $viewData);

            return true;
        });

        $this->assertEquals(1, $accountChangedMailCount);

        $this->assertEquals(1, $accountChangeRequestMailCount);
    }

    protected function assertBankAccountUpdateAllMailQueued($org = null)
    {
        $accountChangeRequestMailCount = 0;

        $pennyTestingFailMailCount     = 0;

        $accountChangedMailCount       = 0;

        Mail::assertQueued(MerchantMail\MerchantDashboardEmail::class, function ($mail) use ($org, & $accountChangeRequestMailCount, & $pennyTestingFailMailCount ,& $accountChangedMailCount)
        {
            $viewData = $mail->viewData;

            $view = $mail->view;

            $this->assertTrue(in_array($view, ['emails.merchant.bankaccount_change_request', 'emails.merchant.bankaccount_change', 'emails.merchant.bankaccount_change_penny_testing_failure']));

            if ($view === 'emails.merchant.bankaccount_change_request')
            {
                $accountChangeRequestMailCount = $accountChangeRequestMailCount + 1;
                $this->assertEquals('testname', $viewData['name']);
                $this->assertEquals('Test R4zorpay:', $viewData['beneficiary_name']);
                $this->assertEquals('0000009999999999999', $viewData['account_number']);
                $this->assertEquals('ICIC0001206', $viewData['ifsc_code']);
            }
            elseif ($view === 'emails.merchant.bankaccount_change_penny_testing_failure')
            {
                $pennyTestingFailMailCount = $pennyTestingFailMailCount + 1;
            }
            elseif ($view === 'emails.merchant.bankaccount_change')
            {
                $accountChangedMailCount = $accountChangedMailCount + 1;
                $this->assertEquals('Test R4zorpay:', $viewData['beneficiary_name']);
                $this->assertEquals('0000009999999999999', $viewData['account_number']);
                $this->assertEquals('ICIC0001206', $viewData['ifsc_code']);
            }

            $this->assertOrgDataForBankAccountUpdateMail($org, $viewData);

            return true;
        });

        $this->assertEquals(1, $accountChangedMailCount);

        $this->assertEquals(1, $accountChangeRequestMailCount);

        $this->assertEquals(1, $pennyTestingFailMailCount);
    }

    protected function assertOrgDataForBankAccountUpdateMail($org, $viewData)
    {
        if (is_null($org) === true)
        {
            $this->assertRazorpayOrgMailData($viewData);
        }
        else
        {
            $this->assertCustomBrandingMailViewData($org, $viewData);
        }
    }

    public function testUpdateBankAccountPennyTestingFailMailForCustomBrandingOrg()
    {
        Config(['services.bvs.mock' => true]);

        $this->testData[__FUNCTION__] = $this->testData['testUpdateBankAccountViaPennyTesting'];

        $this->setupWorkflowForBankAccountUpdate();

        $merchantId = $this->setupMerchantForBankAccountUpdateTestViaPennyTesting(__FUNCTION__, true);

        $org = $this->createCustomBrandingOrgAndAssignMerchant($merchantId);

        $this->startTest();

        $bvsValidationEntity = $this->getDbLastEntity('bvs_validation')->toArray();

        $bvsResponse = $this->getBvsResponse($bvsValidationEntity['validation_id'], 'failed', 'NO_PROVIDER_ERROR');

        $this->processBvsResponse($bvsResponse);

        $this->assertBankAccountUpdateRequestAndPennyTestingFailedMailQueued($org);
    }

    public function testUpdateBankAccountPennyTestingFailWorkflowReject()
    {
        Config(['services.bvs.mock' => true]);

        $this->setupWorkflowForBankAccountUpdate();

        $merchantId = $this->setupMerchantForBankAccountUpdateTestViaPennyTesting(__FUNCTION__, true);

        $beforeCount = $this->getBankAccountsCount($merchantId);

        $this->testData[__FUNCTION__] = $this->testData['testUpdateBankAccountViaPennyTesting'];

        $this->startTest();

        $bvsValidationEntity = $this->getDbLastEntity('bvs_validation')->toArray();

        $bvsResponse = $this->getBvsResponse($bvsValidationEntity['validation_id'], 'failed', 'NO_PROVIDER_ERROR');

        $this->processBvsResponse($bvsResponse);

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $this->esClient->indices()->refresh();

        $this->performWorkflowAction($workflowAction['id'], false);

        $this->assertBankAccountForMerchant($merchantId, [
            'entity'            => 'bank_account',
            'ifsc'              => 'RZPB0000000',
            'account_number'    => '10010101011',
        ]);

        Mail::assertNotQueued(MerchantMail\MerchantDashboardEmail::class, function ($mail) {

            if ($mail->view === 'emails.merchant.bankaccount_change')
            {
                return true;
            }

            return false;
        });

        $afterCount = $this->getBankAccountsCount($merchantId);

        $this->assertEquals($beforeCount, $afterCount);

        $this->assertFalse($this->getBankAccountChangeStatusForMerchant($merchantId));
    }

    public function updateUploadDocumentData(string $callee, string $documentType)
    {
        $testData = &$this->testData[$callee];

        $testData['request']['files'][$documentType] = new UploadedFile(
            __DIR__ . '/../Storage/a.png',
            'a.png',
            'image/png',
            filesize(__DIR__ . '/../Storage/a.png'),
            null,
            true);
    }

    public function testUpdateBankAccountWithAddressProofUsingUFH()
    {
        $this->testUpdateBankAccountWithAddressProof();

        $merchantDocumentEntry = $this->getLastEntity('merchant_document', true, 'test');

        $this->assertEquals($merchantDocumentEntry['source'], Source::UFH);
    }

    public function testGetBankAccount()
    {
        $this->testAddBankAccount();

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testChangeBankAccount()
    {
        $this->markTestSkipped('Change bank account is breaking for now');

        $this->testAddBankAccount();

        $this->startTest();

        $bankAccounts = $this->getEntities(
            'bank_account', ['deleted' => true, 'type' => 'merchant'], true);

        // The old account should get deleted (hard delete) as there are
        // no settlements attached to it.
        $this->assertEquals(1, $bankAccounts['count']);
    }

    public function testChangeBankAccountWithZeroes()
    {
        $this->markTestSkipped('Change bank account is breaking for now');

        $this->testAddBankAccount();

        $content = $this->startTest();

        $bankAccounts = $this->getEntities(
            'bank_account', ['deleted' => true, 'type' => 'merchant'], true);

        // The old account should get deleted (hard delete) as there are
        // no settlements attached to it.
        $this->assertEquals(1, $bankAccounts['count']);
        $this->assertEquals('2020000304030434', $bankAccounts['items'][0]['account_number']);
    }

    public function testChangeBankAccountWithSettlement()
    {
        $this->markTestSkipped('Change bank account is breaking for now');

        $this->testAddBankAccount();

        $createdAt = Carbon::today(Timezone::IST)->subDays(5)->timestamp + 5;
        $capturedAt = Carbon::today(Timezone::IST)->subDays(5)->timestamp + 10;

        $capturedPayments = $this->fixtures->times(4)->create(
            'payment:captured',
            ['captured_at' => $capturedAt,
                'created_at' => $createdAt,
                'updated_at' => $createdAt + 10]);

        $settleAtTimestamp = (new Transaction\Core)->calculateSettledAtTimestamp($capturedAt, 3) + 1;

        $this->initiateSettlements('axis', $settleAtTimestamp);

        $testData = & $this->testData['testChangeBankAccount'];
        $this->runRequestResponseFlow($testData);

        $bankAccounts = $this->getEntities(
            'bank_account', ['deleted' => true, 'type' => 'merchant'], true);

        // The old account should get SOFT deleted as there are settlements
        // attached to it.
        $this->assertEquals(2, $bankAccounts['count']);
    }

    public function testSetBanks()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testSetEmptyBanks()
    {
        $this->ba->adminAuth();

        $content = $this->startTest();

        $this->assertSame([], $content['enabled']);
    }

    public function testGetBanksByMerchantAuth()
    {
        $this->ba->publicTestAuth();

        $this->startTest();

        $this->fixtures->merchant->activate('10000000000000');

        $this->ba->publicLiveAuth();

        $this->startTest();
    }

    public function testGetBanksByAdminAuth()
    {
        $this->testSetBanks();

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testGetPaymentMethodsRoute()
    {
        $this->ba->publicLiveAuth();

        $this->fixtures->merchant->activate('10000000000000');

        $attributes = array(
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'axis_genius',
            'card'                      => 1,
            'gateway_merchant_id'       => 'razorpay axis_genius',
            'gateway_terminal_id'       => 'nodal account axis_genius',
            'gateway_terminal_password' => 'razorpay_password',
        );

        $this->fixtures->merchant->enablePaytm();

        $this->fixtures->on('live')->create('terminal', $attributes);

        $this->startTest();
    }

    public function testGetCheckoutRoute()
    {
        $this->ba->publicLiveAuth();

        $this->fixtures->merchant->activate('10000000000000');

        $request = array(
            'url' => '/checkout',
            'method' => 'get',
            'content' => [
                'currency' => 'INR',
            ],
        );

        $response = $this->sendRequest($request);

        $headers = $response->headers->all();
        $this->assertArrayNotHasKey('x-frame-options', $headers);
    }

    public function testGetCheckoutPublicRoute()
    {
        $this->ba->directAuth();

        $response = $this->call('GET', '/v1/checkout/public');

        $response->assertStatus(200);
        $this->assertStringContainsString('<title>Razorpay Checkout</title>', $response->getContent());
    }

    public function testGetNetbankingDowntimeInfoForDirectNetbankingGateway()
    {
        $this->ba->publicAuth();

        $dt = Carbon::createFromTimestamp(1517077800, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->fixtures->create('gateway_downtime:netbanking', [
            'gateway' => 'netbanking_hdfc',
            'issuer'  => 'ALL']);

        $this->startTest();
    }

    public function testGetNetbankingDowntimeInfoWithSharedNetbankingGateway()
    {
        $this->ba->publicAuth();

        $dt = Carbon::createFromTimestamp(1517077800, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->fixtures->create('gateway_downtime:netbanking', [
            'gateway' => 'billdesk',
            'issuer'  => 'ALL']);

        $this->startTest();
    }

    public function testGetNetbankingDowntimeInfoWithBothSharedAndDirectGateway()
    {
        $this->ba->publicAuth();

        $dt = Carbon::createFromTimestamp(1517077800, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->fixtures->create('gateway_downtime:netbanking', [
            'gateway' => 'billdesk',
            'issuer'  => 'ALL']);

        $dt = Carbon::createFromTimestamp(1517077800, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->fixtures->create('gateway_downtime:netbanking', [
            'gateway' => 'netbanking_hdfc',
            'issuer'  => 'ALL']);

        $this->startTest();
    }

    public function testGetNetbankingDowntimeWithNoBanksExclusiveToGateway()
    {
        $this->ba->publicAuth();

        $dt = Carbon::createFromTimestamp(1517077800, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->fixtures->create('gateway_downtime:netbanking', [
            'gateway' => 'ebs',
            'issuer'  => 'ALL']);

        $this->startTest();
    }

    public function testGetNetbankingDowntimeInfoWithIssuerExclusiveToGateway()
    {
        $this->ba->publicAuth();

        $dt = Carbon::createFromTimestamp(1517077800, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->fixtures->create('gateway_downtime:netbanking', [
            'gateway' => 'billdesk',
            'issuer'  => 'ALLA']);

        $this->startTest();
    }

    public function testGetNetbankingDowntimeInfoWithIssuerNa()
    {
        $this->ba->publicAuth();

        $dt = Carbon::createFromTimestamp(1517077800, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->fixtures->create('gateway_downtime:netbanking', [
            'gateway' => 'billdesk',
            'issuer'  => 'NA']);

        $this->startTest();
    }

    public function testGetNetbankingDowntimeInfoWithGatewayAll()
    {
        $this->ba->publicAuth();

        $dt = Carbon::createFromTimestamp(1517077800, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->fixtures->create('gateway_downtime:netbanking', [
            'gateway' => 'ALL',
            'issuer'  => 'HDFC']);

        $this->startTest();
    }

    public function testGetNetbankingDowntimeInfoWithMultipleDowntimes()
    {
        $this->ba->publicAuth();

        $dt = Carbon::createFromTimestamp(1517077800, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->fixtures->create('gateway_downtime:netbanking', [
            'gateway'     => 'netbanking_hdfc',
            'issuer'      => 'HDFC',
            'reason_code' => 'ISSUER_DOWN']);

        $dt = Carbon::createFromTimestamp(1517077800, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->fixtures->create('gateway_downtime:netbanking', [
            'gateway'     => 'billdesk',
            'issuer'      => 'ALLA',
            'reason_code' => 'LOW_SUCCESS_RATE']);

        $this->startTest();
    }

    public function testPreferenceforTpvMerchantWithOrder()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->enableTPV();

        $order = $this->fixtures->order->create(['receipt' => 'check123', 'bank' => 'ICIC', 'account_number' => '0040304030403040', 'amount' => '100']);

        $this->testData[__FUNCTION__]['request']['content']['order_id'] = $order->getPublicId();

        $this->startTest();
    }

    public function testPreferencesAfterFilterForMinimumAmountWithOrderAmountGreater()
    {
        $this->fixtures->merchant->enablePayLater();

        $this->fixtures->create('terminal:paylater_icici_terminal');
        $this->fixtures->create('terminal:paylater_flexmoney_terminal');

        $this->ba->publicAuth();

        $order = $this->fixtures->order->create(['receipt' => 'check001', 'amount' => '200000']);

        $this->testData[__FUNCTION__]['request']['content']['order_id'] = $order->getPublicId();

        $response = $this->startTest();

        $this->assertArrayHasKey('hdfc', $response['methods']['paylater']);
    }

    public function testPreferencesAfterFilterForMinimumAmountWithOrderAmountLess()
    {
        $this->fixtures->merchant->enablePayLater();

        $this->fixtures->create('terminal:paylater_icici_terminal');
        $this->fixtures->create('terminal:paylater_flexmoney_terminal');

        $this->ba->publicAuth();

        $order = $this->fixtures->order->create(['receipt' => 'check002', 'amount' => '1000']);

        $this->testData[__FUNCTION__]['request']['content']['order_id'] = $order->getPublicId();

        $response = $this->startTest();

        $this->assertArrayNotHasKey('hdfc', $response['methods']['paylater']);
    }

    public function testPreferencesAfterFilterForMinimumAmountWithoutOrderOrAmount()
    {
        $this->fixtures->merchant->enablePayLater();

        $this->fixtures->create('terminal:paylater_icici_terminal');
        $this->fixtures->create('terminal:paylater_flexmoney_terminal');

        $this->ba->publicAuth();

        $response = $this->startTest();

        $this->assertArrayHasKey('hdfc', $response['methods']['paylater']);
    }

    public function testPreferenceforForcedOfferWithMethod()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->enableWallet('10000000000000', 'phonepe');

        $this->fixtures->create('terminal:shared_phonepe_terminal');

        $offer = $this->fixtures->create('offer', ['payment_method'   => 'wallet']);

        $order = $this->fixtures->order->createWithOffers($offer, [
            'force_offer' => true,
        ]);

        $this->testData[__FUNCTION__]['request']['content']['order_id'] = $order->getPublicId();

        $this->startTest();
    }

    public function testGetCheckoutRouteWithSavedLocal()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->activate('10000000000000');

        $response = $this->startTest();

        $this->assertNotNull($response['customer']['tokens']);
    }

    public function testGetCheckoutRouteCustomerContact()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->activate('10000000000000');

        $response = $this->startTest();

        $this->assertEquals($response['customer']['saved'], true);
    }

    public function testGetCheckoutRouteWithDeviceToken()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->activate('10000000000000');

        $response = $this->startTest();
    }

    public function testGetCheckoutRouteWithAndroidMetadata()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures(['cardsaving']);

        $this->session(['test_app_token' => '1000001custapp']);

        $response = $this->startTest();

        $this->assertEquals(isset($response['options']['customer']), false);
    }

    public function testGetCheckoutRouteWithCustomerTokenNoBillingAddress()
    {
        $this->ba->publicAuth();

        $response = $this->startTest();

        $this->assertNotNull($response['customer']['tokens']);

        $items = $response['customer']['tokens']['items'];

        foreach ($items as $item)
        {
            $this->assertNull($item['billing_address']);
        }
    }

    public function testGetCheckoutRouteWithAndroidMetadataNoSession()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures(['cardsaving']);

        $response = $this->startTest();
    }

    public function testGetCheckoutRouteWithEmi()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->activate('10000000000000');

        $this->fixtures->merchant->enableEmi();

        $response = $this->startTest();

        $this->assertEquals($response['methods']['emi'], true);
    }

    public function testGetCheckoutRouteWithMerchantSubEmi()
    {
        $this->ba->publicAuth();

        $this->fixtures->edit(
            'methods',
            '10000000000000',
            [
                'emi' => [
                    Merchant\Methods\EmiType::CREDIT => '1',
                ],
            ]);

        $this->fixtures->create('emi_plan:default_emi_plans');

        $offer = $this->fixtures->create('offer:emi_subvention');

        $order = $this->fixtures->order->createWithOffers($offer, ['amount' => 400000]);

        $this->ba->publicAuth();

        $this->testData[__FUNCTION__]['request']['content']['order_id'] = $order->getPublicId();

        $this->startTest();
    }

    public function testGetCheckoutWithMultipleSubEmiOffers()
    {
        $this->ba->publicAuth();

        $this->fixtures->edit(
            'methods',
            '10000000000000',
            [
                'emi' => [Merchant\Methods\EmiType::CREDIT => '1'],
            ]);

        $this->fixtures->create('emi_plan:default_emi_plans');

        $offer1 = $this->fixtures->create('offer:emi_subvention');
        $offer2 = $this->fixtures->create('offer:emi_subvention', ['emi_durations' => [6,9]]);

        $order = $this->fixtures->order->createWithOffers([
            $offer1, $offer2
        ], ['amount' => 400000]);

        $this->ba->publicAuth();

        $this->testData[__FUNCTION__]['request']['content']['order_id'] = $order->getPublicId();

        $this->startTest();
    }

    public function testGetCheckoutRouteWithSavedGlobal()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->activate('10000000000000');

        $response = $this->startTest();

        $this->assertNotNull($response['customer']['tokens']);

        $this->assertEquals($response['options']['remember_customer'], true);
    }

    public function testGetCheckoutRouteWithWrongKey()
    {
        $this->ba->publicLiveAuth('random');

        $this->fixtures->merchant->activate('10000000000000');

        $request = array(
            'url' => '/checkout',
            'method' => 'get',
            'content' => [],
        );

        $response = $this->sendRequest($request);

        $headers = $response->headers->all();
        $this->assertArrayNotHasKey('x-frame-options', $headers);
    }

    public function testGetCheckoutRouteWithCheckoutFeatures()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->activate('10000000000000');

        $this->fixtures->merchant->addFeatures(['google_pay']);

        $this->fixtures->merchant->addFeatures(['google_pay_omnichannel', 'phonepe_intent']);

        $response = $this->startTest();

        $this->assertNotNull($response['features']['google_pay']);

        $this->assertNotNull($response['features']['google_pay_omnichannel']);

        $this->assertNotNull($response['features']['phonepe_intent']);

        $this->assertNotNull($response['features']['dcc']);

        $this->assertTrue($response['features']['dcc'] === true);
    }

    public function testGetCheckoutRouteWithCheckoutFeaturesInternationalDisabled()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->activate('10000000000000');

        $this->fixtures->merchant->disableInternational();

        $this->fixtures->merchant->addFeatures(['google_pay']);

        $this->fixtures->merchant->addFeatures(['google_pay_omnichannel', 'phonepe_intent', 'avs']);

        $response = $this->makePreferencesRouteRequest();

        $this->assertNotNull($response['features']['google_pay']);

        $this->assertNotNull($response['features']['google_pay_omnichannel']);

        $this->assertNotNull($response['features']['phonepe_intent']);

        $this->assertArrayNotHasKey('avs',$response['features']);

        $this->assertArrayNotHasKey('dcc', $response['features']);
    }

    public function testPutPaytmMethod()
    {
        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1A0Fkd38fGZPVC']);
        $this->fixtures->merchant->disableInternational();

        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach('10000000000000');

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testFetchEsScheduledPricing()
    {
        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1A0Fkd38fGZPVC']);

        $this->ba->proxyAuthTest();

        $this->startTest();
    }

    public function testEnableEsScheduledEsautomaticPricingUnavailableForSharedPlan()
    {
        Mail::fake();

        $this->fixtures->pricing->createTestPlanForNoOndemandAndEsAutomaticPricing();

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1BFFkd38fFGbnh',  'international'    => 0]);

        $merchant2 = $this->fixtures->create('merchant');

        $merchant2Id = $merchant2['id'];

        $this->fixtures->merchant->edit($merchant2Id, ['pricing_plan_id' => '1BFFkd38fFGbnh', 'international'    => 0]);

        $this->fixtures->create(
            'schedule',
            [
                'id'       => '100001schedule',
                'period'   => 'hourly',
                'interval' => 1,
                'anchor'   => null,
                'delay'    => 0,
                'hour'     => 0,
                'name'     => 'hh',
            ]);

        $this->fixtures->merchant->addFeatures(['es_on_demand']);

        $this->ba->proxyAuthTest();

        $this->startTest();

        $esAutomaticPricingRulesOldPlan = $this->getDbEntities('pricing', ['feature'        => 'esautomatic',
                                                                           'plan_id'        => '1BFFkd38fFGbnh',])->toArray();

        $this->assertEmpty($esAutomaticPricingRulesOldPlan);

        $esAutomaticPricingRulesNewPlan = $this->getDbEntities('pricing', ['feature'        => 'esautomatic'])->toArray();

        $this->assertEquals(sizeof($esAutomaticPricingRulesNewPlan), 11);

        Mail::assertQueued(EsEnabledNotify::class);
    }

    public function testFetchEsScheduledPricingInternationalPricing()
    {
        $this->fixtures->pricing->createTestPlanForNoOndemandAndEsAutomaticPricing();

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1BFFkd38fFGbnh']);

        $esInternationalRules = [
            [
                'id'             => 'EsInterPrice01',
                'plan_id'        => '1BFFkd38fFGbnh',
                'plan_name'      => 'testDefaultPlan',
                'feature'        => 'esautomatic',
                'payment_method' => 'card',
                'percent_rate'   => 0,
                'fixed_rate'     => 0,
                'international'  => 1,
                'org_id'         => '100000razorpay',
            ],
            [
                'id'             => 'EsInterPrice02',
                'plan_id'        => '1BFFkd38fFGbnh',
                'plan_name'      => 'testDefaultPlan',
                'feature'        => 'esautomatic',
                'payment_method' => 'upi',
                'percent_rate'   => 20,
                'fixed_rate'     => 0,
                'international'  => 1,
                'org_id'         => '100000razorpay',
            ]
        ];

        $this->fixtures->pricing->addPricingRulesToDb($esInternationalRules);

        $this->ba->proxyAuthTest();

        $this->startTest();

        $esAutomaticPricingRules = $this->getDbEntities('pricing', ['feature'        => 'esautomatic',
                                                                    'plan_id'        => '1BFFkd38fFGbnh',])->toArray();

        $this->assertEquals(sizeof($esAutomaticPricingRules), 12);
    }

    public function testMerchantFeatures()
    {
        $this->fixtures->merchant->addFeatures(['vas_merchant']);

        $merchant = (new Merchant\Repository)->findOrFail('10000000000000');

        $isEnabled = $merchant->isFeatureEnabled(Feature\Constants::VAS_MERCHANT);

        // check feature enabled
        $this->assertTrue($isEnabled);
    }

    public function testEnableEsScheduledSuccess()
    {
        // We expect a mail to be shot to merchant every time Es schedule enable succeeds
        Mail::fake();

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1A0Fkd38fGZPVC']);

        $this->fixtures->create(
            'schedule',
            [
                'id'       => '100001schedule',
                'period'   => 'hourly',
                'interval' => 1,
                'anchor'   => null,
                'delay'    => 0,
                'hour'     => 0,
                'name'     => 'demo',
            ]);

        $scheduleTaskCard = [
            'method'        => 'card',
            'international' => 1,
            'entity_type'   =>'merchant'
        ];

        $scheduleTaskUpi = [
            'method'        => 'upi',
            'international' => 0,
            'entity_type'   =>'merchant'
        ];

        $this->fixtures->create(
            'schedule_task',
            $scheduleTaskCard);

        $this->fixtures->create(
            'schedule_task',
            $scheduleTaskUpi);

        $this->fixtures->merchant->addFeatures(['es_on_demand']);

        $this->ba->proxyAuthTest();

        $this->startTest();

        $features = $this->getEntities('feature', [], true);

        $this->assertCount(2, $features['items']);

        $this->assertEquals('es_automatic', $features['items'][0]['name']);

        $this->assertEquals('es_on_demand', $features['items'][1]['name']);

        $pricingRule = $this->getDbEntityById('pricing', '1zE31zbyeGCTd9');

        $this->assertEquals('18', $pricingRule['percent_rate']);

        $scheduleTaskUpi['schedule_id'] = '100001schedule';

        $scheduleTasks = $this->getEntities('schedule_task', [], true);

        $this->assertArraySelectiveEquals($scheduleTaskUpi,
            (array) collect($scheduleTasks['items'])->firstWhere('method', '=', 'upi'));

        $this->assertArraySelectiveEquals($scheduleTaskCard,
            (array) collect($scheduleTasks['items'])->firstWhere('method', '=', 'card'));

        $this->assertArraySelectiveEquals([
            'method'        => null,
            'international' => 0,
            'entity_type'   =>'merchant',
            'schedule_id'   =>'100001schedule'
        ],
            (array) collect($scheduleTasks['items'])->firstWhere('method', '=', null));

        $this->assertNotEquals('100001schedule', $scheduleTasks['items'][2]['schedule_id']);

        // In this case we expect only one mail is queued
        Mail::assertQueued(EsEnabledNotify::class, 1);

        Mail::assertQueued(EsEnabledNotify::class, function ($mail)
        {
            $this->assertEquals(EsEnabledNotify::MERCHANT_MAILER_VIEW,$mail->view);

            $this->assertEquals(EsEnabledNotify::MERCHANT_MAILER_SUBJECT, $mail->subject);

            $this->assertArrayKeysExist($mail->viewData,
                                            [EsEnabledNotify::TO_EMAIL, EsEnabledNotify::TO_NAME, EsEnabledNotify::SUBJECT, EsEnabledNotify::VIEW, Pricing\Entity::PERCENT_RATE]);

            return ($mail->hasFrom(self::CAPITAL_SUPPORT_EMAIL)) and
                    ($mail->hasTo(self::CAPITAL_SUPPORT_EMAIL));
        });
    }

    public function testEnableEsScheduledSuccessWithKAMMail()
    {
        // We expect 2 mails to be queued in case a Key Account (one with tag 'KA') enables es scheduled, additional mail is circulated internally to kam and capital product.
        Mail::fake();

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1A0Fkd38fGZPVC']);

        $this->fixtures->create(
            'schedule',
            [
                'id'       => '100001schedule',
                'period'   => 'hourly',
                'interval' => 1,
                'anchor'   => null,
                'delay'    => 0,
                'hour'     => 0,
                'name'     => 'demo',
            ]);

        $scheduleTaskCard = [
            'method'        => 'card',
            'international' => 1,
            'entity_type'   =>'merchant'
        ];

        $this->fixtures->create(
            'schedule_task',
            $scheduleTaskCard);

        $this->fixtures->merchant->addFeatures(['es_on_demand']);

        $this->ba->proxyAuthTest();

        $merchantEntityInstance = $this->getDbEntityById('merchant', '10000000000000', true);

        $merchantEntityInstance->tag('KA');

        $this->startTest();

        // First check if enough mails have been queued
        Mail::assertQueued(EsEnabledNotify::class, 2);

        Mail::assertQueued(EsEnabledNotify::class, function ($mail)
        {
            switch ($mail->view){
                // Check mailer for merchant intimation
                case EsEnabledNotify::MERCHANT_MAILER_VIEW:
                    $this->assertEquals(EsEnabledNotify::MERCHANT_MAILER_SUBJECT, $mail->subject);

                    $this->assertArrayKeysExist($mail->viewData,
                        [EsEnabledNotify::TO_EMAIL, EsEnabledNotify::TO_NAME, EsEnabledNotify::SUBJECT, EsEnabledNotify::VIEW, Pricing\Entity::PERCENT_RATE]);

                    return ($mail->hasFrom(self::CAPITAL_SUPPORT_EMAIL)) and
                        ($mail->hasTo(self::CAPITAL_SUPPORT_EMAIL));

                // Check mailer for Key Account intimation
                case EsEnabledNotify::KAM_MAILER_VIEW:
                    $this->assertEquals(EsEnabledNotify::KAM_MAILER_SUBJECT, $mail->subject);

                    $this->assertArrayKeysExist($mail->viewData,
                        [EsEnabledNotify::TO_EMAIL, EsEnabledNotify::TO_NAME, EsEnabledNotify::SUBJECT, EsEnabledNotify::VIEW, EsEnabledNotify::MERCHANT_DATA]);

                    return ($mail->hasFrom(self::CAPITAL_SUPPORT_EMAIL)) and
                        ($mail->hasTo(EsEnabledNotify::KAM_MAILING_LIST_EMAILS));

                // If either not present than appropriate mailer is missing
                default:
                    return false;
            }
        });
    }

    public function testMerchantEmailUpdateUserStatusForEmailUserNotExist()
    {
        Mail::fake();

        $merchant = $this->fixtures->create('merchant');

        $user = $this->fixtures->create('user', ['email' => 'abctest@gmail.com']);

        $this->createMerchantUserMapping($user->getId(), $merchant->getId(), 'owner');

        $testData = $this->testData['testMerchantEmailGetUserStatus'];

        $testData['response']['content'] = [
            'is_user_exist'  => false,
            'is_team_member' => false,
            'is_owner'       => false,
        ];

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $user['id']);

        $this->startTest();

        $user = $this->getDbEntityById('user', $user['id']);

        $token = $user->getPasswordResetToken();

        $expectedCacheData = [
            'current_owner_email'    => 'abctest@gmail.com',
            'email'                  => 'newowner@gmail.com',
            'merchant_id'            => $merchant['id'],
            'reattach_current_owner' => true,
            'set_contact_email'      => true,
        ];

        $this->assertCacheDataForMerchantEmailUpdate($merchant['id'], $expectedCacheData);

        Mail::assertQueued(MerchantMail\OwnerEmailChange::class, function ($mailable) use($merchant, $token)
        {
            $mailData = $mailable->viewData;

            $this->assertEquals('emails.merchant.owner_email_change_request', $mailable->view);

            $this->assertNotEmpty($mailData['org']);

            $this->assertEquals('abctest@gmail.com', $mailData['current_owner_email']);

            $this->assertEquals('newowner@gmail.com', $mailData['email']);

            $this->assertTrue($mailable->hasTo('abctest@gmail.com'));

            return true;
        });

        Mail::assertQueued(PasswordAndEmailResetMail::class, function ($mailable) use($merchant, $token)
        {
            $mailData = $mailable->viewData;

            $this->assertEquals($mailData['token'], $token);

            $this->assertNotEmpty($mailData['org']);

            $this->assertEquals('abctest@gmail.com', $mailData['current_owner_email']);

            $this->assertEquals($merchant['id'], $mailData['merchant_id']);

            $this->assertEquals('newowner@gmail.com', $mailData['email']);

            $this->assertTrue($mailable->hasTo('newowner@gmail.com'));

            return true;
        });
    }

    public function testMerchantEmailUpdateUserStatusForEmailUserExistInTeam()
    {
        Mail::fake();

        $merchant = $this->fixtures->create('merchant');
        $user = $this->fixtures->create('user', ['email' => 'abctest@gmail.com']);
        $this->createMerchantUserMapping($user['id'], $merchant['id'], 'owner');

        $existingTeamUser = $this->fixtures->user->createEntityInTestAndLive('user', [
            'email' => 'newowner@gmail.com'
        ]);
        $this->createMerchantUserMapping($existingTeamUser['id'], $merchant['id'], 'manager');

        $testData = $this->testData['testMerchantEmailGetUserStatus'];

        $testData['response']['content'] = [
            'is_user_exist'  => true,
            'is_team_member' => true,
            'is_owner'       => false,
        ];

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $user['id']);

        $this->startTest();

        $this->assertCacheDataForMerchantEmailUpdate($merchant['id'], null);

        Mail::assertNotQueued(MerchantMail\OwnerEmailChange::class);

        Mail::assertNotQueued(PasswordAndEmailResetMail::class);
    }

    public function testMerchantEmailUpdateUserStatusForEmailUserExistInNonTeamNonOwner()
    {
        Mail::fake();

        $merchant1 = $this->fixtures->create('merchant');
        $user = $this->fixtures->create('user', ['email' => 'abctest@gmail.com']);
        $this->createMerchantUserMapping($user['id'], $merchant1['id'], 'owner');

        $merchant2 = $this->fixtures->create('merchant');
        $nonTeamExistingUser = $this->fixtures->user->createEntityInTestAndLive('user', [
            'email' => 'newowner@gmail.com'
        ]);
        $this->createMerchantUserMapping($nonTeamExistingUser['id'], $merchant2['id'], 'manager');

        $testData = $this->testData['testMerchantEmailGetUserStatus'];

        $testData['response']['content'] = [
            'is_user_exist'  => true,
            'is_team_member' => false,
            'is_owner'       => false,
        ];

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->proxyAuth('rzp_test_' . $merchant1['id'], $user['id']);

        $this->startTest();

        $this->assertCacheDataForMerchantEmailUpdate($merchant1['id'], null);

        Mail::assertNotQueued(MerchantMail\OwnerEmailChange::class);

        Mail::assertNotQueued(PasswordAndEmailResetMail::class);
    }

    public function testMerchantEmailUpdateUserStatusForEmailUserExistInNonTeamOwner()
    {
        Mail::fake();

        $merchant1 = $this->fixtures->create('merchant');
        $user = $this->fixtures->create('user', ['email' => 'abctest@gmail.com']);
        $this->createMerchantUserMapping($user['id'], $merchant1['id'], 'owner');

        $merchant2 = $this->fixtures->create('merchant');
        $nonTeamExistingUser = $this->fixtures->user->createEntityInTestAndLive('user', [
            'email' => 'newowner@gmail.com'
        ]);
        $this->createMerchantUserMapping($nonTeamExistingUser['id'], $merchant2['id'], 'owner');

        $testData = $this->testData['testMerchantEmailGetUserStatus'];

        $testData['response']['content'] =  [
            'is_user_exist'  => true,
            'is_team_member' => false,
            'is_owner'       => true,
        ];

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->proxyAuth('rzp_test_' . $merchant1['id'], $user['id']);

        $this->startTest();

        $this->assertCacheDataForMerchantEmailUpdate($merchant1['id'], null);

        Mail::assertNotQueued(MerchantMail\OwnerEmailChange::class);

        Mail::assertNotQueued(PasswordAndEmailResetMail::class);
    }

    public function testMerchantEmailUpdateCreateNewOwnerDetachOldOwner()
    {
        $this->merchantEmailUpdateCreateNewOwner(false, false, true);
    }

    public function testMerchantEmailUpdateCreateNewOwnerDetachOldOwnerSetContactEmail()
    {
        $this->merchantEmailUpdateCreateNewOwner(false, true, false);
    }

    public function testMerchantEmailUpdateCreateNewOwnerReAttachOldOwner()
    {
        $this->merchantEmailUpdateCreateNewOwner(true, false, true);
    }

    public function testMerchantEmailUpdateCreateNewOwnerReAttachOldOwnerSetContactEmail()
    {
        $this->merchantEmailUpdateCreateNewOwner(true, true, false);
    }

    public function testMerchantEmailUpdateCreateNewOwnerTokenMismatchFail()
    {
        Mail::fake();

        $reAttachCurrentOwner = true;

        $setContactEmail = true;

        $app = App::getFacadeRoot();

        $merchant = $this->fixtures->create('merchant', ['email' => 'oldcontact@gmail.com']);

        $token1 = str_random(50);

        $token2 = str_random(50);

        $oldOwnerUser = $this->fixtures->create('user',[
            'email'                   => 'oldowner@gmail.com',
            'contact_mobile'          => '8839106483',
            'name'                    => 'ownername',
            'contact_mobile_verified' => true,
            'password_reset_token'    => $token1,
            'password_reset_expiry'   => Carbon::now()->timestamp + 86400,
        ]);

        $this->createMerchantUserMapping($oldOwnerUser['id'], $merchant['id'], 'owner');

        // put data in cache
        $cacheData = [
            'current_owner_email'    => 'oldowner@gmail.com',
            'email'                  => 'newowner@gmail.com',
            'merchant_id'            => $merchant['id'],
            'reattach_current_owner' => $reAttachCurrentOwner,
            'set_contact_email'      => $setContactEmail,
        ];

        $app['cache']->put('merchant_email_update_' . $merchant['id'], $cacheData, 60*60*24);

        $testData = $this->testData['testMerchantEmailUpdateCreateNewUserByExpiredToken'];

        $testData['request']['content']['token']       = $token2;
        $testData['request']['content']['merchant_id'] = $merchant['id'];

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

        Mail::assertNotQueued(MerchantMail\OwnerEmailChange::class);
    }

    public function testMerchantEmailUpdateCreateNewOwnerTokenExpiredFail()
    {
        Mail::fake();

        $reAttachCurrentOwner = true;

        $setContactEmail = true;

        $app = App::getFacadeRoot();

        $merchant = $this->fixtures->create('merchant', ['email' => 'oldcontact@gmail.com']);

        $token = str_random(50);

        $oldOwnerUser = $this->fixtures->create('user',[
            'email'                   => 'oldowner@gmail.com',
            'contact_mobile'          => '8839106483',
            'name'                    => 'ownername',
            'contact_mobile_verified' => true,
            'password_reset_token'    => $token,
            'password_reset_expiry'   => Carbon::now()->timestamp - 86400,
        ]);

        $this->createMerchantUserMapping($oldOwnerUser['id'], $merchant['id'], 'owner');

        // put data in cache
        $cacheData = [
            'current_owner_email'    => 'oldowner@gmail.com',
            'email'                  => 'newowner@gmail.com',
            'merchant_id'            => $merchant['id'],
            'reattach_current_owner' => $reAttachCurrentOwner,
            'set_contact_email'      => $setContactEmail,
        ];

        $app['cache']->put('merchant_email_update_' . $merchant['id'], $cacheData, 60*60*24);

        $testData = $this->testData['testMerchantEmailUpdateCreateNewUserByExpiredToken'];

        $testData['request']['content']['token']       = $token;
        $testData['request']['content']['merchant_id'] = $merchant['id'];

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

        Mail::assertNotQueued(MerchantMail\OwnerEmailChange::class);
    }

    public function testMerchantEmailUpdateCreateNewOwnerCacheDataExpiredFail()
    {
        Mail::fake();

        $merchant = $this->fixtures->create('merchant', ['email' => 'oldcontact@gmail.com']);

        $oldOwnerUser = $this->fixtures->create('user',[
            'email'                   => 'oldowner@gmail.com',
            'contact_mobile'          => '8839106483',
            'name'                    => 'ownername',
            'contact_mobile_verified' => true,
        ]);

        $this->createMerchantUserMapping($oldOwnerUser['id'], $merchant['id'], 'owner');

        $testData = $this->testData['testMerchantEmailUpdateCreateNewUserByExpiredToken'];

        $testData['request']['content']['token']       = str_random(50);
        $testData['request']['content']['merchant_id'] = $merchant['id'];

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

        Mail::assertNotQueued(MerchantMail\OwnerEmailChange::class);
    }

    public function testMerchantEmailUpdateEmailUserExistNotInTeamReAttachOldOwner()
    {
        $this->merchantEmailUpdateForExistingEmailUser(false, true, false, true);
    }

    public function testMerchantEmailUpdateEmailUserExistNotInTeamDetachOldOwnerSetContactEmail()
    {
        $this->merchantEmailUpdateForExistingEmailUser(false, false, true, false);
    }

    public function testMerchantEmailUpdateEmailUserExistInTeamReAttachOldOwner()
    {
        $this->merchantEmailUpdateForExistingEmailUser(true, true, false, true);
    }

    public function testMerchantEmailUpdateEmailUserExistInTeamDetachOldOwnerSetContactEmail()
    {
        $this->merchantEmailUpdateForExistingEmailUser(true, false, true, false);
    }

    public function testMerchantEmailUpdateEmailUserExistContactEmailAlreadyTakenPass()
    {
        $this->testData[__FUNCTION__] = $this->testData['testMerchantEmailUpdateExistingUser'];

        $merchant = $this->fixtures->create('merchant', [
            'email' => 'oldcontact@gmail.com'
        ]);

        // this merchant has new owner's email as contact email
         $this->fixtures->create('merchant', [
            'email' => 'newowner@gmail.com'
        ]);

        $oldOwnerUser = $this->fixtures->create('user',[
            'email'                   => 'oldowner@gmail.com',
            'contact_mobile'          => '8839106483',
            'name'                    => 'ownername',
            'contact_mobile_verified' => true,
        ]);

        $this->createMerchantUserMapping($oldOwnerUser['id'], $merchant['id'], 'owner');

        $this->fixtures->create('user', ['email' => 'newowner@gmail.com']);

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $oldOwnerUser['id']);

        $this->startTest();

        $this->assertMerchantContactEmailForEmailUpdate($merchant['id'], true);
    }

    public function testMerchantEmailUpdateEmailUserExistUserHasCrossOrgMerchantFail()
    {
        $merchant = $this->fixtures->create('merchant', [
            'email' => 'oldcontact@gmail.com'
        ]);

        $oldOwnerUser = $this->fixtures->create('user',[
            'email'                   => 'oldowner@gmail.com',
            'contact_mobile'          => '8839106483',
            'name'                    => 'ownername',
            'contact_mobile_verified' => true,
        ]);

        $this->createMerchantUserMapping($oldOwnerUser['id'], $merchant['id'], 'owner');

        $existingUser = $this->fixtures->create('user', ['email' => 'newowner@gmail.com']);

        $crossOrg =  $this->fixtures->create('org');

        $crossOrgMerchant = $this->fixtures->create('merchant', [
            'org_id' => $crossOrg['id']
        ]);

        $this->createMerchantUserMapping($existingUser['id'], $crossOrgMerchant['id'], 'owner');

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $oldOwnerUser['id']);

        $this->startTest();
    }

    protected function merchantEmailUpdateCreateNewOwner($reAttachCurrentOwner, $setContactEmail, $isCurrentOwnerOnX)
    {
        Mail::fake();

        $app = App::getFacadeRoot();

        $merchant = $this->fixtures->create('merchant', ['email' => 'oldcontact@gmail.com']);

        $token = str_random(50);

        $oldOwnerUser = $this->fixtures->create('user',[
            'email'                   => 'oldowner@gmail.com',
            'contact_mobile'          => '8839106483',
            'name'                    => 'ownername',
            'contact_mobile_verified' => true,
            'password_reset_token'    => $token,
            'password_reset_expiry'   => Carbon::now()->timestamp + 86400,
        ]);

        // create owner role on pg
        $this->createMerchantUserMapping($oldOwnerUser['id'], $merchant['id'], 'owner', 'test', 'primary');

        // if current owner is owner for X: create owner role for banking product
        if ($isCurrentOwnerOnX === true)
        {
            $this->createMerchantUserMapping($oldOwnerUser['id'], $merchant['id'], 'owner', 'test', 'banking');
        }

        // put data in cache
        $cacheData = [
            'current_owner_email'    => 'oldowner@gmail.com',
            'email'                  => 'newowner@gmail.com',
            'merchant_id'            => $merchant['id'],
            'reattach_current_owner' => $reAttachCurrentOwner,
            'set_contact_email'      => $setContactEmail,
        ];
        $app['cache']->put('merchant_email_update_' . $merchant['id'], $cacheData, 60*60*24);

        $oldOwnerUser = $this->getDbEntityById('user', $oldOwnerUser['id']);

        $testData = $this->testData['testMerchantEmailUpdateCreateNewUser'];

        $testData['request']['content']['token']       = $token;
        $testData['request']['content']['merchant_id'] = $merchant['id'];

        $testData['response']['content']['logout_sessions_for_users'] = [$oldOwnerUser->getId()];

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

        Mail::assertQueued(MerchantMail\OwnerEmailChange::class, function ($mailable) use($merchant, $token)
        {
            $mailData = $mailable->viewData;

            $this->assertEquals('emails.merchant.owner_email_change', $mailable->view);

            $this->assertNotEmpty($mailData['org']);

            $this->assertEquals('oldowner@gmail.com', $mailData['current_owner_email']);

            $this->assertEquals('newowner@gmail.com', $mailData['email']);

            $this->assertTrue($mailable->hasTo('oldowner@gmail.com'));

            return true;
        });

        $newOwnerUser = $this->getLastEntity('user', true);

        $this->assertOldAndNewOwnerAttributes($newOwnerUser['id'], $oldOwnerUser['id']);

        $this->assertCacheDataForMerchantEmailUpdate($merchant['id'], null);

        // assert roles for PG
        $this->assertRolesOfOldAndNewOwnersForMerchantEmailUpdate(
            $merchant['id'],
            $oldOwnerUser['id'],
            $newOwnerUser['id'],
            $reAttachCurrentOwner,
            'primary'
        );

        // if current owner is owner for X : assert Role for banking product
        if ($isCurrentOwnerOnX === true)
        {
            $this->assertRolesOfOldAndNewOwnersForMerchantEmailUpdate(
                $merchant['id'],
                $oldOwnerUser['id'],
                $newOwnerUser['id'],
                $reAttachCurrentOwner,
                'banking'
            );
        }

        $this->assertMerchantContactEmailForEmailUpdate($merchant['id'], $setContactEmail);
    }

    protected function merchantEmailUpdateForExistingEmailUser($userExistInTeam, $reAttachCurrentOwner, $setContactEmail, $isCurrentOwnerOnX)
    {
        Mail::fake();

        $merchant = $this->fixtures->create('merchant', [
            'email' => 'oldcontact@gmail.com'
        ]);

        $oldOwnerUser = $this->fixtures->create('user',[
            'email'                   => 'oldowner@gmail.com',
            'contact_mobile'          => '8839106483',
            'name'                    => 'ownername',
            'contact_mobile_verified' => true,
        ]);

        // create owner role on pg
        $this->createMerchantUserMapping($oldOwnerUser['id'], $merchant['id'], 'owner', 'test', 'primary');

        // if current owner is owner for X: create owner role for banking product
        if ($isCurrentOwnerOnX === true)
        {
            $this->createMerchantUserMapping($oldOwnerUser['id'], $merchant['id'], 'owner', 'test', 'banking');
        }

        $existingUser = $this->fixtures->create('user', ['email' => 'newowner@gmail.com']);

        if ($userExistInTeam === true)
        {
            $this->createMerchantUserMapping($existingUser['id'], $merchant['id'], 'support');
        }

        $testData = $this->testData['testMerchantEmailUpdateExistingUser'];

        $testData['request']['content']['set_contact_email']      = $setContactEmail;
        $testData['request']['content']['reattach_current_owner'] = $reAttachCurrentOwner;

        $testData['response']['content']['logout_sessions_for_users'] = [$existingUser['id'], $oldOwnerUser['id']];

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $oldOwnerUser['id']);

        $this->startTest();

        Mail::assertQueued(MerchantMail\OwnerEmailChange::class, function ($mailable) use($merchant)
        {
            $mailData = $mailable->viewData;

            $this->assertEquals('emails.merchant.owner_email_change', $mailable->view);

            $this->assertNotEmpty($mailData['org']);

            $this->assertEquals('oldowner@gmail.com', $mailData['current_owner_email']);

            $this->assertEquals('newowner@gmail.com', $mailData['email']);

            $this->assertTrue($mailable->hasTo('oldowner@gmail.com'));

            return true;
        });

        // assert roles for PG
        $this->assertRolesOfOldAndNewOwnersForMerchantEmailUpdate(
            $merchant['id'],
            $oldOwnerUser['id'],
            $existingUser['id'],
            $reAttachCurrentOwner,
            'primary'
        );

        // if current owner is owner for X : assert Role for banking product
        if ($isCurrentOwnerOnX === true)
        {
            $this->assertRolesOfOldAndNewOwnersForMerchantEmailUpdate(
                $merchant['id'],
                $oldOwnerUser['id'],
                $existingUser['id'],
                $reAttachCurrentOwner,
                'banking'
            );
        }

        $this->assertMerchantContactEmailForEmailUpdate($merchant['id'], $setContactEmail);
    }

    protected function assertCacheDataForMerchantEmailUpdate($merchantId, $expectedCacheData)
    {
        $app = App::getFacadeRoot();

        $cacheData = $app['cache']->get('merchant_email_update_' . $merchantId);

        $this->assertEquals($expectedCacheData, $cacheData);
    }

    protected function assertRolesOfOldAndNewOwnersForMerchantEmailUpdate($merchantId, $oldOwnerUserId, $newOwnerUserId, $reAttachOldOwner, $product = 'primary')
    {
        $oldOwnerMerchantMapping = $this->getDBMerchantUserMapping($merchantId, $oldOwnerUserId, $product);

        $newOwnerMerchantMapping = $this->getDBMerchantUserMapping($merchantId, $newOwnerUserId, $product);

        $this->assertEquals('owner', $newOwnerMerchantMapping->role);

        if ($reAttachOldOwner === true)
        {
            switch ($product)
            {
                case 'primary':
                    $this->assertEquals('manager', $oldOwnerMerchantMapping->role);
                    break;

                case 'banking':
                    $this->assertEquals('finance_l1', $oldOwnerMerchantMapping->role);
            }
        }
        else
        {
            $this->assertEmpty($oldOwnerMerchantMapping);
        }
    }

    protected function assertMerchantContactEmailForEmailUpdate($merchantId, $setContactEmail)
    {
        $merchant =  $this->getDbEntityById('merchant', $merchantId);

        if ( $setContactEmail === true)
        {
            $this->assertEquals('newowner@gmail.com', $merchant->getEmail());
        }
        else
        {
            $this->assertEquals('oldcontact@gmail.com', $merchant->getEmail());
        }
    }

    protected function getDBMerchantUserMapping($merchantId, $userId, $product = 'primary')
    {
        return  DB::table('merchant_users')
            ->where('user_id', '=', $userId)
            ->where('merchant_id', $merchantId)
            ->where('product', $product)
            ->first();
    }

    protected function assertOldAndNewOwnerAttributes($newOwnerUserId, $oldOwnerUserId)
    {
        $newOwnerUser = $this->getDbEntityById('user', $newOwnerUserId);

        $oldOwnerUser = $this->getDbEntityById('user', $oldOwnerUserId);

        $isPasswordEqual = (new BcryptHasher)->check('New124@user', $newOwnerUser->getPassword());
        $this->assertTrue($isPasswordEqual);

        $this->assertEquals('newowner@gmail.com', $newOwnerUser->getEmail());
        $this->assertNull($newOwnerUser->getPasswordResetToken());

        $this->assertEquals($oldOwnerUser->getName(), $newOwnerUser->getName());
        $this->assertEquals($oldOwnerUser->isContactMobileVerified(), $newOwnerUser->isContactMobileVerified());
        $this->assertEquals($oldOwnerUser->getContactMobile(), $newOwnerUser->getContactMobile());
    }

    public function testEnableEsScheduledSuccessUpdatesOnDemandPricing()
    {
        // We expect a mail to be shot to merchant every time Es schedule enable succeeds
        Mail::fake();

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1A0Fkd38fGZPVC']);

        // Update pricing rule percent rate to 23 and later test it to have 15
        $this->fixtures->pricing->edit('1zE31zbyeGCTd9', ['percent_rate' => 23]);

        $this->fixtures->pricing->edit('1zE31zbyeGCTe1', ['percent_rate' => 23]);

        $this->fixtures->create(
            'schedule',
            [
                'id'       => '100001schedule',
                'period'   => 'hourly',
                'interval' => 1,
                'anchor'   => null,
                'delay'    => 0,
                'hour'     => 0,
                'name'     => 'demo',
            ]);

        $scheduleTaskCard = [
            'method'        => 'card',
            'international' => 1,
            'entity_type'   =>'merchant'
        ];

        $scheduleTaskUpi = [
            'method'        => 'upi',
            'international' => 0,
            'entity_type'   =>'merchant'
        ];

        $this->fixtures->create(
            'schedule_task',
            $scheduleTaskCard);

        $this->fixtures->create(
            'schedule_task',
            $scheduleTaskUpi);

        $this->fixtures->merchant->addFeatures(['es_on_demand']);

        $this->ba->proxyAuthTest();

        $this->startTest();

        $features = $this->getEntities('feature', [], true);

        $this->assertCount(2, $features['items']);

        $this->assertEquals('es_automatic', $features['items'][0]['name']);

        $this->assertEquals('es_on_demand', $features['items'][1]['name']);

        $settlementOndemandPricingRule = $this->getDbEntities('pricing', ['feature'         => 'settlement_ondemand',
                                                                           'payment_method' => 'fund_transfer',
                                                                           'plan_id'        => '1A0Fkd38fGZPVC'])->toArray();

        $ondemandPayoutPricingRule = $this->getDbEntities('pricing', ['feature'         => 'payout',
                                                                      'payment_method'  => 'fund_transfer',
                                                                      'plan_id'         => '1A0Fkd38fGZPVC'])->toArray();

        $this->assertEquals('15', $settlementOndemandPricingRule[0]['percent_rate']);

        $this->assertEquals('15', $ondemandPayoutPricingRule[0]['percent_rate']);

        // In this case we expect only one mail is queued
        Mail::assertQueued(EsEnabledNotify::class, 1);

        Mail::assertQueued(EsEnabledNotify::class, function ($mail)
        {
            $this->assertEquals(EsEnabledNotify::MERCHANT_MAILER_VIEW,$mail->view);

            $this->assertEquals(EsEnabledNotify::MERCHANT_MAILER_SUBJECT, $mail->subject);

            $this->assertArrayKeysExist($mail->viewData,
                [EsEnabledNotify::TO_EMAIL, EsEnabledNotify::TO_NAME, EsEnabledNotify::SUBJECT, EsEnabledNotify::VIEW, Pricing\Entity::PERCENT_RATE]);

            return ($mail->hasFrom(self::CAPITAL_SUPPORT_EMAIL)) and
                ($mail->hasTo(self::CAPITAL_SUPPORT_EMAIL));
        });
    }

    public function testEnableEsScheduledSuccessUpdatesOnDemandPricingReplicatesPlan()
    {
        // We expect a mail to be shot to merchant every time Es schedule enable succeeds
        Mail::fake();

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1A0Fkd38fGZPVC', 'international' => 0]);

        $merchant2 = $this->fixtures->create('merchant');

        $merchant2Id = $merchant2['id'];

        $this->fixtures->merchant->edit($merchant2Id, ['pricing_plan_id' => '1A0Fkd38fGZPVC', 'international' => 0]);

        // Update pricing rule percent rate to 23 and later test it to have 15
        $this->fixtures->pricing->edit('1zE31zbyeGCTd9', ['percent_rate' => 23]);

        $this->fixtures->pricing->edit('1zE31zbyeGCTe1', ['percent_rate' => 23]);

        $this->fixtures->create(
            'schedule',
            [
                'id'       => '100001schedule',
                'period'   => 'hourly',
                'interval' => 1,
                'anchor'   => null,
                'delay'    => 0,
                'hour'     => 0,
                'name'     => 'demo',
            ]);

        $scheduleTaskCard = [
            'method'        => 'card',
            'international' => 0,
            'entity_type'   =>'merchant'
        ];

        $scheduleTaskUpi = [
            'method'        => 'upi',
            'international' => 0,
            'entity_type'   =>'merchant'
        ];

        $this->fixtures->create(
            'schedule_task',
            $scheduleTaskCard);

        $this->fixtures->create(
            'schedule_task',
            $scheduleTaskUpi);

        $this->fixtures->merchant->addFeatures(['es_on_demand']);

        $this->ba->proxyAuthTest();

        $this->startTest();

        $features = $this->getEntities('feature', [], true);

        $this->assertCount(2, $features['items']);

        $this->assertEquals('es_automatic', $features['items'][0]['name']);

        $this->assertEquals('es_on_demand', $features['items'][1]['name']);

        $settlementOndemandPricingRule = $this->getDbEntities('pricing', ['feature'        => 'settlement_ondemand',
                                                                          'payment_method' => 'fund_transfer',
                                                                          'percent_rate'   => 15])->toArray();

        $ondemandPayoutPricingRule = $this->getDbEntities('pricing', ['feature'        => 'payout',
                                                                      'payment_method' => 'fund_transfer',
                                                                      'percent_rate'   => 15])->toArray();

        $this->assertNotEquals('1A0Fkd38fGZPVC', $settlementOndemandPricingRule[0]['plan_id']);

        $this->assertNotEquals('1A0Fkd38fGZPVC', $ondemandPayoutPricingRule[0]['plan_id']);

        // In this case we expect only one mail is queued
        Mail::assertQueued(EsEnabledNotify::class, 1);

        Mail::assertQueued(EsEnabledNotify::class, function ($mail)
        {
            $this->assertEquals(EsEnabledNotify::MERCHANT_MAILER_VIEW,$mail->view);

            $this->assertEquals(EsEnabledNotify::MERCHANT_MAILER_SUBJECT, $mail->subject);

            $this->assertArrayKeysExist($mail->viewData,
                [EsEnabledNotify::TO_EMAIL, EsEnabledNotify::TO_NAME, EsEnabledNotify::SUBJECT, EsEnabledNotify::VIEW, Pricing\Entity::PERCENT_RATE]);

            return ($mail->hasFrom(self::CAPITAL_SUPPORT_EMAIL)) and
                ($mail->hasTo(self::CAPITAL_SUPPORT_EMAIL));
        });
    }

    public function testEnableEsScheduledMailExpectedRoleTypesOnly()
    {
        // Es auto should enabled and mail should be sent to one of each role type
        Mail::fake();

        $unexpectedUserRole = array_random(self::USER_ROLES_UNAUTHORIZED_TO_RECEIVE_ES_SCHEDULED_MAILS);

        $userAlternateRole = 'admin';

        // We choose the sending user randomly from users who can send
        $userSendingRole = array_random(self::USER_ROLES_AUTHORIZED_TO_ENABLE_ES_SCHEDULED);

        $financeEmailId = 'finance@xyz.com';

        $alternateEmailId = 'thirdguy@xyz.com';

        $unexpectedUserEmailId = 'unexpected@xyz.com';

        $this->fixtures->create('pricing:standard_plan');

        $merchant = $this->fixtures->merchant->create();

        $merchantId = $merchant->getId();

        $this->fixtures->merchant->edit($merchantId, ['pricing_plan_id' => '1A0Fkd38fGZPVC']);

        $this->fixtures->merchant->addFeatures(['es_on_demand'], $merchantId);

        $this->fixtures->create(
            'schedule',
            [
                'id'       => '100001schedule',
                'period'   => 'hourly',
                'interval' => 1,
                'anchor'   => null,
                'delay'    => 0,
                'hour'     => 0,
                'name'     => 'demo',
            ]);

        $scheduleTaskCard = [
            'method'        => 'card',
            'international' => 1,
            'entity_type'   =>'merchant'
        ];

        $this->fixtures->create(
            'schedule_task',
            $scheduleTaskCard);

        // We create and attach a user with finance role. This guy is expected to receive the mail.
        $this->fixtures->create('user',['id' => 'MerchantUser99', 'email' => $financeEmailId, 'name' => 'FinanceMan']);

        $financeUser = $this->getDbEntityById('user','MerchantUser99', true);

        $this->ba->proxyAuth("rzp_test_{$merchantId}");

        (new UserCore)->updateUserMerchantMapping($financeUser, ['merchant_id' => $merchantId, 'role' => 'finance', 'product' => 'primary', 'action' => 'update']);
        $userSendingRole = 'admin';
        // If random selects sender as Admin then update sending users role to admin and alternate users role to owner
        if ($userSendingRole === 'admin')
        {
            $sendingUser = $this->getDbEntityById('user','MerchantUser01', true);

            (new UserCore)->updateUserMerchantMapping($sendingUser, ['merchant_id' => $merchantId, 'role' => $userSendingRole, 'product' => 'primary', 'action' => 'update']);

            $userAlternateRole = 'owner';
        }

        // Add the alternate guy from users who have access to enable es scheduled
        $this->fixtures->create('user',['id' => 'MerchantUser98', 'email' => $alternateEmailId, 'name' => 'AlternateGuy']);

        $alternateUser = $this->getDbEntityById('user','MerchantUser98', true);

        (new UserCore)->updateUserMerchantMapping($alternateUser, ['merchant_id' => $merchantId, 'role' => $userAlternateRole, 'product' => 'primary', 'action' => 'update']);

        // Add the last guy who is attached to the merchant but part of the groups who should not receive the mail
        $this->fixtures->create('user',['id' => 'MerchantUser97', 'email' => $unexpectedUserEmailId, 'name' => 'UnexpectedGuy']);

        $unexpectedUser = $this->getDbEntityById('user','MerchantUser97', true);

        (new UserCore)->updateUserMerchantMapping($unexpectedUser, ['merchant_id' => $merchantId, 'role' => $unexpectedUserRole, 'product' => 'primary', 'action' => 'update']);

        $this->startTest();

        Mail::assertQueued(EsEnabledNotify::class, function ($mail) use ($financeEmailId, $alternateEmailId, $unexpectedUserEmailId)
        {
            $this->assertEquals(EsEnabledNotify::MERCHANT_MAILER_VIEW, $mail->view);

            $this->assertEquals(EsEnabledNotify::MERCHANT_MAILER_SUBJECT, $mail->subject);

            $this->assertArrayKeysExist($mail->viewData,
                [EsEnabledNotify::TO_EMAIL, EsEnabledNotify::TO_NAME, EsEnabledNotify::SUBJECT, EsEnabledNotify::VIEW, Pricing\Entity::PERCENT_RATE]);

            return ($mail->hasTo($alternateEmailId)) and
                    ($mail->hasTo($financeEmailId) and
                    !($mail->hasTo($unexpectedUserEmailId)));
        });
    }

    public function testEnableEsScheduledUnauthorizedUserAccess()
    {
        // ES should neither be enabled nor any mail should be sent
        Mail::fake();

        $userRoleToBeSet = array_random(self::USER_ROLES_UNAUTHORIZED_TO_ENABLE_ES_SCHEDULED);

        $this->fixtures->create('pricing:standard_plan');

        $merchant = $this->fixtures->merchant->create();

        $merchantId = $merchant->getId();

        $this->fixtures->merchant->edit($merchantId, ['pricing_plan_id' => '1A0Fkd38fGZPVC']);

        $this->fixtures->merchant->addFeatures(['es_on_demand'], $merchantId);

        $this->fixtures->create(
            'schedule',
            [
                'id'       => '100001schedule',
                'period'   => 'hourly',
                'interval' => 1,
                'anchor'   => null,
                'delay'    => 0,
                'hour'     => 0,
                'name'     => 'demo',
            ]);

        $scheduleTaskCard = [
            'method'        => 'card',
            'international' => 1,
            'entity_type'   =>'merchant'
        ];

        $this->fixtures->create(
            'schedule_task',
            $scheduleTaskCard);

        $this->ba->proxyAuth("rzp_test_{$merchantId}");

        $user = $this->getDbEntityById('user','MerchantUser01', true);

        (new UserCore)->updateUserMerchantMapping($user, ['merchant_id' => $merchantId, 'role' => $userRoleToBeSet, 'product' => 'primary', 'action' => 'update']);

        $this->startTest();

        $features = $this->getEntities('feature', [], true);

        $this->assertCount(1, $features['items']);

        $this->assertEquals('es_on_demand', $features['items'][0]['name']);

        Mail::assertNotQueued(EsEnabledNotify::class);
    }

    public function testEnableEsScheduledUnknownScheduleFailure()
    {
        Mail::fake();

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1A0Fkd38fGZPVC']);

        $this->fixtures->merchant->addFeatures(['es_on_demand']);

        $this->ba->proxyAuthTest();

        $this->startTest();

        Mail::assertNotQueued(EsEnabledNotify::class);
    }

    public function testEnableEsScheduledUneditableFeature()
    {
        Mail::fake();

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1A0Fkd38fGZPVC']);

        $this->fixtures->create(
            'schedule',
            [
                'id'       => '100001schedule',
                'period'   => 'hourly',
                'interval' => 1,
                'anchor'   => null,
                'delay'    => 0,
                'hour'     => 0,
                'name'     => 'hh',
            ]);

        $this->ba->proxyAuthTest();

        $this->startTest();

        Mail::assertNotQueued(EsEnabledNotify::class);
    }

    public function testEnableEsScheduledEsautomaticPricingUnavailable()
    {
        Mail::fake();

        $this->fixtures->pricing->createTestPlanForNoOndemandAndEsAutomaticPricing();

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1BFFkd38fFGbnh']);

        $this->fixtures->create(
            'schedule',
            [
                'id'       => '100001schedule',
                'period'   => 'hourly',
                'interval' => 1,
                'anchor'   => null,
                'delay'    => 0,
                'hour'     => 0,
                'name'     => 'hh',
            ]);

        $this->fixtures->merchant->addFeatures(['es_on_demand']);

        $this->ba->proxyAuthTest();

        $this->startTest();

        $esAutomaticPricingRules = $this->getDbEntities('pricing', ['feature'        => 'esautomatic',
                                                                    'plan_id'        => '1BFFkd38fFGbnh',])->toArray();

        $this->assertEquals(sizeof($esAutomaticPricingRules), 11);

        Mail::assertQueued(EsEnabledNotify::class);
    }

    public function testPutEmiMethod()
    {
        $this->fixtures->create('pricing:emi_pricing_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach('10000000000000');

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testGetMerchantPaymentFailureAnalysis()
    {
        $this->createPaymentsForFailureAnalysis();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testGetMerchantPaymentFailureAnalysisInvalidRangeFail()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testPutPaytmCardNetworkAndEMIMethodWithUpdateObserverData()
    {
        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                              function ($actionId, $feature, $mode)
                              {
                                  if($feature == "perform_action_on_workflow_observer_data")
                                  {
                                      return 'on';
                                  }
                                  else
                                  {
                                      return 'control';
                                  }
                              }) );

        $this->fixtures->create('pricing:emi_pricing_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $this->fixtures->merchant->enableEmiCredit('10000000000000');

        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach('10000000000000');

        $this->ba->adminAuth();

        $this->setupWorkflow('Payment Method Workflow', PermissionName::EDIT_MERCHANT_METHODS, "test");

        $response = $this->startTest();

        $this->assertStringStartsWith('w_action_', $response['id']);

        $this->esClient->indices()->refresh();

        $this->updateObserverData($response['id'],  [
            'ticket_id'     => '123',
            'fd_instance'   => 'rzp'
        ]);

        $this->fixtures->create('merchant_freshdesk_tickets', $this->getDefaultFreshdeskArray());

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $this->esClient->indices()->refresh();

        $this->setUpFreshdeskClientMock();

        $this->expectFreshdeskRequestAndRespondWith('tickets/123/reply', 'post',
                                                    [
                                                        'body' => implode("<br><br>",(new PaymentMethodChangeObserver([
                                                                                                                          Entity::ENTITY_ID => '10000000000000',
                                                                                                                          Entity::PAYLOAD=>[
                                                                                                                              'paytm'     =>  1,
                                                                                                                              'emi' =>    ['credit' =>"0", 'debit' => "1"],
                                                                                                                              'card_networks' =>  [
                                                                                                                                  'AMEX' => "1"
                                                                                                                              ]
                                                                                                                          ]]))->getTicketReplyContent(ObserverConstants::APPROVE,'10000000000000')),
                                                    ],
                                                    []);

        $this->expectFreshdeskRequestAndRespondWith('tickets/123?include=requester', 'GET',
                                                    [],
                                                    [
                                                        'id'        => '123',
                                                        'tags'      => ['xyz']
                                                    ]);

        $this->expectFreshdeskRequestAndRespondWith('tickets/123', 'PUT',
                                                    [
                                                        'status'    => 4,
                                                        'tags'      => ['xyz','automated_workflow_response']
                                                    ],
                                                    [
                                                        'id'            => '123',
                                                    ]);

        $this->performWorkflowAction($workflowAction['id'], true);

        $request = [
            'url' => '/merchant/methods',
            'method' => 'get',
        ];

        $this->fixtures->create('merchant_detail',
                                [
                                    'merchant_id' => '10000000000000',
                                    'business_registered_address'   => 'ksjdnfk akejnffn',
                                    'business_registered_state'     => 'karnanata',
                                    'business_registered_city'      => 'bengaluru',
                                    'business_registered_pin'       => '12345457',
                                    'contact_mobile'                => '124098598978',
                                ]);

        $this->ba->proxyAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertArraySelectiveEquals(['wallet' =>['paytm' => true ],"card_networks"=>["AMEX"=> 1]], $response);

        $merchantMethods = $this->getDbEntityById('merchant', '10000000000000')->getMethods();

        $this->assertFalse($merchantMethods->isCreditEmiEnabled());

        $this->assertTrue($merchantMethods->isDebitEmiEnabled());
    }

    public function testPutEMIMethodWithUpdateObserverData()
    {
        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                              function ($actionId, $feature, $mode)
                              {
                                  if($feature == "perform_action_on_workflow_observer_data")
                                  {
                                      return 'on';
                                  }
                                  else
                                  {
                                      return 'control';
                                  }
                              }) );

        $this->fixtures->create('pricing:emi_pricing_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $this->fixtures->merchant->enableEmiCredit('10000000000000');

        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach('10000000000000');

        $this->ba->adminAuth();

        $this->setupWorkflow('Payment Method Workflow', PermissionName::EDIT_MERCHANT_METHODS, "test");

        $response = $this->startTest();

        $this->assertStringStartsWith('w_action_', $response['id']);

        $this->esClient->indices()->refresh();

        $this->updateObserverData($response['id'],  [
            'ticket_id'     => '123',
            'fd_instance'   => 'rzp'
        ]);

        $this->fixtures->create('merchant_freshdesk_tickets', $this->getDefaultFreshdeskArray());

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $this->esClient->indices()->refresh();

        $this->setUpFreshdeskClientMock();

        $this->expectFreshdeskRequestAndRespondWith('tickets/123/reply', 'post',
                                                    [
                                                        'body' => implode("<br><br>",(new PaymentMethodChangeObserver([
                                                                                                                          Entity::ENTITY_ID => '10000000000000',
                                                                                                                          Entity::PAYLOAD=>[
                                                                                                                              'emi' =>    ['credit' =>"0", 'debit' => "1"],
                                                                                                                          ]]))->getTicketReplyContent(ObserverConstants::APPROVE,'10000000000000')),
                                                    ],
                                                    []);

        $this->expectFreshdeskRequestAndRespondWith('tickets/123?include=requester', 'GET',
                                                    [],
                                                    [
                                                        'id'        => '123',
                                                        'tags'      => ['xyz']
                                                    ]);

        $this->expectFreshdeskRequestAndRespondWith('tickets/123', 'PUT',
                                                    [
                                                        'status'    => 4,
                                                        'tags'      => ['xyz','automated_workflow_response']
                                                    ],
                                                    [
                                                        'id'            => '123',
                                                    ]);

        $this->performWorkflowAction($workflowAction['id'], true);

        $this->ba->proxyAuth();

        $merchantMethods = $this->getDbEntityById('merchant', '10000000000000')->getMethods();

        $this->assertFalse($merchantMethods->isCreditEmiEnabled());

        $this->assertTrue($merchantMethods->isDebitEmiEnabled());
    }

    public function testPutPaytmMethodWithUpdateObserverData()
    {
        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                              function ($actionId, $feature, $mode)
                              {
                                  if($feature == "perform_action_on_workflow_observer_data")
                                  {
                                      return 'on';
                                  }
                                  else
                                  {
                                      return 'control';
                                  }
                              }) );

        $this->fixtures->create('pricing:emi_pricing_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $this->fixtures->merchant->enableEmiCredit('10000000000000');

        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach('10000000000000');

        $this->ba->adminAuth();

        $this->setupWorkflow('Payment Method Workflow', PermissionName::EDIT_MERCHANT_METHODS, "test");

        $response = $this->startTest();

        $this->assertStringStartsWith('w_action_', $response['id']);

        $this->esClient->indices()->refresh();

        $this->updateObserverData($response['id'],  [
            'ticket_id'     => '123',
            'fd_instance'   => 'rzp'
        ]);

        $this->fixtures->create('merchant_freshdesk_tickets', $this->getDefaultFreshdeskArray());

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $this->esClient->indices()->refresh();

        $this->setUpFreshdeskClientMock();

        $this->expectFreshdeskRequestAndRespondWith('tickets/123/reply', 'post',
                                                    [
                                                        'body' => implode("<br><br>",(new PaymentMethodChangeObserver([
                                                                                                                          Entity::ENTITY_ID => '10000000000000',
                                                                                                                          Entity::PAYLOAD=>[
                                                                                                                              'paytm'     =>  1,
                                                                                                                          ]]))->getTicketReplyContent(ObserverConstants::APPROVE,'10000000000000')),
                                                    ],
                                                    []);

        $this->expectFreshdeskRequestAndRespondWith('tickets/123?include=requester', 'GET',
                                                    [],
                                                    [
                                                        'id'        => '123',
                                                        'tags'      => ['xyz']
                                                    ]);

        $this->expectFreshdeskRequestAndRespondWith('tickets/123', 'PUT',
                                                    [
                                                        'status'    => 4,
                                                        'tags'      => ['xyz','automated_workflow_response']
                                                    ],
                                                    [
                                                        'id'            => '123',
                                                    ]);

        $this->performWorkflowAction($workflowAction['id'], true);

        $request = [
            'url' => '/merchant/methods',
            'method' => 'get',
        ];

        $this->fixtures->create('merchant_detail',
                                [
                                    'merchant_id' => '10000000000000',
                                    'business_registered_address'   => 'ksjdnfk akejnffn',
                                    'business_registered_state'     => 'karnanata',
                                    'business_registered_city'      => 'bengaluru',
                                    'business_registered_pin'       => '12345457',
                                    'contact_mobile'                => '124098598978',
                                ]);

        $this->ba->proxyAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertArraySelectiveEquals(['wallet' =>['paytm' => true ]], $response);
    }

    public function testPutCardNetworkMethodWithUpdateObserverData()
    {
        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                              function ($actionId, $feature, $mode)
                              {
                                  if($feature == "perform_action_on_workflow_observer_data")
                                  {
                                      return 'on';
                                  }
                                  else
                                  {
                                      return 'control';
                                  }
                              }) );

        $this->fixtures->create('pricing:emi_pricing_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $this->fixtures->merchant->enableEmiCredit('10000000000000');

        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach('10000000000000');

        $this->ba->adminAuth();

        $this->setupWorkflow('Payment Method Workflow', PermissionName::EDIT_MERCHANT_METHODS, "test");

        $response = $this->startTest();

        $this->assertStringStartsWith('w_action_', $response['id']);

        $this->esClient->indices()->refresh();

        $this->updateObserverData($response['id'],  [
            'ticket_id'     => '123',
            'fd_instance'   => 'rzp'
        ]);

        $this->fixtures->create('merchant_freshdesk_tickets', $this->getDefaultFreshdeskArray());

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $this->esClient->indices()->refresh();

        $this->setUpFreshdeskClientMock();

        $this->expectFreshdeskRequestAndRespondWith('tickets/123/reply', 'post',
                                                    [
                                                        'body' => implode("<br><br>",(new PaymentMethodChangeObserver([
                                                                                                                          Entity::ENTITY_ID => '10000000000000',
                                                                                                                          Entity::PAYLOAD=>[
                                                                                                                              'card_networks' =>  [
                                                                                                                                  'AMEX' => "1"
                                                                                                                              ]
                                                                                                                          ]]))->getTicketReplyContent(ObserverConstants::APPROVE,'10000000000000')),
                                                    ],
                                                    []);

        $this->expectFreshdeskRequestAndRespondWith('tickets/123?include=requester', 'GET',
                                                    [],
                                                    [
                                                        'id'        => '123',
                                                        'tags'      => ['xyz']
                                                    ]);

        $this->expectFreshdeskRequestAndRespondWith('tickets/123', 'PUT',
                                                    [
                                                        'status'    => 4,
                                                        'tags'      => ['xyz','automated_workflow_response']
                                                    ],
                                                    [
                                                        'id'            => '123',
                                                    ]);

        $this->performWorkflowAction($workflowAction['id'], true);

        $request = [
            'url' => '/merchant/methods',
            'method' => 'get',
        ];

        $this->fixtures->create('merchant_detail',
                                [
                                    'merchant_id' => '10000000000000',
                                    'business_registered_address'   => 'ksjdnfk akejnffn',
                                    'business_registered_state'     => 'karnanata',
                                    'business_registered_city'      => 'bengaluru',
                                    'business_registered_pin'       => '12345457',
                                    'contact_mobile'                => '124098598978',
                                ]);

        $this->ba->proxyAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertArraySelectiveEquals(["card_networks"=>["AMEX"=> 1]], $response);
    }

    public function testGetKeySecret()
    {
        $this->ba->expressAuth();

        $this->startTest();
    }

    public function testQueryCacheHitForKeyAndMerchant()
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
                if (starts_with($tag, 'key') === true)
                {
                    $this->assertEquals('key_TheTestAuthKey', $tag);
                }
                else if (starts_with($tag, 'merchant') === true)
                {
                    $this->assertEquals('merchant_10000000000000', $tag);
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
                else if (starts_with($tag, 'merchant') === true)
                {
                    $this->assertEquals('merchant_10000000000000', $tag);

                    $this->assertEquals('10000000000000', $e->value[0]->id);
                }
            }

            return true;
        });

        //
        // Asserts cache should not have been hit the first time
        //
        Event::assertNotDispatched(CacheHit::class, function($e) {
            foreach ($e->tags as $tag) {
                $this->assertNotEquals('merchant_10000000000000', $tag);
                $this->assertNotEquals('key_TheTestAuthKey', $tag);
            }
            return false;
        });

        $this->doAuthPayment($payment);

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
                else if (starts_with($tag, 'merchant') === true)
                {
                    $this->assertEquals('merchant_10000000000000', $tag);

                    $this->assertEquals('10000000000000', $e->value[0]->id);
                }
            }

            return true;
        });
    }

    public function testQueryCacheFlushForKeyAndMerchant()
    {
        config(['app.query_cache.mock' => false]);

        Event::fake(false);

        $this->ba->proxyAuthTest();

        $testData = $this->testData['testUpdateKeyExpireNow'];

        $payment = $this->getDefaultPaymentArray();

        //
        // Expires default key
        //
        $content = $this->runRequestResponseFlow($testData);

        Event::assertDispatched(KeyForgotten::class, function ($e) use ($content)
        {
            $expectedTags = [
                'key_TheTestAuthKey',
            ];

            $this->assertArraySelectiveEquals($expectedTags, $e->tags);

            return true;
        });

        $newKey = $content['new']['id'];

        $this->doAuthPayment($payment, null, $newKey);

        Key\Entity::stripSign($newKey);

        //
        // Repeats the sequence of assertions, to test that new key is properly
        // read from cache
        //
        Event::assertDispatched(CacheMissed::class, function ($e) use ($newKey)
        {
            foreach ($e->tags as $tag)
            {
                if (starts_with($tag, 'key') === true)
                {
                    $this->assertEquals('key_' . $newKey, $tag);
                }
                else if (starts_with($tag, 'merchant') === true)
                {
                    $this->assertEquals('merchant_10000000000000', $tag);
                }
            }

            return true;
        });

        Event::assertDispatched(KeyWritten::class, function ($e) use ($newKey)
        {
            foreach ($e->tags as $tag)
            {
                if (starts_with($tag, 'key') === true)
                {
                    $this->assertEquals('key_' . $newKey, $tag);

                    $this->assertEquals($newKey, $e->value[0]->id);
                }
                else if (starts_with($tag, 'merchant') === true)
                {
                    $this->assertEquals('merchant_10000000000000', $tag);

                    $this->assertEquals('10000000000000', $e->value[0]->id);
                }
            }

            return true;
        });
    }

    public function testBeneficiaryRegisterYesbank()
    {
        Mail::fake();

        $md = $this->fixtures->create('merchant_detail',
            [
                'merchant_id' => '10000000000000',
                'business_registered_address'   => 'ksjdnfk akejnffn',
                'business_registered_state'     => 'karnanata',
                'business_registered_city'      => 'bengaluru',
                'business_registered_pin'       => '12345457',
                'contact_mobile'                => '124098598978',
            ]);

        $this->ba->adminAuth();

        $request = [
            'url'       => '/merchants/beneficiary/file/yesbank',
            'method'    => 'post',
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('register_count', $content);

        $this->assertArrayHasKey('total_count', $content);

        $this->assertEquals(Channel::YESBANK, $content['channel']);

        Mail::assertQueued(BeneficiaryFileMail::class);
    }

    public function testBeneficiaryRegisterKotak()
    {
        Mail::fake();

        $md = $this->fixtures->create('merchant_detail',
            [
                'merchant_id' => '10000000000000',
                'business_registered_address'   => 'ksjdnfk akejnffn',
                'business_registered_state'     => 'karnanata',
                'business_registered_city'      => 'bengaluru',
                'business_registered_pin'       => '12345457',
                'contact_mobile'                => '124098598978',
            ]);

        $this->ba->adminAuth();

        $request = [
            'url'       => '/merchants/beneficiary/file/kotak',
            'method'    => 'post',
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('signed_url', $content);
        $this->assertEquals(Channel::KOTAK, $content['channel']);

        Mail::assertQueued(BeneficiaryFileMail::class);
    }

    public function testBeneficiaryRegisterBetweenTimestampKotak()
    {
        Mail::fake();

        // Choosing a non-holiday, and previous day is also not holiday
        $thirdJan2017 = Carbon::createFromDate(2017, 1, 3, Timezone::IST);

        $thirdJan2017Timestamp = $thirdJan2017->timestamp;

        Carbon::setTestNow($thirdJan2017);

        $ba1 = $this->fixtures->create('bank_account', ['created_at' => $thirdJan2017Timestamp - 2]);
        $ba2 = $this->fixtures->create('bank_account', ['created_at' => $thirdJan2017Timestamp - 10]);
        $ba3 = $this->fixtures->create('bank_account', ['created_at' => $thirdJan2017Timestamp + 50]);

        $md = $this->fixtures->create('merchant_detail',
            [
                'merchant_id' => '10000000000000',
                'business_registered_address'   => 'ksjdnfk akejnffn',
                'business_registered_state'     => 'karnanata',
                'business_registered_city'      => 'bengaluru',
                'business_registered_pin'       => '12345457',
                'contact_mobile'                => '124098598978',
            ]);

        $this->ba->cronAuth();

        $request = [
            'url'       => '/merchants/beneficiary/file/bank/kotak',
            'method'    => 'post',
            'content'   => [
                BankAccount::ON => $thirdJan2017Timestamp,
                BankAccount::RECIPIENT_EMAILS => ['abc@d.com', 'efg@h.com'],
            ]
        ];

        $content = $this->makeRequestAndGetContent($request);

        Carbon::setTestNow();

        $this->assertArrayHasKey('signed_url', $content);
        $this->assertEquals(2, $content['register_count']);
        $this->assertEquals(2, $content['total_count']);
        $this->assertEquals(Channel::KOTAK, $content['channel']);

        Mail::assertQueued(BeneficiaryFileMail::class, function ($mail)
        {
            return $mail->hasTo(['abc@d.com', 'efg@h.com']);
        });
    }

    public function testBeneficiaryRegisterAxis()
    {
        Mail::fake();

        $this->ba->adminAuth();

        $md = $this->fixtures->create('merchant_detail',
            [
                'merchant_id' => '10000000000000',
                'business_registered_address'   => 'ksjdnfk akejnffn',
                'business_registered_state'     => 'karnanata',
                'business_registered_city'      => 'bengaluru',
                'business_registered_pin'       => '12345457',
                'contact_mobile'                => '124098598978',
            ]);

        $request = [
            'url'       => '/merchants/beneficiary/file/axis',
            'method'    => 'post',
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('signed_url', $content);
        $this->assertEquals(Channel::AXIS, $content['channel']);

        Mail::assertQueued(BeneficiaryFileMail::class);
    }

    public function testBeneficiaryRegisterIcici()
    {
        Mail::fake();

        $this->ba->adminAuth();

        $md = $this->fixtures->create('merchant_detail',
            [
                'merchant_id' => '10000000000000',
                'business_registered_address'   => 'ksjdnfk akejnffn',
                'business_registered_state'     => 'karnanata',
                'business_registered_city'      => 'bengaluru',
                'business_registered_pin'       => '12345457',
                'contact_mobile'                => '124098598978',
            ]);

        $request = [
            'url'       => '/merchants/beneficiary/file/icici',
            'method'    => 'post',
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('signed_url', $content);
        $this->assertEquals(Channel::ICICI, $content['channel']);

        Mail::assertQueued(BeneficiaryFileMail::class);
    }

    public function testBeneficiaryRegisterForMerchant()
    {
        Mail::fake();

        $this->ba->adminAuth();

        $this->fixtures->merchant->create();

        $this->fixtures->create('merchant_detail',
            [
                'merchant_id' => '10000000000000',
                'business_registered_address'   => 'ksjdnfk akejnffn',
                'business_registered_state'     => 'karnanata',
                'business_registered_city'      => 'bengaluru',
                'business_registered_pin'       => '12345457',
                'contact_mobile'                => '124098598978',
            ]);

        $request = [
            'url'       => '/merchants/beneficiary/file/icici',
            'method'    => 'post',
            'content'   => [
                'merchant_ids' => [
                    '10000000000000'
                ]
            ]
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('signed_url', $content);
        $this->assertEquals(Channel::ICICI, $content['channel']);

        Mail::assertQueued(BeneficiaryFileMail::class);
    }

    public function testEditCredits()
    {
        $this->merchantEditCredits('10000000000000', '10000');

        $balance = $this->getEntityById('balance', '10000000000000', true);
        $this->assertEquals(10000, $balance['credits']);

        $nodalBalance = $this->getNodalAccountBalance();
        //$this->assertEquals(10000, $nodalBalance['credits']);

        $merchant = $this->fixtures->create('merchant:with_balance');
        $id = $merchant->getId();
        $this->merchantEditCredits($id, '20000');
        $balance = $this->getEntityById('balance', $id, true);
        $this->assertEquals(20000, $balance['credits']);

        $nodalBalance = $this->getNodalAccountBalance();
        //$this->assertEquals(30000, $nodalBalance['credits']);

        $this->merchantEditCredits('10000000000000', '5000');

        $balance = $this->getEntityById('balance', '10000000000000', true);
        $this->assertEquals(5000, $balance['credits']);

        $nodalBalance = $this->getNodalAccountBalance();
        //$this->assertEquals(25000, $nodalBalance['credits']);
    }

    public function testEditCreditsWrongFormat()
    {
        $this->runRequestResponseFlow(
            $this->testData[__FUNCTION__],
            function ()
            {
                $this->merchantEditCredits('10000000000000', 'abcde');
            });
    }

    public function testCreateMerchantWithLongName()
    {
        $id = '1X4hRFHFx4UiXt';
        $merchant = array(
            'id'    => $id,
            'name'  => 'Merchant business name just long enought to break things',
            'email' => 'liveandtest@localhost.com'
        );

        $request = array(
            'content' => $merchant,
            'url' => '/merchants',
            'method' => 'POST'
        );

        $content = $this->makeRequestAndGetContent($request);
    }

    protected function createPaymentsForFailureAnalysis()
    {
        // for error source : internal
        $this->fixtures->times(1)->create('payment:netbanking_created', [
            'merchant_id'         => '10000000000000',
            'method'              => 'card',
            'status'              => 'failed',
            'internal_error_code' => 'GATEWAY_ERROR_DUPLICATE_TRANSACTION',
            'created_at'          => 1632361753,
        ]);

        // for error source : issuer_bank
        $this->fixtures->times(2)->create('payment:netbanking_created', [
            'merchant_id'         => '10000000000000',
            'method'              => 'card',
            'status'              => 'failed',
            'internal_error_code' => 'BAD_REQUEST_CARD_DISABLED_FOR_ONLINE_PAYMENTS',
            'created_at'          => 1632361754,
        ]);

        // for error source : customer
        $this->fixtures->times(3)->create('payment:netbanking_created', [
            'merchant_id'         => '10000000000000',
            'method'              => 'card',
            'status'              => 'failed',
            'internal_error_code' => 'BAD_REQUEST_CARD_FROZEN',
            'created_at'          => 1632361755,
        ]);

        // for error source : business
        $this->fixtures->times(4)->create('payment:netbanking_created', [
            'merchant_id'         => '10000000000000',
            'method'              => 'card',
            'status'              => 'failed',
            'internal_error_code' => 'GATEWAY_ERROR_CARD_RUPAY_MAESTRO_NOT_ENABLED',
            'created_at'          => 1632361756,
        ]);

        // for non failed payment : authorized
        $this->fixtures->times(5)->create('payment:netbanking_created', [
            'merchant_id' => '10000000000000',
            'method'      => 'card',
            'status'      => 'authorized',
            'created_at'  => 1632361757,
        ]);

        // for non failed payment : captured
        $this->fixtures->times(6)->create('payment:netbanking_created', [
            'merchant_id' => '10000000000000',
            'method'      => 'card',
            'status'      => 'captured',
            'created_at'  => 1632361757,
        ]);

        // for non failed payment : refunded
        $this->fixtures->times(7)->create('payment:netbanking_created', [
            'merchant_id' => '10000000000000',
            'method'      => 'card',
            'status'      => 'refunded',
            'created_at'  => 1632361757,
        ]);
    }

    protected function startTest($testDataToReplace = [])
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        $this->replaceValuesRecursively($testData, $testDataToReplace);

        return $this->runRequestResponseFlow($testData);
    }

    protected function createMerchant($attributes = [])
    {
        $this->ba->adminAuth();

        $defaultAttributes = [
            'id'    => '1X4hRFHFx4UiXt',
            'name'  => 'Tester 2',
            'email' => 'liveandtest@localhost.com'
        ];

        $merchant = array_merge($defaultAttributes, $attributes);

        $request = [
            'content' => $merchant,
            'url' => '/merchants',
            'method' => 'POST'
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->merchantAssignPricingPlan('1hDYlICobzOCYt', $merchant['id']);


        $this->assertArraySelectiveEquals($merchant, $content);

        return $content;
    }

    protected function createUploadedFile($file)
    {
        $this->assertFileExists($file);

        $mimeType = 'image/png';
        $uploadedFile = new UploadedFile(
            $file,
            $file,
            $mimeType,
            filesize($file),
            null,
            true
        );

        return $uploadedFile;
    }

    public function testStoreImageAndGetLogoUrl()
    {
        $originalFile = $this->createUploadedFile('tests/Functional/Storage/a.png');
        copy($originalFile, 'tests/Functional/Storage/a2.png');
        $testFile = $this->createUploadedFile('tests/Functional/Storage/a2.png');

        $this->createMerchant();

        $this->ba->proxyAuth();

        $testData = $this->testData['testStoreImageAndGetLogoUrl'];

        $testData['request']['files']['logo'] = $testFile;

        $response = $this->runRequestResponseFlow($testData);

        $this->assertStringContainsString('/logos/', $response['logo_url']);
        $this->assertStringStartsWith('http', $response['logo_url']);
    }

    public function testGetGstin()
    {
        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id' => '10000000000000',
                'gstin' => '29AAGCR4375J1ZU'
            ]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testEditGstin()
    {
        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id' => '10000000000000',
            ]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testEditGstinInvalidRole()
    {
        $this->fixtures->create('user');

        $user = $this->getLastEntity('user', true);

        $this->fixtures->user->createUserMerchantMapping([
            'user_id'     => $user['id'],
            'merchant_id' => '10000000000000',
            'role'        => 'operations'
        ]);

        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id' => '10000000000000',
            ]);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user, 'operations');

        $this->startTest();
    }

    public function testDeleteLogoUrl()
    {
        // Check: Need to ensure logo exists. So create it first and then delete it
        // set dummy logo url for a default merchant

        $defaultMerchantId = '10000000000000';

        $defaultImgPath = '/logos/a.png';

        $merchant = $this->fixtures->merchant->setLogoUrl($defaultImgPath);

        $this->assertStringContainsString($defaultImgPath, $merchant->getLogoUrl());

        $testData = $this->testData['testDeleteLogoUrl'];

        $this->ba->proxyAuth();

        $response = $this->runRequestResponseFlow($testData);

        $this->assertEquals($defaultMerchantId, $response['id']);

        $this->assertEquals(null, $response['logo_url']);
    }

    public function testValidateImage()
    {
        $merchantValidator = new Merchant\Validator();

        $mimeType = 'image/jpeg';
        $extension = 'jpeg';

        $merchantValidator->validateImage($mimeType, $extension);

        $mimeType = 'image/gif';
        $extension = 'gif';

        $data = $this->testData['testValidateImage'];

        $this->runRequestResponseFlow($data, function() use ($merchantValidator, $mimeType, $extension)
        {
            $merchantValidator->validateImage($mimeType, $extension);
        });

        $mimeType = 'text/plain';
        $extension = 'jpeg';

        $this->runRequestResponseFlow($data, function() use ($merchantValidator, $mimeType, $extension)
        {
            $merchantValidator->validateImage($mimeType, $extension);
        });
    }

    public function testValidateLogo()
    {
        $merchantValidator = new Merchant\Validator();

        $imageDetails = ['size' => 1, 'width' => '1', 'height' => '1'];

        $data = $this->testData['testValidateLogoImageSmall'];

        $this->runRequestResponseFlow($data, function() use ($merchantValidator, $imageDetails)
        {
            $merchantValidator->validateLogo($imageDetails);
        });

        $data = $this->testData['testValidateLogoImageNotSquare'];

        $imageDetails = ['size' => 1, 'width' => '300', 'height' => '310'];

        $this->runRequestResponseFlow($data, function() use ($merchantValidator, $imageDetails)
        {
            $merchantValidator->validateLogo($imageDetails);
        });

        $imageDetails = ['size' => 1, 'width' => '300', 'height' => '300'];

        $merchantValidator->validateLogo($imageDetails);

        $imageDetails = ['size' => 1 + (1024 * 1024), 'width' => '300', 'height' => '300'];

        $data = $this->testData['testValidateLogoImageTooBig'];

        $this->runRequestResponseFlow($data, function() use ($merchantValidator, $imageDetails)
        {
            $merchantValidator->validateLogo($imageDetails);
        });
    }

    public function testScheduleTaskMigration()
    {
        $merchant = $this->createMerchant();

        $this->ba->cronAuth();
        $this->startTest();

        $scheduleTask = $this->getLastEntity('schedule_task', true);

        $this->assertEquals(null, $scheduleTask['method']);

        $this->ba->dashboardInternalAppAuth(null, 'live');

        $scheduleTask = $this->getLastEntity('schedule_task', true);

        $this->assertEquals(null, $scheduleTask['method']);
    }

    public function testAssignScheduleBulk()
    {
        $this->fixtures->create(
            'schedule',
            [
                'id'       => '100001schedule',
                'period'   => 'daily',
                'interval' => 1,
                'delay'    => 2,
                'name'     => 'Basic T2',
            ]);

        $this->setAdminForInternalAuth();

        $perm = $this->fixtures->create('permission', ['name' => 'schedule_assign_bulk']);

        $this->org->permissions()->sync($perm);

        $this->createMerchant(['id' => '1000000000test']);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testCreateMerchantWithAdmin()
    {
        $adminId = 'admin_' . Org::SUPER_ADMIN;

        $id = '1X4hRFHFx4UiXt';

        $merchant = [
            'id'       => $id,
            'name'     => 'Tester 2',
            'email'    => 'liveandtest@localhost.com',
            'admins'   => [$adminId],
        ];

        $request = [
            'content' => $merchant,
            'url'     => '/merchants',
            'method'  => 'POST'
        ];

        $this->ba->adminAuth();

        $content = $this->makeRequestAndGetContent($request);

        $row = DB::table('merchant_map')
            ->where('merchant_id', '=', $content['id'])
            ->where('entity_id', '=', Org::SUPER_ADMIN)
            ->where('entity_type', '=', 'admin')
            ->first();

        $this->assertNotNull($row);
    }

    public function testCreateNbRecurringTokenPreferencesRoute()
    {
        $this->markTestSkipped();
        $this->fixtures->create('terminal:shared_netbanking_icici_recurring_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->create('customer');

        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $response = $this->makePreferencesRouteRequest();

        $expectedTokenCount = $response['customer']['tokens']['count'];

        $payment = $this->getEmandateNetbankingRecurringPaymentArray('ICIC');
        unset($payment['card']);

        // We create a new nb recurring token via payment
        $this->doAuthPayment($payment);

        // Asserting that token was created, using Netbanking ICICI's SI Ref ID
        $netbanking = $this->getLastEntity('netbanking', true);
        $this->assertEquals('ICIC', $netbanking['bank']);
        $token = $this->getLastEntity('token', true);
        $this->assertEquals($netbanking['si_token'], $token['gateway_token']);

        $response = $this->makePreferencesRouteRequest();

        // We expect that the token created above is not sent in the preferences response
        $this->assertEquals($expectedTokenCount, $response['customer']['tokens']['count']);
    }

    protected function makePreferencesRouteRequest()
    {
        $this->ba->publicAuth();

        $request = [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'contact' => '9918899029',
                'customer_id' => 'cust_100000customer'
            ],
        ];

        return $this->makeRequestAndGetContent($request);
    }

    protected function createUserMerchantMapping(string $userId, string $merchantId, string $role, $mode = 'test')
    {
        DB::connection($mode)->table('merchant_users')
            ->insert([
                'merchant_id' => $merchantId,
                'user_id'     => $userId,
                'role'        => $role,
                'created_at'  => 1493805150,
                'updated_at'  => 1493805150
            ]);
    }

    public function testUpdateSubmerchantEmail()
    {
        $user = $this->fixtures->create('user');

        $this->fixtures->create('merchant',[
            'id'     => '10000000000044',
            'name'   => 'Submerchant',
            'org_id' => '100000razorpay',
            'email'  => 'test@razorpay.com',
        ]);

        $merchant = Merchant\Entity::find('10000000000044');
        $merchant->reTag(['ref-10000000000000']);
        $merchant->saveOrFail();

        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach($merchant);

        $this->ba->adminAuth('test');

        $this->startTest();
    }

    public function testCreateSubmerchantLogin()
    {
        Mail::fake();

        $merchant = $this->fixtures->create('merchant', [
            'id'     => '10000000000040',
            'email'  => 'test1@razorpay.com',
        ]);

        $this->fixtures->merchant->addFeatures(['aggregator']);

        $merchant->reTag(['ref-10000000000000']);

        $this->ba->proxyAuth();

        $this->startTest();

        Mail::assertSent(PasswordResetMail::class, function ($mailable)
        {
            $mailData = $mailable->viewData;

            $this->assertNotEmpty($mailData['token']);

            $this->assertNotEmpty($mailData['org']);

            $this->assertTrue($mailable->hasTo('test1@razorpay.com'));

            return true;
        });

        Mail::assertNotQueued(MappedToAccount::class);
    }

    public function testCreateSubmerchantLoginByAdmin()
    {
        Mail::fake();

        $merchant = $this->fixtures->create('merchant', [
            'id'     => '10000000000040',
            'email'  => 'test1@razorpay.com',
        ]);

        $this->fixtures->merchant->addFeatures(['aggregator']);

        $merchant->reTag(['ref-10000000000000']);

        $this->ba->proxyAuth('rzp_test_10000000000000', null, 'manager');

        $this->startTest();

        Mail::assertSent(PasswordResetMail::class, function ($mailable)
        {
            $mailData = $mailable->viewData;

            $this->assertNotEmpty($mailData['token']);

            $this->assertNotEmpty($mailData['org']);

            $this->assertTrue($mailable->hasTo('test1@razorpay.com'));

            return true;
        });

        Mail::assertNotQueued(MappedToAccount::class);
    }

    public function testCreateSubmerchantLoginPartnerAppMissing()
    {
        $this->fixtures->create('merchant', [
            'id'     => '10000000000040',
            'email'  => 'test@razorpay.com',
        ]);

        $this->fixtures->user->createUserForMerchant('10000000000000', ['email' => 'test@razorpay.com']);

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'fully_managed']);

        $this->createOAuthApplication([
            'id'           => '10000000000App',
            'type'         => 'partner',
            'partner_type' => 'fully_managed',
            'deleted_at'   => Carbon::now()->timestamp]);

        $this->fixtures->create('merchant_access_map', ['merchant_id' => '10000000000040']);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCreateSubmerchantLoginDuplicate()
    {
        $merchant = $this->fixtures->create('merchant', [
            'id'     => '10000000000040',
            'email'  => 'test1@razorpay.com',
        ]);

        $this->fixtures->user->createUserForMerchant('10000000000000', ['email' => 'test@razorpay.com']);

        $this->fixtures->user->createUserForMerchant('10000000000040', ['email' => 'test1@razorpay.com']);

        $this->fixtures->merchant->addFeatures(['aggregator']);

        $merchant->reTag(['ref-10000000000000']);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCreateSubmerchantLoginUserExists()
    {
        Mail::fake();

        $merchant = $this->fixtures->create('merchant', [
            'id'     => '10000000000040',
            'email'  => 'test1@razorpay.com',
            'org_id' => Org::RZP_ORG,
        ]);

        $user = $this->fixtures->user->createUserForMerchant('10000000000000', ['email' => 'test@razorpay.com']);

        $this->fixtures->user->createUserMerchantMapping([
            'merchant_id' => '10000000000040',
            'user_id'     => $user['id'],
            'role'        => 'owner'
        ]);

        $user2 = $this->fixtures->create('user', ['email' => 'test1@razorpay.com']);

        $this->fixtures->merchant->addFeatures(['aggregator']);

        $merchant->reTag(['ref-10000000000000']);

        $this->ba->proxyAuth();

        $this->startTest();

        Mail::assertQueued(MappedToAccount::class, function ($mailable)
        {
            $mailData = $mailable->viewData;

            $this->assertRazorpayOrgMailData($mailData['data']);

            $this->assertNotEmpty($mailData['org']);

            $this->assertTrue($mailable->hasTo('test1@razorpay.com'));

            return true;
        });

        Mail::assertNotSent(PasswordResetMail::class);

        $mapping = $this->fixtures->user->getMerchantUserMapping($merchant['id'], $user2['id']);

        $this->assertEquals(1, count($mapping));
    }

    public function testCreateSubmerchantLoginUserExistsMailForCustomBrandinOrg()
    {
        Mail::fake();

        $this->testData[__FUNCTION__] = $this->testData['testCreateSubmerchantLoginUserExists'];

        $merchant = $this->fixtures->create('merchant', [
            'id'     => '10000000000040',
            'email'  => 'test1@razorpay.com',
        ]);

        $user = $this->fixtures->user->createUserForMerchant('10000000000000', ['email' => 'test@razorpay.com']);

        $this->fixtures->user->createUserMerchantMapping([
            'merchant_id' => '10000000000040',
            'user_id'     => $user['id'],
            'role'        => 'owner'
        ]);

        $org = $this->createCustomBrandingOrgAndAssignMerchant('10000000000000');

        $this->fixtures->create('user', ['email' => 'test1@razorpay.com']);

        $this->fixtures->merchant->addFeatures(['aggregator']);

        $merchant->reTag(['ref-10000000000000']);

        $this->ba->proxyAuth();

        $this->startTest();

        Mail::assertQueued(MappedToAccount::class, function ($mailable) use ($org)
        {
            $viewData = $mailable->viewData['data'];

            $this->assertCustomBrandingMailViewData($org, $viewData);

            $this->assertTrue($mailable->hasTo('test1@razorpay.com'));

            return true;
        });
    }

    /**
     * Creating a linked account(marketplace) login by the marketplace merchant,
     * this should throw exception
     */
    public function testCreateLinkedAccountLogin()
    {
        $this->fixtures->create('merchant',[
            'id'        => '10000000000040',
            'email'     => 'test@razorpay.com',
            'parent_id' => '10000000000000',
        ]);

        $this->fixtures->user->createUserForMerchant('10000000000000', ['email' => 'test@razorpay.com']);

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    /**
     * Creating a sub-merchant(partner program) login by a partner who
     * is also an aggregator
     */
    public function testCreateSubmerchantLoginPartnerWithMarketplace()
    {
        Mail::fake();

        $this->fixtures->create('merchant', [
            'id'     => '10000000000040',
            'email'  => 'test1@razorpay.com',
        ]);

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'fully_managed']);

        $this->createOAuthApplication(['id' => '10000000000App', 'type' => 'partner', 'partner_type'=> 'fully_managed']);

        $this->fixtures->create('merchant_access_map', ['merchant_id' => '10000000000040']);

        $this->ba->proxyAuth();

        $this->startTest();

        Mail::assertSent(PasswordResetMail::class, function ($mailable)
        {
            $mailData = $mailable->viewData;

            $this->assertNotEmpty($mailData['token']);

            $this->assertNotEmpty($mailData['org']);

            $this->assertTrue($mailable->hasTo('test1@razorpay.com'));

            return true;
        });

        Mail::assertNotQueued(MappedToAccount::class);
    }

    public function testAggregatorInviteSubMerchantToManageDash()
    {
        Mail::fake();

        $this->fixtures->create('merchant',[
            'id'     => '10000000000040',
            'email'  => 'test@razorpay.com',
        ]);

        $this->fixtures->user->createUserForMerchant('10000000000000', ['email' => 'test@razorpay.com']);

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'aggregator']);

        $this->createOAuthApplication(['id' => '10000000000App', 'type' => 'partner', 'partner_type' => 'aggregator']);

        $this->fixtures->create('merchant_access_map', ['merchant_id' => '10000000000040']);

        $this->ba->proxyAuth();

        $this->startTest();

        Mail::assertSent(PasswordResetMail::class, function ($mailable)
        {
            $mailData = $mailable->viewData;

            $this->assertNotEmpty($mailData['token']);

            $this->assertNotEmpty($mailData['org']);

            $this->assertTrue($mailable->hasTo('invite.owner@razorpay.com'));

            return true;
        });

        Mail::assertNotQueued(MappedToAccount::class);
    }

    public function testFullyManagedInviteSubMerchantToManageDash()
    {
        Mail::fake();

        $this->fixtures->create('merchant',[
            'id'     => '10000000000040',
            'email'  => 'test@razorpay.com',
        ]);

        $this->fixtures->user->createUserForMerchant('10000000000000', ['email' => 'test@razorpay.com']);

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'fully_managed']);

        $this->createOAuthApplication(['id' => '10000000000App', 'type' => 'partner', 'partner_type' => 'fully_managed']);

        $this->fixtures->create('merchant_access_map', ['merchant_id' => '10000000000040']);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testAggregatorInviteSubMerchantToManageDash2Owners()
    {
        $this->fixtures->create('merchant',[
            'id'     => '10000000000040',
            'email'  => 'test@razorpay.com',
        ]);

        $user = $this->fixtures->user->createUserForMerchant('10000000000000', ['email' => 'test@razorpay.com']);

        $this->fixtures->user->createUserMerchantMapping([
            'user_id'     => $user->getId(),
            'merchant_id' => '10000000000040',
            'role'        => 'owner']);

        $this->fixtures->user->createUserForMerchant('10000000000040', ['email' => 'test1@razorpay.com']);

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'aggregator']);

        $this->createOAuthApplication(['id' => '10000000000App', 'type' => 'partner', 'partner_type' => 'aggregator']);

        $this->fixtures->create('merchant_access_map', ['merchant_id' => '10000000000040']);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testAggregatorInviteSubMerchantToManageDashAlreadyOwner()
    {
        $this->fixtures->create('merchant',[
            'id'     => '10000000000040',
            'email'  => 'test@razorpay.com',
        ]);

        $this->fixtures->user->createUserForMerchant('10000000000000', ['email' => 'test@razorpay.com']);

        $this->fixtures->user->createUserForMerchant('10000000000040', ['email' => 'invite.owner@razorpay.com']);

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'aggregator']);

        $this->createOAuthApplication(['id' => '10000000000App', 'type' => 'partner', 'partner_type' => 'aggregator']);

        $this->fixtures->create('merchant_access_map', ['merchant_id' => '10000000000040']);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    /**
     * Sub-merchant's registered email is different from partner but does not
     * have user with the same email as registered email, invite using
     * registered email.
     */
    public function testAggregatorInviteSubMerchantToManageDashEmailDifferent()
    {
        Mail::fake();

        $this->fixtures->create('merchant',[
            'id'     => '10000000000040',
            'email'  => 'testnew@razorpay.com',
        ]);

        $this->fixtures->user->createUserForMerchant('10000000000000', ['email' => 'test@razorpay.com']);

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'aggregator']);

        $this->createOAuthApplication(['id' => '10000000000App', 'type' => 'partner', 'partner_type' => 'aggregator']);

        $this->fixtures->create('merchant_access_map', ['merchant_id' => '10000000000040']);

        $this->ba->proxyAuth();

        $this->startTest();

        Mail::assertSent(PasswordResetMail::class, function ($mailable)
        {
            $mailData = $mailable->viewData;

            $this->assertNotEmpty($mailData['token']);

            $this->assertNotEmpty($mailData['org']);

            $this->assertTrue($mailable->hasTo('testnew@razorpay.com'));

            return true;
        });

        Mail::assertNotQueued(MappedToAccount::class);
    }

    /**
     * Submerchant's email is different from partner and partner email is invited
     * for login as owner. Any other email would also fail, test emphasizes that
     * even partner email is not allowed in these cases.
     */
    public function testAggregatorInviteEmailDifferentSubLoginPartnerEmail()
    {
        $this->fixtures->create('merchant',[
            'id'     => '10000000000040',
            'email'  => 'testnew@razorpay.com',
        ]);

        $this->fixtures->user->createUserForMerchant('10000000000000', ['email' => 'test@razorpay.com']);

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'aggregator']);

        $this->createOAuthApplication(['id' => '10000000000App', 'type' => 'partner', 'partner_type' => 'aggregator']);

        $this->fixtures->create('merchant_access_map', ['merchant_id' => '10000000000040']);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testOldAggregatorInviteSubMerchantUserWithEmail()
    {
        $this->fixtures->create('merchant',[
            'id'     => '10000000000040',
            'email'  => 'test1@razorpay.com',
        ]);

        $user = $this->fixtures->user->createUserForMerchant('10000000000000', ['email' => 'test@razorpay.com']);

        $this->fixtures->user->createUserMerchantMapping([
            'user_id'     => $user->getId(),
            'merchant_id' => '10000000000040',
            'role'        => 'owner']);

        $this->fixtures->merchant->addFeatures(['aggregator']);

        $merchant = Merchant\Entity::find('10000000000040');

        $merchant->reTag(['ref-10000000000000']);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testBeneficiaryRegisterYesbankBetweenTimestamps()
    {
        Mail::fake();

        $this->fixtures->create('bank_account');

        $this->ba->cronAuth();

        $request = [
            'url'       => '/merchants/beneficiary/api/yesbank',
            'method'    => 'post',
            'content'   => [
                'duration' => 15
            ]
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('register_count', $content);

        $this->assertEquals(1, $content['register_count']);

        $this->assertEquals(Channel::YESBANK, $content['channel']);

        Mail::assertQueued(BeneficiaryFileMail::class);

        $nodalBeneficiary = $this->getLastEntity('nodal_beneficiary', true);

        $this->assertEquals('registered', $nodalBeneficiary['registration_status']);
    }

    public function testFailedBeneficiaryRegistrationWithYesbank()
    {
        $ba = $this->fixtures->create('bank_account');

        $this->fixtures->create('nodal_beneficiary',
            [
                'bank_account_id'     => $ba->getId(),
                'merchant_id'         => $ba->merchant->getId(),
                'registration_status' => 'failed'
            ]
        );

        $this->ba->cronAuth();

        $request = [
            'url'       => '/merchants/beneficiary/api/yesbank',
            'method'    => 'post',
            'content'   => [
                'duration'        => 120,
                'failed_response' => 1
            ]
        ];

        $content = $this->makeRequestAndGetContent($request);

        $nodalBeneficiary = $this->getLastEntity('nodal_beneficiary', true);

        $this->assertEquals('registered', $nodalBeneficiary['registration_status']);
    }

    public function testFetchingLinkedAcountsForMerchant()
    {
        $this->fixtures->create('merchant',[
            'id'         => 'parentaccount1',
            'email'      => 'parentaccount1@razorpay.com',
        ]);

        $this->fixtures->create('merchant',[
            'id'         => 'linkdaccount01',
            'email'      => 'linkdaccount01@razorpay.com',
            'parent_id'  => 'parentaccount1'
        ]);

        $this->fixtures->create(
            'feature',
            [
                'entity_id'     => 'parentaccount1',
                'entity_type'   => 'merchant',
                'name'          => 'marketplace'
            ]);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testPartnerAcountsForMerchant()
    {
        $this->fixtures->create('merchant',[
            'id'            => 'parentaccount1',
            'email'         => 'parentaccount1@razorpay.com',
            'partner_type'  => 'aggregator',
        ]);

        $this->fixtures->create('merchant',[
            'id'         => 'submerchant001',
            'email'      => 'submerchant001@razorpay.com'
        ]);

        $user = $this->fixtures->create('user');

        $this->fixtures->user->createUserMerchantMapping([
            'merchant_id' => 'parentaccount1',
            'user_id'     => $user['id'],
            'role'        => 'owner'
        ]);

        $this->createOAuthApplication([
            'id'            => '10000000000App',
            'merchant_id'   => 'parentaccount1',
            'type'          => 'partner',
            'partner_type'  => 'aggregator',
        ]);

        $this->fixtures->create('merchant_access_map', [
            'merchant_id' => 'submerchant001',
            'entity_id'   => '10000000000App',
            'entity_type' => 'application',
        ]);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testReferredAccountForMerchant()
    {
        $this->fixtures->create('merchant',[
            'id'            => 'parentaccount1',
            'email'         => 'parentaccount1@razorpay.com'
        ]);

        $this->fixtures->create('merchant',[
            'id'         => 'refaccount0001',
            'email'      => 'refaccount0001@razorpay.com'
        ]);

        $this->fixtures->create(
            'feature',
            [
                'entity_id'     => 'parentaccount1',
                'entity_type'   => 'merchant',
                'name'          => 'aggregator'
            ]);

        DB::table('tagging_tagged')
            ->insert([
                'taggable_id'       => 'refaccount0001',
                'taggable_type'     => 'merchant',
                'tag_name'          => '',
                'tag_slug'          => 'ref-parentaccount1',
            ]);

        $this->ba->adminAuth();
        $this->startTest();
    }

    public function testNegativeBeneficiaryRegisterBetweenTimestampAxis()
    {
        Mail::fake();

        // Choosing a non-holiday, and previous day is also not holiday
        $twentythirdOct2018 = Carbon::createFromDate(2017, 10, 23, Timezone::IST);

        Carbon::setTestNow($twentythirdOct2018);

        $this->fixtures->create('bank_account', ['ifsc_code'  => 'UTIB0CCH274']);

        $this->fixtures->create('bank_account', ['ifsc_code'  => 'UTIB0123456']);

        $this->fixtures->create('bank_account', ['ifsc_code'  => 'HDFC0153496']);

        $this->fixtures->create('merchant_detail',
            [
                'merchant_id' => '10000000000000',
                'business_registered_address'   => 'ksjdnfk akejnffn',
                'business_registered_state'     => 'karnanata',
                'business_registered_city'      => 'bengaluru',
                'business_registered_pin'       => '12345457',
                'contact_mobile'                => '124098598978',
            ]);

        $this->ba->cronAuth();

        $request = [
            'url'       => '/merchants/beneficiary/file/bank/axis',
            'method'    => 'post',
            'content'   => [
                BankAccount::ON => $twentythirdOct2018->timestamp,
                BankAccount::RECIPIENT_EMAILS => ['abc@d.com', 'efg@h.com'],
            ]
        ];

        $content = $this->makeRequestAndGetContent($request);

        Carbon::setTestNow();

        $this->assertArrayHasKey('signed_url', $content);
        $this->assertEquals(2, $content['register_count']);
        $this->assertEquals(3, $content['total_count']);
        $this->assertEquals(Channel::AXIS, $content['channel']);

        Mail::assertQueued(BeneficiaryFileMail::class, function ($mail)
        {
            return $mail->hasTo(['abc@d.com', 'efg@h.com']);
        });
    }

    public function testBeneficiaryRegisterBetweenTimestampAxisWithInvalidIfsc()
    {
        Mail::fake();

        // Choosing a non-holiday, and previous day is also not holiday
        $twentythirdOct2018 = Carbon::createFromDate(2017, 10, 23, Timezone::IST);

        Carbon::setTestNow($twentythirdOct2018);

        $this->fixtures->create('bank_account', ['ifsc_code'  => 'UTIB0CCH274']);

        $this->fixtures->create('merchant_detail',
            [
                'merchant_id' => '10000000000000',
                'business_registered_address'   => 'ksjdnfk akejnffn',
                'business_registered_state'     => 'karnanata',
                'business_registered_city'      => 'bengaluru',
                'business_registered_pin'       => '12345457',
                'contact_mobile'                => '124098598978',
            ]);

        $this->ba->cronAuth();

        $request = [
            'url'       => '/merchants/beneficiary/file/bank/axis',
            'method'    => 'post',
            'content'   => [
                BankAccount::ON => $twentythirdOct2018->timestamp,
                BankAccount::RECIPIENT_EMAILS => ['abc@d.com', 'efg@h.com'],
            ]
        ];

        $content = $this->makeRequestAndGetContent($request);

        Carbon::setTestNow();

        $this->assertEquals(0, $content['register_count']);
        $this->assertEquals(1, $content['total_count']);
        $this->assertEquals(Channel::AXIS, $content['channel']);
    }

    public function testSubmitSupportCallRequest()
    {
        $this->enableRazorXTreatmentForFeature('support_call');

        $this->ba->proxyAuth();
        $this->fixtures->merchant->edit('10000000000000', ['activated' => 0]);

        // 5th Nov 2018, 10 AM, Monday
        Carbon::setTestNow(Carbon::create(2018, 11, 5, 10, null, null, Timezone::IST));

        $this->startTest();

        $this->fixtures->merchant->activate();
        $this->fixtures->merchant->holdFunds();

        $this->startTest();

        $this->assertTrue($this->isMerchantAllowedtoSubmitSupportCallRequest());
    }

    public function testSubmitSupportCallRequestForBanking()
    {
        $this->enableRazorXTreatmentForXOnboarding("on");

        $user = (new User())->createUserForMerchant();

        $this->fixtures->edit('merchant',
            '10000000000000',
            ['activated' => true, 'business_banking' => true, 'category2' => "school"]);

        $this->fixtures->create('merchant_detail',
            [
                'merchant_id' => '10000000000000',
                'activation_status' => 'activated'
            ]);

        $this->fixtures->create('terminal:bank_account_terminal_for_business_banking',
            ['merchant_id' => '100000Razorpay']);

        // To create a virtual account we need to enable bank transfer
        $this->fixtures->edit('methods', '10000000000000', ['bank_transfer' => true]);

        $liveBankingAccount = $this->getDbEntity('banking_account',
            [
                'merchant_id' => '10000000000000',
            ],
            'live');

        $this->assertNull($liveBankingAccount);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id'], 'owner');

        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        // 5th Nov 2018, 10 AM, Monday
        Carbon::setTestNow(Carbon::create(2018, 11, 5, 10, null, null, Timezone::IST));

        $this->startTest();
    }

    public function testSubmitSupportCallRequestWithInvalidContact()
    {
        $this->enableRazorXTreatmentForFeature('support_call');

        $this->ba->proxyAuth();

        // 5th Nov 2018, 10 AM, Monday
        Carbon::setTestNow(Carbon::create(2018, 11, 5, 10, null, null, Timezone::IST));

        $this->startTest();

        $this->assertTrue($this->isMerchantAllowedtoSubmitSupportCallRequest());
    }

    public function testSubmitSupportCallRequestOnNonWorkingHours()
    {
        $this->enableRazorXTreatmentForFeature('support_call');

        $this->ba->proxyAuth();
        $this->fixtures->merchant->activate();

        // 5th Nov 2018, 8 AM, Monday
        Carbon::setTestNow(Carbon::create(2018, 11, 5, 8, null, null, Timezone::IST));
        $this->startTest();

        // 5th Nov 2018, 7 PM, Monday
        Carbon::setTestNow(Carbon::create(2018, 11, 5, 19, null, null, Timezone::IST));
        $this->startTest();

        // 4th Nov 2018, 10 AM, Sunday
        Carbon::setTestNow(Carbon::create(2018, 11, 4, 10, null, null, Timezone::IST));
        $this->startTest();

        $this->assertFalse($this->isMerchantAllowedtoSubmitSupportCallRequest());
    }

    public function testSearchWithDateFilter()
    {
        $this->createEsMockAndSetExpectations(__FUNCTION__);

        $this->ba->adminAuth();

        $this->startTest();
    }

    /**
     * Verifies queue entries merchant_sync_es_balance_bulk call
     */
    public function testQueueEntriesAfterBalanceSync()
    {
        Queue::fake();

        $this->createBalanceEntities();

        $this->ba->cronAuth();

        $this->startTest();

        // Asserting entries are being pushed on merchant_sync_es_balance_bulk api call .
        Queue::assertPushed(EsSync::class, 1);
    }

    /**
     * Verifies ES bulkupdate method is called during merchant_sync_es_balance_bulk call
     */
    public function testESQueryAfterSync()
    {
        $this->createEsMockAndSetExpectations(__FUNCTION__, 'bulkUpdate');

        $this->createBalanceEntities();

        $this->ba->cronAuth();

        $this->startTest();
    }

    /**
     * Creates balance entities
     *
     * @return array
     */
    private function createBalanceEntities(): array
    {
        Carbon::setTestNow(Carbon::now()->addHours(25));

        $merchant1  = $this->fixtures->create('merchant');
        $merchant2  = $this->fixtures->create('merchant');
        $updated_at = Carbon::now()->subMinutes(10)->getTimestamp();

        $entity1 = $this->fixtures->create('balance', [
            Balance::MERCHANT_ID => $merchant1[Merchant\Entity::ID],
            Balance::UPDATED_AT  => $updated_at,
        ]);

        $entity2 = $this->fixtures->create('balance', [
            Balance::MERCHANT_ID => $merchant2[Merchant\Entity::ID],
            Balance::UPDATED_AT  => $updated_at,
        ]);

        return array($entity1, $entity2);
    }

    public function testMerchantSwitchProductActivationSMS($expValue = 'on', $category2 = 'school')
    {
        $this->enableRazorXTreatmentForXOnboarding($expValue);

        $user = (new User())->createUserForMerchant('10000000000000', [
            'contact_mobile' => '8888888888',
        ]);

        $this->mockRaven();

        $this->fixtures->edit('merchant',
            '10000000000000',
            ['activated' => true, 'business_banking' => false, 'category2' => $category2]);

        $this->fixtures->create('merchant_detail',
            [
                'merchant_id' => '10000000000000',
                'activation_status' => 'activated'
            ]);

        $this->fixtures->create('terminal:bank_account_terminal_for_business_banking',
            ['merchant_id' => '100000Razorpay']);

        // To create a virtual account we need to enable bank transfer
        $this->fixtures->edit('methods', '10000000000000', ['bank_transfer' => true]);

        $liveBankingAccount = $this->getDbEntity('banking_account',
            [
                'merchant_id' => '10000000000000',
            ],
            'live');

        $this->assertNull($liveBankingAccount);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id'], 'owner');

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $this->startTest();

        //TPV flow is created during switch merchant flow
        $liveBankAccount = $this->getDbEntity('bank_account',
                                              [
                                                  'entity_id' => '10000000000000',
                                                  'type'      => 'merchant'
                                              ],
                                              'live')->toArray();

        $liveTpv = $this->getDbEntity('banking_account_tpv',
                                      [
                                          'merchant_id'          => '10000000000000',
                                          'payer_ifsc'           => $liveBankAccount['ifsc_code'],
                                          'payer_account_number' => $liveBankAccount['account_number'],
                                          'payer_name'           => $liveBankAccount['beneficiary_name'],
                                          'status'               => 'approved',
                                      ],
                                      'live')->toArray();

        $this->assertNotNull($liveTpv);

        $testBankAccount = $this->getDbEntity('bank_account',
                                              [
                                                  'entity_id' => '10000000000000',
                                                  'type'      => 'merchant'
                                              ],
                                              'test')->toArray();

        $testTpv = $this->getDbEntity('banking_account_tpv',
                                      [
                                          'merchant_id'          => '10000000000000',
                                          'payer_ifsc'           => $testBankAccount['ifsc_code'],
                                          'payer_account_number' => $testBankAccount['account_number'],
                                          'payer_name'           => $testBankAccount['beneficiary_name'],
                                          'status'               => 'approved',
                                      ],
                                      'test')->toArray();

        $this->assertNotNull($testTpv);
    }

    /**
     * Switches product of merchant from PG to BB.
     */
    public function testMerchantSwitchProduct($expValue = 'on', $category2 = 'school')
    {
        $this->mockLedgerSns(0);

        $this->enableRazorXTreatmentForXOnboarding($expValue);

        $user = (new User())->createUserForMerchant('10000000000000', [
            'contact_mobile' => '8888888888',
        ]);

        $this->fixtures->edit('merchant',
                              '10000000000000',
                              ['activated' => true, 'business_banking' => false, 'category2' => $category2]);

        $this->fixtures->create('merchant_detail',
                                [
                                    'merchant_id' => '10000000000000',
                                    'activation_status' => 'activated'
                                ]);

        $this->fixtures->create('terminal:bank_account_terminal_for_business_banking',
                                ['merchant_id' => '100000Razorpay']);

        // To create a virtual account we need to enable bank transfer
        $this->fixtures->edit('methods', '10000000000000', ['bank_transfer' => true]);

        $liveBankingAccount = $this->getDbEntity('banking_account',
                                                 [
                                                     'merchant_id' => '10000000000000',
                                                 ],
                                                 'live');

        $this->assertNull($liveBankingAccount);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id'], 'owner');

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $this->startTest();

        $liveBankingAccount = $this->getDbEntity('banking_account',
            [
                'merchant_id' => '10000000000000',
            ],
            'live');

        $this->assertNotNull($liveBankingAccount);

        /** @var BankingAccount\Entity $bankingAccount */
        $bankingAccount = $this->getDbLastEntity('banking_account');

        $expectedBankingAccount = [
            'channel'     => 'yesbank',
            'merchant_id' => '10000000000000',
            'status'      => 'activated',
            'pincode'     => null
        ];

        $balanceId = $bankingAccount->getBalanceId();

        $this->assertArraySelectiveEquals($expectedBankingAccount, $bankingAccount->toArray());
        $this->assertArraySelectiveEquals($expectedBankingAccount, $liveBankingAccount->toArray());
        $this->assertNotNull($balanceId);

        /** @var BankingAccount\Entity $bankingAccount */
        $balance = $this->getDbEntityById('balance', $balanceId);

        $expectedBalance = [
            'type'             => 'banking',
            'account_type'     => 'shared',
            'channel'          => null,
            'merchant_id'      => '10000000000000',
        ];

        $this->assertArraySelectiveEquals($expectedBalance, $balance->toArray());

        $merchants = DB::connection('test')->table('merchant_users')
            ->where('user_id', '=', $user['id'])
            ->pluck('merchant_id', 'product');

        $this->assertEquals(count($merchants), 2);

        $this->assertArrayHasKey('banking', $merchants);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(BankingAccount\AccountType::NODAL, $bankingAccount->getAccountType());

        $testFeaturesArray = $this->getDbEntity('feature',
                                                [
                                                    'entity_id' => '10000000000000',
                                                    'entity_type' => 'merchant'
                                                ])->pluck('name')->toArray();

        $liveFeaturesArray = $this->getDbEntity('feature',
                                                [
                                                    'entity_id' => '10000000000000',
                                                    'entity_type' => 'merchant'
                                                ],
                                               'live')->pluck('name')->toArray();

        $this->assertContains('payout', $testFeaturesArray);
        $this->assertContains('payout', $liveFeaturesArray);

        $this->assertContains('skip_hold_funds_on_payout', $testFeaturesArray);
        $this->assertContains('skip_hold_funds_on_payout', $liveFeaturesArray);

        $this->assertContains(Features::NEW_BANKING_ERROR, $testFeaturesArray);
        $this->assertContains(Features::NEW_BANKING_ERROR, $liveFeaturesArray);

        // Assert that the ledger_journal_writes feature is not enabled at all
        $this->assertNotContains('ledger_journal_writes', $testFeaturesArray);
        $this->assertNotContains('ledger_journal_writes', $liveFeaturesArray);
    }

    public function testMerchantSwitchProductWithLedgerExperimentOn($expValue = 'on', $category2 = 'school')
    {
        $this->mockLedgerSns(2);

        $this->enableRazorXTreatmentForXOnboarding($expValue, 'on');

        $liveUser = (new User())->createUserForMerchant('10000000000000', [
            'contact_mobile' => '8888888888',
        ],'owner', 'live');

        $this->fixtures->edit('merchant',
                              '10000000000000',
                              ['activated' => true, 'business_banking' => false, 'category2' => $category2]);

        $this->fixtures->create('merchant_detail',
                                [
                                    'merchant_id' => '10000000000000',
                                    'activation_status' => 'activated'
                                ]);

        $this->fixtures->create('terminal:bank_account_terminal_for_business_banking',
                                ['merchant_id' => '100000Razorpay']);

        // To create a virtual account we need to enable bank transfer
        $this->fixtures->edit('methods', '10000000000000', ['bank_transfer' => true]);

        $liveBankingAccount = $this->getDbEntity('banking_account',
                                                 [
                                                     'merchant_id' => '10000000000000',
                                                 ],
                                                 'live');

        $this->assertNull($liveBankingAccount);

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $this->ba->proxyAuth('rzp_live_10000000000000', $liveUser['id'], 'owner');

        $this->startTest();

        $liveBankingAccount = $this->getDbEntity('banking_account',
                                                 [
                                                     'merchant_id' => '10000000000000',
                                                 ],
                                                 'live');

        $this->assertNotNull($liveBankingAccount);

        /** @var BankingAccount\Entity $bankingAccount */
        $bankingAccount = $this->getDbLastEntity('banking_account');

        $expectedBankingAccount = [
            'channel'     => 'yesbank',
            'merchant_id' => '10000000000000',
            'status'      => 'activated',
            'pincode'     => null
        ];

        $balanceId = $liveBankingAccount->getBalanceId();

        $this->assertArraySelectiveEquals($expectedBankingAccount, $bankingAccount->toArray());
        $this->assertArraySelectiveEquals($expectedBankingAccount, $liveBankingAccount->toArray());
        $this->assertNotNull($balanceId);

        /** @var BankingAccount\Entity $bankingAccount */
        $balance = $this->getDbEntityById('balance', $balanceId, 'live');

        $expectedBalance = [
            'type'             => 'banking',
            'account_type'     => 'shared',
            'channel'          => null,
            'merchant_id'      => '10000000000000',
        ];

        $this->assertArraySelectiveEquals($expectedBalance, $balance->toArray());

        $merchants = DB::connection('live')->table('merchant_users')
                       ->where('user_id', '=', $liveUser['id'])
                       ->pluck('merchant_id', 'product');

        $this->assertEquals(count($merchants), 2);

        $this->assertArrayHasKey('banking', $merchants);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(BankingAccount\AccountType::NODAL, $bankingAccount->getAccountType());

        $testFeaturesArray = $this->getDbEntity('feature',
                                                [
                                                    'entity_id' => '10000000000000',
                                                    'entity_type' => 'merchant'
                                                ])->pluck('name')->toArray();

        $liveFeaturesArray = $this->getDbEntity('feature',
                                                [
                                                    'entity_id' => '10000000000000',
                                                    'entity_type' => 'merchant'
                                                ],
                                                'live')->pluck('name')->toArray();

        $this->assertContains('payout', $testFeaturesArray);
        $this->assertContains('payout', $liveFeaturesArray);

        $this->assertContains('skip_hold_funds_on_payout', $testFeaturesArray);
        $this->assertContains('skip_hold_funds_on_payout', $liveFeaturesArray);

        $this->assertContains(Features::NEW_BANKING_ERROR, $testFeaturesArray);
        $this->assertContains(Features::NEW_BANKING_ERROR, $liveFeaturesArray);

        // Assert that the ledger_journal_writes feature is enabled for live mode
        $this->assertContains('ledger_journal_writes', $testFeaturesArray);
        $this->assertContains('ledger_journal_writes', $liveFeaturesArray);
    }

    /**
     * Switches product of merchant from PG to BB.
     */
    public function testMerchantSwitchProductWhenL1Incomplete()
    {
        $this->testMerchantSwitchProduct('on', null);
    }

    public function testMerchantBankingVAMigration()
    {
        $this->testMerchantSwitchProduct('on', null);

        (new Admin\Service)->setConfigKeys(
            [
                Admin\ConfigKey::RX_ACCOUNT_NUMBER_SERIES_PREFIX => [
                    Merchant\Account::SHARED_ACCOUNT => '232323',
                ]
            ]);

        $this->ba->cronAuth('live');

        $this->startTest();

        $balances = $this->getDbEntities('balance',
            [
                'merchant_id'   => '10000000000000',
                'account_type'  => 'shared',

            ], 'live');

        $bankingAccounts = $this->getDbEntities('banking_account',
            [
                'merchant_id' => '10000000000000',

            ], 'live');

        $virtualAccounts = $this->getDbEntities('virtual_account',
            [
                'merchant_id' => '10000000000000',
                'balance_id'  => $bankingAccounts->first()->getBalanceId(),
            ], 'live');


        $this->assertEquals($balances->count(), 1);

        $this->assertEquals($virtualAccounts->count(), 2);

        $this->assertEquals($bankingAccounts->count(), 1);


        $counter = $this->getDbLastEntity('counter','live')->toArray();

        // Counter check
        $this->assertEquals($counter['balance_id'], $bankingAccounts->first()->getBalanceId());
        $this->assertEquals($counter['account_type'], 'shared');
    }

    /**
     * Switches product of merchant from PG to BB.
     */
    public function testMerchantSwitchProductWhenMerchantNotActivated()
    {
        $this->enableRazorXTreatmentForXOnboarding();

        $user = (new User())->createUserForMerchant();

        $this->fixtures->edit('merchant',
            '10000000000000',
            ['activated' => false, 'business_banking' => true, 'category2' => 'school']);

        $this->fixtures->create('merchant_detail',
            [
                'merchant_id' => '10000000000000',
                'activation_status' => 'pending'
            ]);

        $this->fixtures->create('terminal:bank_account_terminal_for_business_banking',
            ['merchant_id' => '100000Razorpay']);

        // To create a virtual account we need to enable bank transfer
        $this->fixtures->edit('methods', '10000000000000', ['bank_transfer' => true]);

        $liveBankingAccount = $this->getDbEntity('banking_account',
            [
                'merchant_id' => '10000000000000',
            ],
            'live');

        $this->assertNull($liveBankingAccount);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id'], 'owner');

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $this->startTest();

        $liveBankingAccount = $this->getDbEntity('banking_account',
            [
                'merchant_id' => '10000000000000',
            ],
            'live');

        $this->assertNull($liveBankingAccount);

        /** @var BankingAccount\Entity $bankingAccount */
        $bankingAccount = $this->getDbLastEntity('banking_account');

        $expectedBankingAccount = [
            'channel'     => 'yesbank',
            'merchant_id' => '10000000000000',
            'status'      => 'activated',
            'pincode'     => null
        ];

        $balanceId = $bankingAccount->getBalanceId();

        $this->assertArraySelectiveEquals($expectedBankingAccount, $bankingAccount->toArray());
        $this->assertNotNull($balanceId);

        /** @var BankingAccount\Entity $bankingAccount */
        $balance = $this->getDbEntityById('balance', $balanceId);

        $expectedBalance = [
            'type'             => 'banking',
            'account_type'     => 'shared',
            'channel'          => null,
            'merchant_id'      => '10000000000000',
        ];

        $this->assertArraySelectiveEquals($expectedBalance, $balance->toArray());

        $merchants = DB::connection('test')->table('merchant_users')
            ->where('user_id', '=', $user['id'])
            ->pluck('merchant_id', 'product');

        $this->assertEquals(count($merchants), 2);

        $this->assertArrayHasKey('banking', $merchants);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(BankingAccount\AccountType::NODAL, $bankingAccount->getAccountType());

        $testFeaturesArray = $this->getDbEntity('feature',
                                                [
                                                    'entity_id' => '10000000000000',
                                                    'entity_type' => 'merchant'
                                                ])->pluck('name')->toArray();

        $liveFeaturesArray = $this->getDbEntity('feature',
                                                [
                                                    'entity_id' => '10000000000000',
                                                    'entity_type' => 'merchant'
                                                ],
                                                'live');

        $this->assertContains(Feature\Constants::PAYOUT, $testFeaturesArray);
        $this->assertNull($liveFeaturesArray);
    }

    /**
     * Switches product of merchant from PG to BB.
     */
    public function testMerchantSwitchProductWhenMerchantNotActivatedAndL1Incomplete()
    {
        $this->enableRazorXTreatmentForXOnboarding('on');

        $user = (new User())->createUserForMerchant();

        $this->fixtures->edit('merchant',
                              '10000000000000',
                              ['activated' => false, 'business_banking' => true, 'category2' => null]);

        $this->fixtures->create('merchant_detail',
                                [
                                    'merchant_id' => '10000000000000',
                                    'activation_status' => 'pending'
                                ]);

        $this->fixtures->create('terminal:bank_account_terminal_for_business_banking',
                                ['merchant_id' => '100000Razorpay']);

        // To create a virtual account we need to enable bank transfer
        $this->fixtures->edit('methods', '10000000000000', ['bank_transfer' => true]);

        $liveBankingAccount = $this->getDbEntity('banking_account',
                                                 [
                                                     'merchant_id' => '10000000000000',
                                                 ],
                                                 'live');

        $this->assertNull($liveBankingAccount);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id'], 'owner');

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $this->startTest();

        $liveBankingAccount = $this->getDbEntity('banking_account',
                                                 [
                                                     'merchant_id' => '10000000000000',
                                                 ],
                                                 'live');

        $this->assertNull($liveBankingAccount);

        /** @var BankingAccount\Entity $bankingAccount */
        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertNotNull($bankingAccount);

        $merchants = DB::connection('test')->table('merchant_users')
                       ->where('user_id', '=', $user['id'])
                       ->pluck('merchant_id', 'product');

        $this->assertEquals(count($merchants), 2);

        $this->assertArrayHasKey('banking', $merchants);

        $testFeaturesArray = $this->getDbEntity('feature',
                                                [
                                                    'entity_id' => '10000000000000',
                                                    'entity_type' => 'merchant'
                                                ]);

        $liveFeaturesArray = $this->getDbEntity('feature',
                                                [
                                                    'entity_id' => '10000000000000',
                                                    'entity_type' => 'merchant'
                                                ],
                                                'live');

        $this->assertNotNull($testFeaturesArray);
        $this->assertNull($liveFeaturesArray);
    }

    public function testMerchantSwitchProductWithInvalidBeneficiaryNameThrowsProperException()
    {
        // Activate merchant with business_banking flag set to true.
        $this->fixtures->on('live')->merchant->edit('10000000000000', ['business_banking' => 1]);
        $this->fixtures->on('live')->merchant->activate();

        // Creates banking balance
        $bankingBalance = $this->fixtures->on('live')->merchant->createBalanceOfBankingType();

        // Creates virtual account, its bank account receiver on new banking balance.
        $virtualAccount = $this->fixtures->on('live')->create('virtual_account');

        // Creates bank account with invalid b
        $bankAccount    = $this->fixtures->on('live')->create(
            'bank_account',
            [
                'id'               => '1000001lcustba',
                'type'             => 'virtual_account',
                'entity_id'        => $virtualAccount->getId(),
                'account_number'   => '3434440041626905',
                'ifsc_code'        => 'YESB0CMSNOC',
                'beneficiary_name' => '_abc'
            ]);

        $virtualAccount->bankAccount()->associate($bankAccount);
        $virtualAccount->balance()->associate($bankingBalance);
        $virtualAccount->save();

        $this->fixtures->on('live')->merchant->addFeatures(['virtual_accounts', 'payout']);

        $user = (new User())->createUserForMerchant();

        $this->fixtures->edit('merchant',
            '10000000000000',
            ['activated' => true, 'business_banking' => false,]);

        $this->fixtures->create('merchant_detail',
            [
                'merchant_id'       => '10000000000000',
                'activation_status' => 'activated'
            ]);

        $this->fixtures->create('terminal:bank_account_terminal_for_business_banking',
            ['merchant_id' => '100000Razorpay']);

        // To create a virtual account we need to enable bank transfer
        $this->fixtures->edit('methods', '10000000000000', ['bank_transfer' => true]);

        $liveBankingAccount = $this->getDbEntity('banking_account',
            [
                'merchant_id' => '10000000000000',
            ],
            'live');

        $this->assertNull($liveBankingAccount);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id'], 'owner');

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $this->startTest();
    }

    public function testMerchantSwitchProductWithoutPreSignupSkipsOnboarding()
    {
        $this->fixtures->on('live')->merchant->addFeatures(['virtual_accounts', 'payout']);

        $user = (new User())->createUserForMerchant();

        // billing label/name - empty to signify no presignup filled.
        $this->fixtures->edit('merchant',
            '10000000000000',
            [
                'activated' => false,
                'name' => '',
                'business_banking' => false,
                'billing_label' => ''
            ]
        );

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $this->fixtures->create('terminal:bank_account_terminal_for_business_banking',
            ['merchant_id' => '100000Razorpay']);

        // To create a virtual account we need to enable bank transfer
        $this->fixtures->edit('methods', '10000000000000', ['bank_transfer' => true]);

        $liveBankingAccount = $this->getDbEntity('banking_account',
            [
                'merchant_id' => '10000000000000',
            ],
            'live');

        $this->assertNull($liveBankingAccount);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id'], 'owner');

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $this->startTest();

        // Assert Banking entities not created in test mode
        $testBankingAccount = $this->getDbEntity('banking_account',
            [
                'merchant_id' => '10000000000000',
            ], 'test');


        $this->assertNull($testBankingAccount);
    }

    public function testBulkAssignPricing()
    {
        $this->setAdminForInternalAuth();

        $this->fixtures->methods->createDefaultMethods(['merchant_id' => '10000000000011']);
        $this->fixtures->methods->createDefaultMethods(['merchant_id' => '10000000000012']);
        $this->fixtures->methods->createDefaultMethods(['merchant_id' => '10000000000013']);
        $this->fixtures->methods->createDefaultMethods(['merchant_id' => '10000000000014']);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testBulkAssignPricingMissingInput()
    {
        $this->setAdminForInternalAuth();

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testGetMerchantPartnerStatus()
    {
        $this->ba->directAuth();

        $this->startTest();

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['content']['email'] = 'test@razorpay.com';

        $testData['response']['content']['merchant'] = true;

        $this->startTest();

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'fully_managed']);

        $testData['response']['content']['partner'] = true;

        $this->startTest();

        $this->fixtures->edit('merchant', '10000000000000', ['org_id' => Org::SBIN_ORG]);

        $testData['response']['content']['partner'] = false;

        $testData['response']['content']['merchant'] = false;

        $this->startTest();
    }

    public function testGetMerchantPartnerStatusExtraInput()
    {
        $this->ba->directAuth();

        $this->startTest();
    }

    public function testInternationalEnable()
    {
        $merchantDetailsData = [
            'business_category'             => 'ecommerce',
            'business_subcategory'          => 'arts_and_collectibles',
            'international_activation_flow' => 'whitelist',
        ];

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', $merchantDetailsData);

        $merchantId = $merchantDetail->getMerchantId();
        $this->fixtures->user->createUserMerchantMappingForDefaultUser($merchantId);

        $merchantData = [
            'international'     => 0,
            'activated'         => 1,
            'convert_currency'  => null,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantData);

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $this->startTest();
    }

    public function testInternationalEnableForBlackList()
    {
        $merchantDetailsData = [
            'business_category'             => 'not_for_profit',
            'business_subcategory'          => 'educational',
            'activation_status'             => 'activated',
            'international_activation_flow' => 'blacklist',
        ];

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', $merchantDetailsData);

        $merchantId = $merchantDetail->getMerchantId();
        $this->fixtures->user->createUserMerchantMappingForDefaultUser($merchantId);

        $merchantData = [
            'international'     => 0,
            'activated'         => 1,
            'convert_currency'  => null,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantData);

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $this->startTest();
    }

    public function testInternationalEnableWhenAlreadyActive()
    {
        $merchantData = [
            'international'     => 1,
            'activated'         => 1,
            'convert_currency'  => false,
        ];

        $merchant = $this->fixtures->create('merchant', $merchantData);
        $this->fixtures->user->createUserMerchantMappingForDefaultUser($merchant->id);

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => $merchant['id'],
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $this->ba->proxyAuth('rzp_test_' . $merchant['id']);

        $this->startTest();
    }

    public function testInternationalEnableWhenWebsiteNotSet()
    {
        $merchantData = [
            'international'     => 0,
            'activated'         => 1,
            'convert_currency'  => null,
            'website'           => ''
        ];

        $merchant = $this->fixtures->create('merchant', $merchantData);
        $this->fixtures->user->createUserMerchantMappingForDefaultUser($merchant->id);

        $merchantDetailsData =[
            'merchant_id'      => $merchant['id'],
            'business_website' => '',
        ];

        $merchantDetail = $this->fixtures->create('merchant_detail', $merchantDetailsData);

        $this->ba->proxyAuth('rzp_test_' . $merchant['id']);

        $this->startTest();
    }

    public function testInternationalDisable()
    {
        $merchantData = [
            'international'     => 1,
            'activated'         => 1,
            'convert_currency'  => false,
        ];

        $merchant = $this->fixtures->create('merchant', $merchantData);
        $this->fixtures->user->createUserMerchantMappingForDefaultUser($merchant->id);

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => $merchant['id'],
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $this->ba->proxyAuth('rzp_test_' . $merchant['id']);

        $this->startTest();
    }

    public function testInternationalDisableWhenAlreadyInActive()
    {
        $merchant = $this->fixtures->create('merchant');
        $this->fixtures->user->createUserMerchantMappingForDefaultUser($merchant->id);

        $merchantData = [
            'international'     => 0,
            'activated'         => 1,
            'convert_currency'  => false,
        ];

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => $merchant['id'],
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $this->fixtures->edit('merchant', $merchant['id'], $merchantData);

        $this->ba->proxyAuth('rzp_test_' . $merchant['id']);

        $this->startTest();
    }

    public function testInternationalToggleWithInvalidValue()
    {
        $merchant = $this->fixtures->create('merchant');
        $this->fixtures->user->createUserMerchantMappingForDefaultUser($merchant->id);

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => $merchant['id'],
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $this->ba->proxyAuth('rzp_test_' . $merchant['id']);

        $this->startTest();
    }

    /**
     * Test case for merchant query cache , verifies that in live and test mode only live cache key is getting
     * populated.
     */
    public function testMerchantCacheSyncInBothMode()
    {
        config(['app.query_cache.mock' => false]);

        $merchantId = 10000000000000;

        $admin = $this->ba->getAdmin();
        $this->fixtures->admin->edit($admin['id'], ['allow_all_merchants' => true]);

        $this->ba->adminProxyAuth($merchantId, 'rzp_test_' . $merchantId);

        $this->startTest();

        $testKeyValue = Redis::connection('query_cache_redis')->get('test:tag:merchant_10000000000000:key');
        $liveKeyValue = Redis::connection('query_cache_redis')->get('live:tag:merchant_10000000000000:key');

        $this->assertNull($testKeyValue);
        $this->assertNotNull($liveKeyValue);

        $this->flushCache();

        $this->ba->adminProxyAuth($merchantId, 'rzp_live_' . $merchantId);

        $this->startTest();

        $testKeyValue = Redis::connection('query_cache_redis')->get('test:tag:merchant_10000000000000:key');
        $liveKeyValue = Redis::connection('query_cache_redis')->get('live:tag:merchant_10000000000000:key');

        $this->assertNull($testKeyValue);
        $this->assertNotNull($liveKeyValue);
    }

    public function testBeneficiaryRegisterApiYesbankWithMailNotQueued()
    {
        Mail::fake();

        $this->ba->cronAuth();

        $this->fixtures->create('bank_account');

        $request = [
            'url'       => '/merchants/beneficiary/api/yesbank',
            'method'    => 'post',
            'content'   => [
                'duration'   => 1200,
                'send_email' => false,
            ]
        ];

        $this->makeRequestAndGetContent($request);

        Mail::assertNotQueued(BeneficiaryFileMail::class);
    }

    public function testGetOrgDetails()
    {
        $this->ba->authServiceAuth();

        $this->startTest();
    }

    public function testSendBankingAccountsViaWebhook()
    {
        $attributes = [
            'account_type' => 'nodal',
            'channel'      => 'yesbank',
        ];

        $baNodal = $this->createBankingAccount($attributes);

        $rblCurrentAcc = $this->createBankingAccount(['id' => 'ABCde1234ABCdg']);

        $iciciCurrentAcc = $this->createBankingAccount(['id'             => 'ABCde1234ABCla',
                                                        'account_number' => '3334440041626905',
                                                        'channel'        => 'icici']);

        $this->fixtures->on('test')->create('banking_account', [
            'id'                    => 'ABCde1234ABCha',
            'account_number'        => '2224440041626874',
            'account_ifsc'          => 'RATN0000088',
            'account_type'          => 'current',
            'merchant_id'           => '10000000000000',
            'channel'               => 'sbi',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'balance_id'            => '',
            'status'                => 'created',
        ]);

        $expectedEvent = [
            'entity'     => 'event',
            'event'      => 'banking_accounts.issued',
            'account_id' => 'acc_10000000000000',
            'contains'   => ['accounts'],
            'payload'    => [
                'accounts' => [
                    'virtual' => [
                        'account_number' => $baNodal->getAccountNumber(),
                    ],
                    'current' => [
                        [
                            'channel'        => 'rbl',
                            'account_number' => $rblCurrentAcc->getAccountNumber(),
                        ],
                        [
                            'channel'        => 'icici',
                            'account_number' => $iciciCurrentAcc->getAccountNumber(),
                        ]
                    ],
                ],
            ],
        ];

        // This webhook will be called for banking_accounts.issued event.
        $this->expectWebhookEvent(
            'banking_accounts.issued',
            function(array $event) use ($expectedEvent) {
                $this->assertArraySelectiveEquals($expectedEvent, $event);
            }
        );

        $this->ba->authServiceAuth();

        $this->startTest();
    }

    public function testSendBankingAccountsViaWebhook1()
    {
        //only va exist for the merchant and no ca account.

        $attributes = [
            'account_type' => 'nodal',
            'channel'      => 'yesbank',
        ];

        $baNodal = $this->createBankingAccount($attributes);

        $expectedEvent = [
            'entity'     => 'event',
            'event'      => 'banking_accounts.issued',
            'account_id' => 'acc_10000000000000',
            'contains'   => ['accounts'],
            'payload'    => [
                'accounts' => [
                    'virtual' => [
                        'account_number' => $baNodal->getAccountNumber(),
                    ]
                ],
            ],
        ];

        // This webhook will be called for banking_accounts.issued event.
        $this->expectWebhookEvent(
            'banking_accounts.issued',
            function(array $event) use ($expectedEvent) {
                $this->assertArraySelectiveEquals($expectedEvent, $event);
            }
        );

        $this->ba->authServiceAuth();

        $this->startTest();
    }

    /**
     * When international activation flow is already set then use same
     * instead of recalculating international activation flow from business category and subcategory again
     */
    public function testInternationalEnableWhenInternationalActivationFlowIsAlreadySet()
    {

        $merchantData = [
            'international'    => 0,
            'activated'        => 1,
            'convert_currency' => null,
            'website'          => 'abc@gmail.com',
            'pricing_plan_id'  => '1In3Yh5Mluj605',
        ];

        $merchant = $this->fixtures->create('merchant', $merchantData);
        $this->fixtures->user->createUserMerchantMappingForDefaultUser($merchant->id);

        $this->merchantAssignPricingPlan('1hDYlICobzOCYt', $merchant['id']);
        //
        // If international activation flow is recalculated from business category and subcategory
        // then it points to blacklist category
        //
        $merchantDetailData = [
            'business_category'             => 'healthcare',
            'business_subcategory'          => 'pharmacy',
            'international_activation_flow' => 'whitelist',
            'merchant_id'                   => $merchant['id']
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailData);

        $this->ba->proxyAuth('rzp_test_' . $merchant['id']);

        $this->startTest();
    }

    public function testEditDashboardWhitelistedIpsLive()
    {
        $merchantPublicAttributes = $this->createMerchant();

        $this->ba->adminAuth();

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', $merchantPublicAttributes['id']);

        $this->assertEquals($merchant->getMerchantDashboardWhitelistedIpsLive(), ['1.1.1.1', '2.2.2.2']);
    }

    public function testEditDashboardInvalidWhitelistedIpsLive()
    {
        $this->getDbEntityById('merchant', '10000000000000');

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testEditDashboardRedundantWhitelistedIpsLive()
    {
        $this->getDbEntityById('merchant', '10000000000000');

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testEditDashboardWhitelistedIpsTest()
    {
        $merchantPublicAttributes = $this->createMerchant();

        $this->ba->adminAuth();

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', $merchantPublicAttributes['id']);

        $this->assertEquals($merchant->getMerchantDashboardWhitelistedIpsTest(), ['1.1.1.1', '2.2.2.2']);
    }

    public function testEditDashboardInvalidWhitelistedIpsTest()
    {
        $this->getDbEntityById('merchant', '10000000000000');

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testEditDashboardRedundantWhitelistedIpsTest()
    {
        $this->getDbEntityById('merchant', '10000000000000');

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testMerchantApplyRestrictionSettingsSuccess()
    {
        $ownerUser = $this->fixtures->create('user');

        $merchantIds = $ownerUser->merchants()->distinct()->get()->pluck('id')->toArray();

        $this->ba->adminAuth();

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['content']['merchant_id'] = $merchantIds[0];

        $testData['response']['content']['merchant_id'] = $merchantIds[0];

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', $merchantIds[0]);

        $this->assertEquals(1, $merchant['restricted']);
    }


    public function testMerchantApplyRestrictionSettingFailure()
    {
        $ownerUser = $this->fixtures->create('user');

        $merchant = $this->fixtures->create('merchant');

        $mappingData = [
            'user_id'     => $ownerUser['id'],
            'merchant_id' => $merchant['id'],
            'role'        => 'owner',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->ba->adminAuth();

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['content']['merchant_id'] = $merchant['id'];

        $response = $this->startTest();

        $merchant = $this->getDbEntityById('merchant', $merchant['id']);

        $this->assertEquals(0, $merchant['restricted']);
    }

    public function testMerchantRemoveRestrictionSettings()
    {
        $ownerUser = $this->fixtures->create('user');

        $merchantIds = $ownerUser->merchants()->distinct()->get()->pluck('id')->toArray();

        $this->ba->adminAuth();

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['content']['merchant_id'] = $merchantIds[0];

        $testData['response']['content']['merchant_id'] = $merchantIds[0];

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', $merchantIds[0]);

        $this->assertEquals(0, $merchant['restricted']);
    }

    public function testUpdateContactMobileOfUser()
    {
        $merchant = $this->fixtures->create('merchant');

        $this->fixtures->merchant->setRestricted(true, $merchant['id']);

        $user1 = $this->fixtures->create('user');

        $user2 = $this->fixtures->create('user');

        $this->createUserMerchantMapping($user1['id'], $merchant['id'], 'admin');

        $this->createUserMerchantMapping($user2['id'], $merchant['id'], 'owner');

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $user1['id']);

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['content']['user_id'] = $user2['id'];

        $response = $this->startTest();

        $userDB = $this->getDbEntityById('user', $user2['id']);

        $this->assertEquals($userDB['contact_mobile_verified'], $response['contact_mobile_verified']);

        $this->assertEquals($userDB['contact_mobile_verified'], true);

        $this->assertEquals($userDB['contact_mobile'], $response['contact_mobile']);
    }

    public function testUpdateContactMobileOfUserByAdmin()
    {
        $this->ba->adminAuth();

        $user = $this->fixtures->create('user');

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['content']['user_id'] = $user['id'];

        $this->startTest();

        $userDb = $this->getDbEntityById('user', $user['id']);

        $this->assertEquals($userDb['contact_mobile_verified'], false);
    }

    public function testUpdateContactMobileOfUserByAdminAndVerifyByFeatureFlag()
    {
        $this->fixtures->create(
            'feature',
            [
                'entity_id' => '100000razorpay',
                'name' => 'contact_verify_default',
                'entity_type' => 'org',
            ]);

        $this->ba->adminAuth();

        $user = $this->fixtures->create('user');

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['content']['user_id'] = $user['id'];

        $this->startTest();

        $userDb = $this->getDbEntityById('user', $user['id']);

        $this->assertEquals($userDb['contact_mobile_verified'], true);
    }

    public function testUpdateContactMobileOfSelfUser()
    {
        $merchant = $this->createMerchant();

        $this->fixtures->merchant->setRestricted(true, $merchant['id']);

        $user = $this->fixtures->create('user');

        $this->createUserMerchantMapping($user['id'], $merchant['id'], 'owner', 'test');

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $user['id']);

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['content']['user_id'] = $user['id'];

        $this->startTest();
    }

    public function testUserAccountUnlock()
    {
        $merchant = $this->createMerchant();

        $this->fixtures->merchant->setRestricted(true, $merchant['id']);

        $userMapping = $this->fixtures->create('user');

        $this->createUserMerchantMapping($userMapping['id'], $merchant['id'], 'owner', 'test');

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $userMapping['id']);

        $user = $this->fixtures->create('user');

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => $merchant['id'],
            'role'        => 'manager',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['url'] = '/users/account/' . $user['id'] . '/unlock';

        $testData['response']['content']['user_id'] = $user['id'];

        $response = $this->startTest();

        $userDB = $this->getDbEntityById('user', $user['id']);

        $this->assertEquals($userDB['account_locked'], $response['account_locked']);
    }

    public function testMerchantRazorxBulkExperimentFetch()
    {
        $merchantId = 10000000000000;

        $this->enableRazorXTreatmentForRazorX();

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $this->startTest();
    }

    public function testFetchPartnerIntent()
    {
        $merchant = $this->getDbEntityById('merchant','10000000000000');

        Settings\Accessor::for($merchant, Settings\Module::PARTNER)
            ->upsert('partner_intent', true)
            ->save();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testFetchPartnerIntentForMerchantWithoutPartnerIntent()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testFetchPartnerIntentWithPartnerIntentFalse()
    {
        $merchant = $this->getDbEntityById('merchant','10000000000000');

        Settings\Accessor::for($merchant, Settings\Module::PARTNER)
            ->upsert('partner_intent', false)
            ->save();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testUpdatePartnerIntentWithPartnerIntentTrue()
    {
        $merchant = $this->getDbEntityById('merchant','10000000000000');

        Settings\Accessor::for($merchant, Settings\Module::PARTNER)
            ->upsert('partner_intent', true)
            ->save();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testUpdatePartnerIntentWithPartnerIntentFalse()
    {
        $merchant = $this->getDbEntityById('merchant', '10000000000000');

        Settings\Accessor::for($merchant, Settings\Module::PARTNER)
            ->upsert('partner_intent', false)
            ->save();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testUpdatePartnerIntentWithPartnerIntentNull()
    {
        $merchant = $this->getDbEntityById('merchant', '10000000000000');

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testSetInheritanceParent()
    {
        $this->ba->adminAuth();

        $subMerchantId = $this->setUpPartnerAndGetSubMerchantId();

        $this->testData[__FUNCTION__]['request']['url'] = '/merchants/' . $subMerchantId . '/inheritance_parent';

        $this->testData[__FUNCTION__]['request']['content'] = [
            'id'    =>  '10000000000000'
        ];

        $a = $this->startTest();

        $this->assertEquals($a['merchant_id'], $subMerchantId);

        $this->assertEquals($a['parent_merchant_id'], '10000000000000');

        $merchantInheritanceMap = $this->getLastEntity('merchant_inheritance_map', true);

        $this->assertEquals($merchantInheritanceMap['parent_merchant_id'], '10000000000000');

        $this->assertEquals($merchantInheritanceMap['merchant_id'], $subMerchantId);
    }

    public function testSetInheritanceParentBatch()
    {
        $this->ba->batchAppAuth();

        $subMerchantId = $this->setUpPartnerAndGetSubMerchantId();

        $subMerchantId2 =$this->fixtures->create('merchant')->getId();


        $this->testData[__FUNCTION__]['request']['content'] = [
            [
                'idempotency_key'    =>  '12345',
                'merchant_id'        =>  $subMerchantId,
                'parent_merchant_id' =>  '10000000000000'
            ],
            [
                'idempotency_key'    => '12346',
                'merchant_id'        =>  $subMerchantId2,
                'parent_merchant_id' =>  '10000000000000'
            ]
        ];

        $response = $this->startTest();

        $this->assertEquals($response['count'], 2);

        $this->assertEquals($response['items'][0]['merchant_id'], $subMerchantId);
        $this->assertEquals($response['items'][0]['parent_merchant_id'], '10000000000000');
        $this->assertEquals($response['items'][0]['success'], true);

        $this->assertEquals($response['items'][1]['success'], false);
        $this->assertEquals($response['items'][1]['error']['code'], ErrorCode::BAD_REQUEST_INHERITANCE_PARENT_SHOULD_BE_PARTNER_PARENT_OF_SUBMERCHANT);
        $this->assertEquals($response['items'][1]['http_status_code'], 400);
    }

    public function testSetNonPartnerInheritanceParent()
    {
        $this->ba->adminAuth();

        $submerchant = $this->fixtures->create('merchant');

        $this->testData[__FUNCTION__]['request']['url'] = '/merchants/' . $submerchant['id'] . '/inheritance_parent';

        $this->testData[__FUNCTION__]['request']['content'] = [
            'id'    =>  '10000000000000'
        ];

        $this->expectException(BadRequestException::class);

        $this->expectExceptionCode(
            ErrorCode::BAD_REQUEST_INHERITANCE_PARENT_SHOULD_BE_PARTNER_PARENT_OF_SUBMERCHANT);

        $this->expectExceptionMessage(
            'Inheritance parent should be aggregator or fully-managed partner of the submerchant');

        $this->startTest();

    }

    public function testGetInheritanceParent()
    {
        $this->ba->adminAuth();

        $subMerchantId = $this->setUpPartnerAndGetSubMerchantId();

        // set inheritance parent
        $request = [
            'method'  => 'post',
            'url'     => '/merchants/' . $subMerchantId. '/inheritance_parent',
            'content' => [
                'id'  => '10000000000000'
            ]
        ];

        $this->makeRequestAndGetContent($request);

        $this->testData[__FUNCTION__]['request']['url'] = '/merchants/' . $subMerchantId . '/inheritance_parent';

        $res = $this->startTest();

        $this->assertEquals($res['merchant_id'], $subMerchantId);

        $this->assertEquals($res['parent_merchant_id'], '10000000000000');
    }

    public function testGetInheritanceParentIfNotPresent()
    {
        $this->ba->adminAuth();

        $subMerchantId = $this->setUpPartnerAndGetSubMerchantId();

        $this->testData[__FUNCTION__]['request']['url'] = '/merchants/' . $subMerchantId . '/inheritance_parent';

        $this->expectException(BadRequestException::class);

        $this->startTest();
    }

    public function testDeleteInheritanceParent()
    {
        $this->ba->adminAuth();

        $subMerchantId = $this->setUpPartnerAndGetSubMerchantId();

        // set inheritance parent
        $request = [
            'method'  => 'post',
            'url'     => '/merchants/' . $subMerchantId . '/inheritance_parent',
            'content' => [
                'id'  => '10000000000000'
            ]
        ];

        $createRes = $this->makeRequestAndGetContent($request);

        $this->testData[__FUNCTION__]['request']['url'] = '/merchants/' . $subMerchantId . '/inheritance_parent';

        $res = $this->startTest();

        $this->assertEquals($res['id'], $createRes['id']);

        $this->assertEquals($res['deleted'], true);

        $merchantInheritanceMap = $this->getLastEntity('merchant_inheritance_map', true);

        $this->assertNull($merchantInheritanceMap);
    }

    public function testDeleteInheritanceParentIfNotPresent()
    {
        $this->ba->adminAuth();

        $subMerchantId = $this->setUpPartnerAndGetSubMerchantId();

        $this->testData[__FUNCTION__]['request']['url'] = '/merchants/' . $subMerchantId . '/inheritance_parent';

        $this->expectException(BadRequestException::class);

        $this->expectExceptionCode(
            ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND);

        $this->expectExceptionMessage(
            'No db records found.');

        $this->startTest();
    }

    // Internal merchant_details route return merchant and merchant_details in response
    public function testInternalGetMerchant()
    {
        $this->ba->terminalsAuth();

        $this->fixtures->create('merchant', ['id'=>'100ghi000ghi00']);

        $this->fixtures->merchant->addFeatures(['upi_otm', 'override_hitachi_blacklst'], '100ghi000ghi00');

        $this->fixtures->create('merchant_detail', ['merchant_id' => '100ghi000ghi00', 'contact_email' => 'test@gmail.com']);

        $this->testData[__FUNCTION__]['request']['url'] = '/internal/merchants/100ghi000ghi00';

        $this->startTest();
    }

    // Internal merchant_details route return merchant name and website
    public function testInternalGetMerchantBulk()
    {
        $this->ba->dashboardInternalAppAuth();

        $this->fixtures->create('merchant', ['id' => '100ghi000ghi00', "name" => 'test0']);
        $this->fixtures->create('merchant', ['id' => '100ghi000ghi01', "name" => 'test1']);

        $this->testData[__FUNCTION__]['request']['url'] = '/internal/merchants?ids[]=100ghi000ghi00&ids[]=100ghi000ghi01';

    }

    public function testInternalMerchantSendEmail()
    {
        Mail::fake();

        $this->ba->terminalsAuth();

        $this->startTest();

        Mail::assertQueued(StatusNotifyMail::class, function ($mail)
        {
            $this->assertEquals('instrumentation', $mail->originProduct);

            $testData = $this->testData['testInternalMerchantSendEmail']['response']['mail_content'];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            return true;
        });
    }

    public function testInternalMerchantSendEmailInvalidType()
    {
        $this->ba->terminalsAuth();

        $this->startTest();
    }

    public function testInternalMerchantSendEmailInstrumentNameMissing()
    {
        $this->ba->terminalsAuth();

        $this->startTest();
    }

    public function testGetBalances()
    {
        $this->fixtures->create('merchant', ['id'=>'100ghi000ghi00']);

        $balanceData1 = [
            'id'                => '100abc000abc00',
            'merchant_id'       => '100ghi000ghi00',
            'type'              => 'banking',
            'currency'          => 'INR',
            'name'              => null,
            'balance'           => 0,
            'credits'           => 0,
            'fee_credits'       => 0,
            'refund_credits'    => 0,
            'account_number'    => '2224440041626905',
            'account_type'      => 'shared',
            'channel'           => null,
            'updated_at'        => 1
        ];

        $balanceData2 = [
            'id'                => '100def000def00',
            'merchant_id'       => '100ghi000ghi00',
            'type'              => 'primary',
            'currency'          => null,
            'name'              => null,
            'balance'           => 100000,
            'credits'           => 50000,
            'fee_credits'       => 0,
            'refund_credits'    => 0,
            'account_number'    => null,
            'account_type'      => null,
            'channel'           => 'shared',
            'updated_at'        => 1
        ];

        $this->fixtures->create('balance',$balanceData1);

        $this->fixtures->create('balance',$balanceData2);

        $user = $this->fixtures->user->createUserForMerchant('100ghi000ghi00', [], 'owner', 'test');

        $this->ba->proxyAuth('rzp_test_100ghi000ghi00', $user->getId());

        $this->startTest();
    }

    public function testGetBalancesWhenNoBalanceExists()
    {
        $this->fixtures->create('merchant', ['id'=>'100ghi000ghi00']);

        $user = $this->fixtures->user->createUserForMerchant('100ghi000ghi00', [], 'owner');

        $this->ba->proxyAuth('rzp_test_100ghi000ghi00', $user->getId());

        $this->startTest();
    }

    public function testGetBalancesByType()
    {
        $this->fixtures->create('merchant', ['id'=>'100ghi000ghi00']);

        $balanceData1 = [
            'id'                => '100abc000abc00',
            'merchant_id'       => '100ghi000ghi00',
            'type'              => 'banking',
            'currency'          => 'INR',
            'name'              => null,
            'balance'           => 0,
            'credits'           => 0,
            'fee_credits'       => 0,
            'refund_credits'    => 0,
            'account_number'    => '2224440041626905',
            'account_type'      => 'shared',
            'channel'           => null,
            'updated_at'        => 1
        ];

        $balanceData2 = [
            'id'                => '100def000def00',
            'merchant_id'       => '100ghi000ghi00',
            'type'              => 'primary',
            'currency'          => null,
            'name'              => null,
            'balance'           => 100000,
            'credits'           => 50000,
            'fee_credits'       => 0,
            'refund_credits'    => 0,
            'account_number'    => null,
            'account_type'      => null,
            'channel'           => 'shared',
            'updated_at'        => 1
        ];

        $this->fixtures->create('balance',$balanceData1);

        $this->fixtures->create('balance',$balanceData2);

        $user = $this->fixtures->user->createUserForMerchant('100ghi000ghi00', [], 'owner', 'test');

        $this->ba->proxyAuth('rzp_test_100ghi000ghi00', $user->getId());

        $this->startTest();
    }

    protected function setUpPartnerAndGetSubMerchantId()
    {
        $factoryPath = base_path() . '/vendor/razorpay/oauth/database/factories';

        $this->app->make(Factory::class)->load($factoryPath);

        $subMerchant = $this->fixtures->merchant->createEntityInTestAndLive('merchant');

        $subMerchantId = $subMerchant->getId();

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'aggregator']);

        // Assign submerchant to partner
        $accessMapData = [
            'entity_type'     => 'application',
            'merchant_id'     => $subMerchantId,
            'entity_owner_id' => '10000000000000',
        ];

        $this->fixtures->merchant_access_map->createEntityInTestAndLive('merchant_access_map', $accessMapData);

        return $subMerchantId;
    }

    protected function enableRazorXTreatmentForRazorX()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatmentBulk'])
            ->getMock();

        $response = [
           ['result' => 'on',
               'feature_flag' => 'feature1'],
           ['result' => 'off',
            'feature_flag' => 'feature2'],
        ];

        $razorxMock->method('getTreatmentBulk')
            ->will($this->onConsecutiveCalls($response));

        $this->app->instance('razorx', $razorxMock);
    }

    protected function enableRazorXTreatmentForXOnboarding($value = 'on',
                                                           $ledgerOnboardingValue = 'control')
    {
        (new Admin\Service)->setConfigKeys(
            [
                Admin\ConfigKey::RX_ACCOUNT_NUMBER_SERIES_PREFIX => [
                    Merchant\Account::SHARED_ACCOUNT => '222444',
                ]
            ]);

        (new Admin\Service)->setConfigKeys(
            [
                Admin\ConfigKey::RX_SHARED_ACCOUNT_ALLOWED_CHANNELS => [Channel::YESBANK, Channel::ICICI]
            ]);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                              function ($mid, $feature, $mode) use ($value, $ledgerOnboardingValue)
                              {
                                  if ($feature === Merchant\RazorxTreatment::RAZORPAY_X_TEST_MODE_ONBOARDING)
                                  {
                                      return $value;
                                  }
                                  if ($feature === Merchant\RazorxTreatment::SUPPORT_CALL)
                                  {
                                      return $value;
                                  }

                                  if ($feature == Merchant\RazorxTreatment::RAZORPAY_X_ACL_DENY_UNAUTHORISED)
                                  {
                                      return $value;
                                  }

                                  if ($feature == Merchant\RazorxTreatment::LEDGER_ONBOARDING)
                                  {
                                      return $ledgerOnboardingValue;
                                  }

                                  return 'off';
                              }));
    }

    protected function enableRazorXTreatmentForFeature($featureUnderTest, $value = 'on')
    {
        $mock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $mock->method('getTreatment')
            ->will(
                $this->returnCallback(
                    function (string $mid, string $feature, string $mode) use ($featureUnderTest, $value)
                    {
                        return $feature === $featureUnderTest ? $value : 'control';
                    }));

        $this->app->instance('razorx', $mock);

    }

    public function testMerchantInternationalDisableAction()
    {
        $this->fixtures->edit('merchant', '10000000000000',
                              ['international' => true, 'product_international' => '1111000000']);

        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000', 'international_activation_flow' => 'whitelist']);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testMerchantInternationalDisableActionFailure()
    {
        $this->fixtures->edit('merchant', '10000000000000',
                              ['international' => false, 'product_international' => '0000000000']);

        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000', 'international_activation_flow' => 'whitelist']);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testMerchantInternationalPGEnableAction()
    {
        $this->setMerchantMerchantDetailsAndPricing(false, 'greylist');

        $this->fixtures->edit('merchant', '10000000000000', [
            'product_international'   => '0000000000']);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testOutOfOrgMerchantInternationalEnableAction()
    {
        $this->setMerchantMerchantDetailsAndPricing(false, null);

        $this->fixtures->edit('merchant', '10000000000000', [
            'org_id'                => Org::SBIN_ORG,
            'product_international' => '0000000000']);

        $this->ba->adminAuth();

        $this->startTest();
    }


    public function testMerchantInternationalProdV2EnableAction()
    {
        $this->setMerchantMerchantDetailsAndPricing(false, 'whitelist');

        $this->fixtures->edit('merchant', '10000000000000', [
            'product_international'   => '0000000000']);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testOutOfOrgBlacklistedMerchantInternationalEnableAction()
    {
        $this->setMerchantMerchantDetailsAndPricing(false, null);

        $this->fixtures->edit('merchant', '10000000000000', [
            'org_id'                => Org::SBIN_ORG,
            'product_international' => '0000000000']);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testMerchantInternationalEnableCategoryOneGreylist()
    {
        $this->fixtures->edit('merchant', '10000000000000', [
            'pricing_plan_id'        => '1In3Yh5Mluj605',
            'international'          => 'greylist',
            'product_international'  => '0000000000']);

        $this->fixtures->create('merchant_detail', [
            'merchant_id'                   => '10000000000000',
            'international_activation_flow' => 'greylist',
            'business_category'             => 'education',
            'business_subcategory'          => 'college',
            'business_type'                 => 1]);

        $this->fixtures->pricing->createPromotionalPlan();

        $this->fixtures->edit('pricing', '1AXp2Xd3t5aRLX', ['international' => true]);

        $this->ba->adminAuth();

        $this->startTest();

        $merchantDetail = $this->getDbEntityById('merchant_detail', 10000000000000);

        $this->assertEquals('greylist', $merchantDetail['international_activation_flow']);
    }


    public function testMerchantInternationalDisableBulkEdit()
    {
        $this->setMerchantMerchantDetailsAndPricing(true, 'whitelist');

        $this->fixtures->edit('merchant', '10000000000000', ['product_international' => '1100000000']);

        $this->ba->adminAuth();

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', 10000000000000);

        $merchantDetail = $this->getDbEntityById('merchant_detail', 10000000000000);

        $this->assertEquals($merchant['international'], false);

        $this->assertEquals('0000000000', $merchant['product_international']);

        $this->assertEquals($merchantDetail['international_activation_flow'], 'blacklist');
    }

    public function testMerchantInternationalEnableBulkEdit()
    {
        $this->setMerchantMerchantDetailsAndPricing(false, 'whitelist');

        $this->ba->adminAuth();

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', 10000000000000);

        $merchantDetail = $this->getDbEntityById('merchant_detail', 10000000000000);

        $this->assertEquals($merchant['international'], true);

        $this->assertEquals($merchantDetail['international_activation_flow'], 'whitelist');
    }

    public function testMerchantInternationalEnableBulkEditBlacklistFailure()
    {
        $this->setMerchantMerchantDetailsAndPricing(false, 'blacklist');

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testMerchantProductInternationalEnableBulkEdit()
    {
        $this->setMerchantMerchantDetailsAndPricing(false, 'greylist');

        $this->ba->adminAuth();

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', 10000000000000);

        $merchantDetail = $this->getDbEntityById('merchant_detail', 10000000000000);

        $this->assertEquals($merchant['international'], true);

        $this->assertEquals($merchant['product_international'], '1111000000');

        $this->assertEquals($merchantDetail['international_activation_flow'], 'greylist');
    }

    public function testMerchantProductInternationalEnableBulkEditFailure()
    {
        $this->setMerchantMerchantDetailsAndPricing(true, 'whitelist');

        $this->fixtures->edit('merchant', '10000000000000', [
            'product_international'   => '1111000000']);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function setMerchantMerchantDetailsAndPricing($international, $internationalActivationFlow)
    {
        $this->fixtures->edit('merchant', '10000000000000', [
            'pricing_plan_id' => '1In3Yh5Mluj605',
            'international'   => $international]);

        $this->fixtures->create('merchant_detail', [
            'merchant_id'                   => '10000000000000',
            'international_activation_flow' => $internationalActivationFlow,
            'business_category'             => 'education',
            'business_subcategory'          => 'college']);

        $this->fixtures->pricing->createPromotionalPlan();

        $this->fixtures->edit('pricing', '1AXp2Xd3t5aRLX', ['international' => true]);
    }

    public function mockRazorX(string $functionName, string $featureName, string $variant, $merchantId = '1cXSLlUU8V9sXl')
    {
        $testData = &$this->testData[$functionName];

        $uniqueLocalId = RazorXClient::getLocalUniqueId($merchantId, $featureName, Mode::TEST);

        $testData['request']['cookies'] = [RazorXClient::RAZORX_COOKIE_KEY => '{"' . $uniqueLocalId . '":"' . $variant . '"}'];

    }

    public function testEditMerchantWebsite()
    {
        $merchant = $this->fixtures->edit('merchant', '10000000000000', [
            'pricing_plan_id'     => '1In3Yh5Mluj605',
            'international'       => false,
            'website'             => 'http://example.com',
            'whitelisted_domains' => ['example.com']
        ]);

        $this->fixtures->pricing->createPromotionalPlan();

        $this->fixtures->create('merchant_detail', [
            'merchant_id'      => '10000000000000',
            'business_website' => 'http://example.com']);

        $this->ba->adminAuth();

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', '10000000000000');

        $this->assertEquals(['abc.com'], $merchant->getWhitelistedDomains());
    }

    public function testGetCheckoutRouteWithTokenForDCC()
    {
        $this->ba->publicAuth();
        $this->fixtures->merchant->activate('10000000000000');

        $request = [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'contact' => '9988776655',
                'customer_id' => 'cust_100000customer',
                'currency' => 'INR',
            ]
        ];

        $response = $this->sendRequest($request);
        $responseContent = json_decode($response->getContent(), true);

        $this->assertNotNull($responseContent['customer']['tokens']);

        $tokens = $responseContent['customer']['tokens'];
        $this->assertTrue($tokens['count'] > 0);
        $this->assertTrue(array_key_exists('dcc_enabled', $tokens['items'][0]) === true);
    }

    public function testRequestMerchantProductInternational()
    {
        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $this->fixtures->edit('merchant','10000000000000', ['product_international' => '0000000000']);

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest();
    }

    public function testRequestMerchantProductInternationalFailure()
    {
        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest();
    }

    public function testGetProductInternationalStatus()
    {
        $this->fixtures->edit('merchant', '10000000000000', [
            'product_international' => '0000000000']);

        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $pgPermission = $this->getDbEntity('permission', ['name' => 'edit_merchant_pg_international']);

        $prod2Permission = $this->getDbEntity('permission', ['name' => 'edit_merchant_prod_v2_international']);

        $this->fixtures->create('workflow_action', [
            'entity_id'     => '10000000000000',
            'entity_name'   => 'merchant',
            'state'         => 'rejected',
            'permission_id' => $pgPermission->getId()
        ]);

        $this->fixtures->create('workflow_action', [
            'entity_id'     => '10000000000000',
            'entity_name'   => 'merchant',
            'state'         => 'executed',
            'permission_id' => $prod2Permission->getId()
        ]);

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest();
    }

    public function testGetProductInternationalStatusOldWorkflow()
    {
        $this->fixtures->edit('merchant', '10000000000000', [
            'product_international' => '0000000000']);

        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $oldPermission = $this->getDbEntity('permission', ['name' => 'edit_merchant_international_new']);

        $this->fixtures->create('workflow_action', [
            'entity_id'     => '10000000000000',
            'entity_name'   => 'merchant',
            'state'         => 'rejected',
            'permission_id' => $oldPermission->getId()
        ]);

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest();
    }

    public function testOrgLevelFeatureAccessForSubMerchantCreateLoginAsMerchantPass()
    {
        $testData = $this->testData['testCreateSubmerchantWithCode'];

        $testData['request']['headers'] = [ 'X-Dashboard-AdminLoggedInAsMerchant' => true];

        $this->testData[__FUNCTION__] = $testData;

        // need this to create sub merchant
        $this->fixtures->merchant->addFeatures(['marketplace', 'route_code_support']);

        // create feature for org to block route access
        $this->fixtures->create('feature', [
            'name'        => 'sub_merchant_create',
            'entity_id'   => '100000razorpay',
            'entity_type' => 'org'
        ]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testOrgLevelFeatureAccessForSubMerchantCreateFail()
    {
        $request = [
            'url'       => '/submerchants',
            'method'    => 'post',
            'headers'   =>  [ 'X-Dashboard-AdminLoggedInAsMerchant' => false],
        ];

        $routeFeatureName = 'sub_merchant_create';

        $this->startTestForOrgLevelFeatureAccessValidation($request, $routeFeatureName);
    }

    public function testOrgLevelFeatureAccessForReserveBalanceTicket()
    {
        $request = [
            'url'    => '/fd/reserve_balance/tickets',
            'method' => 'post'
        ];

        $routeFeatureName = 'freshdesk_create_ticket';

        $this->startTestForOrgLevelFeatureAccessValidation($request, $routeFeatureName);
    }

    public function testOrgLevelFeatureAccessForBankAccountUpdate()
    {
        $request = [
            'url'    => '/merchants/bank_account/update',
            'method' => 'post'
        ];

        $routeFeatureName = 'bank_account_update_ss';

        $this->startTestForOrgLevelFeatureAccessValidation($request, $routeFeatureName);
    }

    public function testOrgLevelFeatureAccessForCreatingFreshDeskTicket()
    {
        $request = [
            'url'    => '/fd/support_dashboard/ticket',
            'method' => 'post'
        ];

        $routeFeatureName = 'freshdesk_create_ticket';

        $this->startTestForOrgLevelFeatureAccessValidation($request, $routeFeatureName);
    }

    protected function startTestForOrgLevelFeatureAccessValidation($request, $routeFeature)
    {
        $testData = $this->testData['testOrgLevelFeatureAccess'];

        $testData['request'] = $request;

        $this->testData[__FUNCTION__] = $testData;

        $merchant = $this->fixtures->create('merchant');

        $attributes = [
            'name'        => $routeFeature,
            'entity_id'   => $merchant->getOrgId(),
            'entity_type' => 'org'
        ];

        $this->fixtures->create('feature', $attributes);

        $this->ba->proxyAuth('rzp_test_' . $merchant['id']);

        $this->startTest();
    }

    public function testCreatedMerchantHasPlServiceFeatureFlag()
    {
        $content = $this->createMerchant();

        $merchantId = $content['id'];

        $testFeaturesArray = $this->getDbEntity('feature',
            [
                'entity_id' => $merchantId,
                'entity_type' => 'merchant'
            ])->pluck('name')->toArray();

        $liveFeaturesArray = $this->getDbEntity('feature',
            [
                'entity_id' => $merchantId,
                'entity_type' => 'merchant'
            ],
            'live')->pluck('name')->toArray();

        $this->assertContains('paymentlinks_v2', $testFeaturesArray);
        $this->assertContains('paymentlinks_v2', $liveFeaturesArray);

    }

    public function testGetCheckoutRouteWithSavedGlobalVault()
    {
        $app = App::getFacadeRoot();

        $this->mockCardVault();

        $this->ba->publicAuth();

        $this->fixtures->merchant->activate('10000000000000');

        $response = $this->startTest();

        $this->assertNotNull($response['customer']['tokens']);

        $this->assertEquals($response['options']['remember_customer'], true);

        $cardVault = Mockery::mock('RZP\Services\CardVault', [$app])->makePartial();

        $this->app->instance('card.cardVault', $cardVault);

        $this->count = 0;

        $cardVault->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), 'post', Mockery::type('array'))
            ->andReturnUsing(function ($route, $method, $input)
            {
                $response = [
                    'error' => '',
                    'success' => false,
                ];
                return $response;
            });

        $this->app->instance('card.cardVault', $cardVault);

        $response = $this->startTest();


        $this->assertEquals($response['options']['remember_customer'], false);

        $cardVault = Mockery::mock('RZP\Services\CardVault', [$app])->makePartial();

        $this->app->instance('card.cardVault', $cardVault);

        $cardVault->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), 'post', Mockery::type('array'))
            ->andReturnUsing(function ($route, $method, $input)
            {
                $response = [];
                $response['token'] = base64_encode($input['secret']);
                $response['fingerprint'] = base64_encode($input['secret']);
                $response['scheme'] = "1";
                return $response;
            });

        $this->app->instance('card.cardVault', $cardVault);

        $response = $this->startTest();


        $this->assertEquals($response['options']['remember_customer'], false);

    }

    public function testGetCheckoutRouteWithoutCardTokenNames()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->activate('10000000000000');

        $this->ba->publicAuth();

        $response = $this->startTest();

        $noOfTokens = count($response['customer']['tokens']);

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['name'] = '';
        $payment['customer_id'] = 'cust_100000customer';
        $payment['save'] = 1;

        $this->doAuthAndCapturePayment($payment);

        $card = $this->getLastEntity('card', true);

        $token = $this->getLastEntity('token', true);

        $this->assertEmpty($card['name']);

        $this->assertEquals('card_'.$token['card_id'], $card['id']);

        $this->ba->publicAuth();

        $response = $this->startTest();

        $this->assertEquals(count($response['customer']['tokens']), $noOfTokens);
    }

    public function testGetPreferencesInternal()
    {
        $this->ba->terminalsAuth();

        $this->startTest();
    }

    public function testGetAutoDisabledMethodsForMerchant()
    {
        $this->ba->terminalsAuth();

        $this->startTest();
    }

    // tests that even if a method is blacklisted in the listed, its not returned if its in ignoreBlacklisted list
    public function testGetAutoDisabledMethodsForMerchantWithIgnoreBlacklistedForInstrument()
    {
        $this->fixtures->merchant->edit('10000000000000', [
            MerchantEntity::CATEGORY          => '6211',
            MerchantEntity::CATEGORY2         => 'mutual_funds',
        ]);

        $this->ba->terminalsAuth();

        $this->startTest();
    }

    public function testGetAutoDisabledMethodsForBlacklistedCategoryMerchant()
    {
        $this->fixtures->merchant->edit('10000000000000', [
            MerchantEntity::CATEGORY          => '6051',
            MerchantEntity::CATEGORY2         => 'cryptocurrency',
        ]);

        $this->ba->terminalsAuth();

        $this->startTest();
    }

    public function testGetAutoDisabledMethodsForMerchantWithRandomCategory()
    {
        $this->fixtures->merchant->edit('10000000000000', [
            MerchantEntity::CATEGORY          => '1234',
            MerchantEntity::CATEGORY2         => 'random',
        ]);

        $this->ba->terminalsAuth();

        $this->startTest();
    }

    public function testGetAutoDisabledMethodsFor5399EcommerceMerchant()
    {
        $this->fixtures->merchant->edit('10000000000000', [
            MerchantEntity::CATEGORY          => '5399',
            MerchantEntity::CATEGORY2         => 'ecommerce',
        ]);

        $this->ba->terminalsAuth();

        $this->startTest();
    }

    public function testGetAutoDisabledMethodsFor5399OthersMerchant()
    {
        $this->fixtures->merchant->edit('10000000000000', [
            MerchantEntity::CATEGORY          => '5399',
            MerchantEntity::CATEGORY2         => 'others',
        ]);

        $this->ba->terminalsAuth();

        $this->startTest();
    }

    public function testCreateSubmerchantWithCode()
    {
        $this->fixtures->merchant->addFeatures(['marketplace', 'route_code_support']);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCreateSubmerchantWithInvalidCode()
    {
        $this->fixtures->merchant->addFeatures(['marketplace', 'route_code_support']);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testEditMerchantCategoryShouldResetMethods()
    {
        $this->createMerchant();

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, 'org_'.$this->org->id);

        $this->startTest();

        $methodsArray =  ((new MethodRepo)->find('1X4hRFHFx4UiXt'))->toArray();

        $expectedMethods = [
            'credit_card'   => false,
            'debit_card'    => true,
            'amex'          => false,
            'netbanking'    => true,
            'upi'           => true,
            'emi'           => [], // emi disabled
            'prepaid_card'  => false,
            'paylater'      => false,
            'airtelmoney'   => true,
            'freecharge'    => true,
            'jiomoney'      => true,
            'mobikwik'      => true,
            'mpesa'         => true,
            'olamoney'      => true,
            'payumoney'     => true,
            'payzapp'       => true,
            'sbibuddy'      => true,
        ];

        $this->assertArraySelectiveEquals($expectedMethods, $methodsArray);

        $cardNetworks = $methodsArray['card_networks'];

        $expectedCardNetworks =  [
            Network::AMEX   =>  0,
        ];

        $this->assertArraySelectiveEquals($expectedCardNetworks, $cardNetworks);
    }

    public function testEditMerchantCategoryShouldNotResetMethodsIfResetMethodsInInputIsFalse()
    {
        $this->createMerchant();

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, 'org_'.$this->org->id);

        $this->startTest();

        $methodsArray =  ((new MethodRepo)->find('1X4hRFHFx4UiXt'))->toArray();

        $expectedMethods = [
            'credit_card'   => true,
            'debit_card'    => true,
            'amex'          => false,
            'netbanking'    => true,
            'upi'           => true,
            'emi'           => [], // emi disabled
            'prepaid_card'  => true,
            'paylater'      => true,
            'airtelmoney'   => true,
            'freecharge'    => true,
        ];

        $this->assertArraySelectiveEquals($expectedMethods, $methodsArray);

        $cardNetworks = $methodsArray['card_networks'];

        $expectedCardNetworks =  [
            Network::AMEX   =>  0,
        ];

        $this->assertArraySelectiveEquals($expectedCardNetworks, $cardNetworks);
    }

    public function testEditMerchantCategoryShouldResetMethodsValidationFailure2()
    {
        $this->createMerchant();

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, 'org_'.$this->org->id);

        $this->expectException(BadRequestValidationFailureException::class);

        $this->expectExceptionCode(
            ErrorCode::BAD_REQUEST_VALIDATION_FAILURE);

        $this->expectExceptionMessage(
            'The reset methods field must be true or false.');

        $this->startTest();
    }

    public function testCreateSubmerchantWithCodeAlreadyInUse()
    {
        $this->fixtures->merchant->addFeatures(['marketplace', 'route_code_support']);

        $testData = $this->testData['testCreateSubmerchantWithCode'];

        $this->makeRequestAndCatchException(
            function() use ($testData)
            {
                $this->ba->proxyAuth();

                $this->runRequestResponseFlow($testData);

                $this->runRequestResponseFlow($testData);
            },
            BadRequestException::class,
            'This code is already in use, please try another.'
        );
    }

    protected function setupMerchantForBankAccountUpdateTestViaPennyTesting($testcasename, $createBankAccount = true, $merchantDetails = [])
    {
        Mail::fake();

        $this->updateUploadDocumentData($testcasename, 'address_proof_url');

        $merchant = $this->fixtures->create('merchant', ['name' => 'testname']);

        $this->fixtures->user->createUserMerchantMappingForDefaultUser($merchant->id);

        $merchantId = $merchant['id'];

        $merchantDetails = array_merge($merchantDetails, [
            'merchant_id'           => $merchantId,
            'address_proof_url'     => 'old_address_proof_file_url',
        ]);

        $this->fixtures->create('merchant_detail:valid_fields', $merchantDetails);

        if ($createBankAccount === true)
        {
            $this->fixtures->merchant->createBankAccount(['merchant_id' => $merchantId, 'entity_id' => $merchantId]);
        }

        $user = $this->fixtures->create('user', ['email' => 'testingemail@gmail.com', 'contact_mobile' => '1234567890', 'contact_mobile_verified' => true]);

        $this->createMerchantUserMapping($user['id'], $merchantId, 'admin');

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        return $merchantId;
    }

    private function assertBankAccountForMerchant($merchantId, $expectedBankAccount)
    {
        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $merchant = (new Merchant\Repository)->findOrFail($merchantId);

        $actualBankAccount = (new Repository)->getBankAccount($merchant)->toArrayPublic();

        $this->assertArraySelectiveEquals($expectedBankAccount, $actualBankAccount);
    }

    private function setupWorkflowForBankAccountUpdate(): void
    {
        $this->fixtures->on('live')->create('org:admin_for_razorpay_org');

        $permission = $this->getDbEntity('permission', ['name' => 'edit_merchant_bank_detail'], 'live');

        DB::connection('live')->table('permission_map')->insert(
            [
                'entity_id' => Org::RZP_ORG,
                'entity_type' => 'org',
                'permission_id' => $permission->getId(),
            ]);

        $org = (new OrgRepository)->getRazorpayOrg();

        $this->fixtures->on('live')->create('org:workflow_users', ['org' => $org]);


        $this->createWorkflow([
            'org_id' => '100000razorpay',
            'name' => 'merchant bank account update workflow',
            'permissions' => ['edit_merchant_bank_detail'],
            'levels' => [
                [
                    'level' => 1,
                    'op_type' => 'or',
                    'steps' => [
                        [
                            'reviewer_count' => 1,
                            'role_id' => Org::ADMIN_ROLE,
                        ],
                    ],
                ],
            ],
        ]);

        // as this workflow is created in a worker context, we want to assert that the workflow maker is set correctly
        // to simulate this, we will initialize the worker maker to someother merchant(eg: 10000000000000).
        // then after the workflow is created in the test, we will assert that the maker changed from 10000000000000 to
        // the correct merchant id
        $this->app['basicauth']->setMerchant(((new Merchant\Repository())->findOrFail('10000000000000')));

        $this->app['workflow']->initWorkflowMaker();
    }

    protected function createMerchantUserMapping(string $userId, string $merchantId, string $role, $mode = 'test', $product = 'primary')
    {
        DB::connection($mode)->table('merchant_users')
            ->insert([
                'merchant_id' => $merchantId,
                'user_id'     => $userId,
                'product'     => $product,
                'role'        => $role,
                'created_at'  => 1493805150,
                'updated_at'  => 1493805150
            ]);
    }

    protected function setMockUfhServiceResponseForGetSignedUrl($response)
    {
        $defaultResponse = [
            'id'            => 'file_DM6dXJfU4WzeAFb',
            'type'          => 'amfi_certificate',
            'name'          => 'myfile2.pdf',
            'bucket'        => 'test_bucket',
            'mime'          => 'text/csv',
            'extension'     => 'csv',
            'merchant_id'   => '10000000000000',
            'store'         => 's3',
            'signed_url'    => 'paper-mandate/generated/ppm_DczOAf1V7oqaDA_DczOEhobMkq2Do.pdf'
        ];

        $response = array_merge($defaultResponse, $response);

        $ufhServiceMock = $this->getMockBuilder(Mock\UfhService::class)
            ->setConstructorArgs([$this->app])
            ->setMethods([ 'getSignedUrl'])
            ->getMock();

        $this->app->instance('ufh.service', $ufhServiceMock);

        $ufhServiceMock->method( 'getSignedUrl')
            ->willReturn($response);
    }

    private function getBankAccountsCount($merchantId)
    {
        $merchant = (new Merchant\Repository)->findOrFail($merchantId);

        $bankAccounts = (new Repository)->getAllBankAccounts($merchant);

        return $bankAccounts->count();
    }

    /**
     * @param $merchantId
     * @return mixed
     */
    protected function getBankAccountChangeStatusForMerchant($merchantId): bool
    {
        $merchant = (new Merchant\Repository)->findOrFail($merchantId);

        $this->app['basicauth']->setOrgId($merchant->getOrgId());

        return (new Merchant\Service())->getBankAccountChangeStatus($merchantId);
    }

    protected function isMerchantAllowedtoSubmitSupportCallRequest($merchantId = '10000000000000')
    {
        $this->ba->proxyAuth();

        $response = $this->makeRequestAndGetContent(
            [
                'url'    => '/merchants/support_call/can_submit',
                'method' => 'GET',
            ]
        );

        return $response['response'];
    }

    public function testMerchantSupportOptions()
    {
        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                              function ($merchantId, $feature, $mode)
                              {
                                  if ($feature === "DEDUPE_FUNCTIONALITY" || $feature === "show_create_ticket_popup")
                                  {
                                      return 'on';
                                  }
                                  else
                                  {
                                      return "control";
                                  }

                              }) );

        $this->ba->proxyAuth();

        $testCases = $this->getTestCasesForTestMerchantSupportOptions();

        $this->fixtures->on('live')->create('state', [
            'entity_id'   => '10000000000000',
            'entity_type' => 'merchant_detail',
            'name'        => 'under_review',
            'created_at'  =>  1539543931
        ]);

        foreach ($testCases as $testCase)
        {
            $this->testData[__FUNCTION__]['response'] = $testCase[self::RESPONSE];

            $this->testData[__FUNCTION__]['request'] = $testCase[self::REQUEST];

            $this->createTestDataForTestMerchantSupportOptions($testCase);

            $this->startTest();

            Carbon::setTestNow(Carbon::createFromTimestamp(1617883696)); // timestamp corresponds to valid chat timing on a working day
        }
    }

    protected function getTestCasesForTestMerchantSupportOptions()
    {
        $popupDataForActivationStatus = MerchantConstants::TICKET_CREATION_POPUP_DATA_FOR_ACTIVATION_STATUS;

        $popupDataForFormFillRange = MerchantConstants::TICKET_CREATION_POPUP_DATA_FOR_ACTIVATION_PROGRESS_RANGES;

        return [
            [
                self::REQUEST       => [
                    'url'      => '/merchants/support/option/flags',
                    'method'   => \Requests::GET
                ],
                self::RESPONSE       => [
                    'content' => [
                        "show_chat"                 =>  false,
                        "show_create_ticket_popup"  =>  true,
                        "message_body"              =>  "We received your activation form on October 14, 2018. Your documents and KYC details are under review. It usually takes 3-4 working days for our team to review your documents. We will reach out if we need any other clarification. Please go through our FAQs if you have any other queries.",
                        "cta_list"                  =>  $popupDataForActivationStatus[ActivationStatus::UNDER_REVIEW][MerchantConstants::X_HOURS_AFTER_ACTIVATION_FORM_SUBMISSION][MerchantConstants::CTA_LIST],
                    ],
                ],
                self::CREATE_MERCHANT_DETAILS   =>  [
                    'merchant_id'                   => '10000000000000',
                    'international_activation_flow' => 'greylist',
                    'business_category'             => 'education',
                    'business_subcategory'          => 'alcohol',
                    'activation_status'             => 'under_review',
                    'submitted_at'                  => 1539543931,
                    'business_type'                 => 1
                ],
                'time'                  => Carbon::createFromDate(2019, 10, 16, Timezone::IST),
            ],
            [
                self::REQUEST       => [
                    'url'      => '/merchants/support/option/flags',
                    'method'   => \Requests::GET
                ],
                self::RESPONSE       => [
                    'content' => [
                        "show_chat"                 =>  false,
                        "show_create_ticket_popup"  =>  true,
                        "cta_list"                  => $popupDataForActivationStatus[ActivationStatus::UNDER_REVIEW][MerchantConstants::X_HOURS_WITHIN_ACTIVATION_FORM_SUBMISSION][MerchantConstants::CTA_LIST],
                        "message_body"              => "We received your activation form on October 14, 2018. Your documents and KYC details are under review. It usually takes 3-4 working days for our team to review your documents. We will reach out if we need any clarification. Please go through our FAQs if you have any other queries.",
                    ],
                ],
                self::EDIT_MERCHANT_DETAILS     =>  [
                    'merchant_id'                   => '10000000000000',
                    'activation_flow'               => 'greylist',
                    'business_category'             => 'education',
                    'business_subcategory'          => 'alcohol',
                    'activation_status'             => 'under_review',
                    'submitted_at'                  => 1539543931,
                    'business_type'                 => 2
                ],
                'time'                  => Carbon::createFromDate(2018, 10, 15, Timezone::IST),
            ],
            [
                self::REQUEST       => [
                    'url'      => '/merchants/support/option/flags',
                    'method'   => \Requests::GET
                ],
                self::RESPONSE       => [
                    'content' => [
                        "show_chat"                 =>  false,
                        "show_create_ticket_popup"  =>  true,
                        "cta_list"                  => $popupDataForActivationStatus[ActivationStatus::NEEDS_CLARIFICATION][MerchantConstants::NON_DEDUPE_MERCHANT][MerchantConstants::CTA_LIST],
                        "message_body"              => $popupDataForActivationStatus[ActivationStatus::NEEDS_CLARIFICATION][MerchantConstants::NON_DEDUPE_MERCHANT][MerchantConstants::MESSAGE],
                    ],
                ],
                self::EDIT_MERCHANT_DETAILS   =>  [
                    'merchant_id'                   => '10000000000000',
                    'business_category'             => 'education',
                    'business_subcategory'          => 'alcohol',
                    'activation_status'             => 'needs_clarification',
                    'submitted_at'                  => 1539543931,
                    'business_type'                 => 2,
                    'activation_progress'           => 23,
                ]
            ],
            [
                self::REQUEST       => [
                    'url'      => '/merchants/support/option/flags',
                    'method'   => \Requests::GET
                ],
                self::RESPONSE       => [
                    'content' => [
                        "show_chat"                 => true,
                        "show_create_ticket_popup"  => true,
                        "cta_list"                  => $popupDataForActivationStatus[ActivationStatus::REJECTED][MerchantConstants::DEFAULT][MerchantConstants::CTA_LIST],
                        "message_body"              => $popupDataForActivationStatus[ActivationStatus::REJECTED][MerchantConstants::DEFAULT][MerchantConstants::MESSAGE],
                    ],
                ],
                self::EDIT_MERCHANT_DETAILS   =>  [
                    'merchant_id'                   => '10000000000000',
                    'activation_flow'               => 'whitelist',
                    'business_category'             => 'education',
                    'business_subcategory'          => 'college',
                    'business_type'                 => 3,
                    'activation_status'             => 'rejected',
                    'submitted_at'                  => 1539543931,
                ]
            ],
            [
                self::REQUEST       => [
                    'url'      => '/merchants/support/option/flags',
                    'method'   => \Requests::GET
                ],
                self::RESPONSE       => [
                    'content' => [
                        "show_chat"                 => true,
                        "show_create_ticket_popup"  => true,
                        "cta_list"                  => $popupDataForFormFillRange[0][MerchantConstants::CTA_LIST],
                        "message_body"              => $popupDataForFormFillRange[0][MerchantConstants::MESSAGE],
                    ]
                ],
                self::EDIT_MERCHANT_DETAILS   =>  [
                    'merchant_id'                   => '10000000000000',
                    'activation_flow'               => 'whitelist',
                    'business_category'             => 'education',
                    'business_subcategory'          => 'college',
                    'activation_status'             => null,
                    'business_type'                 => 3,
                    'submitted'                     => false,
                    'activation_progress'           => 0,
                ]
            ],
            [
                self::REQUEST       => [
                    'url'      => '/merchants/support/option/flags',
                    'method'   => \Requests::GET
                ],
                self::RESPONSE       => [
                    'content' => [
                        "show_chat"                 =>  true,
                        "show_create_ticket_popup"  =>  true,
                        "cta_list"                  => $popupDataForFormFillRange[1][MerchantConstants::CTA_LIST],
                        "message_body"              => $popupDataForFormFillRange[1][MerchantConstants::MESSAGE],
                    ],
                ],
                self::EDIT_MERCHANT_DETAILS     =>  [
                    'merchant_id'                   => '10000000000000',
                    'activation_flow'               => 'whitelist',
                    'business_category'             => 'education',
                    'business_subcategory'          => 'college',
                    'activation_status'             => null,
                    'business_type'                 => 4,
                    'submitted'                     => false,
                    'activation_progress'           => 23,
                ]
            ],
            [
                self::REQUEST       => [
                    'url'      => '/merchants/support/option/flags',
                    'method'   => \Requests::GET
                ],
                self::RESPONSE       => [
                    'content' => [
                        "show_chat"                 =>  true,
                        "show_create_ticket_popup"  =>  false
                    ],
                ],
                self::ACTIVATE_MERCHANT =>  [1]
            ],
            [
                self::REQUEST       => [
                    'url'      => '/merchants/support/option/flags',
                    'method'   => \Requests::GET
                ],
                self::RESPONSE       => [
                    'content' => [
                        "show_chat"                 =>  false,
                        "show_create_ticket_popup"  =>  false
                    ],
                ],
                self::ACTIVATE_MERCHANT =>  [1],
                'time'                  => Carbon::createFromTime(2, 0, 0, Timezone::IST),
            ],
            //holiday
            [
                self::REQUEST       => [
                    'url'      => '/merchants/support/option/flags',
                    'method'   => \Requests::GET
                ],
                self::RESPONSE       => [
                    'content' => [
                        "show_chat"                 =>  false,
                        "show_create_ticket_popup"  =>  false
                    ],
                ],
                self::ACTIVATE_MERCHANT =>  [1],
                'time'                  => Carbon::createFromDate(2021, 4, 13, Timezone::IST),
            ],
        ];

    }

    protected function createTestDataForTestMerchantSupportOptions($testCase)
    {
        if (isset($testCase['time']) === true)
        {
            Carbon::setTestNow($testCase['time']);
        }

        if (array_key_exists(self::CREATE_MERCHANT_DETAILS, $testCase) === true)
        {
            $this->fixtures->create('merchant_detail',
                                    $testCase[self::CREATE_MERCHANT_DETAILS]);
        }

        if (array_key_exists(self::EDIT_MERCHANT_DETAILS, $testCase) === true)
        {
            $this->fixtures->edit('merchant_detail','10000000000000',
                $testCase[self::EDIT_MERCHANT_DETAILS]);
        }

        if (array_key_exists(self::ACTIVATE_MERCHANT, $testCase) === true) {
            $this->fixtures->merchant->activate();
        }
    }

    protected function setupWorkflow($workflowName, $permissionName, $mode ='live'): void
    {
        $this->fixtures->on('live')->create('org:admin_for_razorpay_org');

        $permission = $this->getDbEntity('permission', ['name' => $permissionName], 'live');

        DB::connection('live')->table('permission_map')->insert(
            [
                'entity_id' => Org::RZP_ORG,
                'entity_type' => 'org',
                'permission_id' => $permission->getId(),
            ]);

        $org = (new OrgRepository)->getRazorpayOrg();

        $this->fixtures->on('live')->create('org:workflow_users', ['org' => $org]);

        $workflow = $this->createWorkflow([
            'org_id' => '100000razorpay',
            'name' => $workflowName,
            'permissions' => [ $permissionName ],
            'levels' => [
                [
                    'level' => 1,
                    'op_type' => 'or',
                    'steps' => [
                        [
                            'reviewer_count' => 1,
                            'role_id' => Org::ADMIN_ROLE,
                        ],
                    ],
                ],
            ],
        ],$mode);

    }

    protected function getExpectedArraysForWorkflowObserverTestCases($arrayType) : array
    {
        if ($arrayType === self::METHODS_EXPECTED_WORKFLOW_ES_DATA_WITH_OBSERVER)
        {
            return [
                'url' => "https://api.razorpay.com/v1/merchants/10000000000000/methods",
                'method' => "PUT",
                'payload' => [
                    'workflow_observer_data' =>  [
                        'ticket_id' => 123,
                        'fd_instance' => "rzp"
                    ],

                ],
                'state' => "open",
                'route' => "merchant_put_payment_methods"
            ];
        }

        if ($arrayType === self::EMAIL_EXPECTED_WORKFLOW_ES_DATA_WITH_OBSERVER)
        {
            return [
                'url' => "https://api.razorpay.com/v1/merchants/10000000000044/email",
                'method' => "PUT",
                'payload' => [
                    'workflow_observer_data' =>  [
                        'ticket_id' => 123,
                        'fd_instance' => "rzp"
                    ],

                ],
                'state' => "open",
                'route' => "merchant_edit_email"
            ];
        }

        if ($arrayType === self::HOLD_FUNDS_WORKFLOW_ES_DATA_WITH_OBSERVER)
        {
            return [
                'url' => "https://api.razorpay.com/v1/merchants/10000000000000/action",
                'method' => "PUT",
                'payload' => [
                    'workflow_observer_data' =>  [
                        'ticket_id' => 123,
                        'fd_instance' => "rzp"
                    ],

                ],
                'state' => "open",
                'route' => "merchant_actions"
            ];
        }

        if ($arrayType === self::RELEASE_FUNDS_WORKFLOW_ES_DATA_WITH_OBSERVER)
        {
            return [
                'url' => "https://api.razorpay.com/v1/merchants/10000000000000/action",
                'method' => "PUT",
                'payload' => [
                    'workflow_observer_data' =>  [
                        'ticket_id' => 123,
                        'fd_instance' => "rzp"
                    ],

                ],
                'state' => "open",
                'route' => "merchant_actions"
            ];
        }

        if ($arrayType === self::METHODS_EXPECTED_WORKFLOW_ES_DATA_WITHOUT_OBSERVER)
        {
            return [
                'url' => "https://api.razorpay.com/v1/merchants/10000000000000/methods",
                'method' => "PUT",
                'payload' => [
                ],
                'state' => "open",
                'route' => "merchant_put_payment_methods"
            ];
        }

    }

    private function mockCredEligibilityResponse($gatewayResponse = null, \Throwable $gatewayException = null, array $pRequest = null)
    {
        $this->fixtures->customer->create(
            [
                'id'            => '1000ggcustomer',
                'name'          => 'test123',
                'email'         => 'test@razorpay.com',
                'contact'       => '+919671967980',
                'merchant_id'   => '10000000000000'
            ]
        );

        $this->fixtures->merchant->activate('10000000000000');

        $this->fixtures->merchant->enableApp('10000000000000', 'cred');

        $this->fixtures->merchant->addFeatures(['cred_merchant_consent']);

        $this->fixtures->create('terminal:direct_cred_terminal');

        $order = $this->fixtures->order->create(['receipt' => 'check123', 'amount' => '100', 'app_offer' => true]);

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::ENABLE_CRED_ELIGIBILITY_CALL => true]);

        $defaultRequest = [
            'url'     => '/preferences',
            'method'  => 'get',
            'content' => [
                'currency' => [
                    'INR'
                ],
                'customer_id' => 'cust_1000ggcustomer',
                'order_id'    => $order->getPublicId(),
            ],
        ];

        $request = $pRequest ?? $defaultRequest;

        $gateway = Mockery::mock('RZP\Gateway\GatewayManager');

        $gateway->shouldReceive('call')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'),
                Mockery::type('string'), Mockery::type('RZP\Models\Terminal\Entity'))->andReturnUsing
            (function ($gateway, $action, $input, $mode, $terminal) use ($gatewayResponse, $gatewayException)
            {
                if (is_null($gatewayException) === false)
                {
                    throw $gatewayException;
                }

                return $gatewayResponse;
            });

        $this->app->instance('gateway', $gateway);

        $this->ba->publicAuth();

        return $this->makeRequestAndGetContent($request);
    }

    public function testGetCheckoutPreferencesAfterCredEligibility()
    {
        $response = $this->mockCredEligibilityResponse(
            null,
            new GatewayErrorException(ErrorCode::BAD_REQUEST_CRED_CUSTOMER_NOT_ELIGIBLE)
        );

        $this->assertEquals(1, $response['methods']['app']['cred']);
        $this->assertEquals(false, $response['methods']['app_meta']['cred']['hit_eligibility']);
        $this->assertEquals(false, $response['methods']['app_meta']['cred']['user_eligible']);
    }

    public function testGetCheckoutPreferencesAfterCredEligibilityTimeout()
    {
        $response = $this->mockCredEligibilityResponse(
            null,
            new GatewayTimeoutException(ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT)
        );

        $this->assertEquals(1, $response['methods']['app']['cred']);
        $this->assertEquals(true, $response['methods']['app_meta']['cred']['hit_eligibility']);
        $this->assertArrayNotHasKey('offer', $response['methods']['app_meta']['cred']);
        $this->assertArrayNotHasKey('user_eligible', $response['methods']['app_meta']['cred']);
    }

    public function testGetCheckoutPreferencesAfterCredEligibilityOffersSubtext()
    {
        $this->enableRazorXTreatmentForFeature(
            Merchant\RazorxTreatment::CRED_OFFER_SUBTEXT,
            'sub_text'
        );

        $credOffer = 'pay seamlessly using your CRED coins. #killthebill';

        $gatewayResponse = [
            'data'    => [
                'state'       => 'ELIGIBLE',
                'tracking_id' => 'rand10001',
                'layout'      => [
                    'sub_text' => $credOffer,
                ],
            ]
        ];

        $response = $this->mockCredEligibilityResponse($gatewayResponse);

        $this->assertEquals(1, $response['methods']['app']['cred']);
        $this->assertEquals('sub_text', $response['methods']['app_meta']['cred']['experiment']);

        $this->assertEquals(false, $response['methods']['app_meta']['cred']['hit_eligibility']);

        $this->assertEquals($credOffer, $response['methods']['app_meta']['cred']['offer']['description']);

        $this->assertEquals(true, $response['methods']['app_meta']['cred']['user_eligible']);
    }

    public function testGetCheckoutPreferencesAfterCredEligibilityStickyOffersSubtext()
    {
        $credOffer = 'pay seamlessly using your CRED coins. #killthebill';

        $gatewayResponse = [
            'data'    => [
                'state'       => 'ELIGIBLE',
                'tracking_id' => 'rand10001',
                'layout'      => [
                    'sub_text' => $credOffer,
                ],
            ]
        ];

        $request = [
            'url'     => '/preferences',
            'method'  => 'get',
            'content' => [
                'currency' => [
                    'INR'
                ],
                'customer_id'           => 'cust_1000ggcustomer',
                'cred_offer_experiment' => 'subtext',
            ],
        ];

        $response = $this->mockCredEligibilityResponse($gatewayResponse, null, $request);

        $this->assertEquals(1, $response['methods']['app']['cred']);
        $this->assertEquals('subtext', $response['methods']['app_meta']['cred']['experiment']);

        $this->assertEquals($credOffer, $response['methods']['app_meta']['cred']['offer']['description']);

        $this->assertEquals(false, $response['methods']['app_meta']['cred']['hit_eligibility']);

        $this->assertEquals(true, $response['methods']['app_meta']['cred']['user_eligible']);
    }

    protected function mockMerchantImpersonated()
    {
        $action = [
            'keysToCheck' => [
                MerchantDetails::PROMOTER_PAN => [
                    'list' => 'blacklist',
                    'matchType'=> 'exact_match',
                ]
            ],
            'action' => 'deactivate'
        ];

        $mockedResponseForDetails = [];

        $mockedResponseForMatch = [];

        foreach ($action['keysToCheck'] as $fieldName => $data)
        {
            $mockedResponseForMatch[]   = [
                'field'     => $fieldName,
                'list'      => $data['list'],
                'score'     => 900,  // some random score
            ];

            $mockedResponseForDetails[] = [
                'field'     => $fieldName,
                'list'      => $data['list'],
                'score'     => 900,  // some random score
                'matched_entity' => [
                    [
                        'key' => 'id',
                        'value' => '10000000000'
                    ]
                ],
            ];
        }

        $merchantRiskClientMock = Mockery::mock('RZP\Services\MerchantRiskClient');

        $merchantRiskClientMock->shouldReceive('getMerchantImpersonatedDetails')->andReturn([
                                                                                                "client_type" => "onboarding",
                                                                                                "entity_id" => '10000000000000',
                                                                                                "fields" => $mockedResponseForDetails
                                                                                            ]);

        $merchantRiskClientMock->shouldReceive('getMerchantRiskScores')->andReturn([
            "client_type" => "onboarding",
            "entity_id" => '10000000000000',
            "fields" => $mockedResponseForMatch
        ]);

        $this->app->instance('merchantRiskClient', $merchantRiskClientMock);
    }

    public function testGetRiskData()
    {
        $druidService = $this->getMockBuilder(MockDruidService::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods([ 'getDataFromDruid'])
            ->getMock();

        $this->app->instance('druid.service', $druidService);

        $dataFromDruid = $this->testData[__FUNCTION__]['druid_response'];

        $druidService->method( 'getDataFromDruid')
            ->willReturn([null, [$dataFromDruid]]);

        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => PermissionName::GET_MERCHANT_RISK_DATA]);

        $role->permissions()->attach($perm->getId());

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testGetRiskDataNotFoundCase()
    {
        $druidService = $this->getMockBuilder(MockDruidService::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods([ 'getDataFromDruid'])
            ->getMock();

        $this->app->instance('druid.service', $druidService);

        $druidService->method( 'getDataFromDruid')
            ->willReturn([null, []]);

        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => PermissionName::GET_MERCHANT_RISK_DATA]);

        $role->permissions()->attach($perm->getId());

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testGetRiskDataDruidFailedCase()
    {
        $druidService = $this->getMockBuilder(MockDruidService::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods([ 'getDataFromDruid'])
            ->getMock();

        $this->app->instance('druid.service', $druidService);

        $druidService->method( 'getDataFromDruid')
            ->willReturn(["dummy druid error", null]);

        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => PermissionName::GET_MERCHANT_RISK_DATA]);

        $role->permissions()->attach($perm->getId());

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testGetRiskDataColumnNotPresent()
    {
        $druidService = $this->getMockBuilder(MockDruidService::class)
                             ->setConstructorArgs([$this->app])
                             ->onlyMethods([ 'getDataFromDruid'])
                             ->getMock();

        $this->app->instance('druid.service', $druidService);

        $dataFromDruid = $this->testData[__FUNCTION__]['druid_response'];

        $druidService->method( 'getDataFromDruid')
                     ->willReturn([null, [$dataFromDruid]]);

        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => PermissionName::GET_MERCHANT_RISK_DATA]);

        $role->permissions()->attach($perm->getId());

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testFireHubspotEventFromDashboard()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testFireHubspotEventFromDashboardForWrongEmailId()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->create('merchant_detail', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->expectException(\RZP\Exception\BadRequestException::class);

        $this->startTest();
    }

    public function testNeostoneSendFlagToSalesforce()
    {
        $this->ba->proxyAuth();

        $methodName = 'sendNeostoneFlag';

        $salesforceClientMock = $this->getMockBuilder(SalesForceClient::class)
                                     ->setConstructorArgs([$this->app])
                                     ->getMock();

        $this->app->instance('salesforce', $salesforceClientMock);

        $salesforceClientMock->expects($this->exactly(1))->method($methodName);

        $this->startTest();
    }

    public function testNeostoneSendFlagToSalesforceWrongMid()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->create('merchant_detail', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->expectException(\RZP\Exception\BadRequestException::class);

        $this->startTest();
    }

    public function testEditBulkMerchantActionCronFOH()
    {
        $this->createMerchant([
            'id'    => '10000000000044',
            'email' => 'test1@razorpay.com',
        ]);

        $this->setAdminForInternalAuth();

        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => 'edit_merchant_hold_funds_bulk']);

        $role->permissions()->attach($perm->getId());

        $this->ba->adminAuth('live');

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', '10000000000044');

        $this->assertEquals(1, $merchant['hold_funds']);
        $merchants = (new Merchant\Repository)->fetchMerchantsWithTag(Merchant\Constants::MERCHANT_RISK_FOH_CRON_TAG);
        $this->assertCount(1, $merchants, 'array empty'.$merchants);
        $this->assertEquals($merchant->getId(), $merchants[0]->getId(), 'id not matching'.$merchants[0]);
    }

    public function testEditBulkMerchantActionCronSuspend()
    {
        $this->createMerchant([
            'id'    => '10000000000044',
            'email' => 'test1@razorpay.com',
        ]);

        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => 'edit_merchant_suspend_bulk']);

        $role->permissions()->attach($perm->getId());

        $this->ba->adminAuth();

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', '10000000000044');

        $this->assertNotNull($merchant['suspended_at']);

        $merchants = (new Merchant\Repository)->fetchMerchantsWithTag(Merchant\Constants::MERCHANT_RISK_SUSPEND_CRON_TAG);
        $this->assertCount(1, $merchants, 'array empty'.$merchants);
        $this->assertEquals($merchant->getId(), $merchants[0]->getId(), 'id not matching'.$merchants[0]);
    }

    public function testMerchantActionNotificationCronFOH()
    {
        $this->createMerchant([
            'id'    => '10000000000044',
            'email' => 'test1@razorpay.com',
        ]);
        $merchant = $this->getDbEntityById('merchant', '10000000000044');
        (new MerchantCore())->appendTag($merchant, Merchant\Constants::MERCHANT_RISK_FOH_CRON_TAG);

        $merchants = (new Merchant\Repository)->fetchMerchantsWithTag(Merchant\Constants::MERCHANT_RISK_FOH_CRON_TAG);
        $this->assertCount(1, $merchants);

        $this->ba->cronAuth();
        $this->startTest();

        $merchants = (new Merchant\Repository)->fetchMerchantsWithTag(Merchant\Constants::MERCHANT_RISK_FOH_CRON_TAG);
        $this->assertCount(0, $merchants);
    }

    public function testMerchantActionNotificationCronSuspend()
    {
        $this->createMerchant([
            'id'    => '10000000000044',
            'email' => 'test1@razorpay.com',
        ]);
        $merchant = $this->getDbEntityById('merchant', '10000000000044');
        (new MerchantCore())->appendTag($merchant, Merchant\Constants::MERCHANT_RISK_SUSPEND_CRON_TAG);

        $merchants = (new Merchant\Repository)->fetchMerchantsWithTag(Merchant\Constants::MERCHANT_RISK_SUSPEND_CRON_TAG);
        $this->assertCount(1, $merchants);

        $this->ba->cronAuth();
        $this->startTest();

        $merchants = (new Merchant\Repository)->fetchMerchantsWithTag(Merchant\Constants::MERCHANT_RISK_SUSPEND_CRON_TAG);
        $this->assertCount(0, $merchants);
    }

    public function testEditBulkMerchantActionDisableLive()
    {
        $this->createMerchant([
            'id'    => '10000000000044',
            'email' => 'test1@razorpay.com',
        ]);

        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => 'edit_merchant_toggle_live_bulk']);

        $this->fixtures->merchant->edit('10000000000044', ['live' => true, 'activated' => 1]);
        $role->permissions()->attach($perm->getId());

        $this->ba->adminAuth();

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', '10000000000044');

        $this->assertFalse($merchant->isLive());

        $merchants = (new Merchant\Repository)->fetchMerchantsWithTag(Merchant\Constants::MERCHANT_RISK_DISABLE_LIVE_CRON_TAG);
        $this->assertCount(1, $merchants, 'array empty'.$merchants);
        $this->assertEquals($merchant->getId(), $merchants[0]->getId(), 'id not matching'.$merchants[0]);
    }

    public function testEditBulkMerchantActionEnableLive()
    {
        $this->createMerchant([
            'id'    => '10000000000044',
            'email' => 'test1@razorpay.com',
        ]);

        $this->fixtures->merchant->edit('10000000000044', ['live' => false, 'activated' => 1]);
        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => 'edit_merchant_toggle_live_bulk']);

        $role->permissions()->attach($perm->getId());

        $this->ba->adminAuth();

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', '10000000000044');

        $this->assertTrue($merchant->isLive());

        $merchants = (new Merchant\Repository)->fetchMerchantsWithTag(Merchant\Constants::MERCHANT_RISK_DISABLE_LIVE_CRON_TAG);
        $this->assertCount(0, $merchants, 'array is not empty'.$merchants);
    }


    public function testBulkDisableLiveWithoutPermissionFail()
    {
        $this->createMerchant([
            'id'    => '10000000000044',
            'email' => 'test1@razorpay.com',
        ]);

        $this->createMerchant([
            'id'    => '10000000000055',
            'email' => 'test2@razorpay.com',
        ]);

        $this->fixtures->merchant->edit('10000000000044', ['live' => true, 'activated' => 1]);
        $this->fixtures->merchant->edit('10000000000055', ['live' => true, 'activated' => 1]);

        $this->removePermission(PermissionName::EDIT_MERCHANT_TOGGLE_LIVE_BULK);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testBulkEnableLiveWithoutPermissionFail()
    {
        $this->createMerchant([
            'id'    => '10000000000044',
            'email' => 'test1@razorpay.com',
        ]);

        $this->createMerchant([
            'id'    => '10000000000055',
            'email' => 'test2@razorpay.com',
        ]);
        $this->fixtures->merchant->edit('10000000000044', ['live' => false, 'activated' => 1]);
        $this->fixtures->merchant->edit('10000000000055', ['live' => false, 'activated' => 1]);

        $this->removePermission(PermissionName::EDIT_MERCHANT_TOGGLE_LIVE_BULK);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testEditBulkDisableLiveMerchantNotLive()
    {
        $this->createMerchant([
            'id'    => '10000000000044',
            'email' => 'test1@razorpay.com',
        ]);

        $this->fixtures->merchant->edit('10000000000044', ['live' => false, 'activated' => 1]);

        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => 'edit_merchant_toggle_live_bulk']);

        $role->permissions()->attach($perm->getId());

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testEditBulkEnableLiveMerchantAlreadyLive()
    {
        $this->createMerchant([
            'id'    => '10000000000044',
            'email' => 'test1@razorpay.com',
        ]);

        $this->fixtures->merchant->edit('10000000000044', ['live' => true, 'activated' => 1]);

        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => 'edit_merchant_toggle_live_bulk']);

        $role->permissions()->attach($perm->getId());

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testEditBulkEnableLiveMerchantSuspended()
    {
        $this->createMerchant([
            'id'    => '10000000000044',
            'email' => 'test1@razorpay.com',
        ]);

        $this->fixtures->merchant->edit('10000000000044', ['live' => true, 'activated' => 1]);
        $this->fixtures->base->editEntity('merchant', '10000000000044', [ 'suspended_at' => '123456789' ]);
        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => 'edit_merchant_toggle_live_bulk']);

        $role->permissions()->attach($perm->getId());

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testPreferencesToCheckDisabledSbibuddyWallet()
    {
        $this->fixtures->create('terminal:shared_sbibuddy_terminal');
        $this->fixtures->merchant->enableWallet('10000000000000', 'sbibuddy');

        $this->ba->publicAuth();

        $response = $this->startTest();

        $this->assertArrayNotHasKey('sbibuddy', $response['methods']['wallet']);
    }

    public function testEditBulkEnableLiveMerchantNotActivated()
    {
        $this->createMerchant([
            'id'    => '10000000000044',
            'email' => 'test1@razorpay.com',
        ]);

        $this->fixtures->merchant->edit('10000000000044', ['live' => true, 'activated' => 0]);

        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => 'edit_merchant_toggle_live_bulk']);

        $role->permissions()->attach($perm->getId());

        $this->ba->adminAuth();

        $this->startTest();
    }


    public function testCheck2FAException()
    {
        $feature = 'email_update_2fa_enabled';

        $merchant = $this->fixtures->create('merchant');

        $this->fixtures->create(
            'feature',
            [
                'entity_id' => '100000razorpay',
                'name' => $feature,
                'entity_type' => 'org',
            ]);

        $this->fixtures->user->createUserMerchantMappingForDefaultUser($merchant['id']);
        $this->ba->proxyAuth('rzp_test_'.$merchant['id']);

        $this->startTest();
    }

    public function testCheck2FACorrectOTP()
    {

        $feature = 'email_update_2fa_enabled';

        $merchant = $this->fixtures->create('merchant');
        $this->fixtures->user->createUserMerchantMappingForDefaultUser($merchant->id);

        $this->fixtures->create(
            'feature',
            [
                'entity_id' => '100000razorpay',
                'name' => $feature,
                'entity_type' => 'org',
            ]);

        $this->ba->proxyAuth('rzp_test_'.$merchant['id']);

        $this->startTest();
    }

    public function testCheck2FAIncorrectOTP()
    {

        $feature = 'email_update_2fa_enabled';

        $merchant = $this->fixtures->create('merchant');

        $this->fixtures->create(
            'feature',
            [
                'entity_id' => '100000razorpay',
                'name' => $feature,
                'entity_type' => 'org',
            ]);

        $this->fixtures->user->createUserMerchantMappingForDefaultUser($merchant['id']);
        $this->ba->proxyAuth('rzp_test_'.$merchant['id']);

        $this->startTest();
    }

    public function testToggleFeeBearerToCustomer()
    {
        $merchant = $this->fixtures->create('merchant', ['activated' => 1]);

        $user = $this->fixtures->create('user');

        $this->createMerchantUserMapping($user->getId(), $merchant->getId(), 'owner');

        $this->fixtures->pricing->createStandardPricingPlanForFeeBearer('platform');

        $this->fixtures->merchant->edit($merchant['id'], ['fee_bearer' => 'platform']);

        $this->fixtures->merchant->edit($merchant['id'], ['pricing_plan_id' => '3R0Ssm31kRSKSS']);

        $this->ba->proxyAuth('rzp_test_'.$merchant['id'], $user->getId());

        $this->startTest();

        $merchantEntity = $this->getDbEntityById('merchant', $merchant['id']);

        $rules = (new PricingRepo())->getPlanByIdOrFailPublic($merchantEntity['pricing_plan_id']);

        $this->assertNotEquals($merchantEntity['pricing_plan_id'], '3R0Ssm31kRSKSS');

        $this->assertEquals($merchantEntity['fee_bearer'], 'customer');

        foreach ($rules as $rule)
        {
            $this->assertEquals($rule['fee_bearer'], 'customer');
        }

        $this->assertEquals(sizeof($rules), 13);
    }

    public function testToggleFeeBearerToPlatform()
    {
        $merchant = $this->fixtures->create('merchant', ['activated' => 1]);

        $user = $this->fixtures->create('user');

        $this->createMerchantUserMapping($user->getId(), $merchant->getId(), 'owner');

        $this->fixtures->pricing->createStandardPricingPlanForFeeBearer('customer');

        $this->fixtures->merchant->edit($merchant['id'], ['fee_bearer' => 'customer']);

        $this->fixtures->merchant->edit($merchant['id'], ['pricing_plan_id' => '3R0Ssm31kRSKSS']);

        $this->ba->proxyAuth('rzp_test_'.$merchant['id'], $user->getId());

        $this->startTest();

        $merchantEntity = $this->getDbEntityById('merchant', $merchant['id']);

        $rules = (new PricingRepo())->getPlanByIdOrFailPublic($merchantEntity['pricing_plan_id']);

        $this->assertNotEquals($merchantEntity['pricing_plan_id'], '3R0Ssm31kRSKSS');

        $this->assertEquals($merchantEntity['fee_bearer'], 'platform');

        foreach ($rules as $rule)
        {
            $this->assertEquals($rule['fee_bearer'], 'platform');
        }

        $this->assertEquals(sizeof($rules), 13);
    }

    public function testToggleFeeBearerFailForCustomer()
    {
        $merchant = $this->fixtures->create('merchant', ['activated' => 1]);

        $user = $this->fixtures->create('user');

        $this->createMerchantUserMapping($user->getId(), $merchant->getId(), 'owner');

        $this->fixtures->pricing->createStandardPricingPlanForFeeBearer('customer');

        $this->fixtures->merchant->edit($merchant['id'], ['fee_bearer' => 'customer']);

        $this->fixtures->merchant->edit($merchant['id'], ['pricing_plan_id' => '3R0Ssm31kRSKSS']);

        $this->ba->proxyAuth('rzp_test_'.$merchant['id'], $user->getId());

        $this->startTest();
    }

    public function testToggleFeeBearerToDynamicFail()
    {
        $merchant = $this->fixtures->create('merchant', ['activated' => 1]);

        $user = $this->fixtures->create('user');

        $this->createMerchantUserMapping($user->getId(), $merchant->getId(), 'owner');

        $this->fixtures->pricing->createStandardPricingPlanForFeeBearer('customer');

        $this->fixtures->merchant->edit($merchant['id'], ['fee_bearer' => 'customer']);

        $this->fixtures->merchant->edit($merchant['id'], ['pricing_plan_id' => '3R0Ssm31kRSKSS']);

        $this->ba->proxyAuth('rzp_test_'.$merchant['id'], $user->getId());

        $this->startTest();
    }

    protected function createRiskTaggedMerchantForBulkForAction($action, array $permissions)
    {
        $this->createMerchant([
            'id'    => '10000000000044',
            'email' => 'test1@razorpay.com',
        ]);

        switch ($action)
        {
            case 'live_enable':
                $this->fixtures->merchant->edit('10000000000044', ['live' => false, 'activated' => 1]);
                break;
            case 'unsuspend':
                $this->fixtures->base->editEntity('merchant', '10000000000044', ['suspended_at' => '123456789']);
                break;
        }

        $tagInputData = [
            'tags' => ['MS_Risk_review_watchlist'],
        ];

        (new MerchantCore())->addTags('10000000000044',$tagInputData,false);

        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        foreach ($permissions as $permission)
        {
            $perm = $this->fixtures->create('permission', ['name' => $permission]);

            $role->permissions()->attach($perm->getId());
        }
    }

    /**
     * Failure test case with checked not having required permissions
     *
     * Testing the flow for "enabling live" via bulk update for the merchant which is tagged by risk team,
     * for such cases, user should have the 'merchant_risk_constructive_action'
     * if not the constructive action should fail for that merchant
     */
    public function testEditBulkEnableLiveRiskTaggedMerchantWithoutPermission()
    {
        $this->createRiskTaggedMerchantForBulkForAction('live_enable',['edit_merchant_toggle_live_bulk']);

        $this->ba->adminAuth();

        $this->startTest();
    }

    /**
     * Happy test case
     * Testing the flow for "enabling live" via bulk update for the merchant which is tagged by risk team,
     * for such cases, user should have the 'merchant_risk_constructive_action'
     * if not the constructive action should fail for that merchant
     */
    public function testEditBulkEnableLiveRiskTaggedMerchant()
    {
        $this->createRiskTaggedMerchantForBulkForAction('live_enable',
            [
                'merchant_risk_constructive_action',
                'edit_merchant_toggle_live_bulk',
            ]);

        $this->ba->adminAuth();

        $this->startTest();
    }

    /**
     * Failure test case with checked not having required permissions
     *
     * Testing the flow for "unsuspend" via bulk update for the merchant which is tagged by risk team,
     * for such cases, user should have the 'merchant_risk_constructive_action'
     * if not the constructive action should fail for that merchant
     */
    public function testEditBulkUnsuspendRiskTaggedMerchantWithoutPermission()
    {
        $this->createRiskTaggedMerchantForBulkForAction('unsuspend',
            [
                'edit_merchant_suspend_bulk'
            ]);

        $this->ba->adminAuth();

        $this->startTest();
    }

    /**
     * Happy test case
     *
     * Testing the flow for "unsuspend" via bulk update for the merchant which is tagged by risk team,
     * for such cases, user should have the 'merchant_risk_constructive_action'
     * if not the constructive action should fail for that merchant
     *
     * PS: Workflow is not created is this test case
     */
    public function testEditBulkUnsuspendRiskTaggedMerchant()
    {
        $this->createRiskTaggedMerchantForBulkForAction('unsuspend',
            [
                'edit_merchant_suspend_bulk',
                'merchant_risk_constructive_action'
            ]);

        $this->ba->adminAuth();

        $this->startTest();
    }


    protected function createRiskTaggedMerchantForUnsuspendAction($action, array $permissions)
    {
        $merchant = $this->getLastEntity('merchant', true);

        $this->fixtures->base->editEntity('merchant', $merchant['id'], [ 'suspended_at' => '123456789' ]);

        $tagInputData = [
            'tags' => ['MS_Risk_review_watchlist'],
        ];

        (new MerchantCore())->addTags($merchant['id'],$tagInputData,false);

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, 'org_'.$this->org->id);

        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        foreach ($permissions as $permission)
        {
            $perm = $this->fixtures->create('permission', ['name' => $permission]);

            $role->permissions()->attach($perm->getId());
        }

        return $merchant;
    }
    /**
     * Happy test case
     *
     * Testing the flow for "unsuspend" for the merchant which is tagged by risk team,
     * for such cases, user should have the 'merchant_risk_constructive_action'
     ** if not then validation exception will be thrown
     *
     * PS: Workflow is not created is this test case
     */
    public function testRiskTaggedMerchantUnsuspendRiskTagged()
    {
        $merchant = $this->createRiskTaggedMerchantForUnsuspendAction('unsuspend', ['merchant_risk_constructive_action']);

        $url = sprintf($this->testData[__FUNCTION__]['request']['url'], $merchant['id']);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $response = $this->startTest();

        $merchant = $this->getEntityById('merchant', $merchant['id'], true);

        $this->assertNull($merchant['suspended_at']);
    }

    /**
     * Failure test case - failing as actioneer doesn't have permission
     *
     * Testing the flow for "unsuspend" for the merchant which is tagged by risk team,
     * for such cases, user should have the 'merchant_risk_constructive_action'
     ** if not then validation exception will be thrown
     *
     * PS: Workflow is not created is this test case
     */
    public function testRiskTaggedMerchantUnsuspendRiskTaggedWithoutPermission()
    {
        $merchant = $this->createRiskTaggedMerchantForUnsuspendAction('unsuspend', []);

        $url = sprintf($this->testData[__FUNCTION__]['request']['url'], $merchant['id']);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $response = $this->startTest();

        $merchant = $this->getEntityById('merchant', $merchant['id'], true);

        $this->assertNull($merchant['suspended_at']);
    }

    protected function createMerchantForRiskTaggedMerchantForReleaseFundsWithWorkflow(array $permissions)
    {
        $this->ba->adminAuth();

        $this->fixtures->merchant->edit('10000000000000', ['hold_funds' => 1]);

        $tagInputData = [
            'tags' => ['MS_Risk_review_watchlist'],
        ];

        (new MerchantCore())->addTags('10000000000000',$tagInputData,false);

        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        foreach($permissions as $permission)
        {
            $perm = $this->fixtures->create('permission', ['name' => $permission]);
            $role->permissions()->attach($perm->getId());
        }

        $this->ba->adminAuth();

        $this->setupWorkflow('Release Funds',PermissionName::$actionMap["release_funds"], "test");
    }

    /**
     * Happy test case
     *
     * Testing the flow for "release_funds" for the merchant which is tagged by risk team,
     * for such cases, workflow will be created.
     * Checker should have the 'merchant_risk_constructive_action'
     * if not then validation exception will be thrown
     *
     */
    public function testRiskTaggedMerchantReleaseFundsWithWorkflow()
    {
        $this->createMerchantForRiskTaggedMerchantForReleaseFundsWithWorkflow(
            [
                PermissionName::EDIT_MERCHANT_RELEASE_FUNDS,
                PermissionName::MERCHANT_RISK_CONSTRUCTIVE_ACTION
            ]);

        $url = sprintf($this->testData[__FUNCTION__]['request']['url'], '10000000000000');

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $response = $this->startTest();

        $this->assertStringStartsWith('w_action_', $response['id']);

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $this->esClient->indices()->refresh();

        $this->performWorkflowAction($workflowAction['id'], true);

        $merchant = $this->getDbEntityById('merchant', '10000000000000');

        $this->assertEquals(false, $merchant->isFundsOnHold());
    }

    /**
     * Failure test case
     *
     * Testing the flow for "release_funds" for the merchant which is tagged by risk team,
     * for such cases, workflow will be created.
     * Checker should have the 'merchant_risk_constructive_action'
     * if not then validation exception will be thrown
     *
     */
    public function testRiskTaggedMerchantReleaseFundsWithWorkflowWithoutPermission()
    {
        $this->createMerchantForRiskTaggedMerchantForReleaseFundsWithWorkflow([PermissionName::EDIT_MERCHANT_RELEASE_FUNDS]);

        $url = sprintf($this->testData[__FUNCTION__]['request']['url'], '10000000000000');

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $response = $this->startTest();

        $this->assertStringStartsWith('w_action_', $response['id']);

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $this->esClient->indices()->refresh();

        $caughtException = false;

        try
        {
            $this->performWorkflowAction($workflowAction['id'], true);
        }
        catch (BadRequestValidationFailureException $e)
        {
            $this->assertEquals(
                'Merchant is tagged by risk team hence constructive action can be performed on this only by risk team',
            $e->getMessage());
            $caughtException = true;
        }

        $this->assertEquals(true, $caughtException);

        $merchant = $this->getDbEntityById('merchant', '10000000000000');

        $this->assertEquals(true, $merchant->isFundsOnHold());
    }

    public function testBulkAssignTag()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testEditBulkEnableLiveNewFlow()
    {
        $this->createMerchant([
            'id'    => '10000000000044',
            'email' => 'test1@razorpay.com',
        ]);

        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => 'edit_merchant_toggle_live_bulk']);

        $this->fixtures->merchant->edit('10000000000044', ['live' => false, 'activated' => 1]);

        $role->permissions()->attach($perm->getId());

        $this->setupWorkflow('toggle live',PermissionName::EXECUTE_MERCHANT_TOGGLE_LIVE_BULK, "test");

        $this->enableRazorXTreatmentForFeature(
            BulkActionConstants::BULK_RISK_ACTION_WORKFLOW_TRIGGER_FEATURE,'on');

        $this->ba->adminAuth();

        $this->startTest();
    }


    public function testEditBulkEnableLiveNewFlowWithoutPermission()
    {
        $this->createMerchant([
            'id'    => '10000000000044',
            'email' => 'test1@razorpay.com',
        ]);

        $this->removePermission(PermissionName::EDIT_MERCHANT_TOGGLE_LIVE_BULK);

        $this->ba->adminAuth();

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', '10000000000044');

        $this->assertFalse($merchant->isLive());
    }

    public function testEditBulkDisableLiveNewFlowWithCorrectAttributes()
    {
        $this->createMerchant([
            'id'    => '10000000000044',
            'email' => 'test1@razorpay.com',
        ]);

        $this->createMerchant([
            'id'    => '10000000000004',
            'email' => 'test2@razorpay.com',
        ]);

        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => 'edit_merchant_toggle_live_bulk']);

        $this->fixtures->merchant->edit('10000000000044', ['live' => true, 'activated' => 1]);

        $this->fixtures->merchant->edit('10000000000004', ['live' => true, 'activated' => 1]);

        $role->permissions()->attach($perm->getId());

        $this->setupWorkflow('toggle live',PermissionName::EXECUTE_MERCHANT_TOGGLE_LIVE_BULK, "test");

        $this->enableRazorXTreatmentForFeature(
            BulkActionConstants::BULK_RISK_ACTION_WORKFLOW_TRIGGER_FEATURE,'on');

        $this->ba->adminAuth();

        $resp = $this->startTest();

        $this->assertNotNull($resp['id']);

        $this->assertEquals('bulk_workflow_action', $resp['entity_name']);
    }

    public function testEditBulkReleaseFundsNewFlow()
    {
        $this->createMerchant([
            'id'    => '10000000000044',
            'email' => 'test1@razorpay.com',
        ]);

        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => 'edit_merchant_hold_funds_bulk']);

        $this->fixtures->merchant->edit('10000000000044', [
            'live'          => false,
            'activated'     => 1,
            'hold_funds'    => true,
        ]);

        $this->fixtures->create('bank_account', [
            'type'           => 'merchant',
            'merchant_id'    => '10000000000044',
            'entity_id'      => '10000000000044',
            'account_number' => '10010101011',
            'ifsc_code'      => 'RAZRB000000',
        ]);

        $role->permissions()->attach($perm->getId());

        $this->setupWorkflow('Execute Release Funds Bulk',PermissionName::EXECUTE_MERCHANT_HOLD_FUNDS_BULK, "test");

        $this->enableRazorXTreatmentForFeature(
            BulkActionConstants::BULK_RISK_ACTION_WORKFLOW_TRIGGER_FEATURE,'on');
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testEditBulkHoldFundsNewFlow()
    {
        $this->createMerchant([
            'id'    => '10000000000044',
            'email' => 'test1@razorpay.com',
        ]);

        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => 'edit_merchant_hold_funds_bulk']);

        $this->fixtures->merchant->edit('10000000000044', ['live' => false, 'activated' => 1]);

        $role->permissions()->attach($perm->getId());

        $this->setupWorkflow('Execute Hold Funds Bulk',PermissionName::EXECUTE_MERCHANT_HOLD_FUNDS_BULK, "test");

        $this->enableRazorXTreatmentForFeature(
            BulkActionConstants::BULK_RISK_ACTION_WORKFLOW_TRIGGER_FEATURE,'on');

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testEditBulkSuspendMerchantNewFlow()
    {
        $this->createMerchant([
            'id'    => '10000000000044',
            'email' => 'test1@razorpay.com',
        ]);

        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => 'edit_merchant_suspend_bulk']);

        $this->fixtures->merchant->edit('10000000000044', ['live' => true, 'activated' => 1]);

        $role->permissions()->attach($perm->getId());

        $this->setupWorkflow('Execute merchant suspend bulk', PermissionName::EXECUTE_MERCHANT_SUSPEND_BULK, "test");

        $this->enableRazorXTreatmentForFeature(
            BulkActionConstants::BULK_RISK_ACTION_WORKFLOW_TRIGGER_FEATURE,'on');

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testEditBulkDisableLiveNewFlowWithoutRiskAttributes()
    {
        $this->createMerchant([
            'id'    => '10000000000044',
            'email' => 'test1@razorpay.com',
        ]);

        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => 'edit_merchant_toggle_live_bulk']);

        $role->permissions()->attach($perm->getId());

        $this->enableRazorXTreatmentForFeature(
            BulkActionConstants::BULK_RISK_ACTION_WORKFLOW_TRIGGER_FEATURE,'on');

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testEditBulkDisableLiveNewFlowIncorrectRiskAttributes()
    {
        $this->createMerchant([
            'id'    => '10000000000044',
            'email' => 'test1@razorpay.com',
        ]);

        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => 'edit_merchant_toggle_live_bulk']);

        $role->permissions()->attach($perm->getId());

        $this->enableRazorXTreatmentForFeature(
            BulkActionConstants::BULK_RISK_ACTION_WORKFLOW_TRIGGER_FEATURE,'on');

        $this->ba->adminAuth();

        $this->startTest();
    }

    protected function setupWorkflows($permissionWorkflowNameMap)
    {
        $this->fixtures->on('live')->create('org:admin_for_razorpay_org');

        $permissionNames = array_keys($permissionWorkflowNameMap);

        foreach ($permissionNames as $permissionName)
        {
            $permission = $this->getDbEntity('permission', ['name' => $permissionName], 'live');

            DB::connection('live')->table('permission_map')->insert(
                [
                    'entity_id'     => Org::RZP_ORG,
                    'entity_type'   => 'org',
                    'permission_id' => $permission->getId(),
                ]);
        }


        $org = (new OrgRepository)->getRazorpayOrg();

        $this->fixtures->on('live')->create('org:workflow_users', ['org' => $org]);

        foreach ($permissionWorkflowNameMap as $permissionName => $workflowName)
        {
            $workflow = $this->createWorkflow([
                                                  'org_id'      => '100000razorpay',
                                                  'name'        => $workflowName,
                                                  'permissions' => [$permissionName],
                                                  'levels' => [
                                                      [
                                                          'level' => 1,
                                                          'op_type' => 'or',
                                                          'steps' => [
                                                              [
                                                                  'reviewer_count' => 1,
                                                                  'role_id' => Org::ADMIN_ROLE,
                                                              ],
                                                          ],
                                                      ],
                                                  ],
                                              ], 'test');
        }
    }

    protected function mockRazorxTreatment(string $returnValue = 'On')
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn($returnValue);
    }

    protected function setupMerchantWithMerchantDetails(array $predefinedMerchant = [], array $predefinedMerchantDetails = [], string $role = Role::OWNER)
    {
        $merchant = $this->fixtures->create('merchant', $predefinedMerchant);

        $merchantId = $merchant['id'];

        $user = $this->fixtures->create('user', [
            'contact_mobile'          => '1234567890',
            'contact_mobile_verified' => true,
        ]);

        $this->fixtures->user->createUserMerchantMapping([
            'user_id'     => $user->id,
            'merchant_id' => $merchantId,
            'role'        => $role,
        ]);

        $predefinedMerchantDetails = array_merge(['merchant_id'  => $merchantId], $predefinedMerchantDetails );

        $this->fixtures->create('merchant_detail', $predefinedMerchantDetails);

        return [$merchantId, $user->id];
    }

    public function testUnregisteredIncreaseTransactionLimitWorkflowApprove()
    {
        Mail::fake();

        $predefinedMerchant = [
            'name'               => 'testname',
            'activated'          => 1,
            'max_payment_amount' => 10000
        ];

        $predefinedMerchantDetails = [
            'business_type'      => 2,
            'business_category'  => Merchant\Detail\BusinessCategory::MEDIA_AND_ENTERTAINMENT
        ];

        [$merchantId, $userId] = $this->setupMerchantWithMerchantDetails($predefinedMerchant, $predefinedMerchantDetails);

        $this->mockRavenAndStorkForTransactionLimitSelfServe($merchantId);

        $this->setupWorkflow('increase_transaction_limit', PermissionName::INCREASE_TRANSACTION_LIMIT, 'test');

        $this->ba->proxyAuth('rzp_test_'.$merchantId, $userId);

        $this->startTest();

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $this->assertNotEmpty($workflowAction);

        $workflowActionId = $workflowAction['id'];

        $this->esClient->indices()->refresh();

        $observerData = ['approved_transaction_limit' => '800000'];

        $this->updateObserverData($workflowActionId, $observerData);

        $insertedObserverData = $this->getWorkflowData();

        $this->assertNotEmpty($insertedObserverData);

        $insertedObserverData = $insertedObserverData['workflow_observer_data'];

        $this->assertArraySelectiveEquals($insertedObserverData, $observerData);

        $this->performWorkflowAction($workflowActionId, true);

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $this->assertEquals(800000 , $merchant->getMaxPaymentAmount());

        Mail::assertQueued(MerchantMail\MerchantDashboardEmail::class, function ($mail)
        {
            $data = $mail->viewData;

            $this->assertEquals('8000', $data['updated_transaction_limit']);

            $this->assertEquals('testname', $data['merchant_name']);

            $this->assertEquals('emails.merchant.increase_transaction_limit_request_approve', $mail->view);

            return true;
        });
    }

    public function testRegisteredIncreaseTransactionLimitWorkflowApprove()
    {
        Mail::fake();

        $predefinedMerchant = [
            'name'               => 'testname',
            'activated'          => 1,
            'max_payment_amount' => 10000
        ];

        $predefinedMerchantDetails = [
            'business_type'      => 4,
            'business_category'  => Merchant\Detail\BusinessCategory::OTHERS
        ];

        [$merchantId, $userId] = $this->setupMerchantWithMerchantDetails($predefinedMerchant, $predefinedMerchantDetails);

        $this->mockRavenAndStorkForTransactionLimitSelfServe($merchantId);

        $druidService = $this->getMockBuilder(MockDruidService::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods([ 'getDataFromDruid'])
            ->getMock();

        $this->app->instance('druid.service', $druidService);

        $dataFromDruid = $this->testData['testGetRiskData']['druid_response'];

        $dataFromDruid['Domestic_cts_overall_merchant_id'] = $merchantId;

        $dataFromDruid['Domestic_FTS_merchant_id'] = $merchantId;

        $druidService->method('getDataFromDruid')
            ->willReturn([null, [$dataFromDruid]]);

        $testData = $this->testData['testUnregisteredIncreaseTransactionLimitWorkflowApprove'];

        $this->testData[__FUNCTION__] = $testData;

        $this->setupWorkflow('increase_transaction_limit', PermissionName::INCREASE_TRANSACTION_LIMIT, 'test');

        $this->ba->proxyAuth('rzp_test_'.$merchantId, $userId);

        $this->startTest();

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $this->assertNotEmpty($workflowAction);

        $workflowActionId = $workflowAction['id'];

        $this->esClient->indices()->refresh();

        $observerData = ['approved_transaction_limit' => '800000'];

        $this->updateObserverData($workflowActionId, $observerData);

        $insertedObserverData = $this->getWorkflowData();

        $this->assertNotEmpty($insertedObserverData);

        $insertedObserverData = $insertedObserverData['workflow_observer_data'];

        $this->assertArraySelectiveEquals($insertedObserverData, $observerData);

        $this->performWorkflowAction($workflowActionId, true);

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $this->assertEquals(800000 , $merchant->getMaxPaymentAmount());

        Mail::assertQueued(MerchantMail\MerchantDashboardEmail::class, function ($mail)
        {
            $data = $mail->viewData;

            $this->assertEquals('8000', $data['updated_transaction_limit']);

            $this->assertEquals('testname', $data['merchant_name']);

            $this->assertEquals('emails.merchant.increase_transaction_limit_request_approve', $mail->view);

            return true;
        });
    }

    public function testIncreaseTransactionLimitRoleFailure()
    {
        $predefinedMerchant = [
            'activated'          => 1,
            'max_payment_amount' => 10000
        ];

        $predefinedMerchantDetails = [
            'business_type'      => 4,
            'business_category'  => Merchant\Detail\BusinessCategory::OTHERS
        ];

        [$merchantId, $userId] = $this->setupMerchantWithMerchantDetails($predefinedMerchant, $predefinedMerchantDetails, 'manager');

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $userId);

        $this->startTest();
    }

    public function testIncreaseTransactionMerchantActivationFailure()
    {
        $predefinedMerchant = [
            'activated'          => 0,
            'max_payment_amount' => 10000
        ];

        $predefinedMerchantDetails = [
            'business_type'      => 2,
            'business_category'  => Merchant\Detail\BusinessCategory::OTHERS
        ];

        [$merchantId, $userId] = $this->setupMerchantWithMerchantDetails($predefinedMerchant, $predefinedMerchantDetails);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $userId);

        $this->startTest();
    }

    public function testIncreaseTransactionLimitSameAsPreviousFailure()
    {
        $predefinedMerchant = [
            'activated'          => 1,
            'max_payment_amount' => 1000000
        ];

        $predefinedMerchantDetails = [
            'business_type'      => 2,
            'business_category'  => Merchant\Detail\BusinessCategory::OTHERS
        ];

        [$merchantId, $userId] = $this->setupMerchantWithMerchantDetails($predefinedMerchant, $predefinedMerchantDetails);

        $testData = $this->testData['testIncreaseTransactionMerchantActivationFailure'];

        $testData['response']['content']['error']['description'] = 'The new transaction limit is same as the current transaction limit';

        $testData['exception']['class'] = 'RZP\Exception\BadRequestValidationFailureException';

        $testData['exception']['internal_error_code'] = ErrorCode::BAD_REQUEST_VALIDATION_FAILURE;

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $userId);

        $this->startTest();
    }

    public function testIncreaseTransactionLimitMaximumLimitFailure()
    {
        $predefinedMerchant = [
            'activated'          => 1,
            'max_payment_amount' => 10000000
        ];

        $predefinedMerchantDetails = [
            'business_type'      => 2,
            'business_category'  => Merchant\Detail\BusinessCategory::MEDIA_AND_ENTERTAINMENT
        ];

        [$merchantId, $userId] = $this->setupMerchantWithMerchantDetails($predefinedMerchant, $predefinedMerchantDetails);

        $testData = $this->testData['testIncreaseTransactionMerchantActivationFailure'];

        $testData['request']['content']['new_transaction_limit_by_merchant'] = 15000000;

        $testData['response']['content']['error']['description'] = 'Your transaction limit cannot be increased any further, as per the guidelines set by our partner banks';

        $testData['exception']['class'] = 'RZP\Exception\BadRequestValidationFailureException';

        $testData['exception']['internal_error_code'] = ErrorCode::BAD_REQUEST_VALIDATION_FAILURE;

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $userId);

        $this->startTest();
    }

    public function testIncreaseTransactionLimitUnregisteredMerchantBlacklistFailure()
    {
        $predefinedMerchant = [
            'activated'          => 1,
            'max_payment_amount' => 10000
        ];

        $predefinedMerchantDetails = [
            'business_type'      => 2,
            'business_category'  => Merchant\Detail\BusinessCategory::GAMING
        ];

        [$merchantId, $userId] = $this->setupMerchantWithMerchantDetails($predefinedMerchant, $predefinedMerchantDetails);

        $testData = $this->testData['testIncreaseTransactionMerchantActivationFailure'];

        $testData['response']['content']['error']['description'] = PublicErrorDescription::BAD_REQUEST_ACCESS_DENIED;

        $testData['exception']['internal_error_code'] = ErrorCode::BAD_REQUEST_ACCESS_DENIED;

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $userId);

        $this->startTest();
    }

    public function testIncreaseTransactionLimitRegisteredCtsFailure()
    {
        $predefinedMerchant = [
            'activated'          => 1,
            'max_payment_amount' => 10000
        ];

        $predefinedMerchantDetails = [
            'business_type'      => 4,
            'business_category'  => Merchant\Detail\BusinessCategory::OTHERS
        ];

        [$merchantId, $userId] = $this->setupMerchantWithMerchantDetails($predefinedMerchant, $predefinedMerchantDetails);

        $druidService = $this->getMockBuilder(MockDruidService::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['getDataFromDruid'])
            ->getMock();

        $this->app->instance('druid.service', $druidService);

        $dataFromDruid = $this->testData['testGetRiskData']['druid_response'];

        $dataFromDruid['Domestic_cts_overall_merchant_id'] = $merchantId;

        $dataFromDruid['Domestic_FTS_merchant_id'] = $merchantId;

        $dataFromDruid['Domestic_cts_overall_lifetime_cts'] = 5.04;

        $druidService->method('getDataFromDruid')
            ->willReturn([null, [$dataFromDruid]]);

        $testData = $this->testData['testIncreaseTransactionMerchantActivationFailure'];

        $testData['response']['content']['error']['description'] = PublicErrorDescription::BAD_REQUEST_EDIT_TRANSACTION_LIMIT_CTS_OR_FTS_MORE_THAN_5;

        $testData['exception']['internal_error_code'] = ErrorCode::BAD_REQUEST_EDIT_TRANSACTION_LIMIT_CTS_OR_FTS_MORE_THAN_5;

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $userId);

        $this->startTest();
    }

    protected function mockRavenAndStorkForTransactionLimitSelfServe($merchantId)
    {
        $this->enableRazorXTreatmentForFeature('whatsapp_notifications');

        $ravenMock = Mockery::mock('RZP\Services\Raven', [$this->app])->makePartial();

        $this->app->instance('raven', $ravenMock);

        $expectedRavenParametersForTemplate = [
            'updated_transaction_limit' => '8000'
        ];

        $this->expectRavenSendSmsRequest($ravenMock,'sms.dashboard.increase_transaction_limit_request_approve', '1234567890', $expectedRavenParametersForTemplate);

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $this->expectStorkWhatsappRequest($storkMock,
            'With regards to the request we received an update from the partner banks to increase the transaction limit to ₹8000
The same has been enabled for the account.
-Team Razorpay',
            '1234567890',
            $merchantId
        );
    }

    public function testKAMMerchantIncreaseTransactionLimitWorkflowApprove()
    {
        Mail::fake();

        $predefinedMerchant = [
            'name'               => 'testname',
            'activated'          => 1,
            'max_payment_amount' => 10000
        ];

        $predefinedMerchantDetails = [
            'business_type'      => 4,
            'business_category'  => Merchant\Detail\BusinessCategory::OTHERS
        ];

        [$merchantId, $userId] = $this->setupMerchantWithMerchantDetails($predefinedMerchant, $predefinedMerchantDetails);

        $this->mockRavenAndStorkForTransactionLimitSelfServe($merchantId);

        $prestoService = $this->getMockBuilder(Mock\DataLakePresto::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods([ 'getDataFromDataLake'])
            ->getMock();

        $this->app->instance('datalake.presto', $prestoService);

        $prestoServiceData = [
            [
                'owner_role__c' => MerchantConstants::MERCHANT_TYPE_KAM,
            ]
        ];

        $prestoService->method( 'getDataFromDataLake')
            ->willReturn($prestoServiceData);

        $this->setupWorkflow('increase_transaction_limit', PermissionName::INCREASE_TRANSACTION_LIMIT, 'test');

        $testData = $this->testData['testUnregisteredIncreaseTransactionLimitWorkflowApprove'];

        $testData['request']['content']['new_transaction_limit_by_merchant'] = 999999999999;

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->proxyAuth('rzp_test_'.$merchantId, $userId);

        $this->startTest();

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $this->assertNotEmpty($workflowAction);

        $workflowActionId = $workflowAction['id'];

        $this->esClient->indices()->refresh();

        $observerData = ['approved_transaction_limit' => '800000'];

        $this->updateObserverData($workflowActionId, $observerData);

        $insertedObserverData = $this->getWorkflowData();

        $this->assertNotEmpty($insertedObserverData);

        $insertedObserverData = $insertedObserverData['workflow_observer_data'];

        $this->assertArraySelectiveEquals($insertedObserverData, $observerData);

        $this->performWorkflowAction($workflowActionId, true);

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $this->assertEquals(800000 , $merchant->getMaxPaymentAmount());

        Mail::assertQueued(MerchantMail\MerchantDashboardEmail::class, function ($mail)
        {
            $data = $mail->viewData;

            $this->assertEquals('8000', $data['updated_transaction_limit']);

            $this->assertEquals('testname', $data['merchant_name']);

            $this->assertEquals('emails.merchant.increase_transaction_limit_request_approve', $mail->view);

            return true;
        });
    }

    protected function testRejectionReasonNotificationForMerchantWorkflowType(string $merchantId, string $workflowType)
    {
        Mail::fake();

        $user = $this->getDbLastEntity('user');

        $this->esClient->indices()->refresh();

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $this->assertNotEmpty($workflowAction);

        $workflowActionId = $workflowAction['id'];

        $rejectionReason = ['subject' => 'Test subject', 'body' => 'Test body'];

        $observerData = [ 'rejection_reason' => $rejectionReason, 'ticket_id' => '123', 'fd_instance' => 'rzp' ];

        $this->updateObserverData($workflowActionId, $observerData);

        $insertedObserverData = $this->getWorkflowData();

        $this->assertNotEmpty($insertedObserverData);

        $insertedObserverData = $insertedObserverData['workflow_observer_data'];

        $expectedObserverData = ['rejection_reason' => json_encode($rejectionReason), 'ticket_id' => '123', 'fd_instance' => 'rzp' ];

        $this->assertArraySelectiveEquals($insertedObserverData, $expectedObserverData);

        $this->performWorkflowAction($workflowActionId, false);

        Mail::assertQueued(MerchantMail\MerchantDashboardEmail::class, function ($mail) use($user)
        {
            $data = $mail->viewData;

            $this->assertEquals('Test body', $data['messageBody']);

            $this->assertEquals('emails.merchant.rejection_reason_notification', $mail->view);

            $mail->hasTo($user['email']);

            return true;
        });

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchant/" . $workflowType . "/details";

        $this->fixtures->user->createUserMerchantMapping([
            'user_id'     => $user['id'],
            'merchant_id' => $merchantId,
            'role'        => Role::OWNER,
        ]);
        $this->ba->proxyAuth('rzp_test_'.$merchantId, $user['id']);

        $this->startTest();
    }

    public function testBulkWfActionExecution()
    {
        $this->createMerchant([
                                  'id'    => '10000000000044',
                                  'email' => 'test1@razorpay.com',
                              ]);

        $this->fixtures->merchant->edit('10000000000044', [
            'live'          => true,
            'activated'     => 1,
            'hold_funds'    => true,
        ]);

        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => 'edit_merchant_toggle_live_bulk']);

        $role->permissions()->attach($perm->getId());

        $this->setupWorkflows([
                                  PermissionName::EXECUTE_MERCHANT_TOGGLE_LIVE_BULK      => 'Execute toggle live bulk',
                                  PermissionName::EDIT_MERCHANT_DISABLE_LIVE             => 'disable live',
                              ]);

        $this->enableRazorXTreatmentForFeature(
            BulkActionConstants::BULK_RISK_ACTION_WORKFLOW_TRIGGER_FEATURE,'on');

        $request = [
            'method'  => 'PUT',
            'url'     => '/merchants/bulk',
            'content' => [
                'merchant_ids' => ['10000000000044'],
                'action'       => 'live_disable',
                'risk_attributes' => [
                    'risk_reason' => 'high_cts',
                    'risk_source' => 'high_fts',
                    'risk_tag'     => 'risk_review_watchlist',
                    'trigger_communication' => '1'
                ]
            ],
            'server'  => [
                'HTTP_X-Dashboard' => 'true',
            ],
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->testData[__FUNCTION__]['request']['content']['entity_id'] = $response['entity_id'];

        $this->startTest();

        $request = [
            'method'  => 'POST',
            'url'     => '/risk-actions/execute',
            'content' => [
                'merchant_id'               => '10000000000044',
                'bulk_workflow_action_id'   => $response['id'],
            ],
        ];

        $this->ba->batchAppAuth();

        Action\Entity::verifyIdAndSilentlyStripSign($request['content']['bulk_workflow_action_id']);

        $this->esClient->indices()->refresh();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('EXECUTED', $response['workflow_action_status']);
    }

    public function testRejectionReasonMerchantNotificationForTransactionLimitSelfServe()
    {
        $predefinedMerchant = [
            'activated'          => 1,
            'max_payment_amount' => 10000
        ];

        $predefinedMerchantDetails = [
            'business_type'      => 2,
            'business_category'  => Merchant\Detail\BusinessCategory::MEDIA_AND_ENTERTAINMENT
        ];

        [$merchantId, $userId] = $this->setupMerchantWithMerchantDetails($predefinedMerchant, $predefinedMerchantDetails);

        $this->setupWorkflow('increase_transaction_limit', PermissionName::INCREASE_TRANSACTION_LIMIT, 'test');

        $this->ba->proxyAuth('rzp_test_'.$merchantId, $userId);

        $testData = $this->testData['testUnregisteredIncreaseTransactionLimitWorkflowApprove'];

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();

        $this->testRejectionReasonNotificationForMerchantWorkflowType($merchantId,MerchantConstants::INCREASE_TRANSACTION_LIMIT);
    }

    public function testGetCheckoutRouteWithTokenForCardCountry()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->activate('10000000000000');

        $request = [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'contact' => '9988776655',
                'customer_id' => 'cust_100000customer',
                'currency' => 'INR',
            ]
        ];

        $response = $this->sendRequest($request);

        $responseContent = json_decode($response->getContent(), true);

        $this->assertNotNull($responseContent['customer']['tokens']);

        $tokens = $responseContent['customer']['tokens'];

        $this->assertTrue($tokens['count'] > 0);

        $this->assertTrue(array_key_exists('country', $tokens['items'][0]['card']) === true);
    }

}
