<?php

namespace RZP\Tests\Functional\Merchant;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use DB;
use Mail;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;

use RZP\Mail\Merchant\Activation as ActivationMail;
use RZP\Mail\Merchant\AccountChange as BankAccountChangeMail;
use RZP\Mail\Banking\BeneficiaryFile as BeneficiaryFileMail;
use RZP\Models\Merchant;
use RZP\Models\Transaction;
use RZP\Models\Merchant\Methods;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Helpers\EntityActionTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\Helpers\Schedule\ScheduleTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Settlement\SettlementTrait;

class MerchantTest extends TestCase
{
    use ScheduleTrait;
    use SettlementTrait;
    use InteractsWithSession;
    use EntityActionTrait;
    use RequestResponseFlowTrait;
    use HeimdallTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantTestData.php';

        parent::setUp();

        $this->ba->appAuth();
    }

    public function testCreateKey()
    {
        $this->createMerchant();

        $this->startTest();
    }

    public function testCreateKeyForNonActivatedMerchant()
    {
        $this->createMerchant();

        $this->ba->appAuthLive();
        $this->startTest();
    }

    public function testGetMerchant()
    {
        $this->createMerchant();

        $this->ba->appAuthTest();
        $this->startTest();

        $this->ba->appAuthLive();
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

        $this->ba->appAuth();

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'role'        => 'owner1',
            'merchant_id' => $merchant['id']
        ];

        $testData['request']['url'] = '/merchants/' . $merchant['id'] . '/users';

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

        $this->ba->proxyAuthLive();
        $this->testData[__FUNCTION__]['response']['content']['balance'] = 0;
        $this->startTest();
    }

    public function testMerchantFetchKeys()
    {
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
        $content = $this->startTest();

        $expired = time() + 1;

        $this->assertLessThan($expired, $content['old']['expired_at']);
    }

    public function testUpdateKeyExpireInFuture()
    {
        $content = $this->startTest();

        $expired = time() + 10;

        $this->assertGreaterThan($expired, $content['old']['expired_at']);
    }

    public function testUpdateKeyTwice()
    {
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

        $this->startTest();
    }

    public function testEditTransactionEmailWithError()
    {
        $this->createMerchant();

        $this->startTest();
    }

    public function testEditMerchantEmail()
    {
        $this->createMerchant();

        $this->startTest();
    }

    public function testEditMerchantUppercaseEmail()
    {
        $this->createMerchant();

        $this->startTest();
    }

    public function testEditMerchantEmptyEmail()
    {
        $this->createMerchant();

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

    public function testEditMerchantInvalidAutoRefundDelay()
    {
        $this->createMerchant();

        $this->startTest();
    }

    public function testEditMerchantInvalidDurationAutoRefundDelay()
    {
        $this->createMerchant();

        $this->startTest();
    }

    public function testEditMerchantAutoRefundDelay()
    {
        $this->createMerchant();

        $this->startTest();
    }

    public function testAddCategory2()
    {
        $this->createMerchant();

        $this->startTest();
    }

    public function testAddInvalidCategory2()
    {
        $this->createMerchant();

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

    public function testActivateMerchantWithoutBankAccount()
    {
        $this->ba->appAuthLive();

        $this->startTest();
    }

    public function testActivateMerchant()
    {
        Mail::fake();

        $this->ba->appAuthLive();

        $ba = $this->fixtures
                   ->on('live')
                   ->create(
                        'merchant:bank_account',
                        ['merchant_id' => '1cXSLlUU8V9sXl',
                         'entity_id'   => '1cXSLlUU8V9sXl',
                         'type'        => 'merchant']);

        $this->fixtures->create('org_hostname', [
            'org_id'    => '100000razorpay',
            'hostname'  => 'dashboard.razorpay.com'
        ]);

        $activatedAt = time();

        $content = $this->startTest();

        $this->assertLessThanOrEqual($content['activated_at'], $activatedAt);

        // We check that the merchant balance is just zero in live mode
        $this->ba->proxyAuth('rzp_live_1cXSLlUU8V9sXl');

        $testData = $this->testData['testGetBalance'];
        $testData['request']['url'] = '/balance';
        $testData['response']['content']['id'] = '1cXSLlUU8V9sXl';
        $testData['response']['content']['balance'] = 0;

        $this->runRequestResponseFlow($testData);

        Mail::assertSent(ActivationMail::class, function ($mailable)
        {
            $mailData = $mailable->viewData;

            $this->assertNotNull($mailData['merchant']);
            $this->assertNotNull($mailData['rules']);
            $this->assertNotNull($mailData['subject']);

            $this->assertNotNull($mailData['merchant']['name']);
            $this->assertNotNull($mailData['merchant']['website']);
            $this->assertNotNull($mailData['merchant']['billing_label']);
            $this->assertNotNull($mailData['merchant']['email']);
            $this->assertNotNull($mailData['merchant']['org']);

            $this->assertNotNull($mailData['merchant']['org']['business_name']);
            $this->assertNotNull($mailData['merchant']['org']['hostname']);
            $this->assertNotNull($mailData['merchant']['org']['custom_code']);

            $this->assertNotNull($mailData['rules']['amountRangeRules']);
            $this->assertNotNull($mailData['rules']['otherRules']);

            return true;
        });

        // Because rest of the tests require appAuth, reset it back
        $this->ba->appAuthLive();
    }

    public function testMerchantEnableLive()
    {
        $this->testMerchantDisableLive();
        $this->startTest();
    }

    public function testMerchantDisableLive()
    {
        $this->testActivateMerchant();

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

        $this->startTest();

        $merchant = $this->getEntityById('merchant', $merchant['id'], true);

        $this->assertNotNull($merchant['archived_at']);
    }

    public function testMerchantArchiveWithNoMerchantDetails()
    {
        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, 'org_'.$this->org->id);

        $this->startTest();
    }

    public function testMerchantArchiveForAlreadyArchivedMerchant()
    {
        $merchant = $this->getLastEntity('merchant', true);

        $this->fixtures->base->editEntity('merchant', $merchant['id'], [ 'archived_at' => '123456789' ]);

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, 'org_'.$this->org->id);

        $this->startTest();
    }

    public function testMerchantUnarchive()
    {
        $merchant = $this->getLastEntity('merchant', true);

        $this->fixtures->base->editEntity('merchant', $merchant['id'], [ 'archived_at' => '123456789' ]);

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, 'org_'.$this->org->id);

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

        $this->startTest();
    }

    public function testMerchantUnSuspend()
    {
        $merchant = $this->getLastEntity('merchant', true);

        $this->fixtures->base->editEntity('merchant', $merchant['id'], [ 'suspended_at' => '123456789' ]);

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, 'org_'.$this->org->id);

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

        $this->startTest();

        Mail::assertSent(BankAccountChangeMail::class, function ($mail)
        {
            $testData = $this->testData['testAddBankAccount']['response']['content'];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            return true;
        });
    }

    public function testAddBankAccountWithMerchantDetail()
    {
        Mail::fake();

        $merchantDetail = $this->fixtures->create('merchant_detail',
                                                [
                                                    'merchant_id' => '10000000000000',
                                                ]);

        $this->startTest();

        Mail::assertSent(BankAccountChangeMail::class, function ($mail)
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

    public function testAddBankAccountWithInvalidIFSC()
    {
        $this->startTest();
    }

    public function testGetBankAccount()
    {
        $this->testAddBankAccount();

        $content = $this->startTest();
    }

    public function testChangeBankAccount()
    {
        $this->markTestSkipped('Change bank account is breaking for now');

        $this->testAddBankAccount();

        $content = $this->startTest();

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

        $this->initiateSettlements('kotak', $settleAtTimestamp);

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
        $this->ba->appAuth();

        $content = $this->startTest();
    }

    public function testSetEmptyBanks()
    {
        $this->ba->appAuth();

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

    public function testGetBanksByAppAuth()
    {
        $this->testSetBanks();

        $this->ba->appAuth();

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

        $terminal = $this->fixtures->on('live')->create('terminal', $attributes);

        $content = $this->startTest();
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

        $this->assertCount(21, $banks);

        $this->fixtures->merchant->disableTPV();
    }

    public function testGetCheckoutPreferencesWithAllCardGeatewayDowntime()
    {
        $this->ba->publicAuth();

        $this->fixtures->create('gateway_downtime:card', [
            'gateway' => 'ALL',
            'issuer'  => 'ALL',
            'network' => 'VISA']);

        $this->startTest();
    }

    public function testGetNetbankingDowntimeInfoForDirectNetbankingGateway()
    {
        $this->ba->publicAuth();

        $this->fixtures->create('gateway_downtime:netbanking', [
            'gateway' => 'netbanking_hdfc',
            'issuer'  => 'ALL']);

        $this->startTest();
    }

    public function testGetNetbankingDowntimeInfoWithSharedNetbankingGateway()
    {
        $this->ba->publicAuth();

        $this->fixtures->create('gateway_downtime:netbanking', [
            'gateway' => 'billdesk',
            'issuer'  => 'ALL']);

        $this->startTest();
    }

    public function testGetNetbankingDowntimeInfoWithBothSharedAndDirectGateway()
    {
        $this->ba->publicAuth();

        $this->fixtures->create('gateway_downtime:netbanking', [
            'gateway' => 'billdesk',
            'issuer'  => 'ALL']);

        $this->fixtures->create('gateway_downtime:netbanking', [
            'gateway' => 'netbanking_hdfc',
            'issuer'  => 'ALL']);

        $this->startTest();
    }

    public function testGetNetbankingDowntimeWithNoBanksExclusiveToGateway()
    {
         $this->ba->publicAuth();

         $this->fixtures->create('gateway_downtime:netbanking', [
            'gateway' => 'ebs',
            'issuer'  => 'ALL']);

         $this->startTest();
    }

    public function testGetNetbankingDowntimeInfoWithIssuerExclusiveToGateway()
    {
        $this->ba->publicAuth();

        $this->fixtures->create('gateway_downtime:netbanking', [
            'gateway' => 'billdesk',
            'issuer'  => 'ALLA']);

        $this->startTest();
    }

    public function testGetNetbankingDowntimeInfoWithIssuerNA()
    {
        $this->ba->publicAuth();

        $this->fixtures->create('gateway_downtime:netbanking', [
            'gateway' => 'billdesk',
            'issuer'  => 'NA']);

        $this->startTest();
    }

    public function testGetNetbankingDowntimeInfoWithGatewayAll()
    {
        $this->ba->publicAuth();

        $this->fixtures->create('gateway_downtime:netbanking', [
            'gateway' => 'ALL',
            'issuer'  => 'HDFC']);

        $this->startTest();
    }

    public function testGetNetbankingDowntimeInfoWithMultipleDowntimes()
    {
        $this->ba->publicAuth();

        $this->fixtures->create('gateway_downtime:netbanking', [
            'gateway'     => 'netbanking_hdfc',
            'issuer'      => 'HDFC',
            'reason_code' => 'ISSUER_DOWN']);

        $this->fixtures->create('gateway_downtime:netbanking', [
            'gateway'     => 'billdesk',
            'issuer'      => 'ALLA',
            'reason_code' => 'LOW_SUCCESS_RATE']);

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithCardDowntimeWithIssuerOrNetworkUnknown()
    {
        $this->ba->publicAuth();

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

        $this->fixtures->create('gateway_downtime:card', [
            'gateway' => 'hdfc',
            'issuer'  => 'ALL',
            'network' => 'ALL']);

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithCardDowntimeWithGatewayExclusiveNetworkDown()
    {
        $this->ba->publicAuth();

        $this->fixtures->create('gateway_downtime:card', [
            'gateway' => 'hdfc',
            'issuer'  => 'ALL',
            'network' => 'DICL']);

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithNetbankingDowntimeWithAllGateway()
    {
        $this->ba->publicAuth();

        $this->fixtures->create('gateway_downtime:netbanking', [
            'gateway' => 'ALL',
            'issuer'  => 'HDFC',]);

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithNetbankingDowntimeWithSharedNetbankingGateway()
    {
        $this->ba->publicAuth();

        $this->fixtures->create('gateway_downtime:netbanking', [
            'gateway' => 'billdesk',
            'issuer'  => 'ALL',]);

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithNetbankingWithIssuerExclusiveTogateway()
    {
        $this->ba->publicAuth();

         $this->fixtures->create('gateway_downtime:netbanking', [
            'gateway' => 'billdesk',
            'issuer'  => 'ALLA',]);

         $this->startTest();
    }

    public function testGetCheckoutPreferencesWithDirectNetbankingDowntime()
    {
        $this->ba->publicAuth();

        $this->fixtures->create('gateway_downtime:netbanking', [
            'gateway'     => 'netbanking_hdfc',
            'issuer'      => 'ALL',]);

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithWalletDowntime()
    {
        $this->ba->publicAuth();

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
            $order = $this->fixtures->create('order:with_offer_applied', ['offer_id' => $offer->getId()]);

            $data['request']['url'] = '/preferences?order_id=' . $order->getPublicId();

            $this->runRequestResponseFlow($data);
        }
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

    public function testPutPaytmMethod()
    {
        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1A0Fkd38fGZPVC']);
        $this->fixtures->merchant->disableInternational();

        $this->ba->appAuth();

        $content = $this->startTest();
    }

    public function testPutEmiMethod()
    {
        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $this->ba->appAuth();

        $content = $this->startTest();
    }

    public function testGetKeySecret()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testGetMercantBeneficiaryFile()
    {
        Mail::fake();

        $this->ba->appAuth();

        $request = array(
            'url' => '/merchants/beneficiary/file',
            'method' => 'get',
            'content' => [],
        );

        $content = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('url', $content);

        Mail::assertSent(BeneficiaryFileMail::class);
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

    protected function createMerchant()
    {
        $id = '1X4hRFHFx4UiXt';

        $merchant = [
            'id'    => $id,
            'name'  => 'Tester 2',
            'email' => 'liveandtest@localhost.com'
        ];

        $request = [
            'content' => $merchant,
            'url' => '/merchants',
            'method' => 'POST'
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->merchantAssignPricingPlan('1hDYlICobzOCYt', $id);

        $this->assertArraySelectiveEquals($merchant, $content);

        return $content;
    }

    protected function createUploadedFile($file)
    {
        $this->assertFileExists($file);

        $mimeType = "image/png";
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

    public function testGetMerchantFeatures()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testUpdateMerchantFeatures()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testUpdateMerchantUnEditableFeatures()
    {
        $this->ba->proxyAuth();

        $this->startTest();
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

        $content = $this->makeRequestAndGetContent($request);

        $row = DB::table('merchant_map')
                   ->where('merchant_id', '=', $content['id'])
                   ->where('entity_id', '=', Org::SUPER_ADMIN)
                   ->where('entity_type', '=', 'admin')
                   ->first();

        $this->assertNotNull($row);
    }

    protected function createUserMerchantMapping(string $userId, string $merchantId, string $role)
    {
        DB::table('merchant_users')
            ->insert([
                'merchant_id' => $merchantId,
                'user_id'     => $userId,
                'role'        => $role,
                'created_at'  => 1493805150,
                'updated_at'  => 1493805150
            ]);
    }
}
