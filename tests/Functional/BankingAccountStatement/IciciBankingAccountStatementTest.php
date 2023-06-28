<?php

namespace Functional\BankingAccountStatement;

use Mail;
use Queue;
use Mockery;
use Carbon\Carbon;
use RZP\Models\Payout;
use RZP\Error\ErrorCode;
use RZP\Services\Mozart;
use RZP\Constants\Table;
use RZP\Constants\Timezone;
use RZP\Models\FundTransfer;
use RZP\Error\PublicErrorCode;
use RZP\Models\Admin\ConfigKey;
use RZP\Constants\Mode as EnvMode;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\TestsMetrics;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\BankingAccount\Channel;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Exception\GatewayErrorException;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Jobs\BankingAccountStatementRecon;
use RZP\Constants\Entity as EntityConstants;
use RZP\Services\Mock\BankingAccountService;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\BankingAccount\Gateway\Fields;
use RZP\Jobs\BankingAccountStatementProcessor;
use RZP\Mail\Transaction\Payout as PayoutMail;
use Razorpay\Metrics\Manager as MetricManager;
use RZP\Models\BankingAccountStatement\Metric;
use RZP\Services\Mock\Mutex as MockMutexService;
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
use function Termwind\renderUsing;

class IciciBankingAccountStatementTest extends TestCase
{
    use PayoutTrait;
    use PaymentTrait;
    use TestsMetrics;
    use WorkflowTrait;
    use DbEntityFetchTrait;
    use TestsWebhookEvents;
    use TestsBusinessBanking;

    protected $fundAccount;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/IciciBankingAccountStatementTestData.php';

        parent::setUp();

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
                        "CHEQUENO"      => "",
                        "REMARKS"       => "MMT/IMPS/104910349740/Shippuden/Naruto",
                        "TRANSACTIONID" => "S71034864",
                        "TXNDATE"       => "18-02-2021 10:59:00",
                        "TYPE"          => "CR",
                        "VALUEDATE"     => "18-02-2021"
                    ],
                    [
                        "AMOUNT"        => "1.00",
                        "BALANCE"       => "9,999.00",
                        "CHEQUENO"      => "",
                        "REMARKS"       => "MMT/IMPS/104913832918/TESTICICI/SAMPLE/Hokage",
                        "TRANSACTIONID" => "S74203578",
                        "TXNDATE"       => "18-02-2021 13:20:51",
                        "TYPE"          => "DR",
                        "VALUEDATE"     => "18-02-2021"
                    ],
                    [
                        "AMOUNT"        => "1.00",
                        "BALANCE"       => "9,998.00",
                        "CHEQUENO"      => "",
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
                        "CHEQUENO"      => "",
                        "REMARKS"       => "NEFT-AXISCN0118376057-RAZORPAY PVT",
                        "TRANSACTIONID" => "S71034864",
                        "TXNDATE"       => "18-02-2021 10:59:00",
                        "TYPE"          => "CR",
                        "VALUEDATE"     => "18-02-2021"
                    ],
                    [
                        "AMOUNT"        => "1.00",
                        "BALANCE"       => "101.00",
                        "CHEQUENO"      => "",
                        "REMARKS"       => "INF/INFT/025802182571/Razorpay/Leaf",
                        "TRANSACTIONID" => "S74203578",
                        "TXNDATE"       => "18-02-2021 13:20:51",
                        "TYPE"          => "CR",
                        "VALUEDATE"     => "18-02-2021"
                    ],
                    [
                        "AMOUNT"        => "1.00",
                        "BALANCE"       => "102.00",
                        "CHEQUENO"      => "",
                        "REMARKS"       => "BIL/INFT/000270116851/TEST ICICI/Ishiki",
                        "TRANSACTIONID" => "S86758818",
                        "TXNDATE"       => "19-02-2021 04:29:52",
                        "TYPE"          => "CR",
                        "VALUEDATE"     => "19-02-2021",
                    ],
                    [
                        "AMOUNT"        => "1.00",
                        "BALANCE"       => "103.00",
                        "CHEQUENO"      => "",
                        "REMARKS"       => "RTGS-AUBLR12021123000584069-RZP PVT-212121133524511",
                        "TRANSACTIONID" => "S86234818",
                        "TXNDATE"       => "19-02-2021 04:29:52",
                        "TYPE"          => "CR",
                        "VALUEDATE"     => "19-02-2021",
                    ],
                    [
                        "AMOUNT"        => "1.00",
                        "BALANCE"       => "104.00",
                        "CHEQUENO"      => "",
                        "REMARKS"       => "UPI/115421282359/UPI/Jiraya/DBS Bank India",
                        "TRANSACTIONID" => "S86569818",
                        "TXNDATE"       => "19-02-2021 04:29:52",
                        "TYPE"          => "CR",
                        "VALUEDATE"     => "19-02-2021",
                    ],
                    [
                        "AMOUNT"        => "1.00",
                        "BALANCE"       => "103.00",
                        "CHEQUENO"      => "",
                        "REMARKS"       => "BIL/BPAY/000000043NVN/Testing",
                        "TRANSACTIONID" => "S86758150",
                        "TXNDATE"       => "19-02-2021 04:29:52",
                        "TYPE"          => "DR",
                        "VALUEDATE"     => "19-02-2021",
                    ],
                    [
                        "AMOUNT"        => "1.00",
                        "BALANCE"       => "102.00",
                        "CHEQUENO"      => "",
                        "REMARKS"       => "BIL/ONL/000286716570/Testing",
                        "TRANSACTIONID" => "S86758346",
                        "TXNDATE"       => "19-02-2021 04:29:52",
                        "TYPE"          => "DR",
                        "VALUEDATE"     => "19-02-2021",
                    ],
                    [
                        "AMOUNT"        => "1.00",
                        "BALANCE"       => "101.00",
                        "CHEQUENO"      => "",
                        "REMARKS"       => "UPI/115600327157/NA/Itachi/",
                        "TRANSACTIONID" => "S86758231",
                        "TXNDATE"       => "19-02-2021 04:29:52",
                        "TYPE"          => "DR",
                        "VALUEDATE"     => "19-02-2021",
                    ],
                    [
                        "AMOUNT"        => "1.00",
                        "BALANCE"       => "102.00",
                        "CHEQUENO"      => "",
                        "REMARKS"       => "MMT IMPS 212211671710 APIJQFQgSvI8qvN MR SATYANAR  SBIN0003281",
                        "TRANSACTIONID" => "S86768231",
                        "TXNDATE"       => "19-02-2021 04:29:52",
                        "TYPE"          => "CR",
                        "VALUEDATE"     => "19-02-2021",
                    ],
                    [
                        "AMOUNT"        => "1.00",
                        "BALANCE"       => "103.00",
                        "CHEQUENO"      => "",
                        "REMARKS"       => "IMPS 204813976491 19 02 2021 BOI",
                        "TRANSACTIONID" => "S86768232",
                        "TXNDATE"       => "19-02-2021 04:29:52",
                        "TYPE"          => "CR",
                        "VALUEDATE"     => "19-02-2021",
                    ],
                    [
                        "AMOUNT"        => "1.00",
                        "BALANCE"       => "104.00",
                        "CHEQUENO"      => "",
                        "REMARKS"       => "PRO-MMT/IMPS/313818380043/APIL",
                        "TRANSACTIONID" => "S86768233",
                        "TXNDATE"       => "19-02-2021 04:29:52",
                        "TYPE"          => "CR",
                        "VALUEDATE"     => "19-02-2021",
                    ],
                    [
                        "AMOUNT"        => "1.00",
                        "BALANCE"       => "105.00",
                        "CHEQUENO"      => "",
                        "REMARKS"       => "FT-MMT/IMPS/312113616259/APILkK97uHZ9EWx/DIPANKARSA/FSFB0000001",
                        "TRANSACTIONID" => "S86768234",
                        "TXNDATE"       => "19-02-2021 04:29:52",
                        "TYPE"          => "CR",
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
                        "CHEQUENO"      => "",
                        "REMARKS"       => "MMT/IMPS/104913832918/TESTICICI/SAMPLE/Hokage",
                        "TRANSACTIONID" => "S74203578",
                        "TXNDATE"       => "18-02-2021 13:20:51",
                        "TYPE"          => "DR",
                        "VALUEDATE"     => "18-02-2021"
                    ],
                    [
                        "AMOUNT"        => "1.00",
                        "BALANCE"       => "9,998.00",
                        "CHEQUENO"      => "",
                        "REMARKS"       => "INF/NEFT/023629961691/SBIN0050103/TestIcici/Boruto",
                        "TRANSACTIONID" => "S86758818",
                        "TXNDATE"       => "19-02-2021 04:29:52",
                        "TYPE"          => "DR",
                        "VALUEDATE"     => "19-02-2021",
                    ],
                    [
                        "AMOUNT"        => "1.00",
                        "BALANCE"       => "9,997.00",
                        "CHEQUENO"      => "",
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
                        "CHEQUENO"      => "",
                        "REMARKS"       => "MMT/IMPS/104910349740/Shippuden/Naruto",
                        "TRANSACTIONID" => "S71034864",
                        "TXNDATE"       => "18-02-2021 10:59:00",
                        "TYPE"          => "CR",
                        "VALUEDATE"     => "18-02-2021"
                    ],
                    [
                        "AMOUNT"        => "1.00",
                        "BALANCE"       => "19,999.00",
                        "CHEQUENO"      => "",
                        "REMARKS"       => "MMT/IMPS/104913832918/TESTICICI/SAMPLE/Hokage",
                        "TRANSACTIONID" => "S74203578",
                        "TXNDATE"       => "18-02-2021 13:20:51",
                        "TYPE"          => "DR",
                        "VALUEDATE"     => "18-02-2021"
                    ],
                    [
                        "AMOUNT"        => "1.00",
                        "BALANCE"       => "19,998.00",
                        "CHEQUENO"      => "",
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

    protected function getIciciDataResponseWithChequeNo()
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
                        "BALANCE"       => "9,800.00",
                        "CHEQUENO"      => "607",
                        "REMARKS"       => "Acc Debit Charge",
                        "TRANSACTIONID" => "S71034864",
                        "TXNDATE"       => "18-02-2021 10:59:00",
                        "TYPE"          => "DR",
                        "VALUEDATE"     => "18-02-2021"
                    ],
                    [
                        "AMOUNT"        => "100.00",
                        "BALANCE"       => "9,700.00",
                        "CHEQUENO"      => "",
                        "REMARKS"       => "Acc Debit Charge",
                        "TRANSACTIONID" => "S71034864",
                        "TXNDATE"       => "18-02-2021 10:59:00",
                        "TYPE"          => "DR",
                        "VALUEDATE"     => "18-02-2021"
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

    protected function getIciciDataResponseWithTempRecords()
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
                        "BALANCE"       => "9,800.00",
                        "CHEQUENO"      => "607",
                        "REMARKS"       => "UPI/212483983015/Payment to 2022/ashuviya16@okax/IDFC FIRST Bank/20220504181136",
                        "TRANSACTIONID" => "S71034864",
                        "TXNDATE"       => "18-02-2021 10:59:00",
                        "TYPE"          => "DR",
                        "VALUEDATE"     => "18-02-2021"
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
                        "CHEQUENO"      => "",
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
                        "CHEQUENO"      => "",
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
                        "CHEQUENO"      => "",
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
                        "CHEQUENO"      => "",
                        "REMARKS"       => "MMT/IMPS/104910349740/Shippuden/Naruto",
                        "TRANSACTIONID" => "S71034864",
                        "TXNDATE"       => "18-02-2021 10:59:00",
                        "TYPE"          => "CR",
                        "VALUEDATE"     => "18-02-2021"
                    ],
                    [
                        "AMOUNT"        => "1.00",
                        "BALANCE"       => "9,999.00",
                        "CHEQUENO"      => "",
                        "REMARKS"       => "INF/NEFT/023629961691/SBIN0050103/TestIcici/Boruto",
                        "TRANSACTIONID" => "S86758818",
                        "TXNDATE"       => "19-02-2021 04:29:52",
                        "TYPE"          => "DR",
                        "VALUEDATE"     => "19-02-2021",
                    ],
                    [
                        "AMOUNT"        => "1.00",
                        "BALANCE"       => "10,000.00",
                        "CHEQUENO"      => "",
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

    protected function getIciciDataResponseForFetchingMissingRecords()
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
                        "TXNDATE"       => "03-07-2022 20:51:21",
                        "TYPE"          => "CR",
                        "VALUEDATE"     => "03-07-2022"
                    ],
                    [
                        "AMOUNT"        => "1.00",
                        "BALANCE"       => "9,999.00",
                        "CHEQUENO"      => [],
                        "REMARKS"       => "INF/NEFT/023629961691/SBIN0050103/TestIcici/Boruto",
                        "TRANSACTIONID" => "S86758818",
                        "TXNDATE"       => "03-07-2022 20:51:23",
                        "TYPE"          => "DR",
                        "VALUEDATE"     => "03-07-2022",
                    ],
                    [
                        "AMOUNT"        => "1.00",
                        "BALANCE"       => "10,000.00",
                        "CHEQUENO"      => [],
                        "REMARKS"       => "NEFT-RETURN-23629961691DC-Naruto-ACCOUNT DOES NOT EXIST  R03",
                        "TRANSACTIONID" => "S87272425",
                        "TXNDATE"       => "03-07-2022 20:53:01",
                        "TYPE"          => "CR",
                        "VALUEDATE"     => "03-07-2022",
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
        $setDate = Carbon::create(2021, 2, 18, 16, 32, 0, Timezone::IST);

        Carbon::setTestNow($setDate);

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

        Carbon::setTestNow();
    }

    public function testIciciStatementFetchDispatchForIciciNonBankingHours()
    {
        Queue::fake();

        $setDate = Carbon::create(2016, 6, 17, 4, 32, 0, Timezone::IST);

        Carbon::setTestNow($setDate);

        (new AdminService)->setConfigKeys([ConfigKey::BANKING_ACCOUNT_STATEMENT_RATE_LIMIT => 3]);

        $this->fixtures->create('banking_account_statement_details', [
            BasDetails\Entity::ID                                  => 'xba00000000002',
            BasDetails\Entity::MERCHANT_ID                         => '10000000000012',
            BasDetails\Entity::BALANCE_ID                          => 'xba00000000002',
            BasDetails\Entity::ACCOUNT_NUMBER                      => '2323230041626902',
            BasDetails\Entity::CHANNEL                             => BasDetails\Channel::ICICI,
            BasDetails\Entity::STATUS                              => BasDetails\Status::ACTIVE,
            BasDetails\Entity::GATEWAY_BALANCE                     => 80,
            BasDetails\Entity::STATEMENT_CLOSING_BALANCE           => 80,
            BasDetails\Entity::GATEWAY_BALANCE_CHANGE_AT           => Carbon::now(Timezone::IST)->subMinutes(20)->getTimestamp(),
            BasDetails\Entity::STATEMENT_CLOSING_BALANCE_CHANGE_AT => Carbon::now(Timezone::IST)->subMinutes(25)->getTimestamp(),
            BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT           => Carbon::now(Timezone::IST)->subMinutes(25)->getTimestamp()
        ]);

        $this->ba->cronAuth();

        $this->startTest();

        $setDate = Carbon::create(2016, 6, 17, 22, 0, 0, Timezone::IST);

        Carbon::setTestNow($setDate);

        $this->startTest();

        $setDate = Carbon::create(2016, 6, 17, 6, 0, 0, Timezone::IST);

        Carbon::setTestNow($setDate);

        $this->startTest();

        $setDate = Carbon::create(2016, 6, 17, 0, 0, 0, Timezone::IST);

        Carbon::setTestNow($setDate);

        $this->startTest();

        Queue::assertPushed(IciciBankingAccountStatementJob::class, 0);

        Carbon::setTestNow();
    }

    public function testIciciStatementFetchDisableInJob()
    {
        $setDate = Carbon::create(2016, 6, 17, 4, 32, 0, Timezone::IST);

        Carbon::setTestNow($setDate);

        $this->app['rzp.mode'] = EnvMode::TEST;

        $mozartMock = Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $mockedResponse = $this->getIciciDataResponse();

        $mozartMock->shouldReceive('sendMozartRequest')
                   ->andReturnUsing(function($request) use ($mockedResponse) {
                       return $mockedResponse;
                   })->times(0);

        $this->app->instance('mozart', $mozartMock);

        $this->fixtures->create('balance', [
            'id'             => 'xbalance000001',
            'type'           => 'banking',
            'account_type'   => 'direct',
            'account_number' => '409102065472',
            'merchant_id'    => '10000000000012',
            'balance'        => 300000
        ]);

        $this->fixtures->create('banking_account_statement_details', [
            BasDetails\Entity::ID                                  => 'xba00000000002',
            BasDetails\Entity::MERCHANT_ID                         => '10000000000012',
            BasDetails\Entity::BALANCE_ID                          => 'xbalance000001',
            BasDetails\Entity::ACCOUNT_NUMBER                      => '409102065472',
            BasDetails\Entity::CHANNEL                             => BasDetails\Channel::ICICI,
            BasDetails\Entity::STATUS                              => BasDetails\Status::ACTIVE,
            BasDetails\Entity::GATEWAY_BALANCE                     => 80,
            BasDetails\Entity::STATEMENT_CLOSING_BALANCE           => 80,
            BasDetails\Entity::GATEWAY_BALANCE_CHANGE_AT           => Carbon::now(Timezone::IST)->subMinutes(20)->getTimestamp(),
            BasDetails\Entity::STATEMENT_CLOSING_BALANCE_CHANGE_AT => Carbon::now(Timezone::IST)->subMinutes(25)->getTimestamp(),
            BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT           => Carbon::now(Timezone::IST)->subMinutes(25)->getTimestamp()
        ]);

        $iciciStatementFetchJob = new IciciBankingAccountStatementJob(EnvMode::TEST, [
            'channel'        => 'icici',
            'account_number' => '409102065472'
        ]);

        $iciciStatementFetchJob->handle();

        $setDate = Carbon::create(2016, 6, 17, 22, 0, 0, Timezone::IST);

        Carbon::setTestNow($setDate);

        $iciciStatementFetchJob->handle();

        $setDate = Carbon::create(2016, 6, 17, 6, 0, 0, Timezone::IST);

        Carbon::setTestNow($setDate);

        $iciciStatementFetchJob->handle();

        $setDate = Carbon::create(2016, 6, 17, 0, 0, 0, Timezone::IST);

        Carbon::setTestNow($setDate);

        $iciciStatementFetchJob->handle();

        $setDate = Carbon::create(2016, 6, 17, 8, 0, 0, Timezone::IST);

        Carbon::setTestNow($setDate);

        $mozartMock->shouldReceive('sendMozartRequest')
                   ->andReturnUsing(function($request) use ($mockedResponse) {
                       return $mockedResponse;
                   })->times(1);

        $this->app->instance('mozart', $mozartMock);

        $iciciStatementFetchJob->handle();

        Carbon::setTestNow();
    }

    public function testIciciStatementFetchDisableInReconJob()
    {
        $setDate = Carbon::create(2016, 6, 17, 4, 32, 0, Timezone::IST);

        Carbon::setTestNow($setDate);

        $this->app['rzp.mode'] = EnvMode::TEST;

        $mozartMock = Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $mockedResponse = $this->getIciciDataResponse();

        $mozartMock->shouldReceive('sendMozartRequest')
                   ->andReturnUsing(function($request) use ($mockedResponse) {
                       return $mockedResponse;
                   })->times(0);

        $this->app->instance('mozart', $mozartMock);

        $this->fixtures->create('balance', [
            'id'             => 'xbalance000001',
            'type'           => 'banking',
            'account_type'   => 'direct',
            'account_number' => '409102065472',
            'merchant_id'    => '10000000000012',
            'balance'        => 300000
        ]);

        $this->fixtures->create('banking_account_statement_details', [
            BasDetails\Entity::ID                                  => 'xba00000000002',
            BasDetails\Entity::MERCHANT_ID                         => '10000000000012',
            BasDetails\Entity::BALANCE_ID                          => 'xbalance000001',
            BasDetails\Entity::ACCOUNT_NUMBER                      => '409102065472',
            BasDetails\Entity::CHANNEL                             => BasDetails\Channel::ICICI,
            BasDetails\Entity::STATUS                              => BasDetails\Status::ACTIVE,
            BasDetails\Entity::GATEWAY_BALANCE                     => 80,
            BasDetails\Entity::STATEMENT_CLOSING_BALANCE           => 80,
            BasDetails\Entity::GATEWAY_BALANCE_CHANGE_AT           => Carbon::now(Timezone::IST)->subMinutes(20)->getTimestamp(),
            BasDetails\Entity::STATEMENT_CLOSING_BALANCE_CHANGE_AT => Carbon::now(Timezone::IST)->subMinutes(25)->getTimestamp(),
            BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT           => Carbon::now(Timezone::IST)->subMinutes(25)->getTimestamp()
        ]);

        $iciciStatementFetchJob = new BankingAccountStatementRecon(EnvMode::TEST, [
            'channel'           => 'icici',
            'account_number'    => '409102065472',
            'from_date'         => Carbon::now(Timezone::IST)->startOfDay()->getTimestamp(),
            'to_date'           => Carbon::now(Timezone::IST)->endOfDay()->getTimestamp(),
            'expected_attempts' => 1,
            'pagination_key'    => null,
            'save_in_redis'     => false,
        ], true);

        $iciciStatementFetchJob->handle();

        $setDate = Carbon::create(2016, 6, 17, 22, 0, 0, Timezone::IST);

        Carbon::setTestNow($setDate);

        $iciciStatementFetchJob->handle();

        $setDate = Carbon::create(2016, 6, 17, 6, 0, 0, Timezone::IST);

        Carbon::setTestNow($setDate);

        $iciciStatementFetchJob->handle();

        // For midnight, we are allowing fetch due to redis key being enabled
        $setDate = Carbon::create(2016, 6, 17, 0, 0, 0, Timezone::IST);

        Carbon::setTestNow($setDate);

        (new AdminService)->setConfigKeys([ConfigKey::ICICI_STATEMENT_FETCH_ENABLE_IN_OFF_HOURS => true]);

        $mozartMock->shouldReceive('sendMozartRequest')
                   ->andReturnUsing(function($request) use ($mockedResponse) {
                       return $mockedResponse;
                   })->times(1);

        $this->app->instance('mozart', $mozartMock);

        $iciciStatementFetchJob->handle();

        $setDate = Carbon::create(2016, 6, 17, 8, 0, 0, Timezone::IST);

        Carbon::setTestNow($setDate);

        (new AdminService)->setConfigKeys([ConfigKey::ICICI_STATEMENT_FETCH_ENABLE_IN_OFF_HOURS => false]);

        $mozartMock->shouldReceive('sendMozartRequest')
                   ->andReturnUsing(function($request) use ($mockedResponse) {
                       return $mockedResponse;
                   })->times(1);

        $this->app->instance('mozart', $mozartMock);

        $iciciStatementFetchJob->handle();

        Carbon::setTestNow();
    }

    /**
     * Case where the response from ICICI is success
     */
    public function testIciciAccountStatementCase1($mockedResponse = null)
    {
        $setDate = Carbon::create(2021, 2, 18, 16, 32, 0, Timezone::IST);

        Carbon::setTestNow($setDate);

        if ($mockedResponse === null)
        {
            $mockedResponse = $this->getIciciDataResponse();

            $this->setMozartMockResponse($mockedResponse);
        }

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

        Carbon::setTestNow();
    }

    public function testFetchIciciMissingAccountStatement()
    {
        $setDate = Carbon::now(Timezone::IST)->firstOfMonth()->addDays(15)->addHours(10);

        Carbon::setTestNow($setDate);

        (new AdminService)->setConfigKeys([ConfigKey::ICICI_MISSING_STATEMENT_FETCH_MAX_RECORDS => 8000]);

        $metricsMock = $this->createMetricsMock();

        $boolMetricCaptured = false;

        $this->mockAndCaptureCountMetric(
            Metric::MISSING_STATEMENTS_FOUND,
            $metricsMock,
            $boolMetricCaptured,
            [
                'is_monitoring' => false,
                'channel'       => 'icici'
            ]
        );

        $this->fixtures->create('banking_account_statement',
                                [
                                    'type'                      => 'credit',
                                    'amount'                    => '1000000',
                                    'channel'                   => 'icici',
                                    'account_number'            => 2224440041626905,
                                    'bank_transaction_id'       => 'S71034864',
                                    'balance'                   => 1000000,
                                    'transaction_date'          => 1656786600,
                                    'posted_date'               => 1656861681,
                                    'bank_serial_number'        => 'S71034864',
                                    'description'               => 'MMT/IMPS/104910349740/Shippuden/Naruto',
                                    'balance_currency'          => 'INR',
                                ]);

        $this->fixtures->create('banking_account_statement',
                                [
                                    'type'                      => 'credit',
                                    'amount'                    => '100',
                                    'channel'                   => 'icici',
                                    'account_number'            => 2224440041626905,
                                    'bank_transaction_id'       => 'S87272425',
                                    'balance'                   => 1000100,
                                    'transaction_date'          => 1656786600,
                                    'posted_date'               => 1656861781,
                                    'bank_serial_number'        => 'S87272425',
                                    'description'               => 'NEFT-RETURN-23629961691DC-Naruto-ACCOUNT DOES NOT EXIST  R03',
                                    'balance_currency'          => 'INR',
                                ]);

        $mockedResponse = $this->getIciciDataResponseForFetchingMissingRecords();

        $this->setMozartMockResponse($mockedResponse);

        $this->ba->adminAuth();

        $this->startTest();

        $redisKey = 'missing_statements_10000000000000_2224440041626905';

        $merchantMissingStatementList = json_decode($this->app['redis']->get($redisKey), true);

        $this->app['redis']->del($redisKey);

        $basExpected = [
            BasEntity::ACCOUNT_NUMBER        => '2224440041626905',
            BasEntity::BANK_TRANSACTION_ID   => 'S86758818',
            BasEntity::TYPE                  => 'debit',
            BasEntity::AMOUNT                => 100,
            BasEntity::BALANCE               => 999900,
            BasEntity::POSTED_DATE           => 1656861683,
            BasEntity::TRANSACTION_DATE      => 1656786600,
            BasEntity::DESCRIPTION           => 'INF/NEFT/023629961691/SBIN0050103/TestIcici/Boruto',
            BasEntity::CHANNEL               => 'icici',
        ];

        $this->assertTrue($boolMetricCaptured);

        $this->assertArraySubset($basExpected, array_first($merchantMissingStatementList));

        Carbon::setTestNow();
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
        $setDate = Carbon::create(2021, 2, 18, 16, 32, 0, Timezone::IST);

        Carbon::setTestNow($setDate);

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
            BasEntity::TYPE                  => 'credit',
            BasEntity::AMOUNT                => 100,
            BasEntity::BALANCE               => 10500,
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
            TransactionEntity::DEBIT            => 0,
            TransactionEntity::CREDIT           => $externalActual[ExternalEntity::AMOUNT],
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
            '212211671710',
            '204813976491',
            '313818380043',
            '312113616259',
        ];

        $utrsActual = $this->getDbEntities(EntityConstants::BANKING_ACCOUNT_STATEMENT)
                           ->map(function($basEntity) {
                               return $basEntity->getUtr();
                           })->all();

        $this->assertEqualsCanonicalizing($utrsExpected, $utrsActual);

        Carbon::setTestNow($setDate);
    }

    /**
     * Case where icici return error.
     * assumption error not mapped at mozart
     */
    public function testIciciAccountStatementCase2()
    {
        $setDate = Carbon::now(Timezone::IST)->firstOfMonth()->addDays(15)->addHours(10);

        Carbon::setTestNow($setDate);

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

        Carbon::setTestNow();
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
                        "CHEQUENO"      => "",
                        "REMARKS"       => "MMT/IMPS/104910349740/Shippuden/Naruto",
                        "TRANSACTIONID" => "S71034864",
                        "TXNDATE"       => "18-02-2021 10:59:00",
                        "TYPE"          => "CR",
                        "VALUEDATE"     => "18-02-2021"
                    ],
                     [
                        "AMOUNT"        => "1.00",
                        "BALANCE"       => "9,999.00",
                        "CHEQUENO"      => "",
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
        $setDate = Carbon::create(2021, 2, 18, 16, 32, 0, Timezone::IST);

        Carbon::setTestNow($setDate);

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

        Carbon::setTestNow();
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
        $setDate = Carbon::now(Timezone::IST)->firstOfMonth()->addDays(15)->addHours(10);

        Carbon::setTestNow($setDate);

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

        Carbon::setTestNow();
    }

    public function testUtrMappingForIMPS()
    {
        $setDate = Carbon::now(Timezone::IST)->firstOfMonth()->addDays(15)->addHours(10);

        Carbon::setTestNow($setDate);

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

        Carbon::setTestNow($setDate);
    }

    // asserting external credit and payout events to ledger
    public function testUtrMappingForIMPSWithLedgerShadow()
    {
        $setDate = Carbon::now(Timezone::IST)->firstOfMonth()->addDays(15)->addHours(10);

        Carbon::setTestNow($setDate);

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

        Carbon::setTestNow($setDate);
    }

    public function testUtrMappingForFeePayoutWithLedgerShadow()
    {
        $setDate = Carbon::now(Timezone::IST)->firstOfMonth()->addDays(15)->addHours(10);

        Carbon::setTestNow($setDate);

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

        Carbon::setTestNow($setDate);
    }

    public function testUtrMappingForRTGS()
    {
        $setDate = Carbon::now(Timezone::IST)->firstOfMonth()->addDays(15)->addHours(10);

        Carbon::setTestNow($setDate);

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

        Carbon::setTestNow($setDate);
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
        $setDate = Carbon::create(2021, 2, 18, 16, 32, 0, Timezone::IST);

        Carbon::setTestNow($setDate);

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

        Carbon::setTestNow($setDate);
    }

    public function testWebhookEventForIciciAccountStatementForSuccessfulMappingToReversal()
    {
        $setDate = Carbon::now(Timezone::IST)->firstOfMonth()->addDays(15)->addHours(10);

        Carbon::setTestNow($setDate);

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

        Carbon::setTestNow($setDate);
    }

    // asserting external credit, external debit, payout and reversal events to ledger
    public function testWebhookEventForIciciAccountStatementForSuccessfulMappingToReversalWithLedgerShadow()
    {
        $setDate = Carbon::now(Timezone::IST)->firstOfMonth()->addDays(15)->addHours(10);

        Carbon::setTestNow($setDate);

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

        Carbon::setTestNow($setDate);
    }

    public function testWebhookEventForIciciAccountStatementForSuccessfulMappingToReversalForFeePayoutWithLedgerShadow()
    {
        $setDate = Carbon::now(Timezone::IST)->firstOfMonth()->addDays(15)->addHours(10);

        Carbon::setTestNow($setDate);

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

        Carbon::setTestNow($setDate);
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
        $oldDateTime = Carbon::create(2021, 3, 27, 12, 0, 0, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

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

        Carbon::setTestNow();
    }

    public function testIciciAccountStatementFetchV2DedupeLogicWithChequeNo()
    {
        $oldDateTime = Carbon::create(2021, 3, 27, 12, 0, 0, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->fixtures->create('banking_account_statement',
                                [
                                    'type'                      => 'debit',
                                    'amount'                    => '10000',
                                    'channel'                   => 'icici',
                                    'account_number'            => 2224440041626905,
                                    'bank_transaction_id'       => 'S71034864',
                                    'description'               => "Acc Debit Charge",
                                    'balance'                   => 990000,
                                    'transaction_date'          => 1613586600,
                                    'posted_date'               => 1613626140,
                                    'bank_serial_number'        => 'S71034864',
                                    'transaction_id'            => 'JzYlLzcIIQL45S',
                                    'entity_type'               => 'external',
                                    'entity_id'                 => 'JzYlLzMRxzj6po'
                                ]);

        $mockedResponse = $this->getIciciDataResponseWithChequeNo();

        $this->setMozartMockResponse($mockedResponse);

        $basd = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->fixtures->edit('balance', $basd[BasDetails\Entity::BALANCE_ID], ['balance' => 990000]);

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

        $this->assertEquals($txnActual[TransactionEntity::POSTED_AT], $basActual[BasEntity::POSTED_DATE]);

        $basExpected = [
            BasEntity::MERCHANT_ID           => $txnActual[TransactionEntity::MERCHANT_ID],
            BasEntity::BANK_TRANSACTION_ID   => trim($txn[F::TRANSACTION_ID]),
            BasEntity::TYPE                  => 'debit',
            BasEntity::AMOUNT                => 10000,
            BasEntity::BALANCE               => 980000,
            BasEntity::POSTED_DATE           => 1613626140,
            BasEntity::TRANSACTION_DATE      => 1613586600,
            BasEntity::DESCRIPTION           => trim($txn[F::REMARKS]),
            BasEntity::CHANNEL               => 'icici',
            BasEntity::ENTITY_ID             => $externalId,
            BasEntity::ENTITY_TYPE           => $externalActual[ExternalEntity::ENTITY],
            BasEntity::TRANSACTION_ID        => $txnActual[TransactionEntity::ID],
            BasEntity::BANK_SERIAL_NUMBER    => "607"
        ];

        $this->assertArraySubset($basExpected, $basActual, true);

        Carbon::setTestNow();
    }

    public function testIciciAccountStatementFetchV2ExcludingWronglyMarkedTempRecords()
    {
        $oldDateTime = Carbon::create(2021, 3, 27, 12, 0, 0, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->fixtures->create('banking_account_statement',
                                [
                                    'type'                      => 'debit',
                                    'amount'                    => '10000',
                                    'channel'                   => 'icici',
                                    'account_number'            => 2224440041626905,
                                    'bank_transaction_id'       => 'S71034864',
                                    'description'               => "Acc Debit Charge",
                                    'balance'                   => 990000,
                                    'transaction_date'          => 1613586600,
                                    'posted_date'               => 1613626140,
                                    'bank_serial_number'        => 'S71034864',
                                    'transaction_id'            => 'JzYlLzcIIQL45S',
                                    'entity_type'               => 'external',
                                    'entity_id'                 => 'JzYlLzMRxzj6po'
                                ]);

        $mockedResponse = $this->getIciciDataResponseWithTempRecords();

        $this->setMozartMockResponse($mockedResponse);

        $basd = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->fixtures->edit('balance', $basd[BasDetails\Entity::BALANCE_ID], ['balance' => 990000]);

        (new AdminService)->setConfigKeys([
                                              ConfigKey::ICICI_STATEMENT_FETCH_ALLOW_DESCRIPTION => [
                                                  'UPI/212483983015/Payment to 2022/ashuviya16@okax/IDFC FIRST Bank/20220504181136'
                                              ]
                                          ]);

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

        $this->assertEquals($txnActual[TransactionEntity::POSTED_AT], $basActual[BasEntity::POSTED_DATE]);

        $basExpected = [
            BasEntity::MERCHANT_ID           => $txnActual[TransactionEntity::MERCHANT_ID],
            BasEntity::BANK_TRANSACTION_ID   => trim($txn[F::TRANSACTION_ID]),
            BasEntity::TYPE                  => 'debit',
            BasEntity::AMOUNT                => 10000,
            BasEntity::BALANCE               => 980000,
            BasEntity::POSTED_DATE           => 1613626140,
            BasEntity::TRANSACTION_DATE      => 1613586600,
            BasEntity::DESCRIPTION           => trim($txn[F::REMARKS]),
            BasEntity::CHANNEL               => 'icici',
            BasEntity::ENTITY_ID             => $externalId,
            BasEntity::ENTITY_TYPE           => $externalActual[ExternalEntity::ENTITY],
            BasEntity::TRANSACTION_ID        => $txnActual[TransactionEntity::ID],
            BasEntity::BANK_SERIAL_NUMBER    => "607"
        ];

        $this->assertArraySubset($basExpected, $basActual, true);

        Carbon::setTestNow();
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
        $setDate = Carbon::create(2021, 2, 18, 16, 32, 0, Timezone::IST);

        Carbon::setTestNow($setDate);

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

        Carbon::setTestNow($setDate);
    }

    public function testDedupLogicForIciciCaseWhenDifferenceResets()
    {
        $setDate = Carbon::create(2021, 2, 18, 16, 32, 0, Timezone::IST);

        Carbon::setTestNow($setDate);

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
            "CHEQUENO"      => "",
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

        Carbon::setTestNow();
    }

    public function testIciciAccountStatementFetchExistingAccounts()
    {
        $setDate = Carbon::create(2021, 2, 18, 16, 32, 0, Timezone::IST);

        Carbon::setTestNow($setDate);

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

        Carbon::setTestNow();
    }

    public function testLasttridValueWhenBalanceForLastTransactionIsInSingleDigit()
    {
        $setDate = Carbon::create(2021, 2, 18, 16, 32, 0, Timezone::IST);

        Carbon::setTestNow($setDate);

        (new AdminService)->setConfigKeys(
            [
                ConfigKey::ICICI_STATEMENT_FETCH_RATE_LIMIT    => 1,
                ConfigKey::ICICI_STATEMENT_FETCH_ATTEMPT_LIMIT => 1,
                ConfigKey::ICICI_ENABLE_RATE_LIMIT_FLOW        => 0
            ]);

        $mockedResponse = [
            "data" => [
                "ACCOUNTNO" => "2224440041626905",
                "AGGR_ID"   => "RZP1234",
                "CORP_ID"   => "RAZORPAY",
                "RESPONSE"  => "SUCCESS",
                "Record"    => [
                    [
                        "AMOUNT"        => "0.08",
                        "BALANCE"       => "0.08",
                        "CHEQUENO"      => "",
                        "REMARKS"       => "MMT/IMPS/104910349740/Shippuden/Naruto",
                        "TRANSACTIONID" => "S71034864",
                        "TXNDATE"       => "18-02-2021 10:59:00",
                        "TYPE"          => "CR",
                        "VALUEDATE"     => "18-02-2021"
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

        $this->setMozartMockResponse($mockedResponse);

        $this->ba->cronAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/process/icici',
            'content' => [],
        ];

        // first run
        $this->makeRequestAndGetContent($request);

        $mockedResponse = [
            "data" => [
                "ACCOUNTNO" => "2224440041626905",
                "AGGR_ID"   => "RZP1234",
                "CORP_ID"   => "RAZORPAY",
                "RESPONSE"  => "SUCCESS",
                "Record"    => [
                    [
                        "AMOUNT"        => "1.00",
                        "BALANCE"       => "1.08",
                        "CHEQUENO"      => "",
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

        $request = [
            'method'  => 'POST',
            'url'     => '/banking_account_statement/process/icici',
            'content' => [],
        ];

        $mockMozart = \Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();
        $this->app->instance('mozart', $mockMozart);

        $req = "";
        $mockMozart->shouldReceive('sendRawRequest')
                   ->withArgs(
                       function($request) use(& $req) {
                           $req = $request;
                       })
                   ->andReturn($mockedResponse);

        // second run
        $this->makeRequestAndGetContent($request);

        // extract lasttrid from request sent to mozart
        $lasttrid = json_decode($req['content'])->entities->last_transaction->lasttrid;

        //assert the balance that is sent in lasttrid to mozart
        $this->assertEquals('1|S71034864|18-02-2021 00:00:00|INR|.08|18-02-2021 10:59:00', $lasttrid);

        Carbon::setTestNow();
    }


    public function testInsertIciciMissingAccountStatement()
    {
        $oldDateTime = Carbon::create(2022, 8, 1, 12, 0, 0, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->testIciciAccountStatementCase1();

        $this->fixtures->merchant->addFeatures([Features::DA_LEDGER_JOURNAL_WRITES]);

        $ledgerSnsPayloadArray = [];

        $this->mockLedgerSns(1, $ledgerSnsPayloadArray);

        $missingStatementsBeforeInsertion = [
            [
                'type'                => 'credit',
                'amount'              => '100',
                'currency'            => 'INR',
                'channel'             => 'icici',
                'account_number'      => '2224440041626905',
                'bank_transaction_id' => 'S71034964',
                'balance'             => 1000100,
                'transaction_date'    => 1613586600,
                'posted_date'         => 1613627140,
                'bank_serial_number'  => 'S71034964',
                'description'         => 'INF/NEFT/023629961691/SBIN0050103/TestIcici/Boruto',
                'balance_currency'    => 'INR',
            ],
            [
                'type'                => 'debit',
                'amount'              => '100',
                'currency'            => 'INR',
                'channel'             => 'icici',
                'account_number'      => '2224440041626905',
                'bank_transaction_id' => 'S71034965',
                'balance'             => 1000000,
                'transaction_date'    => 1613586600,
                'posted_date'         => 1613627145,
                'bank_serial_number'  => 'S71034965',
                'description'         => 'INF/NEFT/023629961692/SBIN0050103/TestIcici/Boruto',
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
                'bank_transaction_id' => 'S71034864'
        ])[0];

        $initialStatement2 = $this->getDbEntities('banking_account_statement', [
            'account_number'      => '2224440041626905',
            'bank_transaction_id' => 'S74203578'
        ])[0];

        $initialStatement3 = $this->getDbEntities('banking_account_statement', [
            'account_number'      => '2224440041626905',
            'bank_transaction_id' => 'S86758818'
        ])[0];

        $mockedResponse = $this->getIciciDataResponse();

        $this->app['rzp.mode'] = EnvMode::TEST;

        $mozartMock = Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $mozartMock->shouldReceive('sendRawRequest')
                   ->andReturnUsing(
                       function(array $request) use ($mockedResponse) {
                           return json_encode($mockedResponse);
                       }
                   )->times(0);

        $this->app->instance('mozart', $mozartMock);

        $this->ba->adminAuth();

        $this->startTest();

        $merchantMissingStatementList = json_decode($this->app['redis']->get($redisKey), true);

        $this->app['redis']->del($redisKey);

        $this->assertArraySubset($missingStatementsBeforeInsertion[1], $merchantMissingStatementList[0]);

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);

        $this->assertCount($initialCount + 1, $basEntries);

        $basDetails = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $finalStatementClosingBalance = $basDetails[BasDetails\Entity::STATEMENT_CLOSING_BALANCE];

        $this->assertEquals($initialStatementClosingBalance + 100, $finalStatementClosingBalance);

        $finalStatement1 = $this->getDbEntities('banking_account_statement', [
            'account_number'      => '2224440041626905',
            'bank_transaction_id' => 'S71034864'
        ])[0];

        $finalStatement2 = $this->getDbEntities('banking_account_statement', [
            'account_number'      => '2224440041626905',
            'bank_transaction_id' => 'S74203578'
        ])[0];

        $finalStatement3 = $this->getDbEntities('banking_account_statement', [
            'account_number'      => '2224440041626905',
            'bank_transaction_id' => 'S86758818'
        ])[0];

        $insertedStatement = $this->getDbEntities('banking_account_statement', [
            'account_number'      => '2224440041626905',
            'bank_transaction_id' => 'S71034964'
        ])[0];

        $externalEntries = $this->getDbEntities('external', ['banking_account_statement_id' => $insertedStatement[BasEntity::ID]])[0];

        $this->assertEquals($initialStatement1->getBalance(), $finalStatement1->getBalance());

        $this->assertEquals($initialStatement2->getBalance() + 100, $finalStatement2->getBalance());

        $this->assertEquals($initialStatement3[BasEntity::BALANCE] + 100, $finalStatement3[BasEntity::BALANCE]);

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

    public function testViewIciciMissingAccountStatementsFromRedis()
    {
        $this->fixtures->merchant->addFeatures([Features::DA_LEDGER_JOURNAL_WRITES]);

        $ledgerSnsPayloadArray = [];

        $this->mockLedgerSns(0, $ledgerSnsPayloadArray);

        $statement = [
            'type'                      => 'credit',
            'amount'                    => '100',
            'currency'                  => 'INR',
            'channel'                   => 'icici',
            'account_number'            => '2224440041626905',
            'bank_transaction_id'       => 'S71034964',
            'balance'                   => 1000100,
            'transaction_date'          => 1613586600,
            'posted_date'               => 1613627140,
            'bank_serial_number'        => 'S71034964',
            'description'               => 'INF/NEFT/023629961691/SBIN0050103/TestIcici/Boruto',
            'balance_currency'          => 'INR',
        ];

        $redisKey = 'missing_statements_10000000000000_2224440041626905';

        $this->app['redis']->set($redisKey, json_encode([$statement]));

        $this->ba->adminAuth();

        $response = $this->startTest();

        $this->app['redis']->del($redisKey);

        $this->assertEquals(1, $response['number_of_missing_statements']);

        $this->assertEquals(json_encode([$statement]), $response['missing_statements']);
    }

    public function testIciciInsertMissingAccountStatementWhileInProgress()
    {
        $missingStatement = [
            [
                'type'                => 'credit',
                'amount'              => '100',
                'currency'            => 'INR',
                'channel'             => 'icici',
                'account_number'      => '2224440041626915',
                'bank_transaction_id' => 'S71034964',
                'balance'             => 1000100,
                'transaction_date'    => 1613586600,
                'posted_date'         => 1613627140,
                'bank_serial_number'  => 'S71034964',
                'description'         => 'INF/NEFT/023629961691/SBIN0050103/TestIcici/Boruto',
                'balance_currency'    => 'INR',
            ]
        ];

        $redisKey = 'missing_statements_10000000000000_2224440041626915';

        $this->app['redis']->set($redisKey, json_encode($missingStatement));

        $mockMutex = new MockMutexService($this->app);

        $this->app->instance('api.mutex', $mockMutex);

        $mutex = $this->app['api.mutex'];

        $basDetails = $this->fixtures->create('banking_account_statement_details', [
            'account_number' => '2224440041626915',
            'channel' => BasDetails\Channel::ICICI
        ]);

        $this->ba->adminAuth();

        $testData = $this->testData['testInsertIciciMissingAccountStatement'];

        $testData['request']['content']['account_number'] = '2224440041626915';

        $testData['response'] = [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Something went wrong, please try again after sometime.',
                ],
            ],
            'status_code' => 400,
        ];

        $testData['exception'] = [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ANOTHER_BANKING_ACCOUNT_STATEMENT_FETCH_IN_PROGRESS,
        ];

        $mutex->acquireAndRelease(
            'banking_account_statement_fetch_2224440041626915_icici',
            function() use ($testData) {
                $this->startTest($testData);
            },
            300);

        $basDetails->reload();

        $this->app['redis']->del($redisKey);

        $this->assertEquals(BasDetails\Status::ACTIVE, $basDetails->getStatus());
    }

    public function testDryRunInsertMissingAccountStatement()
    {
        $setDate = Carbon::create(2021, 2, 18, 16, 32, 0, Timezone::IST);

        Carbon::setTestNow($setDate);

        $this->testIciciAccountStatementCase1();

        $this->fixtures->merchant->addFeatures([Features::DA_LEDGER_JOURNAL_WRITES]);

        $ledgerSnsPayloadArray = [];

        $this->mockLedgerSns(0, $ledgerSnsPayloadArray);

        (new AdminService)->setConfigKeys(
            [
                ConfigKey::RETRY_COUNT_FOR_ID_GENERATION => 100
            ]);

        $missingStatements = [
            [
                'type'                => 'credit',
                'amount'              => '100',
                'currency'            => 'INR',
                'channel'             => 'icici',
                'account_number'      => '2224440041626905',
                'bank_transaction_id' => 'S71034964',
                'balance'             => 1000100,
                'transaction_date'    => 1613586600,
                'posted_date'         => 1613627140,
                'bank_serial_number'  => 'S71034964',
                'description'         => 'INF/NEFT/023629961691/SBIN0050103/TestIcici/Boruto',
                'balance_currency'    => 'INR',
            ]
        ];

        $redisKey = 'missing_statements_10000000000000_2224440041626905';

        $this->app['redis']->set($redisKey, json_encode($missingStatements));

        $initialBasEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);

        $initialCount = count($initialBasEntries);

        $initialGroupedBasEntities = $initialBasEntries->groupBy('bank_transaction_id')->toArray();

        $initialBasDetails = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $initialStatementClosingBalance = $initialBasDetails[BasDetails\Entity::STATEMENT_CLOSING_BALANCE];

        $initialStatement1 = $initialGroupedBasEntities['S71034864'];

        $initialStatement2 = $initialGroupedBasEntities['S74203578'];

        $initialStatement3 = $initialGroupedBasEntities['S86758818'];

        $this->ba->adminAuth();

        $this->startTest();

        $merchantMissingStatementList = json_decode($this->app['redis']->get($redisKey), true);

        $this->assertCount(1, $merchantMissingStatementList);

        $this->app['redis']->del($redisKey);

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);

        $groupedBasEntities = $basEntries->groupBy('bank_transaction_id')->toArray();

        $basDetails = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $finalStatementClosingBalance = $basDetails[BasDetails\Entity::STATEMENT_CLOSING_BALANCE];

        $this->assertCount($initialCount , $basEntries);

        $this->assertEquals($initialStatementClosingBalance, $finalStatementClosingBalance);

        $finalStatement1 = $groupedBasEntities['S71034864'];

        $finalStatement2 = $groupedBasEntities['S74203578'];

        $finalStatement3 = $groupedBasEntities['S86758818'];

        $this->assertEquals($initialStatement1[0][BasEntity::BALANCE], $finalStatement1[0][BasEntity::BALANCE]);

        $this->assertEquals($initialStatement2[0][BasEntity::BALANCE], $finalStatement2[0][BasEntity::BALANCE]);

        $this->assertEquals($initialStatement3[0][BasEntity::BALANCE], $finalStatement3[0][BasEntity::BALANCE]);

        Carbon::setTestNow();
    }

    public function testICICIStatementFetchFor2FAMerchants()
    {
        $this->fixtures->merchant->addFeatures([Features::ICICI_2FA]);

        $this->testIciciAccountStatementCase1();
    }

    public function testICICIStatementShouldNotFetchForNon2FAMerchantsIfBlockIsEnabled()
    {
        $setDate = Carbon::create(2021, 2, 18, 16, 32, 0, Timezone::IST);

        Carbon::setTestNow($setDate);

        (new AdminService)->setConfigKeys([ConfigKey::RX_ICICI_BLOCK_NON_2FA_NON_BAAS_FOR_CA => true]);

        $mockedResponse = $this->getIciciDataResponse();

        // Mock mozart
        $this->app['rzp.mode'] = EnvMode::TEST;

        $mock = Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        // Assert that mozart call was not made
        $mock->shouldNotHaveBeenCalled([
            'sendRawRequest' => json_encode($mockedResponse)
        ]);

        $this->app->instance('mozart', $mock);

        $basdBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->assertNull($basdBeforeTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        $this->ba->cronAuth();

        $this->startTest();

        $basActual = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT, true);

        $basdAfterTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        // assert that basd and bas, both are null since the statement fetch was blocked
        $this->assertNull($basdAfterTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        $this->assertNull($basActual);

        Carbon::setTestNow();
    }

    public function testICICIStatementFetchForBaasMerchantsWhenCredentialsIsReturnedByBas()
    {
        (new AdminService)->setConfigKeys([ConfigKey::RX_ICICI_BLOCK_NON_2FA_NON_BAAS_FOR_CA => true]);

        $this->fixtures->merchant->addFeatures([Features::ICICI_BAAS]);

        // mock BAS
        $mock = Mockery::mock(BankingAccountService::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        $mock->shouldReceive('fetchBankingCredentials')
             ->andReturn([
                             \RZP\Models\BankingAccount\Gateway\Icici\Fields::CORP_ID   => 'RAZORPAY12345',
                             \RZP\Models\BankingAccount\Gateway\Icici\Fields::CORP_USER => 'USER12345',
                             \RZP\Models\BankingAccount\Gateway\Icici\Fields::URN       => 'URN12345',
                             Fields::CREDENTIALS                                        => [
                                 "AGGR_ID"           => "BAAS0123",
                                 "AGGR_NAME"         => "ACMECORP",
                                 "beneficiaryApikey" => "wfeg34t34t34t3r43t34GG"
                             ]
                         ]);

        $this->app->instance('banking_account_service', $mock);

        //mock Mozart
        $mozartServiceMock = Mockery::mock(Mozart::class, [$this->app])->makePartial();

        $partialMozartRequest = [
            F::SOURCE_ACCOUNT => [
                F::ACCOUNT_NUMBER => '2224440041626905',
                F::CREDENTIALS => [
                    //For BaaS merchants, all creds are to be used from BAS response
                    F::CORP_ID                  => 'RAZORPAY12345',
                    F::USER_ID                  => 'USER12345',
                    F::AGGR_ID                  => 'BAAS0123',
                    F::URN                      => 'URN12345',
                    F::ACCOUNT_STATEMENT_APIKEY => 'wfeg34t34t34t3r43t34GG',
                ]
            ],
            F::MERCHANT_ID => '10000000000000',
        ];

        $mozartResponse = $this->getIciciDataResponse();

        $mozartServiceMock->shouldReceive('sendMozartRequest')
                   ->withArgs(function($namespace, $gateway, $action, $input) use ($partialMozartRequest)
                   {
                       $this->assertArraySelectiveEquals($partialMozartRequest, $input);

                       return true;

                   })->andReturn($mozartResponse);

        $this->app->instance('mozart', $mozartServiceMock);

        $this->testIciciAccountStatementCase1($mozartResponse);
    }

    public function testICICIStatementShouldNotFetchForBaasMerchantsWhenCredentialsIsNotReturnedByBas()
    {
        $setDate = Carbon::create(2021, 2, 18, 16, 32, 0, Timezone::IST);

        Carbon::setTestNow($setDate);

        (new AdminService)->setConfigKeys([ConfigKey::RX_ICICI_BLOCK_NON_2FA_NON_BAAS_FOR_CA => true]);

        $mockedResponse = $this->getIciciDataResponse();

        // Mock mozart
        $this->app['rzp.mode'] = EnvMode::TEST;

        $mock = Mockery::mock(Mozart::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();

        // Assert that mozart call was not made
        $mock->shouldNotHaveBeenCalled([
            'sendRawRequest' => json_encode($mockedResponse)
        ]);

        $this->app->instance('mozart', $mock);

        $basdBeforeTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        $this->fixtures->create('feature', [
            'name'        => Features::ICICI_BAAS,
            'entity_id'   => $basdBeforeTest["merchant_id"],
            'entity_type' => 'merchant',
        ]);

        $this->assertNull($basdBeforeTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        $this->ba->cronAuth();

        $this->startTest();

        $basActual = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT, true);

        $basdAfterTest = $this->getLastEntity(EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS, true);

        // assert that basd and bas, both are null since the statement fetch was blocked
        $this->assertNotNull($basdAfterTest[BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT]);

        $this->assertNull($basActual);

        Carbon::setTestNow();
    }

    public function testICICIStatementFetchForNon2FANonBaasMerchantsIfBlockIsDisabled()
    {
        (new AdminService)->setConfigKeys([ConfigKey::RX_ICICI_BLOCK_NON_2FA_NON_BAAS_FOR_CA => false]);

        //Only mocking Mozart as default mock for BAS is enough for non-BaaS flow
        $mozartServiceMock = Mockery::mock(Mozart::class, [$this->app])->makePartial();

        $aggrId = $this->app['config']['banking_account']['icici']['aggr_id'];
        $accountStatementApiKey = $this->app['config']['banking_account']['icici']['beneficiary_api_key'];

        $partialMozartRequest = [
            F::SOURCE_ACCOUNT => [
                F::ACCOUNT_NUMBER => '2224440041626905',
                F::CREDENTIALS => [
                    //First three creds are from the method fetchBankingCredentials() in Mock/BankingAccountService.php
                    F::CORP_ID                  => 'RAZORPAY12345',
                    F::USER_ID                  => 'USER12345',
                    F::URN                      => 'URN12345',
                    F::AGGR_ID                  => $aggrId,
                    F::ACCOUNT_STATEMENT_APIKEY => $accountStatementApiKey,
                ],
            ],
        ];

        $mozartResponse = $this->getIciciDataResponse();

        $mozartServiceMock->shouldReceive('sendMozartRequest')
                   ->withArgs(function($namespace, $gateway, $action, $input) use ($partialMozartRequest)
                   {
                       $this->assertArraySelectiveEquals($partialMozartRequest, $input);

                       $this->assertArrayNotHasKey(F::MERCHANT_ID, $input);

                       return true;

                   })->andReturn($mozartResponse);

        $this->app->instance('mozart', $mozartServiceMock);

        $this->testIciciAccountStatementCase1($mozartResponse);
    }

    public function testPayoutProcessedMailTriggeredViaStmtProcessingJobIcici()
    {
        (new AdminService)->setConfigKeys([ConfigKey::ACCOUNT_STATEMENT_V2_FLOW => ["2224440041626905"]]);

        Mail::fake();

        $this->setupForIciciPayout();

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
                                    'channel'                   => 'icici',
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
            BasDetails\Entity::CHANNEL                             => BasDetails\Channel::ICICI,
            BasDetails\Entity::STATUS                              => BasDetails\Status::ACTIVE,
            BasDetails\Entity::GATEWAY_BALANCE                     => -95,
            BasDetails\Entity::STATEMENT_CLOSING_BALANCE           => -95,
            BasDetails\Entity::GATEWAY_BALANCE_CHANGE_AT           => 123456,
            BasDetails\Entity::STATEMENT_CLOSING_BALANCE_CHANGE_AT => 123399,
            BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT           => 123460,
            BasDetails\Entity::ACCOUNT_TYPE                        => BasDetails\AccountType::DIRECT,
        ]);

        BankingAccountStatementProcessor::dispatch('test', [
            'channel'           => \RZP\Models\BankingAccount\Channel::ICICI,
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
    public function testPayoutProcessedMailTriggeredViaStmtProcessingJobIciciCase2()
    {
        (new AdminService)->setConfigKeys([ConfigKey::ACCOUNT_STATEMENT_V2_FLOW => ["2224440041626905"]]);

        Mail::fake();

        $this->setupForIciciPayout();

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
                                    'channel'                   => 'icici',
                                    'account_number'            => 2224440041626905,
                                    'bank_transaction_id'       => 'SDHDH',
                                    'balance'                   => -10095,
                                    'transaction_date'          => 1584987183,
                                    'posted_date'               => Carbon::now()->getTimestamp(),
                                ]);

        $this->fixtures->create('banking_account_statement_details', [
            BasDetails\Entity::ID                                  => 'xba00000000006',
            BasDetails\Entity::MERCHANT_ID                         => '10000000000000',
            BasDetails\Entity::BALANCE_ID                          => $this->bankingBalance->getId(),
            BasDetails\Entity::ACCOUNT_NUMBER                      => '2323230041626905',
            BasDetails\Entity::CHANNEL                             => BasDetails\Channel::ICICI,
            BasDetails\Entity::STATUS                              => BasDetails\Status::ACTIVE,
            BasDetails\Entity::GATEWAY_BALANCE                     => -10095,
            BasDetails\Entity::STATEMENT_CLOSING_BALANCE           => -10095,
            BasDetails\Entity::GATEWAY_BALANCE_CHANGE_AT           => 123456,
            BasDetails\Entity::STATEMENT_CLOSING_BALANCE_CHANGE_AT => 123399,
            BasDetails\Entity::LAST_STATEMENT_ATTEMPT_AT           => 123460,
            BasDetails\Entity::ACCOUNT_TYPE                        => BasDetails\AccountType::DIRECT,
        ]);

        BankingAccountStatementProcessor::dispatch('test', [
            'channel'           => \RZP\Models\BankingAccount\Channel::ICICI,
            'account_number'    => 2224440041626905
        ]);

        Mail::assertnotQueued(PayoutMail::class);

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

        Mail::assertQueued(PayoutMail::class);

        $this->assertEquals(Payout\Status::PROCESSED, $payout->getStatus());
    }

    public function testIciciAccountStatementTxnMappingUsingGatewayRefNo()
    {
        $setDate = Carbon::now(Timezone::IST)->firstOfMonth()->addDays(15)->addHours(10);

        Carbon::setTestNow($setDate);

        $this->mockLedgerSns(0);

        $channel = Channel::ICICI;

        $this->setupForIciciPayout($channel, 10000);

        $payout = $this->getDbLastEntity('payout');

        $this->assertEquals(590, $payout['fees']);
        $this->assertEquals(90, $payout['tax']);
        $this->assertEquals('Bbg7cl6t6I3XB7', $payout['pricing_rule_id']);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->fixtures->edit('payout', $payout['id'], [
            'status'       => 'initiated',
            'utr'          => '307612641235'
        ]);

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->edit('fund_transfer_attempt', $attempt['id'], [
            'cms_ref_no'     => 'S5',
            'utr'            => '307612641235',
            'gateway_ref_no' => 'APILSCQOSeJ8123'
        ]);

        $this->fixtures->edit('balance', $payout['balance_id'], ['balance' => 50000]);

        // set ICICI account statement mock data
        $mockedResponse = [
            "data"              => [
                "ACCOUNTNO" => "2224440041626905",
                "AGGR_ID"   => "RZP1234",
                "CORP_ID"   => "RAZORPAY",
                "RESPONSE"  => "SUCCESS",
                "Record"    => [
                    [
                        "AMOUNT"        => "100",
                        "BALANCE"       => "50000",
                        "CHEQUENO"      => "",
                        "REMARKS"       => "MMT/IMPS/307612641236/APILSCQOSeJ8123/TEST/SBIN0070663", // utr here does not match payout utr
                        "TRANSACTIONID" => "S71034864",
                        "TXNDATE"       => "18-02-2021 10:59:00",
                        "TYPE"          => "DR",
                        "VALUEDATE"     => "18-02-2021"
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

        $this->setMozartMockResponse($mockedResponse);

        $this->ba->cronAuth();
        $this->startTest();

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $payout = $this->getDbLastEntity('payout');

        // assert that payout is linked with debit BAS using gateway ref num
        $this->assertEquals(EntityConstants::PAYOUT, $basEntries[0]['entity_type']);
        $this->assertEquals($payout['id'], $basEntries[0]['entity_id']);
        $this->assertEquals($payout['transaction_id'], $basEntries[0]['transaction_id']);
        $this->assertEquals(Payout\Mode::IMPS, $payout[Payout\Entity::MODE]);

        // changing record to simulate a credit for reversed txn
        $txn = $mockedResponse['data']['Record'][0];
        $txn['BALANCE'] = "50100";
        $txn['TYPE'] = "CR";
        $txn['TRANSACTIONID'] = "S71034865";
        $mockedResponse['data']['Record'][0] = $txn;

        $this->setMozartMockResponse($mockedResponse);

        $this->ba->cronAuth();
        $this->startTest();

        $basEntries = $this->getDbEntities('banking_account_statement', ['account_number' => '2224440041626905']);
        $payout = $this->getDbLastEntity('payout');
        $reversal = $this->getDbLastEntity('reversal');

        // assert that payout is reversed and credit BAS is linked to reversal using gateway ref num
        $this->assertEquals(EntityConstants::PAYOUT, $basEntries[0]['entity_type']);
        $this->assertEquals($payout['id'], $basEntries[0]['entity_id']);
        $this->assertEquals(Payout\Status::REVERSED, $payout[Payout\Entity::STATUS]);
        $this->assertEquals(Payout\Mode::IMPS, $payout[Payout\Entity::MODE]);
        $this->assertEquals(TransactionEntity::DEBIT, $basEntries[0]['type']);

        $this->assertEquals(EntityConstants::REVERSAL, $basEntries[1]['entity_type']);
        $this->assertEquals($reversal['id'], $basEntries[1]['entity_id']);
        $this->assertEquals($reversal['transaction_id'], $basEntries[1]['transaction_id']);
        $this->assertEquals(TransactionEntity::CREDIT, $basEntries[1]['type']);
        $this->assertEquals($payout['id'], $reversal['entity_id']);

        Carbon::setTestNow();
    }

    public function testIciciMissingAccountStatementDetection()
    {
        $setDate = Carbon::now(Timezone::IST)->firstOfMonth()->addDays(15)->addHours(10);

        Carbon::setTestNow($setDate);

        $this->setMockRazorxTreatment([RazorxTreatment::BAS_FETCH_RE_ARCH => 'on']);

        $basDetails = $this->getDbEntity('banking_account_statement_details', ['account_number' => 2224440041626905]);

        $this->fixtures->edit('banking_account_statement_details', $basDetails->getId(), [
            'created_at' => 1652812200
        ]);

        $this->fixtures->create('banking_account_statement',
            [
                'type'                      => 'credit',
                'amount'                    => '1000000',
                'channel'                   => 'icici',
                'account_number'            => 2224440041626905,
                'bank_transaction_id'       => 'S71034864',
                'balance'                   => 1000000,
                'transaction_date'          => 1656786600,
                'posted_date'               => 1656861681,
                'bank_serial_number'        => 'S71034864',
                'description'               => 'MMT/IMPS/104910349740/Shippuden/Naruto',
                'balance_currency'          => 'INR',
            ]);

        $latestBAS = $this->fixtures->create('banking_account_statement',
            [
                'type'                      => 'credit',
                'amount'                    => '100',
                'channel'                   => 'icici',
                'account_number'            => 2224440041626905,
                'bank_transaction_id'       => 'S87272425',
                'balance'                   => 1000100,
                'transaction_date'          => 1656786600,
                'posted_date'               => 1656861781,
                'bank_serial_number'        => 'S87272425',
                'description'               => 'NEFT-RETURN-23629961691DC-Naruto-ACCOUNT DOES NOT EXIST  R03',
                'balance_currency'          => 'INR',
            ]);

        $mockedResponse = $this->getIciciDataResponseForFetchingMissingRecords();

        $this->setMozartMockResponse($mockedResponse);

        $this->app['rzp.mode'] = EnvMode::TEST;

        $BASCoreMock = Mockery::mock('RZP\Models\BankingAccountStatement\Core');

        $this->ba->adminAuth();

        // Add assertion for checking if fetch call was made in the end

        $this->startTest();

        $missingStatementDetectionConfig = (new AdminService)->getConfigKey(
            [
                'key' => ConfigKey::RX_CA_MISSING_STATEMENT_DETECTION_ICICI
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
                'mismatch_amount' => -100,
                'mismatch_type'   => "missing_debit",
                'analysed_bas_id' => $latestBAS->getId()
            ], $missingStatementDetectionConfig['2224440041626905']['mismatch_data'][0]);

        Carbon::setTestNow();
    }
}
