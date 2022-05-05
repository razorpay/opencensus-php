<?php

namespace Functional\Merchant;

use Mail;
use Carbon\Carbon;

use RZP\Models\Admin;
use RZP\Constants\Mode;
use RZP\Models\Merchant\MerchantNotificationConfig\NotificationType;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Tests\Functional\Fixtures\Entity\User;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Models\Merchant\MerchantNotificationConfig\Entity;

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
        $merchantNotificationConfigFetchLimit = (new Admin\Service)->getConfigKey(
            ['key' => Admin\ConfigKey::MERCHANT_NOTIFICATION_CONFIG_FETCH_LIMIT]
        );

        $merchantNotificationConfigFetchLimit[NotificationType::BENE_BANK_DOWNTIME]    = $limit;
        $merchantNotificationConfigFetchLimit[NotificationType::FUND_LOADING_DOWNTIME] = $limit;
        $merchantNotificationConfigFetchLimit[NotificationType::PARTNER_BANK_HEALTH]   = $limit;

        (new Admin\Service)->setConfigKeys(
            [
                Admin\ConfigKey::MERCHANT_NOTIFICATION_CONFIG_FETCH_LIMIT => $merchantNotificationConfigFetchLimit,
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

    public function testCreateMerchantNotificationConfigWithNotificationTypeAsAdmin()
    {
        $this->ba->adminAuth();

        $this->startTest();

        $content = &$this->testData[__FUNCTION__]['request']['content'];

        $this->assertArraySelectiveEquals(
            [
                Entity::MERCHANT_ID                 => '10000000000000',
                Entity::NOTIFICATION_EMAILS         => implode(',', $content[Entity::NOTIFICATION_EMAILS]),
                Entity::NOTIFICATION_MOBILE_NUMBERS => implode(',', $content[Entity::NOTIFICATION_MOBILE_NUMBERS]),
                Entity::NOTIFICATION_TYPE           => $content[Entity::NOTIFICATION_TYPE]
            ],
            $this->getDbLastEntity('merchant_notification_config')->toArray()
        );
    }

    public function testCreateMerchantNotificationConfigWithNotificationTypeAndWithoutMobileNumbersAsAdmin()
    {
        $this->ba->adminAuth();

        $this->startTest();

        $content = &$this->testData[__FUNCTION__]['request']['content'];

        $expectedConfigEntity = [
            Entity::MERCHANT_ID                 => '10000000000000',
            Entity::NOTIFICATION_EMAILS         => implode(',', $content[Entity::NOTIFICATION_EMAILS]),
            Entity::NOTIFICATION_MOBILE_NUMBERS => '',
            Entity::NOTIFICATION_TYPE           => $content[Entity::NOTIFICATION_TYPE]
        ];

        $this->assertArraySelectiveEquals(
            $expectedConfigEntity,
            $this->getDbLastEntity('merchant_notification_config')->toArray()
        );
    }

    public function testCreateMerchantNotificationConfigWithoutMobileAndEmail()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testCreateDuplicateMerchantNotificationConfigWithNotificationTypeAsAdmin()
    {
        $this->testCreateMerchantNotificationConfigWithNotificationTypeAsAdmin();

        $this->startTest();
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

    public function testUpdateMerchantNotificationConfigWithNoEmailIdsAsAdmin()
    {
        $config = $this->testCreateMerchantNotificationConfigAsAdmin();

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/admin/merchants/10000000000000/merchant_notification_configs/' . $config['id'];
        $this->startTest();

        $content = &$this->testData[__FUNCTION__]['request']['content'];

        $expectedConfigEntity = [
            Entity::MERCHANT_ID                 => '10000000000000',
            Entity::NOTIFICATION_EMAILS         => '',
            Entity::NOTIFICATION_MOBILE_NUMBERS => implode(',', $content[Entity::NOTIFICATION_MOBILE_NUMBERS]),
        ];

        $this->assertArraySelectiveEquals(
            $expectedConfigEntity,
            $this->getDbLastEntity('merchant_notification_config')->toArray()
        );
    }

    public function testUpdateNotificationMobileNumbersForMerchantNotificationConfigAsAdminWithIncorrectMobileNumber()
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

        $testData                   = &$this->testData['testEnableMerchantNotificationConfigAsAdmin'];
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
            'notification_type'           => 'fund_loading_downtime',
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
            'notification_type'           => 'fund_loading_downtime',
            'notification_emails'         => 'test1@razorpay.com,test11@gmail.com',
            'notification_mobile_numbers' => '9587612341,814582777',
            'created_at'                  => $time->subHour()->timestamp,
        ]);

        $this->fixtures->on('test')->create('merchant_notification_config', [
            'id'                          => 'Fz2IHRXebge3l1',
            'notification_type'           => 'fund_loading_downtime',
            'notification_emails'         => 'test2@razorpay.com,test22@gmail.com',
            'notification_mobile_numbers' => '9587612341,6363200000',
            'created_at'                  => $time->subHour()->timestamp,
        ]);

        $this->startTest();
    }

    public function testFetchMultipleNotificationConfigsWithQueryParamsAsAdmin()
    {
        $time = Carbon::now();

        Carbon::setTestNow($time);

        $this->testCreateMerchantNotificationConfigAsAdmin();

        $this->fixtures->on('test')->create('merchant_notification_config', [
            'id'                          => 'abcdefghiljkmn',
            'notification_type'           => 'fund_loading_downtime',
            'notification_emails'         => 'test1@razorpay.com,test11@gmail.com',
            'notification_mobile_numbers' => '9587612341,814582777',
            'created_at'                  => $time->subHour()->timestamp,
        ]);

        $this->fixtures->on('test')->create('merchant_notification_config', [
            'id'                          => 'bcdefghiljkmno',
            'notification_type'           => 'fund_loading_downtime',
            'notification_emails'         => 'test2@razorpay.com,test22@gmail.com',
            'notification_mobile_numbers' => '9587612341,814582777',
            'created_at'                  => $time->subHour()->timestamp,
            'config_status'               => 'disabled',
        ]);

        $testData = &$this->testData[__FUNCTION__];

        // add query params in url
        $testData['request']['url'] .= '?notification_type=fund_loading_downtime&config_status=enabled';

        $this->startTest();
    }

    public function testProcessDowntimeEventForWebhook()
    {
        $this->markTestSkipped('Old payload no longer in use');

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
            'url'     => '/fts/channel/notify',
            'method'  => 'post',
            'content' => [
                'type'    => 'bene_health',
                'payload' => [
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
            ],
        ];

        $this->makeRequestAndGetContent($request);
    }
}
