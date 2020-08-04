<?php

namespace Functional\Merchant;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Pricing\Fee;
use RZP\Tests\Functional\TestCase;
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

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        // Merchant needs to be activated to make live requests
        $this->fixtures->on('live')->merchant->edit('10000000000000', ['activated' => 1]);

        // Create merchant user mapping
        $this->fixtures->on('live')->user->createUserMerchantMapping(
            [
                'merchant_id' => '10000000000000',
                'user_id'     => User::MERCHANT_USER_ID,
                'product'     => 'primary',
                'role'        => 'owner',
            ], 'live');

        $this->fixtures->user->createUserForMerchant('10000000000000', ['id' => 'MerchantUser02'], 'Finance L3', 'live');
        $this->nonOwnerUser =  $this->getDbLastEntity('user', 'live');
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
                'notify_after'        => 6
            ],
        ];

        $observedResult = $this->makeRequestAndGetContent($request);

        $expectedResult = [
            'account_number'      => '2224440041626905',
            'threshold_amount'    => '1000',
            'notification_emails' => ['kunal.sikri@razorpay.com','abcd@razorpay.com'],
            'notify_after'        => '6',
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

        $this->ba->proxyAuth('rzp_live_10000000000000', $this->nonOwnerUser->getId());

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/low_balance_configs/'  . $lowBalanceConfig['id'];
        $this->startTest();
    }

    public function testCreateLowBalanceConfigForNonOwnerUser()
    {
        $oldDateTime = Carbon::create(2019, 07, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

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
            'notify_after'        => '9',
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
            'notify_after'        => '9',
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
}
