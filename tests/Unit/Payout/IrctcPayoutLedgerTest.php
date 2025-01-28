<?php

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\LedgerOutbox\Core as LedgerOutboxCore;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\LedgerOutbox\Constants as LedgerOutboxConstants;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Ledger\ReverseShadow\IRCTCPayout\Core as IRCTCPayoutCore;
use RZP\Models\Ledger\Constants as LedgerConstants;
use RZP\Models\Payout\Service as PayoutService;
use RZP\Models\Payout\Core as PayoutCore;
use RZP\Tests\Traits\MocksSplitz;
use Mockery;
use App;

class IrctcPayoutLedgerTest extends TestCase
{
    use DbEntityFetchTrait;
    use MocksSplitz;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testDataFilePath = __DIR__.'/IrctcPayoutLedgerTest.php';

        $this->app = App::getFacadeRoot();

        $this->app['rzp.mode'] = 'test';

    }
    public function testGetCLSBalance()
    {
        $merchantId = 'sampleMerchant';
        $fundAccountType = FeatureConstants::MERCHANT_BALANCE;

        // Mock the method
        $this->mockLedgerFetchAccountDetails($merchantId, $fundAccountType);

        // Call the method
        $result = (new IRCTCPayoutCore())->getCLSBalance($merchantId, $fundAccountType);

        // Assert the expected result
        $this->assertEquals(50000, $result);

    }

    /**
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function testInternalMerchantRDSReduceWithoutMerchantID() {

        $input  = [
            "currency"          =>      "INR",
            "transactor_id"     =>      "0000",
        ];

        $payoutService = new PayoutService();
        $this->expectException(\RZP\Exception\BadRequestValidationFailureException::class);
        $this->expectExceptionMessage("merchant_id is mandatory for the RDS balance update");

        $payoutService->internalMerchantRDSReduce($input);

    }

    public function testUpdateMerchantRDSReduceWithNonIRCTCMID() {

        $merchant = $this->fixtures->create('merchant', [
            'id' => '12345678901234',
        ]);

        $this->fixtures->merchant->addFeatures([FeatureConstants::PG_LEDGER_REVERSE_SHADOW], $merchant->getId());

        $input  = [
            "currency"          =>         "INR",
            "transactor_id"     =>         "0000",
            "merchant"          =>         $merchant->getId(),
        ];

        $payoutCore = new PayoutCore();

        $result = $payoutCore->updateMerchantRDSBalance($input, $merchant);

        $this->assertEquals("failure", $result["status"]);
        $this->assertEquals("Merchant is not IRCTC or Reverse Shadow flag not enabled", $result["message"]);
    }

    public function testUpdateMerchantRDSReduceWithIRCTCMID() {

        $merchant = $this->fixtures->create('merchant', [
            'id'                =>  'OGJDenfkpc6whP',
        ]);

        $this->fixtures->merchant->addFeatures([FeatureConstants::PG_LEDGER_REVERSE_SHADOW], $merchant->getId());

        $input  = [
            "currency"          =>         "INR",
            "transactor_id"     =>         "IwHCToefEWVgph",
            "merchant"          =>         $merchant->getId()
        ];

        $payoutCore = new PayoutCore();

        $this->mockLedgerFetchAccountDetails($merchant->getId(), LedgerConstants::RDS_BALANCE);

        $result = $payoutCore->updateMerchantRDSBalance($input, $merchant);

        $ledgerOutboxEntity = $this->getDbLastEntity('ledger_outbox');

        $this->assertEquals("success", $result["status"]);
        $this->assertEquals("RDS balance update request has been successfully initiated.", $result["message"]);

        $this->assertNotNull($ledgerOutboxEntity);
        $this->assertEquals(sprintf('rds_%s-irctc_rds_balance_updated', $input["transactor_id"]), $ledgerOutboxEntity['payload_name']);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);
        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry  = [
            "merchant_id" => "OGJDenfkpc6whP",
            "currency" => "INR",
            "money_params" => [
                "amount" => "50000"
            ],
            "transactor_id" => "rds_IwHCToefEWVgph",
            "transactor_event" => "irctc_rds_balance_updated",
            "ledger_integration_mode" => "reverse-shadow",
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);

        $this->assertEquals('rds_'.$input['transactor_id'], $actualLedgerOutboxEntry['transactor_id']);
        $this->assertNotNull($actualLedgerOutboxEntry['idempotency_key']);
    }

    public function testCreateLedgerJournalAsync() {

        $createdAt = Carbon::now(Timezone::IST)->subHours(11)->getTimestamp();

        $payout = $this->fixtures->create('payout', [
            'id'              =>    'IwHCToefEWVgph',
            'amount'          =>    '1200',
            'created_at'      =>    $createdAt
        ]);

        $merchant = $this->fixtures->create('merchant', [
            'id'              =>  'SMMWFIqabujap2',
        ]);

        $payout->merchant()->associate($merchant);

        $ledgerData = [
            'merchant_id'           => (string)$payout->merchant->getMerchantId(),
            'amount'                => $payout->getAmount(),
            'currency'              => 'INR',
            'transactor_id'         => LedgerConstants::PAYOUTS_PREFIX . $payout->getId(),
            'transactor_date'       => $payout->getCreatedAt(),
        ];

        (new IRCTCPayoutCore())->createLedgerJournalAsync($ledgerData, LedgerConstants::IRCTC_PAYOUT_PROCESSED);

        $ledgerOutboxEntity = $this->getDbLastEntity('ledger_outbox');
        $this->assertNotNull($ledgerOutboxEntity);
        $this->assertEquals(sprintf('%s-irctc_payout_processed', LedgerConstants::PAYOUTS_PREFIX . $payout->getId()), $ledgerOutboxEntity['payload_name']);

        $expectedLedgerOutboxEntry = [
            "merchant_id" => "SMMWFIqabujap2",
            "currency" => "INR",
            "money_params" => [
                "amount" => "1200"
            ],
            "transactor_id" => "pout_IwHCToefEWVgph",
            "transactor_event" => "irctc_payout_processed",
            "ledger_integration_mode" => "reverse-shadow",
            "tenant" => "PG"
        ];

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);
        $actualLedgerOutboxEntry = json_decode($payload, true);

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);

        $this->assertEquals(LedgerConstants::PAYOUTS_PREFIX . $payout->getId(), $actualLedgerOutboxEntry['transactor_id']);
        $this->assertNotNull($actualLedgerOutboxEntry['idempotency_key']);

    }

    public function testFundTransferCreationViaAcknowledgement()
    {

        $journal = $this->getJournal();

        $kafkaPayload = $this->getKafkaPayloadForAcknowledgement($journal);

        $this->fixtures->merchant->createAccount('OGJDenfkpc6whP');

        $this->fixtures->merchant->edit('OGJDenfkpc6whP', ['activated' => true, 'live' => true]);

        $balance = $this->fixtures->create('balance', [
            'account_number'            => '2224440041626904',
            'merchant_id'               => 'OGJDenfkpc6whP',
            'balance'                   => 400000
        ]);

        $this->fixtures->create('payout', [
            'id'                         =>    'PYjl5uc4ZT6yCe',
            'merchant_id'                =>    'OGJDenfkpc6whP',
            'amount'                     =>    5,
            'balance_id'                 =>    $balance['id'],
            'purpose'                    =>    'payout',
            'purpose_type'               =>    'settlement'
        ]);

        $this->mockSplitzExperiment(['response' => ['variant' => ['name' => 'enable']]]);

        $response = (new \RZP\Services\KafkaMessageProcessor())->process("outbox_jobs_api", $kafkaPayload, "test");

        $this->assertTrue($response);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals('OGJDenfkpc6whP', $attempt['merchant_id']);
        $this->assertEquals('settlement', $attempt['purpose']);

        $payout = $this->getDbEntityById('payout', 'PYjl5uc4ZT6yCe');

        $this->assertEquals($payout['transaction_id'], $journal['id']);

    }

    public function testFundTransferCreationViaAcknowledgementFailure()
    {

        $journal = $this->getJournal();

        $kafkaPayload = $this->getKafkaPayloadForAcknowledgement($journal, "", "validation_failure: validation_failure: BAD_REQUEST_VALIDATION_FAILURE");

        $this->fixtures->merchant->createAccount('OGJDenfkpc6whP');

        $this->fixtures->merchant->edit('OGJDenfkpc6whP', ['activated' => true, 'live' => true]);

        $balance = $this->fixtures->create('balance', [
            'account_number'            => '2224440041626904',
            'merchant_id'               => 'OGJDenfkpc6whP',
            'balance'                   => 400000
        ]);

        $this->fixtures->create('payout', [
            'id'                         =>    'PYjl5uc4ZT6yCe',
            'merchant_id'                =>    'OGJDenfkpc6whP',
            'amount'                     =>    5,
            'balance_id'                 =>    $balance['id'],
            'purpose'                    =>    'payout',
            'purpose_type'               =>    'settlement'
        ]);

        $this->mockSplitzExperiment(['response' => ['variant' => ['name' => 'enable']]]);

        $response = (new \RZP\Services\KafkaMessageProcessor())->process("outbox_jobs_api", $kafkaPayload, "test");

        $this->assertTrue($response);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertNull($attempt);

    }

    public function testCreateTransactionFromJournals()
    {
        $journal = $this->getJournal();
        $source = LedgerOutboxConstants::ACK_WORKER;

        $this->fixtures->merchant->createAccount('OGJDenfkpc6whP');

        $this->fixtures->merchant->edit('OGJDenfkpc6whP', ['activated' => true, 'live' => true]);

        $balance = $this->fixtures->create('balance', [
            'account_number'            => '2224440041626904',
            'merchant_id'               => 'OGJDenfkpc6whP',
            'balance'                   => 400000
        ]);

        $this->fixtures->create('payout', [
            'id'                         =>    'PYjl5uc4ZT6yCe',
            'merchant_id'                =>    'OGJDenfkpc6whP',
            'amount'                     =>    5,
            'balance_id'                 =>    $balance['id'],
            'purpose'                    =>    'payout',
            'purpose_type'               =>    'settlement'
        ]);

        $this->mockSplitzExperiment(['response' => ['variant' => ['name' => 'enable']]]);

        (new LedgerOutboxCore())->createTransactionFromJournal($journal, $source);

        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals('OGJDenfkpc6whP', $attempt['merchant_id']);
        $this->assertEquals('settlement', $attempt['purpose']);

        $payout = $this->getDbEntityById('payout', 'PYjl5uc4ZT6yCe');

        $this->assertEquals($payout['transaction_id'], $journal['id']);

    }

    public function getJournal(): array
    {
        return [
            "id" => "PidUskHgx5mDBB",
            "created_at" => 1736707625,
            "updated_at" => 1736707625,
            "amount" => 500,
            "base_amount" => 500,
            "currency" => "INR",
            "tenant" => "PG",
            "transactor_id" => "pout_PYjl5uc4ZT6yCe",
            "transactor_event" => "irctc_payout_initiated",
            "transaction_date" => 0,
            "ledger_entry" => [
                [
                    "id" => "PidUskNfbBsVkW",
                    "created_at" => 1736707625,
                    "updated_at" => 1736707625,
                    "merchant_id" => "OGJDenfkpc6whP",
                    "journal_id" => "PidUskHgx5mDBB",
                    "account_id" => "OIbE59Tiq0g781",
                    "amount" => 500,
                    "base_amount" => 500,
                    "type" => "debit",
                    "currency" => "INR",
                    "balance" => 3907680,
                    "balance_updated" => true,
                    "account_entities" => [
                        "account_type" => ["payable"],
                        "fund_account_type" => ["merchant_balance"]
                    ],
                    "notes" => null
                ],
                [
                    "id" => "PidUskNgUXxtIB",
                    "created_at" => 1736707625,
                    "updated_at" => 1736707625,
                    "merchant_id" => "OGJDenfkpc6whP",
                    "journal_id" => "PidUskHgx5mDBB",
                    "account_id" => "PNWG6WKL9fFumF",
                    "amount" => 500,
                    "base_amount" => 500,
                    "type" => "credit",
                    "currency" => "INR",
                    "balance" => 2953350,
                    "balance_updated" => true,
                    "account_entities" => [
                        "account_type" => ["payable"],
                        "fund_account_type" => ["control"]
                    ],
                    "notes" => null
                ]
            ]
        ];
    }

    public function mockLedgerFetchAccountDetails(string $merchantId, string $fundAccountType): void
    {
        $ledgerServiceMock = Mockery::mock('RZP\Services\Ledger', [$this->app])->makePartial();

        $ledgerServiceMock->shouldReceive('fetchAccountsByEntitiesAndMerchantID')
            ->times(1)
            ->with(
                [
                    FeatureConstants::MERCHANT_ID => $merchantId,
                    FeatureConstants::ENTITIES => [
                        [
                            FeatureConstants::ACCOUNT_TYPE => [FeatureConstants::PAYABLE],
                            FeatureConstants::FUND_ACCOUNT_TYPE => [$fundAccountType]
                        ]
                    ]
                ],
                Mockery::type('array'),
                true
            )
            ->andReturn([
                    "body" => [
                        "accounts"  => [
                            [
                                "id"                => "sampleAccountID",
                                "name"              => "test name",
                                "status"            => "ACTIVATED",
                                "balance"           => "50000.000000",
                                "min_balance"       => "0.000000",
                                "merchant_id"       => "sampleMerchant",
                                "created_at"        => "1634027277",
                                "updated_at"        => "1634027277",
                                "entities"          => [
                                    "account_type"      => ["payable"],
                                    "fund_account_type" => ["merchant_balance"]
                                ]
                            ],
                            [
                                "id"                => "sampleAccountID",
                                "name"              => "test name",
                                "status"            => "ACTIVATED",
                                "balance"           => "0.000000",
                                "min_balance"       => "0.000000",
                                "merchant_id"       => "sampleMerchant",
                                "created_at"        => "1634027277",
                                "updated_at"        => "1634027277",
                                "entities"          => [
                                    "account_type"      => ["payable"],
                                    "fund_account_type" => ["merchant_fee_credits"]
                                ]

                            ],
                            [
                                "id"                => "sampleAccountID",
                                "name"              => "test name",
                                "status"            => "ACTIVATED",
                                "balance"           => "500.000000",
                                "min_balance"       => "0.000000",
                                "merchant_id"       => "sampleMerchant",
                                "created_at"        => "1634027277",
                                "updated_at"        => "1634027277",
                                "entities"          => [
                                    "account_type"      => ["payable"],
                                    "fund_account_type" => ["reward"]
                                ]
                            ]
                        ]
                    ]
                ]
            );
        $this->app->instance('ledger', $ledgerServiceMock);
    }
    public function getKafkaPayloadForAcknowledgement($journal, $request = null, $msg = ""): array
    {
        $kafkaPayload = [
            "request"=> $request,
            "response"=> $journal,
            "error_response"=> [
                "msg"=> $msg
            ]
        ];

        $serializedPayload = base64_encode(json_encode($kafkaPayload));

        return  [
            "before"=> null,
            "after"=> [
                "id"=> "LLJMDzemcsroDp",
                "payload_serialized"=> $serializedPayload,
                "created_at"=> 1677466532,
                "updated_at"=> 1677466532
            ],
            "source"=> [
                "version"=> "2.1.1.Final",
                "connector"=> "postgresql",
                "name"=> "internal_db_stage_ledger_payments_test_outbox",
                "ts_ms"=> 1677466532793,
                "snapshot"=> "false",
                "db"=> "stage_ledger_pg_test",
                "sequence"=> "[\"60869735408\",\"60869737336\"]",
                "schema"=> "public",
                "table"=> "outbox_jobs_api_default",
                "txId"=> 242246764,
                "lsn"=> 60869737336,
                "xmin"=> null
            ],
            "op"=> "c",
            "ts_ms"=> 1677466533213,
            "transaction"=> null,
            "_record_source"=> "debezium_postgres"
        ];
    }
}
