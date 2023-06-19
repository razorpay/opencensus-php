<?php

namespace RZP\Tests\Functional\Customer;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Exception\BadRequestException;

use Mockery;

class CustomerBankAccountTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/CustomerBankAccountTestData.php';

        parent::setUp();
    }

    public function testAddCustomerBankAccount()
    {
        $this->ba->privateAuth();

        $data = $this->startTest();

        return $data;
    }

    public function testGetCustomerBankAccounts()
    {
        $this->ba->privateAuth();

        return $this->startTest();
    }

    public function testAddCustomerBankAccountDuplicate()
    {
        $this->testAddCustomerBankAccount();

        (new Carbon(Timezone::IST))->setTimestamp(time()+1);

        $this->testAddCustomerBankAccount();

        $accounts = $this->testGetCustomerBankAccounts();

        $this->assertEquals($accounts['count'], 2);
    }

    public function testSoftDeleteCustomerBankAccount()
    {
        $this->ba->privateAuth();

        $bank_account = $this->testAddCustomerBankAccount();

        $bank_id = $bank_account['id'];
        $cust_id = 'cust_100000customer';

        $this->testData[__FUNCTION__] = $this->testData['testSoftDeleteCustomerBankAccount'];

        $dataToReplace = [
            'request'  => [
                'url' => '/customers/'. $cust_id . '/bank_account/' . $bank_id,
            ]
        ];

        $data = $this->startTest($dataToReplace);

    }

    public function testSoftDeleteCustomerBankAccountDeletingItTwice()
    {
        $this->ba->privateAuth();

        $bank_account = $this->testAddCustomerBankAccount();

        $bank_id = $bank_account['id'];
        $cust_id = 'cust_100000customer';

        $this->testData[__FUNCTION__] = $this->testData['testSoftDeleteCustomerBankAccount'];

        $dataToReplace = [
            'request'  => [
                'url' => '/customers/'. $cust_id . '/bank_account/' . $bank_id,
            ]
        ];

        $data = $this->startTest($dataToReplace);

        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage("Bank account is already deleted");

        $data = $this->startTest($dataToReplace);

    }
}
