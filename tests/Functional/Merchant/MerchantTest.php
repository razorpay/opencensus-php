<?php

namespace RZP\Tests\Functional\Merchant;

use DB;
use Mail;
use Event;
use Redis;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;

use RZP\Models\Key;
use RZP\Jobs\EsSync;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Models\Transaction;
use RZP\Services\RazorXClient;
use RZP\Mail\User\MappedToAccount;
use RZP\Models\Settlement\Channel;
use RZP\Tests\Functional\TestCase;
use Illuminate\Support\Facades\Queue;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Fixtures\Entity\User;
use RZP\Tests\Functional\Helpers\MocksDnsTrait;
use RZP\Models\BankAccount\Entity as BankAccount;
use RZP\Models\Merchant\Balance\Entity as Balance;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Settlement\SettlementTrait;
use RZP\Mail\User\PasswordReset as PasswordResetMail;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\Helpers\Schedule\ScheduleTrait;
use RZP\Tests\Unit\Models\Invoice\Traits\CreatesInvoice;
use RZP\Mail\Banking\BeneficiaryFile as BeneficiaryFileMail;
use RZP\Mail\Merchant\AccountChange as BankAccountChangeMail;

/**
 * @group dns-sensitive
 */
class MerchantTest extends TestCase
{
    use PaymentTrait;
    use ScheduleTrait;
    use SettlementTrait;
    use InteractsWithSession;
    use HeimdallTrait;
    use MocksDnsTrait;
    use DbEntityFetchTrait;
    use OAuthTrait;
    use CreatesInvoice;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantTestData.php';

        parent::setUp();

        $this->ba->appAuth();

        $factoryPath = base_path() . '/vendor/razorpay/oauth/database/factories';

        $this->setupMockDns();

        $this->app->make(Factory::class)->load($factoryPath);
    }

    public function testCreateKey()
    {
        $this->createMerchant();

        $user = $this->fixtures->user->createUserForMerchant('1X4hRFHFx4UiXt');

        $this->ba->proxyAuth('rzp_test_1X4hRFHFx4UiXt', $user->getId());

        $this->startTest();
    }

    public function testCreateKeyForNonActivatedMerchant()
    {
        $this->createMerchant();

        $this->fixtures->merchant->setHasKeyAccess(true, '1X4hRFHFx4UiXt');

        $user = $this->fixtures->create('user');

        $this->createUserMerchantMapping($user['id'], '1X4hRFHFx4UiXt', 'owner', 'live');

        $this->ba->proxyAuth('rzp_live_1X4hRFHFx4UiXt', $user['id']);

        $this->startTest();
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
    }

    public function testGetMerchantUsers()
    {
        $merchant = $this->fixtures->create('merchant');

        $user1 = $this->fixtures->create('user');
        $user2 = $this->fixtures->create('user');

        $this->createUserMerchantMapping($user1['id'], $merchant['id'], 'owner');

        $this->createUserMerchantMapping($user2['id'], $merchant['id'], 'manager');

        $this->ba->proxyAuth('rzp_test_' . $merchant['id']);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/merchants-users';

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

        $this->ba->adminAuth('test', $this->authToken, 'org_'.$this->org->id);

        $result = $this->startTest();

        $this->assertArrayNotHasKey('groups', $result);
    }

    public function testEditMerchantWithHighRiskThreshold()
    {
        $this->createMerchant();

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, 'org_'.$this->org->id);

        $result = $this->startTest();

        $this->assertArrayNotHasKey('groups', $result);
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

        $this->ba->adminAuth('live');

        $this->startTest();

        $merchant1 = $this->getDbEntityById('merchant', '10000000000044');
        $merchant2 = $this->getDbEntityById('merchant', '10000000000055');

        foreach ([$merchant1, $merchant2] as $merchant)
        {
            $this->assertEquals(1, $merchant['hold_funds']);
        }
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

        $this->assertEquals('shake@razorpay.com', $merchant->primaryOwner()->getEmail());
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

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest();

        Mail::assertQueued(BankAccountChangeMail::class, function ($mail)
        {
            $testData = $this->testData['testAddBankAccount']['response']['content'];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            return true;
        });
    }

    public function testAddBankAccountWithAccountType()
    {
        Mail::fake();

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest();

        Mail::assertQueued(BankAccountChangeMail::class, function ($mail)
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

    public function testAddBankAccountWithMerchantIdInURL()
    {
        Mail::fake();

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest();

        Mail::assertQueued(BankAccountChangeMail::class, function ($mail)
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

        Mail::assertQueued(BankAccountChangeMail::class, function ($mail)
        {
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

    public function testDiwaliPromotionalPlan()
    {
        $this->markTestSkipped();

        $this->fixtures->pricing->createDiwaliPromotionalPlan();

        $payment = $this->getDefaultPaymentArray();

        $payment = $this->doAuthAndCapturePayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals($payment['id'], $transaction['entity_id']);
        $this->assertEquals(1000, $transaction['fee']);

        $this->fixtures->merchant->addFeatures(['diwali_promotional_plan']);

        $payment = $this->getDefaultPaymentArray();

        $payment = $this->doAuthAndCapturePayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals($payment['id'], $transaction['entity_id']);

        $this->assertEquals(100, $transaction['fee']);

        // mock carbon to test timestamp check

        $feb2019 = Carbon::createFromTimestamp(1549002600);

        Carbon::setTestNow($feb2019);

        $payment = $this->getDefaultPaymentArray();

        $payment = $this->doAuthAndCapturePayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals($payment['id'], $transaction['entity_id']);
        $this->assertEquals(1000, $transaction['fee']);
    }

    public function testDiwaliPromotionalPlanFeatureRemoval()
    {
        $this->markTestSkipped();

        $this->fixtures->merchant->addFeatures(['diwali_promotional_plan']);
        $this->fixtures->pricing->createStandardPlan();
        $this->fixtures->merchant->disableInternational();

        $merchant = $this->getDbEntityById('merchant', '10000000000000', true);

        $this->assertTrue($merchant->isFeatureEnabled('diwali_promotional_plan'));
        $this->ba->adminAuth();
        $this->merchantAssignPricingPlan('1A0Fkd38fGZPVC', '10000000000000');

        $merchant = $this->getDbEntityById('merchant', '10000000000000', true);

        $this->assertFalse($merchant->isFeatureEnabled('diwali_promotional_plan'));
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
            'content' => [],
        );

        $response = $this->sendRequest($request);

        $headers = $response->headers->all();
        $this->assertArrayNotHasKey('x-frame-options', $headers);
    }

    public function testGetCheckoutPreferencesWithNetbankingDisabled()
    {
        $this->ba->publicLiveAuth();

        $this->fixtures->merchant->activate('10000000000000');
        $this->fixtures->merchant->disableNetbanking('10000000000000');

        $content = $this->startTest();

        $count = count($content['methods']['netbanking']);
        $this->assertEquals(0, $count);
    }

    public function testGetCheckoutPreferencesForMerchantDisabledBanks()
    {
        $this->testSetBanks();

        $this->ba->publicAuth();

        $content = $this->startTest();

        $banks = $content['methods']['netbanking'];

        $this->assertCount(2, $banks);
    }

    public function testGetCheckoutPreferencesForTpvEnabledMerchant()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->enableTPV();

        $content = $this->startTest();

        $banks = $content['methods']['netbanking'];

        $this->assertCount(35, $banks);

        $this->fixtures->merchant->disableTPV();
    }

    public function testGetCheckoutPreferencesForMagicEnabledMerchant()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures(['magic']);

        $this->startTest();
    }

    public function testGetCheckoutPreferencesForMagicDisabledMerchant()
    {
        $this->markTestSkipped();

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithAllCardGeatewayDowntime()
    {
        $this->ba->publicAuth();

        $dt = Carbon::createFromTimestamp(1517077800, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->fixtures->create('gateway_downtime:card', [
            'gateway' => 'ALL',
            'issuer'  => 'ALL',
            'network' => 'VISA']);

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithDebitCardDisabled()
    {
        $this->ba->publicAuth();

        $this->fixtures->create('gateway_downtime:card', [
            'gateway' => 'ALL',
            'issuer'  => 'ALL',
            'network' => 'VISA']);

        $this->fixtures->merchant->disableDebitCard();

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithCreditCardDisabled()
    {
        $this->ba->publicAuth();

        $this->fixtures->create('gateway_downtime:card', [
            'gateway' => 'ALL',
            'issuer'  => 'ALL',
            'network' => 'VISA']);

        $this->fixtures->merchant->disableCreditCard();

        $this->startTest();
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

    public function testGetCheckoutPreferencesWithCardDowntimeWithIssuerOrNetworkUnknown()
    {
        $this->ba->publicAuth();

        $dt = Carbon::createFromTimestamp(1517077800, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->fixtures->create('gateway_downtime:card', [
            'gateway' => 'first_data',
            'issuer'  => 'UNKNOWN',
            'network' => 'UNKNOWN']);

        $content = $this->startTest();

        $this->assertArrayNotHasKey('downtime', $content);
    }

    public function testGetCheckoutPreferencesWithCardDowntimeWithSpecificGatewayDown()
    {
        $this->ba->publicAuth();

        $dt = Carbon::createFromTimestamp(1517077800, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->fixtures->create('gateway_downtime:card', [
            'gateway' => 'hdfc',
            'issuer'  => 'ALL',
            'network' => 'ALL']);

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithCardDowntimeWithGatewayExclusiveNetworkDown()
    {
        $this->ba->publicAuth();

        $dt = Carbon::createFromTimestamp(1517077800, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->fixtures->create('gateway_downtime:card', [
            'gateway' => 'hdfc',
            'issuer'  => 'ALL',
            'network' => 'DICL']);

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithNetbankingDowntimeWithAllGateway()
    {
        $this->ba->publicAuth();

        $dt = Carbon::createFromTimestamp(1517077800, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->fixtures->create('gateway_downtime:netbanking', [
            'gateway' => 'ALL',
            'issuer'  => 'HDFC',]);

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithNetbankingDowntimeWithSharedNetbankingGateway()
    {
        $this->ba->publicAuth();

        $dt = Carbon::createFromTimestamp(1517077800, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->fixtures->create('gateway_downtime:netbanking', [
            'gateway' => 'billdesk',
            'issuer'  => 'ALL',]);

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithNetbankingWithIssuerExclusiveTogateway()
    {
        $this->ba->publicAuth();

         $dt = Carbon::createFromTimestamp(1517077800, Timezone::IST);

         Carbon::setTestNow($dt);

         $this->fixtures->create('gateway_downtime:netbanking', [
            'gateway' => 'billdesk',
            'issuer'  => 'ALLA',]);

         $this->startTest();
    }

    public function testGetCheckoutPreferencesWithDirectNetbankingDowntime()
    {
        $this->ba->publicAuth();

        $dt = Carbon::createFromTimestamp(1517077800, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->fixtures->create('gateway_downtime:netbanking', [
            'gateway'     => 'netbanking_hdfc',
            'issuer'      => 'ALL',]);

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithWalletDowntime()
    {
        $this->ba->publicAuth();

        $dt = Carbon::createFromTimestamp(1517077800, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->fixtures->create('gateway_downtime:wallet', [
            'gateway' => 'wallet_olamoney',
            'issuer'  => 'olamoney']);

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithNonOrderRelatedOffer()
    {
        $this->ba->publicAuth();

        $startsAt = Carbon::yesterday(Timezone::IST)->timestamp;

        $offer = $this->fixtures->create('offer:wallet', [
            'checkout_display' => true,
            'display_text'     => 'Some display text',
            'terms'            => 'Some terms',
            'starts_at'        => $startsAt,
        ]);

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithSharedMerchantOffer()
    {
        $this->ba->publicAuth();

        $startsAt = Carbon::yesterday(Timezone::IST)->timestamp;

        $offer = $this->fixtures->create('offer:wallet', [
            'merchant_id'      => '100000Razorpay',
            'checkout_display' => true,
            'display_text'     => 'Merchant specific offer',
            'terms'            => 'Some terms',
            'starts_at'        => $startsAt,
        ]);

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithFreechargeOfferOnMerchantWithDirectFreechargeTerminal()
    {
        $this->ba->publicAuth();

        $this->fixtures->create('terminal:direct_freecharge_terminal');

        $startsAt = Carbon::yesterday(Timezone::IST)->timestamp;

        $offer = $this->fixtures->create('offer:wallet', [
            'merchant_id'      => '100000Razorpay',
            'checkout_display' => true,
            'display_text'     => 'Shared olamoney offer',
            'terms'            => 'Some terms',
            'starts_at'        => $startsAt,
        ]);

        //
        // Tests that the freecharge offer is not shown as the merchant has a
        // direct freecharge terminal.
        //
        $this->fixtures->create('offer:wallet', [
            'merchant_id'      => '100000Razorpay',
            'issuer'           => 'freecharge',
            'checkout_display' => true,
            'display_text'     => 'Shared freecharge offer',
            'terms'            => 'Some terms',
            'starts_at'        => $startsAt,
        ]);

        $content = $this->startTest();

        $this->assertCount(1, $content['offers']);
    }

    public function testGetCheckoutPreferencesWithMerchantSpecificAndSharedOffers()
    {
        $this->ba->publicAuth();

        $startsAt = Carbon::yesterday(Timezone::IST)->timestamp;

        $offer1 = $this->fixtures->create('offer:wallet', [
            'merchant_id'      => '100000Razorpay',
            'checkout_display' => true,
            'display_text'     => 'Some display text',
            'terms'            => 'Some terms',
            'starts_at'        => $startsAt,
        ]);

        $offer2 = $this->fixtures->create('offer:wallet', [
            'merchant_id'      => '10000000000000',
            'checkout_display' => true,
            'display_text'     => 'Merchant specific offer',
            'terms'            => 'Some terms',
            'starts_at'        => $startsAt,
        ]);

        $response = $this->startTest();

        $this->assertStringStartsWith('offer_', $response['offers'][0]['id']);
    }

    public function testGetCheckoutPreferencesWithMultipleOrderOffers()
    {
        $offer1 = $this->fixtures->create('offer:live_card', ['iins' => ['401200']]);
        $offer2 = $this->fixtures->create('offer:live_card', ['iins' => ['401200']]);

        $order = $this->fixtures->order->createWithOffers([
            $offer1,
            $offer2,
        ]);

        $this->ba->publicAuth();

        $this->testData[__FUNCTION__]['request']['content']['order_id'] = $order->getPublicId();

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithForcedEmiSubventionOffer()
    {
        $this->fixtures->merchant->enableEmi();

        $this->fixtures->create('emi_plan:default_emi_plans');

        $offer = $this->fixtures->create('offer:emi_subvention', [
            'issuer'          => 'HDFC',
            'payment_network' => null,
        ]);

        $order = $this->fixtures->order->createWithOffers($offer, [
            'force_offer' => true,
        ]);

        $response = $this->getPreferences($order->getPublicId());

        // Only one expected, since HDFC is forced
        $this->assertEquals(1, count($response['methods']['emi_options']));
        $this->assertArrayHasKey('HDFC', $response['methods']['emi_options']);

    }

    public function testGetCheckoutPreferencesWithForcedEmiSubventionOfferWithMerchantSpecificEmi()
    {
        $this->fixtures->merchant->enableEmi();

        $this->fixtures->create('emi_plan:default_emi_plans');

        $this->fixtures->create('emi_plan:merchant_specific_emi_plans');

        $offer = $this->fixtures->create('offer:emi_subvention', [
            'issuer'          => 'HDFC',
            'emi_durations'   => [6],
            'payment_network' => null,
        ]);

        $order = $this->fixtures->order->createWithOffers($offer, [
            'force_offer' => true,
        ]);

        $response = $this->getPreferences($order->getPublicId());

        // Only one expected, since HDFC is forced
        $this->assertEquals(1, count($response['methods']['emi_options']));
        $this->assertArrayHasKey('HDFC', $response['methods']['emi_options']);
        $this->assertEquals('6', $response['methods']['emi_options']['HDFC'][0]['duration']);
        $this->assertEquals('0', $response['methods']['emi_options']['HDFC'][0]['interest']);

    }

    public function testGetCheckoutPreferencesForCardlessEmi()
    {
        $this->fixtures->merchant->enableCardlessEmi();

        $this->fixtures->create('terminal:shared_cardless_emi_terminal');

        $response = $this->getPreferences();

        $this->assertEquals(1, count($response['methods']['cardless_emi']));

        $this->assertArrayHasKey('earlysalary', $response['methods']['cardless_emi']);
    }

    public function testGetCheckoutPreferencesForPayLater()
    {
        $this->fixtures->merchant->enablePayLater();

        $this->fixtures->create('terminal:paylater_epaylater_terminal');

        $response = $this->getPreferences();

        $this->assertEquals(1, count($response['methods']['paylater']));

        $this->assertArrayHasKey('epaylater', $response['methods']['paylater']);
    }

    public function testGetCheckoutPreferencesWithInactiveEmiSubventionOffer()
    {
        $this->fixtures->merchant->enableEmi();

        $this->fixtures->create('emi_plan:default_emi_plans');

        $offer = $this->fixtures->create('offer:emi_subvention', [
            'issuer'          => 'HDFC',
            'payment_network' => null,
            'active'          => false
        ]);

        $order = $this->fixtures->order->createWithOffers($offer);

        $response = $this->getPreferences($order->getPublicId());

        // Offer is inactive now, so plans will be back to customer subvention
        foreach ($response['methods']['emi_options']['HDFC'] as $plan)
        {
            $this->assertEquals('customer', $plan['subvention']);
        }
    }

    public function testGetCheckoutPreferencesWithEmiSubventionOfferUnderMinAmount()
    {
        $this->fixtures->merchant->enableEmi();

        $this->fixtures->create('emi_plan:default_emi_plans');

        $offer = $this->fixtures->create('offer:emi_subvention', [
            'issuer'          => 'HDFC',
            'payment_network' => null,
            'emi_durations'   => [
                6,
                9,
            ],
        ]);

        $order = $this->fixtures->order->createWithOffers($offer, ['amount' => 7000]);

        $response = $this->getPreferences($order->getPublicId());

        $hdfcPlans = $response['methods']['emi_options']['HDFC'];

        // Amount is under the minimum amount for EMI subvention offers,
        // so plans show up as customer subvention
        foreach ($response['methods']['emi_options']['HDFC'] as $plan)
        {
            $this->assertEquals('customer', $plan['subvention']);
        }

        $order = $this->fixtures->order->createWithOffers($offer, ['amount' => 700000]);

        $response = $this->getPreferences($order->getPublicId());

        $hdfcPlans = $response['methods']['emi_options']['HDFC'];

        // Amount is above the minimum amount for EMI subvention offers,
        // so plans show up as merchant subvention
        foreach ($response['methods']['emi_options']['HDFC'] as $plan)
        {
            $this->assertEquals('merchant', $plan['subvention']);
        }
    }

    protected function getPreferences($orderId = null)
    {
        $request = [
            'url'     => '/preferences',
            'method'  => 'get',
            'content' => [
            ],
        ];

        if ($orderId !== null)
        {
            $request['content']['order_id'] = $orderId;
        }

        $this->ba->publicAuth();

        return $this->makeRequestAndGetContent($request);
    }

    public function testGetCheckoutPreferencesForPaidOrder()
    {
        $order = $this->fixtures->order->createPaid();

        $this->ba->publicAuth();

        $this->testData[__FUNCTION__]['request']['content']['order_id'] = $order->getPublicId();

        $this->startTest();
    }

    public function testGetCheckoutPreferencesForCancelledInvoice()
    {
        $attributes = [
            'type'         => 'link',
            'status'       => 'cancelled',
            'amount'       => 100000,
            'cancelled_at' => Carbon::now(Timezone::IST)->getTimestamp(),
        ];

        $invoice = $this->createInvoice($attributes);

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testGetCheckoutPreferencesForExpiredInvoice()
    {
        $attributes = [
            'type'       => 'link',
            'status'     => 'expired',
            'amount'     => 100000,
            'expired_at' => Carbon::now(Timezone::IST)->getTimestamp(),
        ];

        $invoice = $this->createInvoice($attributes);

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithOrderRelatedUndiscountedOffer()
    {
        $this->ba->publicAuth();

        $startsAt = Carbon::yesterday(Timezone::IST)->timestamp;

        $testData = $this->testData[__FUNCTION__];

        $request = $testData['request'];

        foreach ($testData['tests'] as $test)
        {
            $data = [
                'request' => $request,
                'response' => $test['response'],
            ];

            $fixtureData = $test['offer'];
            $fixtureData['starts_at'] = $startsAt;

            $offer = $this->fixtures->create('offer', $fixtureData);
            $order = $this->fixtures->order->createWithUndiscountedOffers($offer, [
                'force_offer' => true,
            ]);

            $data['request']['url'] = '/preferences?order_id=' . $order->getPublicId();

            $this->runRequestResponseFlow($data);
        }
    }

    public function testGetCheckoutPreferencesWithoutOfferWithInvalidAmount()
    {
        $offerWithoutMinAmount = $this->fixtures->create('offer',[
            'name'       => 'offer_without_min_amount',
        ]);

        $offerWithMinAmount = $this->fixtures->create('offer', [
            'name'       => 'offer_with_min_amount',
            'min_amount' => 10000,
        ]);

        // Order created with 2 offers
        $order = $this->fixtures->order->createWithOffers([
            $offerWithoutMinAmount,
            $offerWithMinAmount
        ], [ 'amount' => 5000 ]);

        $this->ba->publicAuth();

        $response = $this->makeRequestAndGetContent([
            'method'  => 'GET',
            'url'     => '/preferences?order_id=' . $order->getPublicId(),
        ]);

        $preferencesOffers = $response['offers'];

        // Only 1 offers appears in preferences response
        $this->assertEquals(count($preferencesOffers), 1);
        // The one without a criteria on amount
        $this->assertEquals($preferencesOffers[0]['name'], 'offer_without_min_amount');
    }

    public function testGetCheckoutPreferencesWithOrderRelatedOffer()
    {
        $this->ba->publicAuth();

        $startsAt = Carbon::yesterday(Timezone::IST)->timestamp;

        $testData = $this->testData[__FUNCTION__];

        $request = $testData['request'];

        foreach ($testData['tests'] as $test)
        {
            $data = [
                'request' => $request,
                'response' => $test['response'],
            ];

            $fixtureData = $test['offer'];
            $fixtureData['starts_at'] = $startsAt;

            $offer = $this->fixtures->create('offer', $fixtureData);
            $order = $this->fixtures->order->createWithOffers($offer);

            $data['request']['url'] = '/preferences?order_id=' . $order->getPublicId();

            $this->runRequestResponseFlow($data);
        }
    }

    public function testGetCheckoutPreferencesWithOrderInActiveOffer()
    {
        $this->ba->publicAuth();

        $offer = $this->fixtures->create('offer:live_card', ['iins' => ['401200'],
            'active' => 0
        ]);

        $order = $this->fixtures->order->createWithOffers($offer);

        $testData = $this->testData['testOfferCheckoutPreferences'];

        $testData['request']['content']['order_id'] = $order->getPublicId();

        $response = $this->runRequestResponseFlow($testData);

        $this->assertArrayNotHasKey('offers', $response);
    }

    public function testGetCheckoutPreferencesWithOrderExpiredOffer()
    {
        $this->ba->publicAuth();

        $offer = $this->fixtures->create('offer:expired', ['iins' => ['401200'],
            'active' => 0
        ]);

        $order = $this->fixtures->order->createWithOffers($offer);

        $testData = $this->testData['testOfferCheckoutPreferences'];

        $testData['request']['content']['order_id'] = $order->getPublicId();

        $response = $this->runRequestResponseFlow($testData);

        $this->assertArrayNotHasKey('offers', $response);
    }

    public function testGetCheckoutPreferencesWithOrderForceOfferExpired()
    {
        $this->ba->publicAuth();

        $offer = $this->fixtures->create('offer:expired', ['iins' => ['401200'],
            'active' => 0
        ]);

        $order = $this->fixtures->order->createWithOffers($offer,[
            'force_offer' => true,
        ]);

        $testData = $this->testData['testOfferCheckoutPreferences'];

        $testData['request']['content']['order_id'] = $order->getPublicId();

        $response = $this->runRequestResponseFlow($testData);

        $this->assertArrayNotHasKey('offers', $response);
    }

    public function testGetCheckoutPreferencesWithMultipleOrderOffersInActive()
    {
        $offer1 = $this->fixtures->create('offer:live_card', ['iins' => ['401200'],
            'active' => 0
        ]);

        $offer2 = $this->fixtures->create('offer:live_card', ['iins' => ['401200'],
            'active' => 1]);

        $order = $this->fixtures->order->createWithOffers([
            $offer1,
            $offer2,
        ]);

        $this->ba->publicAuth();

        $testData = $this->testData['testOfferCheckoutPreferences'];

        $testData['request']['content']['order_id'] = $order->getPublicId();

        $response = $this->runRequestResponseFlow($testData);

        $this->assertNotNull($response['offers']);

        $this->assertEquals(1, count($response['offers']));

        $this->assertEquals('offer_' . $offer2->getId(), $response['offers']['0']['id']);
    }

    public function testGetCheckoutPreferencesWithMultipleOrderOffersExpired()
    {
        $startsAt = Carbon::yesterday(Timezone::IST)->timestamp;

        $endsAt = Carbon::now(Timezone::IST)->timestamp;

        $offer1 = $this->fixtures->create('offer:expired', ['iins' => ['401200']]);

        $offer2 = $this->fixtures->create('offer:live_card', ['iins' => ['401200']]);

        $order = $this->fixtures->order->createWithOffers([
            $offer1,
            $offer2,
        ]);

        $this->ba->publicAuth();

        $testData = $this->testData['testOfferCheckoutPreferences'];

        $testData['request']['content']['order_id'] = $order->getPublicId();

        $response = $this->runRequestResponseFlow($testData);

        $this->assertNotNull($response['offers']);

        $this->assertEquals(1, count($response['offers']));

        $this->assertEquals('offer_' . $offer2->getId(), $response['offers']['0']['id']);
    }

    public function testGetCheckoutPreferencesWithEmiOffer()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->enableEmi();

        $startsAt = Carbon::yesterday(Timezone::IST)->timestamp;

        $testData = $this->testData[__FUNCTION__];

        $offer = $this->fixtures->create('offer', [
            'payment_method' => 'emi',
            'error_message'  => 'Payment method used is not eligible for offer. ' .
                                'Please try with a different payment method.',
            'display_text'   => 'Some display text',
            'percent_rate'   => 5000,
            'min_amount'     => 200000,
            'terms'          => 'Some terms',
        ]);

        $order = $this->fixtures->order->createWithOffers($offer, ['amount' => 300000]);

        $testData['request']['url'] = '/preferences?order_id=' . $order->getPublicId();

        $this->runRequestResponseFlow($testData);
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

        $this->fixtures->merchant->addFeatures(['google_pay_omnichannel']);

        $response = $this->startTest();

        $this->assertNotNull($response['features']['google_pay']);

        $this->assertNotNull($response['features']['google_pay_omnichannel']);
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

    public function testPutEmiMethod()
    {
        $this->fixtures->create('pricing:emi_pricing_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach('10000000000000');

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testGetKeySecret()
    {
        $this->ba->appAuth();

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
        Event::assertNotDispatched(CacheHit::class);

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

        $this->ba->appAuth();

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

    protected function startTest($testDataToReplace = [])
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

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

        $this->ba->appAuth();

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

        $this->assertContains('/logos/', $response['logo_url']);
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

        $this->assertContains($defaultImgPath, $merchant->getLogoUrl());

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
        $this->ba->appAuth();

        $merchant = $this->createMerchant();

        $this->startTest();

        $scheduleTask = $this->getLastEntity('schedule_task', true);

        $this->assertEquals(null, $scheduleTask['method']);

        $this->ba->appAuthLive();

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

    protected function createUserMerchantMapping(string $userId, string $merchantId, string $role, $mode='test')
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

        Mail::assertQueued(PasswordResetMail::class, function ($mailable)
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

        Mail::assertQueued(PasswordResetMail::class, function ($mailable)
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
                'id'         => '10000000000App',
                'type'       => 'partner',
                'deleted_at' => Carbon::now()->timestamp]);

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

            $this->assertNotEmpty($mailData['org']);

            $this->assertTrue($mailable->hasTo('test1@razorpay.com'));

            return true;
        });

        Mail::assertNotQueued(PasswordResetMail::class);

        $mapping = $this->fixtures->user->getMerchantUserMapping($merchant['id'], $user2['id']);

        $this->assertEquals(1, count($mapping));
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

        $this->createOAuthApplication(['id' => '10000000000App', 'type' => 'partner']);

        $this->fixtures->create('merchant_access_map', ['merchant_id' => '10000000000040']);

        $this->ba->proxyAuth();

        $this->startTest();

        Mail::assertQueued(PasswordResetMail::class, function ($mailable)
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

        $this->createOAuthApplication(['id' => '10000000000App', 'type' => 'partner']);

        $this->fixtures->create('merchant_access_map', ['merchant_id' => '10000000000040']);

        $this->ba->proxyAuth();

        $this->startTest();

        Mail::assertQueued(PasswordResetMail::class, function ($mailable)
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
        $this->fixtures->create('merchant',[
            'id'     => '10000000000040',
            'email'  => 'test@razorpay.com',
        ]);

        $this->fixtures->user->createUserForMerchant('10000000000000', ['email' => 'test@razorpay.com']);

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'fully_managed']);

        $this->createOAuthApplication(['id' => '10000000000App', 'type' => 'partner']);

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

        $this->createOAuthApplication(['id' => '10000000000App', 'type' => 'partner']);

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

        $this->createOAuthApplication(['id' => '10000000000App', 'type' => 'partner']);

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

        $this->createOAuthApplication(['id' => '10000000000App', 'type' => 'partner']);

        $this->fixtures->create('merchant_access_map', ['merchant_id' => '10000000000040']);

        $this->ba->proxyAuth();

        $this->startTest();

        Mail::assertQueued(PasswordResetMail::class, function ($mailable)
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

        $this->createOAuthApplication(['id' => '10000000000App', 'type' => 'partner']);

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
            'type'          => 'partner'
        ]);

        $this->fixtures->create('merchant_access_map', [
            'merchant_id' => 'submerchant001',
            'entity_id'   => '10000000000App',
            'entity_type' => 'application',
        ]);

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

        $this->ba->appAuth();

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

        $this->ba->appAuth();

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
        $this->ba->proxyAuth();
        $this->fixtures->merchant->activate();

        // 5th Nov 2018, 10 AM, Monday
        Carbon::setTestNow(Carbon::create(2018, 11, 5, 10, null, null, Timezone::IST));

        $this->startTest();
    }

    public function testSubmitSupportCallRequestWithInvalidContact()
    {
        $this->ba->proxyAuth();
        $this->fixtures->merchant->activate();

        // 5th Nov 2018, 10 AM, Monday
        Carbon::setTestNow(Carbon::create(2018, 11, 5, 10, null, null, Timezone::IST));

        $this->startTest();
    }

    public function testSubmitSupportCallRequestOnNonWorkingHours()
    {
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

        $this->CreateBalanceEntities();

        $this->ba->proxyAuth();

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

        $this->CreateBalanceEntities();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    /**
     * Creates balance entities
     *
     * @return array
     */
    private function CreateBalanceEntities(): array
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

    /**
     * Switches product of merchant from PG to BB.
     */
    public function testMerchantSwitchProduct()
    {
        $user = (new User())->createUserForMerchant();

        $this->fixtures->edit('merchant', '10000000000000', ['activated' => true, 'business_banking' => true]);

        $this->fixtures->create('terminal:bank_account_terminal_for_business_banking', ['merchant_id' => '100000Razorpay']);

        // To create a virtual account we need to enable bank transfer
        $this->fixtures->edit('methods', '10000000000000', ['bank_transfer' => true]);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id'], 'owner');

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $this->startTest();

        $merchants = DB::connection('test')->table('merchant_users')
                                           ->where('user_id', '=', $user['id'])
                                           ->pluck('merchant_id', 'product');

        $this->assertEquals(count($merchants), 2);

        $this->assertArrayHasKey('banking', $merchants);
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
        // Mock Razorx
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->willReturn('On');

        $merchantDetailsData = [
            'business_category'     => 'not_for_profit',
            'business_subcategory'  => 'educational',
            'activation_status'     => 'activated',
        ];

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', $merchantDetailsData);

        $merchantId = $merchantDetail->getMerchantId();

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

        $this->ba->proxyAuth('rzp_test_' . $merchant['id']);

        $this->startTest();
    }

    public function testInternationalDisableWhenAlreadyInActive()
    {
        $merchant = $this->fixtures->create('merchant');

        $merchantData = [
            'international'     => 0,
            'activated'         => 1,
            'convert_currency'  => false,
        ];

        $this->fixtures->edit('merchant', $merchant['id'], $merchantData);

        $this->ba->proxyAuth('rzp_test_' . $merchant['id']);

        $this->startTest();
    }

    public function testInternationalToggleWithInvalidValue()
    {
        $merchant = $this->fixtures->create('merchant');

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
        $this->fixtures->admin->edit($admin["id"], ['allow_all_merchants' => true]);

        $this->ba->adminProxyAuth($merchantId, 'rzp_test_' . $merchantId);

        $this->startTest();

        $testKeyValue = Redis::connection()->get('test:tag:merchant_10000000000000:key');
        $liveKeyValue = Redis::connection()->get('live:tag:merchant_10000000000000:key');

        $this->assertNull($testKeyValue);
        $this->assertNotNull($liveKeyValue);

        Redis::connection()->flushdb();
        Redis::connection()->flushdb();

        $this->ba->adminProxyAuth($merchantId, 'rzp_live_' . $merchantId);

        $this->startTest();

        $testKeyValue = Redis::connection()->get('test:tag:merchant_10000000000000:key');
        $liveKeyValue = Redis::connection()->get('live:tag:merchant_10000000000000:key');

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
            'content'   =>  [
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
}
