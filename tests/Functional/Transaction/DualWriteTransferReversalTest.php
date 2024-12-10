<?php

namespace RZP\Tests\Functional\Transaction;

use Mockery;
use RZP\Models\Ledger\ReverseShadow;
use RZP\Services\KafkaMessageProcessor;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\Settlement\SettlementTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\MocksRazorx;
use RZP\Tests\Traits\MocksSplitz;
use RZP\Tests\Traits\TestsWebhookEvents;

class DualWriteTransferReversalTest extends TestCase
{
    use MocksSplitz;
    use MocksRazorx;
    use PaymentTrait;
    use PartnerTrait;
    use SettlementTrait;
    use DbEntityFetchTrait;
    use TestsWebhookEvents;
    use ReverseShadow\ReverseShadowTrait;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function initialiseLedger($merchantBalance = 0, $feeCredits = 0, $amountCredits = 0, $fetchAccountRequests = 1)
    {
        $this->app['config']->set('applications.ledger.enabled', true);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $mockLedger->shouldReceive('fetchAccountsByEntitiesAndMerchantID')
            ->times($fetchAccountRequests)
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

        return $mockLedger;
    }

    private function getKafkaEventPayload($reversalId, $refundId)
    {
        return  [
            "data" => [
                "payload_api" => [
                    "id" => "72c608162bd30f0fea3c7793871f1cf0",
                    "reversal_and_refund_journal_ids" => [
                        "reversals" => [
                            [
                                "transfer_reversal_id" => $reversalId,
                                "transfer_reversal_journal_id" => "PUmFA4r0jcgLlV",
                                "refund_id" => $refundId,
                                "refund_journal_id" => "PUmFA4fzPQB7PZ"
                            ]
                        ],
                        "is_rearch_refund" => true
                    ],
                    "journals" => [
                        [
                            "id" => "PUmFA4fzPQB7PZ",
                            "created_at" => "1733681665",
                            "updated_at" => "1733681665",
                            "amount" => "100",
                            "base_amount" => "100",
                            "currency" => "INR",
                            "tenant" => "PG",
                            "transactor_id" => "rvrsl_" . $reversalId,
                            "transactor_event" => "transfer_reversal_processed",
                            "transaction_date" => "1733681665",
                            "ledger_entry" => [
                                [
                                    "id" => "PUmFA4jbVX2awW",
                                    "created_at" => "1733681665",
                                    "updated_at" => "1733681665",
                                    "merchant_id" => "10000000000001",
                                    "journal_id" => "PUmFA4fzPQB7PZ",
                                    "account_id" => "K9Ljoft9sie60J",
                                    "amount" => "100",
                                    "base_amount" => "100",
                                    "type" => "credit",
                                    "currency" => "INR",
                                    "balance" => "",
                                    "balance_updated" => false,
                                    "account_entities" => [
                                        "account_type" => ["payable"],
                                        "fund_account_type" => ["merchant_va_merchant"]
                                    ],
                                    "notes" => null
                                ],
                                [
                                    "id" => "PUmFA4jczdStJv",
                                    "created_at" => "1733681665",
                                    "updated_at" => "1733681665",
                                    "merchant_id" => "10000000000001",
                                    "journal_id" => "PUmFA4fzPQB7PZ",
                                    "account_id" => "PLU0ZD5LXOBrj8",
                                    "amount" => "100",
                                    "base_amount" => "100",
                                    "type" => "debit",
                                    "currency" => "INR",
                                    "balance" => "4421.000000",
                                    "balance_updated" => true,
                                    "account_entities" => [
                                        "account_type" => ["payable"],
                                        "fund_account_type" => ["merchant_balance"]
                                    ],
                                    "notes" => null
                                ]
                            ]
                        ],
                        [
                            "id" => "PUmFA4r0jcgLlV",
                            "created_at" => "1733681665",
                            "updated_at" => "1733681665",
                            "amount" => "100",
                            "base_amount" => "100",
                            "currency" => "INR",
                            "tenant" => "PG",
                            "transactor_id" => "rvrsl_" . $reversalId,
                            "transactor_event" => "transfer_reversal_processed",
                            "transaction_date" => "1733681664",
                            "ledger_entry" => [
                                [
                                    "id" => "PUmFA4uI7C62SX",
                                    "created_at" => "1733681665",
                                    "updated_at" => "1733681665",
                                    "merchant_id" => "10000000000000",
                                    "journal_id" => "PUmFA4r0jcgLlV",
                                    "account_id" => "K9Ljoft9sie60J",
                                    "amount" => "100",
                                    "base_amount" => "100",
                                    "type" => "debit",
                                    "currency" => "INR",
                                    "balance" => "",
                                    "balance_updated" => false,
                                    "account_entities" => [
                                        "account_type" => ["payable"],
                                        "fund_account_type" => ["merchant_va_merchant"]
                                    ],
                                    "notes" => null
                                ],
                                [
                                    "id" => "PUmFA4uJFgKoAV",
                                    "created_at" => "1733681665",
                                    "updated_at" => "1733681665",
                                    "merchant_id" => "10000000000000",
                                    "journal_id" => "PUmFA4r0jcgLlV",
                                    "account_id" => "PLTzaHuIkpLPdk",
                                    "amount" => "100",
                                    "base_amount" => "100",
                                    "type" => "credit",
                                    "currency" => "INR",
                                    "balance" => "995520.000000",
                                    "balance_updated" => true,
                                    "account_entities" => [
                                        "account_type" => ["payable"],
                                        "fund_account_type" => ["merchant_balance"]
                                    ],
                                    "notes" => null
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            "task_name" => "dual_write_transaction_for_api_events"
        ];
    }

    public function testTransferReversalAPILedgerDualWriteJob()
    {
        $this->fixtures->create('transfer:to_account', [
            'source_id' => '10000000000000',
            'to_id' => '10000000000001',
            'amount' => 100,
        ]);

        $transferId = $this->getDbLastEntity('transfer')['id'];

        $this->fixtures->reversal->createTransferReversalWithoutTxn($transferId, [
            'id'          => 'PUmF95hAhquk8v',
            'merchant_id' => '10000000000000',
            'entity_type' => 'transfer',
            'amount'      => 100,
            'fee'         => 0,
            'tax'         => 0,
        ]);

        $this->fixtures->refund->createFromTransferPaymentWithoutTxn([
            'payment' => $this->getDbLastPayment(),
            'id'      => 'PUmF9vJcN9RhxY',
        ]);

        $payload = $this->getKafkaEventPayload('PUmF95hAhquk8v', 'PUmF9vJcN9RhxY');

        (new KafkaMessageProcessor)->process(KafkaMessageProcessor::STAGE_TEST_API_LEDGER_DUAL_WRITE_EVENTS, $payload, 'test');

        $reversalTxn = $this->getDbEntityById('transaction', 'PUmFA4r0jcgLlV');
        $this->assertNotNull($reversalTxn);
        $this->assertEquals('PUmF95hAhquk8v', $reversalTxn['entity_id']);
        $this->assertEquals(100, $reversalTxn['amount']);
        $this->assertEquals(0, $reversalTxn['fee']);
        $this->assertEquals(0, $reversalTxn['tax']);
        $this->assertEquals(0, $reversalTxn['debit']);
        $this->assertEquals(100, $reversalTxn['credit']);
        $this->assertNotNull($reversalTxn['balance']);
        $this->assertNotNull($reversalTxn['balance_id']);
        $this->assertNotNull($reversalTxn['settled_at']);

        $refundTxn = $this->getDbEntityById('transaction', 'PUmFA4fzPQB7PZ');
        $this->assertNotNull($refundTxn);
        $this->assertEquals('PUmF9vJcN9RhxY', $refundTxn['entity_id']);
        $this->assertEquals(100, $refundTxn['amount']);
        $this->assertEquals(0, $refundTxn['fee']);
        $this->assertEquals(0, $refundTxn['tax']);
        $this->assertEquals(100, $refundTxn['debit']);
        $this->assertEquals(0, $refundTxn['credit']);
        $this->assertNotNull($refundTxn['balance']);
        $this->assertNotNull($refundTxn['balance_id']);
        $this->assertNotNull($refundTxn['settled_at']);
    }
}
