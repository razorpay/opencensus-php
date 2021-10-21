<?php

namespace Functional\Merchant;

use Mail;
use Carbon\Carbon;

use RZP\Models\Admin;
use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use RZP\Mail\Base\Mailable;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\BadRequestException;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Mail\Payout\DowntimeNotification;
use RZP\Tests\Functional\Fixtures\Entity\User;
use RZP\Tests\Functional\Fixtures\Entity\Payout;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Exception\BadRequestValidationFailureException;

class MerchantNotificationConfigTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;
    use TestsBusinessBanking;
    use TestsWebhookEvents;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/MerchantNotificationConfigTestData.php';

        parent::setUp();

        $this->mockRazorxTreatment();

        $this->setUpMerchantForBusinessBanking(true, 10000000);
        $this->setUpMerchantForBusinessBankingLive(true, 10000000);

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['activated' => 1]);

        $this->fixtures->user->createBankingUserForMerchant(
            '10000000000000', ['id' => 'MerchantUser02'], 'Finance L3', 'live');
    }

    protected function setLimitViaRedisKeyForFetchingConfigs($limit)
    {
        (new Admin\Service)->setConfigKeys(
            [
                Admin\ConfigKey::MERCHANT_NOTIFICATION_CONFIG_FETCH_LIMIT => $limit,
            ]);
    }

    public function testCreateMerchantNotificationConfig()
    {
        $this->ba->proxyAuth('rzp_live_10000000000000', User::MERCHANT_USER_ID);

        return $this->startTest();
    }

    public function testCreateMerchantNotificationConfigAsAdmin()
    {
        $this->ba->adminAuth();
        return $this->startTest();
    }

    public function testCreateMerchantNotificationConfigWhenConfigAlreadyExists()
    {
        $this->testCreateMerchantNotificationConfig();

        $this->startTest();
    }

    public function testCreateMerchantNotificationConfigAsAdminWhenConfigAlreadyExists()
    {
        $this->testCreateMerchantNotificationConfigAsAdmin();

        $this->startTest();
    }

    public function testUpdateUpperThresholdForMerchantNotificationConfig()
    {
        $merchantNotificationConfig = $this->testCreateMerchantNotificationConfig();

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/merchant_notification_configs/' . $merchantNotificationConfig['id'];
        $this->startTest();
    }

    public function testUpdateUpperThresholdForMerchantNotificationConfigAsAdmin()
    {
        $merchantNotificationConfig = $this->testCreateMerchantNotificationConfigAsAdmin();

        $testData = &$this->testData[__FUNCTION__];
        $testData['request']['url']
            = '/admin/merchants/10000000000000/merchant_notification_configs/' . $merchantNotificationConfig['id'];
        $this->startTest();
    }

    public function testUpdateLowerThresholdForMerchantNotificationConfig()
    {
        $merchantNotificationConfig = $this->testCreateMerchantNotificationConfig();

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/merchant_notification_configs/' . $merchantNotificationConfig['id'];
        $this->startTest();
    }

    public function testUpdateLowerThresholdForMerchantNotificationConfigAsAdmin()
    {
        $merchantNotificationConfig = $this->testCreateMerchantNotificationConfigAsAdmin();

        $testData = &$this->testData[__FUNCTION__];
        $testData['request']['url']
            = '/admin/merchants/10000000000000/merchant_notification_configs/' . $merchantNotificationConfig['id'];
        $this->startTest();
    }

    public function testCreateMerchantNotificationConfigWithWrongThresholds()
    {
        $this->ba->proxyAuth('rzp_live_10000000000000', User::MERCHANT_USER_ID);
        $this->startTest();
    }

    public function testCreateMerchantNotificationConfigAsAdminWithWrongThresholds()
    {
        $this->ba->adminAuth();
        $this->startTest();
    }

    public function testUpdateNotificationEmailsForMerchantNotificationConfig()
    {
        $config = $this->testCreateMerchantNotificationConfig();

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/merchant_notification_configs/' . $config['id'];
        $this->startTest();
    }

    public function testUpdateNotificationEmailsForMerchantNotificationConfigAsAdmin()
    {
        $config = $this->testCreateMerchantNotificationConfigAsAdmin();

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/admin/merchants/10000000000000/merchant_notification_configs/' . $config['id'];
        $this->startTest();
    }

    public function testUpdateNotificationMobileNumbersForMerchantNotificationConfig()
    {
        $config = $this->testCreateMerchantNotificationConfig();

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/merchant_notification_configs/' . $config['id'];
        $this->startTest();
    }

    public function testUpdateNotificationMobileNumbersForMerchantNotificationConfigAsAdmin()
    {
        $config = $this->testCreateMerchantNotificationConfigAsAdmin();

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/admin/merchants/10000000000000/merchant_notification_configs/' . $config['id'];
        $this->startTest();
    }

    public function testUpdateNotificationMobileNumbersForMerchantNotificationConfigAsAdminWithIncorrectMobileNumber()
    {
        $config = $this->testCreateMerchantNotificationConfigAsAdmin();

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/admin/merchants/10000000000000/merchant_notification_configs/' . $config['id'];

        $this->startTest();
    }

    public function testUpdateNotifyAfterForMerchantNotificationConfig()
    {
        $config = $this->testCreateMerchantNotificationConfig();

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/merchant_notification_configs/' . $config['id'];
        $this->startTest();
    }

    public function testUpdateNotifyAfterForMerchantNotificationConfigAsAdmin()
    {
        $config = $this->testCreateMerchantNotificationConfigAsAdmin();

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/admin/merchants/10000000000000/merchant_notification_configs/' . $config['id'];
        $this->startTest();
    }

    public function testDeleteMerchantNotificationConfig()
    {
        $config = $this->testCreateMerchantNotificationConfig();

        $countBeforeDeleting = count($this->getDbEntities('merchant_notification_config', [], 'live'));

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/merchant_notification_configs/' . $config['id'];
        $this->startTest();

        $countAfterDeletion = count($this->getDbEntities('merchant_notification_config', [], 'live'));

        $this->assertEquals(1, $countBeforeDeleting - $countAfterDeletion);
    }

    public function testDeleteMerchantNotificationConfigAsAdmin()
    {
        $config = $this->testCreateMerchantNotificationConfigAsAdmin();

        $countBeforeDeleting = count($this->getDbEntities('merchant_notification_config', [], 'test'));

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/admin/merchants/10000000000000/merchant_notification_configs/' . $config['id'];
        $this->startTest();

        $countAfterDeletion = count($this->getDbEntities('merchant_notification_config', [], 'test'));

        $this->assertEquals(1, $countBeforeDeleting - $countAfterDeletion);
    }

    public function testDisableMerchantNotificationConfig()
    {
        $config = $this->testCreateMerchantNotificationConfig();

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/merchant_notification_configs/' . $config['id'] . '/disable';

        $this->startTest();
    }

    public function testDisableMerchantNotificationConfigWhenAlreadyDisabled()
    {
        $config = $this->testCreateMerchantNotificationConfig();

        $testData                   = &$this->testData['testDisableMerchantNotificationConfig'];
        $testData['request']['url'] = '/merchant_notification_configs/' . $config['id'] . '/disable';

        $this->makeRequestAndGetContent($testData['request']);

        // Repeat making request to test disabling a disabled config
        $observedResponse = $this->makeRequestAndGetContent($testData['request']);

        $this->assertEquals('disabled', $observedResponse['config_status'],
                            'Error in testDisableMerchantNotificationConfigWhenAlreadyDisabled');
    }

    public function testDisableMerchantNotificationConfigAsAdmin()
    {
        $config = $this->testCreateMerchantNotificationConfigasAdmin();

        $testData = &$this->testData[__FUNCTION__];
        $testData['request']['url']
            = '/admin/merchants/10000000000000/merchant_notification_configs/' . $config['id'] . '/disable';

        $this->startTest();
    }

    public function testDisableMerchantNotificationConfigAsAdminWhenAlreadyDisabled()
    {
        $config = $this->testCreateMerchantNotificationConfigasAdmin();

        $testData = &$this->testData['testDisableMerchantNotificationConfigAsAdmin'];
        $testData['request']['url']
                  = '/admin/merchants/10000000000000/merchant_notification_configs/' . $config['id'] . '/disable';

        $this->makeRequestAndGetContent($testData['request']);

        // Repeat making request to test disabling a disabled config
        $observedResponse = $this->makeRequestAndGetContent($testData['request']);

        $this->assertEquals('disabled', $observedResponse['config_status'],
                            'Error in testDisableMerchantNotificationConfigWhenAlreadyDisabled');
    }

    public function testEnableMerchantNotificationConfig()
    {
        $config = $this->testCreateMerchantNotificationConfig();

        $this->fixtures->edit('merchant_notification_config', $config['id'], ['config_status' => 'disabled']);
        $config = $this->getDbLastEntityToArray('merchant_notification_config', 'live');

        $this->assertSame('disabled', $config['config_status']);

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/merchant_notification_configs/' . 'mnc_' . $config['id'] . '/enable';

        $this->startTest();
    }

    public function testEnableMerchantNotificationConfigWhenAlreadyEnabled()
    {
        // Not sending the enable request twice because a newly created config is already enabled

        $config = $this->testCreateMerchantNotificationConfig();

        $testData                   = &$this->testData['testEnableMerchantNotificationConfig'];
        $testData['request']['url'] = '/merchant_notification_configs/' . $config['id'] . '/enable';

        $observedResponse = $this->makeRequestAndGetContent($testData['request']);

        $this->assertEquals('enabled', $observedResponse['config_status'],
                            'Error in testEnableMerchantNotificationConfigWhenAlreadyEnabled');
    }

    public function testEnableMerchantNotificationConfigAsAdmin()
    {
        $config = $this->testCreateMerchantNotificationConfigAsAdmin();

        $this->fixtures->edit('merchant_notification_config', $config['id'], ['config_status' => 'disabled']);
        $config = $this->getDbLastEntityToArray('merchant_notification_config', 'test');

        $this->assertSame('disabled', $config['config_status']);

        $testData = &$this->testData[__FUNCTION__];
        $testData['request']['url']
            = '/admin/merchants/10000000000000/merchant_notification_configs/' . 'mnc_' . $config['id'] . '/enable';

        $this->startTest();
    }

    public function testEnableMerchantNotificationConfigAsAdminWhenAlreadyEnabled()
    {
        // Not sending the enable request twice because a newly created config is already enabled

        $config = $this->testCreateMerchantNotificationConfigAsAdmin();

        $testData = &$this->testData['testEnableMerchantNotificationConfigAsAdmin'];
        $testData['request']['url'] =
            '/admin/merchants/10000000000000/merchant_notification_configs/' . $config['id'] . '/enable';

        $observedResponse = $this->makeRequestAndGetContent($testData['request']);

        $this->assertEquals('enabled', $observedResponse['config_status'],
                            'Error in testEnableMerchantNotificationConfigAsAdminWhenAlreadyEnabled');
    }

    public function testGetMerchantNotificationConfigById()
    {
        $config = $this->testCreateMerchantNotificationConfig();

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/merchant_notification_configs/' . $config['id'];
        $this->startTest();
    }

    public function testGetMerchantNotificationConfigAsAdminById()
    {
        $config = $this->testCreateMerchantNotificationConfigAsAdmin();

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/admin/merchants/10000000000000/merchant_notification_configs/' . $config['id'];
        $this->startTest();
    }

    public function testFetchMultipleMerchantNotificationConfigs()
    {
        $time = Carbon::now();

        Carbon::setTestNow($time);

        $this->testCreateMerchantNotificationConfig();

        $this->fixtures->on('live')->create('merchant_notification_config', [
            'id'                          => 'Fz2IHRXebge3l0',
            'upper_threshold'             => '320',
            'lower_threshold'             => '32',
            'mode'                        => 'NEFT',
            'notify_after'                => '1000',
            'notification_emails'         => 'test@razorpay.com,test@gmail.com',
            'notification_mobile_numbers' => '9587612341',
            'created_at'                  => $time->subHour()->timestamp,
        ]);

        $this->startTest();
    }

    public function testFetchMultipleMerchantNotificationConfigsAsAdmin()
    {
        $time = Carbon::now();

        Carbon::setTestNow($time);

        $this->testCreateMerchantNotificationConfigAsAdmin();

        $this->fixtures->on('test')->create('merchant_notification_config', [
            'id'                          => 'Fz2IHRXebge3l0',
            'upper_threshold'             => '320',
            'lower_threshold'             => '32',
            'mode'                        => 'NEFT',
            'notify_after'                => '1000',
            'notification_emails'         => 'test@razorpay.com,test@gmail.com',
            'notification_mobile_numbers' => '9587612341',
            'created_at'                  => $time->subHour()->timestamp,
        ]);

        $this->startTest();
    }

    public function testProcessDowntimeEventForWebhook()
    {
        Mail::fake();

        $this->testCreateMerchantNotificationConfig();

        $this->expectWebhookEvent('payout.downtime.started');

        $this->ba->ftsAuth(Mode::LIVE);

        $request = [
            'url'     => '/fts/channel/notify',
            'method'  => 'post',
            'content' => [
                'contains' => ['bene_health'],
                'entity'   => 'event',
                'event'    => 'bene_health.started',
                'payload'  => [
                    'bene_health' => [
                        'entity' => [
                            'begin'      => 1610430729,
                            'created_at' => 1610430729,
                            'end'        => 0,
                            'entity'     => 'bene_health',
                            'id'         => 'GOHp6DSA5odXTu',
                            'instrument' => [
                                'bank' => 'UTIB'
                            ],
                            'method'     => ['IMPS'],
                            'scheduled'  => false,
                            'source'     => 'BENEFICIARY',
                            'status'     => 'started',
                            'updated_at' => 1610430729
                        ]
                    ]
                ]
            ],
        ];

        $this->makeRequestAndGetContent($request);
    }


    public function testProcessDowntimeEventForWebhookWithNewPayload()
    {
        Mail::fake();

        $this->testCreateMerchantNotificationConfig();

        $this->expectWebhookEvent('payout.downtime.started');

        $this->ba->ftsAuth(Mode::LIVE);

        $request = [
            'url' => '/fts/channel/notify',
            'method' => 'post',
            'content' => [
                'type' => 'bene_health',
                'payload' => [
                    'begin' => 1610430729,
                    'created_at' => 1610430729,
                    'end' => 0,
                    'entity' => 'bene_health',
                    'id' => 'GOHp6DSA5odXTu',
                    'instrument' => [
                        'bank' => 'UTIB'
                    ],
                    'method' => ['IMPS'],
                    'scheduled' => false,
                    'source' => 'BENEFICIARY',
                    'status' => 'started',
                    'updated_at' => 1610430729
                ]
            ],
        ];

        $this->makeRequestAndGetContent($request);
    }

    // This test is used to check the stuck payouts alert functionality
    public function testStuckPayoutsAlert()
    {
        Mail::fake();

        $testDate = Carbon::create(2021, 01, 01, 12, null, null);

        Carbon::setTestNow($testDate);

        // Create a test config
        $this->testCreateMerchantNotificationConfigAsAdmin();

        // Fetch the created config for using later in this code
        $config = $this->getDbLastEntity('merchant_notification_config');

        $this->ba->cronAuth();

        // Create a number of payouts, so as to trigger stuck payouts alert
        $this->createLotsOfPayoutsStuckInInitiatedState($config->getUpperThreshold());

        // Hit the alert route
        $request = [
            'url'    => '/merchant_notification_configs/alert',
            'method' => 'POST',
        ];

        $this->makeRequestAndGetContent($request);

        // This flow creates mails in sync mode
        // We chose sync mode because we don't expect a lot of mails to be generated right now.
        // This was decided as a part of a focus group task to quickly build an alerting solution for stuck payouts.
        Mail::assertSent(DowntimeNotification::class);

        // Fetch the updated config to check if notifyAt has become negative
        $newConfig = $this->getDbLastEntity('merchant_notification_config');

        // Assert that the new notify_at value is negative
        // i.e. an email has been sent
        $this->assertTrue(($newConfig->getNotifyAt()) < 0);

        // Assert that the value of notify_at is correct.
        $this->assertEquals(
            -1 * (Carbon::now()->timestamp + $config->getNotifyAfter()), $newConfig->getNotifyAt());

        // Change the value of notifyAt to be below currentTime so as to re-trigger alerting
        $this->fixtures->edit('merchant_notification_config', $newConfig->getId(), ['notify_at' => -1]);

        $this->makeRequestAndGetContent($request);

        Mail::assertSent(DowntimeNotification::class);

        $newConfig = $this->getDbLastEntity('merchant_notification_config');

        // Assert that notifyAt is still negative, since resolution hasn't happened yet
        $this->assertTrue(($newConfig->getNotifyAt()) < 0);

        // Assert that value of notifyAt is correct
        $this->assertEquals(
            -1 * (1 + $config->getNotifyAfter()), $newConfig->getNotifyAt());

        // clear the payouts table, so as to trigger resolution mail
        $this->app['db']->statement('DELETE FROM payouts');

        $this->makeRequestAndGetContent($request);

        Mail::assertSent(DowntimeNotification::class);

        // Fetch updated config, as we expect notifyAt to get updated
        $newConfig = $this->getDbLastEntity('merchant_notification_config');

        // Check if notifyAt has flipped back to positive, asserting that resolution mail has been sent.
        $this->assertTrue(($newConfig->getNotifyAt()) > 0);
    }

    protected function createLotsOfPayoutsStuckInInitiatedState(int $upperThreshold)
    {
        // The number of stuck payouts should be (at least one) more than the upper threshold, hence loop starts from 0
        // with less than or equal to comparator
        for($x = 0; $x <= $upperThreshold; $x++)
        {
            (new Payout())
                ->createPayoutWithoutTransaction(
                    [
                        'merchant_id' => '10000000000000',
                        'mode'        => 'IMPS',
                        'status'      => 'initiated',
                        'amount'      => 100,
                        'currency'    => 'INR',
                    ]
                );
        }
    }
}
