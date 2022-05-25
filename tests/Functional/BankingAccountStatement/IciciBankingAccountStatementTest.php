<?php

namespace Functional\BankingAccountStatement;

use Queue;
use Mockery;
use Carbon\Carbon;
use RZP\Models\Payout;
use RZP\Services\Mozart;
use RZP\Constants\Table;
use RZP\Constants\Timezone;
use RZP\Models\FundTransfer;
use RZP\Models\Admin\ConfigKey;
use RZP\Constants\Mode as EnvMode;
use RZP\Tests\Functional\TestCase;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\BankingAccount\Channel;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Exception\GatewayErrorException;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\Feature\Constants as Features;
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
use RZP\Jobs\IciciBankingAccountStatement as IciciBankingAccountStatementJob;
use RZP\Models\BankingAccountStatement\Processor\Icici\RequestResponseFields as F;

class IciciBankingAccountStatementTest extends TestCase
{
    use PayoutTrait;
    use PaymentTrait;
    use WorkflowTrait;
    use DbEntityFetchTrait;
    use TestsWebhookEvents;
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

        $this->fixtures->create('banking_account_statement_details',[
            BasDetails\Entity::ID                                  => 'xbas0000000002',
            BasDetails\Entity::MERCHANT_ID                         => '10000000000000',
            BasDetails\Entity::BALANCE_ID                          => $this->bankingBalance->getId(),
            BasDetails\Entity::ACCOUNT_NUMBER                      => '2224440041626905',
            BasDetails\Entity::CHANNEL                             => BasDetails\Channel::ICICI,
            BasDetails\Entity::STATUS                              => BasDetails\Status::ACTIVE,
        ]);

        $this->app['config']->set('applications.banking_account_service.mock', true);
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

    protected function getIciciNoRecordsFoundGatewayExceptionResponse()
    {
        $response = [
            "data" => [
                "MESSAGE"  => "The transactions do not exist for the account with the entered criteria.",
                "RESPONSE" => "Failure",
                "_raw"     => '{\\"MESSAGE\\":\\"The transactions do not exist for the account with the entered criteria.\\",\\"RESPONSE\\":\\"Failure\\"}'
            ],
            "error" => [
                "description"               => "",
                "gateway_error_code"        => "The transactions do not exist for the account with the entered criteria.",
                "gateway_error_description" => "(No error description was mapped for this error code)",
                "gateway_status_code"       => 200,
                "internal_error_code"       => "GATEWAY_ERROR_UNKNOWN_ERROR"
            ],
            "external_trace_id" => "64905671187e1fba1f40983e64ad9c26",
            "mozart_id"         => "c2lpkg7ga874cqjaleq0",
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

    protected function getIciciDataResponseForVariousRegex()
    {
        $response = [
            "data"              => [
                "ACCOUNTNO" => "2224440041626905",
                "AGGR_ID"   => "RZP1234",
                "CORP_ID"   => "RAZORPAY",
                "RESPONSE"  => "SUCCESS",
                "Record"    => [
                    [
                        "AMOUNT"        => "100.00",
                        "BALANCE"       => "100.00",
                        "CHEQUENO"      => [],
                        "REMARKS"       => "NEFT-AXISCN0118376057-RAZORPAY PVT",
                        "TRANSACTIONID" => "S71034864",
                        "TXNDATE"       => "18-02-2021 10:59:00",
                        "TYPE"          => "CR",
                        "VALUEDATE"     => "18-02-2021"
                    ],
                    [
                        "AMOUNT"        => "1.00",
                        "BALANCE"       => "101.00",
                        "CHEQUENO"      => [],
                        "REMARKS"       => "INF/INFT/025802182571/Razorpay/Leaf",
                        "TRANSACTIONID" => "S74203578",
                        "TXNDATE"       => "18-02-2021 13:20:51",
                        "TYPE"          => "CR",
                        "VALUEDATE"     => "18-02-2021"
                    ],
                    [
                        "AMOUNT"        => "1.00",
                        "BALANCE"       => "102.00",
                        "CHEQUENO"      => [],
                        "REMARKS"       => "BIL/INFT/000270116851/TEST ICICI/Ishiki",
                        "TRANSACTIONID" => "S86758818",
                        "TXNDATE"       => "19-02-2021 04:29:52",
                        "TYPE"          => "CR",
                        "VALUEDATE"     => "19-02-2021",
                    ],
                    [
                        "AMOUNT"        => "1.00",
                        "BALANCE"       => "103.00",
                        "CHEQUENO"      => [],
                        "REMARKS"       => "RTGS-AUBLR12021123000584069-RZP PVT-212121133524511",
                        "TRANSACTIONID" => "S86234818",
                        "TXNDATE"       => "19-02-2021 04:29:52",
                        "TYPE"          => "CR",
                        "VALUEDATE"     => "19-02-2021",
                    ],
                    [
                        "AMOUNT"        => "1.00",
                        "BALANCE"       => "104.00",
                        "CHEQUENO"      => [],
                        "REMARKS"       => "UPI/115421282359/UPI/Jiraya/DBS Bank India",
                        "TRANSACTIONID" => "S86569818",
                        "TXNDATE"       => "19-02-2021 04:29:52",
                        "TYPE"          => "CR",
                        "VALUEDATE"     => "19-02-2021",
                    ],
                    [
                        "AMOUNT"        => "1.00",
                        "BALANCE"       => "103.00",
                        "CHEQUENO"      => [],
                        "REMARKS"       => "BIL/BPAY/000000043NVN/Testing",
                        "TRANSACTIONID" => "S86758150",
                        "TXNDATE"       => "19-02-2021 04:29:52",
                        "TYPE"          => "DR",
                        "VALUEDATE"     => "19-02-2021",
                    ],
                    [
                        "AMOUNT"        => "1.00",
                        "BALANCE"       => "102.00",
                        "CHEQUENO"      => [],
                        "REMARKS"       => "BIL/ONL/000286716570/Testing",
                        "TRANSACTIONID" => "S86758346",
                        "TXNDATE"       => "19-02-2021 04:29:52",
                        "TYPE"          => "DR",
                        "VALUEDATE"     => "19-02-2021",
                    ],
                    [
                        "AMOUNT"        => "1.00",
                        "BALANCE"       => "101.00",
                        "CHEQUENO"      => [],
                        "REMARKS"       => "UPI/115600327157/NA/Itachi/",
                        "TRANSACTIONID" => "S86758231",
                        "TXNDATE"       => "19-02-2021 04:29:52",
                        "TYPE"          => "DR",
                        "VALUEDATE"     => "19-02-2021",
                    ]
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

    protected function getIciciDataResponseForExistingAccounts()
    {
        $response = [
            "data"              => [
                "ACCOUNTNO" => "2224440041626905",
                "AGGR_ID"   => "RZP1234",
                "CORP_ID"   => "RAZORPAY",
                "RESPONSE"  => "SUCCESS",
                "Record"    => [
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
                    [
                        "AMOUNT"        => "1.00",
                        "BALANCE"       => "9,997.00",
                        "CHEQUENO"      => [],
                        "REMARKS"       => "CRP/DEBIT/20220201011500",
                        "TRANSACTIONID" => "S86758858",
                        "TXNDATE"       => "01-02-2022 01:15:08",
                        "TYPE"          => "DR",
                        "VALUEDATE"     => "01-02-2022",
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

    protected function getIciciMalFormedDataResponse()
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
                        "BALANCE"       => "20,000.00",
                        "CHEQUENO"      => [],
                        "REMARKS"       => "MMT/IMPS/104910349740/Shippuden/Naruto",
                        "TRANSACTIONID" => "S71034864",
                        "TXNDATE"       => "18-02-2021 10:59:00",
                        "TYPE"          => "CR",
                        "VALUEDATE"     => "18-02-2021"
                    ],
                    [
                        "AMOUNT"        => "1.00",
                        "BALANCE"       => "19,999.00",
                        "CHEQUENO"      => [],
                        "REMARKS"       => "MMT/IMPS/104913832918/TESTICICI/SAMPLE/Hokage",
                        "TRANSACTIONID" => "S74203578",
                        "TXNDATE"       => "18-02-2021 13:20:51",
                        "TYPE"          => "DR",
                        "VALUEDATE"     => "18-02-2021"
                    ],
                    [
                        "AMOUNT"        => "1.00",
                        "BALANCE"       => "19,998.00",
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

    protected function getIciciDataResponseForReversal()
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
                        "REMARKS"       => "INF/NEFT/023629961691/SBIN0050103/TestIcici/Boruto",
                        "TRANSACTIONID" => "S86758818",
                        "TXNDATE"       => "19-02-2021 04:29:52",
                        "TYPE"          => "DR",
                        "VALUEDATE"     => "19-02-2021",
                    ],
                    [
                        "AMOUNT"        => "1.00",
                        "BALANCE"       => "10,000.00",
                        "CHEQUENO"      => [],
                        "REMARKS"       => "NEFT-RETURN-23629961691DC-Naruto-ACCOUNT DOES NOT EXIST  R03",
                        "TRANSACTIONID" => "S87272425",
                        "TXNDATE"       => "19-02-2021 07:31:16",
                        "TYPE"          => "CR",
                        "VALUEDATE"     => "19-02-2021",
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

        Queue::assertPushed(IciciBankingAccountStatementJob::class, 1);
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

    public function testIciciDisableAccountStatementFetch()
    {
        $this->setMockRazorxTreatment([RazorxTreatment::DISABLE_STATEMENT_FETCH    => 'on']);

        $mockedResponse = $this->getIciciDataResponse();

        $this->setMozartMockResponse($mockedResponse);

        $this->ba->cronAuth();

        $response = $this->startTest();

        $this->assertEmpty($response['accounts_processed']);
    }

    public function testIciciAccountStatementWithVariousRegex()
    {
        $mockedResponse = $this->getIciciDataResponseForVariousRegex();

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
            BasEntity::BALANCE               => 10100,
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

        $utrsExpected = [
            'AXISCN0118376057',
            '025802182571',
            '000270116851',
            'AUBLR12021123000584069',
            '115421282359',
            '000000043NVN',
            '000286716570',
            '115600327157',
        ];

        $utrsActual = $this->getDbEntities(EntityConstants::BANKING_ACCOUNT_STATEMENT)
                           ->map(function($basEntity) {
                               return $basEntity->getUtr();
                           })->all();

        $this->assertEqualsCanonicalizing($utrsExpected, $utrsActual);
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
            'url'     => '/banking_account_statement/process/icici',
            'content' => [],
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
        $this->fixtures->create('banking_account_statement',
                                [
                                    'type'                      => 'credit',
                                    'amount'                    => '1000000',
                                    'channel'                   => 'icici',
                                    'account_number'            => '2224440041626905',
                                    'bank_transaction_id'       => 'SDHDH',
                                    'balance'                   => 1000000,
                                    'transaction_date'          => 1584987183,
                                    'posted_date'               => 1584987183,
                                ]);

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

        $eventTestDataKey = 'testTransactionCreatedWebhookForSuccessfulMappingToPayout';
        $this->expectWebhookEventWithContents('transaction.created', $eventTestDataKey);

        $this->startTest();

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $payoutTxn = $this->getDbLastEntity('transaction');
        $externalEntries = $this->getDbEntities('external', ['balance_id' => $payout['balance_id']]);
        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(EntityConstants::PAYOUT, $basEntries[1]['entity_type']);
        $this->assertEquals($payout['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($payout['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals($payout['utr'], $basEntries[1]['utr']);

        $this->assertEquals(1, count($externalEntries));
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
        $this->mockLedgerSns(0);

        $this->fixtures->create('banking_account_statement',
                                [
                                    'type'                      => 'credit',
                                    'amount'                    => '1000000',
                                    'channel'                   => 'icici',
                                    'account_number'            => '2224440041626905',
                                    'bank_transaction_id'       => 'SDHDH',
                                    'balance'                   => 1000000,
                                    'transaction_date'          => 1584987183,
                                    'posted_date'               => 1584987183,
                                ]);

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

        $this->assertEquals(EntityConstants::PAYOUT, $basEntries[1]['entity_type']);
        $this->assertEquals($payout['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($payout['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals($payout['utr'], $basEntries[1]['utr']);

        $this->assertEquals(1, count($externalEntries));
        $this->assertEquals(EntityConstants::PAYOUT, $payoutTxn['type']);
        $this->assertEquals($payoutTxn['id'], $payout['transaction_id']);

        $feeBreakup = $this->getDbEntities('fee_breakup', ['transaction_id' => $payout['transaction_id']]);

        $this->assertEquals('Bbg7cl6t6I3XB7', $feeBreakup[0]['pricing_rule_id']);
        $this->assertEquals(500, $feeBreakup[0]['amount']);
        $this->assertEquals(EntityConstants::PAYOUT, $feeBreakup[0]['name']);
        $this->assertEquals(90, $feeBreakup[1]['amount']);
        $this->assertEquals(EntityConstants::TAX, $feeBreakup[1]['name']);
    }

    // asserting external credit and payout events to ledger
    public function testUtrMappingForIMPSWithLedgerShadow()
    {
        $this->fixtures->merchant->addFeatures([Features::DA_LEDGER_JOURNAL_WRITES]);

        $ledgerSnsPayloadArray = [];
        $this->mockLedgerSns(3, $ledgerSnsPayloadArray);

        $this->fixtures->create('banking_account_statement',
            [
                'type'                      => 'credit',
                'amount'                    => '1000000',
                'channel'                   => 'icici',
                'account_number'            => '2224440041626905',
                'bank_transaction_id'       => 'SDHDH',
                'balance'                   => 1000000,
                'transaction_date'          => 1584987183,
                'posted_date'               => 1584987183,
            ]);

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

        $this->assertEquals(EntityConstants::PAYOUT, $basEntries[1]['entity_type']);
        $this->assertEquals($payout['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($payout['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals($payout['utr'], $basEntries[1]['utr']);

        $this->assertEquals(1, count($externalEntries));
        $this->assertEquals(EntityConstants::PAYOUT, $payoutTxn['type']);
        $this->assertEquals($payoutTxn['id'], $payout['transaction_id']);

        $feeBreakup = $this->getDbEntities('fee_breakup', ['transaction_id' => $payout['transaction_id']]);

        $this->assertEquals('Bbg7cl6t6I3XB7', $feeBreakup[0]['pricing_rule_id']);
        $this->assertEquals(500, $feeBreakup[0]['amount']);
        $this->assertEquals(EntityConstants::PAYOUT, $feeBreakup[0]['name']);
        $this->assertEquals(90, $feeBreakup[1]['amount']);
        $this->assertEquals(EntityConstants::TAX, $feeBreakup[1]['name']);

        $transactorTypeArray = [
            'da_ext_credit',
            'da_payout_processed',
            'da_payout_processed_recon'
        ];

        $transactorIdArray = [
            $externalEntries[0]->getPublicId(),
            $payout->getPublicId(),
            $payout->getPublicId(),
        ];

        $commissionArray = [
            '',
            '590',
            '590',
        ];
        $taxArray = [
            '',
            '90',
            '90',
        ];

        $apiTransactionIdArray = [
            $externalEntries[0]->getTransactionId(),
            $payout->getTransactionId(),
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
    }

    public function testUtrMappingForFeePayoutWithLedgerShadow()
    {
        $this->fixtures->merchant->addFeatures([Features::DA_LEDGER_JOURNAL_WRITES]);

        $ledgerSnsPayloadArray = [];
        $this->mockLedgerSns(2, $ledgerSnsPayloadArray);

        $this->fixtures->create('banking_account_statement',
            [
                'type'                      => 'credit',
                'amount'                    => '1000000',
                'channel'                   => 'icici',
                'account_number'            => '2224440041626905',
                'bank_transaction_id'       => 'SDHDH',
                'balance'                   => 1000000,
                'transaction_date'          => 1584987183,
                'posted_date'               => 1584987183,
            ]);

        $this->setupForIciciPayout(Channel::ICICI, 100, FundTransfer\Mode::IMPS);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XB7', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated',
            'utr'       => '104913832918',
            'purpose'   => 'rzp_fees'
        ]);

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

        $this->assertEquals(EntityConstants::PAYOUT, $basEntries[1]['entity_type']);
        $this->assertEquals($payout['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($payout['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals($payout['utr'], $basEntries[1]['utr']);

        $this->assertEquals(1, count($externalEntries));
        $this->assertEquals(EntityConstants::PAYOUT, $payoutTxn['type']);
        $this->assertEquals($payoutTxn['id'], $payout['transaction_id']);

        $feeBreakup = $this->getDbEntities('fee_breakup', ['transaction_id' => $payout['transaction_id']]);

        $this->assertEquals('Bbg7cl6t6I3XB7', $feeBreakup[0]['pricing_rule_id']);
        $this->assertEquals(500, $feeBreakup[0]['amount']);
        $this->assertEquals(EntityConstants::PAYOUT, $feeBreakup[0]['name']);
        $this->assertEquals(90, $feeBreakup[1]['amount']);
        $this->assertEquals(EntityConstants::TAX, $feeBreakup[1]['name']);

        $transactorTypeArray = [
            'da_ext_credit',
            'da_fee_payout_processed',
        ];

        $transactorIdArray = [
            $externalEntries[0]->getPublicId(),
            $payout->getPublicId(),
        ];

        $commissionArray = [
            '',
            '590',
        ];

        $taxArray = [
            '',
            '90',
        ];

        $apiTransactionIdArray = [
            $externalEntries[0]->getTransactionId(),
            $payout->getTransactionId(),
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
            $this->assertEquals($apiTransactionIdArray[$index], $ledgerRequestPayload['api_transaction_id']);
            $this->assertArrayNotHasKey('fee_accounting', $ledgerRequestPayload['additional_params']);
        }
    }

    public function testUtrMappingForRTGS()
    {
        $this->fixtures->create('banking_account_statement',
                                [
                                    'type'                      => 'credit',
                                    'amount'                    => '35000000',
                                    'channel'                   => 'icici',
                                    'account_number'            => '2224440041626905',
                                    'bank_transaction_id'       => 'SDHDH',
                                    'balance'                   => 35000000,
                                    'transaction_date'          => 1584987183,
                                    'posted_date'               => 1584987183,
                                ]);

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

        $this->assertEquals(EntityConstants::PAYOUT, $basEntries[1]['entity_type']);
        $this->assertEquals($payout['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($payout['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals($payout['utr'], $basEntries[1]['utr']);

        $this->assertEquals(1, count($externalEntries));
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

    public function testWebhookEventForIciciAccountStatementForSuccessfulMappingToExternal()
    {
        $mockedResponse = $this->getIciciDataResponse();

        unset($mockedResponse[F::DATA][F::RECORD][1]);
        unset($mockedResponse[F::DATA][F::RECORD][2]);
        $mockedResponse[F::DATA][F::RECORD] = $mockedResponse[F::DATA][F::RECORD][0];
        $this->setMozartMockResponse($mockedResponse);

        $basdBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNull($basdBeforeTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        $testData = $this->testData['testIciciAccountStatementCase1'];

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->cronAuth();

        $eventTestDataKey = 'testTransactionCreatedWebhookForSuccessfulMappingToExternal';
        $this->expectWebhookEventWithContents('transaction.created', $eventTestDataKey);

        $this->startTest();
    }

    public function testWebhookEventForIciciAccountStatementForSuccessfulMappingToReversal()
    {
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
            Attempt\Status::FAILED);

        $payout = $this->getDbLastEntity('payout');

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Payout\Status::FAILED, $payout['status']);
        $this->assertEquals(FundTransfer\Mode::NEFT, $payout['mode']);
        $this->assertEquals(Attempt\Status::FAILED, $attempt['status']);
        $this->assertEquals(FundTransfer\Mode::NEFT, $attempt['mode']);

        $mockedResponse = $this->getIciciDataResponseForReversal();

        $this->setMozartMockResponse($mockedResponse);

        $this->ba->cronAuth();

        $eventTestDataKey = 'testPayoutReversedWebhookForSuccessfulMappingToReversal';

        $this->expectWebhookEventWithContents('payout.reversed', $eventTestDataKey);

        $eventTestDataKey1 = 'testTransactionCreatedWebhookForSuccessfulMappingToReversal';
        $data = & $this->testData['testTransactionCreatedWebhookForSuccessfulMappingToReversal'];
        $data['payload']['transaction']['entity']['source']['payout_id'] = 'pout_' . $payout->getId();

        $this->expectWebhookEventWithContents('transaction.created', $eventTestDataKey1);

        $testData = $this->testData['testIciciAccountStatementCase1'];

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }

    // asserting external credit, external debit, payout and reversal events to ledger
    public function testWebhookEventForIciciAccountStatementForSuccessfulMappingToReversalWithLedgerShadow()
    {
        $this->fixtures->merchant->addFeatures([Features::DA_LEDGER_JOURNAL_WRITES]);

        $ledgerSnsPayloadArray = [];
        $this->mockLedgerSns(6, $ledgerSnsPayloadArray);

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
            Attempt\Status::FAILED);

        $payout = $this->getDbLastEntity('payout');

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Payout\Status::FAILED, $payout['status']);
        $this->assertEquals(FundTransfer\Mode::NEFT, $payout['mode']);
        $this->assertEquals(Attempt\Status::FAILED, $attempt['status']);
        $this->assertEquals(FundTransfer\Mode::NEFT, $attempt['mode']);

        $mockedResponse = $this->getIciciDataResponseForReversal();

        $this->setMozartMockResponse($mockedResponse);

        $this->ba->cronAuth();

        $eventTestDataKey = 'testPayoutReversedWebhookForSuccessfulMappingToReversal';

        $this->expectWebhookEventWithContents('payout.reversed', $eventTestDataKey);

        $eventTestDataKey1 = 'testTransactionCreatedWebhookForSuccessfulMappingToReversal';
        $data = & $this->testData['testTransactionCreatedWebhookForSuccessfulMappingToReversal'];
        $data['payload']['transaction']['entity']['source']['payout_id'] = 'pout_' . $payout->getId();

        $this->expectWebhookEventWithContents('transaction.created', $eventTestDataKey1);

        $testData = $this->testData['testIciciAccountStatementCase1'];

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();

        $transactorTypeArray = [
            'da_ext_credit',
            'da_ext_debit',
            'da_ext_payout_processed',
            'da_payout_processed_recon',
            'da_payout_reversed',
            'da_payout_reversed_recon',
        ];

        $commissionArray = [
            '',
            '',
            '590',
            '590',
            '590',
            '590',
        ];

        $taxArray = [
            '',
            '',
            '90',
            '90',
            '90',
            '90',
        ];

        for ($index = 0; $index<count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('test', $ledgerRequestPayload['mode']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals($commissionArray[$index], $ledgerRequestPayload['commission']);
            $this->assertEquals($taxArray[$index], $ledgerRequestPayload['tax']);
            $this->assertEquals($transactorTypeArray[$index], $ledgerRequestPayload['transactor_event']);
            $this->assertArrayNotHasKey('fee_accounting', $ledgerRequestPayload['additional_params']);
        }
    }

    public function testWebhookEventForIciciAccountStatementForSuccessfulMappingToReversalForFeePayoutWithLedgerShadow()
    {
        $this->fixtures->merchant->addFeatures([Features::DA_LEDGER_JOURNAL_WRITES]);

        $ledgerSnsPayloadArray = [];
        $this->mockLedgerSns(4, $ledgerSnsPayloadArray);

        $this->setupForIciciPayout(Channel::ICICI, 100, FundTransfer\Mode::NEFT);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XB7', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], ['status' => 'initiated',
            'utr'       => '023629961691',
            'purpose'   => 'rzp_fees'
        ]);

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
            Attempt\Status::FAILED);

        $payout = $this->getDbLastEntity('payout');

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals(Payout\Status::FAILED, $payout['status']);
        $this->assertEquals(FundTransfer\Mode::NEFT, $payout['mode']);
        $this->assertEquals(Attempt\Status::FAILED, $attempt['status']);
        $this->assertEquals(FundTransfer\Mode::NEFT, $attempt['mode']);

        $mockedResponse = $this->getIciciDataResponseForReversal();

        $this->setMozartMockResponse($mockedResponse);

        $this->ba->cronAuth();

        $eventTestDataKey = 'testPayoutReversedWebhookForSuccessfulMappingToReversal';

        $this->expectWebhookEventWithContents('payout.reversed', $eventTestDataKey);

        $eventTestDataKey1 = 'testTransactionCreatedWebhookForSuccessfulMappingToReversal';
        $data = & $this->testData['testTransactionCreatedWebhookForSuccessfulMappingToReversal'];
        $data['payload']['transaction']['entity']['source']['payout_id'] = 'pout_' . $payout->getId();

        $this->expectWebhookEventWithContents('transaction.created', $eventTestDataKey1);

        $testData = $this->testData['testIciciAccountStatementCase1'];

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();

        $transactorTypeArray = [
            'da_ext_credit',
            'da_ext_debit',
            'da_ext_fee_payout_processed',
            'da_fee_payout_reversed',
        ];

        $commissionArray = [
            '',
            '',
            '590',
            '590',
        ];

        $taxArray = [
            '',
            '',
            '90',
            '90',
        ];

        for ($index = 0; $index<count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('test', $ledgerRequestPayload['mode']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals($commissionArray[$index], $ledgerRequestPayload['commission']);
            $this->assertEquals($taxArray[$index], $ledgerRequestPayload['tax']);
            $this->assertEquals($transactorTypeArray[$index], $ledgerRequestPayload['transactor_event']);
            $this->assertArrayNotHasKey('fee_accounting', $ledgerRequestPayload['additional_params']);
        }
    }

    public function testIciciAccountStatementGatewayException()
    {
        $oldDateTime = Carbon::create(2021, 3, 27, 12, 0, 0, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $mockedResponse = $this->getIciciNoRecordsFoundGatewayExceptionResponse();

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

        $response = $this->makeRequestAndGetContent($request);

        $basdAfterTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNull($basdAfterTest[BasDetails\Entity::STATEMENT_CLOSING_BALANCE_CHANGE_AT]);

        Carbon::setTestNow();
    }

    public function testIciciAccountStatementFetchV2WithDuplicateRecords()
    {
        (new AdminService)->setConfigKeys([
                                              ConfigKey::ACCOUNT_STATEMENT_V2_FLOW => ['2224440041626905']]);

        (new AdminService)->setConfigKeys([
                                              ConfigKey::ICICI_ACCOUNT_STATEMENT_RECORDS_TO_FETCH_AT_ONCE => 2]);

        (new AdminService)->setConfigKeys([
                                              ConfigKey::ACCOUNT_STATEMENT_RECORDS_TO_SAVE_AT_ONCE => 2]);

        $this->fixtures->create('banking_account_statement',
                                [
                                    'type'                      => 'credit',
                                    'amount'                    => '1000000',
                                    'channel'                   => 'icici',
                                    'account_number'            => 2224440041626905,
                                    'bank_transaction_id'       => 'S71034864',
                                    'description'               =>  "MMT/IMPS/104910349740/Shippuden/Naruto",
                                    'balance'                   => 1000000,
                                    'transaction_date'          => 1613586600,
                                    'posted_date'               => 1613626140,
                                    'bank_serial_number'        => 'S71034864',
                                ]);

        $mockedResponse = $this->getIciciMalFormedDataResponse();

        $this->setMozartMockResponse($mockedResponse);

        $basdBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNull($basdBeforeTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        IciciBankingAccountStatementJob::dispatch('test', [
            'channel'           => Channel::ICICI,
            'account_number'    => 2224440041626905
        ]);

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
     * balance b
    t1 r1  c  a1        b+a1 = cb1      c=> credit
    t1 r2  c  a2        cb1+a2 = cb2

    lasttrid -     t1|posted_date| cb2
    bank response:
    r1 - cb2+a1 = a1+cb1+a2 = cb1 + (a1+a2)
    r2 - (cb2+a1) + a2 = cb1+a2+a1+a2  = cb1+a2 + (a1+a2)
    r3 - cb2+a2+a1+a3 = cb2+a3 + (a1+a2)

    dedup
    r1 - repeat - 0th index
    r2 - repeat - 1th index

    r1
    r2
    r3
    r4
    r5
     */
    public function testDedupLogicForIcici()
    {
        (new AdminService)->setConfigKeys([
                                              ConfigKey::ACCOUNT_STATEMENT_V2_FLOW => ['2224440041626905']]);

        (new AdminService)->setConfigKeys([
                                              ConfigKey::ICICI_ACCOUNT_STATEMENT_RECORDS_TO_FETCH_AT_ONCE => 2]);

        (new AdminService)->setConfigKeys([
                                              ConfigKey::ACCOUNT_STATEMENT_RECORDS_TO_SAVE_AT_ONCE => 2]);

        $mockedResponse = $this->getIciciDataResponse();

        unset($mockedResponse[F::DATA][F::RECORD][2]);
        $this->setMozartMockResponse($mockedResponse);

        $basdBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNull($basdBeforeTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        $testData = $this->testData['testIciciAccountStatementCase1'];

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->cronAuth();

        $response = $this->makeRequestAndGetContent($testData['request']);

        $basEntities = $this->getDbEntities(EntityConstants::BANKING_ACCOUNT_STATEMENT);

        $this->assertEquals(2, count($basEntities));

        $basBefore = $basEntities->toArray();

        $this->assertEquals(1000000, $basBefore[0][BasEntity::BALANCE]);
        $this->assertEquals(999900, $basBefore[1][BasEntity::BALANCE]);


        (new AdminService)->setConfigKeys([
                                              ConfigKey::ICICI_ACCOUNT_STATEMENT_RECORDS_TO_FETCH_AT_ONCE => 3]);

        (new AdminService)->setConfigKeys([
                                              ConfigKey::ACCOUNT_STATEMENT_RECORDS_TO_SAVE_AT_ONCE => 3]);


        $mockedResponse = $this->getIciciDataResponse();

        $mockedResponse[F::DATA][F::RECORD][0][F::BALANCE] = "19,999.00";
        $mockedResponse[F::DATA][F::RECORD][1][F::BALANCE] = "19,998.00";
        $mockedResponse[F::DATA][F::RECORD][2][F::BALANCE] = "19,997.00";

        $this->setMozartMockResponse($mockedResponse);

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->cronAuth();

        $this->startTest();

        $basEntities = $this->getDbEntities(EntityConstants::BANKING_ACCOUNT_STATEMENT);

        $basAfter = $basEntities->toArray();

        $this->assertEquals(1000000, $basAfter[0][BasEntity::BALANCE]);
        $this->assertEquals(999900, $basAfter[1][BasEntity::BALANCE]);
        $this->assertEquals(999800, $basAfter[2][BasEntity::BALANCE]);

        $this->assertEquals(3, count($basEntities));


    }

    public function testDedupLogicForIciciCaseWhenDifferenceResets()
    {
        (new AdminService)->setConfigKeys([
                                              ConfigKey::ACCOUNT_STATEMENT_V2_FLOW => ['2224440041626905']]);

        (new AdminService)->setConfigKeys([
                                              ConfigKey::ICICI_ACCOUNT_STATEMENT_RECORDS_TO_FETCH_AT_ONCE => 3]);

        (new AdminService)->setConfigKeys([
                                              ConfigKey::ACCOUNT_STATEMENT_RECORDS_TO_SAVE_AT_ONCE => 3]);

        $mockedResponse = $this->getIciciDataResponse();
        $mockedResponse[F::DATA][F::RECORD][2][F::TYPE] = 'CR';
        $mockedResponse[F::DATA][F::RECORD][2][F::BALANCE] = '10,000.00';

        $this->setMozartMockResponse($mockedResponse);

        $basdBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNull($basdBeforeTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        $testData = $this->testData['testIciciAccountStatementCase1'];

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->cronAuth();

        $response = $this->makeRequestAndGetContent($testData['request']);

        $basEntities = $this->getDbEntities(EntityConstants::BANKING_ACCOUNT_STATEMENT);

        $this->assertEquals(3, count($basEntities));

        $basBefore = $basEntities->toArray();

        $this->assertEquals(1000000, $basBefore[0][BasEntity::BALANCE]);
        $this->assertEquals(999900, $basBefore[1][BasEntity::BALANCE]);
        $this->assertEquals(1000000, $basBefore[2][BasEntity::BALANCE]);


        (new AdminService)->setConfigKeys([
                                              ConfigKey::ICICI_ACCOUNT_STATEMENT_RECORDS_TO_FETCH_AT_ONCE => 4]);

        (new AdminService)->setConfigKeys([
                                              ConfigKey::ACCOUNT_STATEMENT_RECORDS_TO_SAVE_AT_ONCE => 4]);


        $mockedResponse = $this->getIciciDataResponse();
        $mockedResponse[F::DATA][F::RECORD][3] = [
            "AMOUNT"        => "5.00",
            "BALANCE"       => "19,995.00",
            "CHEQUENO"      => [],
            "REMARKS"       => "INF/NEFT/023629961643/SBIN0050101/TestIcici/demon",
            "TRANSACTIONID" => "S86758817",
            "TXNDATE"       => "19-02-2021 04:29:56",
            "TYPE"          => "DR",
            "VALUEDATE"     => "19-02-2021",
        ];

        $mockedResponse[F::DATA][F::RECORD][0][F::BALANCE] = "20,000.00";
        $mockedResponse[F::DATA][F::RECORD][1][F::BALANCE] = "19,999.00";
        $mockedResponse[F::DATA][F::RECORD][2][F::TYPE] = 'CR';
        $mockedResponse[F::DATA][F::RECORD][2][F::BALANCE] = "20,000.00";

        $this->setMozartMockResponse($mockedResponse);

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->cronAuth();

        $this->startTest();

        $basEntities = $this->getDbEntities(EntityConstants::BANKING_ACCOUNT_STATEMENT);

        $basAfter = $basEntities->toArray();

        $this->assertEquals(1000000, $basAfter[0][BasEntity::BALANCE]);
        $this->assertEquals(999900, $basAfter[1][BasEntity::BALANCE]);
        $this->assertEquals(1000000, $basAfter[2][BasEntity::BALANCE]);
        $this->assertEquals(999500, $basAfter[3][BasEntity::BALANCE]);

        $this->assertEquals(4, count($basEntities));
    }

    public function testIciciAccountStatementFetchExistingAccounts()
    {
        $this->testData[__FUNCTION__] = $this->testData['testIciciAccountStatementCase1'];

        $mockedResponse = $this->getIciciDataResponseForExistingAccounts();

        $this->setMozartMockResponse($mockedResponse);

        $basdBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNull($basdBeforeTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        $this->ba->cronAuth();

        $this->startTest();

        $basEntities = $this->getDbEntities('banking_account_statement');

        $this->assertCount(2, $basEntities);

        $transactions = $mockedResponse[F::DATA][F::RECORD];

        unset($transactions[count($transactions) -1]);

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
}
