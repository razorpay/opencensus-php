<?php

namespace RZP\Tests\Functional\Merchant;

use Mail;
use Carbon\Carbon;

use RZP\Models\Admin;
use RZP\Constants\Timezone;
use RZP\Models\Pricing\Fee;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Banking\LowBalanceAlert;
use RZP\Models\User\Entity as UserEntity;
use RZP\Tests\Functional\Fixtures\Entity\User;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;

class LowBalanceConfigTest extends TestCase
{
    use DbEntityFetchTrait;
    use TestsBusinessBanking;
    use RequestResponseFlowTrait;

    /**
     * @var UserEntity
     */
    protected $nonOwnerUser;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/LowBalanceConfigTestData.php';

        parent::setUp();

        $this->setUpMerchantForBusinessBankingLive(true, 10000000);

        $this->fixtures->on('live')->merchant->edit(
            '10000000000000', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        // Merchant needs to be activated to make live requests
        $this->fixtures->on('live')->merchant->edit('10000000000000', ['activated' => 1]);

        // Create merchant user mapping
        $this->fixtures->on('live')->user->createUserMerchantMapping(
            [
                'merchant_id' => '10000000000000',
                'user_id'     => User::MERCHANT_USER_ID,
                'product'     => 'banking',
                'role'        => 'owner',
            ], 'live');

        $this->fixtures->user->createBankingUserForMerchant(
            '10000000000000', ['id' => 'MerchantUser02'], 'Finance L3', 'live');
        $this->nonOwnerUser =  $this->getDbLastEntity('user', 'live');
    }

    /**
     *  added for RAZORPAY_X_ACL_DENY_UNAUTHORISED which was added to
     *  identify impact on other clients if unauthorised requests are blocked.
     *  check UserAccess.php -> validateBankingUserAccess for better understanding
     */
    protected function mockRazorx()
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
                                  if ($feature === 'razorpay_x_acl_deny_unauthorised')
                                  {
                                      return 'on';
                                  }
                                  return 'on';
                              }));
    }

    public function testCreateLowBalanceConfig()
    {
        $oldDateTime = Carbon::create(2019, 07, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->ba->proxyAuth('rzp_live_10000000000000', User::MERCHANT_USER_ID);

        $request = [
            'url'     => '/low_balance_configs',
            'method'  => 'POST',
            'content' => [
                'account_number'      => '2224440041626905',
                'threshold_amount'    => 1000,
                'notification_emails' => ['kunal.sikri@razorpay.com', 'abcd@razorpay.com'],
                'notify_after'        => 21600 // 6hrs
            ],
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ];

        $observedResult = $this->makeRequestAndGetContent($request);

        $expectedResult = [
            'account_number'      => '2224440041626905',
            'threshold_amount'    => '1000',
            'notification_emails' => ['kunal.sikri@razorpay.com','abcd@razorpay.com'],
            'notify_after'        => '21600',
            'status'              => 'enabled',
        ];

        $this->assertArraySelectiveEquals($expectedResult, $observedResult);

        Carbon::setTestNow();

        return $observedResult;
    }

    public function testCreateLowBalanceConfigWhenAConfigAlreadyExists()
    {
        $this->testCreateLowBalanceConfig();

        $this->startTest();
    }

    public function testUpdateThresholdAmountForLowBalanceConfig()
    {
        $lowBalanceConfig = $this->testCreateLowBalanceConfig();

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/low_balance_configs/'  . $lowBalanceConfig['id'];
        $this->startTest();
    }

    public function testUpdateNotificationEmailsForLowBalanceConfig()
    {
        $lowBalanceConfig = $this->testCreateLowBalanceConfig();

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/low_balance_configs/'  . $lowBalanceConfig['id'];
        $this->startTest();
    }

    public function testUpdateNotifyAfterForLowBalanceConfig()
    {
        $lowBalanceConfig = $this->testCreateLowBalanceConfig();

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/low_balance_configs/'  . $lowBalanceConfig['id'];
        $this->startTest();
    }

    public function testUpdateNotifyAfterForLowBalanceConfigForNonOwnerUser()
    {
        $lowBalanceConfig = $this->testCreateLowBalanceConfig();

        $this->mockRazorx();

        $this->ba->proxyAuth('rzp_live_10000000000000', $this->nonOwnerUser->getId());

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/low_balance_configs/'  . $lowBalanceConfig['id'];
        $this->startTest();
    }

    public function testCreateLowBalanceConfigForNonOwnerUser()
    {
        $oldDateTime = Carbon::create(2019, 07, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->mockRazorx();

        $this->ba->proxyAuth('rzp_live_10000000000000', $this->nonOwnerUser->getId());

        $this->startTest();

        Carbon::setTestNow();
    }

    public function testDeleteLowBalanceConfig()
    {
        $lowBalanceConfig = $this->testCreateLowBalanceConfig();

        $countBeforeDeleting = count($this->getDbEntities('low_balance_config',[], 'live'));

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/low_balance_configs/'  . $lowBalanceConfig['id'];
        $this->startTest();

        $countAfterDeletion = count($this->getDbEntities('low_balance_config',[], 'live'));

        $this->assertEquals(1, $countBeforeDeleting - $countAfterDeletion);
    }

    public function testDeleteLowBalanceConfigForNonOwnerUser()
    {
        $lowBalanceConfig = $this->testCreateLowBalanceConfig();

        $this->ba->proxyAuth('rzp_live_10000000000000', $this->nonOwnerUser->getId());

        $this->mockRazorx();

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/low_balance_configs/'  . $lowBalanceConfig['id'];
        $this->startTest();
    }

    public function testDisableLowBalanceConfig()
    {
        $lowBalanceConfig = $this->testCreateLowBalanceConfig();

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/low_balance_configs/' . $lowBalanceConfig['id'] . '/disable';

        $this->startTest();
    }

    public function testDisableLowBalanceConfigForNonOwnerUser()
    {
        $lowBalanceConfig = $this->testCreateLowBalanceConfig();

        $this->ba->proxyAuth('rzp_live_10000000000000', $this->nonOwnerUser->getId());

        $this->mockRazorx();

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/low_balance_configs/'  . $lowBalanceConfig['id'];
        $this->startTest();
    }

    public function testEnableLowBalanceConfig()
    {
        $lowBalanceConfig = $this->testCreateLowBalanceConfig();

        $this->fixtures->edit('low_balance_config', $lowBalanceConfig['id'], ['status' => 'disabled']);
        $lowBalanceConfig = $this->getDbLastEntityToArray('low_balance_config','live');

        $this->assertSame('disabled', $lowBalanceConfig['status']);

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/low_balance_configs/'  . 'lbc_' . $lowBalanceConfig['id'] . '/enable';

        $this->startTest();
    }

    public function testEnableLowBalanceConfigForNonOwnerUser()
    {
        $lowBalanceConfig = $this->testCreateLowBalanceConfig();

        $this->fixtures->edit('low_balance_config', $lowBalanceConfig['id'], ['status' => 'disabled']);
        $lowBalanceConfig = $this->getDbLastEntityToArray('low_balance_config','live');

        $this->assertSame('disabled', $lowBalanceConfig['status']);

        $this->ba->proxyAuth('rzp_live_10000000000000', $this->nonOwnerUser->getId());

        $this->mockRazorx();

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/low_balance_configs/'  . $lowBalanceConfig['id'];
        $this->startTest();
    }

    public function testGetLowBalanceConfigById()
    {
        $lowBalanceConfig = $this->testCreateLowBalanceConfig();

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/low_balance_configs/'  . $lowBalanceConfig['id'];
        $this->startTest();
    }

    public function testGetLowBalanceConfigByIdForNonOwnerUser()
    {
        $lowBalanceConfig = $this->testCreateLowBalanceConfig();

        $this->ba->proxyAuth('rzp_live_10000000000000', $this->nonOwnerUser->getId());

        $testData = & $this->testData['testGetLowBalanceConfigById'];
        $testData['request']['url'] = '/low_balance_configs/'  . $lowBalanceConfig['id'];
        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }

    public function testFetchMultipleLowBalanceConfigs()
    {
        $this->testCreateLowBalanceConfig();

        $this->fixtures->on('live')->create('low_balance_config', [
            'id'                  => 'F0wNzLiuKgNuPF',
            'balance_id'          => 'xbalance00001',
            'threshold_amount'    => '100',
            'notification_emails' => 'rtz@razorpay.com,xyz@razorpay.com',
            'notify_after'        => '32400', // 9 hrs
            'status'              => 'enabled',
        ]);

        $this->startTest();

        Carbon::setTestNow();
    }

    public function testFetchMultipleLowBalanceConfigsForNonOwnerUser()
    {
        $this->testCreateLowBalanceConfig();

        $this->fixtures->on('live')->create('low_balance_config', [
            'id'                  => 'F0wNzLiuKgNuPF',
            'balance_id'          => 'xbalance00001',
            'threshold_amount'    => '100',
            'notification_emails' => 'rtz@razorpay.com,xyz@razorpay.com',
            'notify_after'        => '32400', // 9 hrs
            'status'              => 'enabled',
        ]);

        $testData = & $this->testData['testFetchMultipleLowBalanceConfigs'];
        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }

    public function testCreateLowBalanceConfigInTestMode()
    {
        $this->ba->proxyAuth('rzp_test_10000000000000', User::MERCHANT_USER_ID);

        $this->startTest();
    }

    // creates 5 configs
    // direct account 1 with gateway_balance more updated
    // direct account 2 with statement fetch more updated
    // shared account with disabled config
    // 2 shared account with config enabled
    public function createSampleLowBalanceConfigs()
    {
        $merchantDetail = $this->fixtures->on('live')->edit('merchant_detail', '10000000000000', [
            'contact_name'                  => 'Test Account',
            'contact_email'                 => 'test@razorpay.com',
            'contact_mobile'                => '9876543210',
            'business_name'                 => 'PB_Test',
            'business_registered_address'   => 'Flat no 12, opp Adugodi Police Station',
        ]);

        // direct account 1 with gateway_balance more updated
        $balance1 = $this->fixtures->on('live')->create('balance', [
            'id'             => 'xbalance000003',
            'account_number' => '2224440041626908',
            'account_type'   => 'direct',
            'type'           => 'banking',
            'merchant_id'    => '10000000000000',
            'channel'        => 'rbl',
            'balance'        => 16000,
            'updated_at'     => 1565944927
        ]);

        $bankingAccount1 = $this->fixtures->on('live')->create('banking_account', [
            'id'                      => 'xba00000000003',
            'account_number'          => '2224440041626908',
            'status'                  => 'activated',
            'merchant_id'             => '10000000000000',
            'gateway_balance'         => 2500,
            'balance_last_fetched_at' => 1592556993,
            'account_type'            => 'current',
        ]);

        $lowBalanceConfig1 = $this->fixtures->on('live')->create('low_balance_config', [
            'id'                  => 'F4QO8iZ9Valbb2',
            'balance_id'          => 'xbalance000003',
            'threshold_amount'    => '2501',
            'notification_emails' => 'rtz@razorpay.com,xyz@razorpay.com',
            'notify_after'        => '21600', // 6 hrs
            'status'              => 'enabled',
            'created_at'          => 1592556993
        ]);

        // direct account 2 with statement fetch more updated
        $balance2 = $this->fixtures->on('live')->create('balance', [
            'id'             => 'xbalance000002',
            'account_number' => '2224440041626907',
            'account_type'   => 'direct',
            'type'           => 'banking',
            'merchant_id'    => '10000000000000',
            'channel'        => 'rbl',
            'balance'        => 1600,
            'updated_at'     => 1592556993
        ]);

        $bankingAccount2 = $this->fixtures->on('live')->create('banking_account', [
            'id'                      => 'xba00000000002',
            'account_number'          => '2224440041626907',
            'status'                  => 'activated',
            'merchant_id'             => '10000000000000',
            'gateway_balance'         => 2500,
            'balance_last_fetched_at' => 1565944927,
            'account_type'            => 'current',
        ]);

        $lowBalanceConfig2 = $this->fixtures->on('live')->create('low_balance_config', [
            'id'                  => 'F4QO8iZ9Valbb1',
            'balance_id'          => 'xbalance000002',
            'threshold_amount'    => '2400',
            'notification_emails' => 'rtz1@razorpay.com,xyz1@razorpay.com',
            'notify_after'        => '32400', // 9 hrs
            'status'              => 'enabled',
            'created_at'          => 1592556992
        ]);

        // first shared account with disabled config
        $balance3 = $this->fixtures->on('live')->create('balance', [
            'id'             => 'xbalance000001',
            'account_number' => '2224440041626906',
            'account_type'   => 'shared',
            'type'           => 'banking',
            'merchant_id'    => '10000000000000',
            'channel'        => 'icici',
            'balance'        => 1600,
            'updated_at'     => 1592556993
        ]);

        $bankingAccount3 = $this->fixtures->on('live')->create('banking_account', [
            'id'             => 'xba00000000001',
            'account_number' => '2224440041626906',
            'status'         => 'activated',
            'merchant_id'    => '10000000000000',
            'account_type'   => 'shared',
        ]);

        $lowBalanceConfig3 = $this->fixtures->on('live')->create('low_balance_config', [
            'id'                  => 'F4QO8iZ9Valbbz',
            'balance_id'          => 'xbalance000001',
            'threshold_amount'    => '100',
            'notification_emails' => 'rtz+shared2@razorpay.com,xyz+shared2@razorpay.com',
            'notify_after'        => '32400', // 9 hrs
            'status'              => 'disabled',
            'created_at'          => 1592556992
        ]);

        // second shared account with enabled config
        $balance4 = $this->fixtures->on('live')->create('balance', [
            'id'             => 'xbalance000004',
            'account_number' => '2224440041626909',
            'account_type'   => 'shared',
            'type'           => 'banking',
            'merchant_id'    => '10000000000000',
            'channel'        => 'icici',
            'balance'        => 1500,
            'updated_at'     => 1592556993
        ]);

        $bankingAccount4 = $this->fixtures->on('live')->create('banking_account', [
            'id'             => 'xba00000000004',
            'account_number' => '2224440041626909',
            'status'         => 'activated',
            'merchant_id'    => '10000000000000',
            'account_type'   => 'shared',
        ]);

        $lowBalanceConfig4 = $this->fixtures->on('live')->create('low_balance_config', [
            'id'                  => 'F4QO8iZ9Valbbu',
            'balance_id'          => 'xbalance000004',
            'threshold_amount'    => '1800',
            'notification_emails' => 'rtz+shared1@razorpay.com,xyz+shared1@razorpay.com',
            'notify_after'        => '32400', // 9 hrs
            'status'              => 'enabled',
            'created_at'          => 1592556992
        ]);

        // third shared account
        $lowBalanceConfig5 = $this->fixtures->on('live')->create('low_balance_config', [
            'id'                  => 'F0wNzLiuKgNuPF',
            'balance_id'          => $this->bankingBalance->getId(),
            'threshold_amount'    => '15000',
            'notification_emails' => 'rtz+shared@razorpay.com,xyz+shared@razorpay.com',
            'notify_after'        => '18000', // 5 hrs
            'status'              => 'enabled',
            'created_at'          => 1591796314
        ]);

        $merchantDetail->save();

        $balance2->save();
        $bankingAccount2->balance()->associate($balance2);
        $lowBalanceConfig2->balance()->associate($balance2);
        $bankingAccount2->save();
        $lowBalanceConfig2->save();

        $balance1->save();
        $bankingAccount1->balance()->associate($balance1);
        $lowBalanceConfig1->balance()->associate($balance1);
        $bankingAccount1->save();
        $lowBalanceConfig1->save();

        $balance3->save();
        $bankingAccount3->balance()->associate($balance3);
        $lowBalanceConfig3->balance()->associate($balance3);
        $bankingAccount3->save();
        $lowBalanceConfig3->save();

        $balance4->save();
        $bankingAccount4->balance()->associate($balance4);
        $lowBalanceConfig4->balance()->associate($balance4);
        $bankingAccount4->save();
        $lowBalanceConfig4->save();

        $lowBalanceConfig5->balance()->associate($this->bankingBalance);
        $lowBalanceConfig5->save();

        return [$lowBalanceConfig1, $lowBalanceConfig2, $lowBalanceConfig3, $lowBalanceConfig4, $lowBalanceConfig5];
    }

    protected function setLimitViaRedisKeyForFetchingConfigs($limit)
    {
        (new Admin\Service)->setConfigKeys(
            [
                Admin\ConfigKey::LOW_BALANCE_CONFIGS_FETCH_LIMIT_IN_ONE_BATCH => $limit
            ]);
    }

    protected function processLowBalanceAlertsForMerchants()
    {
        $this->ba->cronAuth('live');

        $request = [
            'url'     => '/low_balance_configs/alert',
            'method'  => 'POST',
            'content' => [],
        ];

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    // creates 5 configs
    // direct account 1 with gateway_balance more updated (balance < threshold if used gateway balance)
    // direct account 2 with statement fetch more updated (balance < threshold if used stmt fetched balance)
    // shared account with disabled config (wont be picked by cron)
    // 2 shared account with config enabled (one has balance < threshold and other has > threshold)
    // mail wont be sent for config5 (because balance > threshold) and config 3 (because status = disabled)
    public function testLowBalanceConfigsAlerts()
    {
        Mail::fake();

        $this->setLimitViaRedisKeyForFetchingConfigs(2);

        list($lowBalanceConfig1,
            $lowBalanceConfig2,
            $lowBalanceConfig3,
            $lowBalanceConfig4,
            $lowBalanceConfig5) = $this->createSampleLowBalanceConfigs();

        $observedResponse = $this->processLowBalanceAlertsForMerchants();

        // mail wont be sent for config5 (because balance > threshold) and config 3 (because status = disabled)
        Mail::assertQueued(LowBalanceAlert::class, 3);

        $lowBalanceConfig1 = $this->getDbEntityById('low_balance_config', $lowBalanceConfig1->getId(), 'live');
        $lowBalanceConfig2 = $this->getDbEntityById('low_balance_config', $lowBalanceConfig2->getId(), 'live');
        $lowBalanceConfig3 = $this->getDbEntityById('low_balance_config', $lowBalanceConfig3->getId(), 'live');
        $lowBalanceConfig4 = $this->getDbEntityById('low_balance_config', $lowBalanceConfig4->getId(), 'live');
        $lowBalanceConfig5 = $this->getDbEntityById('low_balance_config', $lowBalanceConfig5->getId(), 'live');

        $expectedResponse =
            [
                'batch_0' => [
                    $lowBalanceConfig1->getId(),
                    $lowBalanceConfig4->getId(),
                ],
                'batch_1' => [
                    $lowBalanceConfig2->getId(),
                    $lowBalanceConfig5->getId(),
                ]
            ];

        $this->assertArraySelectiveEquals($expectedResponse, $observedResponse);

        // email wasn't sent for lowBalanceConfig5
        $this->assertEquals(0, $lowBalanceConfig5->getNotifyAt());
        $this->assertNotEquals(0, $lowBalanceConfig1->getNotifyAt());
        $this->assertNotEquals(0, $lowBalanceConfig4->getNotifyAt());
        $this->assertNotEquals(0, $lowBalanceConfig2->getNotifyAt());

        return [$lowBalanceConfig1, $lowBalanceConfig2, $lowBalanceConfig3, $lowBalanceConfig4, $lowBalanceConfig5];
    }

    // firstly cron is run and email is sent for 3 configs with balance lower than threshold.
    // in the subsequent cron run , email should not be sent if time is within notify_after hrs.
    public function testLowBalanceConfigsAlertsInNextRunOfCron()
    {
        $this->testLowBalanceConfigsAlerts();

        Mail::fake();

        // run cron again
        $this->processLowBalanceAlertsForMerchants();

        Mail::assertQueued(LowBalanceAlert::class, 0);
    }

    // firstly cron is run and email is sent for 3 configs with balance lower than threshold.
    // before the subsequent cron run , the balance was added and it becomes more than threshold
    // so notify_at will become zero for this config. for the rest email wont be sent because
    // notify_at > current_time or balance > threshold
    public function testLowBalanceConfigsAlertsInNextRunWhenBalanceIsAdded()
    {
        list($lowBalanceConfig1,
            $lowBalanceConfig2,
            $lowBalanceConfig3,
            $lowBalanceConfig4,
            $lowBalanceConfig5) = $this->testLowBalanceConfigsAlerts();

        Mail::fake();

        $this->fixtures->edit('balance', $lowBalanceConfig4->getBalanceId(), ['balance' => 2000 ]);

        // run cron again
        $this->processLowBalanceAlertsForMerchants();

        $lowBalanceConfig4 = $this->getDbEntityById('low_balance_config', $lowBalanceConfig4->getId(), 'live');

        Mail::assertQueued(LowBalanceAlert::class, 0);

        $this->assertEquals(0, $lowBalanceConfig4->getNotifyAt());
    }

    // firstly cron is run and email is sent for 3 configs with balance lower than threshold.
    // before the subsequent cron run , the balance was added and it becomes more than threshold
    // so notify_at will become zero for this config. for the rest email wont be sent because
    // notify_at > current_time or balance > threshold.
    // in this test another cron is run after previous 2 runs and email will be sent for balanceid in which
    // balance was added earlier since some amount is spent and its again below threshold
    //
    public function testLowBalanceConfigsAlertsInThirdRun()
    {
        $this->testLowBalanceConfigsAlertsInNextRunWhenBalanceIsAdded();

        Mail::fake();

        $this->fixtures->edit('balance', 'xbalance000004', ['balance' => 1200 ]);

        // run cron again
        $this->processLowBalanceAlertsForMerchants();

        $lowBalanceConfig4 = $this->getDbEntityById('low_balance_config', 'F4QO8iZ9Valbbu', 'live');

        Mail::assertQueued(LowBalanceAlert::class, 1);

        $this->assertNotEquals(0, $lowBalanceConfig4->getNotifyAt());
    }

}
