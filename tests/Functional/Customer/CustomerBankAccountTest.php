<?php

namespace RZP\Tests\Functional\Customer;

use Carbon\Carbon;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

use Mockery;

class CustomerBankAccountTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/CustomerBankAccountTestData.php';

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

        (new Carbon('Asia/Kolkata'))->setTimestamp(time()+1);

        $this->testAddCustomerBankAccount();

        $accounts = $this->testGetCustomerBankAccounts();

        $this->assertEquals(count($accounts['items']), 2);
    }
}