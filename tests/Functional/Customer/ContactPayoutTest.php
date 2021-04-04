<?php

namespace RZP\Tests\Functional\Customer;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class ContactPayoutTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/ContactPayoutTestData.php';

        parent::setUp();
    }

    public function testAddCustomerBankAccount()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testGetCustomerBankAccounts()
    {
        $this->ba->privateAuth();

        return $this->startTest();
    }

    public function testAddCustomerBankAccountDuplicate()
    {
        $this->testAddCustomerBankAccount();

        (new Carbon(Timezone::IST))->setTimestamp(time() + 1);

        $this->testAddCustomerBankAccount();

        $accounts = $this->testGetCustomerBankAccounts();

        $this->assertEquals(count($accounts['items']), 2);
    }
}
