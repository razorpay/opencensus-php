<?php

namespace RZP\Tests\Functional\SubVirtualAccount;

use Hash;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class SubVirtualAccountsTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/SubVirtualAccountsTestData.php';

        parent::setUp();
    }

    public function testCreateSubVirtualAccount()
    {
        $this->ba->adminAuth();

        $this->fixtureSetUp();

        $this->startTest();
    }

    public function testCreateDuplicateSubVirtualAccount()
    {
        $this->ba->adminAuth();

        $this->testCreateSubVirtualAccount();

        $this->startTest();
    }

    public function testCreateSubVirtualAccountWhereSubAccountNumberMissingInDB()
    {
        $this->ba->adminAuth();

        $this->fixtureSetUp('2323230041626905', null);

        $this->startTest();
    }

    public function testCreateSubVirtualAccountWhereMasterAccountNumberMissingInDB()
    {
        $this->ba->adminAuth();

        $this->fixtureSetUp(null);

        $this->startTest();
    }

    public function testCreateSubVirtualAccountWithInvalidAccountType()
    {
        $this->ba->adminAuth();

        $this->fixtureSetUp('2323230041626905', '2323230041626906', 'banking', 'direct');

        $this->startTest();
    }

    public function testCreateSubVirtualAccountWithInvalidType()
    {
        $this->ba->adminAuth();

        $this->fixtureSetUp('2323230041626905', '2323230041626906', 'primary');

        $this->startTest();
    }

    protected function fixtureSetUp(
        $masterAccountNumber = '2323230041626905',
        $subAccountNumber = '2323230041626906',
        $type = 'banking',
        $accountType = 'shared')
    {
        $this->fixtures->on('test')->create('merchant', ['id' => '100abc000abc00', 'email' => 'mahbubani.amit@gmail.com']);

        $this->fixtures->on('test')->create('merchant', ['id' => '100abc000abc01', 'email' => 'mahbubani.amit@gmail.com']);

        $this->fixtures->on('test')->create('balance',
            [
                'type'           => $type,
                'account_type'   => $accountType,
                'account_number' => $masterAccountNumber,
                'merchant_id'    => '100abc000abc00',
                'balance'        => 30000
            ]);

        $this->fixtures->on('test')->create('balance',
            [
                'type'           => $type,
                'account_type'   => $accountType,
                'account_number' => $subAccountNumber,
                'merchant_id'    => '100abc000abc01',
                'balance'        => 0
            ]);
    }
}
