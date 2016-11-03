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

        $this->checkTerminals();

        $this->checkBalances();

        $this->checkNetbankingBanks();

        $this->checkMethods();
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
        $this->fixtures->merchant->addFeature('aggregator');

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCreateSubMerchantWithEmail()
    {
        $this->fixtures->merchant->addFeature('aggregator');

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCreateSubMerchantWithDuplicateEmail()
    {
        // Just to check email collisions are still errors
        $this->fixtures->create('merchant', ['id' => '10000000000002', 'email' => 'test2@razorpay.com']);

        $this->fixtures->merchant->addFeature('aggregator');

        $this->ba->proxyAuth();

        $this->startTest();
    }

    protected function startTest($testDataToReplace = [])
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        return $this->runRequestResponseFlow($testData);
    }
}
