<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Merchant;

class AmountCreditsTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/CreditsData.php';

        parent::setUp();

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

        $this->testData[__FUNCTION__]['request']['url'] .= $creditsLog->getId();
        $this->testData[__FUNCTION__]['response']['content']['id'] = $creditsLog->getPublicId();

        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testPositiveUpdateCredits()
    {
        $creditsLog = $this->addAmountCredits(['value' => 150, 'campaign' => 'silent-ads']);
        $id = $creditsLog['id'];

        $this->testData[__FUNCTION__]['request']['url'] .= $id;

        $this->startTest();

        $creditsLog = $this->getEntityById('credits', $id, true);
        $this->assertEquals($creditsLog['value'], 190);

        $balance = $this->fetchBalance();
        $this->assertEquals($balance['credits'], 190);
    }

    public function testNegativeUpdateCredits()
    {
        $creditsLog = $this->addAmountCredits(['value' => 150, 'campaign' => 'silent-ads']);
        $id = $creditsLog['id'];

        $this->testData[__FUNCTION__]['request']['url'] .= $id;

        $this->startTest();

        $creditsLog = $this->getEntityById('credits', $id, true);
        $this->assertEquals($creditsLog['value'], 100);

        $balance = $this->fetchBalance();
        $this->assertEquals($balance['credits'], 100);
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
        $creditsLog = $this->addAmountCredits(['value' => 150, 'campaign' => 'silent-ads']);

        $creditsLog = $this->fixtures->create('credits');

        $this->testData[__FUNCTION__]['request']['url'] .= $creditsLog->getId();

        $this->startTest();
    }
}
