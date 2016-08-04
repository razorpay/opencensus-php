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

    /**
     * Tests if a  credit log can be created for a merchant
     */
    public function testCreateCreditsLog()
    {
        $this->startTest();
    }

    /**
     * Tests if Credit log already exists
     *
     */
    public function testCreditsLogAlreadyExists()
    {
        $this->fixtures->create('credits');
        $this->startTest();
    }

    /**
     * Test to check if we can add more credits to already assigned
     * campaign for merchant.
     */
    public function testGrantCredits()
    {
        // ID 123 is given in the data so it should match
        $assignedCredits = 150;
        $opCredits = $this->testData[__FUNCTION__]['request']['content']['value'];
        $creditsLog = $this->fixtures->create('credits', ['id' => '123', 'value' => $assignedCredits]);
        $merchant = $creditsLog->merchant;
        $balance = (new Merchant\Balance\Repository)->getMerchantBalance($merchant);
        $oldBalanceCredits = $balance->getCredits();
        $this->startTest();

        // Assert If CreditsLog is updated
        $creditsLog = $this->getEntityById('credits', '123', true);
        $this->assertEquals($assignedCredits + $opCredits, $creditsLog['value']);
        // Assert If Merchant Balance is updated
        $balance = (new Merchant\Balance\Repository)->getMerchantBalance($merchant);
        $this->assertEquals($balance->getCredits(), $oldBalanceCredits + $opCredits);
    }

    public function testDeductCredits()
    {
        // ID 123 is given in the data so it should match
        $assignedCredits = 150;
        $opCredits = $this->testData[__FUNCTION__]['request']['content']['value'];
        $creditsLog = $this->fixtures->create('credits', ['id' => '123', 'value' => $assignedCredits]);
        $balance = (new Merchant\Balance\Repository)->getMerchantBalance($creditsLog->merchant);
        $merchant = $creditsLog->merchant;
        $oldBalanceCredits = $balance->getCredits();
        $this->startTest();

        $creditsLog = $this->getEntityById('credits', '123', true);
        $this->assertEquals($creditsLog['value'], $assignedCredits - abs($opCredits));
        $balance = (new Merchant\Balance\Repository)->getMerchantBalance($merchant);
        $this->assertEquals($balance->getCredits(), $oldBalanceCredits - abs($opCredits));
    }

    public function testFailDeductCredits()
    {
        // ID 123 is given in the data so it should match
        $creditsLog = $this->fixtures->create('credits', ['id' => '123', 'value' => 190]);
        $this->startTest();
    }

    public function testFailDeductCreditsCampaign()
    {
        // id 123 is given in the data so it should match
        $creditslog = $this->fixtures->create('credits', ['id' => '123', 'value' => 90]);
        $this->startTest();
    }

    public function testCreditsGrantedInCampaign()
    {
        $this->fixtures->create('credits', ['id' => '123', 'value' => 90, 'campaign' => 'noisy-ads']);
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
}
