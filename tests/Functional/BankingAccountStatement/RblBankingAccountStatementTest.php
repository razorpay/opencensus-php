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
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Models\FundTransfer;
use RZP\Models\BankingAccount;
use RZP\Services\RazorXClient;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Merchant\Balance;
use RZP\Constants\Mode as EnvMode;
use RZP\Tests\Functional\TestCase;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\BankingAccount\Channel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RZP\Models\Merchant\Balance\Entity;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Models\BankingAccount\Gateway\Rbl;
use RZP\Mail\BankingAccount\StatementMail;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\BankingAccount\Entity as BaEntity;
use RZP\Jobs\FTS\FundTransfer as FtsFundTransfer;
use RZP\Models\External\Entity as ExternalEntity;
use RZP\Models\BankingAccount\Gateway\Rbl\Fields;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\Payout\PayoutTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Models\Transaction\Entity as TransactionEntity;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\BankingAccountStatement\Entity as BasEntity;
use RZP\Jobs\BankingAccountStatement as BankingAccountStatementJob;


class RblBankingAccountStatementTest extends TestCase
{
    use PayoutTrait;
    use AttemptTrait;
    use DbEntityFetchTrait;
    use TestsWebhookEvents;
    use TestsBusinessBanking;

    const UFH_FILE_PATH_REGEX    = '/.*\/ufh\/file\/(.*)/';

    const MOCK_UFH_BASE_LOCATION = 'files/filestore';

    const FILE_ID                = 'file_id';

    /* @var Entity */
    private $balance;

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

        $this->fixtures->create('org:razorpay_org_live');

        $this->fixtures->base->connection('test');
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

        $this->flushCache();

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

    protected function verifyGeneratedXlsxFile($currentTime)
    {
        $openingBalanceCell = 'B36';

        $closingBalanceCell = 'B37';

        $effectiveBalanceCell = 'B38';

        $expectedOpeningBalance = 'INR 214.50';

        $expectedClosingBalance = 'INR 113.55';

        $expectedEffectiveBalance = 'INR 113.55';

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

    /** Case where payout fees is consumed using reward fee credits */
    public function testRblAccountStatementTxnMappingForRewardPayout()
    {
        $channel = Channel::RBL;

        $this->fixtures->edit('card', '100000000lcard', ['last4' => '1112']);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 500 , 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $creditEntity = $this->getDbLastEntity('credits');

        $this->setupForRblPayout($channel);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(500, $payout['fees']);
        $this->assertEquals(0, $payout['tax']);
        $this->assertEquals('reward_fee', $payout['fee_type']);

        $creditEntity = $this->getLastEntity('credits', true);
        $this->assertEquals(500, $creditEntity['used']);

        $creditTxnEntity = $this->getLastEntity('credit_transaction', true);
        $this->assertEquals('payout', $creditTxnEntity['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntity['entity_id']);
        $this->assertEquals(500, $creditTxnEntity['credits_used']);

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

        $balance = $this->getLastEntity('balance', true);

        $balanceBefore = $balance['balance'];

        $this->ba->cronAuth();

        $this->startTest();

        $payout = $this->getDbLastEntity('payout');
        $external = $this->getDbLastEntity('external');
        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $transactions = $this->getDbEntities('transaction');


        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[0]['entity_type']);
        $this->assertEquals($external['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($external['transaction_id'], $basEntries[0]['transaction_id']);
        $this->assertEquals($external['banking_account_statement_id'], $basEntries[0]['id']);

        $this->assertEquals(EntityConstants::PAYOUT, $basEntries[1]['entity_type']);
        $this->assertEquals($payout['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($payout['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals('reward_fee', $transactions[1]['credit_type']);
        $this->assertEquals(500, $transactions[1]['fee_credits']);

        $this->assertEquals($external['balance_id'], $payout['balance_id']);

        $feeBreakup1 = $this->getDbEntities('fee_breakup', ['transaction_id' => $basEntries[0]['transaction_id']]);
        $feeBreakup2 = $this->getDbEntities('fee_breakup', ['transaction_id' => $basEntries[1]['transaction_id']]);

        $this->assertEquals(0, $feeBreakup1->count());
        $this->assertEquals(1, $feeBreakup2->count());

        $this->assertEquals('Bbg7cl6t6I3XA6', $feeBreakup2[0]['pricing_rule_id']);
        $this->assertEquals(EntityConstants::PAYOUT, $feeBreakup2[0]['name']);
        $this->assertEquals(500, $feeBreakup2[0]['amount']);
        $txn = $this->getDbEntity('transaction', ['entity_id' => $payout['id']])->toArray();
        $this->assertEquals($txn['fee'], $payout['fees']);
        $this->assertEquals($txn['tax'], $payout['tax']);

        // check transaction fetch route for dashboard
        $request = [
            'url' => '/transactions/txn_' . $transactions[1]['id'],
            'method' => 'get',
            'content' => []

        ];

        $this->ba->privateAuth();
        $response = $this->makeRequestAndGetContent($request);
        $this->assertEquals(10095, $response['debit']);
        $this->assertEquals('reward_fee', $response['source']['fee_type']);

        // check payout fetch route for dashboard
        $request = [
            'url' => '/payouts/pout_' . $payout['id'],
            'method' => 'get',
            'content' => []

        ];

        $this->ba->privateAuth();
        $response = $this->makeRequestAndGetContent($request);
        $this->assertEquals(500, $response['fees']);
        $this->assertEquals(0, $response['tax']);
        $this->assertEquals('reward_fee', $response['fee_type']);
    }

    /**
     * Asserting that credit balance remains unchanged. The payout tax is set to 0,
     * Credits used column is updated, equal to payout fees. Its fee type is reward_fee
     * The Transaction amount is equal to payout amount(which does not have fee). Its
     * fee is equal to payout fee and tax is 0
     */
    public function testRblAccountStatementTxnMappingForRewardPayoutWithNewCreditsFlow()
    {
        $channel = Channel::RBL;

        $this->fixtures->edit('card', '100000000lcard', ['last4' => '1112']);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 500 , 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->setupForRblPayout($channel);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(500, $payout['fees']);
        $this->assertEquals(0, $payout['tax']);
        $this->assertEquals('reward_fee', $payout['fee_type']);

        $creditEntity = $this->getLastEntity('credits', true);
        $this->assertEquals(500, $creditEntity['used']);

        $creditTxnEntity = $this->getLastEntity('credit_transaction', true);
        $this->assertEquals('payout', $creditTxnEntity['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntity['entity_id']);
        $this->assertEquals(500, $creditTxnEntity['credits_used']);

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

        $balance = $this->getLastEntity('balance', true);

        $this->ba->cronAuth();

        $this->startTest();

        $payout = $this->getDbLastEntity('payout');
        $external = $this->getDbLastEntity('external');
        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $transactions = $this->getDbEntities('transaction');

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[0]['entity_type']);
        $this->assertEquals($external['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($external['transaction_id'], $basEntries[0]['transaction_id']);
        $this->assertEquals($external['banking_account_statement_id'], $basEntries[0]['id']);

        $this->assertEquals(EntityConstants::PAYOUT, $basEntries[1]['entity_type']);
        $this->assertEquals($payout['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($payout['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals('reward_fee', $transactions[1]['credit_type']);
        $this->assertEquals(500, $transactions[1]['fee_credits']);
        $this->assertEquals(10095, $transactions[1]['amount']);
        $this->assertEquals(0, $transactions[1]['tax']);
        $this->assertEquals(500, $transactions[1]['fee']);

        $this->assertEquals($external['balance_id'], $payout['balance_id']);

        $feeBreakup1 = $this->getDbEntities('fee_breakup', ['transaction_id' => $basEntries[0]['transaction_id']]);
        $feeBreakup2 = $this->getDbEntities('fee_breakup', ['transaction_id' => $basEntries[1]['transaction_id']]);

        $this->assertEquals(0, $feeBreakup1->count());
        $this->assertEquals(1, $feeBreakup2->count());

        $this->assertEquals('Bbg7cl6t6I3XA6', $feeBreakup2[0]['pricing_rule_id']);
        $this->assertEquals(EntityConstants::PAYOUT, $feeBreakup2[0]['name']);
        $this->assertEquals(500, $feeBreakup2[0]['amount']);
        $txn = $this->getDbEntity('transaction', ['entity_id' => $payout['id']])->toArray();
        $this->assertEquals($txn['fee'], $payout['fees']);
        $this->assertEquals($txn['tax'], $payout['tax']);

        // check transaction fetch route for dashboard
        $request = [
            'url' => '/transactions/txn_' . $transactions[1]['id'],
            'method' => 'get',
            'content' => []

        ];

        $this->ba->privateAuth();
        $response = $this->makeRequestAndGetContent($request);
        $this->assertEquals(10095, $response['debit']);
        $this->assertEquals('reward_fee', $response['source']['fee_type']);

        // check payout fetch route for dashboard
        $request = [
            'url' => '/payouts/pout_' . $payout['id'],
            'method' => 'get',
            'content' => []

        ];

        $this->ba->privateAuth();
        $response = $this->makeRequestAndGetContent($request);
        $this->assertEquals(500, $response['fees']);
        $this->assertEquals(0, $response['tax']);
        $this->assertEquals('reward_fee', $response['fee_type']);
    }

    /** Case where payout fees is consumed using multiple reward fee credits */
    public function testRblAccountStatementTxnMappingForMultipleRewardsPayout()
    {
        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 100 , 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 600 , 'campaign' => 'test rewards type', 'type' => 'reward_fee', 'product' => 'banking']);

        $channel = Channel::RBL;

        $this->fixtures->edit('card', '100000000lcard', ['last4' => '1112']);

        $this->setupForRblPayout($channel);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(500, $payout['fees']);
        $this->assertEquals(0, $payout['tax']);
        $this->assertEquals('reward_fee', $payout['fee_type']);

        $creditEntities = $this->getDbEntities('credits');
        $this->assertEquals(100, $creditEntities[0]['used']);
        $this->assertEquals(400, $creditEntities[1]['used']);

        $creditTxnEntities = $this->getDbEntities('credit_transaction');
        $this->assertEquals('payout', $creditTxnEntities[0]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[0]['entity_id']);
        $this->assertEquals(100, $creditTxnEntities[0]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[1]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[1]['entity_id']);
        $this->assertEquals(400, $creditTxnEntities[1]['credits_used']);

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
        $transactions = $this->getDbEntities('transaction');

        $this->assertEquals('reward_fee', $transactions[1]['credit_type']);
        $this->assertEquals(500, $transactions[1]['fee_credits']);

        $this->assertEquals('reward_fee', $transactions[1]['credit_type']);
        $this->assertEquals(500, $transactions[1]['fee_credits']);
        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[0]['entity_type']);
        $this->assertEquals($external['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($external['transaction_id'], $basEntries[0]['transaction_id']);
        $this->assertEquals($external['banking_account_statement_id'], $basEntries[0]['id']);

        $this->assertEquals(EntityConstants::PAYOUT, $basEntries[1]['entity_type']);
        $this->assertEquals($payout['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($payout['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals($payout['transaction_id'], $basEntries[1]['transaction_id']);

        $this->assertEquals($external['balance_id'], $payout['balance_id']);

        $feeBreakup1 = $this->getDbEntities('fee_breakup', ['transaction_id' => $basEntries[0]['transaction_id']]);
        $feeBreakup2 = $this->getDbEntities('fee_breakup', ['transaction_id' => $basEntries[1]['transaction_id']]);

        $this->assertEquals(0, $feeBreakup1->count());
        $this->assertEquals(1, $feeBreakup2->count());

        $this->assertEquals(EntityConstants::PAYOUT, $feeBreakup2[0]['name']);
        $this->assertEquals(500, $feeBreakup2[0]['amount']);
        $txn = $this->getDbEntity('transaction', ['entity_id' => $payout['id']])->toArray();
        $this->assertEquals($txn['fee'], $payout['fees']);
        $this->assertEquals($txn['tax'], $payout['tax']);
    }

    /**
     * Checking that credit_balance is not updated, credits and credit txns are created (used is
     * updated ). This checks if multiple credits are present then they are consumed in
     * order of their expiry
     */
    public function testRblAccountStatementTxnMappingForMultipleRewardsPayoutWithNewCreditsFlow()
    {
        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 100 , 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->fixtures->create('credit_balance', ['merchant_id' => '10000000000000', 'balance' => 700 ]);

        $creditBalanceEntity = $this->getDbLastEntity('credit_balance');

        $creditBalanceBefore = $creditBalanceEntity['balance'];

        $creditEntity = $this->getDbLastEntity('credits');

        $this->fixtures->edit('credits', $creditEntity['id'], ['balance_id' => $creditBalanceEntity['id']]);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 600 , 'campaign' => 'test rewards type', 'type' => 'reward_fee', 'product' => 'banking']);

        $creditEntity = $this->getDbLastEntity('credits');

        $this->fixtures->edit('credits', $creditEntity['id'], ['balance_id' => $creditBalanceEntity['id']]);

        $channel = Channel::RBL;

        $this->fixtures->edit('card', '100000000lcard', ['last4' => '1112']);

        $this->setupForRblPayout($channel);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(500, $payout['fees']);
        $this->assertEquals(0, $payout['tax']);
        $this->assertEquals('reward_fee', $payout['fee_type']);

        $creditBalanceEntity = $this->getLastEntity('credit_balance', true);
        $this->assertEquals($creditBalanceBefore, $creditBalanceEntity['balance']);

        $creditEntities = $this->getDbEntities('credits');
        $this->assertEquals(100, $creditEntities[0]['used']);
        $this->assertEquals(400, $creditEntities[1]['used']);

        $creditTxnEntities = $this->getDbEntities('credit_transaction');
        $this->assertEquals('payout', $creditTxnEntities[0]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[0]['entity_id']);
        $this->assertEquals(100, $creditTxnEntities[0]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[1]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[1]['entity_id']);
        $this->assertEquals(400, $creditTxnEntities[1]['credits_used']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '123456','fts_transfer_id'    =>  '69']);

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
        $transactions = $this->getDbEntities('transaction');

        $this->assertEquals('reward_fee', $transactions[1]['credit_type']);
        $this->assertEquals(500, $transactions[1]['fee_credits']);

        $this->assertEquals('reward_fee', $transactions[1]['credit_type']);
        $this->assertEquals(500, $transactions[1]['fee_credits']);
        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[0]['entity_type']);
        $this->assertEquals($external['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($external['transaction_id'], $basEntries[0]['transaction_id']);
        $this->assertEquals($external['banking_account_statement_id'], $basEntries[0]['id']);

        $this->assertEquals(EntityConstants::PAYOUT, $basEntries[1]['entity_type']);
        $this->assertEquals($payout['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($payout['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals($payout['transaction_id'], $basEntries[1]['transaction_id']);

        $this->assertEquals($external['balance_id'], $payout['balance_id']);

        $feeBreakup1 = $this->getDbEntities('fee_breakup', ['transaction_id' => $basEntries[0]['transaction_id']]);
        $feeBreakup2 = $this->getDbEntities('fee_breakup', ['transaction_id' => $basEntries[1]['transaction_id']]);

        $this->assertEquals(0, $feeBreakup1->count());
        $this->assertEquals(1, $feeBreakup2->count());

        $this->assertEquals(EntityConstants::PAYOUT, $feeBreakup2[0]['name']);
        $this->assertEquals(500, $feeBreakup2[0]['amount']);
        $txn = $this->getDbEntity('transaction', ['entity_id' => $payout['id']])->toArray();
        $this->assertEquals($txn['fee'], $payout['fees']);
        $this->assertEquals($txn['tax'], $payout['tax']);
    }

    /** Case where payout fees is consumed using multiple reward fee credits
     *  And then payout gets failed. So the expectation is credits also get
     *  reversed.
     */
    public function testRblAccountStatementTxnMappingForMultipleRewardsPayoutFailed()
    {
        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 100 , 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 600 , 'campaign' => 'test rewards type', 'type' => 'reward_fee', 'product' => 'banking']);

        $creditEntity = $this->getDbLastEntity('credits');

        $channel = Channel::RBL;

        $this->setupForRblPayout($channel);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(500, $payout['fees']);
        $this->assertEquals(0, $payout['tax']);

        $creditEntities = $this->getDbEntities('credits');
        $this->assertEquals(100, $creditEntities[0]['used']);
        $this->assertEquals(400, $creditEntities[1]['used']);

        $creditTxnEntities = $this->getDbEntities('credit_transaction');
        $this->assertEquals('payout', $creditTxnEntities[0]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[0]['entity_id']);
        $this->assertEquals(100, $creditTxnEntities[0]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[1]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[1]['entity_id']);
        $this->assertEquals(400, $creditTxnEntities[1]['credits_used']);

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
            Attempt\Status::FAILED);

        $payout = $this->getDbLastEntity('payout');

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Payout\Status::FAILED, $payout['status']);
        $this->assertEquals(FundTransfer\Mode::IMPS, $payout['mode']);
        $this->assertEquals(Attempt\Status::FAILED, $attempt['status']);
        $this->assertEquals(FundTransfer\Mode::IMPS, $attempt['mode']);

        $creditEntities = $this->getDbEntities('credits');
        $this->assertEquals(0, $creditEntities[0]['used']);
        $this->assertEquals(0, $creditEntities[1]['used']);

        $creditTxnEntities = $this->getDbEntities('credit_transaction');
        $this->assertEquals('payout', $creditTxnEntities[0]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[0]['entity_id']);
        $this->assertEquals(100, $creditTxnEntities[0]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[1]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[1]['entity_id']);
        $this->assertEquals(400, $creditTxnEntities[1]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[2]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[2]['entity_id']);
        $this->assertEquals(-100, $creditTxnEntities[2]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[3]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[3]['entity_id']);
        $this->assertEquals(-400, $creditTxnEntities[3]['credits_used']);
    }

    /**
     * checked credit balance is not updated. The credits are consumed in order of expiry
     * Credit txns are created and then when payout failed, credits used is set to 0 and credit
     * txns with -ve value of credits used is created
     */
    public function testRblAccountStatementTxnMappingForMultipleRewardsPayoutFailedWithNewCreditsFlow()
    {
        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 100 , 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 600 , 'campaign' => 'test rewards type', 'type' => 'reward_fee', 'product' => 'banking']);

        $channel = Channel::RBL;

        $this->setupForRblPayout($channel);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(500, $payout['fees']);
        $this->assertEquals(0, $payout['tax']);

        $creditEntities = $this->getDbEntities('credits');
        $this->assertEquals(100, $creditEntities[0]['used']);
        $this->assertEquals(400, $creditEntities[1]['used']);

        $creditTxnEntities = $this->getDbEntities('credit_transaction');
        $this->assertEquals('payout', $creditTxnEntities[0]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[0]['entity_id']);
        $this->assertEquals(100, $creditTxnEntities[0]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[1]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[1]['entity_id']);
        $this->assertEquals(400, $creditTxnEntities[1]['credits_used']);

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
            Attempt\Status::FAILED);

        $payout = $this->getDbLastEntity('payout');

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Payout\Status::FAILED, $payout['status']);
        $this->assertEquals(FundTransfer\Mode::IMPS, $payout['mode']);
        $this->assertEquals(Attempt\Status::FAILED, $attempt['status']);
        $this->assertEquals(FundTransfer\Mode::IMPS, $attempt['mode']);

        $creditEntities = $this->getDbEntities('credits');
        $this->assertEquals(0, $creditEntities[0]['used']);
        $this->assertEquals(0, $creditEntities[1]['used']);

        $creditTxnEntities = $this->getDbEntities('credit_transaction');
        $this->assertEquals('payout', $creditTxnEntities[0]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[0]['entity_id']);
        $this->assertEquals(100, $creditTxnEntities[0]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[1]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[1]['entity_id']);
        $this->assertEquals(400, $creditTxnEntities[1]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[2]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[2]['entity_id']);
        $this->assertEquals(-100, $creditTxnEntities[2]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[3]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[3]['entity_id']);
        $this->assertEquals(-400, $creditTxnEntities[3]['credits_used']);
    }

    /** Case where payout fees is consumed using multiple reward fee credits
     *  And then payout gets failed. So the expectation is credits also get
     *  reversed.
     */
    public function testRblAccountStatementTxnMappingForMultipleRewardsPayoutReversals()
    {
        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 100 , 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->fixtures->create('credit_balance', ['merchant_id' => '10000000000000', 'balance' => 700 ]);

        $creditBalanceEntity = $this->getDbLastEntity('credit_balance');

        $creditEntity = $this->getDbLastEntity('credits');

        $this->fixtures->edit('credits', $creditEntity['id'], ['balance_id' => $creditBalanceEntity['id']]);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 600 , 'campaign' => 'test rewards type', 'type' => 'reward_fee', 'product' => 'banking']);

        $channel = Channel::RBL;

        $this->setupForRblPayout($channel);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(500, $payout['fees']);
        $this->assertEquals(0, $payout['tax']);

        $creditEntities = $this->getDbEntities('credits');
        $this->assertEquals(100, $creditEntities[0]['used']);
        $this->assertEquals(400, $creditEntities[1]['used']);

        $creditTxnEntities = $this->getDbEntities('credit_transaction');
        $this->assertEquals('payout', $creditTxnEntities[0]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[0]['entity_id']);
        $this->assertEquals(100, $creditTxnEntities[0]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[1]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[1]['entity_id']);
        $this->assertEquals(400, $creditTxnEntities[1]['credits_used']);

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
            Attempt\Status::REVERSED);

        $payout = $this->getDbLastEntity('payout');

        $reversal = $this->getDbLastEntity('reversal');
        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Payout\Status::REVERSED, $payout['status']);
        $this->assertEquals(FundTransfer\Mode::IMPS, $payout['mode']);
        $this->assertEquals(Attempt\Status::REVERSED, $attempt['status']);
        $this->assertEquals(FundTransfer\Mode::IMPS, $attempt['mode']);

        $creditEntities = $this->getDbEntities('credits');
        $this->assertEquals(0, $creditEntities[0]['used']);
        $this->assertEquals(0, $creditEntities[1]['used']);

        $creditTxnEntities = $this->getDbEntities('credit_transaction');
        $this->assertEquals('payout', $creditTxnEntities[0]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[0]['entity_id']);
        $this->assertEquals(100, $creditTxnEntities[0]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[1]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[1]['entity_id']);
        $this->assertEquals(400, $creditTxnEntities[1]['credits_used']);

        $this->assertEquals('reversal', $creditTxnEntities[2]['entity_type']);
        $this->assertEquals($reversal['id'],  $creditTxnEntities[2]['entity_id']);
        $this->assertEquals(-100, $creditTxnEntities[2]['credits_used']);

        $this->assertEquals('reversal', $creditTxnEntities[3]['entity_type']);
        $this->assertEquals($reversal['id'],  $creditTxnEntities[3]['entity_id']);
        $this->assertEquals(-400, $creditTxnEntities[3]['credits_used']);
    }

    /** Case where payout fees is consumed using multiple reward fee credits
     *  And then payout gets reversed. So the expectation is credits also get
     *  reversed. The credit balance is not updated. The credits are consumed in order of expiry
     *  Credit txns are created and then when payout failed, credits used is set to 0 and credit
     *  txns with -ve value of credits used is created
     */
    public function testRblAccountStatementTxnMappingForMultipleRewardsPayoutReversalsWithNewCreditsFlow()
    {
        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 100 , 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $creditEntity = $this->getDbLastEntity('credits');

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 600 , 'campaign' => 'test rewards type', 'type' => 'reward_fee', 'product' => 'banking']);

        $channel = Channel::RBL;

        $this->setupForRblPayout($channel);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(500, $payout['fees']);
        $this->assertEquals(0, $payout['tax']);

        $creditEntities = $this->getDbEntities('credits');
        $this->assertEquals(100, $creditEntities[0]['used']);
        $this->assertEquals(400, $creditEntities[1]['used']);

        $creditTxnEntities = $this->getDbEntities('credit_transaction');
        $this->assertEquals('payout', $creditTxnEntities[0]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[0]['entity_id']);
        $this->assertEquals(100, $creditTxnEntities[0]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[1]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[1]['entity_id']);
        $this->assertEquals(400, $creditTxnEntities[1]['credits_used']);

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
            Attempt\Status::REVERSED);

        $payout = $this->getDbLastEntity('payout');

        $reversal = $this->getDbLastEntity('reversal');
        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Payout\Status::REVERSED, $payout['status']);
        $this->assertEquals(FundTransfer\Mode::IMPS, $payout['mode']);
        $this->assertEquals(Attempt\Status::REVERSED, $attempt['status']);
        $this->assertEquals(FundTransfer\Mode::IMPS, $attempt['mode']);

        $creditEntities = $this->getDbEntities('credits');
        $this->assertEquals(0, $creditEntities[0]['used']);
        $this->assertEquals(0, $creditEntities[1]['used']);

        $creditTxnEntities = $this->getDbEntities('credit_transaction');
        $this->assertEquals('payout', $creditTxnEntities[0]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[0]['entity_id']);
        $this->assertEquals(100, $creditTxnEntities[0]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[1]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[1]['entity_id']);
        $this->assertEquals(400, $creditTxnEntities[1]['credits_used']);

        $this->assertEquals('reversal', $creditTxnEntities[2]['entity_type']);
        $this->assertEquals($reversal['id'],  $creditTxnEntities[2]['entity_id']);
        $this->assertEquals(-100, $creditTxnEntities[2]['credits_used']);

        $this->assertEquals('reversal', $creditTxnEntities[3]['entity_type']);
        $this->assertEquals($reversal['id'],  $creditTxnEntities[3]['entity_id']);
        $this->assertEquals(-400, $creditTxnEntities[3]['credits_used']);
    }

    /** Case where payout fees is consumed using banking balance as rewards are less */
    public function testRblAccountStatementTxnMappingForLessRewardsAndBankingBalancePayout()
    {
        $channel = Channel::RBL;

        $this->fixtures->edit('card', '100000000lcard', ['last4' => '1112']);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 100 , 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $creditEntity = $this->getDbLastEntity('credits');

        $this->setupForRblPayout($channel);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout['pricing_rule_id']);
        $this->assertNull( $payout['fee_type']);

        $creditEntity = $this->getLastEntity('credits', true);
        $this->assertEquals(0, $creditEntity['used']);

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
        $transactions = $this->getDbEntities('transaction');

        $this->assertEquals('default', $transactions[1]['credit_type']);
        $this->assertEquals(0, $transactions[1]['fee_credits']);
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
        $txn = $this->getDbEntity('transaction', ['entity_id' => $payout['id']])->toArray();
        $this->assertEquals($txn['fee'], $payout['fees']);
        $this->assertEquals($txn['tax'], $payout['tax']);
    }

    /** Case where payout fees is consumed using banking balance as rewards are less
     *  Checking  here that credits will not be used and payouts fees and tax will be non 0
     */

    public function testRblAccountStatementTxnMappingForLessRewardsAndBankingBalancePayoutWithNewCreditsFlow()
    {
        $channel = Channel::RBL;

        $this->fixtures->edit('card', '100000000lcard', ['last4' => '1112']);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 100 , 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->fixtures->create('credit_balance', ['merchant_id' => '10000000000000', 'balance' => 100 ]);

        $creditBalanceEntity = $this->getDbLastEntity('credit_balance');

        $creditEntity = $this->getDbLastEntity('credits');

        $this->fixtures->edit('credits', $creditEntity['id'], ['balance_id' => $creditBalanceEntity['id']]);

        $this->setupForRblPayout($channel);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout['pricing_rule_id']);
        $this->assertNull( $payout['fee_type']);

        $creditBalanceEntity = $this->getLastEntity('credit_balance', true);
        $this->assertEquals(100, $creditBalanceEntity['balance']);

        $creditEntity = $this->getLastEntity('credits', true);
        $this->assertEquals(0, $creditEntity['used']);

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
        $transactions = $this->getDbEntities('transaction');

        $this->assertEquals('default', $transactions[1]['credit_type']);
        $this->assertEquals(0, $transactions[1]['fee_credits']);
        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[0]['entity_type']);
        $this->assertEquals($external['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($external['transaction_id'], $basEntries[0]['transaction_id']);
        $this->assertEquals($external['banking_account_statement_id'], $basEntries[0]['id']);

        $this->assertEquals(EntityConstants::PAYOUT, $basEntries[1]['entity_type']);
        $this->assertEquals($payout['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($payout['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals($payout['amount'], $transactions[1]['amount']);

        $this->assertEquals($external['balance_id'], $payout['balance_id']);

        $feeBreakup1 = $this->getDbEntities('fee_breakup', ['transaction_id' => $basEntries[0]['transaction_id']]);
        $feeBreakup2 = $this->getDbEntities('fee_breakup', ['transaction_id' => $basEntries[1]['transaction_id']]);

        $this->assertEquals(0, $feeBreakup1->count());
        $this->assertEquals(2, $feeBreakup2->count());

        $this->assertEquals('Bbg7cl6t6I3XA6', $feeBreakup2[0]['pricing_rule_id']);
        $this->assertEquals(EntityConstants::PAYOUT, $feeBreakup2[0]['name']);
        $this->assertEquals(500, $feeBreakup2[0]['amount']);
        $this->assertEquals(90, $feeBreakup2[1]['amount']);
        $txn = $this->getDbEntity('transaction', ['entity_id' => $payout['id']])->toArray();
        $this->assertEquals($txn['fee'], $payout['fees']);
        $this->assertEquals($txn['tax'], $payout['tax']);
    }

    /**
     * Case where account statement is fetched first, status check is performed later
     */
    public function testRblAccountStatementTxnMappingCase2()
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

        $this->assertEquals(1, count($updatedExternalEntries));
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
        $this->assertEquals(2, $feeBreakup2->count());

        $this->assertEquals('Bbg7cl6t6I3XA6', $feeBreakup2[0]['pricing_rule_id']);
        $this->assertEquals(EntityConstants::PAYOUT, $feeBreakup2[0]['name']);
        $this->assertEquals(500, $feeBreakup2[0]['amount']);
        $this->assertEquals(90, $feeBreakup2[1]['amount']);
        $this->assertEquals(EntityConstants::TAX, $feeBreakup2[1]['name']);
    }

    /**
     * Case where status check is performed first, account statement is fetched later
     * Mapping transaction using utr for NEFT
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

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated', 'amount' => '104',
                                                        'utr' => '000099572822' ]);

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['cms_ref_no' => 'S55959',
                                                                        'utr' => '000099572822' ]);

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
        $this->assertEquals('000099572822', $payout['utr']);
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
        $this->assertEquals($payout['utr'], $basEntries[0]['utr']);

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

    /**
     * Case where status check is performed first, account statement is fetched later
     * Mapping transaction using utr for RTGS
     */
    public function testRblAccountStatementTxnMappingRTGS()
    {
        $channel = Channel::RBL;

        $this->setupForRblPayout($channel, 20000000, FundTransfer\Mode::RTGS);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(1770, $payout['fees']);
        $this->assertEquals(270, $payout['tax']);
        $this->assertEquals('Bbg7e4oKCgaube', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated', 'amount' => '104',
                                                        'utr' => 'UTIBH20106341692' ]);

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['cms_ref_no' => 'S55959',
                                                                        'utr' => 'UTIBH20106341692' ]);

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
        $this->assertEquals(FundTransfer\Mode::RTGS, $payout['mode']);
        $this->assertEquals('UTIBH20106341692', $payout['utr']);
        $this->assertEquals(Attempt\Status::PROCESSED, $attempt['status']);
        $this->assertEquals(FundTransfer\Mode::RTGS, $attempt['mode']);
        $this->assertEquals('S55959', $attempt['cms_ref_no']);

        // Fetch account statement from RBL
        $mockedResponse = $this->getRblPayoutMappingResponseRTGS();

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
        $this->assertEquals($payout['utr'], $basEntries[0]['utr']);

        $this->assertEquals(0, count($externalEntries));
        $this->assertEquals(EntityConstants::PAYOUT, $payoutTxn['type']);
        $this->assertEquals($payoutTxn['id'], $payout['transaction_id']);

        $feeBreakup = $this->getDbEntities('fee_breakup', ['transaction_id' => $payout['transaction_id']]);

        $this->assertEquals('Bbg7e4oKCgaube', $feeBreakup[0]['pricing_rule_id']);
        $this->assertEquals(1500, $feeBreakup[0]['amount']);
        $this->assertEquals(EntityConstants::PAYOUT, $feeBreakup[0]['name']);
        $this->assertEquals(270, $feeBreakup[1]['amount']);
        $this->assertEquals(EntityConstants::TAX, $feeBreakup[1]['name']);
    }

    /**
     * Case where status check is performed first, account statement is fetched later
     * Mapping transaction using cms ref no, in this case we could not get the UTR from
     * regex and so we relied on cms ref no of the payout
     */
    public function testRblAccountStatementTxnMappingRTGSNEFTUsingCmsRefNo()
    {
        $channel = Channel::RBL;

        $this->setupForRblPayout($channel, 20000000, FundTransfer\Mode::RTGS);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(1770, $payout['fees']);
        $this->assertEquals(270, $payout['tax']);
        $this->assertEquals('Bbg7e4oKCgaube', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated',
                                                        'utr' => 'UTIBH20106341692' ]);

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['cms_ref_no' => 'S55959',
                                                                        'utr' => 'UTIBH20106341692' ]);

        $this->fixtures->edit('balance', $payout['balance_id'], ['balance' => 50019891]);

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
        $this->assertEquals(FundTransfer\Mode::RTGS, $payout['mode']);
        $this->assertEquals('UTIBH20106341692', $payout['utr']);
        $this->assertEquals(Attempt\Status::PROCESSED, $attempt['status']);
        $this->assertEquals(FundTransfer\Mode::RTGS, $attempt['mode']);
        $this->assertEquals('S55959', $attempt['cms_ref_no']);

        // Fetch account statement from RBL
        $mockedResponse = $this->getRblPayoutMappingResponseRTGS();

        // changing utr so it doesn't match regex
        $txn = $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][0];
        $txn['transactionSummary']['txnDesc'] = 'UTIBH20106341692 Vivek Karna HDFC';
        $txn['transactionSummary']['txnAmt']['amountValue'] = '200000.00';
        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][0] = $txn;

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

        $this->assertEquals('Bbg7e4oKCgaube', $feeBreakup[0]['pricing_rule_id']);
        $this->assertEquals(1500, $feeBreakup[0]['amount']);
        $this->assertEquals(EntityConstants::PAYOUT, $feeBreakup[0]['name']);
        $this->assertEquals(270, $feeBreakup[1]['amount']);
        $this->assertEquals(EntityConstants::TAX, $feeBreakup[1]['name']);
    }

    /**
     * Case when we could not get the UTR from
     * regex and so we relied on cms ref no of the payout outside 4 hours of posted date.
     * this bas gets mapped to external since cms reference no was not within 4 hrs of posted date.
     */
    public function testRblAccountStatementTxnMappingFailedForIFTUsingCmsRefNo()
    {
        $channel = Channel::RBL;

        $this->setupForRblPayout($channel, 104, FundTransfer\Mode::IFT);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated', 'amount' => '104',
                                                        'utr' => 'UTIBH20106341692' ]);

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['cms_ref_no' => 'S55959',
                                                                        'utr' => 'UTIBH20106341692' ]);

        $this->fixtures->edit('balance', $payout['balance_id'], ['balance' => 30019995]);

        $ftsCreateTransfer = new FtsFundTransfer(
            EnvMode::TEST,
            $attempt['id']);

        $ftsCreateTransfer->handle();

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Attempt\Status::INITIATED, $attempt['status']);

        // Fetch account statement from RBL
        $mockedResponse = $this->getRblPayoutMappingResponseRTGS();

        // changing utr so it doesn't match regex.
        $txn = $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][0];
        $txn['transactionSummary']['txnDesc'] = 'UTIBH20106341692 Vivek Karna HDFC';
        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][0] = $txn;

        $this->setMozartMockResponse($mockedResponse);

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->cronAuth();
        $this->startTest();

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $txn = $this->getDbLastEntity('transaction');
        $external = $this->getDbLastEntity('external');
        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[0]['entity_type']);
        $this->assertEquals($external['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($external['transaction_id'], $basEntries[0]['transaction_id']);

        $this->assertEquals(EntityConstants::EXTERNAL, $txn['type']);
        $this->assertEquals($txn['id'], $external['transaction_id']);

        $feeBreakup = $this->getDbEntities('fee_breakup', ['transaction_id' => $payout['transaction_id']]);

        $this->assertEquals(0, $feeBreakup->count());
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

    /*
     * case when status check is done first and followed by account statement processing.
     * in status check payout is failed , but in account statement we get debit and credit row
     * Credits should be reversed only once when the payout is marked as failed
     */
    public function testRblReversalFailureMappingForRewards()
    {
        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 100 , 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $creditEntity = $this->getDbLastEntity('credits');

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 600 , 'campaign' => 'test rewards type', 'type' => 'reward_fee', 'product' => 'banking']);

        $channel = Channel::RBL;

        $this->setupForRblPayout($channel);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(500, $payout['fees']);
        $this->assertEquals(0, $payout['tax']);

        $creditEntities = $this->getDbEntities('credits');
        $this->assertEquals(100, $creditEntities[0]['used']);
        $this->assertEquals(400, $creditEntities[1]['used']);

        $creditTxnEntities = $this->getDbEntities('credit_transaction');
        $this->assertEquals('payout', $creditTxnEntities[0]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[0]['entity_id']);
        $this->assertEquals(100, $creditTxnEntities[0]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[1]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[1]['entity_id']);
        $this->assertEquals(400, $creditTxnEntities[1]['credits_used']);

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

        $creditEntities = $this->getDbEntities('credits');
        $this->assertEquals(0, $creditEntities[0]['used']);
        $this->assertEquals(0, $creditEntities[1]['used']);

        $creditTxnEntities = $this->getDbEntities('credit_transaction');
        $this->assertEquals('payout', $creditTxnEntities[0]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[0]['entity_id']);
        $this->assertEquals(100, $creditTxnEntities[0]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[1]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[1]['entity_id']);
        $this->assertEquals(400, $creditTxnEntities[1]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[2]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[2]['entity_id']);
        $this->assertEquals(-100, $creditTxnEntities[2]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[3]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[3]['entity_id']);
        $this->assertEquals(-400, $creditTxnEntities[3]['credits_used']);

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

        $creditTxnEntities = $this->getDbEntities('credit_transaction');
        $this->assertEquals(4, $creditTxnEntities->count());

    }

    /*
     * Since this is the new credits flow, we are checking that balance is not updated
     * and remaining assertions are to check payout fees if fees - tax, tax is 0
     * banking balance is debited with just payout amount.
     */
    public function testRblReversalFailureMappingForRewardsWithNewCreditsFlow()
    {
        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 100 , 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->fixtures->create('credit_balance', ['merchant_id' => '10000000000000', 'balance' => 700 ]);

        $creditBalanceEntity = $this->getDbLastEntity('credit_balance');

        $creditBalanceBefore = $creditBalanceEntity['balance'];

        $creditEntity = $this->getDbLastEntity('credits');

        $this->fixtures->edit('credits', $creditEntity['id'], ['balance_id' => $creditBalanceEntity['id']]);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 600 , 'campaign' => 'test rewards type', 'type' => 'reward_fee', 'product' => 'banking']);

        $creditEntity = $this->getDbLastEntity('credits');

        $this->fixtures->edit('credits', $creditEntity['id'], ['balance_id' => $creditBalanceEntity['id']]);

        $channel = Channel::RBL;

        $this->setupForRblPayout($channel);

        $payout = $this->getDbLastEntity('payout');
        $balance = $this->getDbLastEntity('balance');

        $this->assertEquals(10095, $payout['amount']);
        $this->assertEquals(500, $payout['fees']);
        $this->assertEquals(0, $payout['tax']);

        $creditBalanceEntity = $this->getLastEntity('credit_balance', true);
        $this->assertEquals($creditBalanceBefore, $creditBalanceEntity['balance']);

        $creditEntities = $this->getDbEntities('credits');
        $this->assertEquals(100, $creditEntities[0]['used']);
        $this->assertEquals(400, $creditEntities[1]['used']);

        $creditTxnEntities = $this->getDbEntities('credit_transaction');
        $this->assertEquals('payout', $creditTxnEntities[0]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[0]['entity_id']);
        $this->assertEquals(100, $creditTxnEntities[0]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[1]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[1]['entity_id']);
        $this->assertEquals(400, $creditTxnEntities[1]['credits_used']);

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

        $creditBalanceEntity = $this->getLastEntity('credit_balance', true);
        $this->assertEquals($creditBalanceBefore, $creditBalanceEntity['balance']);

        $creditEntities = $this->getDbEntities('credits');
        $this->assertEquals(0, $creditEntities[0]['used']);
        $this->assertEquals(0, $creditEntities[1]['used']);

        $creditTxnEntities = $this->getDbEntities('credit_transaction');
        $this->assertEquals('payout', $creditTxnEntities[0]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[0]['entity_id']);
        $this->assertEquals(100, $creditTxnEntities[0]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[1]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[1]['entity_id']);
        $this->assertEquals(400, $creditTxnEntities[1]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[2]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[2]['entity_id']);
        $this->assertEquals(-100, $creditTxnEntities[2]['credits_used']);

        $this->assertEquals('payout', $creditTxnEntities[3]['entity_type']);
        $this->assertEquals($payout['id'],  $creditTxnEntities[3]['entity_id']);
        $this->assertEquals(-400, $creditTxnEntities[3]['credits_used']);

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

        $creditBalanceEntity = $this->getLastEntity('credit_balance', true);
        $this->assertEquals($creditBalanceBefore, $creditBalanceEntity['balance']);

        $creditTxnEntities = $this->getDbEntities('credit_transaction');
        $this->assertEquals(4, $creditTxnEntities->count());

    }

    /*
     * case when status check is done first and followed by account statement processing.
     * in status check payout is failed , but in account statement we get debit and credit row
     */
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

    public function testRblSlackAlertThrownForRecon()
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

        $mockedResponse = $this->getRblTxnCreation();

        $this->setMozartMockResponse($mockedResponse);

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->cronAuth();
        $this->startTest();

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
            Attempt\Status::FAILED);

        $this->assertEquals('created', $payout['status']);
    }

    /*
     * case when status check is done first and followed by account statement processing.
     * in status check payout is failed , but in account statement we get debit and credit row
     * credit row gets mapped to external because couldn't find existing payout via return utr
     * (as return utr webhook was not received) . later when status 'reversed' is received via status
     * check, external is deleted and corresponding bas and external's txn is mapped to reversal
     */
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

        // Fetch account statement from RBL

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

    protected function setupForRblPayout($channel = Channel::RBL, $amount = 10095, $mode = FundTransfer\Mode::IMPS)
    {
        $this->ba->privateAuth();

        $this->createContact();

        $this->createFundAccount();

        $content = [
            'account_number'  => '2224440041626905',
            'amount'          => $amount,
            'currency'        => 'INR',
            'purpose'         => 'payout',
            'narration'       => 'Rbl account payout',
            'fund_account_id' => 'fa_' . $this->fundAccount->getId(),
            'mode'            => $mode,
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

    protected function getRblDataResponseWithInvalidTransactionType()
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
                                    'txnType' => 'B'
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
        $txn['transactionSummary']['txnDesc'] = 'NEFT/000099572822/Vivek Karna HDFC                ';
        $txn['txnBalance']['amountValue'] = '300198.91';
        $txn['txnId'] = '   S55959';

        $response['data']['PayGenRes']['Body']['transactionDetails'][0] = $txn;
        unset($response['data']['PayGenRes']['Body']['transactionDetails'][1]);

        return $response;
    }

    protected function getRblPayoutMappingResponseRTGS()
    {
        $response = $this->getRblDataResponse();

        $txn = $response['data']['PayGenRes']['Body']['transactionDetails'][1];

        $txn['transactionSummary']['txnAmt']['amountValue'] = '1.04';
        $txn['transactionSummary']['txnDesc'] = 'RTGS/UTIBH20106341692/RAZORPAY SOFTWARE PRIVATE LI ';
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

        $balance = $this->getDbLastEntity('balance');

        $this->createQueuedOrPendingPayout($queuedPayoutAttributes, 'rzp_test_TheTestAuthKey');

        $this->testLatestBalanceWhenBalanceFetchCronRunsAfterBankingAccountStatementCron(70);

        $response = $this->dispatchQueuedPayouts();

        $this->assertEquals($balance['id'], $response['balance_id_list'][0]);

        $payout = $this->getDbLastEntity('payout');

        $this->assertNotEquals('queued', $payout['status']);
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

    protected function getBasicNegativeBalanceResponse()
    {
        $response = [
            'data' => [
                'PayGenRes' => [
                    'Body'      => [
                        'hasMoreData'        => 'N',
                        'transactionDetails' => [
                            [
                                'pstdDate'           => '2016-01-05T01:36:33.000',
                                'transactionSummary' => [
                                    'instrumentId' => '',
                                    'txnAmt'       => [
                                        'amountValue'  => '221.00',
                                        'currencyCode' => 'INR'
                                    ],
                                    'txnDate'      => '2016-01-05T00:00:00.000',
                                    'txnDesc'      => '123456-Z',
                                    'txnType'      => 'D'
                                ],
                                'txnBalance'         => [
                                    'currencyCode' => 'INR',
                                    'amountValue'  => '-121.00'
                                ],
                                'txnCat'             => 'TCI',
                                'txnId'              => '  S807068',
                                'txnSrlNo'           => '  49',
                                'valueDate'          => '2016-01-05T00:00:00.000'
                            ],
                        ]
                    ],
                    'Header'    => [
                        'Approver_ID' => '',
                        'Corp_ID'     => 'RAZORPAY',
                        'Error_Cde'   => '',
                        'Error_Desc'  => '',
                        'Status'      => 'SUCCESS',
                        'TranID'      => '1'
                    ],
                    'Signature' => [
                        'Signature' => 'Signature'
                    ]
                ],
            ],
            'error'             => null,
            'external_trace_id' => '',
            'mozart_id'         => 'bjt1l8jc1osqk0jtadrg',
            'next'              => [],
            'success'           => true
        ];

        return $response;
    }

    public function testRblAccountStatementNegativeBalance()
    {
        $mockedResponse = $this->getBasicNegativeBalanceResponse();

        $this->setMozartMockResponse($mockedResponse);

        $payoutAttributes = [
            'utr'             => '123456',
            'balance_id'      => $this->balance->getId(),
            'amount'          => '22100',
            'channel'         => 'rbl',
            'fees'            => '500',
            'tax'             => '90',
            'pricing_rule_id' => 'Bbg7fgaDwax04u',
        ];

        $payout = $this->fixtures->payout->createPayoutWithoutTransaction($payoutAttributes);

        $this->ba->cronAuth();

        $this->startTest();

        $basAfterTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT, true);

        $txn = $this->getDbLastEntity('transaction')->toArray();

        $payout = $this->getDbEntityById('payout', $payout->getId())->toArray();

        $balance = $this->getDbEntityById('balance', $this->balance->getId())->toArray();

        $this->assertEquals($payout['transaction_id'], $txn['id']);

        $this->assertEquals($payout['balance_id'], $txn['balance_id']);

        $this->assertEquals($txn['balance_id'], $balance['id']);

        $this->assertTrue($balance['balance'] < 0);

        $this->assertTrue($txn['balance'] < 0);

        $this->assertTrue($basAfterTest['balance'] < 0);
    }

    public function testRblAccountStatementNegativeBalanceWithExternalSource()
    {
        $mockedResponse = $this->getBasicNegativeBalanceResponse();

        $this->setMozartMockResponse($mockedResponse);

        $this->ba->cronAuth();

        $this->startTest();

        $basAfterTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT, true);

        $txn = $this->getDbLastEntity('transaction')->toArray();

        $external = $this->getDbLastEntity('external')->toArray();

        $balance = $this->getDbEntityById('balance', $this->balance->getId())->toArray();

        $this->assertEquals($external['transaction_id'], $txn['id']);

        $this->assertEquals($external['utr'], $basAfterTest['utr']);

        $this->assertEquals($external['balance_id'], $txn['balance_id']);

        $this->assertEquals($txn['balance_id'], $balance['id']);

        $this->assertTrue($balance['balance'] < 0);

        $this->assertTrue($txn['balance'] < 0);

        $this->assertTrue($basAfterTest['balance'] < 0);
    }

    public function testRblAccountStatementNegativeBalanceWithSourceReversal()
    {
        $mockedResponse = $this->getBasicNegativeBalanceResponse();

        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][0]['transactionSummary']['txnType'] = 'C';
        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][0]['transactionSummary']['txnDesc'] = 'R-123456//';
        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][0]['transactionSummary']['txnAmt']['amountValue'] ='100.00';

        $balanceId = $this->balance->getId();

        $this->setMozartMockResponse($mockedResponse);

        $payoutAttributes = [
            'utr'             => '123456',
            'balance_id'      => $this->balance->getId(),
            'amount'          => '22100',
            'channel'         => 'rbl',
            'fees'            => '500',
            'tax'             => '90',
            'pricing_rule_id' => 'Bbg7fgaDwax04u',
        ];

        $payout = $this->fixtures->create('payout',$payoutAttributes);

        $this->fixtures->edit('balance', $balanceId, [
            'balance' => -22100
        ]);

        $reversalAttributes = [
            'utr'         => '123456',
            'balance_id'  => $balanceId,
            'entity_id'   => $payout->getId(),
            'entity_type' => 'payout',
            'amount'      => '10000',
            'channel'     => 'rbl',
            'fee'         => 0,
            'tax'         => 0,
        ];

       $reversal = $this->fixtures->reversal->createReversalWithoutTransaction($reversalAttributes);

        $this->ba->cronAuth();

        $this->startTest();

        $basAfterTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT, true);

        $txn = $this->getDbLastEntity('transaction')->toArray();

        $reversal = $this->getDbLastEntity('reversal')->toArray();

        $balance = $this->getDbEntityById('balance', $this->balance->getId())->toArray();

        $this->assertEquals($reversal['transaction_id'], $txn['id']);

        $this->assertEquals($reversal['utr'], $basAfterTest['utr']);

        $this->assertEquals($reversal['balance_id'], $txn['balance_id']);

        $this->assertEquals($reversal['balance_id'], $balance['id']);

        $this->assertTrue($balance['balance'] < 0);

        $this->assertTrue($txn['balance'] < 0);

        $this->assertTrue($basAfterTest['balance'] < 0);
    }

   public function testRblAccountStatementWhenNegativeBalanceExceedsMaxLimit()
    {
        $mockedResponse = $this->getBasicNegativeBalanceResponse();

        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][0]['txnBalance']['amountValue'] = '-1000001.00';

        $this->setMozartMockResponse($mockedResponse);

        $payoutAttributes = [
            'utr'             => '123456',
            'balance_id'      => $this->balance->getId(),
            'amount'          => '22100',
            'channel'         => 'rbl',
            'fees'            => '500',
            'tax'             => '90',
            'pricing_rule_id' => 'Bbg7fgaDwax04u',
        ];

        $this->fixtures->payout->createPayoutWithoutTransaction($payoutAttributes);

        $this->fixtures->edit('balance', $this->balance->getId(), [
            'balance' => -99999900
        ]);

        $this->ba->cronAuth();

        $this->startTest();
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
            'content' => []
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
    }

    // in this first balance fetch cron is run before banking Account statement fetch Cron.
    // and then checks payout creation uses balance from cron which updated latest
    public function testCreateRblPayoutWhenBalanceFetchCronRunsBeforeBankingAccountStatementCron()
    {
        $this->testLatestBalanceWhenBalanceFetchCronRunsBeforeBankingAccountStatementCron();

        $this->setUpCounterToNotAffectPayoutFeesAndTaxInManualTimeChangeTests($this->bankingBalance);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testPayoutForRblWhenBalanceIsNegative()
    {
        $originalMozart = $this->app['mozart'];

        $this->mockMozartResponseForFetchingBalanceFromRblGateway(-300);

        // queue flag is set. Since we dont check for balance amount < or > payoutAmount when queue_if_low_balance not
        // set it goes to processing and fails at fts
        $request = [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'       => '2224440041626905',
                'amount'               => 20000,
                'currency'             => 'INR',
                'purpose'              => 'refund',
                'narration'            => 'Batman',
                'mode'                 => 'IMPS',
                'fund_account_id'      => 'fa_100000000000fa',
                'queue_if_low_balance' => 1,
                'notes'                => [
                    'abc' => 'xyz',
                ],
            ],
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('queued', $response['status']);

        $this->app->instance('mozart', $originalMozart);
    }

    public function testRblBankingAccountStatementAndPayoutWithNegativeBalance()
    {
        $this->testRblAccountStatementNegativeBalance();

        $this->mockMozartResponseForFetchingBalanceFromRblGateway(-121);

        $this->ba->privateAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'       => '2224440041626905',
                'amount'               => 20000,
                'currency'             => 'INR',
                'purpose'              => 'refund',
                'narration'            => 'Batman',
                'mode'                 => 'IMPS',
                'fund_account_id'      => 'fa_100000000000fa',
                'queue_if_low_balance' => 1,
                'notes'                => [
                    'abc' => 'xyz',
                ],
            ],
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('queued', $response['status']);

        $mockedResponse = $this->getBasicNegativeBalanceResponse();

        $this->setMozartMockResponse($mockedResponse);

        $this->ba->cronAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/process',
            'content' => [
                'account_number' => '2224440041626905',
                'channel'        => 'rbl',
            ]
        ];

        $response = $this->makeRequestAndGetContent($request);

        $expectedResponse = [
            'channel' => 'rbl',
            'account_number' => '2224440041626905',
        ];

        $this->assertEquals($expectedResponse, $response);

    }

    // in this first balance fetch cron is run after banking Account statement fetch Cron.
    // and then checks payout creation uses balance from cron which updated latest
    public function testCreateRblPayoutWhenBalanceFetchCronRunsAfterBankingAccountStatementCron()
    {
        $this->testLatestBalanceWhenBalanceFetchCronRunsAfterBankingAccountStatementCron();

        $this->setUpCounterToNotAffectPayoutFeesAndTaxInManualTimeChangeTests($this->bankingBalance);

        $this->ba->privateAuth();

        $this->startTest();
    }

    // in this first balance fetch cron is run after banking Account statement fetch Cron.
    // and then checks payout creation uses balance from cron which updated latest,but this time balance
    // is low so payout gets queued
    public function testCreateRblPayoutWhenBalanceFetchCronRunsAfterBankingAccountStatementCronWithLowBalance()
    {
        $this->testLatestBalanceWhenBalanceFetchCronRunsAfterBankingAccountStatementCron(50);

        $this->setUpCounterToNotAffectPayoutFeesAndTaxInManualTimeChangeTests($this->bankingBalance);

        $this->ba->privateAuth();

        $this->startTest();
    }

    // in this first balance fetch cron is run before banking Account statement fetch Cron.
    // and then checks payout creation uses balance from cron which updated latest,but this time balance
    // is low so payout gets queued
    public function testCreateRblPayoutWhenBalanceFetchCronRunsBeforeBankingAccountStatementCronWithLowBalance()
    {
        $this->testLatestBalanceWhenBalanceFetchCronRunsBeforeBankingAccountStatementCron(50);

        $this->setUpCounterToNotAffectPayoutFeesAndTaxInManualTimeChangeTests($this->bankingBalance);

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

        $balance = $this->getDbLastEntity('balance');

        $this->createQueuedOrPendingPayout($queuedPayoutAttributes, 'rzp_test_TheTestAuthKey');

        $this->testLatestBalanceWhenBalanceFetchCronRunsBeforeBankingAccountStatementCron(50);

        $response = $this->dispatchQueuedPayouts();

        $this->assertEquals($balance['id'], $response['balance_id_list'][0]);

        $payout = $this->getDbLastEntity('payout');

        $this->assertNotEquals('queued', $payout['status']);
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

    protected function createPayoutWithoutContactAndFACreation($amount, $mode, $faId)
    {
        $this->ba->privateAuth();

        $content = [
            'account_number'  => '2224440041626905',
            'amount'          => $amount,
            'currency'        => 'INR',
            'purpose'         => 'payout',
            'narration'       => 'Rbl account payout',
            'fund_account_id' => 'fa_' . $faId,
            'mode'            => $mode,
            'notes'           => [
                'abc' => 'xyz',
            ],
        ];

        $request = [
            'url'       => '/payouts',
            'method'    => 'POST',
            'content'   => $content
        ];

       return $this->makeRequestAndGetContent($request);
    }

    // Case when there is a payout of IMPS mode with UTR not present but cms ref no is present
    public function testAccountStatementFetchWhenUTRIsNotPresentForNonIFTModes()
    {
        $this->setupForRblPayout(Channel::RBL, 104);

        $payout1 = $this->getDbLastEntity('payout');

        $attempt1 = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout1['id'], ['status' => 'initiated',
                                                         'initiated_at' => 1451937900]);

        $this->fixtures->edit('fund_transfer_attempt', $attempt1['id'], ['cms_ref_no' => 'S55959']);

        $ftsCreateTransfer = new FtsFundTransfer(
            EnvMode::TEST,
            $attempt1['id']);

        $ftsCreateTransfer->handle();

        $attempt1 = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Attempt\Status::INITIATED, $attempt1['status']);

        $this->fixtures->edit('balance', $payout1['balance_id'], ['balance' => 30019995]);

        // fetch account statement
        $mockedResponse = $this->getRblPayoutMappingResponse();

        // set imps type regex for description
        $txn = $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][0];
        $txn['transactionSummary']['txnDesc'] = '000099572822-Vivek Karna HDFC';
        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][0] = $txn;

        $this->setMozartMockResponse($mockedResponse);

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->cronAuth();

        $this->startTest();

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $payoutTxn = $this->getDbLastEntity('transaction');
        $externalEntries = $this->getDbEntities('external', ['balance_id' => $payout1['balance_id']]);
        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals('000099572822', $basEntries[0]['utr']);

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

    //Case when there is a payout of NEFT mode with UTR not present but cms ref no is present
    public function testAccountStatementFetchWhenUTRIsNotPresentForNEFTMode()
    {
        $this->setupForRblPayout(Channel::RBL, 104, FundTransfer\Mode::NEFT);

        $payout1 = $this->getDbLastEntity('payout');

        $attempt1 = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout1['id'], ['status' => 'initiated',
                                                         'initiated_at' => 1451937900]);

        $this->fixtures->edit('fund_transfer_attempt', $attempt1['id'], ['cms_ref_no' => 'S55959']);

        $ftsCreateTransfer = new FtsFundTransfer(
            EnvMode::TEST,
            $attempt1['id']);

        $ftsCreateTransfer->handle();

        $attempt1 = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Attempt\Status::INITIATED, $attempt1['status']);

        $this->fixtures->edit('balance', $payout1['balance_id'], ['balance' => 30019995]);

        // fetch account statement
        $mockedResponse = $this->getRblPayoutMappingResponse();

        $this->setMozartMockResponse($mockedResponse);

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->cronAuth();

        $this->startTest();

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $payoutTxn = $this->getDbLastEntity('transaction');
        $externalEntries = $this->getDbEntities('external', ['balance_id' => $payout1['balance_id']]);
        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals('000099572822', $basEntries[0]['utr']);

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

    /*
     * case when status check is done first and followed by account statement processing.
     * in status check payout is reversed and we get return utr , in account statement we get debit and credit row.
     * credit row gets mapped to reversal because we find an existing payout via return utr
     */
    public function testRblReversalTxnCreationViaReturnUTR()
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

        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::REVERSED);

        $this->fixtures->edit('payout', $payout['id'], ['return_utr' => '143535']);

        // Fetch account statement from RBL

        $mockedResponse = $this->getRblTxnCreation();

        $this->setMozartMockResponse($mockedResponse);

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->cronAuth();
        $this->startTest();

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $transactions = $this->getDbEntities('transaction');
        $externalEntries = $this->getDbEntities('external', ['balance_id' => $payout['balance_id']]);
        $reversal = $this->getDbLastEntity('reversal');

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


        $this->assertEquals(EntityConstants::REVERSAL, $basEntries[2]['entity_type']);
        $this->assertEquals($reversal['id'], $basEntries[2]['entity_id']);
        $this->assertEquals($reversal['transaction_id'], $basEntries[2]['transaction_id']);
    }


    // An entry in statement has utr and CMS Ref. no. For this testcase a situation is created where the utr in statement is present as return utr of one payout
    // and CMS Ref. No. of the same statement record is linked to another payout. Hence the reversal should be created against the payout found via cms ref. no.
    // This is an arbitrary situation which should not happen on prod.
    public function testRblReversalTxnCreationViaCmsRefNoBeforeReturnUTR()
    {
        $channel = Channel::RBL;

        $this->setupForRblPayout($channel);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['cms_ref_no' => 'S807069','utr' => '123456']);

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

        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::REVERSED);

        $this->fixtures->edit('payout', $payout['id'], ['return_utr' => '143539']);

        $this->createRblPayout();
        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '143567']);

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

        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::REVERSED);

        $this->fixtures->edit('payout', $payout['id'], ['return_utr' => '143535']);

        // Fetch account statement from RBL

        $mockedResponse = $this->getRblTxnCreation();

        $this->setMozartMockResponse($mockedResponse);

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->cronAuth();
        $this->startTest();

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $transactions = $this->getDbEntities('transaction');
        $externalEntries = $this->getDbEntities('external', ['balance_id' => $payout['balance_id']]);
        $reversal = $this->getDbEntities('reversal');

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[0]['entity_type']);
        $this->assertEquals($externalEntries[0]['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($externalEntries[0]['transaction_id'], $basEntries[0]['transaction_id']);
        $this->assertEquals($externalEntries[0]['banking_account_statement_id'], $basEntries[0]['id']);
        $this->assertEquals($transactions[0]['entity_id'],$externalEntries[0]['id']);

        $payout = $this->getDbEntities('payout');

        $this->assertEquals(EntityConstants::PAYOUT, $basEntries[1]['entity_type']);
        $this->assertEquals($payout[0]['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($payout[0]['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals($transactions[1]['entity_id'],$payout[0]['id']);


        $this->assertEquals(EntityConstants::REVERSAL, $basEntries[2]['entity_type']);
        $this->assertEquals($reversal[0]['id'], $basEntries[2]['entity_id']);
        $this->assertEquals($reversal[0]['transaction_id'], $basEntries[2]['transaction_id']);
        $this->assertEquals($reversal[0]['entity_id'], $payout[0]['id']);
    }

    /*
     * case when status check processed is received first and in first account stmt fetch we get debit row.
     * Later we get status reversed via status check and reversal is created on our end . in next stmt fetch
     * we get a credit row corresponding to it and map that to reversal
     */
    public function testRblReversalTxnCreationViaExistingReversal()
    {
        $channel = Channel::RBL;

        $this->setupForRblPayout($channel);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated','return_utr' => '143535']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '123456']);

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

        // getting only debit row for payout
        unset($mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][2]);

        // fetching account statement first time
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

        // payout reversed status received via status check api . creates a reversal entity
        // which is then used while fetching account stmt.
        // flow is $existing reversal != null in processReversal while stmt fetch
        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::REVERSED);

        $payout = $this->getDbLastEntity('payout');
        $this->assertEquals('reversed', $payout['status']);

        // Fetch account statement from RBL second time
        $mockedResponse = $this->getRblTxnCreation();

        $this->setMozartMockResponse($mockedResponse);

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->cronAuth();
        $this->startTest();

        $reversal = $this->getDbLastEntity('reversal');
        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);

        $this->assertEquals(EntityConstants::REVERSAL, $basEntries[2]['entity_type']);
        $this->assertEquals($reversal['id'], $basEntries[2]['entity_id']);
        $this->assertEquals($reversal['transaction_id'], $basEntries[2]['transaction_id']);
    }

    protected function createRblPayout($amount = 10095, $mode = FundTransfer\Mode::IMPS)
    {
        $this->ba->privateAuth();

        $content = [
            'account_number'  => '2224440041626905',
            'amount'          => $amount,
            'currency'        => 'INR',
            'purpose'         => 'payout',
            'narration'       => 'Rbl account payout',
            'fund_account_id' => 'fa_' . $this->fundAccount->getId(),
            'mode'            => $mode,
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

    public function testAccountStatementLastUpdatedAtInBankingAccountsApi()
    {
        $oldDateTime = Carbon::create(2019, 07, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        // account statement fetch cron
        $mockedResponse = $this->getRblDataResponse();

        $this->setMozartMockResponse($mockedResponse);

        $this->runBankingAccountStatementFetchCron();

        $this->ba->proxyAuth();

        $request = [
            'url'     => '/banking_accounts',
            'method'  => 'GET',
            'content' => [],
        ];

        $observedResponse = $this->makeRequestAndGetContent($request);

        $observedResponse = array_filter($observedResponse['items'],function($item){
            return $item['channel'] === 'rbl';
        });

        $observedResponse = reset($observedResponse);

        $expectedResponse = [
            'id'             => 'bacc_' . 'xba00000000001',
            'channel'        => "rbl",
            'merchant_id'    => "10000000000000",
            'account_number' => "2224440041626905",
            'balance'        => [
                'id'             => $this->bankingBalance->getId(),
                'balance'        => 11355,
                'currency'       => "INR",
                'locked_balance' => 0,
            ],
            'fee_recovery_details' => [
                'outstanding_amount' => 0,
                'last_deducted_at'   => null,
            ],
            'account_statement_last_updated_at' => stringify($oldDateTime->getTimestamp()),
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $observedResponse);

        Carbon::setTestNow();
    }

    public function testStatementGenerationWithValidChannelAndFormat()
    {
        $this->addTestTransactions();

        $currentTime = time();

        $response = $this->startTest(['request' => ['content' => ['to_date' => $currentTime]]]);
    }

    public function testStatementGenerationWithInvalidChannel()
    {
        $this->addTestTransactions();

        $currentTime = time();

        $response = $this->startTest(['request' => ['content' => ['to_date' => $currentTime]]]);
    }

    public function testStatementGenerationWithInvalidFormat()
    {
        $this->addTestTransactions();

        $currentTime = time();

        $response = $this->startTest(['request' => ['content' => ['to_date' => $currentTime]]]);
    }

    /**
     * Case where request txn type is invalid
     */
    public function testRblAccountStatementWithInvalidTxnType()
    {
        $mockedResponse = $this->getRblDataResponseWithInvalidTransactionType();

        $this->setMozartMockResponse($mockedResponse);

        $baBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT, true);

        $this->assertNull($baBeforeTest[BaEntity::LAST_STATEMENT_ATTEMPT_AT]);

        $this->ba->cronAuth();

        $this->startTest();

        $baAfterTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT, true);

        $this->assertNotNull($baAfterTest[BaEntity::LAST_STATEMENT_ATTEMPT_AT]);
    }

    /**
     * Case where request txn category value is unexpected
     */
    public function testRblAccountStatementWithInvalidCategory()
    {
        $mockedResponse = $this->getRblDataResponse();

        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][0]['txnCat'] = 'CRI';

        $this->setMozartMockResponse($mockedResponse);

        $baBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT, true);

        $this->assertNull($baBeforeTest[BaEntity::LAST_STATEMENT_ATTEMPT_AT]);

        $this->ba->cronAuth();

        $this->startTest();

        $baAfterTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT, true);

        $this->assertNotNull($baAfterTest[BaEntity::LAST_STATEMENT_ATTEMPT_AT]);
    }

    public function testWebhookEventForRblAccountStatementForSuccessfulMappingToExternalAndPayout()
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

        $eventTestDataKey = 'testTransactionCreatedWebhookForSuccessfulMappingToExternal';
        $this->expectWebhookEventWithContents('transaction.created', $eventTestDataKey);

        $eventTestDataKey1 = 'testTransactionCreatedWebhookForSuccessfulMappingToPayout';
        $this->expectWebhookEventWithContents('transaction.created', $eventTestDataKey1);

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }

    /*
    * case when status check is done first and followed by account statement processing.
    * in status check payout is failed , but in account statement we get debit and credit row
    */
    public function testWebhookEventForRblAccountStatementForSuccessfulMappingToReversal()
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

        $eventTestDataKey = 'testPayoutReversedWebhookForSuccessfulMappingToReversal';

        $this->expectWebhookEventWithContents('payout.reversed', $eventTestDataKey);

        $eventTestDataKey1 = 'testTransactionCreatedWebhookForSuccessfulMappingToReversal';
        $data = & $this->testData['testTransactionCreatedWebhookForSuccessfulMappingToReversal'];
        $data['payload']['transaction']['entity']['source']['payout_id'] = 'pout_' . $payout->getId();

        $this->expectWebhookEventWithContents('transaction.created', $eventTestDataKey1);

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }

    /*
     * case when status check processed is received first and in first account stmt fetch we get debit row.
     * Later we get status reversed via status check and reversal is created on our end . in next stmt fetch
     * we get a credit row corresponding to it and map that to reversal
     */
    public function testWebhookEventForRblReversalTxnCreationViaExistingReversal()
    {
        $channel = Channel::RBL;

        $this->setupForRblPayout($channel);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated','return_utr' => '143535']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '123456']);

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

        // getting only debit row for payout
        unset($mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][2]);

        // fetching account statement first time
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

        // payout reversed status received via status check api . creates a reversal entity
        // which is then used while fetching account stmt.
        // flow is $existing reversal != null in processReversal while stmt fetch
        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::REVERSED);

        $payout = $this->getDbLastEntity('payout');
        $this->assertEquals('reversed', $payout['status']);

        // Fetch account statement from RBL second time
        $mockedResponse = $this->getRblTxnCreation();

        $this->setMozartMockResponse($mockedResponse);

        $eventTestDataKey = 'testTransactionCreatedWebhookForSuccessfulMappingToReversal';
        $data = & $this->testData['testTransactionCreatedWebhookForSuccessfulMappingToReversal'];
        $data['payload']['transaction']['entity']['source']['payout_id'] = 'pout_' . $payout->getId();
        $this->expectWebhookEventWithContents('transaction.created', $eventTestDataKey);

        $this->dontExpectWebhookEvent('payout.reversed');

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->cronAuth();
        $this->startTest();

        $reversal = $this->getDbLastEntity('reversal');
        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);

        $this->assertEquals(EntityConstants::REVERSAL, $basEntries[2]['entity_type']);
        $this->assertEquals($reversal['id'], $basEntries[2]['entity_id']);
        $this->assertEquals($reversal['transaction_id'], $basEntries[2]['transaction_id']);
    }

    public function testWebhookEventForRblAccountStatementForSuccessfulMappingToExternalWithRazorxFlagOn()
    {
        $mockedResponse = $this->getRblDataResponse();

        unset($mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][1]);

        $this->setMozartMockResponse($mockedResponse);

        $this->ba->cronAuth();

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->willReturn('on');

        $this->dontExpectWebhookEvent('transaction.created');

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }

    public function testRblPayoutManuallyMarkedAsFailed()
    {
        $channel = Channel::RBL;

        $this->setupForRblPayout($channel);

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated']);

        $request = [
            'url'       => '/payouts/' . $payout['id'] . '/manual/status',
            'method'    => 'PATCH',
            'content'   => [
                'status'         => 'failed',
                'failure_reason' => 'payout failed at bank'
            ]
        ];

        $this->ba->adminAuth();

        $this->makeRequestAndGetContent($request);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals('failed', $payout['status']);
    }

    protected function createBankingAccountSetLastAttemptedAtSetBalanceAsDirectBanking(string $id, string $accountNumber, string $merchantId , int $lastAttemptedAt)
    {
        $balance = $this->getDbEntity('balance', ['merchant_id' => $merchantId]);
        $balanceId = $balance->getId();

        $this->fixtures->edit('balance', $balanceId, [
            'account_type' => 'direct',
            'type'         => 'banking'
        ]);

        $this->createBankingAccount([
            'id'                    => $id,
            'account_number'        => $accountNumber,
            'account_type'          => 'current',
            'merchant_id'           => $merchantId,
            'channel'               => 'rbl',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
            'balance_id'            => $balanceId,
            'status'                => 'activated'
        ]);

        $this->fixtures->edit('banking_account', $id, [
            'last_statement_attempt_at'=> $lastAttemptedAt
        ]);
    }

    protected function createRBLBankingDirectBalance(string $merchantId , string $accountNumber)
    {
        $this->fixtures->create(
            'balance',
            [
                'type'             => 'banking',
                'merchant_id'      => $merchantId,
                'balance'          => 10000000,
                'account_type'     => 'direct',
                'channel'          => 'rbl',
                'account_number'   => $accountNumber
            ]);
    }

    protected function createPayoutForBalanceTypeDirectAndBanking(string $id, string $merchantId , int $payoutTime)
    {
        $balance = $this->getDbEntity('balance', ['merchant_id' => $merchantId]);
        $balanceId = $balance->getId();

        $this->fixtures->edit('balance', $balanceId, [
            'account_type' => NULL,
            'type'         => 'primary',
            'balance'      => 10000
        ]);

        $this->fixtures->create(
            'payout',
            [
                'id'                =>      $id ,
                'merchant_id'       =>      $merchantId,
                'balance_id'        =>      $balanceId,
                'amount'            =>      0,
                'currency'          =>      'inr',
                'fees'              =>      0,
                'tax'               =>      0,
                'status'            =>      'processed',
                'type'              =>      'default',
                'created_at'        =>      $payoutTime,
                'updated_at'        =>      $payoutTime,
                'pricing_rule_id'   =>      '1nvp2XPMmaRLxb',
            ]);

        $this->fixtures->edit('balance', $balanceId, [
            'account_type' => 'direct',
            'type'         => 'banking'
        ]);
    }

    public function testLimitAndEightHourRuleForBASFetch()
    {
        $currentTime = Carbon::now()->getTimestamp();

        $this->fixtures->edit('banking_account','xba00000000001', ['account_number'=>'2323230041626901','last_statement_attempt_at'=> $currentTime-9]);

        $this->createRBLBankingDirectBalance('10000000000011','2323230041626907');
        $this->createRBLBankingDirectBalance('10000000000012','2323230041626908');
        $this->createRBLBankingDirectBalance('10000000000013','2323230041626909');
        $this->createRBLBankingDirectBalance('10000000000014','2323230041626910');

        $this->createBankingAccountSetLastAttemptedAtSetBalanceAsDirectBanking('xbacc000000002','2323230041626902','1cXSLlUU8V9sXl',$currentTime-8);
        $this->createBankingAccountSetLastAttemptedAtSetBalanceAsDirectBanking('xbacc000000003','2323230041626903','100000Razorpay',$currentTime-13*60*60);
        $this->createBankingAccountSetLastAttemptedAtSetBalanceAsDirectBanking('xbacc000000004','2323230041626904','100AtomAccount',$currentTime-12*60*60);
        $this->createBankingAccountSetLastAttemptedAtSetBalanceAsDirectBanking('xbacc000000005','2323230041626905','10NodalAccount',$currentTime-11*60*60);
        $this->createBankingAccountSetLastAttemptedAtSetBalanceAsDirectBanking('xbacc000000006','2323230041626906','1ApiFeeAccount',$currentTime-10*60*60);
        $this->createBankingAccountSetLastAttemptedAtSetBalanceAsDirectBanking('xbacc000000007','2323230041626907','10000000000011',$currentTime-9*60*60);
        $this->createBankingAccountSetLastAttemptedAtSetBalanceAsDirectBanking('xbacc000000008','2323230041626908','10000000000012',$currentTime-8*60*60);
        $this->createBankingAccountSetLastAttemptedAtSetBalanceAsDirectBanking('xbacc000000009','2323230041626909','10000000000013',$currentTime-7*60*60);
        $this->createBankingAccountSetLastAttemptedAtSetBalanceAsDirectBanking('xbacc000000010','2323230041626910','10000000000014',$currentTime-7*60*60);

        $this->createPayoutForBalanceTypeDirectAndBanking('00000000000001' , '10NodalAccount' , $currentTime-1);
        $this->createPayoutForBalanceTypeDirectAndBanking('00000000000002' , '10000000000014' , $currentTime);

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testLimitForEightHourRuleAndAccountsThatMadePayoutsForBASFetch()
    {
        $currentTime = Carbon::now()->getTimestamp();

        $this->fixtures->edit('banking_account','xba00000000001', ['account_number'=>'2323230041626901','last_statement_attempt_at'=> $currentTime-9]);

        $this->createRBLBankingDirectBalance('10000000000011','2323230041626907');
        $this->createRBLBankingDirectBalance('10000000000012','2323230041626908');
        $this->createRBLBankingDirectBalance('10000000000013','2323230041626909');
        $this->createRBLBankingDirectBalance('10000000000014','2323230041626910');

        $this->createBankingAccountSetLastAttemptedAtSetBalanceAsDirectBanking('xbacc000000002','2323230041626902','1cXSLlUU8V9sXl',$currentTime-8);
        $this->createBankingAccountSetLastAttemptedAtSetBalanceAsDirectBanking('xbacc000000003','2323230041626903','100000Razorpay',$currentTime-13*60*60);
        $this->createBankingAccountSetLastAttemptedAtSetBalanceAsDirectBanking('xbacc000000004','2323230041626904','100AtomAccount',$currentTime-12*60*60);
        $this->createBankingAccountSetLastAttemptedAtSetBalanceAsDirectBanking('xbacc000000005','2323230041626905','10NodalAccount',$currentTime-8*60*60);
        $this->createBankingAccountSetLastAttemptedAtSetBalanceAsDirectBanking('xbacc000000006','2323230041626906','1ApiFeeAccount',$currentTime-7*60*60);
        $this->createBankingAccountSetLastAttemptedAtSetBalanceAsDirectBanking('xbacc000000007','2323230041626907','10000000000011',$currentTime-1*60*60);
        $this->createBankingAccountSetLastAttemptedAtSetBalanceAsDirectBanking('xbacc000000008','2323230041626908','10000000000012',$currentTime-3*60*60);
        $this->createBankingAccountSetLastAttemptedAtSetBalanceAsDirectBanking('xbacc000000009','2323230041626909','10000000000013',$currentTime-2*60*60);
        $this->createBankingAccountSetLastAttemptedAtSetBalanceAsDirectBanking('xbacc000000010','2323230041626910','10000000000014',$currentTime-1*60*60);

        $this->createPayoutForBalanceTypeDirectAndBanking('00000000000001' , '1ApiFeeAccount' , $currentTime-1);
        $this->createPayoutForBalanceTypeDirectAndBanking('00000000000002' , '100AtomAccount' , $currentTime-100);
        $this->createPayoutForBalanceTypeDirectAndBanking('00000000000003' , '10000000000014' , $currentTime-10);
        $this->createPayoutForBalanceTypeDirectAndBanking('00000000000004' , '10000000000013' , $currentTime-10);
        $this->createPayoutForBalanceTypeDirectAndBanking('00000000000005' , '10000000000012' , $currentTime-10);

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testLimitForAccountsThatMadePayoutsAndOtherAccountsForBASFetch()
    {
        $currentTime = Carbon::now()->getTimestamp();

        $this->fixtures->edit('banking_account','xba00000000001', ['account_number'=>'2323230041626901','last_statement_attempt_at'=> $currentTime-9]);

        $this->createRBLBankingDirectBalance('10000000000011','2323230041626907');
        $this->createRBLBankingDirectBalance('10000000000012','2323230041626908');
        $this->createRBLBankingDirectBalance('10000000000013','2323230041626909');
        $this->createRBLBankingDirectBalance('10000000000014','2323230041626910');

        $this->createBankingAccountSetLastAttemptedAtSetBalanceAsDirectBanking('xbacc000000002','2323230041626902','1cXSLlUU8V9sXl',$currentTime-8);
        $this->createBankingAccountSetLastAttemptedAtSetBalanceAsDirectBanking('xbacc000000003','2323230041626903','100000Razorpay',$currentTime-7);
        $this->createBankingAccountSetLastAttemptedAtSetBalanceAsDirectBanking('xbacc000000004','2323230041626904','100AtomAccount',$currentTime-6);
        $this->createBankingAccountSetLastAttemptedAtSetBalanceAsDirectBanking('xbacc000000005','2323230041626905','10NodalAccount',$currentTime-5);
        $this->createBankingAccountSetLastAttemptedAtSetBalanceAsDirectBanking('xbacc000000006','2323230041626906','1ApiFeeAccount',$currentTime-4);
        $this->createBankingAccountSetLastAttemptedAtSetBalanceAsDirectBanking('xbacc000000007','2323230041626907','10000000000011',$currentTime-3);
        $this->createBankingAccountSetLastAttemptedAtSetBalanceAsDirectBanking('xbacc000000008','2323230041626908','10000000000012',$currentTime-2);
        $this->createBankingAccountSetLastAttemptedAtSetBalanceAsDirectBanking('xbacc000000009','2323230041626909','10000000000013',$currentTime-1);
        $this->createBankingAccountSetLastAttemptedAtSetBalanceAsDirectBanking('xbacc000000010','2323230041626910','10000000000014',$currentTime-0);

        $this->createPayoutForBalanceTypeDirectAndBanking('00000000000001' , '10NodalAccount' , $currentTime-1);
        $this->createPayoutForBalanceTypeDirectAndBanking('00000000000002' , '10000000000014' , $currentTime);

        $this->ba->appAuth();

        $this->startTest();
    }

    /*
    * mapping debit record. found more than 1 unlinked payout with same utr -
    * assert remarks and non failure of acc stmt and creation of external with remarks
    * reference test cases list -
    * https://docs.google.com/spreadsheets/d/10327ImrYtC0MSRoQ-KDUGJmttSz_jXYclnzmi1ITKbE/edit#gid=0
    */
    public function testRblAccountStatementWithMoreThanOneExistingUnlinkedPayoutWithSameUtr()
    {
        $channel = Channel::RBL;

        $this->setupForRblPayout($channel);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated','utr' => '123456']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '123456']);

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

        // create second payout with same utr
        $this->createRblPayout();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated', 'utr' => '123456']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '123456']);

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

        // getting only debit row for payout
        unset($mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][2]);

        // fetching account statement first time
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

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[1]['entity_type']);
        $this->assertEquals($externalEntries[1]['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($externalEntries[1]['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals('multiple unlinked payouts found with same utr for debit mapping', $externalEntries[1]['remarks']);
        $this->assertEquals($transactions[1]['entity_id'],$externalEntries[1]['id']);
    }

    /*
     * case - mapping debit record. found 2 payouts with same utr but one of them is already linked
     */
    public function testRblAccountStatementWithTwoExistingPayoutWithSameUtrWithOneHavingTxnLinked()
    {
        $channel = Channel::RBL;

        $this->setupForRblPayout($channel);

        $payout1 = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout1['fees']);
        $this->assertEquals(90, $payout1['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout1['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout1['id'], ['status' => 'initiated', 'utr' => '123456']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '123456']);

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

        $payout1 = $this->getDbLastEntity('payout');
        $this->assertEquals('processed', $payout1['status']);

        // create 1 more payout with same utr but with txn linked to it
        $attributes = [
            'id'          => 'D6XmrTjmvvZDDx',
            'merchant_id' => '10000000000000',
            'amount'      => 10095,
            'balance_id'  => $this->bankingBalance->getId(),
            'utr'         => '123456',
            'status'      => 'processed'
        ];

        $payout = $this->fixtures->payout->createPayoutWithoutTransaction($attributes);

        $attributes = [
            'merchant_id' => '10000000000000',
            'amount'      => 10095,
            'balance_id'  => $this->bankingBalance->getId(),
        ];

        $txn = $this->fixtures->create('transaction', $attributes);

        $payout->transaction()->associate($txn);

        $payout->save();

        $mockedResponse = $this->getRblTxnCreation();

        // getting only debit row for payout
        unset($mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][2]);

        // fetching account statement first time
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
        $this->assertEquals($transactions[1]['entity_id'],$externalEntries[0]['id']);

        $payout = $this->getDbEntityById('payout', $payout1->getId());

        $this->assertEquals(EntityConstants::PAYOUT, $basEntries[1]['entity_type']);
        $this->assertEquals($payout['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($payout['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals($transactions[2]['entity_id'],$payout['id']);
    }

    /*
  * case - mapping debit record. found 2 payouts with same cms_ref_no for non ift mode
  */
    public function testRblAccountStatementWithMoreThanOnePayoutWithSameCmsRefNoForDebitMapping()
    {
        $channel = Channel::RBL;

        $this->setupForRblPayout($channel);

        $payout1 = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout1['fees']);
        $this->assertEquals(90, $payout1['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout1['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout1['id'], ['status' => 'initiated']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['cms_ref_no' => 'S807068']);

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

        // create second payout with same cms ref no
        $this->createRblPayout();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['cms_ref_no' => 'S807068']);

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

        // Fetch account statement from RBL
        $mockedResponse = $this->getRblTxnCreation();

        // getting only debit row for payout
        unset($mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][2]);

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

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[1]['entity_type']);
        $this->assertEquals($externalEntries[1]['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($externalEntries[1]['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals($externalEntries[1]['remarks'],
                            'multiple payouts found with same cms ref no for non IFT for debit mapping');
        $this->assertEquals($transactions[1]['entity_id'],$externalEntries[1]['id']);
    }

    /*
    * case - mapping debit record. found 2 payouts with same cms_ref_no for ift mode
    */
    public function testRblAccountStatementWithMoreThanOnePayoutWithSameCmsRefNoForIFTForDebitMapping()
    {
        $channel = Channel::RBL;

        $this->setupForRblPayout($channel);

        $payout1 = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout1['fees']);
        $this->assertEquals(90, $payout1['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout1['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout1['id'], ['status' => 'initiated',
                                                        'mode' => FundTransfer\Mode::IFT,
                                                        'initiated_at' => 1451937900]);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['cms_ref_no' => 'S807068', 'mode' => FundTransfer\Mode::IFT]);

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

        // create second payout with same cms ref no
        $this->createRblPayout();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated',
                                                        'mode' => FundTransfer\Mode::IFT,
                                                        'initiated_at' => 1451937900]);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['cms_ref_no' => 'S807068', 'mode' => FundTransfer\Mode::IFT]);

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

        // Fetch account statement from RBL
        $mockedResponse = $this->getRblTxnCreation();

        // getting only debit row for payout
        unset($mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][2]);

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

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[1]['entity_type']);
        $this->assertEquals($externalEntries[1]['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($externalEntries[1]['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals($externalEntries[1]['remarks'],
                            'multiple payouts found with same cms ref no for IFT for debit mapping');
        $this->assertEquals($transactions[1]['entity_id'],$externalEntries[1]['id']);
    }

    /*
     * case - More than 1 existing unlinked reversal with same utr
     */
    public function testRblAccountStatementWithMoreThanOneExistingUnlinkedReversalWithSameUtr()
    {
        $channel = Channel::RBL;

        $this->setupForRblPayout($channel);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated','return_utr' => '143535']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '123456']);

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

        // getting only debit row for payout
        unset($mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][2]);

        // fetching account statement first time
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

        // payout reversed status received via status check api . creates a reversal entity
        // which is then used while fetching account stmt.
        // flow is $existing reversal != null in processReversal while stmt fetch
        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::REVERSED);

        $payout = $this->getDbLastEntity('payout');
        $payout->reversal->toArray();
        $this->assertEquals('reversed', $payout['status']);

        $attributes = [
            'merchant_id' => '10000000000000',
            'amount'      => 10095,
            'balance_id'  => $this->bankingBalance->getId(),
            'utr'         => 143535,
            'entity_id'   => 'D6XmrTjmvvZDDx',
            'entity_type' => 'payout'
        ];

        // create another unlinked reversal that already exists with same utr
        $reversal = $this->fixtures->reversal->createReversalWithoutTransaction($attributes);

        // Fetch account statement from RBL second time
        $mockedResponse = $this->getRblTxnCreation();

        $this->setMozartMockResponse($mockedResponse);

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->cronAuth();
        $this->startTest();

        $external = $this->getDbLastEntity('external');
        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[2]['entity_type']);
        $this->assertEquals($external['remarks'], 'multiple unlinked reversals with same utr 143535');

        $this->assertEquals($external['id'], $basEntries[2]['entity_id']);
        $this->assertEquals($external['transaction_id'], $basEntries[2]['transaction_id']);
    }

    /*
     * case - 2 existing reversal with same utr but one of them is already linked
     */
    public function testRblAccountStatementWithTwoExistingReversalWithSameUtrWithOneHavingTxnLinked()
    {
        $channel = Channel::RBL;

        $this->setupForRblPayout($channel);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated','return_utr' => '143535']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '123456']);

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

        // getting only debit row for payout
        unset($mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][2]);

        // fetching account statement first time
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

        // payout reversed status received via status check api . creates a reversal entity
        // which is then used while fetching account stmt.
        // flow is $existing reversal != null in processReversal while stmt fetch
        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::REVERSED);

        $payout = $this->getDbLastEntity('payout');
        $payout->reversal->toArray();
        $this->assertEquals('reversed', $payout['status']);

        $attributes = [
            'id'          => 'D6XmrTjmvvZDDx',
            'merchant_id' => '10000000000000',
            'amount'      => 10095,
            'balance_id'  => $this->bankingBalance->getId(),
        ];

        $this->fixtures->payout->createPayoutWithoutTransaction($attributes);

        $attributes = [
            'id'          => 'D6XmrTjmvvZDDy',
            'merchant_id' => '10000000000000',
            'amount'      => 10095,
            'balance_id'  => $this->bankingBalance->getId(),
            'utr'         => 143535,
            'entity_id'   => 'D6XmrTjmvvZDDx',
            'entity_type' => 'payout',
            'channel'     => 'rbl',
        ];

        // create another reversal that already exists with same utr but with txn linked
        $reversal = $this->fixtures->reversal->createPayoutReversal($attributes);

        $attributes = [
            'merchant_id' => '10000000000000',
            'amount'      => 10095,
            'balance_id'  => $this->bankingBalance->getId(),
        ];

        $txn = $this->fixtures->create('transaction', $attributes);

        $reversal->transaction()->associate($txn);

        $reversal->save();

        // Fetch account statement from RBL second time
        $mockedResponse = $this->getRblTxnCreation();

        $this->setMozartMockResponse($mockedResponse);

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->cronAuth();
        $this->startTest();

        $reversal = $this->getDbLastEntity('reversal');
        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);

        $this->assertEquals(EntityConstants::REVERSAL, $basEntries[2]['entity_type']);
        $this->assertEquals($reversal['id'], $basEntries[2]['entity_id']);
        $this->assertEquals($reversal['transaction_id'], $basEntries[2]['transaction_id']);
    }

    /*
   * case - no existing reversal found now trying to map via payout . 2 payouts with same return utr found
   */
    public function testRblAccountStatementWithMoreThanOnePayoutWithSameReturnUtrForCreditMapping()
    {
        $channel = Channel::RBL;

        $this->setupForRblPayout($channel);

        $payout1 = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout1['fees']);
        $this->assertEquals(90, $payout1['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout1['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout1['id'], ['status' => 'initiated']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '123456']);

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

        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::REVERSED);

        $this->fixtures->edit('payout', $payout['id'], ['return_utr' => '143535']);

        // create another payout and reverse it with same return utr
        $this->createRblPayout();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '123457']);

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

        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::REVERSED);

        $this->fixtures->edit('payout', $payout['id'], ['return_utr' => '143535']);

        // Fetch account statement from RBL

        $mockedResponse = $this->getRblTxnCreation();

        $this->setMozartMockResponse($mockedResponse);

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->cronAuth();
        $this->startTest();

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $transactions = $this->getDbEntities('transaction');
        $externalEntries = $this->getDbEntities('external', ['balance_id' => $payout['balance_id']]);
        $reversal = $this->getDbLastEntity('reversal');

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[0]['entity_type']);
        $this->assertEquals($externalEntries[0]['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($externalEntries[0]['transaction_id'], $basEntries[0]['transaction_id']);
        $this->assertEquals($externalEntries[0]['banking_account_statement_id'], $basEntries[0]['id']);
        $this->assertEquals($transactions[0]['entity_id'],$externalEntries[0]['id']);

        $payout = $this->getDbEntities('payout');

        $this->assertEquals(EntityConstants::PAYOUT, $basEntries[1]['entity_type']);
        $this->assertEquals($payout[0]['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($payout[0]['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals($transactions[1]['entity_id'],$payout[0]['id']);


        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[2]['entity_type']);
        $this->assertEquals($externalEntries[1]['id'], $basEntries[2]['entity_id']);
        $this->assertEquals($externalEntries[1]['remarks'], 'multiple payouts found with same return utr 143535 for credit mapping');
        $this->assertEquals($externalEntries[1]['transaction_id'], $basEntries[2]['transaction_id']);
    }

    /*
  * case - no existing reversal found trying to map via payout .now 2 payouts with same utr found
  */
    public function testRblAccountStatementWithMoreThanOnePayoutWithSameUtrForCreditMapping()
    {
        $channel = Channel::RBL;

        $this->setupForRblPayout($channel);

        $payout1 = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout1['fees']);
        $this->assertEquals(90, $payout1['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout1['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout1['id'], ['status' => 'initiated']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '123456']);

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

        $this->fixtures->edit('payout', $payout['id'], ['utr' => '123456']);

        // create second payout and reverse it with same utr
        $this->createRblPayout();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '123457']);

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

        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::REVERSED);

        $this->fixtures->edit('payout', $payout['id'], ['utr' => '143535']);

        // create third payout and reverse it with same return utr
        $this->createRblPayout();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '123457']);

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

        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::REVERSED);

        $this->fixtures->edit('payout', $payout['id'], ['utr' => '143535']);

        // Fetch account statement from RBL

        $mockedResponse = $this->getRblTxnCreation();

        $this->setMozartMockResponse($mockedResponse);

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->cronAuth();
        $this->startTest();

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $transactions = $this->getDbEntities('transaction');
        $externalEntries = $this->getDbEntities('external', ['balance_id' => $payout['balance_id']]);
        $reversal = $this->getDbLastEntity('reversal');

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[0]['entity_type']);
        $this->assertEquals($externalEntries[0]['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($externalEntries[0]['transaction_id'], $basEntries[0]['transaction_id']);
        $this->assertEquals($externalEntries[0]['banking_account_statement_id'], $basEntries[0]['id']);
        $this->assertEquals($transactions[0]['entity_id'],$externalEntries[0]['id']);

        $payout = $this->getDbEntities('payout');

        $this->assertEquals(EntityConstants::PAYOUT, $basEntries[1]['entity_type']);
        $this->assertEquals($payout[0]['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($payout[0]['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals($transactions[1]['entity_id'],$payout[0]['id']);


        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[2]['entity_type']);
        $this->assertEquals($externalEntries[1]['id'], $basEntries[2]['entity_id']);
        $this->assertEquals($externalEntries[1]['remarks'], 'multiple payouts found with same utr for credit mapping');
        $this->assertEquals($externalEntries[1]['transaction_id'], $basEntries[2]['transaction_id']);
    }

    /*
  * case - no existing reversal found trying to map via payout .now 2 payouts with same cms_ref_no for non ift mode
  */
    public function testRblAccountStatementWithMoreThanOnePayoutWithSameCmsRefNoForCreditMapping()
    {
        $channel = Channel::RBL;

        $this->setupForRblPayout($channel);

        $payout1 = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout1['fees']);
        $this->assertEquals(90, $payout1['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout1['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout1['id'], ['status' => 'initiated']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '123456']);

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

        $this->fixtures->edit('payout', $payout['id'], ['utr' => '123456']);

        // create second payout and reverse it
        $this->createRblPayout();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['cms_ref_no' => 'S807069']);

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

        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::REVERSED);

        // create third payout and reverse it with same cms ref no
        $this->createRblPayout();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['cms_ref_no' => 'S807069']);

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

        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::REVERSED);

        // Fetch account statement from RBL

        $mockedResponse = $this->getRblTxnCreation();

        $this->setMozartMockResponse($mockedResponse);

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->cronAuth();
        $this->startTest();

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $transactions = $this->getDbEntities('transaction');
        $externalEntries = $this->getDbEntities('external', ['balance_id' => $payout['balance_id']]);
        $reversal = $this->getDbLastEntity('reversal');

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[0]['entity_type']);
        $this->assertEquals($externalEntries[0]['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($externalEntries[0]['transaction_id'], $basEntries[0]['transaction_id']);
        $this->assertEquals($externalEntries[0]['banking_account_statement_id'], $basEntries[0]['id']);
        $this->assertEquals($transactions[0]['entity_id'],$externalEntries[0]['id']);

        $payout = $this->getDbEntities('payout');

        $this->assertEquals(EntityConstants::PAYOUT, $basEntries[1]['entity_type']);
        $this->assertEquals($payout[0]['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($payout[0]['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals($transactions[1]['entity_id'],$payout[0]['id']);


        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[2]['entity_type']);
        $this->assertEquals($externalEntries[1]['id'], $basEntries[2]['entity_id']);
        $this->assertEquals($externalEntries[1]['remarks'],
                            'multiple payouts found with same cms ref no for non IFT for credit mapping');
        $this->assertEquals($externalEntries[1]['transaction_id'], $basEntries[2]['transaction_id']);
    }

    /*
   * case - no existing reversal found trying to map via payout . 2 payouts with same cms ref no for ift mode
   */
    public function testRblAccountStatementWithMoreThanOnePayoutWithSameCmsRefNoForIFTForCreditMapping()
    {
        $channel = Channel::RBL;

        $this->setupForRblPayout($channel);

        $payout1 = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout1['fees']);
        $this->assertEquals(90, $payout1['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout1['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout1['id'], ['status' => 'initiated']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '123456']);

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

        $this->fixtures->edit('payout', $payout['id'], ['utr' => '123456']);

        // create second payout and reverse it
        $this->createRblPayout();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated',
                                                        'mode' => FundTransfer\Mode::IFT,
                                                        'initiated_at' => 1451937900]);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['cms_ref_no' => 'S807069', 'mode' => FundTransfer\Mode::IFT]);

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

        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::REVERSED);

        // create third payout and reverse it with same cms ref no
        $this->createRblPayout();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated',
                                                        'mode' => FundTransfer\Mode::IFT,
                                                        'initiated_at' => 1451937900]);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['cms_ref_no' => 'S807069', 'mode' => FundTransfer\Mode::IFT]);

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

        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::REVERSED);

        // Fetch account statement from RBL

        $mockedResponse = $this->getRblTxnCreation();

        $this->setMozartMockResponse($mockedResponse);

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->cronAuth();
        $this->startTest();

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $transactions = $this->getDbEntities('transaction');
        $externalEntries = $this->getDbEntities('external', ['balance_id' => $payout['balance_id']]);
        $reversal = $this->getDbLastEntity('reversal');

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[0]['entity_type']);
        $this->assertEquals($externalEntries[0]['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($externalEntries[0]['transaction_id'], $basEntries[0]['transaction_id']);
        $this->assertEquals($externalEntries[0]['banking_account_statement_id'], $basEntries[0]['id']);
        $this->assertEquals($transactions[0]['entity_id'],$externalEntries[0]['id']);

        $payout = $this->getDbEntities('payout');

        $this->assertEquals(EntityConstants::PAYOUT, $basEntries[1]['entity_type']);
        $this->assertEquals($payout[0]['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($payout[0]['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals($transactions[1]['entity_id'],$payout[0]['id']);


        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[2]['entity_type']);
        $this->assertEquals($externalEntries[1]['id'], $basEntries[2]['entity_id']);
        $this->assertEquals($externalEntries[1]['remarks'],
                            'multiple payouts found with same cms ref no for IFT for credit mapping');
        $this->assertEquals($externalEntries[1]['transaction_id'], $basEntries[2]['transaction_id']);
    }

    //payout is created in initiated state and then acc statement fetch is done .
    //since there is not utr hence it gets linked to external and then manually updated via admin action
    public function testRblSourceUpdateFromInitiatedToProcessed()
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

        $mockedResponse = $this->getRblDataResponse();

        $this->setMozartMockResponse($mockedResponse);

        $this->ba->cronAuth();

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $transactions = $this->getDbEntities('transaction');
        $externalEntries = $this->getDbEntities('external', ['balance_id' => $payout['balance_id']]);
        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[0]['entity_type']);
        $this->assertEquals($externalEntries[0]['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($externalEntries[0]['transaction_id'], $basEntries[0]['transaction_id']);
        $this->assertEquals($externalEntries[0]['banking_account_statement_id'], $basEntries[0]['id']);
        $this->assertEquals($transactions[0]['entity_id'],$externalEntries[0]['id']);

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[1]['entity_type']);
        $this->assertEquals($externalEntries[1]['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($externalEntries[1]['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals($externalEntries[1]['banking_account_statement_id'], $basEntries[1]['id']);
        $this->assertEquals($transactions[1]['entity_id'],$externalEntries[1]['id']);

        $this->ba->adminAuth();

        $request = [
            'url'     => '/banking_account_statement/source/update/validate',
            'method'  => 'POST',
            'content' => [
                'payout_id'     => $payout['id'],
                'debit_bas_id'  => $basEntries[1]['id'],
                'end_status'    => 'processed'],
        ];

        $content = $this->makeRequestAndGetContent($request);

        $request = [
            'url'     => '/banking_account_statement/source/update',
            'method'  => 'POST',
            'content' => [
                'payout_id'     => $payout['id'],
                'debit_bas_id'  => $basEntries[1]['id'],
                'end_status'    => 'processed'],
        ];

        $content = $this->makeRequestAndGetContent($request);

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $transactions = $this->getDbEntities('transaction');
        $externalEntries = $this->getDbEntities('external', ['balance_id' => $payout['balance_id']]);

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[0]['entity_type']);
        $this->assertEquals($externalEntries[0]['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($externalEntries[0]['transaction_id'], $basEntries[0]['transaction_id']);
        $this->assertEquals($externalEntries[0]['banking_account_statement_id'], $basEntries[0]['id']);
        $this->assertEquals($transactions[0]['entity_id'],$externalEntries[0]['id']);

        $payout = $this->getDbEntities('payout');

        $this->assertEquals(EntityConstants::PAYOUT, $basEntries[1]['entity_type']);
        $this->assertEquals($payout[0]['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($payout[0]['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals($transactions[1]['entity_id'],$payout[0]['id']);

        $this->assertEquals('processed', $payout[0]['status']);
        $this->assertNotNull($payout[0]['transaction_id']);
    }

    // payout is created in initiated state and then acc statement fetch is done .
    // since there is not utr hence both debit and credit record
    // gets linked to external and then manually updated via admin action
    public function testRblSourceUpdateFromInitiatedToReversed()
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

        // Update status
        $ftsCreateTransfer = new FtsFundTransfer(
            EnvMode::TEST,
            $attempt['id']);

        $ftsCreateTransfer->handle();

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Attempt\Status::INITIATED, $attempt['status']);

        // Fetch account statement from RBL

        $mockedResponse = $this->getRblTxnCreation();

        $this->setMozartMockResponse($mockedResponse);

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->cronAuth();
        $this->startTest();

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $transactions = $this->getDbEntities('transaction');
        $externalEntries = $this->getDbEntities('external', ['balance_id' => $payout['balance_id']]);
        $reversal = $this->getDbLastEntity('reversal');

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[0]['entity_type']);
        $this->assertEquals($externalEntries[0]['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($externalEntries[0]['transaction_id'], $basEntries[0]['transaction_id']);
        $this->assertEquals($externalEntries[0]['banking_account_statement_id'], $basEntries[0]['id']);
        $this->assertEquals($transactions[0]['entity_id'],$externalEntries[0]['id']);

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[1]['entity_type']);
        $this->assertEquals($externalEntries[1]['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($externalEntries[1]['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals($externalEntries[1]['banking_account_statement_id'], $basEntries[1]['id']);
        $this->assertEquals($transactions[1]['entity_id'],$externalEntries[1]['id']);

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[2]['entity_type']);
        $this->assertEquals($externalEntries[2]['id'], $basEntries[2]['entity_id']);
        $this->assertEquals($externalEntries[2]['transaction_id'], $basEntries[2]['transaction_id']);
        $this->assertEquals($externalEntries[2]['banking_account_statement_id'], $basEntries[2]['id']);
        $this->assertEquals($transactions[2]['entity_id'],$externalEntries[2]['id']);

        $this->ba->adminAuth();

        $request = [
            'url'     => '/banking_account_statement/source/update/validate',
            'method'  => 'POST',
            'content' => [
                'payout_id'     => $payout['id'],
                'debit_bas_id'  => $basEntries[1]['id'],
                'credit_bas_id' => $basEntries[2]['id'],
                'end_status'    => 'reversed'],
        ];

        $content = $this->makeRequestAndGetContent($request);

        $request = [
            'url'     => '/banking_account_statement/source/update',
            'method'  => 'POST',
            'content' => [
                'payout_id'     => $payout['id'],
                'debit_bas_id'  => $basEntries[1]['id'],
                'credit_bas_id' => $basEntries[2]['id'],
                'end_status'    => 'reversed'],
        ];

        $content = $this->makeRequestAndGetContent($request);

        $expectedContent = [
            'payout' => [
                'id'       => "pout_" . $payout['id'],
                'entity'   => "payout",
                'amount'   => 10095,
                'currency' => "INR",
            ],
            'reversal'             => [
                'entity'    => "reversal",
                'payout_id' => "pout_" . $payout['id'],
                'amount'    => 10095,
                'fee'       => 0,
                'tax'       => 0,
            ],
            'payout_transaction'   => [
                'entity_id' => "pout_" . $payout['id'],
                'type'      => "payout",
                'debit'     => 10095,
                'credit'    => 0,
                'amount'    => 10095,
            ],
            'reversal_transaction' => [
                'type'   => "reversal",
                'debit'  => 0,
                'credit' => 10095,
                'amount' => 10095,
            ]
        ];

        $this->assertArraySelectiveEquals($expectedContent, $content);

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $transactions = $this->getDbEntities('transaction');
        $externalEntries = $this->getDbEntities('external', ['balance_id' => $payout['balance_id']]);
        $payout = $this->getDbEntities('payout');
        $reversal = $this->getDbLastEntity('reversal');

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[0]['entity_type']);
        $this->assertEquals($externalEntries[0]['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($externalEntries[0]['transaction_id'], $basEntries[0]['transaction_id']);
        $this->assertEquals($externalEntries[0]['banking_account_statement_id'], $basEntries[0]['id']);
        $this->assertEquals($transactions[0]['entity_id'],$externalEntries[0]['id']);

        $this->assertEquals(EntityConstants::PAYOUT, $basEntries[1]['entity_type']);
        $this->assertEquals($payout[0]['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($payout[0]['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals($transactions[1]['entity_id'],$payout[0]['id']);

        $this->assertEquals(EntityConstants::REVERSAL, $basEntries[2]['entity_type']);
        $this->assertEquals($reversal['id'], $basEntries[2]['entity_id']);
        $this->assertEquals($reversal['transaction_id'], $basEntries[2]['transaction_id']);

        $this->assertEquals('reversed', $payout[0]['status']);
        $this->assertNotNull($payout[0]['transaction_id']);
    }

    // payout is processed and then acc statement is fetched. suppose debit row gets mapped because utr is there
    //but credit row is marked as external. manually credit row is linked then
    public function testRblSourceUpdateFromProcessedToReversed()
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

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Payout\Status::PROCESSED, $payout['status']);
        $this->assertEquals(FundTransfer\Mode::IMPS, $payout['mode']);
        $this->assertEquals(Attempt\Status::PROCESSED, $attempt['status']);
        $this->assertEquals(FundTransfer\Mode::IMPS, $attempt['mode']);

        // Fetch account statement from RBL

        $mockedResponse = $this->getRblTxnCreation();

        $this->setMozartMockResponse($mockedResponse);

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->cronAuth();
        $this->startTest();

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $transactions = $this->getDbEntities('transaction');
        $externalEntries = $this->getDbEntities('external', ['balance_id' => $payout['balance_id']]);
        $payout = $this->getDbEntities('payout');

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[0]['entity_type']);
        $this->assertEquals($externalEntries[0]['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($externalEntries[0]['transaction_id'], $basEntries[0]['transaction_id']);
        $this->assertEquals($externalEntries[0]['banking_account_statement_id'], $basEntries[0]['id']);
        $this->assertEquals($transactions[0]['entity_id'],$externalEntries[0]['id']);

        $this->assertEquals(EntityConstants::PAYOUT, $basEntries[1]['entity_type']);
        $this->assertEquals($payout[0]['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($payout[0]['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals($transactions[1]['entity_id'],$payout[0]['id']);

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[2]['entity_type']);
        $this->assertEquals($externalEntries[1]['id'], $basEntries[2]['entity_id']);
        $this->assertEquals($externalEntries[1]['transaction_id'], $basEntries[2]['transaction_id']);
        $this->assertEquals($externalEntries[1]['banking_account_statement_id'], $basEntries[2]['id']);
        $this->assertEquals($transactions[2]['entity_id'],$externalEntries[1]['id']);

        $this->ba->adminAuth();

        $request = [
            'url'     => '/banking_account_statement/source/update/validate',
            'method'  => 'POST',
            'content' => [
                'payout_id'     => $payout[0]['id'],
                'debit_bas_id'  => $basEntries[1]['id'],
                'credit_bas_id' => $basEntries[2]['id'],
                'end_status'    => 'reversed'],
        ];

        $content = $this->makeRequestAndGetContent($request);

        $request = [
            'url'     => '/banking_account_statement/source/update',
            'method'  => 'POST',
            'content' => [
                'payout_id'     => $payout[0]['id'],
                'debit_bas_id'  => $basEntries[1]['id'],
                'credit_bas_id' => $basEntries[2]['id'],
                'end_status'    => 'reversed'],
        ];

        $content = $this->makeRequestAndGetContent($request);

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $transactions = $this->getDbEntities('transaction');
        $externalEntries = $this->getDbEntities('external', ['balance_id' => $payout[0]['balance_id']]);
        $payout = $this->getDbEntities('payout');
        $reversal = $this->getDbLastEntity('reversal');

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[0]['entity_type']);
        $this->assertEquals($externalEntries[0]['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($externalEntries[0]['transaction_id'], $basEntries[0]['transaction_id']);
        $this->assertEquals($externalEntries[0]['banking_account_statement_id'], $basEntries[0]['id']);
        $this->assertEquals($transactions[0]['entity_id'],$externalEntries[0]['id']);

        $this->assertEquals(EntityConstants::PAYOUT, $basEntries[1]['entity_type']);
        $this->assertEquals($payout[0]['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($payout[0]['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals($transactions[1]['entity_id'],$payout[0]['id']);

        $this->assertEquals(EntityConstants::REVERSAL, $basEntries[2]['entity_type']);
        $this->assertEquals($reversal['id'], $basEntries[2]['entity_id']);
        $this->assertEquals($reversal['transaction_id'], $basEntries[2]['transaction_id']);

        $this->assertEquals('reversed', $payout[0]['status']);
        $this->assertNotNull($payout[0]['transaction_id']);
    }

    // there are 2 unlinked payouts with duplicate utr. acc statement is fetched and debit row gets mapped to
    //external because of duplicate utr . manual linking is done then
    public function testRblSourceUpdateFromProcessedToProcessed()
    {
        $channel = Channel::RBL;

        $this->setupForRblPayout($channel);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated','utr' => '123456']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '123456']);

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

        // create second payout with same utr
        $this->createRblPayout();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated', 'utr' => '123456']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '123456']);

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
        // Fetch account statement from RBL

        $mockedResponse = $this->getRblTxnCreation();

        unset($mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][2]);

        $this->setMozartMockResponse($mockedResponse);

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->cronAuth();
        $this->startTest();

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $transactions = $this->getDbEntities('transaction');
        $externalEntries = $this->getDbEntities('external', ['balance_id' => $payout['balance_id']]);
        $payout = $this->getDbEntities('payout');

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[0]['entity_type']);
        $this->assertEquals($externalEntries[0]['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($externalEntries[0]['transaction_id'], $basEntries[0]['transaction_id']);
        $this->assertEquals($externalEntries[0]['banking_account_statement_id'], $basEntries[0]['id']);
        $this->assertEquals($transactions[0]['entity_id'],$externalEntries[0]['id']);

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[1]['entity_type']);
        $this->assertEquals($externalEntries[1]['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($externalEntries[1]['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals($externalEntries[1]['banking_account_statement_id'], $basEntries[1]['id']);
        $this->assertEquals($transactions[1]['entity_id'],$externalEntries[1]['id']);

        $this->ba->adminAuth();

        $request = [
            'url'     => '/banking_account_statement/source/update',
            'method'  => 'POST',
            'content' => [
                'payout_id'     => $payout[0]['id'],
                'debit_bas_id'  => $basEntries[1]['id'],
                'end_status'    => 'processed'],
        ];

        $content = $this->makeRequestAndGetContent($request);

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $transactions = $this->getDbEntities('transaction');
        $externalEntries = $this->getDbEntities('external', ['balance_id' => $payout[0]['balance_id']]);
        $payout = $this->getDbEntities('payout');

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[0]['entity_type']);
        $this->assertEquals($externalEntries[0]['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($externalEntries[0]['transaction_id'], $basEntries[0]['transaction_id']);
        $this->assertEquals($externalEntries[0]['banking_account_statement_id'], $basEntries[0]['id']);
        $this->assertEquals($transactions[0]['entity_id'],$externalEntries[0]['id']);

        $this->assertEquals(EntityConstants::PAYOUT, $basEntries[1]['entity_type']);
        $this->assertEquals($payout[0]['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($payout[0]['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals($transactions[1]['entity_id'],$payout[0]['id']);

        $this->assertEquals('processed', $payout[0]['status']);
        $this->assertNotNull($payout[0]['transaction_id']);
    }

    // invalid state transition for payout is not allowed from processed to failed
    public function testRblSourceUpdateInvalidStateTransitionFromProcessedToFailed()
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

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Payout\Status::PROCESSED, $payout['status']);
        $this->assertEquals(FundTransfer\Mode::IMPS, $payout['mode']);
        $this->assertEquals(Attempt\Status::PROCESSED, $attempt['status']);
        $this->assertEquals(FundTransfer\Mode::IMPS, $attempt['mode']);

        $data = $this->testData[__FUNCTION__];

        $data['request']['content']['payout_id'] = $payout['id'];

        $this->testData[__FUNCTION__] = $data;

        $this->ba->adminAuth();

        $this->startTest();
    }

    // invalid state transition for payout is not allowed from failed to processed
    public function testRblSourceUpdateInvalidStateTransitionFromFailedToProcessed()
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
            Attempt\Status::FAILED);

        $payout = $this->getDbLastEntity('payout');

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Payout\Status::FAILED, $payout['status']);
        $this->assertEquals(FundTransfer\Mode::IMPS, $payout['mode']);
        $this->assertEquals(Attempt\Status::FAILED, $attempt['status']);
        $this->assertEquals(FundTransfer\Mode::IMPS, $attempt['mode']);

        $data = $this->testData[__FUNCTION__];

        $data['request']['content']['payout_id'] = $payout['id'];

        $this->testData[__FUNCTION__] = $data;

        $this->ba->adminAuth();

        $this->startTest();
    }

    // payout is failed and then act statement is fetched . since payout is failed debit row gets linked to external.
    //and credit row also gets linked to external since no utr or cms present. Manual linking is done then.
    public function testRblSourceUpdateFromFailedToReversed()
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
            Attempt\Status::FAILED);

        $payout = $this->getDbLastEntity('payout');

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Payout\Status::FAILED, $payout['status']);
        $this->assertEquals(FundTransfer\Mode::IMPS, $payout['mode']);
        $this->assertEquals(Attempt\Status::FAILED, $attempt['status']);
        $this->assertEquals(FundTransfer\Mode::IMPS, $attempt['mode']);

        // Fetch account statement from RBL

        $mockedResponse = $this->getRblTxnCreation();

        $this->setMozartMockResponse($mockedResponse);

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->cronAuth();
        $this->startTest();

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $transactions = $this->getDbEntities('transaction');
        $externalEntries = $this->getDbEntities('external', ['balance_id' => $payout['balance_id']]);
        $payout = $this->getDbEntities('payout');

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[0]['entity_type']);
        $this->assertEquals($externalEntries[0]['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($externalEntries[0]['transaction_id'], $basEntries[0]['transaction_id']);
        $this->assertEquals($externalEntries[0]['banking_account_statement_id'], $basEntries[0]['id']);
        $this->assertEquals($transactions[0]['entity_id'],$externalEntries[0]['id']);

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[1]['entity_type']);
        $this->assertEquals($externalEntries[1]['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($externalEntries[1]['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals($externalEntries[1]['banking_account_statement_id'], $basEntries[1]['id']);
        $this->assertEquals($transactions[1]['entity_id'],$externalEntries[1]['id']);

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[2]['entity_type']);
        $this->assertEquals($externalEntries[2]['id'], $basEntries[2]['entity_id']);
        $this->assertEquals($externalEntries[2]['transaction_id'], $basEntries[2]['transaction_id']);
        $this->assertEquals($externalEntries[2]['banking_account_statement_id'], $basEntries[2]['id']);
        $this->assertEquals($transactions[2]['entity_id'],$externalEntries[2]['id']);

        $this->ba->adminAuth();

        $request = [
            'url'     => '/banking_account_statement/source/update/validate',
            'method'  => 'POST',
            'content' => [
                'payout_id'     => $payout[0]['id'],
                'debit_bas_id'  => $basEntries[1]['id'],
                'credit_bas_id' => $basEntries[2]['id'],
                'end_status'    => 'reversed'],
        ];

        $content = $this->makeRequestAndGetContent($request);

        $request = [
            'url'     => '/banking_account_statement/source/update',
            'method'  => 'POST',
            'content' => [
                'payout_id'     => $payout[0]['id'],
                'debit_bas_id'  => $basEntries[1]['id'],
                'credit_bas_id' => $basEntries[2]['id'],
                'end_status'    => 'reversed'],
        ];

        $content = $this->makeRequestAndGetContent($request);

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $transactions = $this->getDbEntities('transaction');
        $externalEntries = $this->getDbEntities('external', ['balance_id' => $payout[0]['balance_id']]);
        $payout = $this->getDbEntities('payout');
        $reversal = $this->getDbLastEntity('reversal');

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[0]['entity_type']);
        $this->assertEquals($externalEntries[0]['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($externalEntries[0]['transaction_id'], $basEntries[0]['transaction_id']);
        $this->assertEquals($externalEntries[0]['banking_account_statement_id'], $basEntries[0]['id']);
        $this->assertEquals($transactions[0]['entity_id'],$externalEntries[0]['id']);

        $this->assertEquals(EntityConstants::PAYOUT, $basEntries[1]['entity_type']);
        $this->assertEquals($payout[0]['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($payout[0]['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals($transactions[1]['entity_id'],$payout[0]['id']);

        $this->assertEquals(EntityConstants::REVERSAL, $basEntries[2]['entity_type']);
        $this->assertEquals($reversal['id'], $basEntries[2]['entity_id']);
        $this->assertEquals($reversal['transaction_id'], $basEntries[2]['transaction_id']);

        $this->assertEquals('reversed', $payout[0]['status']);
        $this->assertNotNull($payout[0]['transaction_id']);
    }

    // 2 payouts with duplicate return utr but different utr , debit row gets linked to one payout while credit row
    //gets linked to external . manually credit row is linked with that payout reversal
    public function testRblSourceUpdateFromReversedToReversed()
    {
        $channel = Channel::RBL;

        $this->setupForRblPayout($channel);

        $payout1 = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout1['fees']);
        $this->assertEquals(90, $payout1['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout1['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout1['id'], ['status' => 'initiated']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '123456']);

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

        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::REVERSED);

        $this->fixtures->edit('payout', $payout['id'], ['return_utr' => '143535']);

        // create another payout and reverse it with same return utr
        $this->createRblPayout();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '123457']);

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

        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::REVERSED);

        $this->fixtures->edit('payout', $payout['id'], ['return_utr' => '143535']);

        // Fetch account statement from RBL

        $mockedResponse = $this->getRblTxnCreation();

        $this->setMozartMockResponse($mockedResponse);

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->cronAuth();
        $this->startTest();

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $transactions = $this->getDbEntities('transaction');
        $externalEntries = $this->getDbEntities('external', ['balance_id' => $payout['balance_id']]);
        $reversal = $this->getDbLastEntity('reversal');

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[0]['entity_type']);
        $this->assertEquals($externalEntries[0]['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($externalEntries[0]['transaction_id'], $basEntries[0]['transaction_id']);
        $this->assertEquals($externalEntries[0]['banking_account_statement_id'], $basEntries[0]['id']);
        $this->assertEquals($transactions[0]['entity_id'],$externalEntries[0]['id']);

        $payout = $this->getDbEntities('payout');

        $this->assertEquals(EntityConstants::PAYOUT, $basEntries[1]['entity_type']);
        $this->assertEquals($payout[0]['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($payout[0]['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals($transactions[1]['entity_id'],$payout[0]['id']);


        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[2]['entity_type']);
        $this->assertEquals($externalEntries[1]['id'], $basEntries[2]['entity_id']);
        $this->assertEquals($externalEntries[1]['remarks'], 'multiple payouts found with same return utr 143535 for credit mapping');
        $this->assertEquals($externalEntries[1]['transaction_id'], $basEntries[2]['transaction_id']);

        $this->ba->adminAuth();

        $request = [
            'url'     => '/banking_account_statement/source/update',
            'method'  => 'POST',
            'content' => [
                'payout_id'     => $payout[0]['id'],
                'debit_bas_id'  => $basEntries[1]['id'],
                'credit_bas_id' => $basEntries[2]['id'],
                'end_status'    => 'reversed'],
        ];

        $content = $this->makeRequestAndGetContent($request);

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $transactions = $this->getDbEntities('transaction');
        $externalEntries = $this->getDbEntities('external', ['balance_id' => $payout[0]['balance_id']]);
        $reversal = $this->getDbEntities('reversal');

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[0]['entity_type']);
        $this->assertEquals($externalEntries[0]['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($externalEntries[0]['transaction_id'], $basEntries[0]['transaction_id']);
        $this->assertEquals($externalEntries[0]['banking_account_statement_id'], $basEntries[0]['id']);
        $this->assertEquals($transactions[0]['entity_id'],$externalEntries[0]['id']);

        $payout = $this->getDbEntities('payout');

        $this->assertEquals(EntityConstants::PAYOUT, $basEntries[1]['entity_type']);
        $this->assertEquals($payout[0]['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($payout[0]['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals($transactions[1]['entity_id'],$payout[0]['id']);

        $this->assertEquals(EntityConstants::REVERSAL, $basEntries[2]['entity_type']);
        $this->assertEquals($reversal[0]['id'], $basEntries[2]['entity_id']);
        $this->assertEquals($reversal[0]['transaction_id'], $basEntries[2]['transaction_id']);
        $this->assertEquals($reversal[0]['entity_id'], $payout[0]['id']);
        $this->assertEquals($reversal[0]['transaction_id'], $transactions[2]['id']);

        $this->assertEquals('reversed', $payout[0]['status']);
        $this->assertNotNull($payout[0]['transaction_id']);
    }

    // payout is processed and then acc statement is fetched. both debit row and credit row are unmapped
    // since while matching debit row we got duplicate utr and credit doesnt find any utr or cms. manually
    // linking is done then
    public function testRblSourceUpdateFromProcessedToReversedWithBothCreditAndDebitUnmapped()
    {
        $channel = Channel::RBL;

        $this->setupForRblPayout($channel);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated','utr' => '123456']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '123456']);

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

        // create second payout with same utr
        $this->createRblPayout();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated', 'utr' => '123456']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '123456']);

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
        // Fetch account statement from RBL

        $mockedResponse = $this->getRblTxnCreation();

        $this->setMozartMockResponse($mockedResponse);

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->cronAuth();
        $this->startTest();

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $transactions = $this->getDbEntities('transaction');
        $externalEntries = $this->getDbEntities('external', ['balance_id' => $payout['balance_id']]);
        $payout = $this->getDbEntities('payout');

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[0]['entity_type']);
        $this->assertEquals($externalEntries[0]['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($externalEntries[0]['transaction_id'], $basEntries[0]['transaction_id']);
        $this->assertEquals($externalEntries[0]['banking_account_statement_id'], $basEntries[0]['id']);
        $this->assertEquals($transactions[0]['entity_id'],$externalEntries[0]['id']);

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[1]['entity_type']);
        $this->assertEquals($externalEntries[1]['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($externalEntries[1]['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals($externalEntries[1]['banking_account_statement_id'], $basEntries[1]['id']);
        $this->assertEquals($transactions[1]['entity_id'],$externalEntries[1]['id']);

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[2]['entity_type']);
        $this->assertEquals($externalEntries[2]['id'], $basEntries[2]['entity_id']);
        $this->assertEquals($externalEntries[2]['transaction_id'], $basEntries[2]['transaction_id']);
        $this->assertEquals($externalEntries[2]['banking_account_statement_id'], $basEntries[2]['id']);
        $this->assertEquals($transactions[2]['entity_id'],$externalEntries[2]['id']);

        $this->ba->adminAuth();

        $request = [
            'url'     => '/banking_account_statement/source/update',
            'method'  => 'POST',
            'content' => [
                'payout_id'     => $payout[0]['id'],
                'debit_bas_id'  => $basEntries[1]['id'],
                'credit_bas_id' => $basEntries[2]['id'],
                'end_status'    => 'reversed'],
        ];

        $content = $this->makeRequestAndGetContent($request);

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $transactions = $this->getDbEntities('transaction');
        $externalEntries = $this->getDbEntities('external', ['balance_id' => $payout[0]['balance_id']]);
        $payout = $this->getDbEntities('payout');
        $reversal = $this->getDbLastEntity('reversal');

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[0]['entity_type']);
        $this->assertEquals($externalEntries[0]['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($externalEntries[0]['transaction_id'], $basEntries[0]['transaction_id']);
        $this->assertEquals($externalEntries[0]['banking_account_statement_id'], $basEntries[0]['id']);
        $this->assertEquals($transactions[0]['entity_id'],$externalEntries[0]['id']);

        $this->assertEquals(EntityConstants::PAYOUT, $basEntries[1]['entity_type']);
        $this->assertEquals($payout[0]['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($payout[0]['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals($transactions[1]['entity_id'],$payout[0]['id']);

        $this->assertEquals(EntityConstants::REVERSAL, $basEntries[2]['entity_type']);
        $this->assertEquals($reversal['id'], $basEntries[2]['entity_id']);
        $this->assertEquals($reversal['transaction_id'], $basEntries[2]['transaction_id']);

        $this->assertEquals('reversed', $payout[0]['status']);
        $this->assertNotNull($payout[0]['transaction_id']);
    }

    // during manual linking with end_status as reversed ,
    // credit bas is not passed , hence an exception is thrown
    public function testRblSourceUpdateFromInitiatedToReversedWhenCreditBasIdIsNotPassed()
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

        // Update status
        $ftsCreateTransfer = new FtsFundTransfer(
            EnvMode::TEST,
            $attempt['id']);

        $ftsCreateTransfer->handle();

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Attempt\Status::INITIATED, $attempt['status']);

        // Fetch account statement from RBL

        $mockedResponse = $this->getRblTxnCreation();

        $this->setMozartMockResponse($mockedResponse);

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->cronAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/process',
            'content' => [
                'account_number' => '2224440041626905',
                'channel'        => 'rbl',
            ],
        ];

        $this->makeRequestAndGetContent($request);

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $transactions = $this->getDbEntities('transaction');
        $externalEntries = $this->getDbEntities('external', ['balance_id' => $payout['balance_id']]);
        $reversal = $this->getDbLastEntity('reversal');

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[0]['entity_type']);
        $this->assertEquals($externalEntries[0]['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($externalEntries[0]['transaction_id'], $basEntries[0]['transaction_id']);
        $this->assertEquals($externalEntries[0]['banking_account_statement_id'], $basEntries[0]['id']);
        $this->assertEquals($transactions[0]['entity_id'],$externalEntries[0]['id']);

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[1]['entity_type']);
        $this->assertEquals($externalEntries[1]['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($externalEntries[1]['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals($externalEntries[1]['banking_account_statement_id'], $basEntries[1]['id']);
        $this->assertEquals($transactions[1]['entity_id'],$externalEntries[1]['id']);

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[2]['entity_type']);
        $this->assertEquals($externalEntries[2]['id'], $basEntries[2]['entity_id']);
        $this->assertEquals($externalEntries[2]['transaction_id'], $basEntries[2]['transaction_id']);
        $this->assertEquals($externalEntries[2]['banking_account_statement_id'], $basEntries[2]['id']);
        $this->assertEquals($transactions[2]['entity_id'],$externalEntries[2]['id']);

        $this->ba->adminAuth();

        $request = [
            'url'     => '/banking_account_statement/source/update/validate',
            'method'  => 'POST',
            'content' => [
                'payout_id'     => $payout['id'],
                'debit_bas_id'  => $basEntries[1]['id'],
                'end_status'    => 'reversed'],
        ];

        $content = $this->makeRequestAndCatchException(function() use ($request) {
            $this->makeRequestAndGetContent($request);
            },
            BadRequestValidationFailureException::class,
            ErrorCode::BAD_REQUEST_CREDIT_BAS_ID_MISSING);
    }

    // during manual linking with end_status as reversed ,
    // credit bas is not passed , hence an exception is thrown
    public function testRblSourceUpdateFromProcessedToReversedWhenCreditBasIdIsNotPassed()
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

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Payout\Status::PROCESSED, $payout['status']);
        $this->assertEquals(FundTransfer\Mode::IMPS, $payout['mode']);
        $this->assertEquals(Attempt\Status::PROCESSED, $attempt['status']);
        $this->assertEquals(FundTransfer\Mode::IMPS, $attempt['mode']);

        // Fetch account statement from RBL

        $mockedResponse = $this->getRblTxnCreation();

        $this->setMozartMockResponse($mockedResponse);

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->cronAuth();
        $this->startTest();

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $transactions = $this->getDbEntities('transaction');
        $externalEntries = $this->getDbEntities('external', ['balance_id' => $payout['balance_id']]);
        $payout = $this->getDbEntities('payout');

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[0]['entity_type']);
        $this->assertEquals($externalEntries[0]['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($externalEntries[0]['transaction_id'], $basEntries[0]['transaction_id']);
        $this->assertEquals($externalEntries[0]['banking_account_statement_id'], $basEntries[0]['id']);
        $this->assertEquals($transactions[0]['entity_id'],$externalEntries[0]['id']);

        $this->assertEquals(EntityConstants::PAYOUT, $basEntries[1]['entity_type']);
        $this->assertEquals($payout[0]['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($payout[0]['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals($transactions[1]['entity_id'],$payout[0]['id']);

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[2]['entity_type']);
        $this->assertEquals($externalEntries[1]['id'], $basEntries[2]['entity_id']);
        $this->assertEquals($externalEntries[1]['transaction_id'], $basEntries[2]['transaction_id']);
        $this->assertEquals($externalEntries[1]['banking_account_statement_id'], $basEntries[2]['id']);
        $this->assertEquals($transactions[2]['entity_id'],$externalEntries[1]['id']);

        $this->ba->adminAuth();

        $request = [
            'url'     => '/banking_account_statement/source/update/validate',
            'method'  => 'POST',
            'content' => [
                'payout_id'     => $payout[0]['id'],
                'debit_bas_id'  => $basEntries[1]['id'],
                'end_status'    => 'reversed'],
        ];

        $content = $this->makeRequestAndCatchException(function() use ($request) {
            $this->makeRequestAndGetContent($request);
        },
            BadRequestValidationFailureException::class,
            ErrorCode::BAD_REQUEST_CREDIT_BAS_ID_MISSING);

    }

    // during manual linking with end_status as reversed ,
    // credit bas is not passed , hence an exception is thrown
    public function testRblSourceUpdateFromFailedToReversedWhenCreditBasIdIsNotPassed()
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
            Attempt\Status::FAILED);

        $payout = $this->getDbLastEntity('payout');

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Payout\Status::FAILED, $payout['status']);
        $this->assertEquals(FundTransfer\Mode::IMPS, $payout['mode']);
        $this->assertEquals(Attempt\Status::FAILED, $attempt['status']);
        $this->assertEquals(FundTransfer\Mode::IMPS, $attempt['mode']);

        // Fetch account statement from RBL

        $mockedResponse = $this->getRblTxnCreation();

        $this->setMozartMockResponse($mockedResponse);

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->cronAuth();
        $this->startTest();

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $transactions = $this->getDbEntities('transaction');
        $externalEntries = $this->getDbEntities('external', ['balance_id' => $payout['balance_id']]);
        $payout = $this->getDbEntities('payout');

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[0]['entity_type']);
        $this->assertEquals($externalEntries[0]['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($externalEntries[0]['transaction_id'], $basEntries[0]['transaction_id']);
        $this->assertEquals($externalEntries[0]['banking_account_statement_id'], $basEntries[0]['id']);
        $this->assertEquals($transactions[0]['entity_id'],$externalEntries[0]['id']);

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[1]['entity_type']);
        $this->assertEquals($externalEntries[1]['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($externalEntries[1]['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals($externalEntries[1]['banking_account_statement_id'], $basEntries[1]['id']);
        $this->assertEquals($transactions[1]['entity_id'],$externalEntries[1]['id']);

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[2]['entity_type']);
        $this->assertEquals($externalEntries[2]['id'], $basEntries[2]['entity_id']);
        $this->assertEquals($externalEntries[2]['transaction_id'], $basEntries[2]['transaction_id']);
        $this->assertEquals($externalEntries[2]['banking_account_statement_id'], $basEntries[2]['id']);
        $this->assertEquals($transactions[2]['entity_id'],$externalEntries[2]['id']);

        $this->ba->adminAuth();

        $request = [
            'url'     => '/banking_account_statement/source/update/validate',
            'method'  => 'POST',
            'content' => [
                'payout_id'     => $payout[0]['id'],
                'debit_bas_id'  => $basEntries[1]['id'],
                'end_status'    => 'reversed'],
        ];

        $content = $this->makeRequestAndCatchException(function() use ($request) {
            $this->makeRequestAndGetContent($request);
        },
            BadRequestValidationFailureException::class,
            ErrorCode::BAD_REQUEST_CREDIT_BAS_ID_MISSING);
    }

    // during manual linking with end_status as reversed ,
    // credit bas is not passed , hence an exception is thrown
    public function testRblSourceUpdateFromReversedToReversedWhenCreditBasIdIsNotPassed()
    {
        $channel = Channel::RBL;

        $this->setupForRblPayout($channel);

        $payout1 = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout1['fees']);
        $this->assertEquals(90, $payout1['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout1['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout1['id'], ['status' => 'initiated']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '123456']);

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

        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::REVERSED);

        $this->fixtures->edit('payout', $payout['id'], ['return_utr' => '143535']);

        // create another payout and reverse it with same return utr
        $this->createRblPayout();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '123457']);

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

        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::REVERSED);

        $this->fixtures->edit('payout', $payout['id'], ['return_utr' => '143535']);

        // Fetch account statement from RBL

        $mockedResponse = $this->getRblTxnCreation();

        $this->setMozartMockResponse($mockedResponse);

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->cronAuth();
        $this->startTest();

        $basEntries      = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $transactions    = $this->getDbEntities('transaction');
        $externalEntries = $this->getDbEntities('external', ['balance_id' => $payout['balance_id']]);
        $reversal        = $this->getDbLastEntity('reversal');

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[0]['entity_type']);
        $this->assertEquals($externalEntries[0]['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($externalEntries[0]['transaction_id'], $basEntries[0]['transaction_id']);
        $this->assertEquals($externalEntries[0]['banking_account_statement_id'], $basEntries[0]['id']);
        $this->assertEquals($transactions[0]['entity_id'], $externalEntries[0]['id']);

        $payout = $this->getDbEntities('payout');

        $this->assertEquals(EntityConstants::PAYOUT, $basEntries[1]['entity_type']);
        $this->assertEquals($payout[0]['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($payout[0]['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals($transactions[1]['entity_id'], $payout[0]['id']);

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[2]['entity_type']);
        $this->assertEquals($externalEntries[1]['id'], $basEntries[2]['entity_id']);
        $this->assertEquals($externalEntries[1]['remarks'], 'multiple payouts found with same return utr 143535 for credit mapping');
        $this->assertEquals($externalEntries[1]['transaction_id'], $basEntries[2]['transaction_id']);

        $this->ba->adminAuth();

        $request = [
            'url'     => '/banking_account_statement/source/update/validate',
            'method'  => 'POST',
            'content' => [
                'payout_id'     => $payout[0]['id'],
                'debit_bas_id'  => $basEntries[1]['id'],
                'end_status'    => 'reversed'],
        ];


        $content = $this->makeRequestAndCatchException(function() use ($request) {
            $this->makeRequestAndGetContent($request);
        },
            BadRequestValidationFailureException::class,
            ErrorCode::BAD_REQUEST_CREDIT_BAS_ID_MISSING);
    }
}
