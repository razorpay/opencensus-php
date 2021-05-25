<?php

namespace Functional\BankingAccountStatement;

use Queue;
use Mockery;

use RZP\Models\Payout;
use RZP\Services\Mozart;
use RZP\Models\FundTransfer;
use RZP\Models\Admin\ConfigKey;
use RZP\Constants\Mode as EnvMode;
use RZP\Tests\Functional\TestCase;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\BankingAccount\Channel;
use RZP\Exception\GatewayErrorException;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\BankingAccount\Entity as BaEntity;
use RZP\Models\External\Entity as ExternalEntity;
use RZP\Jobs\FTS\FundTransfer as FtsFundTransfer;
use RZP\Tests\Functional\Helpers\Payout\PayoutTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Models\Transaction\Entity as TransactionEntity;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Models\BankingAccountStatement\Entity as BasEntity;
use RZP\Models\BankingAccountStatement\Details as BasDetails;
use RZP\Jobs\BankingAccountStatement as BankingAccountStatementJob;
use RZP\Models\BankingAccountStatement\Processor\Icici\RequestResponseFields as F;

class IciciBankingAccountStatementTest extends TestCase
{
    use PayoutTrait;
    use PaymentTrait;
    use WorkflowTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;

    protected $fundAccount;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/IciciBankingAccountStatementTestData.php';

        parent::setUp();

        $this->fixtures->create('org:razorpay_org_live');

        $this->fixtures->on('test')->create('contact', ['id' => '1000001contact', 'active' => 1]);

        $this->fixtures->on('test')->create(
            'fund_account',
            [
                'id'           => '100000000000fa',
                'source_id'    => '1000001contact',
                'source_type'  => 'contact',
                'account_type' => 'bank_account',
                'account_id'   => '1000000lcustba'
            ]);

        $this->setUpMerchantForBusinessBanking(false, 0, 'direct', 'icici');

        $bankingAccountParams = [
            'id'             => 'xba00000000002',
            'merchant_id'    => '10000000000000',
            'account_ifsc'   => 'ICIC0000047',
            'account_number' => '2224440041626905',
            'status'         => 'activated',
            'channel'        => 'icici',
            'balance_id'     => $this->bankingBalance->getId(),
        ];

        $this->createBankingAccount($bankingAccountParams);

        $this->fixtures->create('banking_account_statement_details',[
            BasDetails\Entity::ID                                  => 'xbas0000000002',
            BasDetails\Entity::MERCHANT_ID                         => '10000000000000',
            BasDetails\Entity::BALANCE_ID                          => $this->bankingBalance->getId(),
            BasDetails\Entity::ACCOUNT_NUMBER                      => '2224440041626905',
            BasDetails\Entity::CHANNEL                             => BasDetails\Channel::ICICI,
            BasDetails\Entity::STATUS                              => BasDetails\Status::ACTIVE,
        ]);
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

    protected function getIciciErrorResponse()
    {
        $response = [
            "data" => [
                "MESSAGE"  => "Invalid user access or Account id",
                "RESPONSE" => "Failure",
                "_raw"     => "{\"MESSAGE\"=>\"Invalid user access or Account id\",\"RESPONSE\"=>\"Failure\"}"
            ],
            "error" => [
                "description"               => "",
                "gateway_error_code"        => "200",
                "gateway_error_description" => "(No error description was mapped for this error code)",
                "gateway_status_code"       => 200,
                "internal_error_code"       => "GATEWAY_ERROR_UNKNOWN_ERROR"
            ],
            "external_trace_id" => "a9a748272640c86f123b58f4601bab8b",
            "mozart_id"         => "c0qe3r2055u5f78fipv0",
            "next"              => [],
            "success"           => false
        ];

        return $response;
    }

    protected function getIciciDataResponse()
    {
        $response = [
            "data"              => [
                "ACCOUNTNO" => "2224440041626905",
                "AGGR_ID"   => "RZP1234",
                "CORP_ID"   => "RAZORPAY",
                "RESPONSE"  => "SUCCESS",
                "Record"    => [
                    [
                        "AMOUNT"        => "10,000.00",
                        "BALANCE"       => "10,000.00",
                        "CHEQUENO"      => [],
                        "REMARKS"       => "MMT/IMPS/104910349740/Shippuden/Naruto",
                        "TRANSACTIONID" => "S71034864",
                        "TXNDATE"       => "18-02-2021 10:59:00",
                        "TYPE"          => "CR",
                        "VALUEDATE"     => "18-02-2021"
                    ],
                    [
                        "AMOUNT"        => "1.00",
                        "BALANCE"       => "9,999.00",
                        "CHEQUENO"      => [],
                        "REMARKS"       => "MMT/IMPS/104913832918/TESTICICI/SAMPLE/Hokage",
                        "TRANSACTIONID" => "S74203578",
                        "TXNDATE"       => "18-02-2021 13:20:51",
                        "TYPE"          => "DR",
                        "VALUEDATE"     => "18-02-2021"
                    ],
                    [
                        "AMOUNT"        => "1.00",
                        "BALANCE"       => "9,998.00",
                        "CHEQUENO"      => [],
                        "REMARKS"       => "INF/NEFT/023629961691/SBIN0050103/TestIcici/Boruto",
                        "TRANSACTIONID" => "S86758818",
                        "TXNDATE"       => "19-02-2021 04:29:52",
                        "TYPE"          => "DR",
                        "VALUEDATE"     => "19-02-2021",
                    ],
                ],
                "URN"       => "SR189932540",
                "USER_ID"   => "SATYANAR"
            ],
            "error"             => null,
            "external_trace_id" => "0fd2229a19bf561b600847afb283c551",
            "mozart_id"         => "c0qd3ta055u5f78fipug",
            "next"              => [],
            "success"           => true
        ];

        return $response;
    }

    protected function getIciciDataResponseForRtgs()
    {
        $response = [
            "data" => [
                "ACCOUNTNO" => "2224440041626905",
                "AGGR_ID"   => "RZP1234",
                "CORP_ID"   => "RAZORPAY",
                "RESPONSE"  => "SUCCESS",
                "Record"    => [
                    [
                        "AMOUNT"        => "2,00,000.00",
                        "BALANCE"       => "1,50,000.00",
                        "CHEQUENO"      => [],
                        "REMARKS"       => "RTGS/ICICR42021042600532487/RATN0000156/Naruto Uzumaki",
                        "TRANSACTIONID" => "S39060827",
                        "TXNDATE"       => "26-04-2021 17:04:47",
                        "TYPE"          => "DR",
                        "VALUEDATE"     => "26-04-2021"
                    ]
                ],
                "URN"       => "SR189932540",
                "USER_ID"   => "Sasuke"
            ],
            "error"             => null,
            "external_trace_id" => "0fd2229a19bf561b600847afb283c551",
            "mozart_id"         => "c0qd3ta055u5f78fipug",
            "next"              => [],
            "success"           => true
        ];

        return $response;
    }

    protected function getIciciDataResponseForIFT()
    {
        $response = [
            "data" => [
                "ACCOUNTNO" => "2224440041626905",
                "AGGR_ID"   => "RZP1234",
                "CORP_ID"   => "RAZORPAY",
                "RESPONSE"  => "SUCCESS",
                "Record" => [
                    [

                        "AMOUNT"        => "1.00",
                        "BALANCE"       => "9,999.00",
                        "CHEQUENO"      => [],
                        "REMARKS"       => "INF/INFT/023652565741/TestIciciProd06/Rajat Singh",
                        "TRANSACTIONID" => "C97592667",
                        "TXNDATE"       => "23-02-2021 03:22:34",
                        "TYPE"          => "DR",
                        "VALUEDATE"     => "23-02-2021",

                    ]
                ],
                "URN"       => "SR189932540",
                "USER_ID"   => "Sasuke"
            ],
            "error"             => null,
            "external_trace_id" => "0fd2229a19bf561b600847afb283c551",
            "mozart_id"         => "c0qd3ta055u5f78fipug",
            "next"              => [],
            "success"           => true
        ];

        return $response;
    }

    protected function getIciciDataResponseForNEFT()
    {
        $response = [
            "data" => [
                "ACCOUNTNO" => "2224440041626905",
                "AGGR_ID"   => "RZP1234",
                "CORP_ID"   => "RAZORPAY",
                "RESPONSE"  => "SUCCESS",
                "Record"    => [
                    [
                        "AMOUNT"        => "1.00",
                        "BALANCE"       => "9,999.00",
                        "CHEQUENO"      => [],
                        "REMARKS"       => "INF/NEFT/023629961691/SBIN0050103/TestIcici/Boruto",
                        "TRANSACTIONID" => "S86758818",
                        "TXNDATE"       => "19-02-2021 04:29:52",
                        "TYPE"          => "DR",
                        "VALUEDATE"     => "19-02-2021",
                    ],
                ],
                "URN"       => "SR189932540",
                "USER_ID"   => "Sasuke"
            ],
            "error"             => null,
            "external_trace_id" => "0fd2229a19bf561b600847afb283c551",
            "mozart_id"         => "c0qd3ta055u5f78fipug",
            "next"              => [],
            "success"           => true
        ];

        return $response;
    }

    public function testDispatchIciciAccountStatementFetch($channel = Channel::ICICI)
    {
        $this->ba->cronAuth();

        $request = [
            'url'       => '/banking_account_statement/process/icici',
            'method'    => 'POST'
        ];

        $this->flushCache();

        (new AdminService)->setConfigKeys([ConfigKey::BANKING_ACCOUNT_STATEMENT_RATE_LIMIT => 1]);

        Queue::fake();

        $this->makeRequestAndGetContent($request);

        Queue::assertPushed(BankingAccountStatementJob::class, 1);
    }

    /**
     * Case where the response from ICICI is success
     */
    public function testIciciAccountStatementCase1()
    {
        $mockedResponse = $this->getIciciDataResponse();

        $this->setMozartMockResponse($mockedResponse);

        $basdBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNull($basdBeforeTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        $this->ba->cronAuth();

        $this->startTest();

        $transactions = $mockedResponse[F::DATA][F::RECORD];

        $txn = last($transactions);

        $basActual = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT, true);

        $externalActual = $this->getLastEntity(EntityConstants::EXTERNAL, true);

        $externalId = str_after($externalActual[ExternalEntity::ID], 'ext_');

        $externalTxnId = $externalActual[ExternalEntity::TRANSACTION_ID];

        $txnEntity = $this->getDbEntityById(EntityConstants::TRANSACTION, $externalTxnId);

        $txnActual = $txnEntity->toArray();

        $basdAfterTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNotNull($basdAfterTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        $this->assertEquals($txnActual[TransactionEntity::POSTED_AT], $basActual[BasEntity::POSTED_DATE]);

        $basExpected = [
            BasEntity::MERCHANT_ID           => $txnActual[TransactionEntity::MERCHANT_ID],
            BasEntity::BANK_TRANSACTION_ID   => trim($txn[F::TRANSACTION_ID]),
            BasEntity::TYPE                  => 'debit',
            BasEntity::AMOUNT                => 100,
            BasEntity::BALANCE               => 999800,
            BasEntity::POSTED_DATE           => 1613689192,
            BasEntity::TRANSACTION_DATE      => 1613673000,
            BasEntity::DESCRIPTION           => trim($txn[F::REMARKS]),
            BasEntity::CHANNEL               => 'icici',
            BasEntity::ENTITY_ID             => $externalId,
            BasEntity::ENTITY_TYPE           => $externalActual[ExternalEntity::ENTITY],
            BasEntity::TRANSACTION_ID        => $txnActual[TransactionEntity::ID],
        ];

        $this->assertArraySubset($basExpected, $basActual, true);

        $externalExpected = [
            BasEntity::MERCHANT_ID                => $basActual[BasEntity::MERCHANT_ID],
            ExternalEntity::BALANCE_ID            => $this->bankingBalance->getId(),
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
     * Case where icici return error.
     * assumption error not mapped at mozart
     */
    public function testIciciAccountStatementCase2()
    {
       $mockedResponse = $this->getIciciErrorResponse();

        $basdBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNull($basdBeforeTest[BasDetails\Entity::STATEMENT_CLOSING_BALANCE_CHANGE_AT]);

       $this->setMozartMockResponse($mockedResponse);

        $this->ba->cronAuth();

        $request = [
            'method'  => 'post',
            'url'     => '/banking_account_statement/process',
            'content' => [
                'account_number'  => '2224440041626905',
                'channel'         => 'icici',
            ],
        ];

        $response = $this->makeRequestAndCatchException(function() use ($request) {
            $this->makeRequestAndGetContent($request);
        }, GatewayErrorException::class);

        $basdAfterTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNull($basdAfterTest[BasDetails\Entity::STATEMENT_CLOSING_BALANCE_CHANGE_AT]);
    }

    protected function getIciciPage1Response()
    {
        $response = [
            "data" => [
                "ACCOUNTNO" => "2224440041626905",
                "AGGR_ID"   => "RZP1234",
                "CORP_ID"   => "RAZORPAY",
                "RESPONSE"  => "SUCCESS",
                "Record"    => [
                    [
                        "AMOUNT"        => "10,000.00",
                        "BALANCE"       => "10,000.00",
                        "CHEQUENO"      => [],
                        "REMARKS"       => "MMT/IMPS/104910349740/Shippuden/Naruto",
                        "TRANSACTIONID" => "S71034864",
                        "TXNDATE"       => "18-02-2021 10:59:00",
                        "TYPE"          => "CR",
                        "VALUEDATE"     => "18-02-2021"
                    ],
                     [
                        "AMOUNT"        => "1.00",
                        "BALANCE"       => "9,999.00",
                        "CHEQUENO"      => [],
                        "REMARKS"       => "MMT/IMPS/104913832918/TESTICICI/SAMPLE/Hokage",
                        "TRANSACTIONID" => "S74203578",
                        "TXNDATE"       => "18-02-2021 13:20:51",
                        "TYPE"          => "DR",
                        "VALUEDATE"     => "18-02-2021"
                    ],
                ],
                "URN"       => "SR189932540",
                "USER_ID"   => "Sasuke"
            ],
            "error"             => null,
            "external_trace_id" => "0fd2229a19bf561b600847afb283c551",
            "mozart_id"         => "c0qd3ta055u5f78fipug",
            "next"              => [],
            "success"           => true
        ];

        return $response;
    }

    public function testConstructingLasttrid()
    {
        (new AdminService)->setConfigKeys(
            [
                ConfigKey::ICICI_STATEMENT_FETCH_RATE_LIMIT    => 1,
                ConfigKey::ICICI_STATEMENT_FETCH_ATTEMPT_LIMIT => 1,
                ConfigKey::ICICI_ENABLE_RATE_LIMIT_FLOW        => 0
            ]);

        $mockedResponse = $this->getIciciPage1Response();

        unset($mockedResponse[F::DATA][F::RECORD][1]);
        $mockedResponse[F::DATA][F::RECORD] = $mockedResponse[F::DATA][F::RECORD][0];
        $this->setMozartMockResponse($mockedResponse);

        $basdBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNull($basdBeforeTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        $this->ba->cronAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/process',
            'content' => [
                'account_number'  => '2224440041626905',
                'channel'         => 'icici',
            ],
        ];

        // first run
        $this->makeRequestAndGetContent($request);

        $mockedResponse = $this->getIciciPage1Response();

        unset($mockedResponse[F::DATA][F::RECORD][0]);
        $mockedResponse[F::DATA][F::RECORD] = $mockedResponse[F::DATA][F::RECORD][1];
        $this->setMozartMockResponse($mockedResponse);

        $basdAfterFirstRun = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNotNull($basdAfterFirstRun[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        $this->ba->cronAuth();

        $testData = $this->testData['testIciciAccountStatementCase1'];

        $this->testData[__FUNCTION__] = $testData;

        // Second run
        $this->startTest();

        $basdAfterSecondRun = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNotNull($basdAfterSecondRun[BasDetails\Entity::STATEMENT_CLOSING_BALANCE_CHANGE_AT]);
    }

    protected function setupForIciciPayout($channel = Channel::ICICI, $amount = 10095, $mode = FundTransfer\Mode::IMPS)
    {
        $this->ba->privateAuth();

        $this->createContact();

        $this->createFundAccount();

        $content = [
            'account_number'  => '2224440041626905',
            'amount'          => $amount,
            'currency'        => 'INR',
            'purpose'         => 'payout',
            'narration'       => 'Icici account payout',
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

    protected function updateFta( $ftsId, string $sourceId, string $sourceType, string $status)
    {
        $content = [
            Attempt\Entity::STATUS           => $status,
            Attempt\Entity::SOURCE_ID        => $sourceId,
            Attempt\Entity::SOURCE_TYPE      => $sourceType,
            Attempt\Entity::FUND_TRANSFER_ID => $ftsId
        ];


        $request = [
            'url'       => '/update_fts_fund_transfer',
            'method'    => 'POST',
            'content'   => $content
        ];

        $this->ba->ftsAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    public function testUtrMappingForNEFT()
    {
        $this->fixtures->edit('balance', $this->bankingBalance->getId(), ['balance' => '1000000']);

        $this->setupForIciciPayout(Channel::ICICI, 100, FundTransfer\Mode::NEFT);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XB7', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated',
                                                        'utr' => '023629961691' ]);

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '023629961691' ]);

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
        $this->assertEquals(FundTransfer\Mode::NEFT, $payout['mode']);
        $this->assertEquals('023629961691', $payout['utr']);
        $this->assertEquals(Attempt\Status::PROCESSED, $attempt['status']);
        $this->assertEquals(FundTransfer\Mode::NEFT, $attempt['mode']);

        $mockedResponse = $this->getIciciDataResponseForNEFT();

        $this->setMozartMockResponse($mockedResponse);

        $testData = $this->testData['testIciciAccountStatementCase1'];

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

        $this->assertEquals('Bbg7cl6t6I3XB7', $feeBreakup[0]['pricing_rule_id']);
        $this->assertEquals(500, $feeBreakup[0]['amount']);
        $this->assertEquals(EntityConstants::PAYOUT, $feeBreakup[0]['name']);
        $this->assertEquals(90, $feeBreakup[1]['amount']);
        $this->assertEquals(EntityConstants::TAX, $feeBreakup[1]['name']);
    }

    public function testUtrMappingForIMPS()
    {
        $this->fixtures->edit('balance', $this->bankingBalance->getId(), ['balance' => '1000000']);

        $this->setupForIciciPayout(Channel::ICICI, 100, FundTransfer\Mode::IMPS);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XB7', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated',
                                                        'utr' => '104913832918' ]);

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => '104913832918' ]);

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
        $this->assertEquals('104913832918', $payout['utr']);
        $this->assertEquals(Attempt\Status::PROCESSED, $attempt['status']);
        $this->assertEquals(FundTransfer\Mode::IMPS, $attempt['mode']);

        $mockedResponse = $this->getIciciDataResponse();
        unset($mockedResponse[F::DATA][F::RECORD][0]);
        unset($mockedResponse[F::DATA][F::RECORD][2]);
        $mockedResponse[F::DATA][F::RECORD] = $mockedResponse[F::DATA][F::RECORD][1];
        $this->setMozartMockResponse($mockedResponse);

        $testData = $this->testData['testIciciAccountStatementCase1'];

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

        $this->assertEquals('Bbg7cl6t6I3XB7', $feeBreakup[0]['pricing_rule_id']);
        $this->assertEquals(500, $feeBreakup[0]['amount']);
        $this->assertEquals(EntityConstants::PAYOUT, $feeBreakup[0]['name']);
        $this->assertEquals(90, $feeBreakup[1]['amount']);
        $this->assertEquals(EntityConstants::TAX, $feeBreakup[1]['name']);
    }

    public function testUtrMappingForRTGS()
    {
        $this->fixtures->edit('balance', $this->bankingBalance->getId(), ['balance' => '35000000']);

        $this->setupForIciciPayout(Channel::ICICI, 20000000, FundTransfer\Mode::RTGS);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(1770, $payout['fees']);
        $this->assertEquals(270, $payout['tax']);
        $this->assertEquals('Bbg7e4oKCgaucf', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated',
                                                        'utr'    => 'ICICR42021042600532487']);

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], ['utr' => 'ICICR42021042600532487' ]);

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
        $this->assertEquals('ICICR42021042600532487', $payout['utr']);
        $this->assertEquals(Attempt\Status::PROCESSED, $attempt['status']);
        $this->assertEquals(FundTransfer\Mode::RTGS, $attempt['mode']);

        $mockedResponse = $this->getIciciDataResponseForRtgs();

        $this->setMozartMockResponse($mockedResponse);

        $testData = $this->testData['testIciciAccountStatementCase1'];

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

        $this->assertEquals('Bbg7e4oKCgaucf', $feeBreakup[0]['pricing_rule_id']);
        $this->assertEquals(1500, $feeBreakup[0]['amount']);
        $this->assertEquals(EntityConstants::PAYOUT, $feeBreakup[0]['name']);
        $this->assertEquals(270, $feeBreakup[1]['amount']);
        $this->assertEquals(EntityConstants::TAX, $feeBreakup[1]['name']);
    }

    // currently IFT mode is not supported for icici current account. so it will throw error
    public function testCreatingIFTPayout()
    {
        $this->fixtures->edit('balance', $this->bankingBalance->getId(), ['balance' => '1000000']);

        $this->ba->privateAuth();

        $this->createContact();

        $this->createFundAccount();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['fund_account_id'] =  'fa_' . $this->fundAccount->getId();

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }
}
