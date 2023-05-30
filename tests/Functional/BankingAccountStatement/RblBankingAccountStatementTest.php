<?php

namespace RZP\Tests\Functional\BankingAccountStatement;

use DB;
use App;
use Mail;
use Queue;
use Mockery;
use Config;
use Carbon\Carbon;
use Database\Connection;

use RZP\Constants;

use RZP\Jobs\BankingAccountStatementReconProcessNeo;
use RZP\Models\Admin;
use RZP\Models\Payout;
use RZP\Services\Mozart;
use RZP\Error\ErrorCode;
use RZP\Constants\Table;
use RZP\Constants\Timezone;
use RZP\Models\FundTransfer;
use RZP\Models\Payout\Status;
use RZP\Models\BankingAccount;
use RZP\Services\RazorXClient;

use RZP\Models\Admin\ConfigKey;
use RZP\Models\Merchant\Balance;
use RZP\Exception\LogicException;
use RZP\Constants\Mode as EnvMode;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\TestsMetrics;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\BankingAccount\Channel;
use RZP\Models\Merchant\Webhook\Event;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RZP\Models\Merchant\Balance\Entity;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Models\BankingAccount\Gateway\Rbl;
use RZP\Mail\BankingAccount\StatementMail;
use RZP\Jobs\BankingAccountStatementRecon;
use RZP\Jobs\BankingAccountStatementUpdate;
use RZP\Jobs\BankingAccountStatementCleanUp;
use RZP\Models\BankingAccountStatement\Type;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\Feature\Constants as Features;
use RZP\Jobs\BankingAccountStatementReconNeo;
use RZP\Jobs\BankingAccountStatementProcessor;
use RZP\Mail\Transaction\Payout as PayoutMail;
use RZP\Tests\Functional\Helpers\WebhookTrait;
use RZP\Models\BankingAccountStatement\Metric;
use RZP\Jobs\MissingAccountStatementDetection;
use RZP\Services\Mock\Mutex as MockMutexService;
use RZP\Models\BankingAccount\Entity as BaEntity;
use RZP\Jobs\FTS\FundTransfer as FtsFundTransfer;
use RZP\Models\External\Entity as ExternalEntity;
use RZP\Jobs\BankingAccountMissingStatementInsert;
use RZP\Jobs\BankingAccountStatementSourceLinking;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\Payout\PayoutTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Models\Transaction\Entity as TransactionEntity;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\BankingAccountStatement\Core as BasCore;
use RZP\Models\BankingAccountStatement\Entity as BasEntity;
use RZP\Models\BankingAccountStatement\Details as BasDetails;
use RZP\Models\BankingAccountStatement\Constants as BASConstants;
use RZP\Jobs\BankingAccountStatement as BankingAccountStatementJob;
use RZP\Jobs\RblBankingAccountStatement as RblBankingAccountStatementJob;

class RblBankingAccountStatementTest extends TestCase
{
    use PayoutTrait;
    use AttemptTrait;
    use WebhookTrait;
    use TestsMetrics;
    use DbEntityFetchTrait;
    use TestsWebhookEvents;
    use TestsBusinessBanking;

    const UFH_FILE_PATH_REGEX    = '/.*\/ufh\/file\/(.*)/';

    const MOCK_UFH_BASE_LOCATION = 'files/filestore';

    const FILE_ID                = 'file_id';

    protected $db;

    /* @var Entity */
    private $balance;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/RblBankingAccountStatementTestData.php';

        parent::setUp();

        // TODO: to be removed before merging to master. This one way of handling the new db connection
        //$this->app['db']->connection(Connection::RX_ACCOUNT_STATEMENTS_LIVE)->beginTransaction();

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

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->fixtures->edit('banking_account', $bankingAccount['id'], [
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

        $this->fixtures->create('banking_account_statement_details',[
            BasDetails\Entity::ID                                  => 'xba00000000001',
            BasDetails\Entity::MERCHANT_ID                         => '10000000000000',
            BasDetails\Entity::BALANCE_ID                          => $balanceId,
            BasDetails\Entity::ACCOUNT_NUMBER                      => '2224440041626905',
            BasDetails\Entity::CHANNEL                             => BasDetails\Channel::RBL,
            BasDetails\Entity::STATUS                              => BasDetails\Status::ACTIVE,
            BasDetails\Entity::GATEWAY_BALANCE                     => 100,
            BasDetails\Entity::GATEWAY_BALANCE_CHANGE_AT           => 123400,
            BasDetails\Entity::STATEMENT_CLOSING_BALANCE           => 0,
            BasDetails\Entity::STATEMENT_CLOSING_BALANCE_CHANGE_AT => 123400
        ]);

        $this->mockStorkService();

        $this->balance = $this->getDbEntity('balance', ['merchant_id' => '10000000000000', 'type' => 'banking']);

        $this->fixtures->base->connection('test');
    }

    // TODO: to be removed before merging to master. This one way of handling the new db connection
    //protected function tearDown(): void
    //{
    //    $this->app['db']->connection(Connection::RX_ACCOUNT_STATEMENTS_LIVE)->rollBack();
    //
    //    $this->app['db']->disconnect(Connection::RX_ACCOUNT_STATEMENTS_LIVE);
    //
    //    parent::tearDown(); // TODO: Change the autogenerated stub
    //}

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

    protected function setRazorxMockForBankingAccountStatementV2Api()
    {
        $this->mockRazorxTreatment(
            'yesbank',
            'off',
            'off',
            'off',
            'off',
            'on',
            'on',
            'off',
            'on',
            'on',
            'off',
            'on',
            'on',
            'off',
            'control',
            'control',
            'on'
        );
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

        $basdBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNull($basdBeforeTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

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

        $basdAfterTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNotNull($basdAfterTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

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
     * Case where the response from RBL is success with ledger shadow
     * asserting external credit, external debit events to ledger
     */
    public function testRblAccountStatementExternalWithLedgerShadow()
    {
        $testData = $this->testData['testRblAccountStatementCase1'];

        $this->testData[__FUNCTION__] = $testData;

        $this->fixtures->merchant->addFeatures([Features::DA_LEDGER_JOURNAL_WRITES]);

        $ledgerSnsPayloadArray = [];
        $this->mockLedgerSns(2, $ledgerSnsPayloadArray);

        $mockedResponse = $this->getRblDataResponse();

        $this->setMozartMockResponse($mockedResponse);

        $baBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT, true);

        $this->assertNull($baBeforeTest[BaEntity::LAST_STATEMENT_ATTEMPT_AT]);

        $basdBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNull($basdBeforeTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

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

        $basdAfterTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNotNull($basdAfterTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

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

        $externalsCreated = $this->getDbEntities('external');

        $transactorTypeArray = [
            'da_ext_credit',
            'da_ext_debit',
        ];

        for ($index = 0; $index<count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('test', $ledgerRequestPayload['mode']);
            $this->assertEquals($externalsCreated[$index]->getPublicId(), $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals($externalsCreated[$index]->getTransactionId(), $ledgerRequestPayload['api_transaction_id']);
            $this->assertEmpty($ledgerRequestPayload['commission']);
            $this->assertEmpty($ledgerRequestPayload['tax']);
            $this->assertEquals($transactorTypeArray[$index], $ledgerRequestPayload['transactor_event']);
            $this->assertArrayNotHasKey('fee_accounting', $ledgerRequestPayload['additional_params']);
        }
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

    public function testRblAccountStatementCorrectionInClosingBalance()
    {
        $mockedResponse = $this->getRblDataResponse();

        $txnData = $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'];

        $txnData[0]['txnBalance']['amountValue'] = '215.5';

        $txnData[1]['txnBalance']['amountValue'] = '114.55';

        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'] = $txnData;

        $this->setMozartMockResponse($mockedResponse);

        $baBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT, true);

        $this->assertNull($baBeforeTest[BaEntity::LAST_STATEMENT_ATTEMPT_AT]);

        $basdBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNull($basdBeforeTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        $this->ba->cronAuth();

        $this->setupForRblAccountStatement();

        $closingBalanceDiff = [
            $baBeforeTest[BaEntity::ACCOUNT_NUMBER] => 100
        ];

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RBL_STATEMENT_CLOSING_BALANCE_DIFF => $closingBalanceDiff]);

        $testData = $this->testData['testRblAccountStatementCase1'];

        $this->testData[__FUNCTION__] = $testData;

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

        $basdAfterTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNotNull($basdAfterTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

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

        $bas = $this->getDbEntities(EntityConstants::BANKING_ACCOUNT_STATEMENT)[0];

        $this->assertNotNull($bas);

        $this->assertEquals(21450, $bas->getBalance());
    }

    /**
     * Case where the response from RBL is success.
     *
     * Rbl api supports 2 formats of requests.
     * 1. using from_date and to_date in api request
     * 2. using next_key in api request.
     *
     * This test case uses 1st format.
     */
    public function testRblAccountStatementV2ApiCase1()
    {
        $this->setRazorxMockForBankingAccountStatementV2Api();

        $mockedResponse = $this->getRblDataResponse();

        $this->setMozartMockResponse($this->convertRblV1ResponseToV2Response($mockedResponse));

        $baBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT, true);

        $this->assertNull($baBeforeTest[BaEntity::LAST_STATEMENT_ATTEMPT_AT]);

        $this->ba->cronAuth();

        $this->setupForRblAccountStatement();

        $this->startTest();

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
            BasEntity::BANK_TRANSACTION_ID   => 'S807068',
            BasEntity::TYPE                  => 'debit',
            BasEntity::AMOUNT                => 10095,
            BasEntity::BALANCE               => 11355,
            BasEntity::POSTED_DATE           => 1451937993,
            BasEntity::TRANSACTION_DATE      => 1451932200,
            BasEntity::DESCRIPTION           => '123456-Z',
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
     * Case where the response from RBL is success.
     *
     * Rbl api supports 2 formats of requests.
     * 1. using from_date and to_date in api request
     * 2. using next_key in api request.
     *
     * This test case uses 2nd format.
     */
    public function testRblAccountStatementV2ApiUsingNextKey()
    {
        $this->setRazorxMockForBankingAccountStatementV2Api();

        $mockedResponse = $this->getRblDataResponse();

        $this->setMozartMockResponse($this->convertRblV1ResponseToV2Response($mockedResponse));

        $basDetailsBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNull($basDetailsBeforeTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        $this->fixtures->edit(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS,
                              $basDetailsBeforeTest[BasDetails\Entity::ID],
                              [BasDetails\Entity::PAGINATION_KEY => 'initial_pagination_key']);

        $this->ba->cronAuth();

        $this->testData[__FUNCTION__] = $this->testData['testRblAccountStatementV2ApiCase1'];

        $this->setupForRblAccountStatement();

        $this->startTest();

        $basActual = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT, true);

        $externalActual = $this->getLastEntity(EntityConstants::EXTERNAL, true);

        $externalId = str_after($externalActual[ExternalEntity::ID], 'ext_');

        $externalTxnId = $externalActual[ExternalEntity::TRANSACTION_ID];

        $this->txnEntity = $this->getDbEntityById(EntityConstants::TRANSACTION, $externalTxnId);

        $txnActual = $this->txnEntity->toArray();

        $basDetailsAfterTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNotNull($basDetailsAfterTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        $this->assertEquals('1451937993_random_next_key', $basDetailsAfterTest[BasDetails\Entity::PAGINATION_KEY]);

        $this->assertEquals($txnActual[TransactionEntity::POSTED_AT], $basActual[BasEntity::POSTED_DATE]);

        $basExpected = [
            BasEntity::MERCHANT_ID           => $txnActual[TransactionEntity::MERCHANT_ID],
            BasEntity::BANK_TRANSACTION_ID   => 'S807068',
            BasEntity::TYPE                  => 'debit',
            BasEntity::AMOUNT                => 10095,
            BasEntity::BALANCE               => 11355,
            BasEntity::POSTED_DATE           => 1451937993,
            BasEntity::TRANSACTION_DATE      => 1451932200,
            BasEntity::DESCRIPTION           => '123456-Z',
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
     * Case where the response from RBL is success.
     *
     * Rbl api supports 2 formats of requests.
     * 1. using from_date and to_date in api request
     * 2. using next_key in api request.
     *
     * This test case uses both the formats successively.
     */
    public function testRblAccountStatementV2ApiUsingDatesApiAndNextKeySuccessively()
    {
        $this->setRazorxMockForBankingAccountStatementV2Api();

        $postedDateTimestamp = Carbon::now(Timezone::IST)->subDays(2);

        $this->app['rzp.mode'] = EnvMode::TEST;

        $mock = Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $mock->shouldReceive('sendRawRequest')
             ->andReturnUsing(function(array $request) use ($postedDateTimestamp) {

                 $requestData = json_decode($request['content'], true);

                 $rblResponseData =  $this->getRblDataResponse();
                 $postedDate = $postedDateTimestamp->format('d-m-Y H:i:s');
                 $rblResponseData['data']['PayGenRes']['Body']['transactionDetails'][0]['pstdDate'] = $postedDate;
                 $rblResponseData['data']['PayGenRes']['Body']['transactionDetails'][1]['pstdDate'] = $postedDate;

                 if (array_key_exists('from_date',$requestData['entities']['attempt']) === true)
                 {
                     $mockRblResponse =  $rblResponseData;

                     unset($mockRblResponse['data']['PayGenRes']['Body']['transactionDetails'][1]);

                     return json_encode($this->convertRblV1ResponseToV2Response($mockRblResponse));
                 }
                 else if ($requestData['entities']['attempt']['next_key'] == 'random_next_key')
                 {
                     $mockRblResponse =  $rblResponseData;

                     unset($mockRblResponse['data']['PayGenRes']['Body']['transactionDetails'][0]);

                     $mockRblResponse = $this->convertRblV1ResponseToV2Response($mockRblResponse);

                     $mockRblResponse['data']['FetchAccStmtRes']['Header']['next_key'] = 'too_random_next_key';

                     return json_encode($mockRblResponse);
                 }
                 else if ($requestData['entities']['attempt']['next_key'] == 'too_random_next_key')
                 {
                     $mockRblResponse =  $rblResponseData;

                     $mockRblResponse['data']['PayGenRes']['Body']['transactionDetails'][0]['txnBalance']['amountValue'] = '228.05';
                     $mockRblResponse['data']['PayGenRes']['Body']['transactionDetails'][0]['txnId'] = 'S444';

                     unset($mockRblResponse['data']['PayGenRes']['Body']['transactionDetails'][1]);

                     $mockRblResponse = $this->convertRblV1ResponseToV2Response($mockRblResponse);

                     $mockRblResponse['data']['FetchAccStmtRes']['Header']['next_key'] = 'too_much_random_next_key';

                     return json_encode($mockRblResponse);
                 }

                 $mockRblResponse = $this->convertRblV1ResponseToV2Response($this->getRblNoDataResponse());

                 $mockRblResponse['data']['FetchAccStmtRes']['Header']['Status_Desc'] = "No Records Found";

                 return json_encode($mockRblResponse);
             });

        $this->app->instance('mozart', $mock);

        $basDetailsBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNull($basDetailsBeforeTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        // Adding a valid pagination key here. As the timestamp in pagination key is older than 4 weeks, this pagination key should not be used.
        // If this pagination key gets picked up then the mozart mock will return 0 records and fail assertions in the test case.
        $this->fixtures->edit(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS,
                              $basDetailsBeforeTest[BasDetails\Entity::ID],
                              [BasDetails\Entity::CREATED_AT => 1523781340,
                               BasDetails\Entity::PAGINATION_KEY => '1523781340_random']);

        $this->ba->cronAuth();

        $this->testData[__FUNCTION__] = $this->testData['testRblAccountStatementV2ApiCase1'];

        $this->setupForRblAccountStatement();

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RBL_STATEMENT_FETCH_V2_API_MAX_RECORDS => 1]);

        $this->startTest();

        $basEntities = $this->getDbEntities(EntityConstants::BANKING_ACCOUNT_STATEMENT);

        $this->assertEquals(3, count($basEntities));

        $basActual = $basEntities[1]->toArray();

        $externalActual = $this->getDbEntityById(EntityConstants::EXTERNAL, $basActual[BasEntity::ENTITY_ID])->toArray();

        $externalId = str_after($externalActual[ExternalEntity::ID], 'ext_');

        $externalTxnId = $externalActual[ExternalEntity::TRANSACTION_ID];

        $txnActual = $this->getDbEntityById(EntityConstants::TRANSACTION, $externalTxnId)->toArray();

        $basDetailsAfterTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNotNull($basDetailsAfterTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        // new format of pagination key for RBL: 'timestamp_key'. Example: '1523781340_random'
        $this->assertEquals($postedDateTimestamp->timestamp . '_too_much_random_next_key', $basDetailsAfterTest[BasDetails\Entity::PAGINATION_KEY]);

        // This is to check code's backward compatability. Mock for pagination key 'abcdefg' is not present. So it will return 0 records.
        // But since the pagination key is in older format, it should be converted to 'timestamp_abcdefg'
        $this->fixtures->edit(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS,
                              $basDetailsAfterTest[BasDetails\Entity::ID],
                              [BasDetails\Entity::PAGINATION_KEY => 'abcdefg']);

        $this->ba->cronAuth();
        $this->startTest();

        $basDetailsAfterTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertEquals($postedDateTimestamp->timestamp . '_abcdefg', $basDetailsAfterTest[BasDetails\Entity::PAGINATION_KEY]);

        $this->assertEquals($txnActual[TransactionEntity::POSTED_AT], $basActual[BasEntity::POSTED_DATE]);

        $basExpected = [
            BasEntity::MERCHANT_ID           => $txnActual[TransactionEntity::MERCHANT_ID],
            BasEntity::BANK_TRANSACTION_ID   => 'S807068',
            BasEntity::TYPE                  => 'debit',
            BasEntity::AMOUNT                => 10095,
            BasEntity::BALANCE               => 11355,
            BasEntity::POSTED_DATE           => $postedDateTimestamp->timestamp,
            BasEntity::TRANSACTION_DATE      => 1451932200,
            BasEntity::DESCRIPTION           => '123456-Z',
            BasEntity::CHANNEL               => 'rbl',
            BasEntity::ENTITY_ID             => $externalId,
            BasEntity::ENTITY_TYPE           => EntityConstants::EXTERNAL,
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

    public function testRBLAccountStatementFetchExistingAccounts()
    {
        $this->setMockRazorxTreatment([RazorxTreatment::BAS_FETCH_RE_ARCH          => 'on',
                                       RazorxTreatment::RBL_V2_BAS_API_INTEGRATION => 'on']);

        $this->app['rzp.mode'] = EnvMode::TEST;

        $mock = Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $responseCount = 0;

        $mock->shouldReceive('sendRawRequest')
             ->andReturnUsing(function(array $request) use (& $responseCount){

                 $responseCount++;

                 $requestData = json_decode($request['content'], true);

                 if ((array_key_exists('from_date',$requestData['entities']['attempt']) === true) and
                     ($responseCount === 1))
                 {
                     $this->assertEquals("01-04-2017", $requestData['entities']['attempt']['from_date']);
                     $mockRblResponse =  $this->getRblDataResponseForExistingAccounts();

                     return json_encode($this->convertRblV1ResponseToV2Response($mockRblResponse));
                 }

                 $mockRblResponse = $this->convertRblV1ResponseToV2Response($this->getRblNoDataResponse());

                 $mockRblResponse['data']['FetchAccStmtRes']['Header']['Status_Desc'] = "No Records Found";

                 return json_encode($mockRblResponse);
             });

        $this->app->instance('mozart', $mock);

        $basDetailsBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNull($basDetailsBeforeTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        $this->fixtures->edit(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS,
                              $basDetailsBeforeTest[BasDetails\Entity::ID],
                              [BasDetails\Entity::CREATED_AT => 1516000000]);

        $this->ba->cronAuth();

        $this->testData[__FUNCTION__] = $this->testData['testRblAccountStatementV2ApiCase1'];

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RBL_STATEMENT_FETCH_V2_API_MAX_RECORDS => 1]);

        $this->startTest();

        $basEntities = $this->getDbEntities(EntityConstants::BANKING_ACCOUNT_STATEMENT);

        $this->assertEquals(2, count($basEntities));

        $basActual = $basEntities[1]->toArray();

        $externalActual = $this->getDbEntityById(EntityConstants::EXTERNAL, $basActual[BasEntity::ENTITY_ID])->toArray();

        $externalId = str_after($externalActual[ExternalEntity::ID], 'ext_');

        $externalTxnId = $externalActual[ExternalEntity::TRANSACTION_ID];

        $txnActual = $this->getDbEntityById(EntityConstants::TRANSACTION, $externalTxnId)->toArray();

        $basDetailsAfterTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNotNull($basDetailsAfterTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        $this->assertEquals($txnActual[TransactionEntity::POSTED_AT], $basActual[BasEntity::POSTED_DATE]);

        $basExpected = [
            BasEntity::MERCHANT_ID           => $txnActual[TransactionEntity::MERCHANT_ID],
            BasEntity::BANK_TRANSACTION_ID   => 'S807068',
            BasEntity::TYPE                  => 'debit',
            BasEntity::AMOUNT                => 10095,
            BasEntity::BALANCE               => 11355,
            BasEntity::POSTED_DATE           => 1451937993,
            BasEntity::TRANSACTION_DATE      => 1451932200,
            BasEntity::DESCRIPTION           => '123456-Z',
            BasEntity::CHANNEL               => 'rbl',
            BasEntity::ENTITY_ID             => $externalId,
            BasEntity::ENTITY_TYPE           => EntityConstants::EXTERNAL,
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
    public function testRblAccountStatementV2ApiNoRecordsFound()
    {
        $this->setRazorxMockForBankingAccountStatementV2Api();

        $mockedResponse = $this->convertRblV1ResponseToV2Response($this->getRblNoDataResponse());

        $mockedResponse['data']['FetchAccStmtRes']['Header']['Status_Desc'] = "No Records Found";

        $this->setMozartMockResponse($mockedResponse);

        $basBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT, true);

        $baBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT, true);

        $this->assertNull($baBeforeTest[BaEntity::LAST_STATEMENT_ATTEMPT_AT]);

        $this->ba->cronAuth();

        $this->testData[__FUNCTION__] = $this->testData['testRblAccountStatementV2ApiCase1'];

        $this->startTest();

        $basAfterTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT, true);

        $this->assertEquals($basBeforeTest[BasEntity::ID], $basAfterTest[BasEntity::ID]);

        $baAfterTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT, true);

        $this->assertNotNull($baAfterTest[BaEntity::LAST_STATEMENT_ATTEMPT_AT]);
    }

    /**
     * Case where request details are incorrect to RBL
     */
    public function testRblAccountStatementV2ApiIncorrectRequestDetails()
    {
        $this->setRazorxMockForBankingAccountStatementV2Api();

        $mockedResponse = $this->getRblInvalidDetailsResponse();

        $this->setMozartMockResponse($this->convertRblV1ResponseToV2Response($mockedResponse));

        $baBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT, true);

        $this->assertNull($baBeforeTest[BaEntity::LAST_STATEMENT_ATTEMPT_AT]);

        $this->ba->cronAuth();

        $this->startTest();

        $baAfterTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT, true);

        $this->assertNotNull($baAfterTest[BaEntity::LAST_STATEMENT_ATTEMPT_AT]);
    }

    /**
     * Case where a field in base64 encoded response is empty
     */
    public function testRblAccountStatementV2ApiEmptyFieldInResponse()
    {
        $mockedResponse = $response = $this->getRblDataResponse();

        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][0]['pstdDate'] = '';

        $this->setRazorxMockForBankingAccountStatementV2Api();

        $this->setMozartMockResponse($this->convertRblV1ResponseToV2Response($mockedResponse));

        $this->ba->cronAuth();

        $this->startTest();
    }

    /**
     * Case where a field is missing in base64 encoded response
     */
    public function testRblAccountStatementV2ApiMissingFieldInResponse()
    {
        $this->setRazorxMockForBankingAccountStatementV2Api();

        $mockedResponse = $this->getRblMissingFieldMalFormedDataV2ApiResponse();

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

    /**
     * Bank is not sending cms ref number in the single payments api response for IFT mode. Hence FTS is appending
     * gateway reference number at the end of description of IFT transactions. Recon needs to happen by picking
     * the end 10 characters and match with gateway ref no. in fta table.
     *
     * Only for IFT mode.
     */
    public function testRblAccountStatementTxnMappingForIFTUsingGatewayRefNo()
    {

        $this->mockLedgerSns(0);

        $channel = Channel::RBL;

        $this->setupForRblPayout($channel, 20000000, FundTransfer\Mode::RTGS);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(1770, $payout['fees']);
        $this->assertEquals(270, $payout['tax']);
        $this->assertEquals('Bbg7e4oKCgaube', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], [
            'status'       => 'initiated',
            'amount'       => '104',
            'utr'          => 'UTIBH20106341692',
            'initiated_at' => 1451937960]);

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], [
            'cms_ref_no'     => 'S5',
            'utr'            => 'UTIBH20106341692',
            'gateway_ref_no' => 'jaMesBond7',
            'mode'           => Payout\Mode::IFT]);

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
        // Appending gateway ref. no. at the end of description.
        $txn = $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][0];
        $txn['transactionSummary']['txnDesc'] = 'UTIBH20106341692 Vivek Karna HDFC RZPJAMESBOND7    ';
        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][0] = $txn;

        $txn['txnBalance']['amountValue'] = '300199.95';
        $txn['transactionSummary']['txnType'] = 'C';
        $txn['txnSrlNo'] = '2';
        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][1] = $txn;

        $this->setMozartMockResponse($mockedResponse);

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->cronAuth();
        $this->startTest();

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $payout = $this->getDbLastEntity('payout');
        $reversal = $this->getDbLastEntity('reversal');

        $this->assertEquals(EntityConstants::PAYOUT, $basEntries[0]['entity_type']);
        $this->assertEquals($payout['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($payout['transaction_id'], $basEntries[0]['transaction_id']);
        $this->assertEquals(Payout\Status::REVERSED, $payout[Payout\Entity::STATUS]);
        $this->assertEquals(Payout\Mode::RTGS, $payout[Payout\Entity::MODE]);

        $this->assertEquals(EntityConstants::REVERSAL, $basEntries[1]['entity_type']);
        $this->assertEquals($payout['id'], $reversal['entity_id']);
    }

    /**
     * Bank is not sending cms ref number in the single payments api response for IFT mode. Hence FTS is appending
     * gateway reference number at the end of description of IFT transactions. Recon needs to happen by picking
     * the end 10 characters and match with gateway ref no. in fta table.
     *
     * Only for IFT mode.
     *
     * asserting payout and reversal events to ledger
     */
    public function testRblAccountStatementTxnMappingForIFTUsingGatewayRefNoWithLedgerShadow()
    {
        $this->fixtures->merchant->addFeatures([Features::DA_LEDGER_JOURNAL_WRITES]);

        $ledgerSnsPayloadArray = [];
        $this->mockLedgerSns(4, $ledgerSnsPayloadArray);

        $channel = Channel::RBL;

        $this->setupForRblPayout($channel, 20000000, FundTransfer\Mode::RTGS);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(1770, $payout['fees']);
        $this->assertEquals(270, $payout['tax']);
        $this->assertEquals('Bbg7e4oKCgaube', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], [
            'status'       => 'initiated',
            'amount'       => '104',
            'utr'          => 'UTIBH20106341692',
            'initiated_at' => 1451937960]);

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], [
            'cms_ref_no'     => 'S5',
            'utr'            => 'UTIBH20106341692',
            'gateway_ref_no' => 'jaMesBond7',
            'mode'           => Payout\Mode::IFT]);

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
        // Appending gateway ref. no. at the end of description.
        $txn = $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][0];
        $txn['transactionSummary']['txnDesc'] = 'UTIBH20106341692 Vivek Karna HDFC RZPJAMESBOND7    ';
        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][0] = $txn;

        $txn['txnBalance']['amountValue'] = '300199.95';
        $txn['transactionSummary']['txnType'] = 'C';
        $txn['txnSrlNo'] = '2';
        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][1] = $txn;

        $this->setMozartMockResponse($mockedResponse);

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->cronAuth();
        $this->startTest();

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $payout = $this->getDbLastEntity('payout');
        $reversal = $this->getDbLastEntity('reversal');

        $this->assertEquals(EntityConstants::PAYOUT, $basEntries[0]['entity_type']);
        $this->assertEquals($payout['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($payout['transaction_id'], $basEntries[0]['transaction_id']);
        $this->assertEquals(Payout\Status::REVERSED, $payout[Payout\Entity::STATUS]);
        $this->assertEquals(Payout\Mode::RTGS, $payout[Payout\Entity::MODE]);

        $this->assertEquals(EntityConstants::REVERSAL, $basEntries[1]['entity_type']);
        $this->assertEquals($payout['id'], $reversal['entity_id']);

        $transactorTypeArray = [
            'da_payout_processed',
            'da_payout_processed_recon',
            'da_payout_reversed',
            'da_payout_reversed_recon',
        ];

        $transactorIdArray = [
            $payout->getPublicId(),
            $payout->getPublicId(),
            $reversal->getPublicId(),
            $reversal->getPublicId(),
        ];

        $apiTransactionIdArray = [
            $payout->getTransactionId(),
            null,
            $reversal->getTransactionId(),
            null,
        ];

        for ($index = 0; $index<count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('test', $ledgerRequestPayload['mode']);
            $this->assertEquals($transactorIdArray[$index], $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals('1770', $ledgerRequestPayload['commission']);
            $this->assertEquals('270', $ledgerRequestPayload['tax']);
            $this->assertEquals($transactorTypeArray[$index], $ledgerRequestPayload['transactor_event']);
            $this->assertArrayNotHasKey('fee_accounting', $ledgerRequestPayload['additional_params']);
            if (!empty($apiTransactionIdArray[$index])) {
                $this->assertEquals($apiTransactionIdArray[$index], $ledgerRequestPayload['api_transaction_id']);
            } else {
                $this->assertArrayNotHasKey('api_transaction_id', $ledgerRequestPayload);
            }
        }
    }

    public function testRblAccountStatementTxnMappingForIFTFeePayoutUsingGatewayRefNoWithLedgerShadow()
    {
        $this->fixtures->merchant->addFeatures([Features::DA_LEDGER_JOURNAL_WRITES]);

        $ledgerSnsPayloadArray = [];
        $this->mockLedgerSns(2, $ledgerSnsPayloadArray);

        $channel = Channel::RBL;

        $this->setupForRblPayout($channel, 20000000, FundTransfer\Mode::RTGS);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(1770, $payout['fees']);
        $this->assertEquals(270, $payout['tax']);
        $this->assertEquals('Bbg7e4oKCgaube', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], [
            'status'       => 'initiated',
            'amount'       => '104',
            'utr'          => 'UTIBH20106341692',
            'purpose'      => 'rzp_fees',
            'initiated_at' => 1451937960,
        ]);

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], [
            'cms_ref_no'     => 'S5',
            'utr'            => 'UTIBH20106341692',
            'gateway_ref_no' => 'jaMesBond7',
            'mode'           => Payout\Mode::IFT]);

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
        // Appending gateway ref. no. at the end of description.
        $txn = $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][0];
        $txn['transactionSummary']['txnDesc'] = 'UTIBH20106341692 Vivek Karna HDFC RZPJAMESBOND7    ';
        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][0] = $txn;

        $txn['txnBalance']['amountValue'] = '300199.95';
        $txn['transactionSummary']['txnType'] = 'C';
        $txn['txnSrlNo'] = '2';
        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][1] = $txn;

        $this->setMozartMockResponse($mockedResponse);

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->cronAuth();
        $this->startTest();

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $payout = $this->getDbLastEntity('payout');
        $reversal = $this->getDbLastEntity('reversal');

        $this->assertEquals(EntityConstants::PAYOUT, $basEntries[0]['entity_type']);
        $this->assertEquals($payout['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($payout['transaction_id'], $basEntries[0]['transaction_id']);
        $this->assertEquals(Payout\Status::REVERSED, $payout[Payout\Entity::STATUS]);
        $this->assertEquals(Payout\Mode::RTGS, $payout[Payout\Entity::MODE]);

        $this->assertEquals(EntityConstants::REVERSAL, $basEntries[1]['entity_type']);
        $this->assertEquals($payout['id'], $reversal['entity_id']);

        $transactorTypeArray = [
            'da_fee_payout_processed',
            'da_fee_payout_reversed',
        ];

        $transactorIdArray = [
            $payout->getPublicId(),
            $reversal->getPublicId(),
        ];

        $apiTransactionIdArray = [
            $payout->getTransactionId(),
            $reversal->getTransactionId(),
        ];

        for ($index = 0; $index<count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('test', $ledgerRequestPayload['mode']);
            $this->assertEquals($transactorIdArray[$index], $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals('1770', $ledgerRequestPayload['commission']);
            $this->assertEquals('104', $ledgerRequestPayload['amount']);
            $this->assertEquals('270', $ledgerRequestPayload['tax']);
            $this->assertEquals($apiTransactionIdArray[$index], $ledgerRequestPayload['api_transaction_id']);
            $this->assertEquals($transactorTypeArray[$index], $ledgerRequestPayload['transactor_event']);
            $this->assertArrayNotHasKey('fee_accounting', $ledgerRequestPayload['additional_params']);
        }
    }

    // If more than two fta get matched using gateway ref no then we raise slack alert and link the BAS to external.
    public function testRblAccountStatementTxnMappingForIFTUsingGatewayRefNoWithMultipleMatches()
    {
        $channel = Channel::RBL;

        $this->setupForRblPayout($channel, 104, FundTransfer\Mode::IFT);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], [
            'status'       => 'initiated',
            'amount'       => '104',
            'utr'          => 'UTIBH20106341692',
            'initiated_at' => 1451937960]);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], [
            'cms_ref_no'     => 'S5',
            'utr'            => 'UTIBH20106341692',
            'gateway_ref_no' => 'jaMesBond7']);

        $ftsCreateTransfer = new FtsFundTransfer(
            EnvMode::TEST,
            $attempt['id']);

        $ftsCreateTransfer->handle();

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Attempt\Status::INITIATED, $attempt['status']);

        $this->createRblPayout(104, FundTransfer\Mode::IFT);

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit('payout', $payout['id'], [
            'status'       => 'initiated',
            'amount'       => '104',
            'utr'          => 'UTIBH20106341692',
            'initiated_at' => 1451937960]);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $ftsCreateTransfer = new FtsFundTransfer(
            EnvMode::TEST,
            $attempt['id']);

        $ftsCreateTransfer->handle();

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Attempt\Status::INITIATED, $attempt['status']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], [
            'cms_ref_no'     => 'S9',
            'utr'            => 'UTIBH20106444492',
            'gateway_ref_no' => 'JAMesBond7']);

        $this->fixtures->edit('balance', $payout['balance_id'], ['balance' => 30019995]);

        // Fetch account statement from RBL
        $mockedResponse = $this->getRblPayoutMappingResponseRTGS();

        // changing utr so it doesn't match regex.
        // Appending gateway ref. no. at the end of description.
        $txn = $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][0];
        $txn['transactionSummary']['txnDesc'] = 'UTIBH20106341692 Vivek Karna HDFC RZPJAMESBOND7';
        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][0] = $txn;

        $txn['txnBalance']['amountValue'] = '300199.95';
        $txn['transactionSummary']['txnType'] = 'C';
        $txn['txnSrlNo'] = '2';
        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][1] = $txn;

        $this->setMozartMockResponse($mockedResponse);

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->cronAuth();
        $this->startTest();

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[0]['entity_type']);

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[1]['entity_type']);
    }

    public function testRblAccountStatementTxnMappingCase4()
    {
        $this->markTestSkipped('The flakiness in the testcase needs to be fixed. Skipping as its impacting dev-productivity.');

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

        $originalTestData = $this->testData[__FUNCTION__];

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

        $originalTestData['request']['content']['status']           = Attempt\Status::FAILED;
        $originalTestData['request']['content']['fund_transfer_id'] = $attempt['fts_transfer_id'];
        $originalTestData['request']['content']['source_id']        = $attempt['source'];

        $this->testData[__FUNCTION__] = $originalTestData;

        $this->ba->ftsAuth();

        $this->startTest();

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

    protected function convertRblV1ResponseToV2Response(array $response)
    {
        $transactions = array_key_exists('Body', $response['data']['PayGenRes']) ? $response['data']['PayGenRes']['Body']['transactionDetails'] : [];

        $header = $response['data']['PayGenRes']['Header'];

        $header2['Code'] = 200;
        $header2['next_key'] = null;
        $header2['TranID'] = $header['TranID'];
        $header2['Corp_ID'] = $header['Corp_ID'];
        $header2['Status'] = ucwords(strtolower($header['Status']));

        $transactionHeaderString = 'TRAN_ID,PTSN_NUM,TRAN_DATE,PSTD_DATE,TRAN_TYPE,C/D,TRAN_PARTICULAR,TRAN_AMT,TRAN_BALANCE';
        $transactionsString = '';

        foreach ($transactions as $transaction)
        {
            $transactionsString .= "\n";

            $transactionsString .= $transaction['txnId'] . ',';
            $transactionsString .= $transaction['txnSrlNo'] . ',';
            $transactionsString .= $transaction['transactionSummary']['txnDate'] . ',';
            $transactionsString .= $transaction['pstdDate'] . ',';
            $transactionsString .= $transaction['txnCat'] . ',';
            $transactionsString .= $transaction['transactionSummary']['txnType'] . ',';
            $transactionsString .= $transaction['transactionSummary']['txnDesc'] . ',';
            $transactionsString .= $transaction['transactionSummary']['txnAmt']['amountValue'] . ',';
            $transactionsString .= $transaction['txnBalance']['amountValue'];
        }

        $fileData = null;

        if (empty(trim($transactionsString)) === false)
        {
            $fileData = base64_encode($transactionHeaderString . $transactionsString);

            $header2['next_key'] =  'random_next_key';
        }

        $response['data']['FetchAccStmtRes']['AccStmtData']['File_Data'] = $fileData;
        $response['data']['FetchAccStmtRes']['Header'] = $header2;

        unset($response['data']['PayGenRes']);

        return $response;
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
                                    'txnDesc' => 'IMPS 202413709376 FROM EASEBUZZ PVT LTD NOD',
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

    protected function getRblDataResponseForExistingAccounts()
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
                                        'amountValue' => '11.50',
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

    protected function getRblMissingFieldMalFormedDataV2ApiResponse()
    {
        $response = [
            'data' => [
                'FetchAccStmtRes' => [
                    'AccStmtData' => [
                        'File_Data' => "VFJBTl9JRCxQVFNOX05VTSxUUkFOX0RBVEUsUFNURF9EQVRFLFRSQU5fVFlQRSxDL0QsVFJBTl9QQVJUSUNVTEFSLFRSQU5fQU1ULFRSQU5fQkFMQU5DRQogIFM0Mjk2NTUsIDEsMjAxNS0xMi0yOSxUQkksQyxERUJJVCBDQVJEIEFOTlVBTCBGRUUgMjYzNSwxMTQuNTAsMjE0LjUwCiAgUzgwNzA2OCwgMiwyMDE2LTAxLTA1LDIwMTYtMDEtMDUgMDE6MzY6MzMuMDAwLFRDSSxELDEyMzQ1Ni1aLDEwMC45NSwxMTMuNTU==",
                    ],
                    'Header' => [
                        'Corp_ID' => 'RAZORPAY',
                        'Code' => 200,
                        'Status' => 'Success',
                        'TranID' => '1',
                        'account_no' => "409000768239",
                        'bucket_no' => 1,
                        'from_date' => "25-11-2020",
                        'to_date' =>  "26-11-2020",
                        'total_bucket' =>  1

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

    protected function getRblBulkResponse()
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
                                    'amountValue' => '314.50'
                                ],
                                'txnCat' => 'TBI',
                                'txnId' => '  S429655',
                                'txnSrlNo' => ' 1',
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
                                    'amountValue' => '213.55'
                                ],
                                'txnCat' => 'TCI',
                                'txnId' => '  S807068',
                                'txnSrlNo' => '  2',
                                'valueDate' => '2016-01-05T00:00:00.000'
                            ],
                            [
                                'pstdDate' => '2016-01-05T01:36:33.000',
                                'transactionSummary' => [
                                    'instrumentId' => '',
                                    'txnAmt' => [
                                        'amountValue' => '100.00',
                                        'currencyCode' => 'INR'
                                    ],
                                    'txnDate' => '2016-01-05T00:00:00.000',
                                    'txnDesc' => 'KJ-l-123456-Z',
                                    'txnType' => 'C'
                                ],
                                'txnBalance' => [
                                    'currencyCode' => 'INR',
                                    'amountValue' => '313.55'
                                ],
                                'txnCat' => 'TCI',
                                'txnId' => '  S807089',
                                'txnSrlNo' => '  3',
                                'valueDate' => '2016-01-05T00:00:00.000'
                            ],
                            [
                                'pstdDate' => '2016-01-05T01:36:33.000',
                                'transactionSummary' => [
                                    'instrumentId' => '',
                                    'txnAmt' => [
                                        'amountValue' => '100.00',
                                        'currencyCode' => 'INR'
                                    ],
                                    'txnDate' => '2016-01-05T00:00:00.000',
                                    'txnDesc' => 'K-123456-Z',
                                    'txnType' => 'D'
                                ],
                                'txnBalance' => [
                                    'currencyCode' => 'INR',
                                    'amountValue' => '213.55'
                                ],
                                'txnCat' => 'TCI',
                                'txnId' => '  S807010',
                                'txnSrlNo' => '  4',
                                'valueDate' => '2016-01-05T00:00:00.000'
                            ]
                        ],
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

    protected function getRblBulkResponseForFetchingMissingRecords()
    {
        $response = [
            'data' => [
                'PayGenRes' => [
                    'Body' => [
                        'hasMoreData' => 'N',
                        'transactionDetails' => [
                            [
                                'pstdDate' => '2022-07-03T20:51:21.000',
                                'transactionSummary' => [
                                    'instrumentId' => '',
                                    'txnAmt' => [
                                        'amountValue' => '100.00',
                                        'currencyCode' => 'INR'
                                    ],
                                    'txnDate' => '2022-07-03T00:00:00.000',
                                    'txnDesc' => '209821810000_IMPSIN',
                                    'txnType' => 'C'
                                ],
                                'txnBalance' => [
                                    'currencyCode' => 'INR',
                                    'amountValue' => '100.00'
                                ],
                                'txnCat' => 'TCI',
                                'txnId' => '  S429654',
                                'txnSrlNo' => ' 1',
                                'valueDate' => '2022-07-03T00:00:00.000'
                            ],
                            [
                                'pstdDate' => '2022-07-03T20:51:23.000',
                                'transactionSummary' => [
                                    'instrumentId' => '',
                                    'txnAmt' => [
                                        'amountValue' => '50.00',
                                        'currencyCode' => 'INR'
                                    ],
                                    'txnDate' => '2022-07-03T00:00:00.000',
                                    'txnDesc' => 'DEBIT IMPS 20000324344829',
                                    'txnType' => 'D'
                                ],
                                'txnBalance' => [
                                    'currencyCode' => 'INR',
                                    'amountValue' => '50.00'
                                ],
                                'txnCat' => 'TCI',
                                'txnId' => '  S807089',
                                'txnSrlNo' => '  3',
                                'valueDate' => '2022-07-03T00:00:00.000'
                            ],
                            [
                                'pstdDate' => '2022-07-03T20:53:01.000',
                                'transactionSummary' => [
                                    'instrumentId' => '',
                                    'txnAmt' => [
                                        'amountValue' => '114.50',
                                        'currencyCode' => 'INR'
                                    ],
                                    'txnDate' => '2022-07-03T00:00:00.000',
                                    'txnDesc' => '209821811450_IMPSIN',
                                    'txnType' => 'C'
                                ],
                                'txnBalance' => [
                                    'currencyCode' => 'INR',
                                    'amountValue' => '164.50'
                                ],
                                'txnCat' => 'TBI',
                                'txnId' => '  S429655',
                                'txnSrlNo' => '  2',
                                'valueDate' => '2022-07-03T00:00:00.000'
                            ],
                            [
                                'pstdDate' => '2022-07-04T02:51:23.000',
                                'transactionSummary' => [
                                    'instrumentId' => '',
                                    'txnAmt' => [
                                        'amountValue' => '50.00',
                                        'currencyCode' => 'INR'
                                    ],
                                    'txnDate' => '2022-07-04T00:00:00.000',
                                    'txnDesc' => 'DEBIT IMPS 20000324344839',
                                    'txnType' => 'D'
                                ],
                                'txnBalance' => [
                                    'currencyCode' => 'INR',
                                    'amountValue' => '50.00'
                                ],
                                'txnCat' => 'TCI',
                                'txnId' => '  S807189',
                                'txnSrlNo' => '  3',
                                'valueDate' => '2022-07-04T00:00:00.000'
                            ],
                        ],
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

    protected function getRblResponseForFetchingMissingRecordsWhileCleanUp($fromDate, $toDate)
    {
        $missingTransaction = [];

        if (($fromDate === '01-02-2023') and
            ($toDate === '28-02-2023'))
        {
            $missingTransaction = [
                'pstdDate'           => '2023-02-27T20:51:21.000',
                'transactionSummary' => [
                    'instrumentId' => '',
                    'txnAmt'       => [
                        'amountValue'  => '100.00',
                        'currencyCode' => 'INR'
                    ],
                    'txnDate'      => '2023-02-27T00:00:00.000',
                    'txnDesc'      => '209821810000_IMPSIN',
                    'txnType'      => 'C'
                ],
                'txnBalance'         => [
                    'currencyCode' => 'INR',
                    'amountValue'  => '200.00'
                ],
                'txnCat'             => 'TCI',
                'txnId'              => '  S429654',
                'txnSrlNo'           => ' 1',
                'valueDate'          => '2023-02-27T00:00:00.000'
            ];
        }
        else
        {
            $missingTransaction = [
                'pstdDate'           => '2023-03-23T20:51:23.000',
                'transactionSummary' => [
                    'instrumentId' => '',
                    'txnAmt'       => [
                        'amountValue'  => '50.00',
                        'currencyCode' => 'INR'
                    ],
                    'txnDate'      => '2023-03-23T00:00:00.000',
                    'txnDesc'      => 'DEBIT IMPS 20000324344829',
                    'txnType'      => 'D'
                ],
                'txnBalance'         => [
                    'currencyCode' => 'INR',
                    'amountValue'  => '150.00'
                ],
                'txnCat'             => 'TCI',
                'txnId'              => '  S807089',
                'txnSrlNo'           => '  3',
                'valueDate'          => '2023-03-23T00:00:00.000'
            ];
        }

        $response = [
            'data'              => [
                'PayGenRes' => [
                    'Body'      => [
                        'hasMoreData'        => 'N',
                        'transactionDetails' => [
                            $missingTransaction
                        ],
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

    protected function setMozartMockResponseRblV2($mockedResponse)
    {
        $mozartMock = Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $mozartMock->shouldReceive('sendRawRequest')
                   ->andReturnUsing(function(array $request) use ($mockedResponse){

                       $requestData = json_decode($request['content'], true);

                       if (array_key_exists('from_date',$requestData['entities']['attempt']) === true)
                       {
                           return json_encode($this->convertRblV1ResponseToV2Response($mockedResponse));
                       }

                       $mockRblResponse = $this->convertRblV1ResponseToV2Response($this->getRblNoDataResponse());

                       $mockRblResponse['data']['FetchAccStmtRes']['Header']['Status_Desc'] = "No Records Found";

                       return json_encode($mockRblResponse);
                   });

        $this->app->instance('mozart', $mozartMock);
    }

    protected function addTestTransactions(): void
    {
        $mockedResponse = $this->getRblDataResponse();

        $this->setMozartMockResponse($mockedResponse);

        $this->ba->cronAuth();

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

        $this->fixtures->edit('banking_account', 'xba00000000001', [
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

        $this->fixtures->edit('banking_account', 'xba00000000001', [
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

        $this->fixtures->edit('banking_account', 'xba00000000001', [
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
            'balance' => -499999999900
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

    // Case when there is a payout of IMPS mode with UTR not present but cms ref no is present
    public function testAccountStatementFetchForUpiMode()
    {
        $this->fixtures->on('live')->merchant->addFeatures([Features::RBL_CA_UPI]);

        $this->ba->privateAuth();

        $this->createContact();

        $this->fundAccount = $this->createVpaFundAccount();

        $content = [
            'account_number'  => '2224440041626905',
            'amount'          => 104,
            'currency'        => 'INR',
            'purpose'         => 'payout',
            'narration'       => 'Rbl account payout',
            'fund_account_id' => 'fa_' . $this->fundAccount->getId(),
            'mode'            => FundTransfer\Mode::UPI,
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

        $payout1 = $this->getDbLastEntity('payout');

        $this->assertEquals('rbl', $payout1[Payout\Entity::CHANNEL]);

        $this->assertEquals(FundTransfer\Mode::UPI, $payout1[Payout\Entity::MODE]);

        $attempt1 = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('balance', $payout1['balance_id'], ['balance' => 30019995]);

        $this->fixtures->edit('payout', $payout1['id'], ['status' => 'PROCESSED', 'utr' => 120310176379]);

        $this->fixtures->edit('fund_transfer_attempt', $attempt1['id'],
                              ['status' => 'PROCESSED', 'utr' => 120310176379]);

        // fetch account statement
        $mockedResponse = $this->getRblPayoutMappingResponse();

        // set UPI type regex for description
        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][0]['transactionSummary']['txnDesc'] =
            'UPI/120310176379/Test transfer RAZORPAY/razorpayx.';

        $this->setMozartMockResponse($mockedResponse);

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->cronAuth();

        $this->startTest();

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $payoutTxn = $this->getDbLastEntity('transaction');
        $externalEntries = $this->getDbEntities('external', ['balance_id' => $payout1['balance_id']]);
        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals('rbl', $payout[Payout\Entity::CHANNEL]);

        $this->assertEquals(FundTransfer\Mode::UPI, $payout[Payout\Entity::MODE]);

        $this->assertEquals('120310176379', $basEntries[0]['utr']);

        $this->assertEquals(EntityConstants::PAYOUT, $basEntries[0]['entity_type']);
        $this->assertEquals($payout['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($payout['transaction_id'], $basEntries[0]['transaction_id']);

        $this->assertEquals(0, count($externalEntries));
        $this->assertEquals(EntityConstants::PAYOUT, $payoutTxn['type']);
        $this->assertEquals($payoutTxn['id'], $payout['transaction_id']);
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
     * This test no longer holds true as we have removed the dependency on return utr for getting credits
     */
    public function testRblReversalTxnCreationViaReturnUTR()
    {
        $this->markTestSkipped("failing intermittently on drone. will fix in re arch");

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


        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[2]['entity_type']);
        $this->assertEquals($externalEntries[1]['id'], $basEntries[2]['entity_id']);
        $this->assertEquals($externalEntries[1]['transaction_id'], $basEntries[2]['transaction_id']);
    }


    // An entry in statement has utr and CMS Ref. no. For this testcase a situation is created where the utr in statement is present as return utr of one payout
    // and CMS Ref. No. of the same statement record is linked to another payout. Hence the reversal should be created against the payout found via cms ref. no.
    // This is an arbitrary situation which should not happen on prod.
    public function testRblReversalTxnCreationViaCmsRefNoBeforeReturnUTR()
    {
        $this->markTestSkipped("failing intermittently on drone. will fix in re arch");

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
     * we get a credit row corresponding to it and map that to reversal. Note that return_utr gets saved as
     * UTR of reversal and so the linking is happening using reversal's UTR
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

    protected function getRblDataResponseForUTRExtractionFromDescription()
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
                                    'txnDesc' => '209821868111_IMPSIN',
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
                                'pstdDate' => '2015-12-29T15:58:12.000',
                                'transactionSummary' => [
                                    'instrumentId' => '',
                                    'txnAmt' => [
                                        'amountValue' => '1.00',
                                        'currencyCode' => 'INR'
                                    ],
                                    'txnDate' => '2015-12-29T00:00:00.000',
                                    'txnDesc' => 'NEFT/SFMS RTN/000311505156/MAGICBRICKS REALTY SERV',
                                    'txnType' => 'C'
                                ],
                                'txnBalance' => [
                                    'currencyCode' => 'INR',
                                    'amountValue' => '215.50'
                                ],
                                'txnCat' => 'TBI',
                                'txnId' => '  S429656',
                                'txnSrlNo' => ' 499',
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

    public function testAccountStatementLastUpdatedAtInBankingAccountsApi()
    {
        $oldDateTime = Carbon::create(2019, 07, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->app['config']->set('applications.banking_account_service.mock', true);

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

        $this->startTest(['request' => ['content' => ['to_date' => $currentTime]]]);
    }

    public function testStatementGenerationWithInvalidChannel()
    {
        $this->addTestTransactions();

        $currentTime = time();

        $this->startTest(['request' => ['content' => ['to_date' => $currentTime]]]);
    }

    public function testStatementGenerationWithInvalidFormat()
    {
        $this->addTestTransactions();

        $currentTime = time();

        $this->startTest(['request' => ['content' => ['to_date' => $currentTime]]]);
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
     * we get a credit row corresponding to it and map that to reversal. Note that return_utr gets saved as
     * UTR of reversal and so the linking is happening using reversal's UTR
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

        $this->setMockRazorxTreatment([RazorxTreatment::BAS_FETCH_RE_ARCH => 'off',
                                       RazorxTreatment::RBL_V2_BAS_API_INTEGRATION => 'off'],
                                      'on');

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

        $fta = $payout->fundTransferAttempts()->first();

        // Assert that fta status was initiated (FTS sync call).
        $this->assertEquals('initiated', $fta->getStatus());

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

        $this->assertEquals('failed', $payout->getStatus());

        $fta->reload();

        // Assert that fta status was also updated along with payout status.
        $this->assertEquals('failed', $fta->getStatus());
    }

    // Explanation for the test case:
    //
    // Criterion:
    // 1. When GATEWAY_BALANCE != STATEMENT_CLOSING_BALANCE, merchant is clearly a transacting merchant
    // 2. GATEWAY_BALANCE == STATEMENT_CLOSING_BALANCE and GATEWAY_BALANCE_CHANGE_AT > both STATEMENT_CLOSING_BALANCE_CHANGE_AT
    //    and LAST_STATEMENT_ATTEMPT_AT merchant is still a transacting merchant as this means merchant did credit and
    //    debit of equal amount after statement was fetched.
    // 3. when GATEWAY_BALANCE == STATEMENT_CLOSING_BALANCE and GATEWAY_BALANCE_CHANGE_AT is less than
    //    STATEMENT_CLOSING_BALANCE_CHANGE_AT or LAST_STATEMENT_ATTEMPT_AT, means we have fetched full statement of the
    //    merchant. Hence merchant is non-transacting.
    // 4. when GATEWAY_BALANCE == STATEMENT_CLOSING_BALANCE and GATEWAY_BALANCE_CHANGE_AT is greater than
    //    STATEMENT_CLOSING_BALANCE_CHANGE_AT but less than LAST_STATEMENT_ATTEMPT_AT, means we have fetched full statement
    //    of the merchant. This case arises when gateway balance cron gets delayed. Hence merchant is non-transacting.
    //    INACTIVE_TIME is calculated as the difference between CURRENT_TIME and max(GATEWAY_BALANCE_CHANGE_AT, STATEMENT_CLOSING_BALANCE_CHANGE_AT)
    // 5. If the merchant is inactive for more than a limit (default being 1 hour), and statement has been fetched once between the
    //    current time and max(GATEWAY_BALANCE_CHANGE_AT, STATEMENT_CLOSING_BALANCE_CHANGE_AT) then the merchant is allowed to be satisfy
    //    criteria. Hence Points 1,2,3,4 need to satisfy this criteria so that merchants with permanent discrepancies don't block
    //    bandwidth for other merchants
    //
    // Step by step cron dispatch logic on this test scenario (explained by ID).
    // 1. xba00000000001 is a transacting merchant and gets picked (Criterion point 1 and 5)
    // 2. xba00000000003 not a transacting merchant (Criterion point 3.)
    // 3. xba00000000004 is a transacting merchant (Criterion point 2 and 5)
    // 4. xba00000000002 not a transacting merchant (Criterion point 3.)
    // 5. xba00000000005 not a transacting merchant (Criterion point 4.)
    // 6. Since we can dispatch 3 account numbers and there are only 2 transacting merchants xba00000000003 gets picked
    //    up on the basis of oldest LAST_STATEMENT_ATTEMPT_AT.
    public function testStatementFetchDispatchUsingBASDetailsTable()
    {
        $setDate = Carbon::createFromTimestamp(123500, Timezone::IST);

        Carbon::setTestNow($setDate);

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::BANKING_ACCOUNT_STATEMENT_RATE_LIMIT => 3]);

        $this->fixtures->create('banking_account_statement_details', [
            BasDetails\Entity::ID                                  => 'xba00000000002',
            BasDetails\Entity::MERCHANT_ID                         => '10000000000012',
            BasDetails\Entity::BALANCE_ID                          => 'xba00000000002',
            BasDetails\Entity::ACCOUNT_NUMBER                      => '2323230041626902',
            BasDetails\Entity::CHANNEL                             => BasDetails\Channel::RBL,
            BasDetails\Entity::STATUS                              => BasDetails\Status::ACTIVE,
            BasDetails\Entity::GATEWAY_BALANCE                     => 80,
            BasDetails\Entity::STATEMENT_CLOSING_BALANCE           => 80,
            BasDetails\Entity::GATEWAY_BALANCE_CHANGE_AT           => 123440,
            BasDetails\Entity::STATEMENT_CLOSING_BALANCE_CHANGE_AT => 123455,
            BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT           => 3
        ]);

        $this->fixtures->create('banking_account_statement_details', [
            BasDetails\Entity::ID                                  => 'xba00000000003',
            BasDetails\Entity::MERCHANT_ID                         => '10000000000013',
            BasDetails\Entity::BALANCE_ID                          => 'xba00000000003',
            BasDetails\Entity::ACCOUNT_NUMBER                      => '2323230041626903',
            BasDetails\Entity::CHANNEL                             => BasDetails\Channel::RBL,
            BasDetails\Entity::STATUS                              => BasDetails\Status::ACTIVE,
            BasDetails\Entity::GATEWAY_BALANCE                     => 90,
            BasDetails\Entity::STATEMENT_CLOSING_BALANCE           => 90,
            BasDetails\Entity::GATEWAY_BALANCE_CHANGE_AT           => 123410,
            BasDetails\Entity::STATEMENT_CLOSING_BALANCE_CHANGE_AT => 123410,
            BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT           => 1
        ]);

        $this->fixtures->create('banking_account_statement_details', [
            BasDetails\Entity::ID                                  => 'xba00000000004',
            BasDetails\Entity::MERCHANT_ID                         => '10000000000014',
            BasDetails\Entity::BALANCE_ID                          => 'xba00000000004',
            BasDetails\Entity::ACCOUNT_NUMBER                      => '2323230041626904',
            BasDetails\Entity::CHANNEL                             => BasDetails\Channel::RBL,
            BasDetails\Entity::STATUS                              => BasDetails\Status::ACTIVE,
            BasDetails\Entity::GATEWAY_BALANCE                     => 100,
            BasDetails\Entity::STATEMENT_CLOSING_BALANCE           => 100,
            BasDetails\Entity::GATEWAY_BALANCE_CHANGE_AT           => 123456,
            BasDetails\Entity::STATEMENT_CLOSING_BALANCE_CHANGE_AT => 123399,
            BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT           => 2
        ]);

        $this->fixtures->create('banking_account_statement_details', [
            BasDetails\Entity::ID                                  => 'xba00000000005',
            BasDetails\Entity::MERCHANT_ID                         => '10000000000015',
            BasDetails\Entity::BALANCE_ID                          => 'xba00000000005',
            BasDetails\Entity::ACCOUNT_NUMBER                      => '2323230041626905',
            BasDetails\Entity::CHANNEL                             => BasDetails\Channel::RBL,
            BasDetails\Entity::STATUS                              => BasDetails\Status::ACTIVE,
            BasDetails\Entity::GATEWAY_BALANCE                     => 100,
            BasDetails\Entity::STATEMENT_CLOSING_BALANCE           => 100,
            BasDetails\Entity::GATEWAY_BALANCE_CHANGE_AT           => 123456,
            BasDetails\Entity::STATEMENT_CLOSING_BALANCE_CHANGE_AT => 123399,
            BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT           => 123460,
        ]);

        $this->fixtures->create('banking_account_statement_details', [
            BasDetails\Entity::ID                                  => 'xba00000000006',
            BasDetails\Entity::MERCHANT_ID                         => '10000000000016',
            BasDetails\Entity::BALANCE_ID                          => 'xba00000000006',
            BasDetails\Entity::ACCOUNT_NUMBER                      => '2323230041626906',
            BasDetails\Entity::CHANNEL                             => BasDetails\Channel::RBL,
            BasDetails\Entity::STATUS                              => BasDetails\Status::ACTIVE,
            BasDetails\Entity::GATEWAY_BALANCE                     => 101,
            BasDetails\Entity::STATEMENT_CLOSING_BALANCE           => 100,
            BasDetails\Entity::GATEWAY_BALANCE_CHANGE_AT           => 123456,
            BasDetails\Entity::STATEMENT_CLOSING_BALANCE_CHANGE_AT => 123399,
            BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT           => 123460,
            BasDetails\Entity::ACCOUNT_TYPE                        => BasDetails\AccountType::SHARED,
        ]);

        Queue::fake();

        $this->ba->cronAuth();

        $this->startTest();

        Queue::assertPushed(BankingAccountStatementJob::class, 3);

        Carbon::setTestNow();
    }

    public function testStatementFetchDispatchUsingBASDetailsTablePoolAccounts()
    {
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::BANKING_ACCOUNT_STATEMENT_RATE_LIMIT => 3]);

        $this->fixtures->create('banking_account_statement_details', [
            BasDetails\Entity::ID                                  => 'xba00000000006',
            BasDetails\Entity::MERCHANT_ID                         => '10000000000016',
            BasDetails\Entity::BALANCE_ID                          => 'xba00000000006',
            BasDetails\Entity::ACCOUNT_NUMBER                      => '2323230041626906',
            BasDetails\Entity::CHANNEL                             => BasDetails\Channel::RBL,
            BasDetails\Entity::STATUS                              => BasDetails\Status::ACTIVE,
            BasDetails\Entity::GATEWAY_BALANCE                     => 101,
            BasDetails\Entity::STATEMENT_CLOSING_BALANCE           => 100,
            BasDetails\Entity::GATEWAY_BALANCE_CHANGE_AT           => 123456,
            BasDetails\Entity::STATEMENT_CLOSING_BALANCE_CHANGE_AT => 123399,
            BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT           => 123460,
            BasDetails\Entity::ACCOUNT_TYPE                        => BasDetails\AccountType::SHARED,
        ]);

        Queue::fake();

        $this->ba->cronAuth();

        $this->startTest();

        Queue::assertPushed(BankingAccountStatementJob::class, 1);
    }

    /*
    * mapping debit record. found more than 1 unlinked payout with same utr -
    * assert remarks and non failure of acc stmt and creation of external with remarks
    * reference test cases list -
    * https://docs.google.com/spreadsheets/d/10327ImrYtC0MSRoQ-KDUGJmttSz_jXYclnzmi1ITKbE/edit#gid=0
    */
    public function testRblAccountStatementWithMoreThanOneExistingUnlinkedPayoutWithSameUtr()
    {
        $this->markTestSkipped("failing intermittently on drone. will fix in re arch");

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

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['cms_ref_no' => 'S807068', 'mode' => FundTransfer\Mode::IFT, 'status' => 'initiated']);

        $this->fixtures->edit('payout', $payout1['id'],['status'=> 'processed']);
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

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['cms_ref_no' => 'S807068', 'mode' => FundTransfer\Mode::IFT, 'status' => 'initiated']);

        $this->fixtures->edit('payout', $payout1['id'],['status'=> 'processed']);

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
        $this->fixtures->reversal->createReversalWithoutTransaction($attributes);

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
        $this->markTestSkipped("failing intermittently on drone. will fix in re arch");

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
        $this->assertNull($externalEntries[1]['remarks']);
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

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '123456', 'status' => 'initiated']);

        $this->fixtures->edit('payout', $payout1['id'], ['utr' => '123456', 'status'=> 'processed']);

        // create second payout and reverse it with same utr
        $this->createRblPayout();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '123457', 'status' => 'initiated']);

        $this->fixtures->edit('payout', $payout['id'], ['utr' => '143535', 'status'=> 'reversed']);

        // create third payout and reverse it with same return utr
        $this->createRblPayout();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '123457', 'status'=>'initiated']);

        $this->fixtures->edit('payout', $payout['id'], ['utr' => '143535', 'status' => 'reversed']);

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
        $this->markTestSkipped("failing intermittently on drone. will fix in re arch");
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

    /**
     * In this test, we have 1 debit statement and 2 credit statements
     * The first pair correspond to a reversal and the credit statement here is linked with cms ref no
     * The 3rd statement although having same cms ref no as the payout, doesn't get linked
     * because the payout was initiated before 1 week of its posted date and is masked as external
     */
    public function testRblAccountStatementWithSameCmsRefNoForNonIFTCreditMappingWithRange()
    {
        $channel = Channel::RBL;

        $this->setMockRazorxTreatment([RazorxTreatment::BAS_FETCH_RE_ARCH => 'on']);

        $this->setupForRblPayout($channel, 104, FundTransfer\Mode::IMPS);

        $payout1 = $this->getDbLastEntity('payout');

        $attempt1 = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(590, $payout1['fees']);
        $this->assertEquals(90, $payout1['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout1['pricing_rule_id']);

        $this->fixtures->edit('payout', $payout1['id'], ['status'       => 'initiated',
                                                                 'amount'       => '104',
                                                                 'utr'          => 'UTIBH20106341692',
                                                                 'initiated_at' => '1641997970',
                                                                 'created_at'   => '1642001570']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt1['id'], ['cms_ref_no' => 'S55959',
                                                                                  'utr'        => 'UTIBH20106341692']);

        $payout1->reload();

        $this->fixtures->edit('balance', $payout1['balance_id'], ['balance' => 3000]);

        $ftsCreateTransfer = new FtsFundTransfer(
            EnvMode::TEST,
            $attempt1['id']);

        $ftsCreateTransfer->handle();

        $attempt1->reload();

        $this->assertEquals(Attempt\Status::INITIATED, $attempt1['status']);

        // Fetch account statement from RBL
        $mockedResponse = $this->getRblDataResponse();

        // changing data of txn1
        $txn1 = $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][0];
        $txn1['pstdDate'] = '2022-01-10T15:58:12.000';
        $txn1['transactionSummary']['txnAmt']['amountValue'] = '1.04';
        $txn1['transactionSummary']['txnDate'] = '2022-01-10T00:00:00.000';
        $txn1['transactionSummary']['txnDesc'] = 'UTIBH20106341692-Vivek Karna HDFC';
        $txn1['transactionSummary']['txnType'] = 'D';
        $txn1['txnBalance']['amountValue'] = '2998.96';
        $txn1['txnId'] = 'S55959';
        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][0] = $txn1;

        // changing data of txn2
        $txn2 = $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][1];
        $txn2['pstdDate'] = '2022-01-18T15:50:12.000';
        $txn2['transactionSummary']['txnAmt']['amountValue'] = '1.04';
        $txn2['transactionSummary']['txnDate'] = '2022-01-18T00:00:00.000';
        $txn2['transactionSummary']['txnDesc'] = 'UTIBH20106341692 Vivek Karna HDFC';
        $txn2['transactionSummary']['txnType'] = 'C';
        $txn2['txnBalance']['amountValue'] = '3000.00';
        $txn2['txnId'] = 'S55959';
        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][1] = $txn2;

        // changing data of txn3
        $txn3 = $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][1];
        $txn3['pstdDate'] = '2022-01-22T15:50:12.000';
        $txn3['transactionSummary']['txnAmt']['amountValue'] = '1.04';
        $txn3['transactionSummary']['txnDate'] = '2022-01-20T00:00:00.000';
        $txn3['transactionSummary']['txnDesc'] = 'UTIBH20106341694 Vivek Karna HDFC';
        $txn3['transactionSummary']['txnType'] = 'C';
        $txn3['txnBalance']['amountValue'] = '3001.04';
        $txn3['txnId'] = 'S55959';
        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][2] = $txn3;

        $this->setMozartMockResponse($mockedResponse);

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->cronAuth();
        $this->startTest();

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $txn = $this->getDbLastEntity('transaction');
        $external = $this->getDbLastEntity('external');
        $reversal = $this->getDbLastEntity('reversal');
        $payout1->reload();

        $this->assertEquals(EntityConstants::PAYOUT, $basEntries[0]['entity_type']);
        $this->assertEquals($payout1['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($payout1['transaction_id'], $basEntries[0]['transaction_id']);

        $this->assertEquals(EntityConstants::REVERSAL, $basEntries[1]['entity_type']);
        $this->assertEquals($reversal['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($reversal['transaction_id'], $basEntries[1]['transaction_id']);

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[2]['entity_type']);
        $this->assertEquals($external['id'], $basEntries[2]['entity_id']);
        $this->assertEquals($external['transaction_id'], $basEntries[2]['transaction_id']);

        $this->assertEquals(EntityConstants::EXTERNAL, $txn['type']);
        $this->assertEquals($txn['id'], $external['transaction_id']);
    }

    /**
     * In this test, it is assumed that FTS webhook for the IFT payout hasn't come yet, and the corresponding
     * BAS entity is mapped to external since it couldn't find gateway ref no. to map to payout.
     * When the processed webhook arrives from FTS, it should map the payout to the debit entity.
     */
    public function testRblAccountStatementTxnMappingForIFTUsingGatewayRefNoFromFTSProcessedWebhook()
    {
        $channel = Channel::RBL;

        $this->app['rzp.mode'] = EnvMode::TEST;

        $ftsMock = Mockery::mock('RZP\Services\FTS\FundTransfer', [$this->app])->makePartial();

        $this->app->instance('fts_fund_transfer', $ftsMock);

        $ftsMock->shouldReceive('shouldAllowTransfersViaFts')
                ->andReturn([true, 'Dummy']);

        $this->setupForRblPayout($channel, 20000000, FundTransfer\Mode::RTGS);

        $payout = $this->getDbLastEntity('payout');

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], [
            'initiated_at' => 1660481812]);

        $payout->reload();

        $this->fixtures->edit('balance', $payout['balance_id'], ['balance' => 20100000]);

        // 1. Fetch account statement from RBL - debit fetch only
        $mockedResponse = [
            'data' => [
                'PayGenRes' => [
                    'Body' => [
                        'hasMoreData' => 'N',
                        'transactionDetails' => [
                            [
                                'pstdDate' => '2022-08-14T18:26:53.000',
                                'transactionSummary' => [
                                    'instrumentId' => '',
                                    'txnAmt' => [
                                        'amountValue' => '200000.00',
                                        'currencyCode' => 'INR'
                                    ],
                                    'txnDate' => '2015-12-29T00:00:00.000',
                                    'txnDesc' => 'Vivek Karna HDFC RZPJAMESBOND7 ',
                                    'txnType' => 'D'
                                ],
                                'txnBalance' => [
                                    'currencyCode' => 'INR',
                                    'amountValue' => '1000.00'
                                ],
                                'txnCat' => 'TCI',
                                'txnId' => '  S429655',
                                'txnSrlNo' => ' 498',
                                'valueDate' => '2022-08-14T00:00:00.000'
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

        $this->setMozartMockResponseRblV2($mockedResponse);

        $this->setMockRazorxTreatment([RazorxTreatment::BAS_FETCH_RE_ARCH          => 'on',
                                       RazorxTreatment::RBL_V2_BAS_API_INTEGRATION => 'on']);

        BankingAccountStatementJob::dispatch('test', [
            'channel'           => Channel::RBL,
            'account_number'    => 2224440041626905
        ]);

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $external = $this->getDbLastEntity('external');

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[0]['entity_type']);
        $this->assertEquals($external['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($external['transaction_id'], $basEntries[0]['transaction_id']);
        $this->assertEquals(Payout\Status::INITIATED, $payout[Payout\Entity::STATUS]);
        $this->assertEquals(Payout\Mode::RTGS, $payout[Payout\Entity::MODE]);

        // 2. Initiate FTS status update webhook, status received as processed
        $ftaTestData = $this->testData['testRblSlackAlertThrownForRecon'];
        $ftaTestData['request']['content'] = [
            'bank_account_type'   => "CURRENT",
            'bank_processed_time' => "",
            'bank_status_code'    => "SUCCESS",
            'channel'             => "RBL",
            'extra_info'          => [
                'beneficiary_name' => "Test IFT Payout",
                'cms_ref_no'       => 'PKJS5YtsjaMesBond7'
            ],
            'failure_reason'      => "",
            'fund_transfer_id'    => $attempt['fts_transfer_id'],
            'gateway_ref_no'      => "jaMesBond7",
            'mode'                => 'IFT',
            'source_id'           => $attempt['source_id'],
            'source_type'         => $attempt['source_type'],
            'status'              => Payout\Status::PROCESSED,
            'utr'                 => 'PKJS5YtsjaMesBond7',
        ];

        $ftaTestData['response'] = [
            'content' => []
        ];

        unset($ftaTestData['exception']);

        $this->ba->ftsAuth();

        $this->startTest($ftaTestData);

        $payout->reload();
        $attempt->reload();
        $basEntity = $basEntries[0]->reload();

        $this->assertEquals('PKJS5YtsjaMesBond7', $attempt['utr']);
        $this->assertEquals('jaMesBond7', $attempt['gateway_ref_no']);
        $this->assertEquals($payout['transaction_id'], $basEntity['transaction_id']);
        $this->assertEquals(EntityConstants::PAYOUT, $basEntity['entity_type']);
        $this->assertEquals($payout['id'], $basEntity['entity_id']);
        $this->assertEquals(Payout\Status::PROCESSED, $payout[Payout\Entity::STATUS]);
        $this->assertEquals(Payout\Mode::RTGS, $payout[Payout\Entity::MODE]);
        $this->assertEquals(Payout\Mode::IFT, $attempt[Payout\Entity::MODE]);
    }

    /**
     * In this test, it is assumed that FTS webhook for the IFT payout hasn't come yet, and the corresponding
     * BAS debit and credit entries are mapped to external since it couldn't find gateway ref no. to map to payout.
     * When the failed webhook arrives from FTS, it should map the payout to the debit entity and create a reversed
     * entity mapped to credit BAS.
     */
    public function testRblAccountStatementTxnMappingForIFTUsingGatewayRefNoFromFailedWebhookReroutedToReversed()
    {
        $channel = Channel::RBL;

        $this->app['rzp.mode'] = EnvMode::TEST;

        $ftsMock = Mockery::mock('RZP\Services\FTS\FundTransfer', [$this->app])->makePartial();

        $this->app->instance('fts_fund_transfer', $ftsMock);

        $ftsMock->shouldReceive('shouldAllowTransfersViaFts')
                ->andReturn([true, 'Dummy']);

        $this->setupForRblPayout($channel, 20000000, FundTransfer\Mode::RTGS);

        $payout = $this->getDbLastEntity('payout');

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], [
            'initiated_at' => 1660481812]);

        $payout->reload();

        $this->fixtures->edit('balance', $payout['balance_id'], ['balance' => 20100000]);

        // 1. Fetch account statement from RBL - debit fetch only
        $mockedResponse = [
            'data' => [
                'PayGenRes' => [
                    'Body' => [
                        'hasMoreData' => 'N',
                        'transactionDetails' => [
                            [
                                'pstdDate' => '2022-08-14T18:26:53.000',
                                'transactionSummary' => [
                                    'instrumentId' => '',
                                    'txnAmt' => [
                                        'amountValue' => '200000.00',
                                        'currencyCode' => 'INR'
                                    ],
                                    'txnDate' => '2015-12-29T00:00:00.000',
                                    'txnDesc' => 'UTIBH20106341692 Vivek Karna HDFC RZPJAMESBOND7 ',
                                    'txnType' => 'D'
                                ],
                                'txnBalance' => [
                                    'currencyCode' => 'INR',
                                    'amountValue' => '1000.00'
                                ],
                                'txnCat' => 'TCI',
                                'txnId' => '  S429655',
                                'txnSrlNo' => ' 498',
                                'valueDate' => '2022-08-14T00:00:00.000'
                            ],
                            [
                                'pstdDate' => '2022-08-14T18:26:54.000',
                                'transactionSummary' => [
                                    'instrumentId' => '',
                                    'txnAmt' => [
                                        'amountValue' => '200000.00',
                                        'currencyCode' => 'INR'
                                    ],
                                    'txnDate' => '2015-12-29T00:00:00.000',
                                    'txnDesc' => 'UTIBH20106341692 Vivek Karna HDFC RZPJAMESBOND7 Refund ',
                                    'txnType' => 'C'
                                ],
                                'txnBalance' => [
                                    'currencyCode' => 'INR',
                                    'amountValue' => '201000.00'
                                ],
                                'txnCat' => 'TBI',
                                'txnId' => '  S429655',
                                'txnSrlNo' => ' 499',
                                'valueDate' => '2022-08-14T00:00:00.000'
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

        $this->setMozartMockResponseRblV2($mockedResponse);

        $this->setMockRazorxTreatment([RazorxTreatment::BAS_FETCH_RE_ARCH          => 'on',
                                       RazorxTreatment::RBL_V2_BAS_API_INTEGRATION => 'on']);

        BankingAccountStatementJob::dispatch('test', [
            'channel'           => Channel::RBL,
            'account_number'    => 2224440041626905
        ]);

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);

        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[0]['entity_type']);
        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[1]['entity_type']);

        $this->assertEquals(Payout\Status::INITIATED, $payout[Payout\Entity::STATUS]);
        $this->assertEquals(Payout\Mode::RTGS, $payout[Payout\Entity::MODE]);

        // 2. Initiate FTS status update webhook, status received as failed
        $ftaTestData = $this->testData['testRblSlackAlertThrownForRecon'];
        $ftaTestData['request']['content'] = [
            'bank_account_type'   => "CURRENT",
            'bank_processed_time' => "",
            'bank_status_code'    => "BENE_PSP_OFFLINE",
            'channel'             => "RBL",
            'extra_info'          => [
                'beneficiary_name' => "Test IFT Payout",
                'cms_ref_no'       => 'PKJS5YtsjaMesBond7'
            ],
            'failure_reason'      => "",
            'fund_transfer_id'    => $attempt['fts_transfer_id'],
            'gateway_ref_no'      => "jaMesBond7",
            'mode'                => 'IFT',
            'source_id'           => $attempt['source_id'],
            'source_type'         => $attempt['source_type'],
            'status'              => Payout\Status::FAILED,
            'utr'                 => 'PKJS5YtsjaMesBond7',
        ];

        $ftaTestData['response'] = [
            'content' => []
        ];

        unset($ftaTestData['exception']);

        $this->ba->ftsAuth();

        $this->startTest($ftaTestData);

        $payout->reload();
        $attempt->reload();

        $reversal = $this->getDbLastEntity('reversal');
        $debitBasEntity = $basEntries[0]->reload();
        $creditBasEntity = $basEntries[1]->reload();

        $this->assertEquals('PKJS5YtsjaMesBond7', $attempt['utr']);
        $this->assertEquals('jaMesBond7', $attempt['gateway_ref_no']);
        $this->assertEquals($payout['transaction_id'], $debitBasEntity['transaction_id']);
        $this->assertEquals(EntityConstants::PAYOUT, $debitBasEntity['entity_type']);
        $this->assertEquals($payout['id'], $debitBasEntity['entity_id']);
        $this->assertEquals($reversal['transaction_id'], $creditBasEntity['transaction_id']);
        $this->assertEquals(EntityConstants::REVERSAL, $creditBasEntity['entity_type']);
        $this->assertEquals($reversal['id'], $creditBasEntity['entity_id']);
        $this->assertEquals(Payout\Status::REVERSED, $payout[Payout\Entity::STATUS]);
        $this->assertEquals(Payout\Status::FAILED, $attempt[Payout\Entity::STATUS]);
        $this->assertEquals(Payout\Mode::RTGS, $payout[Payout\Entity::MODE]);
        $this->assertEquals(Payout\Mode::IFT, $attempt[Payout\Entity::MODE]);
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

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '123456', 'status' => 'initiated']);

        $this->fixtures->edit('payout', $payout1['id'], ['utr' => '123456', 'status' => 'processed']);

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

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['cms_ref_no' => 'S807069', 'mode' => FundTransfer\Mode::IFT, 'status' => 'initiated']);

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'reversed']);

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

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['cms_ref_no' => 'S807069', 'mode' => FundTransfer\Mode::IFT, 'status' => 'initiated']);

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'reversed']);

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

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '123456', 'status' => 'initiated']);

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'processed','utr' => '123456']);

        // create second payout with same utr
        $this->createRblPayout();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated', 'utr' => '123456']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '123456', 'status' => 'initiated']);

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
        $this->markTestSkipped('dependency on return utr is removed. hence this test is not needed');

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

        sleep(1);

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
        $this->assertNull($externalEntries[1]['remarks']);
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

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '123456', 'status'=> 'initiated']);

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'processed']);

        // create second payout with same utr
        $this->createRblPayout();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated', 'utr' => '123456']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '123456', 'status'=> 'initiated']);


        $this->fixtures->edit('payout', $payout['id'], ['status' => 'processed']);
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

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '123456', 'status' => 'initiated']);

        $this->fixtures->edit('payout', $payout1['id'], ['utr' => '123456', 'return_utr' => '143535', 'status' => 'reversed']);

        // create another payout and reverse it with same return utr
        $this->createRblPayout();

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '123457', 'status' => 'initiated']);

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
        $this->assertNull($externalEntries[1]['remarks']);
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

    // whenever statement is fetched, BAS Details table is updated with statement closing balance and time of update.
    // Since time of update will differ, assertion of exact epoch value is not done.
    public function testRblStatementClosingBalanceUpdateInBASDetails()
    {
        $mockedResponse = $this->getRblDataResponse();

        $this->setMozartMockResponse($mockedResponse);

        $baBeforeTest = $this->getDbEntity(EntityConstants::BANKING_ACCOUNT, [BaEntity::ACCOUNT_NUMBER => '2224440041626905', BaEntity::CHANNEL => BankingAccount\Channel::RBL]);

        $this->assertNotNull($baBeforeTest);

        $this->assertNull($baBeforeTest[BaEntity::LAST_STATEMENT_ATTEMPT_AT]);

        $basDetail = $this->getDbEntity('banking_account_statement_details', [BasDetails\Entity::ACCOUNT_NUMBER => '2224440041626905']);

        $this->assertNotNull($basDetail);

        $this->testData[__FUNCTION__] =  $this->testData['testRblAccountStatementCase1'];

        $this->ba->cronAuth();

        $this->startTest();

        $basDetail = $this->getDbEntity('banking_account_statement_details', [BasDetails\Entity::ACCOUNT_NUMBER => '2224440041626905']);

        $this->assertNotNull($basDetail);

        $detailsExpected = [
            BasDetails\Entity::MERCHANT_ID               => $baBeforeTest[BasDetails\Entity::MERCHANT_ID],
            BasDetails\Entity::BALANCE_ID                => $baBeforeTest[BasDetails\Entity::BALANCE_ID],
            BasDetails\Entity::ACCOUNT_NUMBER            => $baBeforeTest[BasDetails\Entity::ACCOUNT_NUMBER],
            BasDetails\Entity::CHANNEL                   => $baBeforeTest[BasDetails\Entity::CHANNEL],
            BasDetails\Entity::STATUS                    => BasDetails\Status::ACTIVE,
            BasDetails\Entity::STATEMENT_CLOSING_BALANCE => 11355,
        ];

        $this->assertArraySubset($detailsExpected, $basDetail->toArray(), true);

        $this->assertNotNull($basDetail[BasDetails\Entity::STATEMENT_CLOSING_BALANCE_CHANGE_AT]);

        $this->assertNotNull($basDetail[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);
    }

    // whenever balance is fetched, BAS Details table is updated with fetched gateway balance and time of update.
    // Since time of update will depend on when this test is performed, assertion of exact epoch value is not done.
    public function testGatewayBalanceCreateInBASDetailsTable()
    {
        $this->markTestSkipped('not needed anymore in new flow');
        /** @var BankingAccount\Entity $baBeforeTest */
        $baBeforeTest = $this->getDbEntity(EntityConstants::BANKING_ACCOUNT, [BaEntity::ACCOUNT_NUMBER => '2224440041626905', BaEntity::CHANNEL => BankingAccount\Channel::RBL]);

        $this->fixtures->edit(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, 'xba00000000001', [BasDetails\Entity::ACCOUNT_NUMBER => '2224440041626900']);

        $this->assertNull($baBeforeTest->getBalanceLastFetchedAt());

        $basDetail = $this->getDbEntity('banking_account_statement_details', [BasDetails\Entity::ACCOUNT_NUMBER => '2224440041626905']);

        $this->assertNull($basDetail);

        $this->mockMozartResponseForFetchingBalanceFromRblGateway(100);

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RBL_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_RATE_LIMIT => 1]);

        $this->ba->cronAuth();

        $this->startTest();

        /** @var BankingAccount\Entity $baAfterCronRuns */
        $baAfterCronRuns = $this->getDbEntity(EntityConstants::BANKING_ACCOUNT, [BaEntity::ACCOUNT_NUMBER => '2224440041626905', BaEntity::CHANNEL => BankingAccount\Channel::RBL]);

        $this->assertNotNull($baAfterCronRuns->getBalanceLastFetchedAt());

        $basDetail = $this->getDbEntity('banking_account_statement_details', [BasDetails\Entity::ACCOUNT_NUMBER => '2224440041626905']);

        $this->assertNotNull($basDetail);

        $detailsExpected = [
            BankingAccount\Entity::MERCHANT_ID           => $baBeforeTest[BankingAccount\Entity::MERCHANT_ID],
            BankingAccount\Entity::BALANCE_ID            => $baBeforeTest[BankingAccount\Entity::BALANCE_ID],
            BankingAccount\Entity::ACCOUNT_NUMBER        => $baBeforeTest[BankingAccount\Entity::ACCOUNT_NUMBER],
            BankingAccount\Entity::CHANNEL               => $baBeforeTest[BankingAccount\Entity::CHANNEL],
            BankingAccount\Entity::STATUS                => 'active',
            BankingAccount\Entity::GATEWAY_BALANCE       => 10000,
            BasDetails\Entity::STATEMENT_CLOSING_BALANCE => 0,
        ];

        $this->assertArraySubset($detailsExpected, $basDetail->toArray(), true);

        $this->assertNotNull($basDetail->toArray()['gateway_balance_change_at']);
    }

    public function testGatewayBalanceUpdateInBASDetailsTable()
    {
        $basDetail = $this->getDbLastEntity('banking_account_statement_details');

        $this->assertNotNull($basDetail);

        $this->mockMozartResponseForFetchingBalanceFromRblGateway(5);

        $this->ba->cronAuth();
        $this->testData[__FUNCTION__] = $this->testData['testGatewayBalanceCreateInBASDetailsTable'];
        $this->startTest();

        /** @var BankingAccount\Entity $baAfterCronRuns */
        $baAfterCronRuns = $this->getDbEntity(EntityConstants::BANKING_ACCOUNT, [BaEntity::ACCOUNT_NUMBER => '2224440041626905', BaEntity::CHANNEL => BankingAccount\Channel::RBL]);

        $this->assertNotNull($baAfterCronRuns->getBalanceLastFetchedAt());

        $basDetail = $this->getDbEntities('banking_account_statement_details');

        $this->assertCount(1, $basDetail);

        $this->assertEquals(500, $basDetail[0][BasDetails\Entity::GATEWAY_BALANCE]);

        $this->assertNotNull($basDetail[0][BasDetails\Entity::GATEWAY_BALANCE_CHANGE_AT]);

        $this->assertEquals(0, $basDetail[0][BasDetails\Entity::STATEMENT_CLOSING_BALANCE]);
    }

    public function testCreateBASDetailsTable()
    {
        $this->ba->adminAuth();

        $this->testData[__FUNCTION__]['request']['content'] = [
            BasDetails\Entity::ACCOUNT_NUMBER => "2224440041626906",
            BasDetails\Entity::CHANNEL        => BasDetails\Channel::RBL,
            BasDetails\Entity::MERCHANT_ID    => "10000000000000",
            BasDetails\Entity::ACCOUNT_TYPE   => BasDetails\AccountType::SHARED,
            BasDetails\Entity::BALANCE_ID     => "bal00000000000"
        ];

        $this->startTest();

        $basDetail = $this->getDbEntity('banking_account_statement_details', [BasDetails\Entity::ACCOUNT_NUMBER => '2224440041626906']);

        $this->assertNotNull($basDetail);

        $detailsExpected = [
            BasDetails\Entity::MERCHANT_ID               => "10000000000000",
            BasDetails\Entity::BALANCE_ID                => "bal00000000000",
            BasDetails\Entity::ACCOUNT_NUMBER            => "2224440041626906",
            BasDetails\Entity::CHANNEL                   => "rbl",
            BasDetails\Entity::STATUS                    => 'active',
            BasDetails\Entity::GATEWAY_BALANCE           => 0,
            BasDetails\Entity::STATEMENT_CLOSING_BALANCE => 0,
            BasDetails\Entity::ACCOUNT_TYPE              => BasDetails\AccountType::SHARED
        ];

        $this->assertArraySubset($detailsExpected, $basDetail->toArray(), true);
    }

    public function testCreateBASDetailsTableWithInvalidAccountType()
    {
        $this->ba->adminAuth();

        $this->testData[__FUNCTION__]['request']['content'] = [
            BasDetails\Entity::ACCOUNT_NUMBER => "2224440041626906",
            BasDetails\Entity::CHANNEL        => BasDetails\Channel::RBL,
            BasDetails\Entity::MERCHANT_ID    => "10000000000000",
            BasDetails\Entity::ACCOUNT_TYPE   => "escrow",
            BasDetails\Entity::BALANCE_ID     => "bal00000000000"
        ];

        $this->startTest();

        $basDetail = $this->getDbEntity('banking_account_statement_details', [BasDetails\Entity::ACCOUNT_NUMBER => '2224440041626906']);

        $this->assertNull($basDetail);
    }

    public function testActiveBankingAccountIsPickedInBalanceFetch()
    {
        $basDetail = $this->getDbLastEntity('banking_account_statement_details');

        $this->assertNotNull($basDetail);

        $mozartServiceMock = Mockery::mock('RZP\Services\Mozart')->makePartial();

        $mozartServiceMock->shouldReceive('sendMozartRequest')
                          ->andReturnUsing(function(string $service, string $channel, string $action, array $request) {
                              $accountNumber = $request['source_account']['account_number'];

                              self::assertEquals(2224440041626905, $accountNumber);

                              return [
                                  'data' => [
                                      'success'                       => true,
                                      Rbl\Fields::GET_ACCOUNT_BALANCE => [
                                          Rbl\Fields::BODY => [
                                              Rbl\Fields::BAL_AMOUNT => [
                                                  Rbl\Fields::AMOUNT_VALUE => 5
                                              ]
                                          ]
                                      ]
                                  ]
                              ];
                          });

        $this->app->instance('mozart', $mozartServiceMock);

        /** @var Balance\Entity $balance */
        $balance = $this->getDbEntity(EntityConstants::BALANCE, [BaEntity::MERCHANT_ID => '10000000000000']);

        $this->fixtures->create('banking_account', [
            'account_number'        => '2224440041626906',
            'account_type'          => 'current',
            'merchant_id'           => '10000000000000',
            'balance_id'            => $balance->getId(),
            'channel'               => 'rbl',
            'status'                => 'archived',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ]);

        $this->ba->cronAuth();

        $request = [
            'method' => 'put',
            'url'    => '/banking_accounts/gateway/rbl/balance',
        ];
        $this->makeRequestAndGetContent($request);

        /** @var BankingAccount\Entity $bankingAccount */
        $bankingAccount = $this->getDbEntity(EntityConstants::BANKING_ACCOUNT,
                                             [BaEntity::CHANNEL => BankingAccount\Channel::RBL,
                                              BaEntity::STATUS  => BankingAccount\Status::ACTIVATED]);

        $basDetail = $this->getDbEntities('banking_account_statement_details');

        $this->assertCount(1, $basDetail);

        $this->assertEquals(500, $basDetail[0][BasDetails\Entity::GATEWAY_BALANCE]);

        $this->assertNotNull($basDetail[0][BasDetails\Entity::GATEWAY_BALANCE_CHANGE_AT]);
    }

    // Due to discrepancies on bank side where new records can appear in few seconds, we prefer not to save
    // latest records within time range set using $offset to maintain order.
    // Ref. rbl incident: https://razorpay.slack.com/archives/CM9230B5Y/p1615457898201700
    public function testRblAccountStatementWithDelayAddedInSavingRecords()
    {
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RBL_BANKING_ACCOUNT_STATEMENT_CRON_ATTEMPT_DELAY => 60]);

        $mockedResponse = $this->getRblDataResponse();

        $txns = $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'];

        $txns[1]['pstdDate'] = Carbon::now(Timezone::IST)->format('Y-m-d\TH:i:s.000');

        $txns[0]['pstdDate'] = Carbon::now(Timezone::IST)->subSeconds(90)->format('Y-m-d\TH:i:s.000');

        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'] = $txns;

        $this->setMozartMockResponse($mockedResponse);

        $baBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT, true);

        $this->assertNull($baBeforeTest[BaEntity::LAST_STATEMENT_ATTEMPT_AT]);

        $basdBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNull($basdBeforeTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        $this->ba->cronAuth();

        $this->setupForRblAccountStatement();

        $testData = $this->testData['testRblAccountStatementCase1'];

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();

        $basRecords = $this->getDbEntities(EntityConstants::BANKING_ACCOUNT_STATEMENT);

        $this->assertEquals(1, count($basRecords));

        $basActual = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT, true);

        $externalActual = $this->getLastEntity(EntityConstants::EXTERNAL, true);

        $externalId = str_after($externalActual[ExternalEntity::ID], 'ext_');

        $externalTxnId = $externalActual[ExternalEntity::TRANSACTION_ID];

        $this->txnEntity = $this->getDbEntityById(EntityConstants::TRANSACTION, $externalTxnId);

        $txnActual = $this->txnEntity->toArray();

        $baAfterTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT, true);

        $this->assertNotNull($baAfterTest[BaEntity::LAST_STATEMENT_ATTEMPT_AT]);

        $basdAfterTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNotNull($basdAfterTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        $this->assertEquals($txnActual[TransactionEntity::POSTED_AT], $basActual[BasEntity::POSTED_DATE]);

        $basExpected = [
            BasEntity::MERCHANT_ID           => $txnActual[TransactionEntity::MERCHANT_ID],
            BasEntity::BANK_TRANSACTION_ID   => 'S429655',
            BasEntity::TYPE                  => 'credit',
            BasEntity::AMOUNT                => 11450,
            BasEntity::BALANCE               => 21450,
            BasEntity::TRANSACTION_DATE      => 1451327400,
            BasEntity::DESCRIPTION           => 'IMPS 202413709376 FROM EASEBUZZ PVT LTD NOD',
            BasEntity::CHANNEL               => 'rbl',
            BasEntity::ENTITY_ID             => $externalId,
            BasEntity::ENTITY_TYPE           => $externalActual[ExternalEntity::ENTITY],
            BasEntity::TRANSACTION_ID        => $txnActual[TransactionEntity::ID],
        ];

        $this->assertArraySubset($basExpected, $basActual, true);
    }

    // Due to discrepancies on bank side where new records can appear in few seconds, we prefer not to save
    // latest records within time range set using $offset to maintain order.
    // Ref. rbl incident: https://razorpay.slack.com/archives/CM9230B5Y/p1615457898201700
    public function testRblAccountStatementWithDelayAddedInSavingRecordsInV2Api()
    {
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RBL_BANKING_ACCOUNT_STATEMENT_CRON_ATTEMPT_DELAY => 60]);

        $mockedResponse = $this->getRblDataResponse();

        $txns = $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'];

        $txns[1]['pstdDate'] = Carbon::now(Timezone::IST)->format('Y-m-d\TH:i:s.000');

        $txns[0]['pstdDate'] = Carbon::now(Timezone::IST)->subSeconds(90)->format('Y-m-d\TH:i:s.000');

        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'] = $txns;

        $baBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT, true);

        $this->assertNull($baBeforeTest[BaEntity::LAST_STATEMENT_ATTEMPT_AT]);

        $basdBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNull($basdBeforeTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        $this->ba->cronAuth();

        $mock = Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $mock->shouldReceive('sendRawRequest')
             ->andReturnUsing(function(array $request) use ($mockedResponse){

                 $requestData = json_decode($request['content'], true);

                 if (array_key_exists('from_date',$requestData['entities']['attempt']) === true)
                 {
                     return json_encode($this->convertRblV1ResponseToV2Response($mockedResponse));
                 }

                 $mockRblResponse = $this->convertRblV1ResponseToV2Response($this->getRblNoDataResponse());

                 $mockRblResponse['data']['FetchAccStmtRes']['Header']['Status_Desc'] = "No Records Found";

                 return json_encode($mockRblResponse);
             });

        $this->app->instance('mozart', $mock);

        $this->setRazorxMockForBankingAccountStatementV2Api();

        $testData = $this->testData['testRblAccountStatementCase1'];

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();

        $basRecords = $this->getDbEntities(EntityConstants::BANKING_ACCOUNT_STATEMENT);

        $this->assertEquals(1, count($basRecords));

        $basActual = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT, true);

        $externalActual = $this->getLastEntity(EntityConstants::EXTERNAL, true);

        $externalId = str_after($externalActual[ExternalEntity::ID], 'ext_');

        $externalTxnId = $externalActual[ExternalEntity::TRANSACTION_ID];

        $this->txnEntity = $this->getDbEntityById(EntityConstants::TRANSACTION, $externalTxnId);

        $txnActual = $this->txnEntity->toArray();

        $baAfterTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT, true);

        $this->assertNotNull($baAfterTest[BaEntity::LAST_STATEMENT_ATTEMPT_AT]);

        $basdAfterTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNotNull($basdAfterTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        $this->assertEquals($txnActual[TransactionEntity::POSTED_AT], $basActual[BasEntity::POSTED_DATE]);

        $basExpected = [
            BasEntity::MERCHANT_ID           => $txnActual[TransactionEntity::MERCHANT_ID],
            BasEntity::BANK_TRANSACTION_ID   => 'S429655',
            BasEntity::TYPE                  => 'credit',
            BasEntity::AMOUNT                => 11450,
            BasEntity::BALANCE               => 21450,
            BasEntity::TRANSACTION_DATE      => 1451327400,
            BasEntity::DESCRIPTION           => 'IMPS 202413709376 FROM EASEBUZZ PVT LTD NOD',
            BasEntity::CHANNEL               => 'rbl',
            BasEntity::ENTITY_ID             => $externalId,
            BasEntity::ENTITY_TYPE           => $externalActual[ExternalEntity::ENTITY],
            BasEntity::TRANSACTION_ID        => $txnActual[TransactionEntity::ID],
        ];

        $this->assertArraySubset($basExpected, $basActual, true);
    }

    public function testRblAccountStatementFetchV2Dispatch()
    {
        $this->ba->cronAuth();

        $request = [
            'url'       => '/banking_account_statement/process/rbl',
            'method'    => 'POST'
        ];

        $this->flushCache();

        (new AdminService)->setConfigKeys([ConfigKey::BANKING_ACCOUNT_STATEMENT_RATE_LIMIT => 1]);

        (new AdminService)->setConfigKeys([ConfigKey::ACCOUNT_STATEMENT_V2_FLOW => ["2224440041626905"]]);

        Queue::fake();

        $this->makeRequestAndGetContent($request);

        Queue::assertPushed(RblBankingAccountStatementJob::class, 1);
    }

    /**
     * Here we are checking that merchant with ACCOUNT_STATEMENT_V2_FLOW feature enabled
     * goes through new flow and that its 2 bas rows get saved and linked
     */
    public function testRblAccountStatementFetchV2()
    {
        (new Admin\Service)->setConfigKeys([
            Admin\ConfigKey::ACCOUNT_STATEMENT_V2_FLOW => ['2224440041626905']]);

        $this->fixtures->create('banking_account_statement',
            [
                'type'                      => 'credit',
                'amount'                    => '10000',
                'channel'                   => 'rbl',
                'account_number'            => 2224440041626905,
                'bank_transaction_id'       => 'SDHDH',
                'balance'                   => 20000,
                'transaction_date'          => 1584987183,
                'posted_date'               => 1584987183,
            ]);

        $mockedResponse = $this->getRblDataResponse();

        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][0]['txnBalance']['amountValue'] = '314.50';
        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][0]['txnAmt']['amountValue'] = '114.50';
        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][1]['txnAmt']['amountValue'] = '100.95';
        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][1]['txnBalance']['amountValue'] = '213.55';

        $this->setMozartMockResponse($mockedResponse);

        $baBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT, true);
        $this->assertNull($baBeforeTest[BaEntity::LAST_STATEMENT_ATTEMPT_AT]);

        $basdBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNull($basdBeforeTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        BankingAccountStatementJob::dispatch('test', [
            'channel'           => Channel::RBL,
            'account_number'    => 2224440041626905
        ]);

        $transactions = $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'];

        $txn = last($transactions);

        $basActual = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT, true);

        $externalActual = $this->getLastEntity(EntityConstants::EXTERNAL, true);

        $externalId = str_after($externalActual[ExternalEntity::ID], 'ext_');

        $externalTxnId = $externalActual[ExternalEntity::TRANSACTION_ID];

        $this->txnEntity = $this->getDbEntityById(EntityConstants::TRANSACTION, $externalTxnId);

        $txnActual = $this->txnEntity->toArray();

        $basdAfterTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNotNull($basdAfterTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        $this->assertEquals($txnActual[TransactionEntity::POSTED_AT], $basActual[BasEntity::POSTED_DATE]);

        $basExpected = [
            BasEntity::MERCHANT_ID           => $txnActual[TransactionEntity::MERCHANT_ID],
            BasEntity::BANK_TRANSACTION_ID   => trim($txn['txnId']),
            BasEntity::TYPE                  => 'debit',
            BasEntity::AMOUNT                => 10095,
            BasEntity::BALANCE               => 21355,
            BasEntity::POSTED_DATE           => 1451937993,
            BasEntity::TRANSACTION_DATE      => 1451932200,
            BasEntity::DESCRIPTION           => trim($txn['transactionSummary']['txnDesc']),
            BasEntity::CHANNEL               => 'rbl',
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

    public function testRblAccountStatementCreditBeforeDebit()

    {
        (new Admin\Service)->setConfigKeys([
                                               Admin\ConfigKey::ACCOUNT_STATEMENT_V2_FLOW => ['2224440041626905']]);

        $this->fixtures->create('banking_account_statement',
                                [
                                    'type'                      => 'credit',
                                    'amount'                    => 1355,
                                    'channel'                   => 'rbl',
                                    'account_number'            => 2224440041626905,
                                    'bank_transaction_id'       => 'SDHDH',
                                    'balance'                   => 11355,
                                    'transaction_date'          => 1584987183,
                                    'posted_date'               => 1584987183,
                                ]);

        $this->setupForRblPayout();

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

        $this->assertEquals(Payout\Status::FAILED, $payout['status']);
        $this->assertEquals(FundTransfer\Mode::IMPS, $payout['mode']);

        $mockedResponse = $this->getRblDataResponse();

        $txn0 = $txn1 = $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][1];

        $txn0['txnBalance']['amountValue'] = '214.50';
        $txn0['transactionSummary']['txnDesc'] = 'R-' . $txn0['transactionSummary']['txnDesc'];
        $txn0['transactionSummary']['txnType'] = 'C';
        $txn0['txnSrlNo'] = '876';

        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][0] = $txn0;

        $this->setMozartMockResponse($mockedResponse);

        $baBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT, true);
        $this->assertNull($baBeforeTest[BaEntity::LAST_STATEMENT_ATTEMPT_AT]);

        $basdBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNull($basdBeforeTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        BankingAccountStatementJob::dispatch('test', [
            'channel'           => Channel::RBL,
            'account_number'    => 2224440041626905
        ]);

        $transactions = $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'];

        $txn = last($transactions);

        $basEntities = $this->getDbEntities(EntityConstants::BANKING_ACCOUNT_STATEMENT);

        $this->assertCount(3, $basEntities);

        $this->assertEquals($basEntities[1]->getEntityType(), 'reversal');

        $externals = $this->getDbEntities(EntityConstants::EXTERNAL);

        $this->assertCount(1, $externals);

        $this->assertEquals($externals[0]->getId(), $basEntities[0]->getEntityId());

        $reversals = $this->getDbEntities(EntityConstants::REVERSAL);

        $this->assertCount(1, $reversals);

        $this->assertEquals($reversals[0]->getId(), $basEntities[1]->getEntityId());

        $transactions = $this->getDbEntities(EntityConstants::TRANSACTION);

        $this->assertCount(3, $transactions);

        $basdAfterTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNotNull($basdAfterTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        $basActual = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT, true);

        $basExpected = [
            BasEntity::MERCHANT_ID         => $payout->getMerchantId(),
            BasEntity::BANK_TRANSACTION_ID => trim($txn['txnId']),
            BasEntity::TYPE                => 'debit',
            BasEntity::AMOUNT              => 10095,
            BasEntity::BALANCE             => 11355,
            BasEntity::POSTED_DATE         => 1451937993,
            BasEntity::TRANSACTION_DATE    => 1451932200,
            BasEntity::DESCRIPTION         => trim($txn['transactionSummary']['txnDesc']),
            BasEntity::CHANNEL             => 'rbl',
            BasEntity::ENTITY_TYPE         => Payout\Entity::PAYOUT,
            BasEntity::ENTITY_ID           => $payout->getId(),
        ];

        $this->assertArraySubset($basExpected, $basActual, true);
    }

    public function testRblAccountStatementCreditBeforeDebitUsingRedis()

    {
        (new Admin\Service)->setConfigKeys([
                                               Admin\ConfigKey::ACCOUNT_STATEMENT_V2_FLOW => ['2224440041626905']]);

        $this->fixtures->create('banking_account_statement',
                                [
                                    'type'                      => 'credit',
                                    'amount'                    => 1355,
                                    'channel'                   => 'rbl',
                                    'account_number'            => 2224440041626905,
                                    'bank_transaction_id'       => 'SDHDH',
                                    'balance'                   => 11355,
                                    'transaction_date'          => 1584987183,
                                    'posted_date'               => 1584987183,
                                ]);

        $this->setupForRblPayout();

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

        $this->assertEquals(Payout\Status::FAILED, $payout['status']);
        $this->assertEquals(FundTransfer\Mode::IMPS, $payout['mode']);

        $mockedResponse = $this->getRblDataResponse();

        $txn0 = $txn1 = $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][1];

        $txn0['txnBalance']['amountValue'] = '214.50';
        $txn0['transactionSummary']['txnDesc'] = 'R-' . $txn0['transactionSummary']['txnDesc'];
        $txn0['transactionSummary']['txnType'] = 'C';
        $txn0['txnSrlNo'] = '876';

        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][0] = $txn0;

        $mockedResponseOriginal = $mockedResponse;

        unset($mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][1]);
        $this->setMozartMockResponse($mockedResponse);

        $baBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT, true);
        $this->assertNull($baBeforeTest[BaEntity::LAST_STATEMENT_ATTEMPT_AT]);

        $basdBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNull($basdBeforeTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        BankingAccountStatementJob::dispatch('test', [
            'channel'           => Channel::RBL,
            'account_number'    => 2224440041626905
        ]);

        $UtrConfigKeyExpected = [
            '2224440041626905' => ["123456"]
        ];

        $UtrConfigKeyActual = (new AdminService)->getConfigKey(['key' => ConfigKey::BAS_CREDIT_BEFORE_DEBIT_UTRS]);

        $this->assertArraySubset($UtrConfigKeyExpected, $UtrConfigKeyActual);

        $this->setMozartMockResponse($mockedResponseOriginal);

        BankingAccountStatementJob::dispatch('test', [
            'channel'           => Channel::RBL,
            'account_number'    => 2224440041626905
        ]);

        $UtrConfigKeyAfterProcessing = (new AdminService)->getConfigKey(['key' => ConfigKey::BAS_CREDIT_BEFORE_DEBIT_UTRS]);

        $this->assertEmpty($UtrConfigKeyAfterProcessing);

        $transactions = $mockedResponseOriginal['data']['PayGenRes']['Body']['transactionDetails'];

        $txn = last($transactions);

        $basEntities = $this->getDbEntities(EntityConstants::BANKING_ACCOUNT_STATEMENT);

        $this->assertCount(3, $basEntities);

        $this->assertEquals($basEntities[1]->getEntityType(), 'reversal');

        $externals = $this->getDbEntities(EntityConstants::EXTERNAL);

        $this->assertCount(1, $externals);

        $this->assertEquals($externals[0]->getId(), $basEntities[0]->getEntityId());

        $reversals = $this->getDbEntities(EntityConstants::REVERSAL);

        $this->assertCount(1, $reversals);

        $this->assertEquals($reversals[0]->getId(), $basEntities[1]->getEntityId());

        $transactions = $this->getDbEntities(EntityConstants::TRANSACTION);

        $this->assertCount(3, $transactions);

        $basdAfterTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNotNull($basdAfterTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        $basActual = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT, true);

        $basExpected = [
            BasEntity::MERCHANT_ID         => $payout->getMerchantId(),
            BasEntity::BANK_TRANSACTION_ID => trim($txn['txnId']),
            BasEntity::TYPE                => 'debit',
            BasEntity::AMOUNT              => 10095,
            BasEntity::BALANCE             => 11355,
            BasEntity::POSTED_DATE         => 1451937993,
            BasEntity::TRANSACTION_DATE    => 1451932200,
            BasEntity::DESCRIPTION         => trim($txn['transactionSummary']['txnDesc']),
            BasEntity::CHANNEL             => 'rbl',
            BasEntity::ENTITY_TYPE         => Payout\Entity::PAYOUT,
            BasEntity::ENTITY_ID           => $payout->getId(),
        ];

        $this->assertArraySubset($basExpected, $basActual, true);
    }

    // this will check if we change the bulk fetch and select config key, the behaviour
    // runs as expected
    public function testRblAccountStatementFetchV2WithDifferentValueForBulkFetchAndSave()
    {
        $this->setMockRazorxTreatment([RazorxTreatment::BAS_FETCH_RE_ARCH => 'on']);

        (new Admin\Service)->setConfigKeys([
            Admin\ConfigKey::RBL_ACCOUNT_STATEMENT_RECORDS_TO_FETCH_AT_ONCE => 3]);

        (new Admin\Service)->setConfigKeys([
            Admin\ConfigKey::ACCOUNT_STATEMENT_RECORDS_TO_SAVE_AT_ONCE => 3]);


        (new Admin\Service)->setConfigKeys([
            Admin\ConfigKey::ACCOUNT_STATEMENT_RECORDS_TO_SAVE_IN_TOTAL => 3]);

        $this->fixtures->create('banking_account_statement',
            [
                'type'                      => 'credit',
                'amount'                    => '10000',
                'channel'                   => 'rbl',
                'account_number'            => 2224440041626905,
                'bank_transaction_id'       => 'SDHDH',
                'balance'                   => 20000,
                'transaction_date'          => 1584987183,
                'posted_date'               => 1584987183,
            ]);

        $mockedResponse = $this->getRblBulkResponse();

        $this->setMozartMockResponse($mockedResponse);

        $baBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT, true);
        $this->assertNull($baBeforeTest[BaEntity::LAST_STATEMENT_ATTEMPT_AT]);

        $basdBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNull($basdBeforeTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        BankingAccountStatementJob::dispatch('test', [
            'channel'           => Channel::RBL,
            'account_number'    => 2224440041626905
        ]);

        $transactions = $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'];

        $txn = last($transactions);

        $basActual = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT, true);

        $externalActual = $this->getLastEntity(EntityConstants::EXTERNAL, true);

        $externalId = str_after($externalActual[ExternalEntity::ID], 'ext_');

        $externalTxnId = $externalActual[ExternalEntity::TRANSACTION_ID];

        $this->txnEntity = $this->getDbEntityById(EntityConstants::TRANSACTION, $externalTxnId);

        $txnActual = $this->txnEntity->toArray();

        $basdAfterTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNotNull($basdAfterTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        $this->assertEquals($txnActual[TransactionEntity::POSTED_AT], $basActual[BasEntity::POSTED_DATE]);

        $basExpected = [
            BasEntity::MERCHANT_ID           => $txnActual[TransactionEntity::MERCHANT_ID],
            BasEntity::BANK_TRANSACTION_ID   => trim($txn['txnId']),
            BasEntity::TYPE                  => 'debit',
            BasEntity::AMOUNT                => 10000,
            BasEntity::BALANCE               => 21355,
            BasEntity::POSTED_DATE           => 1451937993,
            BasEntity::TRANSACTION_DATE      => 1451932200,
            BasEntity::DESCRIPTION           => trim($txn['transactionSummary']['txnDesc']),
            BasEntity::CHANNEL               => 'rbl',
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

        // checking few extra things like the balance and ledger both have correct values of balance and bas has
        // correct balance and txn_id and entity_id
        $basEntities = $this->getDbEntities('banking_account_statement');

        $transactionEntities = $this->getDbEntities('transaction');

        $externalEntities = $this->getDbEntities('external');

        $balance = $this->getDbLastEntity('balance');
        $this->assertEquals(21355, $balance['balance']);

        $this->assertEquals($externalEntities[1]['amount'], $basEntities[1]['amount']);
        $this->assertEquals($transactionEntities[1]['amount'], $basEntities[1]['amount']);
        $this->assertEquals('external', $basEntities[1]['entity_type']);
        $this->assertEquals($externalEntities[1]['id'], $basEntities[1]['entity_id']);
        $this->assertEquals($transactionEntities[1]['balance'], $basEntities[1]['balance']);
        $this->assertEquals(11450, $basEntities[1]['amount']);
    }

    // this will check if we get duplicate of a record, we are not saving it in BAS table
    // again. Tested this from logs mostly and checked right balance is getting stored in the end
    public function testRblAccountStatementFetchV2WithDuplicateRecords()
    {
        (new Admin\Service)->setConfigKeys([
            Admin\ConfigKey::ACCOUNT_STATEMENT_V2_FLOW => ['2224440041626905']]);

        (new Admin\Service)->setConfigKeys([
            Admin\ConfigKey::RBL_ACCOUNT_STATEMENT_RECORDS_TO_FETCH_AT_ONCE => 2]);

        (new Admin\Service)->setConfigKeys([
            Admin\ConfigKey::ACCOUNT_STATEMENT_RECORDS_TO_SAVE_AT_ONCE => 2]);

        $this->fixtures->create('banking_account_statement',
            [
                'type'                      => 'credit',
                'amount'                    => '10000',
                'channel'                   => 'rbl',
                'account_number'            => 2224440041626905,
                'bank_transaction_id'       => 'SDHDH',
                'balance'                   => 20000,
                'transaction_date'          => 1584987183,
                'posted_date'               => 1584987183,
            ]);

        $this->fixtures->create('banking_account_statement',
            [
                'type'                      => 'credit',
                'amount'                    => '11450',
                'channel'                   => 'rbl',
                'account_number'            => 2224440041626905,
                'bank_transaction_id'       => 'S429655',
                'balance'                   => 31450,
                'transaction_date'          => 1451327400,
                'posted_date'               => 1451384892,
                'bank_serial_number'        => 1,
                'description'               => 'DEBIT CARD ANNUAL FEE 2635',
                'category'                  => 'bank_initiated',
                'bank_instrument_id'        => '',
                'balance_currency'          => 'INR',
            ]);

        $mockedResponse = $this->getRblBulkResponse();

        $this->setMozartMockResponse($mockedResponse);

        $baBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT, true);
        $this->assertNull($baBeforeTest[BaEntity::LAST_STATEMENT_ATTEMPT_AT]);

        $basdBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNull($basdBeforeTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        BankingAccountStatementJob::dispatch('test', [
            'channel'           => Channel::RBL,
            'account_number'    => 2224440041626905
        ]);

        $transactions = $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'];

        $txn = last($transactions);

        $basActual = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT, true);

        $externalActual = $this->getLastEntity(EntityConstants::EXTERNAL, true);

        $externalId = str_after($externalActual[ExternalEntity::ID], 'ext_');

        $externalTxnId = $externalActual[ExternalEntity::TRANSACTION_ID];

        $this->txnEntity = $this->getDbEntityById(EntityConstants::TRANSACTION, $externalTxnId);

        $txnActual = $this->txnEntity->toArray();

        $basdAfterTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNotNull($basdAfterTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        $this->assertEquals($txnActual[TransactionEntity::POSTED_AT], $basActual[BasEntity::POSTED_DATE]);

        $basExpected = [
            BasEntity::MERCHANT_ID           => $txnActual[TransactionEntity::MERCHANT_ID],
            BasEntity::BANK_TRANSACTION_ID   => trim($txn['txnId']),
            BasEntity::TYPE                  => 'debit',
            BasEntity::AMOUNT                => 10000,
            BasEntity::BALANCE               => 21355,
            BasEntity::POSTED_DATE           => 1451937993,
            BasEntity::TRANSACTION_DATE      => 1451932200,
            BasEntity::DESCRIPTION           => trim($txn['transactionSummary']['txnDesc']),
            BasEntity::CHANNEL               => 'rbl',
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

        // checking few extra things like the balance and ledger both have correct values of balance and bas has
        // correct balance and txn_id and entity_id
        $basEntities = $this->getDbEntities('banking_account_statement');

        $transactionEntities = $this->getDbEntities('transaction');
        $this->assertEquals(5, count($transactionEntities));

        $externalEntities = $this->getDbEntities('external');

        $balance = $this->getDbLastEntity('balance');
        $this->assertEquals(21355, $balance['balance']);

        $this->assertEquals($externalEntities[1]['amount'], $basEntities[1]['amount']);
        $this->assertEquals($transactionEntities[1]['amount'], $basEntities[1]['amount']);
        $this->assertEquals('external', $basEntities[1]['entity_type']);
        $this->assertEquals($externalEntities[1]['id'], $basEntities[1]['entity_id']);
        $this->assertEquals($transactionEntities[1]['balance'], $basEntities[1]['balance']);
        $this->assertEquals(11450, $basEntities[1]['amount']);
    }

    public function testRblAccountStatementFetchV2ApiAndRearchFlowForDuplicateRecords()
    {
        (new Admin\Service)->setConfigKeys([
                                               Admin\ConfigKey::ACCOUNT_STATEMENT_V2_FLOW => ['2224440041626905']]);

        (new Admin\Service)->setConfigKeys([
                                               Admin\ConfigKey::RBL_ACCOUNT_STATEMENT_RECORDS_TO_FETCH_AT_ONCE => 2]);

        (new Admin\Service)->setConfigKeys([
                                               Admin\ConfigKey::ACCOUNT_STATEMENT_RECORDS_TO_SAVE_AT_ONCE => 2]);

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RBL_STATEMENT_FETCH_V2_API_MAX_RECORDS => 1]);

        $this->fixtures->create('banking_account_statement',
                                [
                                    'type'                      => 'credit',
                                    'amount'                    => '10000',
                                    'channel'                   => 'rbl',
                                    'account_number'            => 2224440041626905,
                                    'bank_transaction_id'       => 'SDHDH',
                                    'balance'                   => 20000,
                                    'transaction_date'          => 1584987183,
                                    'posted_date'               => 1584987183,
                                ]);

        $this->fixtures->create('banking_account_statement',
                                [
                                    'type'                      => 'credit',
                                    'amount'                    => '11450',
                                    'channel'                   => 'rbl',
                                    'account_number'            => 2224440041626905,
                                    'bank_transaction_id'       => 'S429655',
                                    'balance'                   => 31450,
                                    'transaction_date'          => 1451327400,
                                    'posted_date'               => 1451384892,
                                    'bank_serial_number'        => 1,
                                    'description'               => 'DEBIT CARD ANNUAL FEE 2635  ',
                                    'category'                  => 'bank_initiated',
                                    'bank_instrument_id'        => '',
                                    'balance_currency'          => 'INR',
                                ]);

        $baBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT, true);
        $this->assertNull($baBeforeTest[BaEntity::LAST_STATEMENT_ATTEMPT_AT]);

        $basdBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNull($basdBeforeTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        $mockedResponse = $this->getRblBulkResponse();

        $postedDateTimestamp = Carbon::now(Timezone::IST)->subDays(2);

        $postedDate = $postedDateTimestamp->format('d-m-Y H:i:s');

        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][3]['pstdDate'] = $postedDate;;

        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][0]['txnBalance']['amountValue'] = '122';

        $mock = Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $mock->shouldReceive('sendRawRequest')
             ->andReturnUsing(function(array $request) use ($mockedResponse){

                 $requestData = json_decode($request['content'], true);

                 if (array_key_exists('from_date',$requestData['entities']['attempt']) === true)
                 {
                     return json_encode($this->convertRblV1ResponseToV2Response($mockedResponse));
                 }

                 $mockRblResponse = $this->convertRblV1ResponseToV2Response($this->getRblNoDataResponse());

                 $mockRblResponse['data']['FetchAccStmtRes']['Header']['Status_Desc'] = "No Records Found";

                 return json_encode($mockRblResponse);
             });

        $this->app->instance('mozart', $mock);

        $this->setRazorxMockForBankingAccountStatementV2Api();

        BankingAccountStatementJob::dispatch('test', [
            'channel'           => Channel::RBL,
            'account_number'    => 2224440041626905
        ]);

        $transactions = $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'];

        $txn = last($transactions);

        $basActual = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT, true);

        $externalActual = $this->getLastEntity(EntityConstants::EXTERNAL, true);

        $externalId = str_after($externalActual[ExternalEntity::ID], 'ext_');

        $externalTxnId = $externalActual[ExternalEntity::TRANSACTION_ID];

        $this->txnEntity = $this->getDbEntityById(EntityConstants::TRANSACTION, $externalTxnId);

        $txnActual = $this->txnEntity->toArray();

        $basdAfterTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNotNull($basdAfterTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        $this->assertEquals($txnActual[TransactionEntity::POSTED_AT], $basActual[BasEntity::POSTED_DATE]);

        $basExpected = [
            BasEntity::MERCHANT_ID           => $txnActual[TransactionEntity::MERCHANT_ID],
            BasEntity::BANK_TRANSACTION_ID   => trim($txn['txnId']),
            BasEntity::TYPE                  => 'debit',
            BasEntity::AMOUNT                => 10000,
            BasEntity::BALANCE               => 21355,
            BasEntity::POSTED_DATE           => $postedDateTimestamp->timestamp,
            BasEntity::TRANSACTION_DATE      => 1451932200,
            BasEntity::DESCRIPTION           => trim($txn['transactionSummary']['txnDesc']),
            BasEntity::CHANNEL               => 'rbl',
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

        // checking few extra things like the balance and ledger both have correct values of balance and bas has
        // correct balance and txn_id and entity_id
        $basEntities = $this->getDbEntities('banking_account_statement');

        $transactionEntities = $this->getDbEntities('transaction');
        $this->assertEquals(5, count($transactionEntities));

        $externalEntities = $this->getDbEntities('external');

        $balance = $this->getDbLastEntity('balance');
        $this->assertEquals(21355, $balance['balance']);

        $this->assertEquals($externalEntities[1]['amount'], $basEntities[1]['amount']);
        $this->assertEquals($transactionEntities[1]['amount'], $basEntities[1]['amount']);
        $this->assertEquals('external', $basEntities[1]['entity_type']);
        $this->assertEquals($externalEntities[1]['id'], $basEntities[1]['entity_id']);
        $this->assertEquals($transactionEntities[1]['balance'], $basEntities[1]['balance']);
        $this->assertEquals(11450, $basEntities[1]['amount']);
    }

    public function testRblMissingAccountStatement($returnResponse = false)
    {
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RBL_MISSING_STATEMENT_FETCH_MAX_RECORDS => 25000]);

        $metricsMock = $this->createMetricsMock();

        $boolMetricCaptured = false;

        $this->mockAndCaptureCountMetric(
            Metric::MISSING_STATEMENTS_FOUND,
            $metricsMock,
            $boolMetricCaptured,
            [
                'channel'       => 'rbl',
                'is_monitoring' => false,
            ]
        );

        $this->fixtures->create('banking_account_statement',
                                [
                                    'type'                      => 'credit',
                                    'amount'                    => '10000',
                                    'channel'                   => 'rbl',
                                    'account_number'            => 2224440041626905,
                                    'bank_transaction_id'       => 'S429654',
                                    'balance'                   => 10000,
                                    'transaction_date'          => 1656786600,
                                    'posted_date'               => 1656861681,
                                    'bank_serial_number'        => 1,
                                    'description'               => 'Credit to account',
                                    'category'                  => 'customer_initiated',
                                    'bank_instrument_id'        => '',
                                    'balance_currency'          => 'INR',
                                ]);

        $this->fixtures->create('banking_account_statement',
                                [
                                    'type'                      => 'credit',
                                    'amount'                    => '11450',
                                    'channel'                   => 'rbl',
                                    'account_number'            => 2224440041626905,
                                    'bank_transaction_id'       => 'S429655',
                                    'balance'                   => 21450,
                                    'transaction_date'          => 1656786600,
                                    'posted_date'               => 1656861781,
                                    'bank_serial_number'        => 2,
                                    'description'               => 'CREDIT NEFT',
                                    'category'                  => 'bank_initiated',
                                    'bank_instrument_id'        => '',
                                    'balance_currency'          => 'INR',
                                ]);

        $basdBeforeTest = $this->getDbEntity('banking_account_statement_details', ['account_number' => '2224440041626905', 'channel' => 'rbl']);

        $this->fixtures->edit(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS,
            $basdBeforeTest[Entity::ID],
            [BasDetails\Entity::PAGINATION_KEY => 'next_key']);

        $mockedResponse = $this->getRblBulkResponseForFetchingMissingRecords();

        $this->app['rzp.mode'] = EnvMode::TEST;

        $mozartMock = Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $mozartMock->shouldReceive('sendRawRequest')
             ->andReturnUsing(function(array $request) use ($mockedResponse){

                 $requestData = json_decode($request['content'], true);

                 if (array_key_exists('from_date',$requestData['entities']['attempt']) === true)
                 {
                     return json_encode($this->convertRblV1ResponseToV2Response($mockedResponse));
                 }

                 $mockRblResponse = $this->convertRblV1ResponseToV2Response($this->getRblNoDataResponse());

                 $mockRblResponse['data']['FetchAccStmtRes']['Header']['Status_Desc'] = "No Records Found";

                 return json_encode($mockRblResponse);
             });

        $this->app->instance('mozart', $mozartMock);

        $this->ba->adminAuth();

        $response = $this->startTest();

        if ($returnResponse === true)
        {
            return $response;
        }

        $redisKey = 'missing_statements_10000000000000_2224440041626905';

        $merchantMissingStatementList = json_decode($this->app['redis']->get($redisKey), true);

        $this->app['redis']->del($redisKey);

        $basExpected = [
            BasEntity::ACCOUNT_NUMBER        => '2224440041626905',
            BasEntity::BANK_TRANSACTION_ID   => 'S807089',
            BasEntity::TYPE                  => 'debit',
            BasEntity::AMOUNT                => 5000,
            BasEntity::BALANCE               => 5000,
            BasEntity::POSTED_DATE           => 1656861683,
            BasEntity::TRANSACTION_DATE      => 1656786600,
            BasEntity::DESCRIPTION           => 'DEBIT IMPS 20000324344829',
            BasEntity::CHANNEL               => 'rbl',
        ];

        $this->assertArraySubset($basExpected, array_first($merchantMissingStatementList));

        $this->assertTrue($boolMetricCaptured);
    }

    public function testInsertRblMissingAccountStatement()
    {
        $oldDateTime = Carbon::create(2016, 1, 6, 12, 0, 0, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->testRblAccountStatementCase1();

        Queue::except(BankingAccountStatementUpdate::class);

        $this->fixtures->merchant->addFeatures([Features::DA_LEDGER_JOURNAL_WRITES]);

        $ledgerSnsPayloadArray = [];

        $this->mockLedgerSns(1, $ledgerSnsPayloadArray);

        $missingStatementsBeforeInsertion = [
            [
                'type'                => 'credit',
                'amount'              => '100',
                'currency'            => 'INR',
                'channel'             => 'rbl',
                'account_number'      => '2224440041626905',
                'bank_transaction_id' => 'S71034964',
                'balance'             => 1000100,
                'transaction_date'    => 1451327400,
                'posted_date'         => 1451384893,
                'bank_serial_number'  => 'S71034964',
                'description'         => 'INF/NEFT/023629961691/SBIN0050103/TestRBL/Boruto',
                'balance_currency'    => 'INR',
            ],
            [
                'type'                => 'debit',
                'amount'              => '100',
                'currency'            => 'INR',
                'channel'             => 'rbl',
                'account_number'      => '2224440041626905',
                'bank_transaction_id' => 'S71034965',
                'balance'             => 1000000,
                'transaction_date'    => 1451327400,
                'posted_date'         => 1451384992,
                'bank_serial_number'  => 'S71034965',
                'description'         => 'INF/NEFT/023629961692/SBIN0050103/TestRBL/Boruto',
                'balance_currency'    => 'INR',
            ]];

        (new AdminService)->setConfigKeys([ConfigKey::PREFIX . 'rx_missing_statements_insertion_limit' => 1]);

        $redisKey = 'missing_statements_10000000000000_2224440041626905';

        $this->app['redis']->set($redisKey, json_encode($missingStatementsBeforeInsertion));

        $initialBasEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);

        $initialCount = count($initialBasEntries);

        $initialBasDetails = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $initialStatementClosingBalance = $initialBasDetails[BasDetails\Entity::STATEMENT_CLOSING_BALANCE];

        $initialStatement1 = $this->getDbEntities('banking_account_statement', [
            'account_number'      => '2224440041626905',
            'bank_transaction_id' => 'S429655'
        ])[0];

        $initialStatement2 = $this->getDbEntities('banking_account_statement', [
            'account_number'      => '2224440041626905',
            'bank_transaction_id' => 'S807068'
        ])[0];

        $mockedResponse = [
            'data' => [
                'PayGenRes' => [
                    'Body' => [
                        'hasMoreData' => 'N',
                        'transactionDetails' => [
                            [
                                'pstdDate' => '2016-01-05T01:38:33.000',
                                'transactionSummary' => [
                                    'instrumentId' => '',
                                    'txnAmt' => [
                                        'amountValue' => '100.00',
                                        'currencyCode' => 'INR'
                                    ],
                                    'txnDate' => '2016-01-05T00:00:00.000',
                                    'txnDesc' => '123456-Z',
                                    'txnType' => 'D'
                                ],
                                'txnBalance' => [
                                    'currencyCode' => 'INR',
                                    'amountValue' => '14.55'
                                ],
                                'txnCat' => 'TCI',
                                'txnId' => '  S808068',
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

        $this->app['rzp.mode'] = EnvMode::TEST;

        $mozartMock = Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $mozartMock->shouldReceive('sendRawRequest')
            ->andReturnUsing(function(array $request) use ($mockedResponse){

                $requestData = json_decode($request['content'], true);

                if (array_key_exists('from_date',$requestData['entities']['attempt']) === true)
                {
                    return json_encode($this->convertRblV1ResponseToV2Response($mockedResponse));
                }

                $mockRblResponse = $this->convertRblV1ResponseToV2Response($this->getRblNoDataResponse());

                $mockRblResponse['data']['FetchAccStmtRes']['Header']['Status_Desc'] = "No Records Found";

                return json_encode($mockRblResponse);
            })->times(1);

        $this->app->instance('mozart', $mozartMock);

        $this->ba->adminAuth();

        $this->startTest();

        $merchantMissingStatementList = json_decode($this->app['redis']->get($redisKey), true);

        $this->app['redis']->del($redisKey);

        $this->assertArraySubset($missingStatementsBeforeInsertion[1], $merchantMissingStatementList[0]);

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);

        $this->assertCount($initialCount + 2, $basEntries);

        $basDetails = $this->getDbLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS);

        $this->assertNull($basDetails->getLastReconciledAt());

        $finalStatementClosingBalance = $basDetails[BasDetails\Entity::STATEMENT_CLOSING_BALANCE];

        $this->assertEquals($initialStatementClosingBalance - 9900, $finalStatementClosingBalance);

        $finalStatement1 = $this->getDbEntities('banking_account_statement', [
            'account_number'      => '2224440041626905',
            'bank_transaction_id' => 'S429655'
        ])[0];

        $finalStatement2 = $this->getDbEntities('banking_account_statement', [
            'account_number'      => '2224440041626905',
            'bank_transaction_id' => 'S807068'
        ])[0];

        $insertedStatement = $this->getDbEntities('banking_account_statement', [
            'account_number'      => '2224440041626905',
            'bank_transaction_id' => 'S71034964'
        ])[0];

        $finalInsertedStatement = $this->getDbEntities('banking_account_statement', [
            'account_number'      => '2224440041626905',
            'bank_transaction_id' => 'S808068'
        ])[0];

        $externalEntries = $this->getDbEntities('external', ['banking_account_statement_id' => $insertedStatement[BasEntity::ID]])[0];

        $this->assertEquals($initialStatement1->getBalance(), $finalStatement1->getBalance());

        $this->assertEquals($initialStatement2->getBalance() + 100, $finalStatement2->getBalance());

        $this->assertEquals($finalInsertedStatement[BasEntity::BALANCE] + 10000, $finalStatement2[BasEntity::BALANCE]);

        $this->assertGreaterThan($initialStatement1[BasEntity::ID], $insertedStatement[BasEntity::ID]);

        $this->assertLessThan($initialStatement2[BasEntity::ID], $insertedStatement[BasEntity::ID]);

        $transactorTypeArray = [
            'da_ext_credit',
        ];

        $transactorIdArray = [
            $externalEntries->getPublicId(),
        ];

        $commissionArray = [
            '',
        ];

        $taxArray = [
            '',
        ];

        $apiTransactionIdArray = [
            $externalEntries->getTransactionId(),
        ];

        for ($index = 0; $index<count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('test', $ledgerRequestPayload['mode']);
            $this->assertEquals($transactorIdArray[$index], $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals($commissionArray[$index], $ledgerRequestPayload['commission']);
            $this->assertEquals($taxArray[$index], $ledgerRequestPayload['tax']);
            $this->assertEquals($transactorTypeArray[$index], $ledgerRequestPayload['transactor_event']);
            $this->assertArrayNotHasKey('fee_accounting', $ledgerRequestPayload['additional_params']);
            if (!empty($apiTransactionIdArray[$index]))
            {
                $this->assertEquals($apiTransactionIdArray[$index], $ledgerRequestPayload['api_transaction_id']);
            }
            else
            {
                $this->assertArrayNotHasKey('api_transaction_id', $ledgerRequestPayload['additional_params']);
            }
        }

        Carbon::setTestNow();
    }

    public function testRblMissingStatementUpdateTriggerAction()
    {
        $basdBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->fixtures->edit(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS,
            $basdBeforeTest[Entity::ID],
            [BasDetails\Entity::STATUS => 'under_maintenance']);

        $paramValue =
            [
                "last_corrected_id"    => '1234567',
                "bas_id_to_amount_map" => [
                    "abcde" => -1233545,
                    "fghjk" => 3000
                ]
            ];

        $encodedParam = json_encode($paramValue);

        $redisKey = '10000000000000_2224440041626905_bas_update_params';

        $this->app['redis']->set($redisKey,$encodedParam);

        $this->ba->adminAuth();

        Queue::fake();

        $this->startTest();

        Queue::assertPushed(BankingAccountStatementUpdate::class, 1);
    }

    public function testRblMissingStatementFailedBatchUpdate()
    {
        $this->fixtures->create('banking_account_statement',
            [
                'type'                      => 'credit',
                'amount'                    => '10000',
                'channel'                   => 'rbl',
                'account_number'            => 2224440041626905,
                'bank_transaction_id'       => 'S429654',
                'balance'                   => 10000,
                'transaction_date'          => 1656786600,
                'posted_date'               => 1656861681,
                'bank_serial_number'        => 1,
                'description'               => 'Credit to account',
                'category'                  => 'customer_initiated',
                'bank_instrument_id'        => '',
                'balance_currency'          => 'INR',
            ]);

        $this->fixtures->create('banking_account_statement',
            [
                'type'                      => 'credit',
                'amount'                    => '11450',
                'channel'                   => 'rbl',
                'account_number'            => 2224440041626905,
                'bank_transaction_id'       => 'S429655',
                'balance'                   => 21450,
                'transaction_date'          => 1656786600,
                'posted_date'               => 1656861781,
                'bank_serial_number'        => 2,
                'description'               => 'CREDIT NEFT',
                'category'                  => 'bank_initiated',
                'bank_instrument_id'        => '',
                'balance_currency'          => 'INR',
            ]);

        $this->fixtures->merchant->addFeatures([Features::DA_LEDGER_JOURNAL_WRITES]);

        $initialBasDetails = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->fixtures->edit(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS,
            $initialBasDetails[Entity::ID],
            [BasDetails\Entity::STATUS => 'under_maintenance']);

        $initialStatementClosingBalance = $initialBasDetails[BasDetails\Entity::STATEMENT_CLOSING_BALANCE];

        $initialStatement1 = $this->getDbEntities('banking_account_statement', [
            'account_number'      => '2224440041626905',
            'bank_transaction_id' => 'S429654'
        ])[0];

        $initialStatement2 = $this->getDbEntities('banking_account_statement', [
            'account_number'      => '2224440041626905',
            'bank_transaction_id' => 'S429655'
        ])[0];

        $paramValue = [
            'channel' => 'rbl',
            'account_number' => '2224440041626905',
            'bas_id_to_amount_map' => [
                $initialStatement1['id'] => 10000
            ],
            'created_at' => $initialStatement1['created_at'],
            'update_before' => Carbon::now()->getTimestamp() + 5,
            'latest_corrected_id' => $initialStatement1['id'],
            'batch_number' => 0,
            'merchant_id' =>'10000000000000',
            'balance_id' => '',
        ];

        $encodedParam = json_encode($paramValue);

        $redisKey = '10000000000000_2224440041626905_bas_update_params';

        $this->app['redis']->set($redisKey,$encodedParam);

        $testData = &$this->testData['testRblMissingStatementUpdateTriggerAction'];

        $this->ba->adminAuth();

        $this->startTest($testData);

        $basDetails = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $finalStatementClosingBalance = $basDetails[BasDetails\Entity::STATEMENT_CLOSING_BALANCE];

        $this->assertEquals($initialStatementClosingBalance + 10000, $finalStatementClosingBalance);

        $finalStatement1 = $this->getDbEntities('banking_account_statement', [
            'account_number'      => '2224440041626905',
            'bank_transaction_id' => 'S429654'
        ])[0];

        $finalStatement2 = $this->getDbEntities('banking_account_statement', [
            'account_number'      => '2224440041626905',
            'bank_transaction_id' => 'S429655'
        ])[0];

        $this->assertEquals($initialStatement1->getBalance(), $finalStatement1->getBalance());

        $this->assertEquals($initialStatement2->getBalance() + 10000, $finalStatement2->getBalance());
    }

    public function testOptimiseInsertRblMissingAccountStatement()
    {
        $oldDateTime = Carbon::create(2016, 1, 6, 12, 0, 0, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->testRblAccountStatementCase1();

        Queue::except(BankingAccountStatementUpdate::class);

        $this->setMockRazorxTreatment([RazorxTreatment::OPTIMISE_INSERTION_LOGIC => 'on']);

        $this->fixtures->merchant->addFeatures([Features::DA_LEDGER_JOURNAL_WRITES]);

        $ledgerSnsPayloadArray = [];

        $this->mockLedgerSns(2, $ledgerSnsPayloadArray);

        $missingStatementsBeforeInsertion = [
            [
                'type'                => 'credit',
                'amount'              => '100',
                'currency'            => 'INR',
                'channel'             => 'rbl',
                'account_number'      => '2224440041626905',
                'bank_transaction_id' => 'S71034964',
                'balance'             => 1000100,
                'transaction_date'    => 1451327400,
                'posted_date'         => 1451384893,
                'bank_serial_number'  => 'S71034964',
                'description'         => 'INF/NEFT/023629961691/SBIN0050103/TestRBL/Boruto',
                'balance_currency'    => 'INR',
            ],
            [
                'type'                => 'debit',
                'amount'              => '100',
                'currency'            => 'INR',
                'channel'             => 'rbl',
                'account_number'      => '2224440041626905',
                'bank_transaction_id' => 'S71034965',
                'balance'             => 1000000,
                'transaction_date'    => 1451327400,
                'posted_date'         => 1451384892,
                'bank_serial_number'  => 'S71034965',
                'description'         => 'INF/NEFT/023629961692/SBIN0050103/TestRBL/Boruto',
                'balance_currency'    => 'INR',
            ]
        ];

        (new AdminService)->setConfigKeys(
            [
                ConfigKey::PREFIX . 'rx_missing_statements_insertion_limit' => 2,
                ConfigKey::PREFIX . 'retry_count_for_id_generation'         => 1,
            ]);

        $redisKey = 'missing_statements_10000000000000_2224440041626905';

        $this->app['redis']->set($redisKey, json_encode($missingStatementsBeforeInsertion));

        $initialBasEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);

        $initialCount = count($initialBasEntries);

        $initialBasDetails = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $initialStatementClosingBalance = $initialBasDetails[BasDetails\Entity::STATEMENT_CLOSING_BALANCE];

        $initialStatement1 = $this->getDbEntities('banking_account_statement', [
            'account_number'      => '2224440041626905',
            'bank_transaction_id' => 'S429655'
        ])[0];

        $initialStatement2 = $this->getDbEntities('banking_account_statement', [
            'account_number'      => '2224440041626905',
            'bank_transaction_id' => 'S807068'
        ])[0];

        $this->ba->adminAuth();

        $this->startTest();

        $merchantMissingStatementList = json_decode($this->app['redis']->get($redisKey), true);

        $this->app['redis']->del($redisKey);

        $this->assertEmpty($merchantMissingStatementList);

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);

        $this->assertCount($initialCount + 2, $basEntries);

        $basDetails = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $finalStatementClosingBalance = $basDetails[BasDetails\Entity::STATEMENT_CLOSING_BALANCE];

        $this->assertEquals($initialStatementClosingBalance, $finalStatementClosingBalance);

        $finalStatement1 = $this->getDbEntities('banking_account_statement', [
            'account_number'      => '2224440041626905',
            'bank_transaction_id' => 'S429655'
        ])[0];

        $finalStatement2 = $this->getDbEntities('banking_account_statement', [
            'account_number'      => '2224440041626905',
            'bank_transaction_id' => 'S807068'
        ])[0];

        $insertedStatement1 = $this->getDbEntities('banking_account_statement', [
            'account_number'      => '2224440041626905',
            'bank_transaction_id' => 'S71034964'
        ])[0];

        $insertedStatement2 = $this->getDbEntities('banking_account_statement', [
            'account_number'      => '2224440041626905',
            'bank_transaction_id' => 'S71034965'
        ])[0];

        $externalEntity1 = $this->getDbEntities(
            'external', ['banking_account_statement_id' => $insertedStatement1[BasEntity::ID]])[0];

        $externalEntity2 = $this->getDbEntities(
            'external', ['banking_account_statement_id' => $insertedStatement2[BasEntity::ID]])[0];

        $this->assertEquals($initialStatement1->getBalance(), $finalStatement1->getBalance());

        $this->assertEquals($initialStatement2->getBalance(), $finalStatement2->getBalance());

        $this->assertEquals($insertedStatement2[BasEntity::BALANCE], $finalStatement1[BasEntity::BALANCE]);

        $this->assertGreaterThan($initialStatement1[BasEntity::ID], $insertedStatement1[BasEntity::ID]);

        $transactorTypeArray = [
            'da_ext_credit',
            'da_ext_debit',
        ];

        $transactorIdArray = [
            $externalEntity1->getPublicId(),
            $externalEntity2->getPublicId(),
        ];

        $commissionArray = [
            "",""
        ];

        $taxArray = [
            "",""
        ];

        $apiTransactionIdArray = [
            $externalEntity1->getTransactionId(),
            $externalEntity2->getTransactionId(),
        ];

        for ($index = 0; $index < count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('test', $ledgerRequestPayload['mode']);
            $this->assertEquals($transactorIdArray[$index], $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals($commissionArray[$index], $ledgerRequestPayload['commission']);
            $this->assertEquals($taxArray[$index], $ledgerRequestPayload['tax']);
            $this->assertEquals($transactorTypeArray[$index], $ledgerRequestPayload['transactor_event']);
            $this->assertArrayNotHasKey('fee_accounting', $ledgerRequestPayload['additional_params']);
            if (!empty($apiTransactionIdArray[$index]))
            {
                $this->assertEquals($apiTransactionIdArray[$index], $ledgerRequestPayload['api_transaction_id']);
            }
            else
            {
                $this->assertArrayNotHasKey('api_transaction_id', $ledgerRequestPayload['additional_params']);
            }
        }

        Carbon::setTestNow();
    }

    // Account Statement Clean Up Tests
    public function testRblMissingAccountStatementCleanUp()
    {
        Queue::fake();

        $oldDateTime = Carbon::create(2023, 5, 2, 12, 0, 0, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RBL_MISSING_STATEMENT_FETCH_MAX_RECORDS => 25000]);

        $this->app['rzp.mode'] = EnvMode::TEST;

        $mozartMock = Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $mozartMock->shouldReceive('sendRawRequest')
                   ->andReturnUsing(function(array $request) {

                       $requestData = json_decode($request['content'], true);

                       if (array_key_exists('from_date', $requestData['entities']['attempt']) === true)
                       {
                           $fromDate = $requestData['entities']['attempt']['from_date'];
                           $toDate   = $requestData['entities']['attempt']['to_date'];

                           $mockedResponse = $this->getRblResponseForFetchingMissingRecordsWhileCleanUp($fromDate, $toDate);

                           return json_encode($this->convertRblV1ResponseToV2Response($mockedResponse));
                       }

                       $mockRblResponse = $this->convertRblV1ResponseToV2Response($this->getRblNoDataResponse());

                       $mockRblResponse['data']['FetchAccStmtRes']['Header']['Status_Desc'] = "No Records Found";

                       return json_encode($mockRblResponse);
                   })->times(2);

        $this->app->instance('mozart', $mozartMock);

        $input = [
            BasEntity::CHANNEL        => 'rbl',
            BasEntity::MERCHANT_ID    => '10000000000000',
            BasEntity::ACCOUNT_NUMBER => '2224440041626905',
            'fetch_input'             => null,
            'clean_up_config'         => [
                'mismatch_data' => [
                    [
                        'from_date'       => 1675189800,
                        'to_date'         => 1677522600,
                        'mismatch_amount' => 10000,
                        'mismatch_type'   => 'missing_credit',
                        'analysed_bas_id' => '10000000000bas',
                    ],
                    [
                        'from_date'       => 1677609000,
                        'to_date'         => 1680201000,
                        'mismatch_amount' => -5000,
                        'mismatch_type'   => 'missing_debit',
                        'analysed_bas_id' => '10000000000bas',
                    ]
                ],
                'completed'     => true
            ],
            'fetch_in_progress'       => false,
            'mismatch_amount_found'   => 0,
            'total_mismatch_amount'   => 5000,
        ];

        Queue::except(BankingAccountStatementCleanUp::class);

        (new BankingAccountStatementCleanUp(EnvMode::TEST, $input))->handle();

        $redisKey = 'missing_statements_10000000000000_2224440041626905';

        $merchantMissingStatementList = json_decode($this->app['redis']->get($redisKey), true);

        $missingStatementsExpected = [
            [
                BasEntity::ACCOUNT_NUMBER      => '2224440041626905',
                BasEntity::BANK_TRANSACTION_ID => 'S429654',
                BasEntity::TYPE                => 'credit',
                BasEntity::AMOUNT              => 10000,
                BasEntity::BALANCE             => 20000,
                BasEntity::POSTED_DATE         => 1677511281,
                BasEntity::TRANSACTION_DATE    => 1677436200,
                BasEntity::DESCRIPTION         => '209821810000_IMPSIN',
                BasEntity::CHANNEL             => 'rbl',
            ],
            [
                BasEntity::ACCOUNT_NUMBER      => '2224440041626905',
                BasEntity::BANK_TRANSACTION_ID => 'S807089',
                BasEntity::TYPE                => 'debit',
                BasEntity::AMOUNT              => 5000,
                BasEntity::BALANCE             => 15000,
                BasEntity::POSTED_DATE         => 1679584883,
                BasEntity::TRANSACTION_DATE    => 1679509800,
                BasEntity::DESCRIPTION         => 'DEBIT IMPS 20000324344829',
                BasEntity::CHANNEL             => 'rbl',
            ],
        ];

        Queue::assertPushed(BankingAccountMissingStatementInsert::class, 1);

        $this->assertEquals(2, count($merchantMissingStatementList));
        $this->assertArraySubset($missingStatementsExpected, $merchantMissingStatementList);

        $this->app['redis']->del($redisKey);

        Carbon::setTestNow();
    }

    public function testRblMissingAccountStatementCleanUpWhenNoMissingStatementsAreFound()
    {
        Queue::fake();

        $oldDateTime = Carbon::create(2023, 5, 2, 12, 0, 0, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RBL_MISSING_STATEMENT_FETCH_MAX_RECORDS => 25000]);

        $this->app['rzp.mode'] = EnvMode::TEST;

        $mozartMock = Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $mozartMock->shouldReceive('sendRawRequest')
                   ->andReturnUsing(function(array $request) {

                       $requestData = json_decode($request['content'], true);

                       if (array_key_exists('from_date', $requestData['entities']['attempt']) === true)
                       {
                           $fromDate = $requestData['entities']['attempt']['from_date'];
                           $toDate   = $requestData['entities']['attempt']['to_date'];

                           $mockedResponse = $this->getRblResponseForFetchingMissingRecordsWhileCleanUp($fromDate, $toDate);

                           return json_encode($this->convertRblV1ResponseToV2Response($mockedResponse));
                       }

                       $mockRblResponse = $this->convertRblV1ResponseToV2Response($this->getRblNoDataResponse());

                       $mockRblResponse['data']['FetchAccStmtRes']['Header']['Status_Desc'] = "No Records Found";

                       return json_encode($mockRblResponse);
                   })->times(0);

        $this->app->instance('mozart', $mozartMock);

        $input = [
            BasEntity::CHANNEL        => 'rbl',
            BasEntity::MERCHANT_ID    => '10000000000000',
            BasEntity::ACCOUNT_NUMBER => '2224440041626905',
            'fetch_input'             => null,
            'clean_up_config'         => [
                'mismatch_data' => [
                ],
                'completed'     => true
            ],
            'fetch_in_progress'       => false,
            'mismatch_amount_found'   => 0,
            'total_mismatch_amount'   => 0,
        ];

        Queue::except(BankingAccountStatementCleanUp::class);

        (new BankingAccountStatementCleanUp(EnvMode::TEST, $input))->handle();

        $redisKey = 'missing_statements_10000000000000_2224440041626905';

        $merchantMissingStatementList = json_decode($this->app['redis']->get($redisKey), true);

        Queue::assertPushed(BankingAccountMissingStatementInsert::class, 0);

        $this->assertEmpty($merchantMissingStatementList);

        $this->app['redis']->del($redisKey);

        Carbon::setTestNow();
    }

    public function testRblMissingAccountStatementCleanUpWhenBalanceMismatchIsPresentForMissingStatements()
    {
        Queue::fake();

        $oldDateTime = Carbon::create(2023, 5, 2, 12, 0, 0, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RBL_MISSING_STATEMENT_FETCH_MAX_RECORDS => 25000]);

        $this->app['rzp.mode'] = EnvMode::TEST;

        $mozartMock = Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $mozartMock->shouldReceive('sendRawRequest')
                   ->andReturnUsing(function(array $request) {

                       $requestData = json_decode($request['content'], true);

                       if (array_key_exists('from_date', $requestData['entities']['attempt']) === true)
                       {
                           $fromDate = $requestData['entities']['attempt']['from_date'];
                           $toDate   = $requestData['entities']['attempt']['to_date'];

                           $mockedResponse = $this->getRblResponseForFetchingMissingRecordsWhileCleanUp($fromDate, $toDate);

                           return json_encode($this->convertRblV1ResponseToV2Response($mockedResponse));
                       }

                       $mockRblResponse = $this->convertRblV1ResponseToV2Response($this->getRblNoDataResponse());

                       $mockRblResponse['data']['FetchAccStmtRes']['Header']['Status_Desc'] = "No Records Found";

                       return json_encode($mockRblResponse);
                   })->times(2);

        $this->app->instance('mozart', $mozartMock);

        $input = [
            BasEntity::CHANNEL        => 'rbl',
            BasEntity::MERCHANT_ID    => '10000000000000',
            BasEntity::ACCOUNT_NUMBER => '2224440041626905',
            'fetch_input'             => null,
            'clean_up_config'         => [
                'mismatch_data' => [
                    [
                        'from_date'       => 1675189800,
                        'to_date'         => 1677522600,
                        'mismatch_amount' => 10000,
                        'mismatch_type'   => 'missing_credit',
                        'analysed_bas_id' => '10000000000bas',
                    ],
                    [
                        'from_date'       => 1677609000,
                        'to_date'         => 1680201000,
                        'mismatch_amount' => -5000,
                        'mismatch_type'   => 'missing_debit',
                        'analysed_bas_id' => '10000000000bas',
                    ]
                ],
                'completed'     => true
            ],
            'fetch_in_progress'       => false,
            'mismatch_amount_found'   => 0,
            'total_mismatch_amount'   => 53000,
        ];

        Queue::except(BankingAccountStatementCleanUp::class);

        (new BankingAccountStatementCleanUp(EnvMode::TEST, $input))->handle();

        $redisKey = 'missing_statements_10000000000000_2224440041626905';

        $merchantMissingStatementList = json_decode($this->app['redis']->get($redisKey), true);

        $missingStatementsExpected = [
            [
                BasEntity::ACCOUNT_NUMBER      => '2224440041626905',
                BasEntity::BANK_TRANSACTION_ID => 'S429654',
                BasEntity::TYPE                => 'credit',
                BasEntity::AMOUNT              => 10000,
                BasEntity::BALANCE             => 20000,
                BasEntity::POSTED_DATE         => 1677511281,
                BasEntity::TRANSACTION_DATE    => 1677436200,
                BasEntity::DESCRIPTION         => '209821810000_IMPSIN',
                BasEntity::CHANNEL             => 'rbl',
            ],
            [
                BasEntity::ACCOUNT_NUMBER      => '2224440041626905',
                BasEntity::BANK_TRANSACTION_ID => 'S807089',
                BasEntity::TYPE                => 'debit',
                BasEntity::AMOUNT              => 5000,
                BasEntity::BALANCE             => 15000,
                BasEntity::POSTED_DATE         => 1679584883,
                BasEntity::TRANSACTION_DATE    => 1679509800,
                BasEntity::DESCRIPTION         => 'DEBIT IMPS 20000324344829',
                BasEntity::CHANNEL             => 'rbl',
            ],
        ];

        Queue::assertPushed(BankingAccountMissingStatementInsert::class, 0);

        $this->assertEquals(2, count($merchantMissingStatementList));
        $this->assertArraySubset($missingStatementsExpected, $merchantMissingStatementList);

        $this->app['redis']->del($redisKey);

        Carbon::setTestNow();
    }

    public function testRblMissingAccountStatementCleanUpWhenQueueDispatchFails()
    {
        Queue::fake();

        $oldDateTime = Carbon::create(2023, 5, 2, 12, 0, 0, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RBL_MISSING_STATEMENT_FETCH_MAX_RECORDS => 25000]);

        $this->app['rzp.mode'] = EnvMode::TEST;

        $mozartMock = Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $mozartMock->shouldReceive('sendRawRequest')
                   ->andReturnUsing(function(array $request) {

                       $requestData = json_decode($request['content'], true);

                       if (array_key_exists('from_date', $requestData['entities']['attempt']) === true)
                       {
                           $fromDate = $requestData['entities']['attempt']['from_date'];
                           $toDate   = $requestData['entities']['attempt']['to_date'];

                           $mockedResponse = $this->getRblResponseForFetchingMissingRecordsWhileCleanUp($fromDate, $toDate);

                           return json_encode($this->convertRblV1ResponseToV2Response($mockedResponse));
                       }

                       $mockRblResponse = $this->convertRblV1ResponseToV2Response($this->getRblNoDataResponse());

                       $mockRblResponse['data']['FetchAccStmtRes']['Header']['Status_Desc'] = "No Records Found";

                       return json_encode($mockRblResponse);
                   })->times(0);

        $this->app->instance('mozart', $mozartMock);

        $input = [
            BasEntity::CHANNEL        => 'rbl',
            BasEntity::MERCHANT_ID    => '10000000000000',
            BasEntity::ACCOUNT_NUMBER => '2224440041626905',
            'fetch_input'             => null,
            'clean_up_config'         => [
                'mismatch_data' => [
                    [
                        'from_date'       => 1675189800,
                        'to_date'         => 1677522600,
                        'mismatch_amount' => 10000,
                        'mismatch_type'   => 'missing_credit',
                        'analysed_bas_id' => '10000000000bas',
                    ],
                    [
                        'from_date'       => 1677609000,
                        'to_date'         => 1680201000,
                        'mismatch_amount' => -5000,
                        'mismatch_type'   => 'missing_debit',
                        'analysed_bas_id' => '10000000000bas',
                    ]
                ],
                'completed'     => true
            ],
            'fetch_in_progress'       => false,
            'mismatch_amount_found'   => 0,
            'total_mismatch_amount'   => 53000,
        ];

        Queue::assertNotPushed(BankingAccountStatementCleanUp::class, function($message)
        {
            throw new LogicException('random exception');
        });

        (new BankingAccountStatementCleanUp(EnvMode::TEST, $input))->handle();

        $redisKey = 'missing_statements_10000000000000_2224440041626905';

        $merchantMissingStatementList = json_decode($this->app['redis']->get($redisKey), true);

        Queue::assertPushed(BankingAccountMissingStatementInsert::class, 0);

        $this->assertEmpty($merchantMissingStatementList);

        $this->app['redis']->del($redisKey);

        Carbon::setTestNow();
    }

    public function testRblMissingAccountStatementCleanUpWhenCompletedInCleanUpConfigIsFalse()
    {
        Queue::fake();

        $oldDateTime = Carbon::create(2023, 5, 2, 12, 0, 0, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RBL_MISSING_STATEMENT_FETCH_MAX_RECORDS => 25000]);

        $this->app['rzp.mode'] = EnvMode::TEST;

        $mozartMock = Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $mozartMock->shouldReceive('sendRawRequest')
                   ->andReturnUsing(function(array $request) {

                       $requestData = json_decode($request['content'], true);

                       if (array_key_exists('from_date', $requestData['entities']['attempt']) === true)
                       {
                           $fromDate = $requestData['entities']['attempt']['from_date'];
                           $toDate   = $requestData['entities']['attempt']['to_date'];

                           $mockedResponse = $this->getRblResponseForFetchingMissingRecordsWhileCleanUp($fromDate, $toDate);

                           return json_encode($this->convertRblV1ResponseToV2Response($mockedResponse));
                       }

                       $mockRblResponse = $this->convertRblV1ResponseToV2Response($this->getRblNoDataResponse());

                       $mockRblResponse['data']['FetchAccStmtRes']['Header']['Status_Desc'] = "No Records Found";

                       return json_encode($mockRblResponse);
                   })->times(0);

        $this->app->instance('mozart', $mozartMock);

        $input = [
            BasEntity::CHANNEL        => 'rbl',
            BasEntity::MERCHANT_ID    => '10000000000000',
            BasEntity::ACCOUNT_NUMBER => '2224440041626905',
            'fetch_input'             => null,
            'clean_up_config'         => [
                'mismatch_data' => [
                    [
                        'from_date'       => 1675189800,
                        'to_date'         => 1677522600,
                        'mismatch_amount' => 10000,
                        'mismatch_type'   => 'missing_credit',
                        'analysed_bas_id' => '10000000000bas',
                    ],
                    [
                        'from_date'       => 1677609000,
                        'to_date'         => 1680201000,
                        'mismatch_amount' => -5000,
                        'mismatch_type'   => 'missing_debit',
                        'analysed_bas_id' => '10000000000bas',
                    ]
                ],
                'completed'     => false
            ],
            'fetch_in_progress'       => false,
            'mismatch_amount_found'   => 0,
            'total_mismatch_amount'   => 53000,
        ];

        (new BankingAccountStatementCleanUp(EnvMode::TEST, $input))->handle();

        $redisKey = 'missing_statements_10000000000000_2224440041626905';

        $merchantMissingStatementList = json_decode($this->app['redis']->get($redisKey), true);

        Queue::assertPushed(BankingAccountStatementCleanUp::class, 0);

        Queue::assertPushed(BankingAccountMissingStatementInsert::class, 0);

        $this->assertEmpty($merchantMissingStatementList);

        $this->app['redis']->del($redisKey);

        Carbon::setTestNow();
    }

    public function testRblMissingAccountStatementCleanUpWhenCompletedInCleanUpConfigIsWrong()
    {
        Queue::fake();

        $oldDateTime = Carbon::create(2023, 5, 2, 12, 0, 0, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RBL_MISSING_STATEMENT_FETCH_MAX_RECORDS => 25000]);

        $this->app['rzp.mode'] = EnvMode::TEST;

        $mozartMock = Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $mozartMock->shouldReceive('sendRawRequest')
                   ->andReturnUsing(function(array $request) {

                       $requestData = json_decode($request['content'], true);

                       if (array_key_exists('from_date', $requestData['entities']['attempt']) === true)
                       {
                           $fromDate = $requestData['entities']['attempt']['from_date'];
                           $toDate   = $requestData['entities']['attempt']['to_date'];

                           $mockedResponse = $this->getRblResponseForFetchingMissingRecordsWhileCleanUp($fromDate, $toDate);

                           return json_encode($this->convertRblV1ResponseToV2Response($mockedResponse));
                       }

                       $mockRblResponse = $this->convertRblV1ResponseToV2Response($this->getRblNoDataResponse());

                       $mockRblResponse['data']['FetchAccStmtRes']['Header']['Status_Desc'] = "No Records Found";

                       return json_encode($mockRblResponse);
                   })->times(0);

        $this->app->instance('mozart', $mozartMock);

        $input = [
            BasEntity::CHANNEL        => 'rbl',
            BasEntity::MERCHANT_ID    => '10000000000000',
            BasEntity::ACCOUNT_NUMBER => '2224440041626905',
            'fetch_input'             => null,
            'clean_up_config'         => [
                'mismatch_data' => [
                    [
                        'from_date'       => 1675189800,
                        'to_date'         => null,
                        'mismatch_amount' => 10000,
                        'mismatch_type'   => 'missing_credit',
                        'analysed_bas_id' => '10000000000bas',
                    ],
                ],
                'completed'     => true
            ],
            'fetch_in_progress'       => false,
            'mismatch_amount_found'   => 0,
            'total_mismatch_amount'   => 53000,
        ];

        (new BankingAccountStatementCleanUp(EnvMode::TEST, $input))->handle();

        $redisKey = 'missing_statements_10000000000000_2224440041626905';

        $merchantMissingStatementList = json_decode($this->app['redis']->get($redisKey), true);

        Queue::assertPushed(BankingAccountStatementCleanUp::class, 0);

        Queue::assertPushed(BankingAccountMissingStatementInsert::class, 0);

        $this->assertEmpty($merchantMissingStatementList);

        $this->app['redis']->del($redisKey);

        Carbon::setTestNow();
    }

    // Automated Recon Tests
    public function testE2ERblAutomatedReconFlow()
    {
        $oldDateTime = Carbon::create(2016, 1, 6, 12, 0, 0, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->testRblAccountStatementCase1();

        Queue::except(BankingAccountStatementReconNeo::class);

        Queue::except(BankingAccountStatementReconProcessNeo::class);

        Queue::except(BankingAccountStatementUpdate::class);

        $this->fixtures->merchant->addFeatures([Features::DA_LEDGER_JOURNAL_WRITES]);

        $ledgerSnsPayloadArray = [];

        $this->mockLedgerSns(1, $ledgerSnsPayloadArray);

        (new AdminService)->setConfigKeys([ConfigKey::PREFIX . 'rx_missing_statements_insertion_limit' => 1]);

        $redisKey = 'missing_statements_10000000000000_2224440041626905';

        $initialBasEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);

        $initialCount = count($initialBasEntries);

        $initialBasDetails = $this->getDbEntity('banking_account_statement_details', ['account_number' => '2224440041626905', 'channel' => 'rbl']);

        $this->fixtures->edit(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS,
                              $initialBasDetails[Entity::ID], [
                                  BasDetails\Entity::PAGINATION_KEY => 'next_key',
                                  'gateway_balance_change_at'       => Carbon::now(Timezone::IST)->subMinutes(20)->getTimestamp(),
                                  'last_reconciled_at'              => Carbon::now(Timezone::IST)->subDays(3)->getTimestamp(),
                              ]);

        $initialStatementClosingBalance = $initialBasDetails[BasDetails\Entity::STATEMENT_CLOSING_BALANCE];

        $initialStatement1 = $this->getDbEntities('banking_account_statement', [
            'account_number'      => '2224440041626905',
            'bank_transaction_id' => 'S429655'
        ])[0];

        $initialStatement2 = $this->getDbEntities('banking_account_statement', [
            'account_number'      => '2224440041626905',
            'bank_transaction_id' => 'S807068'
        ])[0];

        $mockedFetchResponse = [
            'data' => [
                'PayGenRes' => [
                    'Body' => [
                        'hasMoreData' => 'N',
                        'transactionDetails' => [
                            [
                                'pstdDate' => '2016-01-05T01:38:33.000',
                                'transactionSummary' => [
                                    'instrumentId' => '',
                                    'txnAmt' => [
                                        'amountValue' => '100.00',
                                        'currencyCode' => 'INR'
                                    ],
                                    'txnDate' => '2016-01-05T00:00:00.000',
                                    'txnDesc' => '123456-Z',
                                    'txnType' => 'D'
                                ],
                                'txnBalance' => [
                                    'currencyCode' => 'INR',
                                    'amountValue' => '14.55'
                                ],
                                'txnCat' => 'TCI',
                                'txnId' => '  S808068',
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

        $mockedMissingStatementResponse = [
            'data' => [
                'PayGenRes' => [
                    'Body' => [
                        'hasMoreData' => 'N',
                        'transactionDetails' => [
                            [
                                'pstdDate' => '2015-12-29T15:58:13.000',
                                'transactionSummary' => [
                                    'instrumentId' => '',
                                    'txnAmt' => [
                                        'amountValue' => '1.00',
                                        'currencyCode' => 'INR'
                                    ],
                                    'txnDate' => '2015-12-29T00:00:00.000',
                                    'txnDesc' => 'INF/NEFT/023629961691/SBIN0050103/TestRBL/Boruto',
                                    'txnType' => 'C'
                                ],
                                'txnBalance' => [
                                    'currencyCode' => 'INR',
                                    'amountValue' => '10001.00'
                                ],
                                'txnCat' => 'TCI',
                                'txnId' => '  S71034964',
                                'txnSrlNo' => '  5',
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

        $this->app['rzp.mode'] = EnvMode::TEST;

        $mozartMock = Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $mozartMock->shouldReceive('sendRawRequest')
                   ->andReturnUsing(function(array $request) use ($mockedFetchResponse, $mockedMissingStatementResponse){

                       $requestData = json_decode($request['content'], true);

                       if (array_key_exists('from_date', $requestData['entities']['attempt']) === true)
                       {
                           $fromDate = $requestData['entities']['attempt']['from_date'];

                           if ($fromDate === '05-01-2016')
                           {
                               return json_encode($this->convertRblV1ResponseToV2Response($mockedFetchResponse));
                           }
                           else
                           {
                               return json_encode($this->convertRblV1ResponseToV2Response($mockedMissingStatementResponse));
                           }
                       }

                       $mockRblResponse = $this->convertRblV1ResponseToV2Response($this->getRblNoDataResponse());

                       $mockRblResponse['data']['FetchAccStmtRes']['Header']['Status_Desc'] = "No Records Found";

                       return json_encode($mockRblResponse);
                   })->times(2);

        $this->app->instance('mozart', $mozartMock);

        $this->ba->cronAuth();

        $this->startTest();

        $merchantMissingStatementList = json_decode($this->app['redis']->get($redisKey), true);

        $this->app['redis']->del($redisKey);

        $this->assertEmpty($merchantMissingStatementList);

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);

        $this->assertCount($initialCount + 2, $basEntries);

        $basDetails = $this->getDbLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS);

        $expectedLastReconciledAt = Carbon::now(Timezone::IST)->subDay()->startOfDay()->getTimestamp();

        $this->assertEquals($expectedLastReconciledAt ,$basDetails['last_reconciled_at']);

        $finalStatementClosingBalance = $basDetails[BasDetails\Entity::STATEMENT_CLOSING_BALANCE];

        $this->assertEquals($initialStatementClosingBalance - 9900, $finalStatementClosingBalance);

        $finalStatement1 = $this->getDbEntities('banking_account_statement', [
            'account_number'      => '2224440041626905',
            'bank_transaction_id' => 'S429655'
        ])[0];

        $finalStatement2 = $this->getDbEntities('banking_account_statement', [
            'account_number'      => '2224440041626905',
            'bank_transaction_id' => 'S807068'
        ])[0];

        $insertedStatement = $this->getDbEntities('banking_account_statement', [
            'account_number'      => '2224440041626905',
            'bank_transaction_id' => 'S71034964'
        ])[0];

        $finalInsertedStatement = $this->getDbEntities('banking_account_statement', [
            'account_number'      => '2224440041626905',
            'bank_transaction_id' => 'S808068'
        ])[0];

        $externalEntries = $this->getDbEntities('external', ['banking_account_statement_id' => $insertedStatement[BasEntity::ID]])[0];

        $this->assertEquals($initialStatement1->getBalance(), $finalStatement1->getBalance());

        $this->assertEquals($initialStatement2->getBalance() + 100, $finalStatement2->getBalance());

        $this->assertEquals($finalInsertedStatement[BasEntity::BALANCE] + 10000, $finalStatement2[BasEntity::BALANCE]);

        $this->assertGreaterThan($initialStatement1[BasEntity::ID], $insertedStatement[BasEntity::ID]);

        $this->assertLessThan($initialStatement2[BasEntity::ID], $insertedStatement[BasEntity::ID]);

        $transactorTypeArray = [
            'da_ext_credit',
        ];

        $transactorIdArray = [
            $externalEntries->getPublicId(),
        ];

        $commissionArray = [
            '',
        ];

        $taxArray = [
            '',
        ];

        $apiTransactionIdArray = [
            $externalEntries->getTransactionId(),
        ];

        for ($index = 0; $index<count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('test', $ledgerRequestPayload['mode']);
            $this->assertEquals($transactorIdArray[$index], $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals($commissionArray[$index], $ledgerRequestPayload['commission']);
            $this->assertEquals($taxArray[$index], $ledgerRequestPayload['tax']);
            $this->assertEquals($transactorTypeArray[$index], $ledgerRequestPayload['transactor_event']);
            $this->assertArrayNotHasKey('fee_accounting', $ledgerRequestPayload['additional_params']);
            if (!empty($apiTransactionIdArray[$index]))
            {
                $this->assertEquals($apiTransactionIdArray[$index], $ledgerRequestPayload['api_transaction_id']);
            }
            else
            {
                $this->assertArrayNotHasKey('api_transaction_id', $ledgerRequestPayload['additional_params']);
            }
        }

        Carbon::setTestNow();
    }

    public function testE2ERblAutomatedReconFlowWhenAllStatementsAreAlreadyInserted()
    {
        $oldDateTime = Carbon::create(2016, 1, 6, 12, 0, 0, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->testRblAccountStatementCase1();

        $this->fixtures->create('banking_account_statement', [
            'type'                => 'credit',
            'amount'              => 100,
            'channel'             => 'rbl',
            'account_number'      => 2224440041626905,
            'bank_transaction_id' => 'S71034964',
            'balance'             => 1000100,
            'transaction_date'    => 1451327400,
            'posted_date'         => 1451384893,
            'bank_serial_number'  => 5,
            'description'         => 'INF/NEFT/023629961691/SBIN0050103/TestRBL/Boruto',
            'category'            => 'customer_initiated',
            'bank_instrument_id'  => '',
            'balance_currency'    => 'INR',
            'created_at'          => 1451327400
        ]);

        Queue::except(BankingAccountStatementReconNeo::class);

        Queue::assertNotPushed(BankingAccountStatementReconProcessNeo::class);

        Queue::assertNotPushed(BankingAccountStatementUpdate::class);

        $this->fixtures->merchant->addFeatures([Features::DA_LEDGER_JOURNAL_WRITES]);

        $ledgerSnsPayloadArray = [];

        $this->mockLedgerSns(0, $ledgerSnsPayloadArray);

        (new AdminService)->setConfigKeys([ConfigKey::PREFIX . 'rx_missing_statements_insertion_limit' => 1]);

        $redisKey = 'missing_statements_10000000000000_2224440041626905';
        $this->app['redis']->del($redisKey);

        $initialBasEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);

        $initialCount = count($initialBasEntries);

        $initialBasDetails = $this->getDbEntity('banking_account_statement_details', ['account_number' => '2224440041626905', 'channel' => 'rbl']);

        $this->fixtures->edit(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS,
                              $initialBasDetails[Entity::ID], [
                                  BasDetails\Entity::PAGINATION_KEY => 'next_key',
                                  'gateway_balance_change_at'       => Carbon::now(Timezone::IST)->subMinutes(20)->getTimestamp(),
                                  'last_reconciled_at'              => Carbon::now(Timezone::IST)->subDays(3)->getTimestamp(),
                              ]);

        $initialStatementClosingBalance = $initialBasDetails[BasDetails\Entity::STATEMENT_CLOSING_BALANCE];

        $mockedMissingStatementResponse = [
            'data' => [
                'PayGenRes' => [
                    'Body' => [
                        'hasMoreData' => 'N',
                        'transactionDetails' => [
                            [
                                'pstdDate' => '2015-12-29T15:58:13.000',
                                'transactionSummary' => [
                                    'instrumentId' => '',
                                    'txnAmt' => [
                                        'amountValue' => '1.00',
                                        'currencyCode' => 'INR'
                                    ],
                                    'txnDate' => '2015-12-29T00:00:00.000',
                                    'txnDesc' => 'INF/NEFT/023629961691/SBIN0050103/TestRBL/Boruto',
                                    'txnType' => 'C'
                                ],
                                'txnBalance' => [
                                    'currencyCode' => 'INR',
                                    'amountValue' => '10001.00'
                                ],
                                'txnCat' => 'TCI',
                                'txnId' => '  S71034964',
                                'txnSrlNo' => '  5',
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

        $this->app['rzp.mode'] = EnvMode::TEST;

        $mozartMock = Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $mozartMock->shouldReceive('sendRawRequest')
                   ->andReturnUsing(function(array $request) use ($mockedMissingStatementResponse){

                       $requestData = json_decode($request['content'], true);

                       if (array_key_exists('from_date', $requestData['entities']['attempt']) === true)
                       {
                           return json_encode($this->convertRblV1ResponseToV2Response($mockedMissingStatementResponse));
                       }

                       $mockRblResponse = $this->convertRblV1ResponseToV2Response($this->getRblNoDataResponse());

                       $mockRblResponse['data']['FetchAccStmtRes']['Header']['Status_Desc'] = "No Records Found";

                       return json_encode($mockRblResponse);
                   })->times(1);

        $this->app->instance('mozart', $mozartMock);

        $this->ba->cronAuth();

        $this->startTest($this->testData['testE2ERblAutomatedReconFlow']);

        $merchantMissingStatementList = json_decode($this->app['redis']->get($redisKey), true);

        $this->app['redis']->del($redisKey);

        $this->assertEmpty($merchantMissingStatementList);

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);

        $this->assertCount($initialCount, $basEntries);

        $basDetails = $this->getDbLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS);

        $expectedLastReconciledAt = Carbon::now(Timezone::IST)->subDay()->startOfDay()->getTimestamp();

        $this->assertEquals($expectedLastReconciledAt ,$basDetails['last_reconciled_at']);

        $finalStatementClosingBalance = $basDetails[BasDetails\Entity::STATEMENT_CLOSING_BALANCE];

        $this->assertEquals($initialStatementClosingBalance, $finalStatementClosingBalance);

        Carbon::setTestNow();
    }

    public function testRblAutomatedReconForMissingStatementsForGivenRange()
    {
        $missingStatements = [
            [
                BasEntity::ACCOUNT_NUMBER      => '2224440041626905',
                BasEntity::BANK_TRANSACTION_ID => 'S807089',
                BasEntity::TYPE                => 'debit',
                BasEntity::AMOUNT              => 5000,
                BasEntity::BALANCE             => 5000,
                BasEntity::POSTED_DATE         => 1656861683,
                BasEntity::TRANSACTION_DATE    => 1656786600,
                BasEntity::DESCRIPTION         => 'DEBIT IMPS 20000324344829',
                BasEntity::CHANNEL             => 'rbl',
                BasEntity::BANK_SERIAL_NUMBER  => '2',
                basEntity::CURRENCY            => 'INR',
                basEntity::BALANCE_CURRENCY    => 'INR',
            ],
        ];

        $redisKey = 'missing_statements_10000000000000_2224440041626905';

        $this->app['redis']->set($redisKey, json_encode($missingStatements));

        $basdBeforeTest = $this->getDbEntity('banking_account_statement_details', ['account_number' => '2224440041626905', 'channel' => 'rbl']);

        $this->fixtures->edit(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS,
                              $basdBeforeTest[Entity::ID],
                              [BasDetails\Entity::PAGINATION_KEY => 'next_key']);

        $this->ba->cronAuth();

        Queue::fake();

        $this->startTest();

        $expectedParams = [
            BasEntity::CHANNEL              => 'rbl',
            BasEntity::ACCOUNT_NUMBER       => '2224440041626905',
            BasEntity::FROM_DATE            => 1683225000,
            BasEntity::TO_DATE              => 1683268199,
            BASConstants::EXPECTED_ATTEMPTS => 1,
            BASConstants::PAGINATION_KEY    => null,
            BasEntity::SAVE_IN_REDIS        => '1',
        ];

        Queue::assertPushed(BankingAccountStatementReconNeo::class, function($job) use ($expectedParams)
        {
            $this->assertArraySelectiveEquals($expectedParams, $job->getParams());

            return true;
        });

        $this->app['redis']->del($redisKey);
    }

    public function testRblAutomatedReconForNoMissingStatements()
    {
        $oldDateTime = Carbon::create(2023, 5, 2, 14, 25, 10, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $basdBeforeTest = $this->getDbEntity('banking_account_statement_details', ['account_number' => '2224440041626905', 'channel' => 'rbl']);

        $this->fixtures->edit(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS,
                              $basdBeforeTest[Entity::ID],
                              [BasDetails\Entity::PAGINATION_KEY => 'next_key']);

        $this->app['rzp.mode'] = EnvMode::TEST;

        $mozartMock = Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $mozartMock->shouldReceive('sendRawRequest')
                   ->andReturnUsing(function(array $request) {
                       $mockRblResponse = $this->convertRblV1ResponseToV2Response($this->getRblNoDataResponse());

                       $mockRblResponse['data']['FetchAccStmtRes']['Header']['Status_Desc'] = "No Records Found";

                       return json_encode($mockRblResponse);
                   })->times(1);

        $this->app->instance('mozart', $mozartMock);

        $this->ba->cronAuth();

        Queue::fake();

        Queue::except(BankingAccountStatementReconNeo::class);

        $this->startTest($this->testData['testRblAutomatedReconForMissingStatementsForGivenRange']);

        $basdBeforeTest->reload();

        $toDate = $this->testData['testRblAutomatedReconForMissingStatementsForGivenRange']['request']['content']['to_date'];

        $expectedLastReconciledAt = Carbon::createFromTimestamp($toDate, Timezone::IST)->startOfDay()->getTimestamp();

        $this->assertEquals($expectedLastReconciledAt ,$basdBeforeTest->getLastReconciledAt());

        $redisKey = 'missing_statements_10000000000000_2224440041626905';

        $merchantMissingStatementList = json_decode($this->app['redis']->get($redisKey), true);

        Queue::assertPushed(BankingAccountStatementReconProcessNeo::class, 0);

        $this->assertEmpty($merchantMissingStatementList);

        $this->app['redis']->del($redisKey);

        Carbon::setTestNow();
    }

    public function testRblAutomatedReconWithMissingStatements()
    {
        $oldDateTime = Carbon::create(2023, 5, 2, 14, 25, 10, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $missingStatements = [
            [
                BasEntity::ACCOUNT_NUMBER      => '2224440041626905',
                BasEntity::BANK_TRANSACTION_ID => 'S807089',
                BasEntity::TYPE                => 'debit',
                BasEntity::AMOUNT              => 5000,
                BasEntity::BALANCE             => 5000,
                BasEntity::POSTED_DATE         => 1656861683,
                BasEntity::TRANSACTION_DATE    => 1656786600,
                BasEntity::DESCRIPTION         => 'DEBIT IMPS 20000324344829',
                BasEntity::CHANNEL             => 'rbl',
                BasEntity::BANK_SERIAL_NUMBER  => '2',
                basEntity::CURRENCY            => 'INR',
                basEntity::BALANCE_CURRENCY    => 'INR',
            ],
        ];

        $redisKey = 'missing_statements_10000000000000_2224440041626905';

        $this->app['redis']->set($redisKey, json_encode($missingStatements));

        $basdBeforeTest = $this->getDbEntity('banking_account_statement_details', ['account_number' => '2224440041626905', 'channel' => 'rbl']);

        $this->fixtures->edit(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS,
                              $basdBeforeTest[Entity::ID],
                              [BasDetails\Entity::PAGINATION_KEY => 'next_key']);

        $this->app['rzp.mode'] = EnvMode::TEST;

        $mozartMock = Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $mozartMock->shouldReceive('sendRawRequest')
                   ->andReturnUsing(function(array $request) {
                       $mockRblResponse = $this->convertRblV1ResponseToV2Response($this->getRblNoDataResponse());

                       $mockRblResponse['data']['FetchAccStmtRes']['Header']['Status_Desc'] = "No Records Found";

                       return json_encode($mockRblResponse);
                   })->times(1);

        $this->app->instance('mozart', $mozartMock);

        $this->ba->cronAuth();

        Queue::fake();

        Queue::except(BankingAccountStatementReconNeo::class);

        $this->startTest($this->testData['testRblAutomatedReconForMissingStatementsForGivenRange']);

        $basdBeforeTest->reload();

        $this->assertNull($basdBeforeTest->getLastReconciledAt());

        $redisKey = 'missing_statements_10000000000000_2224440041626905';

        $merchantMissingStatementList = json_decode($this->app['redis']->get($redisKey), true);

        Queue::assertPushed(BankingAccountStatementReconProcessNeo::class, 1);

        $this->assertCount(1, $merchantMissingStatementList);

        $this->app['redis']->del($redisKey);

        Carbon::setTestNow();
    }

    public function testRblAutomatedReconWithMismatchedChannel()
    {
        $basdBeforeTest = $this->getDbEntity('banking_account_statement_details', ['account_number' => '2224440041626905', 'channel' => 'rbl']);

        $this->fixtures->edit(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS,
                              $basdBeforeTest[Entity::ID],
                              [BasDetails\Entity::PAGINATION_KEY => 'next_key']);

        $this->ba->cronAuth();

        Queue::fake();

        $response = $this->startTest();

        $this->assertEmpty($response);

        $redisKey = 'missing_statements_10000000000000_2224440041626905';

        $merchantMissingStatementList = json_decode($this->app['redis']->get($redisKey), true);

        Queue::assertNotPushed(BankingAccountStatementReconNeo::class);

        $this->assertEmpty($merchantMissingStatementList);

        $this->app['redis']->del($redisKey);
    }

    public function testRblAutomatedReconWithAccountsWithLastReconciledAt()
    {
        $oldDateTime = Carbon::create(2023, 5, 2, 14, 25, 10, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $basdBeforeTest = $this->getDbEntity('banking_account_statement_details', [
            'account_number' => '2224440041626905',
            'channel'        => 'rbl'
        ]);

        $this->fixtures->edit(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS,
                              $basdBeforeTest[Entity::ID],
                              [BasDetails\Entity::PAGINATION_KEY => 'next_key']);

        $this->fixtures->create('banking_account_statement_details', [
            'account_number'            => '2224440041626906',
            'channel'                   => 'rbl',
            'pagination_key'            => 'next_key1',
            'gateway_balance_change_at' => Carbon::now(Timezone::IST)->subMinutes(20)->getTimestamp(),
            'last_reconciled_at'        => Carbon::now(Timezone::IST)->subDays(2)->startOfDay()->getTimestamp(),
        ]);

        $this->fixtures->create('banking_account_statement_details', [
            'account_number'            => '2224440041626907',
            'channel'                   => 'rbl',
            'pagination_key'            => 'next_key3',
            'gateway_balance_change_at' => Carbon::now(Timezone::IST)->subDays(2)->getTimestamp(),
        ]);

        $this->fixtures->create('banking_account_statement_details', [
            'account_number'            => '2224440041626908',
            'channel'                   => 'rbl',
            'pagination_key'            => null,
            'gateway_balance_change_at' => Carbon::now(Timezone::IST)->subMinutes(40)->getTimestamp(),
        ]);

        $this->ba->cronAuth();

        Queue::fake();

        $this->startTest();

        $expectedParams = [
            BasEntity::CHANNEL              => 'rbl',
            BasEntity::ACCOUNT_NUMBER       => '2224440041626906',
            BasEntity::FROM_DATE            => Carbon::now(Timezone::IST)->subDays(1)->startOfDay()->getTimestamp(),
            BasEntity::TO_DATE              => Carbon::now(Timezone::IST)->subDay()->endOfDay()->getTimestamp(),
            BASConstants::EXPECTED_ATTEMPTS => 1,
            BASConstants::PAGINATION_KEY    => null,
            BasEntity::SAVE_IN_REDIS        => '1',
        ];

        Queue::assertPushed(BankingAccountStatementReconNeo::class, function($job) use ($expectedParams)
        {
            $this->assertArraySelectiveEquals($expectedParams, $job->getParams());

            return true;
        });

        Carbon::setTestNow();
    }

    public function testRblAutomatedReconWithPriorityAccountNumbers()
    {
        $oldDateTime = Carbon::create(2023, 5, 2, 14, 25, 10, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        (new Admin\Service)->setConfigKeys(
            [
                Admin\ConfigKey::PREFIX . 'ca_recon_priority_account_numbers' => ['2224440041626906']
            ]);

        $basdBeforeTest = $this->getDbEntity('banking_account_statement_details', [
            'account_number' => '2224440041626905',
            'channel'        => 'rbl'
        ]);

        $this->fixtures->edit(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS,
                              $basdBeforeTest[Entity::ID],
                              [BasDetails\Entity::PAGINATION_KEY => 'next_key']);

        $this->fixtures->create('banking_account_statement_details', [
            'account_number'            => '2224440041626906',
            'channel'                   => 'rbl',
            'pagination_key'            => 'next_key1',
            'gateway_balance_change_at' => Carbon::now(Timezone::IST)->subMinutes(20)->getTimestamp(),
            'last_reconciled_at'        => Carbon::now(Timezone::IST)->subDays(2)->startOfDay()->getTimestamp(),
        ]);

        $this->fixtures->create('banking_account_statement_details', [
            'account_number'            => '2224440041626907',
            'channel'                   => 'rbl',
            'pagination_key'            => 'next_key3',
            'gateway_balance_change_at' => Carbon::now(Timezone::IST)->subDays(2)->getTimestamp(),
        ]);

        $this->fixtures->create('banking_account_statement_details', [
            'account_number'            => '2224440041626908',
            'channel'                   => 'rbl',
            'pagination_key'            => null,
            'gateway_balance_change_at' => Carbon::now(Timezone::IST)->subMinutes(40)->getTimestamp(),
        ]);

        $this->fixtures->create('banking_account_statement_details', [
            'account_number'            => '2224440041626909',
            'channel'                   => 'rbl',
            'pagination_key'            => 'next_key5',
            'gateway_balance_change_at' => Carbon::now(Timezone::IST)->subMinutes(50)->getTimestamp(),
            'last_reconciled_at'        => Carbon::now(Timezone::IST)->subDays(3)->startOfDay()->getTimestamp(),
        ]);

        $this->ba->cronAuth();

        Queue::fake();

        $this->startTest();

        Queue::assertPushed(BankingAccountStatementReconNeo::class, 2);

        Carbon::setTestNow();
    }

    public function testRblMissingAccountStatementWithCronAuth()
    {
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RBL_MISSING_STATEMENT_FETCH_MAX_RECORDS => 25000]);

        $this->fixtures->create('banking_account_statement',
                                [
                                    'type'                      => 'credit',
                                    'amount'                    => '10000',
                                    'channel'                   => 'rbl',
                                    'account_number'            => 2224440041626905,
                                    'bank_transaction_id'       => 'S429654',
                                    'balance'                   => 10000,
                                    'transaction_date'          => 1656786600,
                                    'posted_date'               => 1656861681,
                                    'bank_serial_number'        => 1,
                                    'description'               => 'Credit to account',
                                    'category'                  => 'customer_initiated',
                                    'bank_instrument_id'        => '',
                                    'balance_currency'          => 'INR',
                                ]);

        $this->fixtures->create('banking_account_statement',
                                [
                                    'type'                      => 'credit',
                                    'amount'                    => '11450',
                                    'channel'                   => 'rbl',
                                    'account_number'            => 2224440041626905,
                                    'bank_transaction_id'       => 'S429655',
                                    'balance'                   => 21450,
                                    'transaction_date'          => 1656786600,
                                    'posted_date'               => 1656861781,
                                    'bank_serial_number'        => 2,
                                    'description'               => 'CREDIT NEFT',
                                    'category'                  => 'bank_initiated',
                                    'bank_instrument_id'        => '',
                                    'balance_currency'          => 'INR',
                                ]);

        $mockedResponse = $this->getRblBulkResponseForFetchingMissingRecords();

        $this->app['rzp.mode'] = EnvMode::TEST;

        $mozartMock = Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $mozartMock->shouldReceive('sendRawRequest')
                   ->andReturnUsing(function(array $request) use ($mockedResponse){

                       $requestData = json_decode($request['content'], true);

                       if (array_key_exists('from_date',$requestData['entities']['attempt']) === true)
                       {
                           return json_encode($this->convertRblV1ResponseToV2Response($mockedResponse));
                       }

                       $mockRblResponse = $this->convertRblV1ResponseToV2Response($this->getRblNoDataResponse());

                       $mockRblResponse['data']['FetchAccStmtRes']['Header']['Status_Desc'] = "No Records Found";

                       return json_encode($mockRblResponse);
                   });

        $this->app->instance('mozart', $mozartMock);

        $this->ba->cronAuth();

        $testData = &$this->testData['testRblMissingAccountStatement'];

        $testData['request']['url'] = '/banking_account_statement/cron/fetch_missing/rbl';

        $this->startTest($testData);

        $redisKey = 'missing_statements_10000000000000_2224440041626905';

        $merchantMissingStatementList = json_decode($this->app['redis']->get($redisKey), true);

        $this->app['redis']->del($redisKey);

        $basExpected = [
            BasEntity::ACCOUNT_NUMBER        => '2224440041626905',
            BasEntity::BANK_TRANSACTION_ID   => 'S807089',
            BasEntity::TYPE                  => 'debit',
            BasEntity::AMOUNT                => 5000,
            BasEntity::BALANCE               => 5000,
            BasEntity::POSTED_DATE           => 1656861683,
            BasEntity::TRANSACTION_DATE      => 1656786600,
            BasEntity::DESCRIPTION           => 'DEBIT IMPS 20000324344829',
            BasEntity::CHANNEL               => 'rbl',
        ];

        $this->assertArraySubset($basExpected, array_first($merchantMissingStatementList));
    }

    public function testfetchRblMissingAccountStatementWhileInProgress()
    {
        $mockMutex = new MockMutexService($this->app);

        $this->app->instance('api.mutex', $mockMutex);

        $mutex = $this->app['api.mutex'];

        $this->ba->adminAuth();

        $mutex->acquireAndRelease(
            'banking_account_statement_recon_2224440041626905_rbl',
            function() {
                $this->testRblMissingAccountStatement(true);
            },
            300);

        $redisKey = 'missing_statements_10000000000000_2224440041626905';

        $merchantMissingStatementList = json_decode($this->app['redis']->get($redisKey), true);

        $this->app['redis']->del($redisKey);

        $this->assertEmpty($merchantMissingStatementList);
    }

    public function testfetchRblMissingAccountStatementWithInvalidDateRange()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testRblAccountStatementFetchPoolSetUp()
    {
        (new Admin\Service)->setConfigKeys([
                                               Admin\ConfigKey::ACCOUNT_STATEMENT_V2_FLOW => ['2224440041626905']]);

        (new Admin\Service)->setConfigKeys([
                                               Admin\ConfigKey::RBL_ACCOUNT_STATEMENT_RECORDS_TO_FETCH_AT_ONCE => 2]);

        (new Admin\Service)->setConfigKeys([
                                               Admin\ConfigKey::ACCOUNT_STATEMENT_RECORDS_TO_SAVE_AT_ONCE => 2]);

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RBL_STATEMENT_FETCH_V2_API_MAX_RECORDS => 1]);

        $this->fixtures->create('banking_account_statement_pool_rbl',
                                [
                                    'type'                      => 'credit',
                                    'amount'                    => '10000',
                                    //'channel'                   => 'rbl',
                                    'account_number'            => 2224440041626905,
                                    'bank_transaction_id'       => 'SDHDH',
                                    'balance'                   => 20000,
                                    'transaction_date'          => 1584987183,
                                    'posted_date'               => 1584987183,
                                ]);

        $this->fixtures->create('banking_account_statement_pool_rbl',
                                [
                                    'type'                      => 'credit',
                                    'amount'                    => '11450',
                                    //'channel'                   => 'rbl',
                                    'account_number'            => 2224440041626905,
                                    'bank_transaction_id'       => 'S429655',
                                    'balance'                   => 31450,
                                    'transaction_date'          => 1451327400,
                                    'posted_date'               => 1451384892,
                                    'bank_serial_number'        => 1,
                                    'description'               => 'DEBIT CARD ANNUAL FEE 2635  ',
                                    'category'                  => 'bank_initiated',
                                    'bank_instrument_id'        => '',
                                    'balance_currency'          => 'INR',
                                ]);

        $baBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT, true);
        $this->assertNull($baBeforeTest[BaEntity::LAST_STATEMENT_ATTEMPT_AT]);

        $basdBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->fixtures->edit(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS,
                              $basdBeforeTest[Entity::ID],
                              [BasDetails\Entity::ACCOUNT_TYPE => BasDetails\AccountType::SHARED]);

        $this->assertNull($basdBeforeTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        $mockedResponse = $this->getRblBulkResponse();

        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][0]['txnBalance']['amountValue'] = '122';

        $mock = Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $responseNumber = 0;
        $mock->shouldReceive('sendRawRequest')
             ->andReturnUsing(function(array $request) use ($mockedResponse, & $responseNumber){

                 $responseNumber++;

                 $requestData = json_decode($request['content'], true);

                 if ((array_key_exists('from_date',$requestData['entities']['attempt']) === true) and
                     ($responseNumber === 1))
                 {
                     return json_encode($this->convertRblV1ResponseToV2Response($mockedResponse));
                 }

                 $mockRblResponse = $this->convertRblV1ResponseToV2Response($this->getRblNoDataResponse());

                 $mockRblResponse['data']['FetchAccStmtRes']['Header']['Status_Desc'] = "No Records Found";

                 return json_encode($mockRblResponse);
             });

        $this->app->instance('mozart', $mock);

        $this->setRazorxMockForBankingAccountStatementV2Api();

        BankingAccountStatementJob::dispatch('test', [
            'channel'           => Channel::RBL,
            'account_number'    => 2224440041626905
        ]);

        $transactions = $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'];

        $txn = last($transactions);

        $basActual = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_POOL_RBL, true);

        $basdAfterTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNotNull($basdAfterTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        $this->assertEquals(BasDetails\AccountType::SHARED, $basdAfterTest[BasDetails\Entity::ACCOUNT_TYPE]);

        $basExpected = [
            BasEntity::MERCHANT_ID           => $basdBeforeTest[BasDetails\Entity::MERCHANT_ID],
            BasEntity::BANK_TRANSACTION_ID   => trim($txn['txnId']),
            BasEntity::TYPE                  => 'debit',
            BasEntity::AMOUNT                => 10000,
            BasEntity::BALANCE               => 21355,
            BasEntity::POSTED_DATE           => 1451937993,
            BasEntity::TRANSACTION_DATE      => 1451932200,
            BasEntity::DESCRIPTION           => trim($txn['transactionSummary']['txnDesc']),
            //BasEntity::CHANNEL               => 'rbl',
        ];

        $this->assertArraySubset($basExpected, $basActual, true);


        // checking few extra things like the balance and ledger both have correct values of balance and bas has
        // correct balance and txn_id and entity_id
        $basEntities = $this->getDbEntities('banking_account_statement_pool_rbl', [], Config::get('database.default'));


        $transactionEntities = $this->getDbEntities('transaction');
        $this->assertEquals(0, count($transactionEntities));

        $externalEntities = $this->getDbEntities('external');
        $this->assertEquals(0, count($externalEntities));

        $balance = $this->getDbLastEntity('balance');
        $this->assertEquals($this->bankingBalance->getBalance(), $balance['balance']);

        $this->assertEquals(11450, $basEntities[1]['amount']);
    }

    public function testRblAccountStatementFetchPoolSetUpCron()
    {
        (new Admin\Service)->setConfigKeys([
                                               Admin\ConfigKey::ACCOUNT_STATEMENT_V2_FLOW => ['2224440041626905']]);

        (new Admin\Service)->setConfigKeys([
                                               Admin\ConfigKey::RBL_ACCOUNT_STATEMENT_RECORDS_TO_FETCH_AT_ONCE => 2]);

        (new Admin\Service)->setConfigKeys([
                                               Admin\ConfigKey::ACCOUNT_STATEMENT_RECORDS_TO_SAVE_AT_ONCE => 2]);

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RBL_STATEMENT_FETCH_V2_API_MAX_RECORDS => 1]);

        $this->fixtures->create('banking_account_statement_pool_rbl',
                                [
                                    'type'                      => 'credit',
                                    'amount'                    => '10000',
                                    //'channel'                   => 'rbl',
                                    'account_number'            => 2224440041626905,
                                    'bank_transaction_id'       => 'SDHDH',
                                    'balance'                   => 20000,
                                    'transaction_date'          => 1584987183,
                                    'posted_date'               => 1584987183,
                                ]);

        $this->fixtures->create('banking_account_statement_pool_rbl',
                                [
                                    'type'                      => 'credit',
                                    'amount'                    => '11450',
                                    //'channel'                   => 'rbl',
                                    'account_number'            => 2224440041626905,
                                    'bank_transaction_id'       => 'S429655',
                                    'balance'                   => 31450,
                                    'transaction_date'          => 1451327400,
                                    'posted_date'               => 1451384892,
                                    'bank_serial_number'        => 1,
                                    'description'               => 'DEBIT CARD ANNUAL FEE 2635  ',
                                    'category'                  => 'bank_initiated',
                                    'bank_instrument_id'        => '',
                                    'balance_currency'          => 'INR',
                                ]);

        $baBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT, true);
        $this->assertNull($baBeforeTest[BaEntity::LAST_STATEMENT_ATTEMPT_AT]);

        $basdBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->fixtures->edit(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS,
                              $basdBeforeTest[Entity::ID],
                              [BasDetails\Entity::ACCOUNT_TYPE => BasDetails\AccountType::SHARED]);

        $this->assertNull($basdBeforeTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        $mockedResponse = $this->getRblBulkResponse();

        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][0]['txnBalance']['amountValue'] = '122';

        $mock = Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $responseNumber = 0;

        $mock->shouldReceive('sendRawRequest')
             ->andReturnUsing(function(array $request) use ($mockedResponse, & $responseNumber){

                 $responseNumber++;

                 $requestData = json_decode($request['content'], true);

                 if ((array_key_exists('from_date',$requestData['entities']['attempt']) === true) and
                     ($responseNumber === 1))
                 {
                     return json_encode($this->convertRblV1ResponseToV2Response($mockedResponse));
                 }

                 $mockRblResponse = $this->convertRblV1ResponseToV2Response($this->getRblNoDataResponse());

                 $mockRblResponse['data']['FetchAccStmtRes']['Header']['Status_Desc'] = "No Records Found";

                 return json_encode($mockRblResponse);
             });

        $this->app->instance('mozart', $mock);

        $this->setRazorxMockForBankingAccountStatementV2Api();

        $testData = $this->testData['testRblAccountStatementCase1'];

        $testData['request']['url'] = "/banking_account_statement/pool/process";

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->cronAuth();

        $this->startTest();

        $transactions = $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'];

        $txn = last($transactions);

        $basActual = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_POOL_RBL, true);

        $basdAfterTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNotNull($basdAfterTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        $this->assertEquals(BasDetails\AccountType::SHARED, $basdAfterTest[BasDetails\Entity::ACCOUNT_TYPE]);

        $basExpected = [
            BasEntity::MERCHANT_ID           => $basdBeforeTest[BasDetails\Entity::MERCHANT_ID],
            BasEntity::BANK_TRANSACTION_ID   => trim($txn['txnId']),
            BasEntity::TYPE                  => 'debit',
            BasEntity::AMOUNT                => 10000,
            BasEntity::BALANCE               => 21355,
            BasEntity::POSTED_DATE           => 1451937993,
            BasEntity::TRANSACTION_DATE      => 1451932200,
            BasEntity::DESCRIPTION           => trim($txn['transactionSummary']['txnDesc']),
            //BasEntity::CHANNEL               => 'rbl',
        ];

        $this->assertArraySubset($basExpected, $basActual, true);


        // checking few extra things like the balance and ledger both have correct values of balance and bas has
        // correct balance and txn_id and entity_id
        $basEntities = $this->getDbEntities('banking_account_statement_pool_rbl', [], Config::get('database.default'));

        $transactionEntities = $this->getDbEntities('transaction');
        $this->assertEquals(0, count($transactionEntities));

        $externalEntities = $this->getDbEntities('external');
        $this->assertEquals(0, count($externalEntities));

        $balance = $this->getDbLastEntity('balance');
        $this->assertEquals($this->bankingBalance->getBalance(), $balance['balance']);

        $this->assertEquals(11450, $basEntities[1]['amount']);
    }

    // this will check if we don't get a record from RBL
    // we are not saving anything in BAS table
    public function testRblAccountStatementFetchV2WithNoNewRecords()
    {
        (new Admin\Service)->setConfigKeys([
            Admin\ConfigKey::ACCOUNT_STATEMENT_V2_FLOW => ['2224440041626905']]);

        $this->fixtures->create('banking_account_statement',
            [
                'type'                      => 'credit',
                'amount'                    => '10000',
                'channel'                   => 'rbl',
                'account_number'            => 2224440041626905,
                'bank_transaction_id'       => 'SDHDH',
                'balance'                   => 20000,
                'transaction_date'          => 1584987183,
                'posted_date'               => 1584987183,
            ]);

        $mockedResponse = $this->getRblNoDataResponse();

        $this->setMozartMockResponse($mockedResponse);

        $baBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT, true);
        $this->assertNull($baBeforeTest[BaEntity::LAST_STATEMENT_ATTEMPT_AT]);

        $basdBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNull($basdBeforeTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        BankingAccountStatementJob::dispatch('test', [
            'channel'           => Channel::RBL,
            'account_number'    => 2224440041626905
        ]);

        $basEntities = $this->getDbEntities('banking_account_statement');

        $this->assertEquals(1, count($basEntities));

        $txnEntities = $this->getDbEntities('transaction');

        $this->assertEquals(1, count($txnEntities));

        $this->assertEquals($txnEntities[0]['id'], $basEntities[0]['transaction_id']);
    }

    /**
     * Here we are checking that merchant with ACCOUNT_STATEMENT_V2_FLOW feature enabled
     * goes through new flow and that payout and reversal have transaction id
     */
    public function testRblAccountStatementFetchV2Linking()
    {
        (new Admin\Service)->setConfigKeys([
            Admin\ConfigKey::ACCOUNT_STATEMENT_V2_FLOW => ['2224440041626905']]);

        $this->fixtures->create('banking_account_statement',
            [
                'type'                      => 'credit',
                'amount'                    => '10000',
                'channel'                   => 'rbl',
                'account_number'            => 2224440041626905,
                'bank_transaction_id'       => 'SDHDH',
                'balance'                   => 20000,
                'transaction_date'          => 1584987183,
                'posted_date'               => 1584987183,
            ]);

        $mockedResponse = $this->getRblBulkResponse();

        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][0]['txnBalance']['amountValue'] = '314.50';
        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][0]['transactionSummary']['txnAmt']['amountValue'] = '114.50';
        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][1]['transactionSummary']['txnAmt']['amountValue'] = '100.95';
        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][1]['txnBalance']['amountValue'] = '213.55';
        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][2]['transactionSummary']['txnAmt']['amountValue'] = '100.95';
        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][2]['txnBalance']['amountValue'] = '314.50';
        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][2]['transactionSummary']['txnDesc'] = 'R-123456-Z';
        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][3]['transactionSummary']['txnAmt']['amountValue'] = '100.95';
        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][3]['txnBalance']['amountValue'] = '213.55';
        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][3]['transactionSummary']['txnDesc'] = 'l-3456-Z';

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

        $this->assertEquals(Payout\Status::FAILED, $payout['status']);
        $this->assertEquals(FundTransfer\Mode::IMPS, $payout['mode']);

        $payoutFailureReason = $payout->getFailureReason();

        $this->setMozartMockResponse($mockedResponse);

        $baBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT, true);
        $this->assertNull($baBeforeTest[BaEntity::LAST_STATEMENT_ATTEMPT_AT]);

        $basdBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNull($basdBeforeTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        BankingAccountStatementJob::dispatch('test', [
            'channel'           => Channel::RBL,
            'account_number'    => 2224440041626905
        ]);

        $transactions = $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'];

        $txn = last($transactions);

        $basActual = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT, true);

        $externalActual = $this->getLastEntity(EntityConstants::EXTERNAL, true);

        $externalId = str_after($externalActual[ExternalEntity::ID], 'ext_');

        $externalTxnId = $externalActual[ExternalEntity::TRANSACTION_ID];

        $this->txnEntity = $this->getDbEntityById(EntityConstants::TRANSACTION, $externalTxnId);

        $txnActual = $this->txnEntity->toArray();

        $basdAfterTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNotNull($basdAfterTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        $this->assertEquals($txnActual[TransactionEntity::POSTED_AT], $basActual[BasEntity::POSTED_DATE]);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals($payoutFailureReason, $payout->getFailureReason());

        $this->assertNotNull($payout['transaction_id']);

        $reversal = $this->getDbLastEntity('reversal');

        $this->assertNotNull($reversal['transaction_id']);

        $externalEntities = $this->getDbEntities('external');

        $this->assertEquals(3, count($externalEntities));
    }

    // Fixed window rate limiter is implemented in job which allows only RBL_STATEMENT_FETCH_RATE_LIMIT number of
    // requests in RBL_STATEMENT_FETCH_WINDOW_LENGTH seconds. Hence first dispatching into the queue should not be rate
    // limited and fetch statement while next dispatched job should be rate limited.
    public function testStatementFetchRateLimiter()
    {
        (new AdminService)->setConfigKeys(
            [
                ConfigKey::RBL_STATEMENT_FETCH_RATE_LIMIT    => 1,
                ConfigKey::RBL_STATEMENT_FETCH_WINDOW_LENGTH => 3600,
                ConfigKey::RBL_ENABLE_RATE_LIMIT_FLOW => 1,
                ConfigKey::ACCOUNT_STATEMENT_V2_FLOW => ["2224440041626905"],
            ]);

        $mockedResponse = $this->getRblDataResponse();

        $txns = $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'];

        $txns[0]['txnBalance']['amountValue'] = '114.50';

        $txns[1]['txnBalance']['amountValue'] = '13.55';

        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'] = $txns;

        $this->setMozartMockResponse($mockedResponse);

        $baBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT, true);

        $this->assertNull($baBeforeTest[BaEntity::LAST_STATEMENT_ATTEMPT_AT]);

        $basdBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNull($basdBeforeTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        $this->ba->cronAuth();

        RblBankingAccountStatementJob::dispatch('test', [
            'channel'        => Channel::RBL,
            'account_number' => 2224440041626905,
            'attempt_number' => 0
        ]);

        $txns = $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'];

        $txns[1]['txnId'] = 'S3';

        $txns[1]['txnBalance']['amountValue'] = '-87.4';

        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'] = $txns;

        $this->setMozartMockResponse($mockedResponse);

        // this time the job should be rate limited, else balance validation error will be thrown because of the mocked response.
        RblBankingAccountStatementJob::dispatch('test', [
            'channel'        => Channel::RBL,
            'account_number' => 2224440041626905,
            'attempt_number' => 0
        ]);

        $app = App::getFacadeRoot();

        $redis = $app['redis']->connection();

        $currentTime = Carbon::now()->getTimestamp();

        // this is for clean up purpose.
        $redis->del('banking_account_statement_rbl' . stringify((int) ($currentTime / 3600)));

        $basRecords = $this->getDbEntities(EntityConstants::BANKING_ACCOUNT_STATEMENT);

        $this->assertEquals(2, count($basRecords));
    }

    //in rbl payouts the payouts go from failed->processed->reversed state so
    // on dashboard the merchant finds it confusing so removing the failed_At when reversed_At is set
    public function testFailedAtNotPresentWhenReversedAtIsSetForProxyAuth()
    {
        $channel = Channel::RBL;

        $this->setupForRblPayout($channel);

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'reversed']);
        $this->fixtures->edit('payout', $payout['id'], ['processed_at' => 1584987183]);
        $this->fixtures->edit('payout', $payout['id'], ['reversed_at' => 1584987183]);
        $this->fixtures->edit('payout', $payout['id'], ['failed_at' => 1584987183]);

        $this->ba->proxyAuth();

        $request = [
            'method' => 'GET',
            'url' => '/payouts/' . 'pout_' . $payout->getId(),
        ];
        $response = $this->sendRequest($request);

        $response = json_decode($response->getContent(), true);

        $this->assertNotContains('failed_at', $response);
    }

    /** This test checks that the when RBL worker's method it pushes the job in new
     * queue called banking_account_statement_processor.
     */
    public function testRblAccountStatementFetchAndProcessOnGatewayQueueV2()
    {
        (new AdminService)->setConfigKeys([ConfigKey::BANKING_ACCOUNT_STATEMENT_RATE_LIMIT => 1]);

        (new AdminService)->setConfigKeys([ConfigKey::ACCOUNT_STATEMENT_V2_FLOW => ["2224440041626905"]]);

        $this->fixtures->create('banking_account_statement',
            [
                'type'                      => 'credit',
                'amount'                    => '10000',
                'channel'                   => 'rbl',
                'account_number'            => 2224440041626905,
                'bank_transaction_id'       => 'SDHDH',
                'balance'                   => 20000,
                'transaction_date'          => 1584987183,
                'posted_date'               => 1584987183,
            ]);

        $mockedResponse = $this->getRblDataResponse();

        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][0]['txnBalance']['amountValue'] = '314.50';
        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][0]['txnAmt']['amountValue'] = '114.50';
        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][1]['txnAmt']['amountValue'] = '100.95';
        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][1]['txnBalance']['amountValue'] = '213.55';

        $this->setMozartMockResponse($mockedResponse);

        $baBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT, true);
        $this->assertNull($baBeforeTest[BaEntity::LAST_STATEMENT_ATTEMPT_AT]);

        $basdBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNull($basdBeforeTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        RblBankingAccountStatementJob::dispatch('test', [
            'channel'           => Channel::RBL,
            'account_number'    => 2224440041626905
        ]);

        $basEntities = $this->getDbEntities('banking_account_statement');
        $this->assertEquals(3, count($basEntities));
        $transactions = $this->getDbEntities('transaction');
        $this->assertEquals(3, count($transactions));
    }

    /**
     * Here we are checking that merchant with ACCOUNT_STATEMENT_V2_FLOW feature enabled
     * goes through new flow and that payout and reversal have transaction id
     */
    public function testRblAccountStatementFetchAndProcessOnGatewayQueueV2Linking()
    {
        (new AdminService)->setConfigKeys([ConfigKey::ACCOUNT_STATEMENT_V2_FLOW => ["2224440041626905"]]);

        $this->fixtures->create('banking_account_statement',
            [
                'type'                      => 'credit',
                'amount'                    => '10000',
                'channel'                   => 'rbl',
                'account_number'            => 2224440041626905,
                'bank_transaction_id'       => 'SDHDH',
                'balance'                   => 20000,
                'transaction_date'          => 1584987183,
                'posted_date'               => 1584987183,
            ]);

        $mockedResponse = $this->getRblBulkResponse();

        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][0]['txnBalance']['amountValue'] = '314.50';
        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][0]['transactionSummary']['txnAmt']['amountValue'] = '114.50';
        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][1]['transactionSummary']['txnAmt']['amountValue'] = '100.95';
        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][1]['txnBalance']['amountValue'] = '213.55';
        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][2]['transactionSummary']['txnAmt']['amountValue'] = '100.95';
        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][2]['txnBalance']['amountValue'] = '314.50';
        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][2]['transactionSummary']['txnDesc'] = 'R-123456-Z';
        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][3]['transactionSummary']['txnAmt']['amountValue'] = '100.95';
        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][3]['txnBalance']['amountValue'] = '213.55';
        $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][3]['transactionSummary']['txnDesc'] = 'l-3456-Z';

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

        $this->assertEquals(Payout\Status::FAILED, $payout['status']);
        $this->assertEquals(FundTransfer\Mode::IMPS, $payout['mode']);

        $this->setMozartMockResponse($mockedResponse);

        $baBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT, true);
        $this->assertNull($baBeforeTest[BaEntity::LAST_STATEMENT_ATTEMPT_AT]);

        $basdBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNull($basdBeforeTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        RblBankingAccountStatementJob::dispatch('test', [
            'channel'           => Channel::RBL,
            'account_number'    => 2224440041626905
        ]);

        $transactions = $mockedResponse['data']['PayGenRes']['Body']['transactionDetails'];

        $txn = last($transactions);

        $basActual = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT, true);

        $externalActual = $this->getLastEntity(EntityConstants::EXTERNAL, true);

        $externalId = str_after($externalActual[ExternalEntity::ID], 'ext_');

        $externalTxnId = $externalActual[ExternalEntity::TRANSACTION_ID];

        $this->txnEntity = $this->getDbEntityById(EntityConstants::TRANSACTION, $externalTxnId);

        $txnActual = $this->txnEntity->toArray();

        $basdAfterTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNotNull($basdAfterTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        $this->assertEquals($txnActual[TransactionEntity::POSTED_AT], $basActual[BasEntity::POSTED_DATE]);

        $payout = $this->getDbLastEntity('payout');

        $this->assertNotNull($payout['transaction_id']);

        $reversal = $this->getDbLastEntity('reversal');

        $this->assertNotNull($reversal['transaction_id']);

        $externalEntities = $this->getDbEntities('external');

        $this->assertEquals(3, count($externalEntities));
    }

    public function testTransactionStatementFetchMultiple()
    {
        $mockedResponse = $this->getRblDataResponse();

        $this->setMozartMockResponse($mockedResponse);

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->cronAuth();
        $this->startTest();

        $this->ba->privateAuth();

        $request = [
            'url'     => '/transactions',
            'method'  => 'get',
            'content' => [
                'account_number' => '2224440041626905',
            ],
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(2, $response['count']);

        $keys = [
            ExternalEntity::ID,
            ExternalEntity::UTR,
            ExternalEntity::AMOUNT,
            ExternalEntity::ENTITY
        ];

        $this->assertEquals('external', $response['items'][0]['source']['entity']);
        $this->assertArrayKeysExist($response['items'][0]['source'], $keys);
        $this->assertEquals('external', $response['items'][1]['source']['entity']);
        $this->assertArrayKeysExist($response['items'][1]['source'], $keys);
    }

    public function testPayoutReversedWhenDebitAndCreditFoundForCurrentAccount()
    {
        $channel = Channel::RBL;

        $this->setupForRblPayout($channel);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '123456', 'fts_transfer_id' => '69']);

        // 1. Fetch account statement from RBL - Both, debit and credit with same utr
        $mockedResponse = $this->getRblDataResponseForFailureMapping();

        $this->setMozartMockResponse($mockedResponse);

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->cronAuth();
        $this->startTest();

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $transactions = $this->getDbEntities('transaction');
        $externalEntries = $this->getDbEntities('external', ['balance_id' => $payout['balance_id']]);

        // assert if external and transaction entities are created from DEBIT BAS
        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[1]['entity_type']);
        $this->assertEquals(Type::DEBIT, $basEntries[1]->getType());
        $this->assertEquals($externalEntries[1]['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($externalEntries[1]['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals($externalEntries[1]['banking_account_statement_id'], $basEntries[1]['id']);
        $this->assertEquals($transactions[1]['entity_id'],$externalEntries[1]['id']);

        // assert if external and transaction entities are created from CREDIT BAS
        $this->assertEquals(EntityConstants::EXTERNAL, $basEntries[2]['entity_type']);
        $this->assertEquals(Type::CREDIT, $basEntries[2]->getType());
        $this->assertEquals($externalEntries[2]['id'], $basEntries[2]['entity_id']);
        $this->assertEquals($externalEntries[2]['transaction_id'], $basEntries[2]['transaction_id']);
        $this->assertEquals($externalEntries[2]['banking_account_statement_id'], $basEntries[2]['id']);
        $this->assertEquals($transactions[2]['entity_id'],$externalEntries[2]['id']);

        // 2. Initiate FTS webhook - status received as failed
        $attempt = $this->getDbLastEntity('fund_transfer_attempt');
        $this->updateFta(
            $attempt['fts_transfer_id'],
            $attempt['source'],
            Attempt\Type::PAYOUT,
            Attempt\Status::FAILED
        );

        $payout = $this->getDbLastEntity('payout');
        $debitBAS = $this->getDbEntity('banking_account_statement', ['id' => $basEntries[1]->getId()]);
        $creditBAS = $this->getDbEntity('banking_account_statement', ['id' => $basEntries[2]->getId()]);

        // assert payout status and debit/credit BAS entity type
        $this->assertEquals(Status::REVERSED, $payout->getStatus());
        $this->assertEquals(Constants\Entity::PAYOUT, $debitBAS->getEntityType());
        $this->assertEquals(Constants\Entity::REVERSAL, $creditBAS->getEntityType());
    }

    public function testPayoutReversedWhenDebitAndCreditFoundForCurrentAccountButFTSUpdateCameBeforeCredit()
    {
        $channel = Channel::RBL;

        $this->setupForRblPayout($channel);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XA6', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated']);

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '123456', 'fts_transfer_id' => '69']);

        // 1. Fetch account statement from RBL - debit fetch only
        $mockedResponse = $this->getRblDataResponseForFailureMapping();
        // unset credit response
        unset($mockedResponse['data']['PayGenRes']['Body']['transactionDetails'][2]);

        $this->setMozartMockResponse($mockedResponse);

        $payloadReversed = null;

        $this->mockServiceStorkRequest(
            function($path, $payload) use (& $payloadReversed) {
                $this->assertContains($payload['event']['name'], ['payout.reversed']);
                switch ($payload['event']['name'])
                {
                    case Event::PAYOUT_REVERSED:
                        $payloadReversed = $payload;
                        break;
                }

                return new \Requests_Response();
            })->times(11);

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->cronAuth();
        $this->startTest();

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        // 2. Initiate FTS status update webhook, status received as failed
        $originalTestData = $this->testData['testPayoutReversedWhenDebitExistsAndCreditIsProcessedAfterFTSUpdateForCurrentAccount'];
        $originalTestData['request']['content']['fund_transfer_id'] = $attempt['fts_transfer_id'];
        $originalTestData['request']['content']['source_id']        = $attempt['source'];

        $this->testData[__FUNCTION__] = $originalTestData;
        $this->ba->ftsAuth();
        $this->startTest();

        $payout = $this->getDbLastEntity('payout');
        $debitBAS = $this->getDbEntity('banking_account_statement', ['id' => $basEntries[1]->getId()]);

        // assert that payout is initiated and external is created for debit after fts status update failure
        $this->assertEquals(Status::INITIATED, $payout->getStatus());
        $this->assertEquals(Constants\Entity::EXTERNAL, $debitBAS->getEntityType());

        // 3. Fetch account statement from RBL - credit fetch
        $mockedResponse = $this->getRblDataResponseForFailureMapping();
        // unset everything other than the mocked credit response
        array_splice($mockedResponse['data']['PayGenRes']['Body']['transactionDetails'], 0, 2);

        $this->setMozartMockResponse($mockedResponse);

        $testData = $this->testData['testRblAccountStatementTxnMappingCase1'];

        $this->testData[__FUNCTION__] = $testData;
        $this->ba->cronAuth();
        $this->startTest();

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $payout = $this->getDbLastEntity('payout');
        $reversal = $this->getDbLastEntity('reversal');
        $transactions = $this->getDbEntities('transaction');

        // assert if payout status is reversed
        $this->assertEquals(Status::REVERSED, $payout->getStatus());

        // assert if debit BAS is linked with payout
        $this->assertEquals(EntityConstants::PAYOUT, $basEntries[1]['entity_type']);
        $this->assertEquals(Type::DEBIT, $basEntries[1]->getType());
        $this->assertEquals($payout->getId(), $basEntries[1]['entity_id']);
        $this->assertEquals($payout->getTransactionId(), $basEntries[1]['transaction_id']);
        $this->assertEquals($transactions[1]['entity_id'],$payout->getId());

        // assert if credit BAS is linked with reversal
        $this->assertEquals(EntityConstants::REVERSAL, $basEntries[2]['entity_type']);
        $this->assertEquals(Type::CREDIT, $basEntries[2]->getType());
        $this->assertEquals($reversal->getId(), $basEntries[2]['entity_id']);
        $this->assertEquals($reversal->getTransactionId(), $basEntries[2]['transaction_id']);
        $this->assertEquals($transactions[2]['entity_id'],$reversal->getId());

        $payoutReversedEventData = [
            'entity'   => 'event',
            'event'    => 'payout.reversed',
            'contains' => [
                'payout',
            ],
            'payload'  => [
                'payout' => [
                    'entity' => [
                        'entity' => 'payout',
                        'status' => 'reversed',
                        'failure_reason' => 'REVERSAL',
                        'status_details'  => [
                            'source' => 'beneficiary_bank',
                            'reason' =>  'beneficiary_bank_failure',
                            'description' => 'Payout failed at beneficiary bank due to technical issue. Please retry.'
                        ]
                    ],
                ],
            ],
        ];

        $this->validateStorkWebhookFireEvent('payout.reversed', $payoutReversedEventData, $payloadReversed);
    }

    public function testRBLAccountStatementWithVariousRegex()
    {
        $mockedResponse = $this->getRblDataResponseForUTRExtractionFromDescription();

        $this->setMozartMockResponse($mockedResponse);

        $testData = $this->testData['testRblAccountStatementCase1'];
        $this->testData[__FUNCTION__] = $testData;

        $this->ba->cronAuth();

        $this->startTest();

        $utrsExpected = [
            '209821868111',
            '000311505156',
        ];

        $utrsActual = $this->getDbEntities(EntityConstants::BANKING_ACCOUNT_STATEMENT)
            ->map(function($basEntity) {
                return $basEntity->getUtr();
            })->all();

        $this->assertEqualsCanonicalizing($utrsExpected, $utrsActual);
    }

    public function testPayoutProcessedMailTriggeredViaStmtProcessingJobRbl()
    {
        (new AdminService)->setConfigKeys([ConfigKey::ACCOUNT_STATEMENT_V2_FLOW => ["2224440041626905"]]);

        Mail::fake();

        $this->setupForRblPayout();

        $payout = $this->getDbLastEntity('payout');

        $request = [
            'method'  => 'POST',
            'url'     =>  '/update_fts_fund_transfer',
            'content' => [
                'bank_processed_time' => '2019-12-04 15:51:21',
                'bank_status_code'    => 'SUCCESS',
                'extra_info'          => [
                    'beneficiary_name' => 'SUSANTA BHUYAN',
                    'cms_ref_no'       => 'd10ce8e4167f11eab1750a0047330000',
                    'internal_error'   => false
                ],
                'failure_reason'      => '',
                'fund_transfer_id'    => 1234567,
                'mode'                => 'IMPS',
                'narration'           => 'Kissht FastCash Disbursal',
                'remarks'             => 'Check the status by calling getStatus API.',
                'source_id'           => $payout['id'],
                'source_type'         => 'payout',
                'status'              => 'processed',
                'utr'                 => '933815383814',
                'source_account_id'   => 111111111,
                'bank_account_type'   => 'current'
            ],
        ];

        $this->ba->ftsAuth();

        $this->makeRequestAndGetContent($request);

        $payout->reload();

        $this->assertEquals('933815383814', $payout->getUtr());

        $this->assertEquals(Payout\Status::PROCESSED, $payout->getStatus());

        Mail::assertNotQueued(PayoutMail::class);

        $this->fixtures->create('banking_account_statement',
                                [
                                    'type'                      => 'debit',
                                    'utr'                       => '933815383814',
                                    'amount'                    => '10095',
                                    'channel'                   => 'rbl',
                                    'account_number'            => 2224440041626905,
                                    'bank_transaction_id'       => 'SDHDH',
                                    'balance'                   => -95,
                                    'transaction_date'          => 1584987183,
                                    'posted_date'               => Carbon::now()->getTimestamp(),
                                ]);

        $this->fixtures->create('banking_account_statement_details', [
            BasDetails\Entity::ID                                  => 'xba00000000006',
            BasDetails\Entity::MERCHANT_ID                         => '10000000000000',
            BasDetails\Entity::BALANCE_ID                          => $this->bankingBalance->getId(),
            BasDetails\Entity::ACCOUNT_NUMBER                      => '2323230041626905',
            BasDetails\Entity::CHANNEL                             => BasDetails\Channel::RBL,
            BasDetails\Entity::STATUS                              => BasDetails\Status::ACTIVE,
            BasDetails\Entity::GATEWAY_BALANCE                     => -95,
            BasDetails\Entity::STATEMENT_CLOSING_BALANCE           => -95,
            BasDetails\Entity::GATEWAY_BALANCE_CHANGE_AT           => 123456,
            BasDetails\Entity::STATEMENT_CLOSING_BALANCE_CHANGE_AT => 123399,
            BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT           => 123460,
            BasDetails\Entity::ACCOUNT_TYPE                        => BasDetails\AccountType::DIRECT,
        ]);

        BankingAccountStatementProcessor::dispatch('test', [
            'channel'           => \RZP\Models\BankingAccount\Channel::RBL,
            'account_number'    => 2224440041626905
        ]);

        Mail::assertQueued(PayoutMail::class, function($mail) {
            $viewData = $mail->viewData;

            $this->assertEquals('banking', $mail->originProduct);

            $this->assertEquals('10095', $viewData['txn']['amount']); // raw amount
            $this->assertEquals('100.95', amount_format_IN($viewData['txn']['amount'])); // formatted amount

            $payout = $this->getDbLastEntity('payout');

            $this->assertEquals('pout_' . $payout->getId(), $viewData['source']['id']);
            $this->assertEquals($payout->getFailureReason(), $viewData['source']['failure_reason']);

            $expectedData = [
                'txn' => [
                    'entity_id' => $payout->getId(),
                ]
            ];

            $this->assertArraySelectiveEquals($expectedData, $viewData);

            $this->assertArrayHasKey('created_at_formatted', $viewData['txn']);

            $this->assertEquals('emails.transaction.payout_processed', $mail->view);

            return true;
        });
    }

    // payout is created in initiated state with utr also present , account statement is created , processing of acc statement is done
    // mail is not triggered , then fts update comes with processed state , then mail is triggered .
    public function testPayoutProcessedMailTriggeredViaStmtProcessingJobRblCase2()
    {
        (new AdminService)->setConfigKeys([ConfigKey::ACCOUNT_STATEMENT_V2_FLOW => ["2224440041626905"]]);

        Mail::fake();

        $this->setupForRblPayout();

        $payout = $this->getDbLastEntity('payout');

        $request = [
            'method'  => 'POST',
            'url'     =>  '/update_fts_fund_transfer',
            'content' => [
                'bank_processed_time' => '2019-12-04 15:51:21',
                'bank_status_code'    => 'SUCCESS',
                'extra_info'          => [
                    'beneficiary_name' => 'SUSANTA BHUYAN',
                    'cms_ref_no'       => 'd10ce8e4167f11eab1750a0047330000',
                    'internal_error'   => false
                ],
                'failure_reason'      => '',
                'fund_transfer_id'    => 1234567,
                'mode'                => 'IMPS',
                'narration'           => 'Kissht FastCash Disbursal',
                'remarks'             => 'Check the status by calling getStatus API.',
                'source_id'           => $payout['id'],
                'source_type'         => 'payout',
                'status'              => 'initiated',
                'utr'                 => '933815383814',
                'source_account_id'   => 111111111,
                'bank_account_type'   => 'current'
            ],
        ];

        $this->ba->ftsAuth();

        $this->makeRequestAndGetContent($request);

        $payout->reload();

        $this->assertEquals('933815383814', $payout->getUtr());

        Mail::assertNotQueued(PayoutMail::class);

        $this->fixtures->create('banking_account_statement',
                                [
                                    'type'                      => 'debit',
                                    'utr'                       => '933815383814',
                                    'amount'                    => '10095',
                                    'channel'                   => 'rbl',
                                    'account_number'            => 2224440041626905,
                                    'bank_transaction_id'       => 'SDHDH',
                                    'balance'                   => -95,
                                    'transaction_date'          => 1584987183,
                                    'posted_date'               => Carbon::now()->getTimestamp(),
                                ]);

        $this->fixtures->create('banking_account_statement_details', [
            BasDetails\Entity::ID                                  => 'xba00000000006',
            BasDetails\Entity::MERCHANT_ID                         => '10000000000000',
            BasDetails\Entity::BALANCE_ID                          => $this->bankingBalance->getId(),
            BasDetails\Entity::ACCOUNT_NUMBER                      => '2323230041626905',
            BasDetails\Entity::CHANNEL                             => BasDetails\Channel::RBL,
            BasDetails\Entity::STATUS                              => BasDetails\Status::ACTIVE,
            BasDetails\Entity::GATEWAY_BALANCE                     => -95,
            BasDetails\Entity::STATEMENT_CLOSING_BALANCE           => -95,
            BasDetails\Entity::GATEWAY_BALANCE_CHANGE_AT           => 123456,
            BasDetails\Entity::STATEMENT_CLOSING_BALANCE_CHANGE_AT => 123399,
            BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT           => 123460,
            BasDetails\Entity::ACCOUNT_TYPE                        => BasDetails\AccountType::DIRECT,
        ]);

        BankingAccountStatementProcessor::dispatch('test', [
            'channel'           => \RZP\Models\BankingAccount\Channel::RBL,
            'account_number'    => 2224440041626905
        ]);

        Mail::assertNotQueued(PayoutMail::class);

        $request = ['method'  => 'POST',
                    'url'     => '/update_fts_fund_transfer',
                    'content' => [
                        'bank_processed_time' => '2019-12-04 15:51:21',
                        'bank_status_code'    => 'SUCCESS',
                        'failure_reason'      => '',
                        'fund_transfer_id'    => 1234567,
                        'mode'                => 'IMPS',
                        'narration'           => 'Kissht FastCash Disbursal',
                        'remarks'             => 'Check the status by calling getStatus API.',
                        'source_id'           => $payout['id'],
                        'source_type'         => 'payout',
                        'status'              => 'processed',
                        'source_account_id'   => 111111111,
                        'bank_account_type'   => 'current',
                        'channel'             => 'rbl',
                        'utr'                 => '933815383814',
                    ]
        ];

        $this->ba->ftsAuth();

        $this->makeRequestAndGetContent($request);

        $payout->reload();

        Mail::assertQueued(PayoutMail::class);

        $this->assertEquals(Payout\Status::PROCESSED, $payout->getStatus());
    }

    public function testPayoutCreationWhenBASDetailsUnderMaintenance()
    {
        $basDetails = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->fixtures->edit(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS,
            $basDetails[BasDetails\Entity::ID],
            [
            'status'                    => BasDetails\Status::UNDER_MAINTENANCE,
            'account_number'            => '2224440041626905',
            'channel'                   => BasDetails\Channel::RBL,
            'balance_last_fetched_at'   => Carbon::now(Timezone::IST)->subHours(2)->getTimestamp(),
            'gateway_balance_change_at' => Carbon::now(Timezone::IST)->subHours(2)->getTimestamp()
            ]);

        $this->ba->privateAuth();

        $this->createContact();

        $this->createFundAccount();

        $this->mockMozartResponseForFetchingBalanceFromRblGateway(500);

        $content = [
            'account_number'  => '2224440041626905',
            'amount'          => 100,
            'currency'        => 'INR',
            'purpose'         => 'payout',
            'narration'       => 'Rbl account payout',
            'fund_account_id' => 'fa_' . $this->fundAccount->getId(),
            'mode'            => 'IMPS',
            'queue_if_low_balance' => 1,
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

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals('created', $payout['status']);
    }

    public function testRblMissingAccountStatementInsertAsync()
    {
        $oldDateTime = Carbon::create(2016, 1, 6, 12, 0, 0, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->testRblAccountStatementCase1();

        Queue::except(BankingAccountStatementUpdate::class);

        Queue::except(BankingAccountMissingStatementInsert::class);

        $this->fixtures->merchant->addFeatures([Features::DA_LEDGER_JOURNAL_WRITES]);

        $ledgerSnsPayloadArray = [];

        $this->mockLedgerSns(2, $ledgerSnsPayloadArray);

        $missingStatementsBeforeInsertion = [
            [
                'type' => 'credit',
                'amount' => '100',
                'currency' => 'INR',
                'channel' => 'rbl',
                'account_number' => '2224440041626905',
                'bank_transaction_id' => 'S71034964',
                'balance' => 1000100,
                'transaction_date' => 1451327400,
                'posted_date' => 1451384893,
                'bank_serial_number' => 'S71034964',
                'description' => 'INF/NEFT/023629961691/SBIN0050103/TestRBL/Boruto',
                'balance_currency' => 'INR',
            ],
            [
                'type' => 'debit',
                'amount' => '100',
                'currency' => 'INR',
                'channel' => 'rbl',
                'account_number' => '2224440041626905',
                'bank_transaction_id' => 'S71034965',
                'balance' => 1000000,
                'transaction_date' => 1451327400,
                'posted_date' => 1451384892,
                'bank_serial_number' => 'S71034965',
                'description' => 'INF/NEFT/023629961692/SBIN0050103/TestRBL/Boruto',
                'balance_currency' => 'INR',
            ]];

        $redisKey = 'missing_statements_10000000000000_2224440041626905';

        $this->app['redis']->set($redisKey, json_encode($missingStatementsBeforeInsertion));

        (new AdminService)->setConfigKeys(
            [
                ConfigKey::PREFIX . 'rx_missing_statements_insertion_limit' => 2,

                ConfigKey::PREFIX . 'retry_count_for_id_generation' => 100,
            ]);

        $initialBasEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);

        $initialCount = count($initialBasEntries);

        $initialBasDetails = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $initialStatementClosingBalance = $initialBasDetails[BasDetails\Entity::STATEMENT_CLOSING_BALANCE];

        $initialStatement1 = $this->getDbEntities('banking_account_statement', [
            'account_number' => '2224440041626905',
            'bank_transaction_id' => 'S429655'
        ])[0];

        $initialStatement2 = $this->getDbEntities('banking_account_statement', [
            'account_number' => '2224440041626905',
            'bank_transaction_id' => 'S807068'
        ])[0];

        $this->ba->cronAuth();

        $this->startTest();

        $merchantMissingStatementList = json_decode($this->app['redis']->get($redisKey), true);

        $this->app['redis']->del($redisKey);

        $this->assertEmpty($merchantMissingStatementList);

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);

        $this->assertCount($initialCount + 2, $basEntries);

        $basDetails = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $finalStatementClosingBalance = $basDetails[BasDetails\Entity::STATEMENT_CLOSING_BALANCE];

        $this->assertEquals($initialStatementClosingBalance, $finalStatementClosingBalance);

        $finalStatement1 = $this->getDbEntities('banking_account_statement', [
            'account_number' => '2224440041626905',
            'bank_transaction_id' => 'S429655'
        ])[0];

        $finalStatement2 = $this->getDbEntities('banking_account_statement', [
            'account_number' => '2224440041626905',
            'bank_transaction_id' => 'S807068'
        ])[0];

        $insertedStatement1 = $this->getDbEntities('banking_account_statement', [
            'account_number' => '2224440041626905',
            'bank_transaction_id' => 'S71034964'
        ])[0];

        $insertedStatement2 = $this->getDbEntities('banking_account_statement', [
            'account_number' => '2224440041626905',
            'bank_transaction_id' => 'S71034965'
        ])[0];

        $externalEntity1 = $this->getDbEntities(
            'external', ['banking_account_statement_id' => $insertedStatement1[BasEntity::ID]])[0];

        $externalEntity2 = $this->getDbEntities(
            'external', ['banking_account_statement_id' => $insertedStatement2[BasEntity::ID]])[0];

        $this->assertEquals($initialStatement1->getBalance(), $finalStatement1->getBalance());

        $this->assertEquals($initialStatement2->getBalance(), $finalStatement2->getBalance());

        $this->assertEquals($insertedStatement2[BasEntity::BALANCE], $finalStatement1[BasEntity::BALANCE]);

        $this->assertGreaterThan($initialStatement1[BasEntity::ID], $insertedStatement1[BasEntity::ID]);

        $transactorTypeArray = [
            'da_ext_credit',
            'da_ext_debit',
        ];

        $transactorIdArray = [
            $externalEntity1->getPublicId(),
            $externalEntity2->getPublicId(),
        ];

        $commissionArray = [
            "",""
        ];

        $taxArray = [
           "",""
        ];

        $apiTransactionIdArray = [
            $externalEntity1->getTransactionId(),
            $externalEntity2->getTransactionId(),
        ];

        for ($index = 0; $index < count($ledgerSnsPayloadArray); $index++) {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('test', $ledgerRequestPayload['mode']);
            $this->assertEquals($transactorIdArray[$index], $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals($commissionArray[$index], $ledgerRequestPayload['commission']);
            $this->assertEquals($taxArray[$index], $ledgerRequestPayload['tax']);
            $this->assertEquals($transactorTypeArray[$index], $ledgerRequestPayload['transactor_event']);
            $this->assertArrayNotHasKey('fee_accounting', $ledgerRequestPayload['additional_params']);
            if (!empty($apiTransactionIdArray[$index])) {
                $this->assertEquals($apiTransactionIdArray[$index], $ledgerRequestPayload['api_transaction_id']);
            } else {
                $this->assertArrayNotHasKey('api_transaction_id', $ledgerRequestPayload['additional_params']);
            }
        }

        Carbon::setTestNow();
    }

    public function testRblMissingAccountStatementDetection()
    {
        $this->setMockRazorxTreatment([RazorxTreatment::BAS_FETCH_RE_ARCH => 'on', RazorxTreatment::RBL_V2_BAS_API_INTEGRATION => 'on']);

        $basDetails = $this->getDbEntity('banking_account_statement_details', ['account_number' => 2224440041626905]);

        $this->fixtures->edit('banking_account_statement_details', $basDetails->getId(), [
           'created_at' => 1652812200
        ]);

        $this->fixtures->create('banking_account_statement',
            [
                'type'                      => 'credit',
                'amount'                    => '10000',
                'channel'                   => 'rbl',
                'account_number'            => 2224440041626905,
                'bank_transaction_id'       => 'S429654',
                'balance'                   => 10000,
                'transaction_date'          => 1656786600,
                'posted_date'               => 1656861681,
                'bank_serial_number'        => 1,
                'description'               => 'Credit to account',
                'category'                  => 'customer_initiated',
                'bank_instrument_id'        => '',
                'balance_currency'          => 'INR',
            ]);

        $latestBAS = $this->fixtures->create('banking_account_statement',
            [
                'type'                      => 'credit',
                'amount'                    => '11450',
                'channel'                   => 'rbl',
                'account_number'            => 2224440041626905,
                'bank_transaction_id'       => 'S429655',
                'balance'                   => 21450,
                'transaction_date'          => 1656786600,
                'posted_date'               => 1656861781,
                'bank_serial_number'        => 2,
                'description'               => 'CREDIT NEFT',
                'category'                  => 'bank_initiated',
                'bank_instrument_id'        => '',
                'balance_currency'          => 'INR',
            ]);

        $mockedResponse = $this->getRblBulkResponseForFetchingMissingRecords();

        $this->app['rzp.mode'] = EnvMode::TEST;

        $mozartMock = Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $mozartMock->shouldReceive('sendRawRequest')
            ->andReturnUsing(function(array $request) use ($mockedResponse){

                $requestData = json_decode($request['content'], true);

                if (array_key_exists('from_date',$requestData['entities']['attempt']) === true)
                {
                    return json_encode($this->convertRblV1ResponseToV2Response($mockedResponse));
                }

                $mockRblResponse = $this->convertRblV1ResponseToV2Response($this->getRblNoDataResponse());

                $mockRblResponse['data']['FetchAccStmtRes']['Header']['Status_Desc'] = "No Records Found";

                return json_encode($mockRblResponse);
            });

        $this->app->instance('mozart', $mozartMock);

        $this->ba->adminAuth();

        // Add assertion for checking if fetch call was made in the end

        $this->startTest();

        $missingStatementDetectionConfig = (new Admin\Service)->getConfigKey(
            [
                'key' => Admin\ConfigKey::RX_CA_MISSING_STATEMENT_DETECTION_RBL
            ]);

        $this->assertCount(1, $missingStatementDetectionConfig);

        $this->assertArraySelectiveEquals(['completed' => true], $missingStatementDetectionConfig['2224440041626905']);

        // assert that there is only one missing statement config saved
        $this->assertCount(1, $missingStatementDetectionConfig['2224440041626905']['mismatch_data']);

        // check if config has missing statement detected of 5000 debit between the range 2nd July to 1st August
        $this->assertArraySelectiveEquals(
            [
                'from_date'       => 1656613800,
                'to_date'         => 1659292199,
                'mismatch_amount' => -5000,
                'mismatch_type'   => "missing_debit",
                'analysed_bas_id' => $latestBAS->getId()
            ], $missingStatementDetectionConfig['2224440041626905']['mismatch_data'][0]);
    }

    public function testRblMissingAccountStatementDetectionWhenThereIsNoMismatch()
    {
        $this->setMockRazorxTreatment([RazorxTreatment::BAS_FETCH_RE_ARCH => 'on', RazorxTreatment::RBL_V2_BAS_API_INTEGRATION => 'on']);

        $basDetails = $this->getDbEntity('banking_account_statement_details', ['account_number' => 2224440041626905]);

        $this->fixtures->edit('banking_account_statement_details', $basDetails->getId(), [
            'created_at' => 1652812200
        ]);

        $this->fixtures->create('banking_account_statement',
            [
                'type'                      => 'credit',
                'amount'                    => '11450',
                'channel'                   => 'rbl',
                'account_number'            => 2224440041626905,
                'bank_transaction_id'       => 'S429655',
                'balance'                   => 16450,
                'transaction_date'          => 1656786600,
                'posted_date'               => 1656861781,
                'bank_serial_number'        => 2,
                'description'               => 'CREDIT NEFT',
                'category'                  => 'bank_initiated',
                'bank_instrument_id'        => '',
                'balance_currency'          => 'INR',
                'utr'                       => '209821811450',
            ]);

        $mockedResponse = $this->getRblBulkResponseForFetchingMissingRecords();

        $this->app['rzp.mode'] = EnvMode::TEST;

        $mozartMock = Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $mozartMock->shouldReceive('sendRawRequest')
            ->andReturnUsing(function(array $request) use ($mockedResponse){

                $requestData = json_decode($request['content'], true);

                if (array_key_exists('from_date',$requestData['entities']['attempt']) === true)
                {
                    return json_encode($this->convertRblV1ResponseToV2Response($mockedResponse));
                }

                $mockRblResponse = $this->convertRblV1ResponseToV2Response($this->getRblNoDataResponse());

                $mockRblResponse['data']['FetchAccStmtRes']['Header']['Status_Desc'] = "No Records Found";

                return json_encode($mockRblResponse);
            });

        $this->app->instance('mozart', $mozartMock);

        $this->ba->adminAuth();

        $this->testData[__FUNCTION__] = $this->testData['testRblMissingAccountStatementDetection'];

        $this->startTest();

        $missingStatementDetectionConfig = (new Admin\Service)->getConfigKey(
            [
                'key' => Admin\ConfigKey::RX_CA_MISSING_STATEMENT_DETECTION_RBL
            ]);

        // Assert that no config was stored in redis
        $this->assertTrue(empty($missingStatementDetectionConfig['mismatch_data']));
    }

    public function testRblMissingAccountStatementPushesCurrentTimestampWhenStartTimeIsNotPassed()
    {
        $currentTime = Carbon::create(2023, 2, 3, 12, 00, 00, Timezone::IST);

        Carbon::setTestNow($currentTime);

        $this->app['rzp.mode'] = EnvMode::TEST;

        $this->ba->adminAuth();

        Queue::fake();

        $this->startTest();

        Queue::assertPushed(MissingAccountStatementDetection::class, function($job) use ($currentTime)
        {
            $this->assertEquals($currentTime->timestamp, $job->toDate);

            $this->assertEquals($currentTime->startOfMonth()->startOfDay()->timestamp, $job->fromDate);

            return true;
        });
    }

    public function testStatementFetchTriggerForDetectedMissingStatements()
    {
        $this->app['rzp.mode'] = EnvMode::TEST;

        Queue::fake();

        (new BasCore())->triggerMissingStatementFetchForIdentifiedTimeRange('10000000000000', '2224440041626905', 'rbl', [
            'completed'     => true,
            'mismatch_data' => [
                [
                    "from_date"       => 1680633000,
                    "to_date"         => 1681065000,
                    "mismatch_amount" => 20447,
                    "mismatch_type"   => "missing_credit",
                    "analysed_bas_id" => "1"
                ],
                [
                    "from_date"       => 1677695400,
                    "to_date"         => 1680373799,
                    "mismatch_amount" => -7113,
                    "mismatch_type"   => "missing_debit",
                    "analysed_bas_id" => "3"
                ],
                [
                    "from_date"       => 1676831400,
                    "to_date"         => 1677695400,
                    "mismatch_amount" => -7113,
                    "mismatch_type"   => "missing_debit",
                    "analysed_bas_id" => "4"
                ],
                [
                    "from_date"       => 1680373800,
                    "to_date"         => 1680546600,
                    "mismatch_amount" => 20447,
                    "mismatch_type"   => "missing_credit",
                    "analysed_bas_id" => "2"
                ]
            ]]);

        $missingStatementDetectionConfig = (new Admin\Service)->getConfigKey(
            [
                'key' => Admin\ConfigKey::RX_CA_MISSING_STATEMENT_DETECTION_RBL
            ]);

        // Assert that config only has 2 elements, since the other 2 will be removed for not having any mismatch
        $this->assertCount(2, $missingStatementDetectionConfig['2224440041626905']['mismatch_data']);

        // Assert that config is finally saved in ascending order before pushing for fetch
        $firstConfig = $missingStatementDetectionConfig['2224440041626905']['mismatch_data'][0];
        $secondConfig = $missingStatementDetectionConfig['2224440041626905']['mismatch_data'][1];

        $this->assertEquals(4, $firstConfig['analysed_bas_id']);
        $this->assertEquals(2, $secondConfig['analysed_bas_id']);
        $this->assertTrue($firstConfig['from_date'] < $secondConfig['from_date']);

        // Assert that cleanup job was dispatched
        Queue::assertPushed(BankingAccountStatementCleanUp::class, function($job) use ($secondConfig, $missingStatementDetectionConfig) {
            $jobInput = $job->getJobInput();

            $this->assertEquals('10000000000000', $jobInput['params']['merchant_id']);
            $this->assertEquals('2224440041626905', $jobInput['params']['account_number']);
            $this->assertEquals(false, $jobInput['params']['fetch_in_progress']);
            $this->assertEquals($secondConfig['mismatch_amount'], $jobInput['params']['total_mismatch_amount']);
            $this->assertEquals(null, $jobInput['fetch_input']);
            $this->assertEquals($missingStatementDetectionConfig['2224440041626905'], $jobInput['clean_up_config']);

            return true;
        });
    }

    public function testMissingStatementDetectionConfigIsResetBeforeDispatching()
    {
        $this->app['rzp.mode'] = EnvMode::TEST;

        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::RX_CA_MISSING_STATEMENT_DETECTION_RBL => [
            "2224440041626905" => [
                "mismatch_data" => [
                    [
                        "start_date"      => 1682965800,
                        "end_date"        => 1683204973,
                        "mismatch_amount" => -1483775,
                        "mismatch_type"   => "missing_debit",
                        "analysed_bas_id" => "LlGg3PkufJ39fg"
                    ],
                    "completed" => true
                ]
            ]
        ]]);

        $this->ba->adminAuth();

        Queue::fake();

        $this->testData[__FUNCTION__] = $this->testData['testRblMissingAccountStatementPushesCurrentTimestampWhenStartTimeIsNotPassed'];

        $this->startTest();

        $missingStatementDetectionConfig = (new Admin\Service)->getConfigKey(
            [
                'key' => Admin\ConfigKey::RX_CA_MISSING_STATEMENT_DETECTION_RBL
            ]);

        $this->assertEquals([], $missingStatementDetectionConfig['2224440041626905']);
    }

    public function testBasSourceLinkingAsyncRetry()
    {
        $this->app['rzp.mode'] = EnvMode::TEST;

        Queue::fake();

        $bas = $this->fixtures->create('banking_account_statement', [
            'merchant_id'         => '10000000000000',
            'entity_id'           => 'testExternal00',
            'entity_type'         => 'external',
            'utr'                 => '211708954836',
            'amount'              => 1000,
            'balance'             => $this->bankingBalance->getBalance() + 1000,
            'channel'             => 'rbl',
            'account_number'      => $this->bankingBalance->getAccountNumber(),
            'bank_transaction_id' => 'M2134215',
            'type'                => 'debit',
            'posted_date'         => 1650628967,
            'description'         => '211708954836-LOAN492836',
            'category'            => 'customer_initiated',
            'bank_serial_number'  => 7,
            'bank_instrument_id'  => "",
            'transaction_date'    => 1650565810,
            'transaction_id'      => 'JOPkusQyH3wn3u',
        ]);

        $this->fixtures->create('external', [
            'id'                           => 'testExternal00' ,
            'merchant_id'                  => '10000000000000',
            'transaction_id'               => 'JOPkusQyH3wn3u',
            'banking_account_statement_id' => $bas->getId(),
            'channel'                      => 'rbl',
            'bank_reference_number'        => $bas->getBankTransactionId(),
            'utr'                          => '211708954836',
            'type'                         => $bas->getType(),
            'amount'                       => $bas->getAmount(),
            'currency'                     => 'INR',
            'balance_id'                   => $this->bankingBalance->getId(),
        ]);

        $payout = $this->fixtures->create('payout', [
            'merchant_id'     => '10000000000000',
            'balance_id'      => $this->bankingBalance->getId(),
            'status'          => 'processed',
            'transaction_id'  => null,
            'utr'             => '211708954836',
            'amount'          => 1000,
            'channel'         => 'rbl',
            'mode'            => 'IMPS',
            'pricing_rule_id' => 'Bbg7fgaDwax04u'
        ]);

        $payout = $this->fixtures->edit('payout', $payout['id'], [
            'transaction_id'   => null,
            'transaction_type' => null,
            'id'               => 'Bbg7fgaDwax0aa',
        ]);

        $this->fixtures->create('transaction', [
            'id'          => 'JOPkusQyH3wn3u',
            'merchant_id' => '10000000000000',
            'amount'      => 1000,
            'balance_id'  => $this->bankingBalance->getId(),
            'type'        => 'external',
            'entity_id'   => 'testExternal00',
        ]);

        $params = [
            "payout_id" => $payout->getId()
        ];

        (new BasCore)->retryBasSourceLinkingForProcessedPayout($params);

        Queue::assertNotPushed(BankingAccountStatementSourceLinking::class);

        $payout = $this->getDbLastEntity('payout');

        $bas = $this->getDbLastEntity('banking_account_statement');

        $this->assertEquals($payout->getTransactionId(), $bas->getTransactionId());

        $this->assertEquals($bas->getEntityType(), 'payout');

        $this->assertEquals($bas->getEntityId(), $payout->getId());
    }
}
