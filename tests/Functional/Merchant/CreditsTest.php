<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Models\Merchant;

class CreditsTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/CreditsData.php';
        parent::setUp();

        $merchantId = '10000000000000';
        $merchant = (new Merchant\Repository)->findOrFailPublic($merchantId);
        $balance = (new Merchant\Balance\Repository)->editMerchantFreeCredits($merchant, 150);

        // All API calls to Credits have to be through admin account.
        $this->ba->appAuth();
    }

    public function testCreateCreditsLog()
    {
        $this->startTest();
    }


    public function testCreditsLogAlreadyExists()
    {
        $this->fixtures->create('credits');
        $this->startTest();
    }

    public function testGetCreditsLog()
    {
        $creditsLog = $this->fixtures->create('credits');

        $url = $this->testData[__FUNCTION__]['request']['url'];
        $url = $url.$creditsLog->getId();
        $this->testData[__FUNCTION__]['request']['url'] = $url;
        $this->testData[__FUNCTION__]['response']['content']['id'] = 'credits_'.$creditsLog->getId();

        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testPositiveUpdateCredits()
    {
        // ID 123 is given in the data so it should match
        $opCredits = $this->testData[__FUNCTION__]['request']['content']['value'];
        $creditsLog = $this->fixtures->create(
            'credits',
            ['id' => '123', 'value' => 150]);

        $merchant = $creditsLog->merchant;
        $balance = (new Merchant\Balance\Repository)->editMerchantFreeCredits($merchant, 150);
        $oldBalanceCredits = $balance->getCredits();
        $creditsDifference = $opCredits - $creditsLog->getValue();

        $this->startTest();

        // Assert If CreditsLog is updated
        $creditsLog = $this->getEntityById('credits', '123', true);
        $this->assertEquals($opCredits, $creditsLog['value']);
        // Assert If Merchant Balance is updated
        $balance = (new Merchant\Balance\Repository)->getMerchantBalance($merchant);
        $this->assertEquals($balance->getCredits(), $oldBalanceCredits + $creditsDifference);
    }

    public function testNegativeUpdateCredits()
    {
        // ID 123 is given in the data so it should match
        $opCredits = $this->testData[__FUNCTION__]['request']['content']['value'];
        $creditsLog = $this->fixtures->create(
            'credits',
            ['id' => '123', 'value' => 150]);
        $merchant = $creditsLog->merchant;
        $balance = (new Merchant\Balance\Repository)->editMerchantFreeCredits($merchant, 150);
        $oldBalanceCredits = $balance->getCredits();
        $creditsDifference = $opCredits - $creditsLog->getValue();

        $this->startTest();

        $creditsLog = $this->getEntityById('credits', '123', true);
        $this->assertEquals($creditsLog['value'], $opCredits);
        $balance = (new Merchant\Balance\Repository)->getMerchantBalance($merchant);
        $this->assertEquals($balance->getCredits(), $oldBalanceCredits + $creditsDifference);
    }

    public function testFailNegativeUpdateCredits()
    {
        // ID 123 is given in the data so it should match
        $creditsLog = $this->fixtures->create(
            'credits', ['id' => '123', 'value' => 150]);
        $merchant = $creditsLog->merchant;
        $balance = (new Merchant\Balance\Repository)->editMerchantFreeCredits($merchant, 10);
        $this->startTest();
    }

    public function testFailDeductCreditsCampaign()
    {
        // id 123 is given in the data so it should match
        $creditslog = $this->fixtures->create(
            'credits', ['id' => '123', 'value' => 90]);
        $this->startTest();
    }

    public function testCreditsGrantedInCampaign()
    {
        $this->fixtures->create(
            'credits',
            ['id' => '123', 'value' => 90, 'campaign' => 'noisy-ads']);
        $this->fixtures->create('credits', ['id' => '124', 'value' => 90]);
        $this->fixtures->create('credits', ['id' => '125', 'value' => 90]);

        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testCreditsGrantedToMerchant()
    {

        $this->fixtures->create('merchant', ['id' => '10000']);
        $this->fixtures->create('credits', ['id' => '123', 'value' => 90, 'merchant_id'=>'10000']);
        $this->fixtures->create('credits', ['id' => '125', 'value' => 90]);

        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testDeleteCreditsLog()
    {
        $creditsLog = $this->fixtures->create('credits');
        $url = $this->testData[__FUNCTION__]['request']['url'];
        $this->testData[__FUNCTION__]['request']['url'] = $url.$creditsLog->getId();

        $this->startTest();
    }
}
