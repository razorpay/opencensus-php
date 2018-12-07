<?php

namespace RZP\Tests\Functional\Transaction;

use RZP\Models\Feature;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\VirtualAccount\VirtualAccountTrait;

class StatementTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;
    use VirtualAccountTrait;

    /**
     * This is a instance member as is being re-used between method call.
     * @var \RZP\Models\VirtualAccount\Entity
     */
    protected $virtualAccount;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/StatementTestData.php';

        parent::setUp();

        $this->setUpMerchantForBusinessBanking();
    }

    public function testFetchMultipleStatementsOnBankingBalance()
    {
        // Creates two bank transfer transaction on banking balance.
        $this->createBankTransferTransaction();
        $this->createBankTransferTransaction();

        // Todo: Creates some payout transaction on banking balance.

        // Creates one normal payment transaction on primary balance.
        $this->doAuthAndCapturePayment(null, 50000);

        // One the first two transactions should appear in response.
        $this->ba->proxyAuth();
        $response = $this->startTest();

        // Asserts other keys existence in items.
        $statement = current($response['items']);
        $this->assertNotEmpty($statement['id']);
        $this->assertNotEmpty($statement['created_at']);
        $this->assertNotEmpty($statement['updated_at']);
        $this->assertNotEmpty($statement['source']['id']);
        $this->assertNotEmpty($statement['source']['bank_reference']);
    }

    public function testFetchMultipleStatementsOnPrimaryBalance()
    {
        // Creates one bank transfer transaction on banking balance.
        $this->createBankTransferTransaction();

        // Creates one normal payment transaction on primary balance.
        $this->doAuthAndCapturePayment(null, 50000);

        // Only the second transaction should appear in response.
        $this->ba->proxyAuth();
        $response = $this->startTest();

        // Asserts other keys existence in items.
        $statement = current($response['items']);
        $this->assertNotEmpty($statement['id']);
        $this->assertNotEmpty($statement['created_at']);
        $this->assertNotEmpty($statement['updated_at']);
        $this->assertNotEmpty($statement['source']['id']);
        $this->assertEquals('payment', $statement['source']['entity']);
    }

    public function testFetchStatementOnBankingBalance()
    {
        $this->createBankTransferTransaction();

        $transaction = $this->getDbLastEntity('transaction');
        $this->testData[__FUNCTION__]['request']['url'] = '/transactions/' . $transaction->getPublicId();

        $this->ba->proxyAuth();
        $response = $this->startTest();

        // Asserts other keys existence in response.
        $this->assertNotEmpty($response['id']);
        $this->assertNotEmpty($response['created_at']);
        $this->assertNotEmpty($response['updated_at']);
        $this->assertNotEmpty($response['source']['id']);
        $this->assertNotEmpty($response['source']['bank_reference']);
    }

    public function testFetchIncorrectStatementOnBankingBalance()
    {
        $this->doAuthAndCapturePayment(null, 50000);

        $transaction = $this->getDbLastEntity('transaction');
        $this->testData[__FUNCTION__]['request']['url'] = '/transactions/' . $transaction->getPublicId();

        // Above transaction if accessed on banking balance should throw 400.
        $this->ba->proxyAuth();
        $this->startTest();
    }

    /**
     * Todo: Duplicated in VirtualAccountTest, move to a common trait.
     */
    protected function setUpMerchantForBusinessBanking()
    {
        // Creates banking balance, a virtual account on bank account receiver and associates these all together. :)
        $bankingBalance = $this->fixtures->merchant->createBalanceOfBankingType();
        $virtualAccount = $this->fixtures->create('virtual_account');
        $bankAccount    = $this->fixtures->create(
            'bank_account',
            [
                'type'           => 'virtual_account',
                'entity_id'      => $virtualAccount->getId(),
                'account_number' => '2224440041626905',
                'ifsc_code'      => 'RAZRB000000',
            ]);
        $virtualAccount->bankAccount()->associate($bankAccount);
        $virtualAccount->balance()->associate($bankingBalance);
        $virtualAccount->save();

        // Enables required features on merchant
        $this->fixtures->merchant->addFeatures(['virtual_accounts']);

        // Additionally, creates a terminal for bank transfer on banking balance.
        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $this->virtualAccount = $virtualAccount;
    }

    protected function createBankTransferTransaction()
    {
        $this->payVirtualAccount($this->virtualAccount->getPublicId(), ['amount' => 25]);
    }
}
