<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Models\Merchant;

class FreeCreditsTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/FreeCreditsData.php';
        parent::setUp();
        $merchantId = '10000000000000';
        $merchant = (new Merchant\Repository)->findOrFailPublic($merchantId);
        $balance = (new Merchant\Balance\Repository)->editMerchantFreeCredits($merchant, 150);

        // All API calls to FreeCredits have to be through admin account.
        $this->ba->appAuth();
    }

    /**
     * Tests if a free credit log can be created for a merchant
     */
    public function testAddFreeCreditsLog()
    {
        $this->startTest();
    }

    /**
     * Tests if  Free Credit log already exists
     *
     */
    public function testFreeCreditsLogAlreadyExists()
    {
        $free_credit_log = $this->fixtures->create('free_credits');
        $this->startTest();
    }

    /**
     * Test to check if we can add more free credits to already assigned
     * campaign for merchant.
     */
    public function testGrantFreeCredits()
    {
        // ID 123 is given in the data so it should match
        $assignedCredits = 150;
        $opCredits = $this->testData[__FUNCTION__]['request']['content']['credits'];
        $freeCreditsLog = $this->fixtures->create('free_credits', ['id' => '123', 'credits' => $assignedCredits]);
        $balance = (new Merchant\Balance\Repository)->getMerchantBalance($freeCreditsLog->merchant);
        $merchant = $freeCreditsLog->merchant;
        $oldBalanceCredits = $balance->credits;
        $this->startTest();

        // Assert If FreeCreditsLog is updated
        $freeCreditsLog = $this->getEntityById('free_credits', '123', true);
        $this->assertEquals($assignedCredits + $opCredits, $freeCreditsLog['credits']);
        // Assert If Merchant Balance is updated
        $balance = (new Merchant\Balance\Repository)->getMerchantBalance($merchant);
        $this->assertEquals($balance->credits, $oldBalanceCredits + $opCredits);
    }

    public function testDeductFreeCredits()
    {
        // ID 123 is given in the data so it should match
        $assignedCredits = 150;
        $opCredits = $this->testData[__FUNCTION__]['request']['content']['credits'];
        $freeCreditsLog = $this->fixtures->create('free_credits', ['id' => '123', 'credits' => $assignedCredits]);
        $balance = (new Merchant\Balance\Repository)->getMerchantBalance($freeCreditsLog->merchant);
        $merchant = $freeCreditsLog->merchant;
        $oldBalanceCredits = $balance->credits;
        $this->startTest();
        $freeCreditsLog = $this->getEntityById('free_credits', '123', true);
        $this->assertEquals($freeCreditsLog['credits'], $assignedCredits - abs($opCredits));
        $balance = (new Merchant\Balance\Repository)->getMerchantBalance($merchant);
        $this->assertEquals($balance->credits, $oldBalanceCredits - abs($opCredits));
    }

    public function testFailDeductFreeCredits()
    {
        // ID 123 is given in the data so it should match
        $freeCreditsLog = $this->fixtures->create('free_credits', ['id' => '123', 'credits' => '190']);
        $this->startTest();
    }

    public function testFailDeductFreeCreditsCampaign()
    {
        // ID 123 is given in the data so it should match
        $freeCreditsLog = $this->fixtures->create('free_credits', ['id' => '123', 'credits' => '90']);
        $this->startTest();
    }
}
