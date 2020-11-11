<?php

namespace Functional\Merchant;

use Mail;
use Carbon\Carbon;

use RZP\Models\Admin;
use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Fixtures\Entity\User;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;

class MerchantNotificationConfigTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;
    use TestsBusinessBanking;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/MerchantNotificationConfigTestData.php';

        parent::setUp();

        $this->setUpMerchantForBusinessBankingLive(true, 10000000);

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['activated' => 1]);

        $this->fixtures->on('live')->user->createUserMerchantMapping(
            [
                'merchant_id' => '10000000000000',
                'user_id'     => User::MERCHANT_USER_ID,
                'product'     => 'banking',
                'role'        => 'owner',
            ], 'live');

        $this->fixtures->user->createBankingUserForMerchant(
            '10000000000000', ['id' => 'MerchantUser02'], 'Finance L3', 'live');
    }

    public function testCreateMerchantNotificationConfig()
    {
        $this->ba->proxyAuth('rzp_live_10000000000000', User::MERCHANT_USER_ID);

        return $this->startTest();
    }

    public function testCreateMerchantNotificationConfigWhenConfigAlreadyExists()
    {
        $this->testCreateMerchantNotificationConfig();

        $this->startTest();
    }

    public function testUpdateUpperThresholdForMerchantNotificationConfig()
    {
        $merchantNotificationConfig = $this->testCreateMerchantNotificationConfig();

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/merchant_notification_configs/' . $merchantNotificationConfig['id'];
        $this->startTest();
    }

    public function testUpdateLowerThresholdForMerchantNotificationConfig()
    {
        $merchantNotificationConfig = $this->testCreateMerchantNotificationConfig();

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/merchant_notification_configs/' . $merchantNotificationConfig['id'];
        $this->startTest();
    }

    public function testCreateMerchantNotificationConfigWithWrongThresholds()
    {
        $this->ba->proxyAuth('rzp_live_10000000000000', User::MERCHANT_USER_ID);
        $this->startTest();
    }

    public function testUpdateNotificationEmailsForMerchantNotificationConfig()
    {
        $config = $this->testCreateMerchantNotificationConfig();

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/merchant_notification_configs/' . $config['id'];
        $this->startTest();
    }

    public function testUpdateNotificationMobileNumbersForMerchantNotificationConfig()
    {
        $config = $this->testCreateMerchantNotificationConfig();

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/merchant_notification_configs/' . $config['id'];
        $this->startTest();
    }

    public function testUpdateNotifyAfterForMerchantNotificationConfig()
    {
        $config = $this->testCreateMerchantNotificationConfig();

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/merchant_notification_configs/' . $config['id'];
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

    public function testDisableMerchantNotificationConfig()
    {
        $config = $this->testCreateMerchantNotificationConfig();

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/merchant_notification_configs/' . $config['id'] . '/disable';

        $this->startTest();
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

    public function testGetMerchantNotificationConfigById()
    {
        $config = $this->testCreateMerchantNotificationConfig();

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/merchant_notification_configs/' . $config['id'];
        $this->startTest();
    }

    public function testFetchMultipleMerchantNotificationConfigs()
    {
        $this->testCreateMerchantNotificationConfig();

        $this->fixtures->on('live')->create('merchant_notification_config', [
            'id'                          => 'Fz2IHRXebge3l0',
            'upper_threshold'             => '320',
            'lower_threshold'             => '32',
            'mode'                        => 'NEFT',
            'notify_after'                => '1000',
            'notification_emails'         => 'test@razorpay.com,test@gmail.com',
            'notification_mobile_numbers' => '9587612341',
        ]);

        $testData                     = &$this->testData['testFetchMultipleMerchantNotificationConfigs'];
        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }
}
