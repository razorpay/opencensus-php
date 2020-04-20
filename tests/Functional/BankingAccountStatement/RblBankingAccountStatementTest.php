<?php

namespace RZP\Tests\Functional\BankingAccountStatement;

use Mail;
use Queue;
use Redis;
use Mockery;
use Carbon\Carbon;

use RZP\Models\Admin;
use RZP\Models\Payout;
use RZP\Services\Mozart;
use RZP\Constants\Timezone;
use RZP\Models\FundTransfer;
use RZP\Models\BankingAccount;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Merchant\Balance;
use RZP\Constants\Mode as EnvMode;
use RZP\Tests\Functional\TestCase;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\BankingAccount\Channel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RZP\Models\BankingAccount\Gateway\Rbl;
use RZP\Mail\BankingAccount\StatementMail;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\BankingAccount\Entity as BaEntity;
use RZP\Jobs\FTS\FundTransfer as FtsFundTransfer;
use RZP\Models\External\Entity as ExternalEntity;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\Payout\PayoutTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Models\Transaction\Entity as TransactionEntity;
use RZP\Models\BankingAccountStatement\Entity as BasEntity;
use RZP\Jobs\BankingAccountStatement as BankingAccountStatementJob;

class RblBankingAccountStatementTest extends TestCase
{
    use PayoutTrait;
    use AttemptTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;

    const UFH_FILE_PATH_REGEX    = '/.*\/ufh\/file\/(.*)/';

    const MOCK_UFH_BASE_LOCATION = 'files/filestore';

    const FILE_ID                = 'file_id';

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/RblBankingAccountStatementTestData.php';

        parent::setUp();

        $this->fixtures->create('contact', ['id' => '1000001contact', 'active' => 1]);

        $this->fixtures->create(
            'fund_account',
            [
                'id'           => '100000000000fa',
                'source_id'    => '1000001contact',
                'source_type'  => 'contact',
                'account_type' => 'bank_account',
                'account_id'   => '1000000lcustba'
            ]);


        $this->ba->privateAuth();

        $this->setUpMerchantForBusinessBanking(
            false,
            10000,
            AccountType::DIRECT,
            Channel::RBL);

        $this->balance = $this->getDbEntity('balance', ['merchant_id' => '10000000000000', 'type' => 'banking']);

        $balanceId = $this->balance->getId();

        $this->fixtures->create('banking_account', [
            'id'                    => 'xba00000000001',
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => '10000000000000',
            'channel'               => 'rbl',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
            'balance_id'            => $balanceId,
            'status'                => 'activated'
        ]);

        $this->balance = $this->getDbEntity('balance', ['merchant_id' => '10000000000000', 'type' => 'banking']);
    }

    protected function mockMozartResponseForFetchingBalanceFromRblGateway(int $amount): void
    {
        $mozartServiceMock = $this->getMockBuilder(\RZP\Services\Mock\Mozart::class)
                                  ->setConstructorArgs([$this->app])
                                  ->setMethods(['sendMozartRequest'])
                                  ->getMock();

        $mozartServiceMock->method('sendMozartRequest')
                          ->willReturn([
                                           'data' => [
                                               'success' => true,
                                               Rbl\Fields::GET_ACCOUNT_BALANCE => [
                                                   Rbl\Fields::BODY => [
                                                       Rbl\Fields::BAL_AMOUNT => [
                                                           Rbl\Fields::AMOUNT_VALUE => $amount
                                                       ]
                                                   ]
                                               ]
                                           ]
                                       ]);

        $this->app->instance('mozart', $mozartServiceMock);
    }

    public function testRblXlsxStatementGeneration()
    {
        $this->addTestTransactions();

        $currentTime = time();

        $response = $this->startTest(['request' => ['content' => ['to_date' => $currentTime]]]);

        $this->assertArrayHasKey(self::FILE_ID, $response);

        $this->verifyGeneratedXlsxFile($currentTime);

    }

    public function testRblXlsxStatementEmailSent()
    {
        Mail::fake();

        $this->addTestTransactions();

        $currentTime = time();

        $response = $this->startTest(['request' => ['content' => ['to_date' => $currentTime]]]);

        Mail::assertQueued(StatementMail::class);
    }

    protected function setupForRblAccountStatement($channel = Channel::RBL)
    {
        $this->ba->cronAuth();

        $request = [
            'url'       => '/banking_account_statement/process/rbl',
            'method'    => 'POST'
        ];

        $this->app['cache']->flush();

        (new AdminService)->setConfigKeys([ConfigKey::BANKING_ACCOUNT_STATEMENT_RATE_LIMIT => 1]);

        Queue::fake();

        $this->makeRequestAndGetContent($request);

        Queue::assertPushed(BankingAccountStatementJob::class, 1);
    }

    /**
     * Case where the response from RBL is success
     */
    public function testRblAccountStatementCase1()
    {
        $mockedResponse = $this->getRblDataResponse();

        $this->setMozartMockResponse($mockedResponse);

        $baBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT, true);

        $this->assertNull($baBeforeTest[BaEntity::LAST_STATEMENT_ATTEMPT_AT]);

        $this->ba->cronAuth();

        $this->setupForRblAccountStatement();

        $this->startTest();

        $transactions = $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'];

        $txn = last($transactions);

        $basActual = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT, true);

        $externalActual = $this->getLastEntity(EntityConstants::EXTERNAL, true);

        $externalId = str_after($externalActual[ExternalEntity::ID], 'ext_');

        $externalTxnId = $externalActual[ExternalEntity::TRANSACTION_ID];

        $this->txnEntity = $this->getDbEntityById(EntityConstants::TRANSACTION, $externalTxnId);

        $txnActual = $this->txnEntity->toArray();

        $baAfterTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT, true);

        $this->assertNotNull($baAfterTest[BaEntity::LAST_STATEMENT_ATTEMPT_AT]);

        $this->assertEquals($txnActual[TransactionEntity::POSTED_AT], $basActual[BasEntity::POSTED_DATE]);

        $basExpected = [
            BasEntity::MERCHANT_ID           => $txnActual[TransactionEntity::MERCHANT_ID],
            BasEntity::BANK_TRANSACTION_ID   => trim($txn['txnId']),
            BasEntity::TYPE                  => 'debit',
            BasEntity::AMOUNT                => 10095,
            BasEntity::BALANCE               => 11355,
            BasEntity::POSTED_DATE           => 1451937993,
            BasEntity::TRANSACTION_DATE      => 1451932200,
            BasEntity::DESCRIPTION           => trim($txn['transactionSummary']['txnDesc']),
            BasEntity::CHANNEL               => 'rbl',
            BasEntity::ENTITY_ID             => $externalId,
            BasEntity::ENTITY_TYPE           => $externalActual[ExternalEntity::ENTITY],
            BasEntity::TRANSACTION_ID        => $txnActual[TransactionEntity::ID],
        ];

        $this->assertArraySubset($basExpected, $basActual, true);

        $externalExpected = [
            BasEntity::MERCHANT_ID                => $basActual[BasEntity::MERCHANT_ID],
            ExternalEntity::BALANCE_ID            => $this->balance->getId(),
            ExternalEntity::BANK_REFERENCE_NUMBER => $basActual[BasEntity::BANK_TRANSACTION_ID],
            ExternalEntity::TYPE                  => $basActual[BasEntity::TYPE],
            ExternalEntity::AMOUNT                => $basActual[BasEntity::AMOUNT],
            ExternalEntity::CHANNEL               => $basActual[BasEntity::CHANNEL],
            ExternalEntity::TRANSACTION_ID        => $txnActual[TransactionEntity::ID],
        ];

        $this->assertArraySubset($externalExpected, $externalActual, true);

        $txnExpected = [
            TransactionEntity::ID               => $externalTxnId,
            TransactionEntity::ENTITY_ID        => $externalId,
            TransactionEntity::TYPE             => 'external',
            TransactionEntity::DEBIT            => $externalActual[ExternalEntity::AMOUNT],
            TransactionEntity::CREDIT           => 0,
            TransactionEntity::AMOUNT           => $externalActual[ExternalEntity::AMOUNT],
            TransactionEntity::FEE              => 0,
            TransactionEntity::TAX              => 0,
            TransactionEntity::PRICING_RULE_ID  => null,
            TransactionEntity::ON_HOLD          => false,
            TransactionEntity::SETTLED          => false,
            TransactionEntity::SETTLED_AT       => null,
            TransactionEntity::SETTLEMENT_ID    => null,
        ];

        $this->assertArraySubset($txnExpected, $txnActual, true);
    }

    /**
     * Case where the no more data is received from RBL
     **/
    public function testRblAccountStatementCase2()
    {
        $mockedResponse = $this->getRblNoDataResponse();

        $this->setMozartMockResponse($mockedResponse);

        $basBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT, true);

        $baBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT, true);

        $this->assertNull($baBeforeTest[BaEntity::LAST_STATEMENT_ATTEMPT_AT]);

        $this->ba->cronAuth();

        $this->startTest();

        $basAfterTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT, true);

        $this->assertEquals($basBeforeTest[BasEntity::ID], $basAfterTest[BasEntity::ID]);

        $baAfterTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT, true);

        $this->assertNotNull($baAfterTest[BaEntity::LAST_STATEMENT_ATTEMPT_AT]);
    }

    /**
     * Case where request details are incorrect to RBL
     */
    public function testRblAccountStatementCase3()
    {
        $mockedResponse = $this->getRblInvalidDetailsResponse();

        $this->setMozartMockResponse($mockedResponse);

        $baBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT, true);

        $this->assertNull($baBeforeTest[BaEntity::LAST_STATEMENT_ATTEMPT_AT]);

        $this->ba->cronAuth();

        $this->startTest();

        $baAfterTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT, true);

        $this->assertNotNull($baAfterTest[BaEntity::LAST_STATEMENT_ATTEMPT_AT]);
    }

    /**
     * Case where request fails at mozart
     */
    public function testRblAccountStatementCase4()
    {
        $mockedResponse = $this->getMozartServiceFailureResponse();

        $this->setMozartMockResponse($mockedResponse);

        $this->ba->cronAuth();

        $this->startTest();
    }

    /**
     * Case where response is malformed
     */
    public function testRblAccountStatementCase5()
    {
        $mockedResponse = $this->getRblMalformedResponse();

        $this->setMozartMockResponse($mockedResponse);

        $this->ba->cronAuth();

        $this->startTest();
    }

    /**
     * Case where the balances don't match
     */
    public function testRblAccountStatementCase6()
    {
        $mockedResponse = $this->getRblIncorrectBalanceResponse();

        $this->setMozartMockResponse($mockedResponse);

        $this->ba->cronAuth();

        $this->startTest();
    }

    /**
     * Case where the balances is negative
     */
    public function testRblAccountStatementCase7()
    {
        $mockedResponse = $this->getRblNegativeBalanceResponse();

        $this->setMozartMockResponse($mockedResponse);

        $this->ba->cronAuth();

        $this->startTest();
    }

    protected function verifyGeneratedXlsxFile($currentTime)
    {
        $openingBalanceCell = 'B36';

        $closingBalanceCell = 'B37';

        $effectiveBalanceCell = 'B38';

        $expectedOpeningBalance = 214.5;

        $expectedClosingBalance = 113.55;

        $expectedEffectiveBalance = 113.55;

        $fileName = storage_path(self::MOCK_UFH_BASE_LOCATION) .
                    '/2224440041626905_946684800_' .
                    $currentTime .
                    '.xlsx';

        $spreadsheet = IOFactory::load($fileName);

        $activeSheet = $spreadsheet->getActiveSheet();

        $openingBalance = $activeSheet->getCell($openingBalanceCell)->getValue();

        $closingBalance = $activeSheet->getCell($closingBalanceCell)->getValue();

        $effectiveBalance = $activeSheet->getCell($effectiveBalanceCell)->getValue();

        $this->assertEquals($expectedOpeningBalance , $openingBalance);

        $this->assertEquals($expectedClosingBalance , $closingBalance);

        $this->assertEquals($expectedEffectiveBalance , $effectiveBalance);
    }

    /**
     * Case where status check is performed first, then account statement is fetched
     */
    public function testRblAccountStatementTxnMappingCase1()
    {
        $channel = Channel::RBL;

        $this->setupForRblPayout($channel);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '123456']);

        $ftsCreateTransfer = new FtsFundTransfer(
            EnvMode::TEST,
            $attempt['id']);

        $ftsCreateTransfer->handle();

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Attempt\Status::INITIATED, $attempt['status']);

        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::PROCESSED);

        $payout = $this->getDbLastEntity('payout');

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Payout\Status::PROCESSED, $payout['status']);
        $this->assertEquals(FundTransfer\Mode::IMPS, $payout['mode']);
        $this->assertEquals(Attempt\Status::PROCESSED, $attempt['status']);
        $this->assertEquals(FundTransfer\Mode::IMPS, $attempt['mode']);

        $mockedResponse = $this->getRblDataResponse();

        $this->setMozartMockResponse($mockedResponse);

        $this->ba->cronAuth();

        $this->startTest();

        $payout = $this->getDbLastEntity('payout');
        $external = $this->getDbLastEntity('external');
        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[0]['entity_type']);
        $this->assertEquals($external['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($external['transaction_id'], $basEntries[0]['transaction_id']);
        $this->assertEquals($external['banking_account_statement_id'], $basEntries[0]['id']);

        $this->assertEquals(EntityConstants::PAYOUT, $basEntries[1]['entity_type']);
        $this->assertEquals($payout['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($payout['transaction_id'], $basEntries[1]['transaction_id']);

        $this->assertEquals($external['balance_id'], $payout['balance_id']);

        $feeBreakup1 = $this->getDbEntities('fee_breakup', ['transaction_id' => $basEntries[0]['transaction_id']]);
        $feeBreakup2 = $this->getDbEntities('fee_breakup', ['transaction_id' => $basEntries[1]['transaction_id']]);

        $this->assertEquals(0, $feeBreakup1->count());
        $this->assertEquals(2, $feeBreakup2->count());

        $this->assertEquals('Bbg7cl6t6I3XA6', $feeBreakup2[0]['pricing_rule_id']);
        $this->assertEquals(EntityConstants::PAYOUT, $feeBreakup2[0]['name']);
        $this->assertEquals(500, $feeBreakup2[0]['amount']);
        $this->assertEquals(90, $feeBreakup2[1]['amount']);
        $this->assertEquals(EntityConstants::TAX, $feeBreakup2[1]['name']);

        $txn = $this->getDbEntity('transaction', ['entity_id' => $payout['id']])->toArray();
        $this->assertEquals($txn['fee'], $payout['fees']);
        $this->assertEquals($txn['tax'], $payout['tax']);
    }

    /**
     * Case where account statement is fetched first, status check is performed later
     */
    public function testRblAccountStatementTxnMappingCase2()
    {
        $this->markTestSkipped();

        $channel = Channel::RBL;

        $this->setupForRblPayout($channel);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '123456']);

        // Fetch account statement from RBL
        $mockedResponse = $this->getRblDataResponse();

        $this->setMozartMockResponse($mockedResponse);

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->cronAuth();
        $this->startTest();

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $externalTxn = $this->getDbLastEntity('transaction');
        $externalEntries = $this->getDbEntities('external', ['balance_id' => $payout['balance_id']]);
        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[0]['entity_type']);
        $this->assertEquals($externalEntries[0]['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($externalEntries[0]['transaction_id'], $basEntries[0]['transaction_id']);
        $this->assertEquals($externalEntries[0]['banking_account_statement_id'], $basEntries[0]['id']);

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[1]['entity_type']);
        $this->assertEquals($externalEntries[1]['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($externalEntries[1]['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals($externalEntries[1]['banking_account_statement_id'], $basEntries[1]['id']);

        $this->assertEquals(2, count($externalEntries));
        $this->assertEquals(EntityConstants::EXTERNAL, $externalTxn['type']);
        $this->assertNull($payout['transaction_id']);

        $feeBreakup1 = $this->getDbEntities('fee_breakup', ['transaction_id' => $basEntries[0]['transaction_id']]);
        $feeBreakup2 = $this->getDbEntities('fee_breakup', ['transaction_id' => $basEntries[1]['transaction_id']]);

        $this->assertEquals(0, $feeBreakup1->count());
        $this->assertEquals(0, $feeBreakup2->count());

        // Update status
        $ftsCreateTransfer = new FtsFundTransfer(
            EnvMode::TEST,
            $attempt['id']);

        $ftsCreateTransfer->handle();

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Attempt\Status::INITIATED, $attempt['status']);

        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::PROCESSED);

        $payout = $this->getDbLastEntity('payout');
        $external = $this->getDbLastEntity('external');
        $updatedTxn = $this->getDbLastEntity('transaction');
        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $updatedExternalEntries = $this->getDbEntities('external', ['balance_id' => $payout['balance_id']]);

        $this->assertEquals(2, count($updatedExternalEntries));
        $this->assertEquals($external['balance_id'], $payout['balance_id']);

        $this->assertEquals(Payout\Status::PROCESSED, $payout['status']);
        $this->assertEquals(FundTransfer\Mode::IMPS, $payout['mode']);
        $this->assertEquals($updatedTxn['id'], $payout['transaction_id']);

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[0]['entity_type']);
        $this->assertEquals($external['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($external['transaction_id'], $basEntries[0]['transaction_id']);
        $this->assertEquals($external['banking_account_statement_id'], $basEntries[0]['id']);

        $this->assertEquals(EntityConstants::PAYOUT, $basEntries[1]['entity_type']);
        $this->assertEquals($payout['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($payout['transaction_id'], $basEntries[1]['transaction_id']);

        $feeBreakup1 = $this->getDbEntities('fee_breakup', ['transaction_id' => $basEntries[0]['transaction_id']]);
        $feeBreakup2 = $this->getDbEntities('fee_breakup', ['transaction_id' => $basEntries[1]['transaction_id']]);
        $this->assertEquals(0, $feeBreakup1->count());
        $this->assertEquals(1, $feeBreakup2->count());

        $this->assertEquals('Bbg7cl6t6I3XA6', $feeBreakup2[0]['pricing_rule_id']);
        $this->assertEquals(EntityConstants::PAYOUT, $feeBreakup2[0]['name']);
        $this->assertEquals(500, $feeBreakup2[0]['amount']);
        $this->assertEquals(90, $feeBreakup2[1]['amount']);
        $this->assertEquals(EntityConstants::TAX, $feeBreakup2[1]['name']);
    }

    /**
     * Case where status check is performed first, account statement is fetched later
     * Mapping transaction using cms_ref_no
     */
    public function testRblAccountStatementTxnMappingCase3()
    {
        $channel = Channel::RBL;

        $this->setupForRblPayout($channel);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated', 'amount' => '104']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['cms_ref_no' => 'S55959']);

        $this->fixtures->edit('balance', $payout['balance_id'], ['balance' => 30019995]);

        $ftsCreateTransfer = new FtsFundTransfer(
            EnvMode::TEST,
            $attempt['id']);

        $ftsCreateTransfer->handle();

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Attempt\Status::INITIATED, $attempt['status']);

        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::PROCESSED);

        $payout = $this->getDbLastEntity('payout');

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Payout\Status::PROCESSED, $payout['status']);
        $this->assertEquals(FundTransfer\Mode::IMPS, $payout['mode']);
        $this->assertEquals(Attempt\Status::PROCESSED, $attempt['status']);
        $this->assertEquals(FundTransfer\Mode::IMPS, $attempt['mode']);
        $this->assertEquals('S55959', $attempt['cms_ref_no']);

        // Fetch account statement from RBL
        $mockedResponse = $this->getRblPayoutMappingResponse();

        $this->setMozartMockResponse($mockedResponse);

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->cronAuth();
        $this->startTest();

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $payoutTxn = $this->getDbLastEntity('transaction');
        $externalEntries = $this->getDbEntities('external', ['balance_id' => $payout['balance_id']]);
        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(EntityConstants::PAYOUT, $basEntries[0]['entity_type']);
        $this->assertEquals($payout['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($payout['transaction_id'], $basEntries[0]['transaction_id']);

        $this->assertEquals(0, count($externalEntries));
        $this->assertEquals(EntityConstants::PAYOUT, $payoutTxn['type']);
        $this->assertEquals($payoutTxn['id'], $payout['transaction_id']);

        $feeBreakup = $this->getDbEntities('fee_breakup', ['transaction_id' => $payout['transaction_id']]);

        $this->assertEquals('Bbg7cl6t6I3XA6', $feeBreakup[0]['pricing_rule_id']);
        $this->assertEquals(500, $feeBreakup[0]['amount']);
        $this->assertEquals(EntityConstants::PAYOUT, $feeBreakup[0]['name']);
        $this->assertEquals(90, $feeBreakup[1]['amount']);
        $this->assertEquals(EntityConstants::TAX, $feeBreakup[1]['name']);
    }

    public function testRblAccountStatementTxnMappingCase4()
    {
        $channel = Channel::RBL;

        $this->setupForRblPayout($channel);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated', 'amount' => '104']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['cms_ref_no' => 'S55959']);

        $this->fixtures->edit('balance', $payout['balance_id'], ['balance' => 30019995]);

        $ftsCreateTransfer = new FtsFundTransfer(
            EnvMode::TEST,
            $attempt['id']);

        $ftsCreateTransfer->handle();

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Attempt\Status::INITIATED, $attempt['status']);

        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::PROCESSED);

        $payout = $this->getDbLastEntity('payout');

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Payout\Status::PROCESSED, $payout['status']);

        // Fetch account statement from RBL
        $mockedResponse = $this->getRblPayoutMappingResponse();

        $this->setMozartMockResponse($mockedResponse);

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->cronAuth();
        $this->startTest();

        // create mapping for 2nd payout
        $content = [
            'account_number'  => '2224440041626905',
            'amount'          => 10095,
            'currency'        => 'INR',
            'purpose'         => 'payout',
            'narration'       => 'Rbl account payout',
            'fund_account_id' => 'fa_' . $this->fundAccount->getId(),
            'mode'            => 'IMPS',
            'notes'           => [
                'abc' => 'xyz',
            ],
        ];

        $request = [
            'url'       => '/payouts',
            'method'    => 'POST',
            'content'   => $content
        ];

        $this->ba->privateAuth();

        $this->makeRequestAndGetContent($request);

        $transaction = $this->fixtures->create('transaction', ['merchant_id'       => '10000000000000']);

        $this->fixtures->create('banking_account_statement',
            [
                'type'                      => 'debit',
                'amount'                    => '104',
                'channel'                   => 'rbl',
                'account_number'            => 2224440041626905,
                'transaction_id'            => $transaction['id'],
                'entity_id'                 => $payout['id'],
                'entity_type'               =>  'payout',
                'bank_transaction_id'       => 'SDHDH',
                'balance'                   => 30019891,
                'transaction_date'          => 1584987183
            ]);
        // creating third payout in failed status

        $content = [
            'account_number'  => '2224440041626905',
            'amount'          => 10095,
            'currency'        => 'INR',
            'purpose'         => 'payout',
            'narration'       => 'Rbl account payout',
            'fund_account_id' => 'fa_' . $this->fundAccount->getId(),
            'mode'            => 'IMPS',
            'notes'           => [
                'abc' => 'xyz',
            ],
        ];

        $request = [
            'url'       => '/payouts',
            'method'    => 'POST',
            'content'   => $content
        ];

        $this->ba->privateAuth();

        $this->makeRequestAndGetContent($request);

        $payout2 = $this->getDbLastEntity('payout');

        $attempt2 = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout2['id'], ['status' => 'initiated', 'amount' => '104']);

        $this->fixtures->edit('balance', $payout2['balance_id'], ['balance' => 30019995]);

        $ftsCreateTransfer = new FtsFundTransfer(
            EnvMode::TEST,
            $attempt2['id']);

        $ftsCreateTransfer->handle();

        $attempt2 = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Attempt\Status::INITIATED, $attempt2['status']);

        $this->updateFta(
            $attempt2['fts_transfer_id'],
            $attempt2['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::FAILED);

        $payout2 = $this->getDbLastEntity('payout');

        $attempt2 = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Payout\Status::FAILED, $payout2['status']);
        $this->assertEquals(FundTransfer\Mode::IMPS, $payout2['mode']);
        $this->assertEquals(Attempt\Status::FAILED, $attempt2['status']);
        $this->assertEquals(FundTransfer\Mode::IMPS, $attempt2['mode']);
    }

    public function testRblReversalFailureMapping()
    {
        $channel = Channel::RBL;

        $this->setupForRblPayout($channel);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '123456']);

        $ftsCreateTransfer = new FtsFundTransfer(
            EnvMode::TEST,
            $attempt['id']);

        $ftsCreateTransfer->handle();

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Attempt\Status::INITIATED, $attempt['status']);

        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::FAILED);

        $payout = $this->getDbLastEntity('payout');

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Payout\Status::FAILED, $payout['status']);
        $this->assertEquals(FundTransfer\Mode::IMPS, $payout['mode']);
        $this->assertEquals(Attempt\Status::FAILED, $attempt['status']);
        $this->assertEquals(FundTransfer\Mode::IMPS, $attempt['mode']);

        $mockedResponse = $this->getRblDataResponseForFailureMapping();

        $this->setMozartMockResponse($mockedResponse);

        $this->ba->cronAuth();

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();

        $reversal = $this->getDbLastEntity('reversal');

        $external = $this->getDbLastEntity('external');

        $payout = $this->getDbLastEntity('payout');

        $balance = $this->getDbLastEntity('balance');

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);

        $transactionEntries = $this->getDbEntities('transaction');

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[0]['entity_type']);
        $this->assertEquals($external['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($external['transaction_id'], $basEntries[0]['transaction_id']);
        $this->assertEquals($external['banking_account_statement_id'], $basEntries[0]['id']);
        $this->assertEquals($external['transaction_id'], $transactionEntries[0]['id']);

        $this->assertEquals(EntityConstants::PAYOUT, $basEntries[1]['entity_type']);
        $this->assertEquals($payout['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($payout['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals($payout['transaction_id'], $transactionEntries[1]['id']);

        $this->assertEquals(EntityConstants::REVERSAL, $basEntries[2]['entity_type']);
        $this->assertEquals($reversal['id'], $basEntries[2]['entity_id']);
        $this->assertEquals($reversal['transaction_id'], $basEntries[2]['transaction_id']);
        $this->assertEquals($reversal['transaction_id'], $transactionEntries[2]['id']);
        $this->assertEquals('reversed', $payout['status']);
        $this->assertNotNull($payout['processed_at']);

        // asserting the balance of merchant is credited back
        $this->assertEquals(21450, $balance['balance']);
    }

    public function testRblReversalTxnCreation()
    {
        $channel = Channel::RBL;

        $this->setupForRblPayout($channel);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '123456']);

        // Fetch account statement from RBL

        // Update status
        $ftsCreateTransfer = new FtsFundTransfer(
            EnvMode::TEST,
            $attempt['id']);

        $ftsCreateTransfer->handle();

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Attempt\Status::INITIATED, $attempt['status']);

        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::PROCESSED);

        $payout = $this->getDbLastEntity('payout');
        $this->assertEquals('processed', $payout['status']);

        $mockedResponse = $this->getRblTxnCreation();

        $this->setMozartMockResponse($mockedResponse);

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->cronAuth();
        $this->startTest();

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $transactions = $this->getDbEntities('transaction');
        $externalEntries = $this->getDbEntities('external', ['balance_id' => $payout['balance_id']]);

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[0]['entity_type']);
        $this->assertEquals($externalEntries[0]['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($externalEntries[0]['transaction_id'], $basEntries[0]['transaction_id']);
        $this->assertEquals($externalEntries[0]['banking_account_statement_id'], $basEntries[0]['id']);
        $this->assertEquals($transactions[0]['entity_id'],$externalEntries[0]['id']);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(EntityConstants::PAYOUT, $basEntries[1]['entity_type']);
        $this->assertEquals($payout['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($payout['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals($transactions[1]['entity_id'],$payout['id']);


        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[2]['entity_type']);
        $this->assertEquals($externalEntries[1]['id'], $basEntries[2]['entity_id']);
        $this->assertEquals($externalEntries[1]['transaction_id'], $basEntries[2]['transaction_id']);
        $this->assertEquals($externalEntries[1]['banking_account_statement_id'], $basEntries[2]['id']);

        $this->fixtures->edit('banking_account_statement', $basEntries[2]['id'], ['utr' => '123456']);

        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::REVERSED);

        $reversal = $this->getDbLastEntity('reversal');

        $this->assertEquals($transactions[2]['id'], $reversal['transaction_id']);
    }

    protected function getRblDataResponseForFailureMapping()
    {
        $response = [
            'data' => [
                'PayGenRes' => [
                    'Body' => [
                        'hasMoreData' => 'N',
                        'transactionDetails' => [
                            [
                                'pstdDate' => '2015-12-29T15:58:12.000',
                                'transactionSummary' => [
                                    'instrumentId' => '',
                                    'txnAmt' => [
                                        'amountValue' => '114.50',
                                        'currencyCode' => 'INR'
                                    ],
                                    'txnDate' => '2015-12-29T00:00:00.000',
                                    'txnDesc' => 'DEBIT CARD ANNUAL FEE 2635',
                                    'txnType' => 'C'
                                ],
                                'txnBalance' => [
                                    'currencyCode' => 'INR',
                                    'amountValue' => '214.50'
                                ],
                                'txnCat' => 'TBI',
                                'txnId' => '  S429655',
                                'txnSrlNo' => ' 498',
                                'valueDate' => '2015-12-29T00:00:00.000'
                            ],
                            [
                                'pstdDate' => '2016-01-05T01:36:33.000',
                                'transactionSummary' => [
                                    'instrumentId' => '',
                                    'txnAmt' => [
                                        'amountValue' => '100.95',
                                        'currencyCode' => 'INR'
                                    ],
                                    'txnDate' => '2016-01-05T00:00:00.000',
                                    'txnDesc' => '123456-Z',
                                    'txnType' => 'D'
                                ],
                                'txnBalance' => [
                                    'currencyCode' => 'INR',
                                    'amountValue' => '113.55'
                                ],
                                'txnCat' => 'TCI',
                                'txnId' => '  S807068',
                                'txnSrlNo' => '  49',
                                'valueDate' => '2016-01-05T00:00:00.000'
                            ],
                            [
                                'pstdDate' => '2016-01-05T01:36:33.000',
                                'transactionSummary' => [
                                    'instrumentId' => '',
                                    'txnAmt' => [
                                        'amountValue' => '100.95',
                                        'currencyCode' => 'INR'
                                    ],
                                    'txnDate' => '2016-01-05T00:00:00.000',
                                    'txnDesc' => 'R-123456-Z',
                                    'txnType' => 'C'
                                ],
                                'txnBalance' => [
                                    'currencyCode' => 'INR',
                                    'amountValue' => '214.50'
                                ],
                                'txnCat' => 'TCI',
                                'txnId' => '  S807069',
                                'txnSrlNo' => '  50',
                                'valueDate' => '2016-01-05T00:00:00.000'
                            ],
                        ]
                    ],
                    'Header' => [
                        'Approver_ID' => '',
                        'Corp_ID' => 'RAZORPAY',
                        'Error_Cde' => '',
                        'Error_Desc' => '',
                        'Status' => 'SUCCESS',
                        'TranID' => '1'
                    ],
                    'Signature' => [
                        'Signature' => 'Signature'
                    ]
                ],
            ],
            'error' => null,
            'external_trace_id' => '',
            'mozart_id' => 'bjt1l8jc1osqk0jtadrg',
            'next' => [],
            'success' => true
        ];

        return $response;
    }


    protected function getRblTxnCreation()
    {
        $response = [
            'data' => [
                'PayGenRes' => [
                    'Body' => [
                        'hasMoreData' => 'N',
                        'transactionDetails' => [
                            [
                                'pstdDate' => '2015-12-29T15:58:12.000',
                                'transactionSummary' => [
                                    'instrumentId' => '',
                                    'txnAmt' => [
                                        'amountValue' => '114.50',
                                        'currencyCode' => 'INR'
                                    ],
                                    'txnDate' => '2015-12-29T00:00:00.000',
                                    'txnDesc' => 'DEBIT CARD ANNUAL FEE 2635',
                                    'txnType' => 'C'
                                ],
                                'txnBalance' => [
                                    'currencyCode' => 'INR',
                                    'amountValue' => '214.50'
                                ],
                                'txnCat' => 'TBI',
                                'txnId' => '  S429655',
                                'txnSrlNo' => ' 498',
                                'valueDate' => '2015-12-29T00:00:00.000'
                            ],
                            [
                                'pstdDate' => '2016-01-05T01:36:33.000',
                                'transactionSummary' => [
                                    'instrumentId' => '',
                                    'txnAmt' => [
                                        'amountValue' => '100.95',
                                        'currencyCode' => 'INR'
                                    ],
                                    'txnDate' => '2016-01-05T00:00:00.000',
                                    'txnDesc' => '123456-Z',
                                    'txnType' => 'D'
                                ],
                                'txnBalance' => [
                                    'currencyCode' => 'INR',
                                    'amountValue' => '113.55'
                                ],
                                'txnCat' => 'TCI',
                                'txnId' => '  S807068',
                                'txnSrlNo' => '  49',
                                'valueDate' => '2016-01-05T00:00:00.000'
                            ],
                            [
                                'pstdDate' => '2016-01-05T01:36:33.000',
                                'transactionSummary' => [
                                    'instrumentId' => '',
                                    'txnAmt' => [
                                        'amountValue' => '100.95',
                                        'currencyCode' => 'INR'
                                    ],
                                    'txnDate' => '2016-01-05T00:00:00.000',
                                    'txnDesc' => 'R-143535-Z',
                                    'txnType' => 'C'
                                ],
                                'txnBalance' => [
                                    'currencyCode' => 'INR',
                                    'amountValue' => '214.50'
                                ],
                                'txnCat' => 'TCI',
                                'txnId' => '  S807069',
                                'txnSrlNo' => '  50',
                                'valueDate' => '2016-01-05T00:00:00.000'
                            ],
                        ]
                    ],
                    'Header' => [
                        'Approver_ID' => '',
                        'Corp_ID' => 'RAZORPAY',
                        'Error_Cde' => '',
                        'Error_Desc' => '',
                        'Status' => 'SUCCESS',
                        'TranID' => '1'
                    ],
                    'Signature' => [
                        'Signature' => 'Signature'
                    ]
                ],
            ],
            'error' => null,
            'external_trace_id' => '',
            'mozart_id' => 'bjt1l8jc1osqk0jtadrg',
            'next' => [],
            'success' => true
        ];

        return $response;
    }

    protected function setupForRblPayout($channel = Channel::RBL)
    {
        $this->ba->privateAuth();

        $this->createContact();

        $this->createFundAccount();

        $content = [
            'account_number'  => '2224440041626905',
            'amount'          => 10095,
            'currency'        => 'INR',
            'purpose'         => 'payout',
            'narration'       => 'Rbl account payout',
            'fund_account_id' => 'fa_' . $this->fundAccount->getId(),
            'mode'            => 'IMPS',
            'notes'           => [
                'abc' => 'xyz',
            ],
        ];

        $request = [
            'url'       => '/payouts',
            'method'    => 'POST',
            'content'   => $content
        ];

        $this->makeRequestAndGetContent($request);
    }

    protected function getRblDataResponse()
    {
        $response = [
            'data' => [
                'PayGenRes' => [
                    'Body' => [
                        'hasMoreData' => 'N',
                        'transactionDetails' => [
                            [
                                'pstdDate' => '2015-12-29T15:58:12.000',
                                'transactionSummary' => [
                                    'instrumentId' => '',
                                    'txnAmt' => [
                                        'amountValue' => '114.50',
                                        'currencyCode' => 'INR'
                                    ],
                                    'txnDate' => '2015-12-29T00:00:00.000',
                                    'txnDesc' => 'DEBIT CARD ANNUAL FEE 2635',
                                    'txnType' => 'C'
                                ],
                                'txnBalance' => [
                                    'currencyCode' => 'INR',
                                    'amountValue' => '214.50'
                                ],
                                'txnCat' => 'TBI',
                                'txnId' => '  S429655',
                                'txnSrlNo' => ' 498',
                                'valueDate' => '2015-12-29T00:00:00.000'
                            ],
                            [
                                'pstdDate' => '2016-01-05T01:36:33.000',
                                'transactionSummary' => [
                                    'instrumentId' => '',
                                    'txnAmt' => [
                                        'amountValue' => '100.95',
                                        'currencyCode' => 'INR'
                                    ],
                                    'txnDate' => '2016-01-05T00:00:00.000',
                                    'txnDesc' => '123456-Z',
                                    'txnType' => 'D'
                                ],
                                'txnBalance' => [
                                    'currencyCode' => 'INR',
                                    'amountValue' => '113.55'
                                ],
                                'txnCat' => 'TCI',
                                'txnId' => '  S807068',
                                'txnSrlNo' => '  49',
                                'valueDate' => '2016-01-05T00:00:00.000'
                            ],
                        ]
                    ],
                    'Header' => [
                        'Approver_ID' => '',
                        'Corp_ID' => 'RAZORPAY',
                        'Error_Cde' => '',
                        'Error_Desc' => '',
                        'Status' => 'SUCCESS',
                        'TranID' => '1'
                    ],
                    'Signature' => [
                        'Signature' => 'Signature'
                    ]
                ],
            ],
            'error' => null,
            'external_trace_id' => '',
            'mozart_id' => 'bjt1l8jc1osqk0jtadrg',
            'next' => [],
            'success' => true
        ];

        return $response;
    }

    protected function getRblNoDataResponse()
    {
        $response = [
            'data' => [
                'PayGenRes' => [
                    'Body' => [
                        'hasMoreData' => 'N',
                        'transactionDetails' => [],
                    ],
                    'Header' => [
                        'Approver_ID' => '',
                        'Corp_ID' => 'RAZORPAY',
                        'Error_Cde' => '',
                        'Error_Desc' => '',
                        'Status' => 'SUCCESS',
                        'TranID' => '1'
                    ],
                    'Signature' => [
                        'Signature' => 'Signature'
                    ]
                ]
            ],
            'error' => null,
            'external_trace_id' => '',
            'mozart_id' => 'bk0u6h3c1osgdo154fh0',
            'next' => [],
            'success' => true
        ];

        return $response;
    }

    protected function getRblGatewayErrorResponse()
    {
        $response = [
            'httpCode' => '401',
            'httpMessage' => 'Unauthorized',
            'moreInformation' => 'Client id in wrong location.'
        ];

        return $response;
    }

    protected function getRblInvalidDetailsResponse()
    {
        $response = [
            'data' => [
                'PayGenRes' => [
                    'Header' => [
                        'Approver_ID' => '',
                        'Corp_ID' => 'RAZORPAY',
                        'Error_Cde' => 'ER034',
                        'Error_Desc' => 'Request not valid for the given Account Number',
                        'Status' => 'FAILED',
                        'TranID' => 'A1'
                    ],
                    'Signature' => [
                        'Signature' => 'Signature'
                    ]
                ],
            ],
            'error' => [
                'description' => 'Request not valid for the given Account Number',
                'gateway_error_code' => 'ER034',
                'gateway_error_description' => 'Request not valid for the given Account Number',
                'gateway_status_code' => 200,
                'internal_error_code' => 'VALIDATION_ERROR'
            ],
            'external_trace_id' => '',
            'mozart_id' => 'bk1mej3c1osssas3oghg',
            'next' => [],
            'success' => false
        ];

        return $response;
    }

    protected function getMozartServiceFailureResponse()
    {
        $response = [
            'data' => [
                'PayGenRes' => [
                    'Header' => [
                        'Approver_ID' => '',
                        'Corp_ID' => 'RAZORPAY',
                        'Error_Cde' => 'ER034',
                        'Error_Desc' => 'Request not valid for the given Account Number',
                        'Status' => 'FAILED',
                        'TranID' => 'A1'
                    ],
                    'Signature' => [
                        'Signature' => 'Signature'
                    ]
                ],
            ],
            'error' => [
                'description' => 'Request not valid for the given Account Number',
                'gateway_error_code' => 'ER034',
                'gateway_error_description' => 'Request not valid for the given Account Number',
                'gateway_status_code' => 200,
                'internal_error_code' => 'VALIDATION_ERROR'
            ],
            'external_trace_id' => '',
            'mozart_id' => 'bk1mej3c1osssas3oghg',
            'next' => [],
            'success' => false
        ];

        return $response;
    }

    protected function getRblMalformedResponse()
    {
        $response = $this->getRblDataResponse();

        unset($response['data']['PayGenRes']['Body']['transactionDetails'][0]['pstdDate']);

        return $response;
    }

    protected function getRblIncorrectBalanceResponse()
    {
        $response = $this->getRblDataResponse();

        $response['data']['PayGenRes']['Body']['transactionDetails'][0]['txnBalance']['amountValue'] = '20.00';

        return $response;
    }

    protected function getRblNegativeBalanceResponse()
    {
        $response = $this->getRblDataResponse();

        $txn = $response['data']['PayGenRes']['Body']['transactionDetails'][1];

        $txn['transactionSummary']['txnAmt']['amountValue'] = '221.00';
        $txn['txnBalance']['amountValue'] = '-3.50';

        $response['data']['PayGenRes']['Body']['transactionDetails'][1] = $txn;

        return $response;
    }

    protected function getRblPayoutMappingResponse()
    {
        $response = $this->getRblDataResponse();

        $txn = $response['data']['PayGenRes']['Body']['transactionDetails'][1];

        $txn['transactionSummary']['txnAmt']['amountValue'] = '1.04';
        $txn['transactionSummary']['txnDesc'] = 'NEFT\\/000099572822\\/Vivek Karna HDFC                ';
        $txn['txnBalance']['amountValue'] = '300198.91';
        $txn['txnId'] = '   S55959';

        $response['data']['PayGenRes']['Body']['transactionDetails'][0] = $txn;
        unset($response['data']['PayGenRes']['Body']['transactionDetails'][1]);

        return $response;
    }

    protected function setMozartMockResponse($mockedResponse)
    {
        $this->app['rzp.mode'] = EnvMode::TEST;

        $mock = Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $mock->shouldReceive([
                                 'sendRawRequest' => json_encode($mockedResponse)
                             ]);

        $this->app->instance('mozart', $mock);
    }

    protected function addTestTransactions(): void
    {
        $mockedResponse = $this->getRblDataResponse();

        $this->setMozartMockResponse($mockedResponse);

        $this->ba->appAuth();

        $request = $this->testData['testRblAccountStatementCase1']['request'];

        $this->sendRequest($request);

        $this->ba->proxyAuth();
    }

    public function testLastFetchedAtWhenNewDataIsPresent()
    {
        $balanceId = $this->balance->getId();

        // 1578044039 is the timestamp of Jan 3, 2020.
        // Need to edit here as balance creation and update occur in test within the same second.
        $this->fixtures->edit('balance', $balanceId, ['updated_at' => 1578044039]);

        $initialBalance = $this->getDbEntityById('balance', $balanceId)->toArray();

        $bankingAccount = $this->fixtures->edit('banking_account', 'xba00000000001', [
            'balance_last_fetched_at' => 1578044039
        ]);

        $startTime = Carbon::now()->timestamp;

        $this->testRblAccountStatementCase1();

        $endTime = Carbon::now()->timestamp;

        $this->ba->proxyAuth();

        $response = $this->startTest();

        foreach ($response['items'] as $balance)
        {
            if (isset($balance['type']) and ($balance['type'] === 'banking') and ($balance['account_type'] === 'direct'))
            {
                $this->assertGreaterThanOrEqual($startTime, $balance['last_fetched_at']);
                $this->assertLessThanOrEqual($endTime, $balance['last_fetched_at']);
            }
        }

        $finalBalance = $this->getDbEntityById('balance', $balanceId)->toArray();

        // last_fetched_at and updated_at both change as there was new data
        $this->assertNotEquals($initialBalance['updated_at'], $finalBalance['updated_at']);
    }

    public function testLastFetchedAtWhenNewDataIsNotPresent()
    {
        $balanceId = $this->balance->getId();

        // 1578044039 is the timestamp of Jan 3, 2020.
        // Need to edit here as balance creation and update occur in test within the same second.
        $this->fixtures->edit('balance', $balanceId, ['updated_at' => 1578044039]);

        $initialBalance = $this->getDbEntityById('balance', $balanceId)->toArray();

        $bankingAccount = $this->fixtures->edit('banking_account', 'xba00000000001', [
            'balance_last_fetched_at' => 1578044039
        ]);

        $startTime = Carbon::now()->timestamp;

        $this->testRblAccountStatementCase2();

        $endTime = Carbon::now()->timestamp;

        $this->ba->proxyAuth();

        $response = $this->startTest();

        foreach ($response['items'] as $balance)
        {
            if (isset($balance['type']) and ($balance['type'] === 'banking') and ($balance['account_type'] === 'direct'))
            {
                $this->assertGreaterThanOrEqual($startTime, $balance['last_fetched_at']);
                $this->assertLessThanOrEqual($endTime, $balance['last_fetched_at']);
            }
        }

        $finalBalance = $this->getDbEntityById('balance', $balanceId)->toArray();

        // last_fetched_at changed but updated_at remains same as there was no new data
        $this->assertEquals($initialBalance['updated_at'], $finalBalance['updated_at']);
    }

    // in this balance fetch cron is run after banking Account statement fetch Cron.
    // and then checks dispatch queued payout flow uses balance from cron which updated latest for processing
    // of queued payout
    public function testProcessingRblQueuedPayoutWhenBalanceFetchCronRunsAfterBankingAccountStatementCron()
    {
        $this->mockMozartResponseForFetchingBalanceFromRblGateway(50);

        $queuedPayoutAttributes = [
            'account_number'        =>  '2224440041626905',
            'amount'                =>  6000,
            'queue_if_low_balance'  =>  1,
        ];

        sleep(1);

        $queuedPayout = $this->createQueuedOrPendingPayout($queuedPayoutAttributes, 'rzp_test_TheTestAuthKey');

        $this->testLatestBalanceWhenBalanceFetchCronRunsAfterBankingAccountStatementCron(70);

        $actualOutput = $this->dispatchQueuedPayouts();

        $expectedOutput = [
            $this->bankingBalance->getId() => [
                'original_balance'         => 7000,
                'balance_remaining'        => 1000,
                'total_payout_count'       => 1,
                'dispatched_payout_count'  => 1,
                'dispatched_payout_amount' => 6000,
            ]
        ];

        $this->assertArraySelectiveEquals($expectedOutput, $actualOutput);
    }

    public function testLastFetchedAtEqualsBalanceUpdatedAtInitially()
    {
        $balanceId = $this->balance->getId();

        // 1578044039 is the timestamp of Jan 3, 2020.
        // Need to edit here as balance creation and update occur in test within the same second.
        $this->fixtures->edit('balance', $balanceId, ['updated_at' => 1578044039]);

        $bankingAccount = $this->fixtures->edit('banking_account', 'xba00000000001', [
            'balance_last_fetched_at' => 1578044039
        ]);

        $initialBalance = $this->getDbEntityById('balance', $balanceId)->toArray();

        $this->testRblAccountStatementCase3();

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $finalBalance = $this->getDbEntityById('balance', $balanceId)->toArray();

        foreach ($response['items'] as $balance)
        {
            if (isset($balance['type']) and ($balance['type'] === 'banking') and ($balance['account_type'] === 'direct'))
            {
                $this->assertEquals('1578044039', $balance['last_fetched_at']);
            }
        }

        // updated_at remains same as BAS fetch failed
        $this->assertEquals($initialBalance['updated_at'], $finalBalance['updated_at']);
    }

    // in this first balance fetch cron is run after banking Account statement fetch Cron.
    // and then balance api is checked to see it uses balance from cron which last updated
    public function testLatestBalanceWhenBalanceFetchCronRunsAfterBankingAccountStatementCron($amount = 500)
    {
        $oldDateTime = Carbon::create(2019, 07, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        // account statement fetch cron
        $mockedResponse = $this->getRblDataResponse();

        $this->setMozartMockResponse($mockedResponse);

        $this->runBankingAccountStatementFetchCron();

        $this->ba->proxyAuth();

        $request = [
            'method'  => 'GET',
            'url'     => '/balances?type=banking',
            'content' => [
            ]
        ];

        $balanceApiResponseAfterStmtCron = $this->makeRequestAndGetContent($request);

        foreach ($balanceApiResponseAfterStmtCron['items'] as $item)
        {
            if (($item[Balance\Entity::ACCOUNT_TYPE] === AccountType::DIRECT) and
                ($item[Balance\Entity::CHANNEL] === Balance\Channel::RBL))
            {
                $actualOutputAfterStmtCron = $item;
            }
        }

        $oldDateTime = Carbon::create(2019, 07, 21, 12, 25, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        // running balance fetch cron
        $this->mockMozartResponseForFetchingBalanceFromRblGateway($amount);

        $this->runBalanceFetchCron();

        $this->ba->proxyAuth();

        $request = [
            'method'  => 'GET',
            'url'     => '/balances?type=banking',
            'content' => [
            ]
        ];

        $response = $this->makeRequestAndGetContent($request);

        $bankingAccount = $this->getDbEntityById('banking_account', 'xba00000000001')->toArray();

        foreach ($response['items'] as $item)
        {
            if (($item[Balance\Entity::ACCOUNT_TYPE] === AccountType::DIRECT) and
                ($item[Balance\Entity::CHANNEL] === Balance\Channel::RBL))
            {
                $actualOutputAfterBalanceFetchCron = $item;
            }
        }

        // assertions

        $expectedResponse = [
            'last_fetched_at' => $bankingAccount[BankingAccount\Entity::BALANCE_LAST_FETCHED_AT],
            'balance'         => $bankingAccount[BankingAccount\Entity::GATEWAY_BALANCE],
        ];

        $this->assertNotEquals($actualOutputAfterStmtCron[Balance\Entity::LAST_FETCHED_AT],
                               $actualOutputAfterBalanceFetchCron[Balance\Entity::LAST_FETCHED_AT]);

        $this->assertArraySelectiveEquals($expectedResponse, $actualOutputAfterBalanceFetchCron);

        Carbon::setTestNow();
    }

    // in this first balance fetch cron is run before banking Account statement fetch Cron.
    // and then balance api is checked to see it uses balance from cron which last updated
    public function testLatestBalanceWhenBalanceFetchCronRunsBeforeBankingAccountStatementCron($amount = 500)
    {
        $oldDateTime = Carbon::create(2019, 07, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        // running balance fetch cron
        $this->mockMozartResponseForFetchingBalanceFromRblGateway($amount);

        $this->runBalanceFetchCron();

        $this->ba->proxyAuth();

        $request = [
            'method'  => 'GET',
            'url'     => '/balances?type=banking',
            'content' => [
            ]
        ];

        $balanceApiResponseAfterBalanceFetchCron = $this->makeRequestAndGetContent($request);

        foreach ($balanceApiResponseAfterBalanceFetchCron['items'] as $item)
        {
            if (($item[Balance\Entity::ACCOUNT_TYPE] === AccountType::DIRECT) and
                ($item[Balance\Entity::CHANNEL] === Balance\Channel::RBL))
            {
                $actualOutputAfterBalanceFetchCron = $item;
            }
        }

        $oldDateTime = Carbon::create(2019, 07, 21, 12, 25, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        // account statement fetch cron
        $mockedResponse = $this->getRblDataResponse();

        $this->setMozartMockResponse($mockedResponse);

        $this->runBankingAccountStatementFetchCron();

        $this->ba->proxyAuth();

        $request = [
            'method'  => 'GET',
            'url'     => '/balances?type=banking',
            'content' => [
            ]
        ];

        $balanceApiResponseAfterStmtCron = $this->makeRequestAndGetContent($request);

        foreach ($balanceApiResponseAfterStmtCron['items'] as $item)
        {
            if (($item[Balance\Entity::ACCOUNT_TYPE] === AccountType::DIRECT) and
                ($item[Balance\Entity::CHANNEL] === Balance\Channel::RBL))
            {
                $actualOutputAfterStmtCron = $item;
            }
        }

        // assertions

        $this->assertNotEquals($actualOutputAfterStmtCron[Balance\Entity::LAST_FETCHED_AT],
                               $actualOutputAfterBalanceFetchCron[Balance\Entity::LAST_FETCHED_AT]);

        $this->assertNotEquals($actualOutputAfterStmtCron[Balance\Entity::BALANCE],
                               $actualOutputAfterBalanceFetchCron[Balance\Entity::BALANCE]);

        Carbon::setTestNow();

    }

    // in this first balance fetch cron is run before banking Account statement fetch Cron.
    // and then checks payout creation uses balance from cron which updated latest
    public function testCreateRblPayoutWhenBalanceFetchCronRunsBeforeBankingAccountStatementCron()
    {
        $this->testLatestBalanceWhenBalanceFetchCronRunsBeforeBankingAccountStatementCron();

        $this->ba->privateAuth();

        $this->startTest();
    }

    // in this first balance fetch cron is run after banking Account statement fetch Cron.
    // and then checks payout creation uses balance from cron which updated latest
    public function testCreateRblPayoutWhenBalanceFetchCronRunsAfterBankingAccountStatementCron()
    {
        $this->testLatestBalanceWhenBalanceFetchCronRunsAfterBankingAccountStatementCron();

        $this->ba->privateAuth();

        $this->startTest();
    }

    // in this first balance fetch cron is run after banking Account statement fetch Cron.
    // and then checks payout creation uses balance from cron which updated latest,but this time balance
    // is low so payout gets queued
    public function testCreateRblPayoutWhenBalanceFetchCronRunsAfterBankingAccountStatementCronWithLowBalance()
    {
        $this->testLatestBalanceWhenBalanceFetchCronRunsAfterBankingAccountStatementCron(50);

        $this->ba->privateAuth();

        $this->startTest();
    }

    // in this first balance fetch cron is run before banking Account statement fetch Cron.
    // and then checks payout creation uses balance from cron which updated latest,but this time balance
    // is low so payout gets queued
    public function testCreateRblPayoutWhenBalanceFetchCronRunsBeforeBankingAccountStatementCronWithLowBalance()
    {
        $this->testLatestBalanceWhenBalanceFetchCronRunsBeforeBankingAccountStatementCron(50);

        $this->ba->privateAuth();

        $this->startTest();
    }

    // in this first balance fetch cron is run before banking Account statement fetch Cron.
    // and then checks dispatch queued payout flow uses balance from cron which updated latest for processing
    // of queued payout
    public function testProcessingRblQueuedPayoutWhenBalanceFetchCronRunsBeforeBankingAccountStatementCron()
    {
        $this->mockMozartResponseForFetchingBalanceFromRblGateway(50);

        $queuedPayoutAttributes = [
            'account_number'        =>  '2224440041626905',
            'amount'                =>  11000,
            'queue_if_low_balance'  =>  1,
        ];

        $queuedPayout = $this->createQueuedOrPendingPayout($queuedPayoutAttributes, 'rzp_test_TheTestAuthKey');

        $this->testLatestBalanceWhenBalanceFetchCronRunsBeforeBankingAccountStatementCron(50);

        $actualOutput = $this->dispatchQueuedPayouts();

        $expectedOutput = [
            $this->bankingBalance->getId() => [
                'original_balance'         => 11355,
                'balance_remaining'        => 355,
                'total_payout_count'       => 1,
                'dispatched_payout_count'  => 1,
                'dispatched_payout_amount' => 11000,
            ]
        ];

        $this->assertArraySelectiveEquals($expectedOutput, $actualOutput);
    }  
      
    public function testFetchStatementByTransactionIdForRbl()
    {
        $this->testRblAccountStatementCase1();

        $this->testData['testFetchStatementByTransactionIdForRbl']['request']['url'] = '/transactions/'  . $this->txnEntity->getPublicId();

        $request = $this->testData['testFetchStatementByTransactionIdForRbl']['request'];

        $this->ba->privateAuth();

        $this->ba->privateAuth();

        $response = $this->startTest();
        
        $this->assertEquals($this->txnEntity->getPostedDate(), $response['created_at']);
        $this->assertEquals($this->txnEntity->getPublicId(), $response['id']);
        $this->assertEquals($this->txnEntity['amount'], $response['amount']);
    }
}
