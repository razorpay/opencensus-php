<?php

namespace RZP\Tests\Functional\BankingAccountStatement;

use Mail;
use Queue;
use Redis;
use Mockery;
use Carbon\Carbon;

use RZP\Models\Payout;
use RZP\Services\Mozart;
use RZP\Models\FundTransfer;
use RZP\Constants\Mode as EnvMode;
use RZP\Tests\Functional\TestCase;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\BankingAccount\Channel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RZP\Mail\BankingAccount\StatementMail;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Constants\Entity as EntityConstants;
use RZP\Jobs\FTS\FundTransfer as FtsFundTransfer;
use RZP\Models\External\Entity as ExternalEntity;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Models\Transaction\Entity as TransactionEntity;
use RZP\Models\BankingAccountStatement\Entity as BasEntity;

class RblBankingAccountStatementTest extends TestCase
{
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

        $this->ba->privateAuth();

        $this->setUpMerchantForBusinessBanking(
            false,
            10000,
            AccountType::DIRECT,
            Channel::RBL);

        $this->balance = $this->getDbEntity('balance', ['merchant_id' => '10000000000000', 'type' => 'banking']);

        $balanceId = $this->balance->getId();

        $this->fixtures->create('banking_account', [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => '10000000000000',
            'channel'               => 'rbl',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
            'balance_id'            => $balanceId,
        ]);
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

    /**
     * Case where the response from RBL is success
     */
    public function testRblAccountStatementCase1()
    {
        $mockedResponse = $this->getRblDataResponse();

        $this->setMozartMockResponse($mockedResponse);

        $this->ba->cronAuth();

        $this->startTest();

        $transactions = $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'];

        $txn = last($transactions);

        $basActual = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT, true);

        $externalActual = $this->getLastEntity(EntityConstants::EXTERNAL, true);

        $externalId = str_after($externalActual[ExternalEntity::ID], 'ext_');

        $externalTxnId = $externalActual[ExternalEntity::TRANSACTION_ID];

        $txnEntity = $this->getDbEntityById(EntityConstants::TRANSACTION, $externalTxnId);

        $txnActual = $txnEntity->toArray();

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

        $this->ba->cronAuth();

        $this->startTest();

        $basAfterTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT, true);

        $this->assertEquals($basBeforeTest[BasEntity::ID], $basAfterTest[BasEntity::ID]);
    }

    /**
     * Case where request details are incorrect to RBL
     */
    public function testRblAccountStatementCase3()
    {
        $mockedResponse = $this->getRblInvalidDetailsResponse();

        $this->setMozartMockResponse($mockedResponse);

        $this->ba->cronAuth();

        $this->startTest();
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

        $this->assertEquals(EntityConstants::PAYOUT, $feeBreakup2[0]['name']);
        $this->assertEquals(0, $feeBreakup2[1]['amount']);
        $this->assertEquals(EntityConstants::TAX, $feeBreakup2[1]['name']);
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

        $this->assertEquals(EntityConstants::PAYOUT, $feeBreakup2[0]['name']);
        $this->assertEquals(0, $feeBreakup2[1]['amount']);
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

        $this->assertEquals('Bbg7fgaDwax04u', $feeBreakup[0]['pricing_rule_id']);
        $this->assertEquals(0, $feeBreakup[0]['amount']);
        $this->assertEquals(EntityConstants::PAYOUT, $feeBreakup[0]['name']);
        $this->assertEquals(0, $feeBreakup[1]['amount']);
        $this->assertEquals(EntityConstants::TAX, $feeBreakup[1]['name']);
    }

    // case to test reversals mapping in RBL BAS for RTGS, same day reversals have same UTR
    public function testRblAccountStatementTxnMappingCase4()
    {
        $channel = Channel::RBL;

        $this->setupForRblPayout($channel, 'RTGS', 20000300);

        $payout = $this->getDbLastEntity('payout');

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['cms_ref_no' => 'S55959']);

        // Fetch account statement from RBL
        $mockedResponse = $this->getRblDataResponseForRTGS();

        $this->setMozartMockResponse($mockedResponse);

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->cronAuth();
        $this->startTest();

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);

        $externals = $this->getDbEntities('external', ['balance_id' => $payout['balance_id']]);
        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[0]['entity_type']);
        $this->assertEquals($externals[0]['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($externals[0]['transaction_id'], $basEntries[0]['transaction_id']);
        $this->assertEquals($externals[0]['banking_account_statement_id'], $basEntries[0]['id']);

        $this->assertEquals(EntityConstants::PAYOUT, $basEntries[1]['entity_type']);
        $this->assertEquals($payout['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($payout['transaction_id'], $basEntries[1]['transaction_id']);

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[2]['entity_type']);
        $this->assertEquals($externals[1]['id'], $basEntries[2]['entity_id']);
        $this->assertEquals($externals[1]['transaction_id'], $basEntries[2]['transaction_id']);
        $this->assertEquals($externals[1]['banking_account_statement_id'], $basEntries[2]['id']);

        $feeBreakup1 = $this->getDbEntities('fee_breakup', ['transaction_id' => $basEntries[0]['transaction_id']]);
        $feeBreakup2 = $this->getDbEntities('fee_breakup', ['transaction_id' => $basEntries[1]['transaction_id']]);
        $feeBreakup3 = $this->getDbEntities('fee_breakup', ['transaction_id' => $basEntries[2]['transaction_id']]);

        $this->assertEquals(0, $feeBreakup1->count());
        $this->assertEquals(2, $feeBreakup2->count());
        $this->assertEquals(0, $feeBreakup3->count());
    }

    protected function setupForRblPayout($channel = Channel::RBL, $mode = 'IMPS', $amount = 10095)
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


    protected function getRblDataResponseForRTGS()
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
                                        'amountValue' => '400003.00',
                                        'currencyCode' => 'INR'
                                    ],
                                    'txnDate' => '2015-12-29T00:00:00.000',
                                    'txnDesc' => 'DEBIT CARD ANNUAL FEE 2635',
                                    'txnType' => 'C'
                                ],
                                'txnBalance' => [
                                    'currencyCode' => 'INR',
                                    'amountValue' => '400103.00'
                                ],
                                'txnCat' => 'TBI',
                                'txnId' => '  S429658',
                                'txnSrlNo' => ' 498',
                                'valueDate' => '2015-12-29T00:00:00.000'
                            ],
                            [
                                'pstdDate' => '2015-12-29T15:58:12.000',
                                'transactionSummary' => [
                                    'instrumentId' => '',
                                    'txnAmt' => [
                                        'amountValue' => '200003.00',
                                        'currencyCode' => 'INR'
                                    ],
                                    'txnDate' => '2015-12-29T00:00:00.000',
                                    'txnDesc' => 'RTGS/RATNH20009002927/Elite                       ',
                                    'txnType' => 'D'
                                ],
                                'txnBalance' => [
                                    'currencyCode' => 'INR',
                                    'amountValue' => '200100.00'
                                ],
                                'txnCat' => 'TBI',
                                'txnId' => '  S55959',
                                'txnSrlNo' => ' 498',
                                'valueDate' => '2015-12-29T00:00:00.000'
                            ],
                            [
                                'pstdDate' => '2016-01-05T01:36:33.000',
                                'transactionSummary' => [
                                    'instrumentId' => '',
                                    'txnAmt' => [
                                        'amountValue' => '200001.00',
                                        'currencyCode' => 'INR'
                                    ],
                                    'txnDate' => '2016-01-05T00:00:00.000',
                                    'txnDesc' => 'RTGS/RATNH20009002927/R ACCOUNT UNAVAILABLE       ',
                                    'txnType' => 'C'
                                ],
                                'txnBalance' => [
                                    'currencyCode' => 'INR',
                                    'amountValue' => '400101.00'
                                ],
                                'txnCat' => 'TCI',
                                'txnId' => '  S623024',
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

    protected function getRblDataResponseForIFT()
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
                                        'amountValue' => '400003.50',
                                        'currencyCode' => 'INR'
                                    ],
                                    'txnDate' => '2015-12-29T00:00:00.000',
                                    'txnDesc' => 'DEBIT CARD ANNUAL FEE 2635',
                                    'txnType' => 'C'
                                ],
                                'txnBalance' => [
                                    'currencyCode' => 'INR',
                                    'amountValue' => '400103.50'
                                ],
                                'txnCat' => 'TBI',
                                'txnId' => '  S429655',
                                'txnSrlNo' => ' 498',
                                'valueDate' => '2015-12-29T00:00:00.000'
                            ],
                            [
                                'pstdDate' => '2015-12-29T15:58:12.000',
                                'transactionSummary' => [
                                    'instrumentId' => '',
                                    'txnAmt' => [
                                        'amountValue' => '200003.00',
                                        'currencyCode' => 'INR'
                                    ],
                                    'txnDate' => '2015-12-29T00:00:00.000',
                                    'txnDesc' => 'RTGS/RATNH20009002927/Elite                       ',
                                    'txnType' => 'D'
                                ],
                                'txnBalance' => [
                                    'currencyCode' => 'INR',
                                    'amountValue' => '200100.50'
                                ],
                                'txnCat' => 'TBI',
                                'txnId' => '    M2012',
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


    public function testLastFetchedAtEqualsBalanceUpdatedAtInitially()
    {
        $balanceId = $this->balance->getId();

        // 1578044039 is the timestamp of Jan 3, 2020.
        // Need to edit here as balance creation and update occur in test within the same second.
        $this->fixtures->edit('balance', $balanceId, ['updated_at' => 1578044039]);

        $initialBalance = $this->getDbEntityById('balance', $balanceId)->toArray();

        $this->testRblAccountStatementCase3();

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $finalBalance = $this->getDbEntityById('balance', $balanceId)->toArray();

        foreach ($response['items'] as $balance)
        {
            if (isset($balance['type']) and ($balance['type'] === 'banking') and ($balance['account_type'] === 'direct'))
            {
                // Since, BAS wasn't fetched, last_fetched_at will be equal to balance's updated_at
                $this->assertEquals($finalBalance['updated_at'], $balance['last_fetched_at']);
            }
        }

        // updated_at remains same as BAS fetch failed
        $this->assertEquals($initialBalance['updated_at'], $finalBalance['updated_at']);
    }
}
