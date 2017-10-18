<?php

namespace RZP\Tests\Functional\Merchant;

use Mail;

use RZP\Http\BasicAuth\BasicAuth;
use RZP\Mail\Merchant\CreateSubMerchant as CreateSubMerchantMail;
use RZP\Tests\Functional\Fixtures\Entity\Org;
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
        $this->ba->adminAuth('test', null, 'org_' . Org::RZP_ORG);

        $testData = $this->testData['testGetTerminalsInTestForCreatedMerchant'];

        $content = $this->runRequestResponseFlow($testData);

        $this->ba->adminAuth('live', null, 'org_' . Org::RZP_ORG);

        $testData = $this->testData['testGetTerminalsInLiveForCreatedMerchant'];

        $content = $this->runRequestResponseFlow($testData);
    }

    protected function checkBalances()
    {
        $this->ba->proxyAuth('rzp_test_1X4hRFHFx4UiXt');

        $this->runRequestResponseFlow($this->testData['testBalanceInTestAfterCreatedMerchant']);

        $this->ba->proxyAuth('rzp_live_1X4hRFHFx4UiXt');

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

        $expectedMethods = [
            'amex'     => false,
            'mobikwik' => true,
            'paytm'    => false
        ];

        $this->assertArraySelectiveEquals($expectedMethods, $methods);
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
        Mail::fake();

        $this->fixtures->merchant->addFeatures(['aggregator']);

        $user = $this->fixtures->create('user');

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => '10000000000000',
            'role'        => 'owner',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->ba->proxyAuth();

        $this->testData[__FUNCTION__]['request']['content']['user_id'] = $user['id'];

        $this->startTest();

        Mail::assertSent(CreateSubMerchantMail::class, function ($mail)
        {
            return $mail->hasTo('test@razorpay.com', 'Submerchant');
        });
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

    public function testCreateLinkedAccountMaxPaymentLimit()
    {
        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->fixtures->merchant->edit('10000000000000', ['max_payment_amount' => 6000]);

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
