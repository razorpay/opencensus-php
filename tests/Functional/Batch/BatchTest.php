<?php

namespace RZP\Tests\Functional\Batch;

use RZP\Models\Vpa;
use RZP\Models\Payout;
use RZP\Models\BankAccount;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;

/**
 * Class: BatchTest
 * Include only one success test case per type.
 * If requires multiple set of tests per type consider adding specific class.
 */
class BatchTest extends TestCase
{
    use BatchTestTrait;
    use TestsBusinessBanking;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/BatchTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();
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

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->startTest();

        // Further assertions referring to test data.
        $contacts = $this->getDbEntities('contact');
        $this->assertCount(3, $contacts);

        $bankAccounts = $this->getDbEntities('bank_account');
        // 2 + 2 (Existing)
        $this->assertCount(2 + 2, $bankAccounts);
        $this->assertCount(1, $bankAccounts->where(BankAccount\Entity::ACCOUNT_NUMBER, '1234567890'));
        $this->assertCount(1, $bankAccounts->where(BankAccount\Entity::ACCOUNT_NUMBER, '1234567891'));

        $vpas = $this->getDbEntities('vpa');
        // 2 + 1 (Existing)
        $this->assertCount(2 + 1, $vpas);
        $this->assertCount(2, $vpas->where(Vpa\Entity::ADDRESS, 'jitendrakkkk@upi'));
    }

    public function testCreateBatchOfPayoutType()
    {
        $this->setUpMerchantForBusinessBanking(false, 5000);

        $this->createContact();

        $this->fixtures
             ->fund_account
             ->createBankAccount(
                [
                    'id'          => '000000000test1',
                    'source_id'   => '1000010contact',
                    'source_type' => 'contact',
                ]);

        $entries = $this->getFileEntries(__FUNCTION__);
        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->startTest();

        $payouts = $this->getDbEntities('payout');
        $this->assertCount(2, $payouts);
        $this->assertEquals(1100, $payouts->sum(Payout\Entity::AMOUNT));
        $this->assertEquals('1234567890', $payouts->first()->fundAccount->account->getAccountNumber());
        $this->assertEquals('Jitendra', $payouts->first()->fundAccount->contact->getName());
        $this->assertEquals('fa_000000000test1', $payouts->last()->fundAccount->getPublicId());
        $this->assertEquals('test user', $payouts->last()->fundAccount->contact->getName());
    }

    protected function getFileEntries(string $callee): array
    {
        return $this->testData["{$callee}RequestFileEntries"];
    }
}
