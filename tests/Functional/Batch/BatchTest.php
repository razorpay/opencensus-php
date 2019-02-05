<?php

namespace RZP\Tests\Functional\Batch;

use RZP\Models\Vpa;
use RZP\Models\BankAccount;
use RZP\Tests\Functional\TestCase;

/**
 * Class: BatchTest
 * Include only one success test case per type.
 * If requires multiple set of tests per type consider adding specific class.
 */
class BatchTest extends TestCase
{
    use BatchTestTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/BatchTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();
    }

    public function testCreateBatchOfContactType()
    {
        // Creates a in-active contact to assert that it is not used in the flow.
        $this->fixtures->create(
            'contact',
            [
                'id'           => '100TestContact',
                'active'       => false,
                'type'         => 'vendor',
                'name'         => 'Another Example',
                'email'        => 'another@example.com',
                'contact'      => '9988998899',
                'reference_id' => null,
            ]);

        $entries = $this->getFileEntries(__FUNCTION__);

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();

        $contacts = $this->getDbEntities('contact');

        $this->assertCount(3 + 1, $contacts);
        $this->assertCount(2, $contacts->where('name', 'Another Example'));
    }

    public function testCreateBatchOfFundAccountType()
    {
        $this->fixtures->create(
            'contact',
            [
                'id'      => '00000000000001',
                'type'    => 'vendor',
                'name'    => 'Jitendra',
                'email'   => 'jitendra@example.com',
                'contact' => '9988998899',
            ]);

        $entries = $this->getFileEntries(__FUNCTION__);

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();

        // Further assertions referring to test data.
        $contacts = $this->getDbEntities('contact');
        $this->assertCount(3, $contacts);

        $bankAccounts = $this->getDbEntities('bank_account');
        // 3 + 2 (Existing)
        $this->assertCount(3 + 2, $bankAccounts);
        $this->assertCount(2, $bankAccounts->where(BankAccount\Entity::ACCOUNT_NUMBER, '1234567890'));
        $this->assertCount(1, $bankAccounts->where(BankAccount\Entity::ACCOUNT_NUMBER, '1234567891'));

        $vpas = $this->getDbEntities('vpa');
        // 2 + 1 (Existing)
        $this->assertCount(2 + 1, $vpas);
        $this->assertCount(2, $vpas->where(Vpa\Entity::ADDRESS, 'jitendrakkkk@upi'));
    }

    protected function getFileEntries(string $callee): array
    {
        return $this->testData["{$callee}RequestFileEntries"];
    }
}
