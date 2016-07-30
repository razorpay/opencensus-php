<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class FreeCreditsTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/FreeCreditsData.php';
        parent::setUp();
        $merchantId = '1000000000000';
        $this->merchant = $this->fixtures->create('merchant', ['id' => $merchantId]);

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
        $free_credits_log = $this->fixtures->create('free_credits', ['id' => '123', 'credits' => 140]);
        $this->startTest();
        $free_credit_log = $this->getEntityById('free_credits', '123', true);
        $this->assertEquals($free_credit_log['credits'], 260);
    }

    public function testDeductFreeCredits()
    {
        // ID 123 is given in the data so it should match
        $free_credits_log = $this->fixtures->create('free_credits', ['id' => '123', 'credits' => 140]);
        $this->startTest();
        $free_credit_log = $this->getEntityById('free_credits', '123', true);
        $this->assertEquals($free_credit_log['credits'], 90);
    }
}
