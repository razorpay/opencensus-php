<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class MerchantCreateTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantCreateTestData.php';

        parent::setUp();

        $this->ba->appAuth();
    }

    public function testCreateMerchantWithDuplicateEmail()
    {
        $this->ba->appAuthTest();

        $this->startTest();
    }

    public function testCreateMerchantWithDuplicateId()
    {
        $this->ba->appAuthTest();

        $this->startTest();
    }

    public function testCreateMerchantAndRelations()
    {
        $this->ba->appAuthTest();

        $this->merchantId = '1X4hRFHFx4UiXt';

        $content = $this->createMerchant();

        $this->assertSame($content['activated'], false);

        $this->checkSettlementSchedule($content);

        $this->checkTerminals();

        $this->checkBalances();

        $this->checkNetbankingBanks();

        $this->checkMethods();

        $this->checkMerchantDetails();
    }

    protected function createMerchant()
    {
        $testData = $this->testData['testCreateMerchant'];

        return $this->runRequestResponseFlow($testData);
    }

    protected function checkTerminals()
    {
        $this->ba->appAuthTest();

        $testData = $this->testData['testGetTerminalsInTestForCreatedMerchant'];

        $content = $this->runRequestResponseFlow($testData);

        $this->ba->appAuthLive();

        $testData = $this->testData['testGetTerminalsInLiveForCreatedMerchant'];

        $content = $this->runRequestResponseFlow($testData);
    }

    protected function checkBalances()
    {
        $this->ba->proxyAuth();

        $this->runRequestResponseFlow($this->testData['testBalanceInTestAfterCreatedMerchant']);

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->runRequestResponseFlow($this->testData['testBalanceInLiveAfterCreatedMerchant']);
    }

    protected function checkNetbankingBanks()
    {
        $this->checkNetbankingBanksInMode('test');

        $this->checkNetbankingBanksInMode('live');
    }

    protected function checkMethods()
    {
        $this->ba->appAuthTest();

        $methods = $this->getEntityById('methods', '1X4hRFHFx4UiXt', true);

        $this->assertEquals($methods['mobikwik'], true);
        $this->assertEquals($methods['paytm'], false);
    }

    protected function checkMerchantDetails()
    {
        $this->ba->appAuthTest();

        $merchantDetails = $this->getEntityById('merchant_detail', '1X4hRFHFx4UiXt', true);

        $this->assertEquals($merchantDetails['contact_email'], 'test@localhost.com');
    }

    protected function checkSettlementSchedule($merchant)
    {
        $this->ba->appAuthTest();

        $scheduleTask = $this->getLastEntity('schedule_task', true);
        $schedule = $this->getEntityById('schedule', $scheduleTask['schedule_id'], true);

        $this->assertEquals($merchant['id'], $scheduleTask['merchant_id']);
        $this->assertEquals($schedule['id'], $merchant['settlement_schedule_id']);
        $this->assertEquals($schedule['merchant_id'], '100000Razorpay');
        $this->assertEquals($schedule['period'], 'daily');
        $this->assertEquals($schedule['delay'], 3);
    }

    protected function checkNetbankingBanksInMode($mode)
    {
        $func = 'appAuth'.ucfirst($mode);
        $this->ba->$func();

        $testData = $this->testData['testGetBankAccountsAfterCreatedMerchant'];

        $content = $this->runRequestResponseFlow($testData);

        $this->assertSame(array(), $content['disabled']);
    }

    public function testCreateSubMerchant()
    {
        $this->fixtures->merchant->addFeatures(['aggregator']);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCreateSubMerchantWithEmail()
    {
        $this->fixtures->merchant->addFeatures(['aggregator']);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCreateSubMerchantWithDuplicateEmail()
    {
        // Just to check email collisions are still errors
        $this->fixtures->create('merchant', ['id' => '10000000000002', 'email' => 'test2@razorpay.com']);

        $this->fixtures->merchant->addFeatures(['aggregator']);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCreateMarketplaceLinkedAccount()
    {
        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testLinkedAccountDefaultSchedule()
    {
        $this->fixtures->create('merchant',
                                [
                                    'id' => '10000000000002',
                                    'email' => 'test2@razorpay.com'
                                ]);

        // Define T+2 cycle for new merchant
        $schedule = [
            'interval'          => 1,
            'delay'             => 2,
            'hour'              => 0
        ];

        $this->fixtures->create('merchant:schedule_task',
                                [
                                    'merchant_id' => '10000000000002',
                                    'schedule'    => $schedule
                                ]);

        $this->fixtures->merchant->addFeatures(['marketplace'], '10000000000002');

        $this->ba->proxyAuth('rzp_test_10000000000002');

        $linkedAcc = $this->startTest();

        $this->ba->appAuthTest();

        // Check schedule entries for new linked account
        $scheduleTask = $this->getLastEntity('schedule_task', true);
        $schedule = $this->getEntityById('schedule', $scheduleTask['schedule_id'], true);

        $this->assertEquals($linkedAcc['id'], $scheduleTask['merchant_id']);
        $this->assertEquals($schedule['id'], $linkedAcc['settlement_schedule_id']);
        $this->assertEquals($schedule['delay'], 2);
    }

    protected function startTest($testDataToReplace = [])
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        return $this->runRequestResponseFlow($testData);
    }
}
