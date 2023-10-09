<?php

namespace RZP\Tests\Functional\Order\Transfers;

use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Models\EntityOrigin\Core;
use RZP\Services\KafkaMessageProcessor;
use RZP\Tests\Traits\MocksSplitz;
use RZP\Tests\Unit\Mock\BasicAuth;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Account\Entity;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Tests\Functional\Partner\Constants;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class OrderTransferLedgerTest extends TestCase
{
    use MocksSplitz;
    use PaymentTrait;
    use PartnerTrait;
    use DbEntityFetchTrait;
    use TestsWebhookEvents;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/OrderTransferTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->initializeTestSetup();
    }

    protected function initializeTestSetup()
    {
        $this->fixtures->merchant->addFeatures(['marketplace']);

        $account = $this->fixtures->create('merchant:marketplace_account');

        $merchantDetailAttributes =  [
            'merchant_id'   => $account['id'],
            'contact_email' => $account['email'],
            'activation_status' => "activated",
            'bank_details_verification_status'  => 'verified'
        ];

        $this->fixtures->create('merchant_detail:associate_merchant', $merchantDetailAttributes);

        $this->linkedAccountId = $account['id'];
    }

    public function initialiseLedger($merchantBalance = 1000000, $feeCredits, $amountCredits, $noOfFetchAccountRequests = 1)
    {
        $this->app['config']->set('applications.ledger.enabled', true);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $mockLedger->shouldReceive('fetchAccountsByEntitiesAndMerchantID')
            ->times($noOfFetchAccountRequests)
            ->andReturn([
                    "body" => [
                        "accounts"  => [
                            [
                                "id"                => "sampleAccountID",
                                "name"              => "test name",
                                "status"            => "ACTIVATED",
                                "balance"           => sprintf('%s.000000', $merchantBalance),
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
                                "balance"           => sprintf('%s.000000', $feeCredits),
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
                                "balance"           => sprintf('%s.000000', $amountCredits),
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
    }

    public function testCreateOrderTransfers()
    {
        $order = $this->startTest();

        return $order;
    }

    public function testCronProcessPendingOrderTransfersInReverseShadowSync()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow'],'10000000000001' );

        $order = $this->fixtures->create('order', ['status' => 'paid']);

        $payment = $this->fixtures->create('payment:captured', ['order_id' => $order['id']]);

        $dummyTransferData = [
            'id'                 => '10000000123456',
            'source_id'          => $order['id'],
            'source_type'        => 'order',
            'status'             => 'pending',
            'settlement_status'  => NULL,
            'to_id'              => 10000000000001,
            'to_type'            => 'merchant',
            'amount'             => 50000,
            'currency'           => 'INR',
            'amount_reversed'    => 0,
            'created_at'         => Carbon::now()->addHours(-5)->getTimestamp(),
            'updated_at'         => Carbon::now()->addHours(-4)->getTimestamp()
        ];

        $this->fixtures->transfer->create($dummyTransferData);

        $transfer = $this->getLastEntity('transfer', true);
        $this->assertEquals('pending', $transfer['status']);

        $this->initialiseLedger(1000000, 0, 0);

        $data = $this->testData[__FUNCTION__];

        $this->ba->cronAuth();

        $this->runRequestResponseFlow($data);

        // payment txn exists
        $paymentTxn = $this->getDbLastEntity('transaction');
        $this->assertEquals('payment', $paymentTxn['type']);
        $this->assertEquals($payment['id'], $paymentTxn['entity_id']);

        // transfer entity exists
        $transfer = $this->getDbLastEntity('transfer');
        $this->assertNotNull($transfer);
        $this->assertEquals($dummyTransferData['id'], $transfer['id']);
        $this->assertEquals('pending', $transfer['status']);
        $this->assertNull($transfer['processed_at']);

        $transferId = $transfer['id'];

        // dummy payment entity exists
        $transferPayment = $this->getDbLastEntity('payment');
        $this->assertNotNull($transferPayment);
        $this->assertEquals($transferId, $transferPayment['transfer_id']);
        $this->assertEquals('transfer', $transferPayment['method']);

        // transfer txn and dummy payment txn not created
        $this->assertNull($transfer['transaction_id']);
        $this->assertNull($transferPayment['transaction_id']);

        $transferTxn = $this->getDbEntity('transaction', ['type' => 'transfer', 'entity_id' => 'trf_'.$transferId]);
        $this->assertNull($transferTxn);

        $transferPaymentTxn = $this->getDbEntity('transaction', ['type' => 'payment', 'entity_id' => 'pay_%s'. $transferPayment['id']]);
        $this->assertNull($transferPaymentTxn);

        $expectedLedgerOutboxEntry = [
            "currency" => "INR",
            "transactor_event" =>  "transfer_processed",
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG",
            "journals" =>[
                [
                    "merchant_id"=>"10000000000000",
                    "currency"=>"INR",
                    "money_params" => [
                        "amount" => "50000",
                        "base_amount" => "50000",
                        "merchant_payable_amount" => "50000",
                        "merchant_balance_amount" => "50000",
                        "tax" => "0",
                        "transfer_commission" => "0",
                    ],
                    "additional_params"=>["entry_type"=>"debit"]
                ],
                [
                    "merchant_id"=>"10000000000001",
                    "currency"=>"INR",
                    "money_params" => [
                        "amount" => "50000",
                        "base_amount" => "50000",
                        "merchant_payable_amount" => "50000",
                        "merchant_balance_amount" => "50000",
                    ],
                    "additional_params"=>["entry_type"=>"credit"]
                ]
            ],
        ];

        // fetch transfer journal payload from ledger_outbox
        $ledgerOutboxEntity = $this->getDbLastEntity('ledger_outbox');
        $this->assertNotNull($ledgerOutboxEntity);
        $this->assertEquals( sprintf('trf_%s-transfer_processed', $transferId),$ledgerOutboxEntity['payload_name']);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);
        $actualLedgerOutboxEntry = json_decode($payload, true);
        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals('trf_'.$transferId, $actualLedgerOutboxEntry['transactor_id']);
        $this->assertNotNull($actualLedgerOutboxEntry['idempotency_key']);
    }

    public function testCronProcessPendingOrderTransfersInReverseShadowAsync()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow'],'10000000000001' );

        $order = $this->fixtures->create('order', ['status' => 'paid']);

        $payment = $this->fixtures->create('payment:captured', ['order_id' => $order['id']]);

        $dummyTransferData = [
            'id'                 => '10000000123456',
            'source_id'          => $order['id'],
            'source_type'        => 'order',
            'status'             => 'pending',
            'settlement_status'  => NULL,
            'to_id'              => 10000000000001,
            'to_type'            => 'merchant',
            'amount'             => 50000,
            'currency'           => 'INR',
            'amount_reversed'    => 0,
            'created_at'         => Carbon::now()->addHours(-5)->getTimestamp(),
            'updated_at'         => Carbon::now()->addHours(-4)->getTimestamp()
        ];

        $this->fixtures->transfer->create($dummyTransferData);

        $transfer = $this->getLastEntity('transfer', true);
        $this->assertEquals('pending', $transfer['status']);

        $this->initialiseLedger(1000000, 0, 0);

        $data = $this->testData[__FUNCTION__];

        $this->ba->cronAuth();

        $this->runRequestResponseFlow($data);

        // payment txn exists
        $paymentTxn = $this->getDbLastEntity('transaction');
        $this->assertEquals('payment', $paymentTxn['type']);
        $this->assertEquals($payment['id'], $paymentTxn['entity_id']);

        // transfer entity exists
        $transfer = $this->getDbLastEntity('transfer');
        $this->assertNotNull($transfer);
        $this->assertEquals($dummyTransferData['id'], $transfer['id']);
        $this->assertEquals('pending', $transfer['status']);
        $this->assertNull($transfer['processed_at']);

        $transferId = $transfer['id'];

        // dummy payment entity exists
        $transferPayment = $this->getDbLastEntity('payment');
        $this->assertNotNull($transferPayment);
        $this->assertEquals($transferId, $transferPayment['transfer_id']);
        $this->assertEquals('transfer', $transferPayment['method']);

        // transfer txn and dummy payment txn not created
        $this->assertNull($transfer['transaction_id']);
        $this->assertNull($transferPayment['transaction_id']);

        $transferTxn = $this->getDbEntity('transaction', ['type' => 'transfer', 'entity_id' => 'trf_'.$transferId]);
        $this->assertNull($transferTxn);

        $transferPaymentTxn = $this->getDbEntity('transaction', ['type' => 'payment', 'entity_id' => 'pay_%s'. $transferPayment['id']]);
        $this->assertNull($transferPaymentTxn);

        $expectedLedgerOutboxEntry = [
            "currency" => "INR",
            "transactor_event" =>  "transfer_processed",
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG",
            "journals" =>[
                [
                    "merchant_id"=>"10000000000000",
                    "currency"=>"INR",
                    "money_params" => [
                        "amount" => "50000",
                        "base_amount" => "50000",
                        "merchant_payable_amount" => "50000",
                        "merchant_balance_amount" => "50000",
                        "tax" => "0",
                        "transfer_commission" => "0",
                    ],
                    "additional_params"=>["entry_type"=>"debit"]
                ],
                [
                    "merchant_id"=>"10000000000001",
                    "currency"=>"INR",
                    "money_params" => [
                        "amount" => "50000",
                        "base_amount" => "50000",
                        "merchant_payable_amount" => "50000",
                        "merchant_balance_amount" => "50000",
                    ],
                    "additional_params"=>["entry_type"=>"credit"]
                ]
            ],
        ];

        // fetch transfer journal payload from ledger_outbox
        $ledgerOutboxEntity = $this->getDbLastEntity('ledger_outbox');
        $this->assertNotNull($ledgerOutboxEntity);
        $this->assertEquals( sprintf('trf_%s-transfer_processed', $transferId),$ledgerOutboxEntity['payload_name']);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);
        $actualLedgerOutboxEntry = json_decode($payload, true);
        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals('trf_'.$transferId, $actualLedgerOutboxEntry['transactor_id']);
        $this->assertNotNull($actualLedgerOutboxEntry['idempotency_key']);
    }

    private function getPaymentMerchantCapturedJournalResponsePayload($transactorId, $journalId)
    {
        return [
            "id"=> $journalId,
            "created_at"=> 1677466532,
            "updated_at"=> 1677466532,
            "amount"=> "2000",
            "base_amount"=> "2000",
            "currency"=> "INR",
            "tenant"=> "PG",
            "transactor_id"=> $transactorId,
            "transactor_event"=> "payment_merchant_captured",
            "transaction_date"=> 1677466530,
            "ledger_entry"=> [
                [
                    "id"=> "LLJMDzXXyjC93B",
                    "created_at"=> 1677466532,
                    "updated_at"=> 1677466532,
                    "merchant_id"=> "10000000000000",
                    "journal_id"=> "LLJMDzRZGnZhGU",
                    "account_id"=> "Jk3pWyD5WaSSPP",
                    "amount"=> "40",
                    "base_amount"=> "40",
                    "type"=> "credit",
                    "currency"=> "INR",
                    "balance"=> "8509.000000",
                    "balance_updated"=> true,
                    "account_entities"=> [
                        "account_type"=> [
                            "receivable"
                        ],
                        "fund_account_type"=> [
                            "rzp_commission"
                        ]
                    ]
                ],
                [
                    "id"=> "LLJMDzXYypsmcx",
                    "created_at"=> 1677466532,
                    "updated_at"=> 1677466532,
                    "merchant_id"=> "10000000000000",
                    "journal_id"=> "LLJMDzRZGnZhGU",
                    "account_id"=> "JjpZUAEYlvYbEG",
                    "amount"=> "0",
                    "base_amount"=> "0",
                    "type"=> "credit",
                    "currency"=> "INR",
                    "balance"=> "1284.000000",
                    "balance_updated"=> true,
                    "account_entities"=> [
                        "account_type"=> [
                            "payable"
                        ],
                        "fund_account_type"=> [
                            "rzp_gst"
                        ]
                    ]
                ],
                [
                    "id"=> "LLJMDzXZQZynxG",
                    "created_at"=> 1677466532,
                    "updated_at"=> 1677466532,
                    "merchant_id"=> "10000000000000",
                    "journal_id"=> "LLJMDzRZGnZhGU",
                    "account_id"=> "Jjpg2D3rgPjGWs",
                    "amount"=> "2000",
                    "base_amount"=> "2000",
                    "type"=> "debit",
                    "currency"=> "INR",
                    "balance"=> "23700.000000",
                    "balance_updated"=> true,
                    "account_entities"=> [
                        "account_type"=> [
                            "payable"
                        ],
                        "fund_account_type"=> [
                            "merchant_gmv"
                        ]
                    ]
                ],
                [
                    "id"=> "LLJMDzXZr1H4OB",
                    "created_at"=> 1677466532,
                    "updated_at"=> 1677466532,
                    "merchant_id"=> "10000000000000",
                    "journal_id"=> "LLJMDzRZGnZhGU",
                    "account_id"=> "JjpZUD9PmJeNPk",
                    "amount"=> "1960",
                    "base_amount"=> "1960",
                    "type"=> "credit",
                    "currency"=> "INR",
                    "balance"=> "468259.000000",
                    "balance_updated"=> true,
                    "account_entities"=> [
                        "account_type"=> [
                            "payable"
                        ],
                        "fund_account_type"=> [
                            "merchant_balance"
                        ]
                    ]
                ]
            ]
        ];
    }

    private function getKafkaEventPayload($journal, $request = null, $msg = "")
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

    public function capturePaymentProcessOrderTransfersInReverseShadow($order)
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order['id'];

        $payment = $this->doAuthAndCapturePayment($payment);

        $paymentId = $payment['id'];

        $entry = $this->getDbLastEntity('ledger_outbox');
        $this->assertNotNull( $entry);
        $this->assertEquals($paymentId.'-'.'payment_merchant_captured', $entry['payload_name']);

        $payload = base64_decode($entry['payload_serialized']);
        $actualOutboxEntry = json_decode($payload, true);
        $apiTxnId = $actualOutboxEntry['api_transaction_id'];
        $this->assertNotNull( $apiTxnId);

        $journal = $this->getPaymentMerchantCapturedJournalResponsePayload($paymentId, $apiTxnId);

        $journalId = $journal['id'];

        $kafkaEventPayload = $this->getKafkaEventPayload($journal);

        (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_PG_LEDGER_ACKNOWLEDGMENTS, $kafkaEventPayload, 'test');

        $txn = $this->getDbLastEntity('transaction');
        $this->assertTrue($txn->isBalanceUpdated());

        $ledgerOutboxEntity = $this->getTrashedDbEntity('ledger_outbox', ['payload_name' => $paymentId.'-'.'payment_merchant_captured']);
        $this->assertEquals($paymentId, 'pay_'.$txn['entity_id']);
        $this->assertEquals($journalId, $txn['id']);
        $this->assertEquals($ledgerOutboxEntity['is_deleted'], 1, 'outbox entry not soft deleted');

        return $payment;
    }

    public function testProcessOrderTransfersInReverseShadow()
    {
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow'],'10000000000001' );

        $this->initialiseLedger(1000000, 0, 0, 2);

        $order = $this->testCreateOrderTransfers();

        $payment = $this->capturePaymentProcessOrderTransfersInReverseShadow($order);

        // transfer entity exists
        $transfer = $this->getDbLastEntity('transfer');
        $this->assertNotNull($transfer);
        $this->assertEquals('pending', $transfer['status']);
        $this->assertNull($transfer['processed_at']);

        $transferId = $transfer['id'];

        // dummy payment entity exists
        $transferPayment = $this->getDbLastEntity('payment');
        $this->assertNotNull($transferPayment);
        $this->assertEquals($transferId, $transferPayment['transfer_id']);
        $this->assertEquals('transfer', $transferPayment['method']);

        // transfer txn and dummy payment txn not created
        $this->assertNull($transfer['transaction_id']);
        $this->assertNull($transferPayment['transaction_id']);

        $transferTxn = $this->getDbEntity('transaction', ['type' => 'transfer', 'entity_id' => 'trf_'.$transferId]);
        $this->assertNull($transferTxn);

        $transferPaymentTxn = $this->getDbEntity('transaction', ['type' => 'payment', 'entity_id' => 'pay_%s'. $transferPayment['id']]);
        $this->assertNull($transferPaymentTxn);

        $expectedLedgerOutboxEntry = [
            "currency" => "INR",
            "transactor_event" =>  "transfer_processed",
            "ledger_integration_mode" =>  "reverse-shadow",
            "tenant" => "PG",
            "journals" =>[
                [
                    "merchant_id"=>"10000000000000",
                    "currency"=>"INR",
                    "money_params" => [
                        "amount" => "50000",
                        "base_amount" => "50000",
                        "merchant_payable_amount" => "50000",
                        "merchant_balance_amount" => "50000",
                        "tax" => "0",
                        "transfer_commission" => "0",
                    ],
                    "additional_params"=>["entry_type"=>"debit"]
                ],
                [
                    "merchant_id"=>"10000000000001",
                    "currency"=>"INR",
                    "money_params" => [
                        "amount" => "50000",
                        "base_amount" => "50000",
                        "merchant_payable_amount" => "50000",
                        "merchant_balance_amount" => "50000",
                    ],
                    "additional_params"=>["entry_type"=>"credit"]
                ]
            ],
        ];

        // fetch transfer journal payload from ledger_outbox
        $ledgerOutboxEntity = $this->getDbLastEntity('ledger_outbox');
        $this->assertNotNull($ledgerOutboxEntity);
        $this->assertEquals( sprintf('trf_%s-transfer_processed', $transferId),$ledgerOutboxEntity['payload_name']);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);
        $actualLedgerOutboxEntry = json_decode($payload, true);
        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals('trf_'.$transferId, $actualLedgerOutboxEntry['transactor_id']);
        $this->assertNotNull($actualLedgerOutboxEntry['idempotency_key']);
    }
}
