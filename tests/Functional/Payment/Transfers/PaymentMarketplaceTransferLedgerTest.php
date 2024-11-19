<?php

namespace RZP\Tests\Functional\Payment\Transfers;

use Mockery;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Transfer\Core;
use RZP\Jobs\TransferProcess;
use RZP\Models\Ledger\ReverseShadow;
use RZP\Models\Payment;
use RZP\Services\KafkaMessageProcessor;
use RZP\Tests\Functional\Settlement\SettlementTrait;
use RZP\Tests\Traits\MocksSplitz;
use RZP\Tests\Traits\MocksRazorx;
use RZP\Tests\Functional\TestCase;
use RZP\Services\Settlements;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Services\Ledger;

class PaymentMarketplaceTransferLedgerTest extends TestCase
{
    use MocksSplitz;
    use MocksRazorx;
    use PaymentTrait;
    use PartnerTrait;
    use TransferTrait;
    use SettlementTrait;
    use DbEntityFetchTrait;
    use TestsWebhookEvents;
    use ReverseShadow\ReverseShadowTrait;

    const STANDARD_PRICING_PLAN_ID  = '1A0Fkd38fGZPVC';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PaymentMarketplaceTransferTestData.php';

        parent::setUp();

        $this->initializeTestSetup();

        $mockTransferCore = $this->createMock(Core::class);
        $mockTransferCore->method('createTransferTransactionsInReverseShadow')
            ->will($this->throwException(new \Exception("Some error occurred")));
    }

    protected function initializeTestSetup()
    {
        $this->payment = $this->doAuthAndCapturePayment();

        $account = $this->fixtures->create('merchant:marketplace_account');

        $merchantDetailAttributes =  [
            'merchant_id'   => $account['id'],
            'contact_email' => $account['email'],
            'activation_status' => "activated",
            'bank_details_verification_status'  => 'verified'
        ];

        $this->fixtures->create('merchant_detail:associate_merchant', $merchantDetailAttributes);

        $this->ba->privateAuth();
    }

    protected function mockSns($debitTxnPayload, $creditTxnPayload)
    {
        $sns = Mockery::mock('RZP\Services\Aws\Sns');

        $this->app->instance('sns', $sns);

        $sns->shouldReceive('publish')
            ->with(Mockery::type('string'), Mockery::type('string'))
            ->andReturnUsing(function ($input) use ($creditTxnPayload, $debitTxnPayload)
            {
                $json_decoded_input = json_decode($input, true);

                if ($json_decoded_input['id'] === $debitTxnPayload['id'])
                {
                    $this->assertEquals($debitTxnPayload['merchant_id'], $json_decoded_input['merchant_id']);
                    $this->assertEquals($debitTxnPayload['source_id'], $json_decoded_input['source_id']);
                    $this->assertEquals($debitTxnPayload['source_type'], $json_decoded_input['source_type']);
                    $this->assertEquals($debitTxnPayload['balance_type'], $json_decoded_input['balance_type']);
                    $this->assertEquals($debitTxnPayload['currency'], $json_decoded_input['currency']);
                    $this->assertEquals($debitTxnPayload['credit'], $json_decoded_input['credit']);
                    $this->assertEquals($debitTxnPayload['debit'], $json_decoded_input['debit']);
                    $this->assertEquals($debitTxnPayload['fee'], $json_decoded_input['fee']);
                    $this->assertEquals($debitTxnPayload['tax'], $json_decoded_input['tax']);
                    $this->assertEquals($debitTxnPayload['settled_by'], $json_decoded_input['settled_by']);
                    $this->assertEquals($debitTxnPayload['on_hold'], $json_decoded_input['on_hold']);
                    $this->assertEquals($debitTxnPayload['on_hold_reason'], $json_decoded_input['on_hold_reason']);
                    $this->assertEquals($debitTxnPayload['meta']['source_type'], $json_decoded_input['meta']['source_type']);
                    $this->assertEquals($debitTxnPayload['meta']['source_id'], $json_decoded_input['meta']['source_id']);
                    $this->assertEquals($debitTxnPayload['meta']['source_method'], $json_decoded_input['meta']['source_method']);
                    $this->assertEquals($debitTxnPayload['meta']['source_settled'], $json_decoded_input['meta']['source_settled']);
                    $this->assertEquals($debitTxnPayload['meta']['international'], $json_decoded_input['meta']['international']);
                }
                else if ($json_decoded_input['id'] === $creditTxnPayload['id'])
                {
                    $this->assertEquals($creditTxnPayload['merchant_id'], $json_decoded_input['merchant_id']);
                    $this->assertEquals($creditTxnPayload['source_id'], $json_decoded_input['source_id']);
                    $this->assertEquals($creditTxnPayload['source_type'], $json_decoded_input['source_type']);
                    $this->assertEquals($creditTxnPayload['balance_type'], $json_decoded_input['balance_type']);
                    $this->assertEquals($creditTxnPayload['currency'], $json_decoded_input['currency']);
                    $this->assertEquals($creditTxnPayload['credit'], $json_decoded_input['credit']);
                    $this->assertEquals($creditTxnPayload['debit'], $json_decoded_input['debit']);
                    $this->assertEquals($creditTxnPayload['fee'], $json_decoded_input['fee']);
                    $this->assertEquals($creditTxnPayload['tax'], $json_decoded_input['tax']);
                    $this->assertEquals($creditTxnPayload['settled_by'], $json_decoded_input['settled_by']);
                    $this->assertEquals($creditTxnPayload['on_hold'], $json_decoded_input['on_hold']);
                    $this->assertEquals($creditTxnPayload['on_hold_reason'], $json_decoded_input['on_hold_reason']);
                    $this->assertEquals($creditTxnPayload['meta']['method'], $json_decoded_input['meta']['method']);
                    $this->assertEquals($creditTxnPayload['meta']['international'], $json_decoded_input['meta']['international']);
                    $this->assertEquals($creditTxnPayload['meta']['origin_method'], $json_decoded_input['meta']['origin_method']);
                }

                return $input;
            });

        $this->app->instance('sns', $sns);
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

    /* OUTBOX PUSH test cases */

    public function testFullPaymentTransferReverseShadowOutboxPush()
    {
        $this->assertNotNull($this->payment);

        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow']);

        $this->initialiseLedger(1000000, 0, 0);

        $this->assertEquals(0, $this->getAccountBalance('10000000000001'));
        $this->fixtures->merchant->addFeatures([ 'pg_ledger_reverse_shadow'], '10000000000001');

        $oldMarketBalance = $this->getAccountBalance('10000000000000');
        $this->assertGreaterThanOrEqual($this->payment['amount'],$oldMarketBalance);

        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 50000,
            'currency'=> 'INR',
        ];

        $expected = [
            'count' => 1,
            'items' => [
                [
                    'source'          => $this->payment['id'],
                    'recipient'       => 'acc_10000000000001',
                    'amount'          => 50000,
                    'amount_reversed' => 0,
                    'status' => 'pending',
                ],
            ],
        ];

        $content = $this->transferPayment($this->payment['id'], $transfers);
        $this->assertNotNull($content);
        $this->assertArraySelectiveEquals($expected, $content);
        $this->assertCount(1, $content['items']);

        $transferResponse = $content['items'][0];
        $transferId = $transferResponse['id'];

        // payment txn exists
        $paymentTxn = $this->getDbLastEntity('transaction');
        $this->assertEquals('payment', $paymentTxn['type']);
        $this->assertEquals($this->payment['id'], sprintf('pay_%s',$paymentTxn['entity_id']));

        // transfer entity exists
        $transfer = $this->getDbLastEntity('transfer');
        $this->assertNotNull($transfer);
        $this->assertEquals($transferId, sprintf('trf_%s',$transfer['id']));

        // dummy payment entity exists
        $transferPayment = $this->getDbEntity('payment', ['transfer_id' => $transferId]);
        $this->assertNull($transferPayment);

        // transfer txn and dummy payment txn not created
        $this->assertNull($transfer['transaction_id']);
        $this->assertNull($transferPayment['transaction_id']);
        $transferTxn = $this->getDbEntity('transaction', ['type' => 'transfer', 'entity_id' => $transferId]);
        $this->assertNull($transferTxn);

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
        $this->assertEquals( sprintf('%s-transfer_processed', $transferId),$ledgerOutboxEntity['payload_name']);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);
        $actualLedgerOutboxEntry = json_decode($payload, true);
        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($transferId, $actualLedgerOutboxEntry['transactor_id']);
        $this->assertNotNull($actualLedgerOutboxEntry['idempotency_key']);

    }

    public function testPaymentTransferReverseShadowSyncOutboxPush()
    {
        $this->mockRazorxTreatmentV2(RazorxTreatment::ENABLE_TRANSFER_SYNC_LEDGER_OUTBOX_PUSH, 'on');

        $this->testFullPaymentTransferReverseShadowOutboxPush();
    }

    public function testTransferPaymentIdForTransferReverseShadowSyncOutboxPush()
    {
        $this->testFullPaymentTransferReverseShadowOutboxPush();
        $ledgerOutboxEntity = $this->getDbLastEntity('ledger_outbox');
        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);
        $actualLedgerOutboxEntry = json_decode($payload, true);
        $this->assertNotNull($actualLedgerOutboxEntry['journals'][1]['notes']['payment_id']);
    }

    public function testPartialPaymentTransferReverseShadowOutboxPush()
    {
        $this->assertNotNull($this->payment);

        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow']);

        $this->initialiseLedger(1000000, 0, 0);

        $this->assertEquals(0, $this->getAccountBalance('10000000000001'));
        $this->fixtures->merchant->addFeatures([ 'pg_ledger_reverse_shadow'], '10000000000001');

        $oldMarketBalance = $this->getAccountBalance('10000000000000');
        $this->assertGreaterThanOrEqual($this->payment['amount'],$oldMarketBalance);

        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 10000,
            'currency'=> 'INR',
        ];

        $expected = [
            'count' => 1,
            'items' => [
                [
                    'source'          => $this->payment['id'],
                    'recipient'       => 'acc_10000000000001',
                    'amount'          => 10000,
                    'amount_reversed' => 0,
                    'status' => 'pending',
                ],
            ],
        ];

        $content = $this->transferPayment($this->payment['id'], $transfers);
        $this->assertNotNull($content);
        $this->assertArraySelectiveEquals($expected, $content);
        $this->assertCount(1, $content['items']);

        $transferResponse = $content['items'][0];
        $transferId = $transferResponse['id'];

        // payment txn exists
        $paymentTxn = $this->getDbLastEntity('transaction');
        $this->assertEquals('payment', $paymentTxn['type']);
        $this->assertEquals($this->payment['id'], sprintf('pay_%s',$paymentTxn['entity_id']));

        // transfer entity exists
        $transfer = $this->getDbLastEntity('transfer');
        $this->assertNotNull($transfer);
        $this->assertEquals($transferId, sprintf('trf_%s',$transfer['id']));

        // dummy payment entity exists
        $transferPayment = $this->getDbEntity('payment', ['transfer_id' => $transferId]);
        $this->assertNull($transferPayment);

        // transfer txn and dummy payment txn not created
        $this->assertNull($transfer['transaction_id']);
        $this->assertNull($transferPayment['transaction_id']);
        $transferTxn = $this->getDbEntity('transaction', ['type' => 'transfer', 'entity_id' => $transferId]);
        $this->assertNull($transferTxn);

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
                        "amount" => "10000",
                        "base_amount" => "10000",
                        "merchant_payable_amount" => "10000",
                        "merchant_balance_amount" => "10000",
                        "tax" => "0",
                        "transfer_commission" => "0",
                    ],
                    "additional_params"=>["entry_type"=>"debit"]
                ],
                [
                    "merchant_id"=>"10000000000001",
                    "currency"=>"INR",
                    "money_params" => [
                        "amount" => "10000",
                        "base_amount" => "10000",
                        "merchant_payable_amount" => "10000",
                        "merchant_balance_amount" => "10000",
                    ],
                    "additional_params"=>["entry_type"=>"credit"]
                ]
            ],
        ];

        // fetch transfer journal payload from ledger_outbox
        $ledgerOutboxEntity = $this->getDbLastEntity('ledger_outbox');
        $this->assertNotNull($ledgerOutboxEntity);
        $this->assertEquals( sprintf('%s-transfer_processed', $transferId),$ledgerOutboxEntity['payload_name']);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);
        $actualLedgerOutboxEntry = json_decode($payload, true);
        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($transferId, $actualLedgerOutboxEntry['transactor_id']);
        $this->assertNotNull($actualLedgerOutboxEntry['idempotency_key']);

    }

    public function testPaymentTransferReverseShadowOutboxPushWithLedgerDualWritesEnabled()
    {
        $this->assertNotNull($this->payment);

        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow', 'pg_ledger_dual_writes']);

        $this->initialiseLedger(1000000, 0, 0);

        $this->assertEquals(0, $this->getAccountBalance('10000000000001'));
        $this->fixtures->merchant->addFeatures([ 'pg_ledger_reverse_shadow', 'pg_ledger_dual_writes'], '10000000000001');

        $oldMarketBalance = $this->getAccountBalance('10000000000000');
        $this->assertGreaterThanOrEqual($this->payment['amount'],$oldMarketBalance);

        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 10000,
            'currency'=> 'INR',
        ];

        $expected = [
            'count' => 1,
            'items' => [
                [
                    'source'          => $this->payment['id'],
                    'recipient'       => 'acc_10000000000001',
                    'amount'          => 10000,
                    'amount_reversed' => 0,
                    'status' => 'pending',
                ],
            ],
        ];

        $content = $this->transferPayment($this->payment['id'], $transfers);
        $this->assertNotNull($content);
        $this->assertArraySelectiveEquals($expected, $content);
        $this->assertCount(1, $content['items']);

        $transferResponse = $content['items'][0];
        $transferId = $transferResponse['id'];

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
                        "amount" => "10000",
                        "base_amount" => "10000",
                        "merchant_payable_amount" => "10000",
                        "merchant_balance_amount" => "10000",
                        "tax" => "0",
                        "transfer_commission" => "0",
                    ],
                    "additional_params"=>["entry_type"=>"debit"]
                ],
                [
                    "merchant_id"=>"10000000000001",
                    "currency"=>"INR",
                    "money_params" => [
                        "amount" => "10000",
                        "base_amount" => "10000",
                        "merchant_payable_amount" => "10000",
                        "merchant_balance_amount" => "10000",
                    ],
                    "additional_params"=>["entry_type"=>"credit"]
                ]
            ],
        ];

        // fetch transfer journal payload from ledger_outbox
        $ledgerOutboxEntity = $this->getDbLastEntity('ledger_outbox');
        $this->assertNotNull($ledgerOutboxEntity);
        $this->assertEquals( sprintf('%s-transfer_processed', $transferId),$ledgerOutboxEntity['payload_name']);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);
        $actualLedgerOutboxEntry = json_decode($payload, true);
        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($transferId, $actualLedgerOutboxEntry['transactor_id']);
        $this->assertNotNull($actualLedgerOutboxEntry['idempotency_key']);

    }

    public function testPaymentTransferReverseShadowOutboxPushWithAmountCreditsDeduction()
    {
        $this->assertNotNull($this->payment);

        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow']);

        $this->initialiseLedger(1000000, 0, 1000000);

        $this->assertEquals(0, $this->getAccountBalance('10000000000001'));
        $this->fixtures->merchant->addFeatures([ 'pg_ledger_reverse_shadow'], '10000000000001');

        $oldMarketBalance = $this->getAccountBalance('10000000000000');
        $this->assertGreaterThanOrEqual($this->payment['amount'],$oldMarketBalance);

        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 50000,
            'currency'=> 'INR',
        ];

        $expected = [
            'count' => 1,
            'items' => [
                [
                    'source'          => $this->payment['id'],
                    'recipient'       => 'acc_10000000000001',
                    'amount'          => 50000,
                    'amount_reversed' => 0,
                    'status' => 'pending',
                ],
            ],
        ];

        $content = $this->transferPayment($this->payment['id'], $transfers);
        $this->assertNotNull($content);
        $this->assertArraySelectiveEquals($expected, $content);
        $this->assertCount(1, $content['items']);

        $transferResponse = $content['items'][0];
        $transferId = $transferResponse['id'];

        // payment txn exists
        $paymentTxn = $this->getDbLastEntity('transaction');
        $this->assertEquals('payment', $paymentTxn['type']);
        $this->assertEquals($this->payment['id'], sprintf('pay_%s',$paymentTxn['entity_id']));

        // transfer entity exists
        $transfer = $this->getDbLastEntity('transfer');
        $this->assertNotNull($transfer);
        $this->assertEquals($transferId, sprintf('trf_%s',$transfer['id']));

        // dummy payment entity exists
        $transferPayment = $this->getDbEntity('payment', ['transfer_id' => $transferId]);
        $this->assertNull($transferPayment);

        // transfer txn and dummy payment txn not created
        $this->assertNull($transfer['transaction_id']);
        $this->assertNull($transferPayment['transaction_id']);
        $transferTxn = $this->getDbEntity('transaction', ['type' => 'transfer', 'entity_id' => $transferId]);
        $this->assertNull($transferTxn);

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
                        "razorpay_rewards"        => "50000",
                        "amount_credits"          => "50000"
                    ],
                    "additional_params"=>["entry_type"=>"debit", "credit_accounting" => "amount_credits_redemption"]
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
        $this->assertEquals( sprintf('%s-transfer_processed', $transferId),$ledgerOutboxEntity['payload_name']);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);
        $actualLedgerOutboxEntry = json_decode($payload, true);
        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($transferId, $actualLedgerOutboxEntry['transactor_id']);
        $this->assertNotNull($actualLedgerOutboxEntry['idempotency_key']);

    }

    public function testPaymentTransferReverseShadowOutboxPushWithAmountCreditsPositiveButInsufficientForTransfer()
    {
        $this->assertNotNull($this->payment);

        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow']);

        $this->initialiseLedger(1000000, 0, 10000);

        $this->assertEquals(0, $this->getAccountBalance('10000000000001'));
        $this->fixtures->merchant->addFeatures([ 'pg_ledger_reverse_shadow'], '10000000000001');

        $oldMarketBalance = $this->getAccountBalance('10000000000000');
        $this->assertGreaterThanOrEqual($this->payment['amount'],$oldMarketBalance);

        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 50000,
            'currency'=> 'INR',
        ];

        $expected = [
            'count' => 1,
            'items' => [
                [
                    'source'          => $this->payment['id'],
                    'recipient'       => 'acc_10000000000001',
                    'amount'          => 50000,
                    'amount_reversed' => 0,
                    'status' => 'pending',
                ],
            ],
        ];

        $content = $this->transferPayment($this->payment['id'], $transfers);
        $this->assertNotNull($content);
        $this->assertArraySelectiveEquals($expected, $content);
        $this->assertCount(1, $content['items']);

        $transferResponse = $content['items'][0];
        $transferId = $transferResponse['id'];

        // payment txn exists
        $paymentTxn = $this->getDbLastEntity('transaction');
        $this->assertEquals('payment', $paymentTxn['type']);
        $this->assertEquals($this->payment['id'], sprintf('pay_%s',$paymentTxn['entity_id']));

        // transfer entity exists
        $transfer = $this->getDbLastEntity('transfer');
        $this->assertNotNull($transfer);
        $this->assertEquals($transferId, sprintf('trf_%s',$transfer['id']));

        // dummy payment entity exists
        $transferPayment = $this->getDbEntity('payment', ['transfer_id' => $transferId]);
        $this->assertNull($transferPayment);

        // transfer txn and dummy payment txn not created
        $this->assertNull($transfer['transaction_id']);
        $this->assertNull($transferPayment['transaction_id']);
        $transferTxn = $this->getDbEntity('transaction', ['type' => 'transfer', 'entity_id' => $transferId]);
        $this->assertNull($transferTxn);

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
        $this->assertEquals( sprintf('%s-transfer_processed', $transferId),$ledgerOutboxEntity['payload_name']);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);
        $actualLedgerOutboxEntry = json_decode($payload, true);
        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($transferId, $actualLedgerOutboxEntry['transactor_id']);
        $this->assertNotNull($actualLedgerOutboxEntry['idempotency_key']);

    }

    public function testPaymentTransferReverseShadowOutboxPushWithFeeCreditsDeduction()
    {
        $this->assertNotNull($this->payment);

        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow']);

        $this->initialiseLedger(1000000, 1000000, 0);

        $this->assertEquals(0, $this->getAccountBalance('10000000000001'));
        $this->fixtures->merchant->addFeatures([ 'pg_ledger_reverse_shadow'], '10000000000001');

        $oldMarketBalance = $this->getAccountBalance('10000000000000');
        $this->assertGreaterThanOrEqual($this->payment['amount'],$oldMarketBalance);

        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 8000,
            'currency'=> 'INR',
        ];

        $expected = [
            'count' => 1,
            'items' => [
                [
                    'source'          => $this->payment['id'],
                    'recipient'       => 'acc_10000000000001',
                    'amount'          => 8000,
                    'amount_reversed' => 0,
                    'status' => 'pending',
                ],
            ],
        ];

        $content = $this->transferPayment($this->payment['id'], $transfers);
        $this->assertNotNull($content);
        $this->assertArraySelectiveEquals($expected, $content);
        $this->assertCount(1, $content['items']);

        $transferResponse = $content['items'][0];
        $transferId = $transferResponse['id'];

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
                        "amount" => "8000",
                        "base_amount" => "8000",
                        "merchant_payable_amount" => "8000",
                        "merchant_balance_amount" => "8000",
                        "tax" => "0",
                        "transfer_commission" => "0",
                        "fee_credits" => "0",
                    ],
                    "additional_params"=>["entry_type"=>"debit", "credit_accounting" => "fee_credits"]
                ],
                [
                    "merchant_id"=>"10000000000001",
                    "currency"=>"INR",
                    "money_params" => [
                        "amount" => "8000",
                        "base_amount" => "8000",
                        "merchant_payable_amount" => "8000",
                        "merchant_balance_amount" => "8000",
                    ],
                    "additional_params"=>["entry_type"=>"credit"]
                ]
            ],
        ];

        // fetch transfer journal payload from ledger_outbox
        $ledgerOutboxEntity = $this->getDbLastEntity('ledger_outbox');
        $this->assertNotNull($ledgerOutboxEntity);
        $this->assertEquals( sprintf('%s-transfer_processed', $transferId),$ledgerOutboxEntity['payload_name']);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);
        $actualLedgerOutboxEntry = json_decode($payload, true);
        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($transferId, $actualLedgerOutboxEntry['transactor_id']);
        $this->assertNotNull($actualLedgerOutboxEntry['idempotency_key']);
    }

    public function testPaymentTransferReverseShadowOutboxPushWithPostpaid()
    {
        $this->assertNotNull($this->payment);

        $this->fixtures->base->editEntity('merchant', '10000000000000', ['fee_model' => 'postpaid']);
        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow']);

        $this->initialiseLedger(1000000, 0, 0);

        $this->assertEquals(0, $this->getAccountBalance('10000000000001'));
        $this->fixtures->merchant->addFeatures([ 'pg_ledger_reverse_shadow'], '10000000000001');

        $oldMarketBalance = $this->getAccountBalance('10000000000000');
        $this->assertGreaterThanOrEqual($this->payment['amount'],$oldMarketBalance);

        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 8000,
            'currency'=> 'INR',
        ];

        $expected = [
            'count' => 1,
            'items' => [
                [
                    'source'          => $this->payment['id'],
                    'recipient'       => 'acc_10000000000001',
                    'amount'          => 8000,
                    'amount_reversed' => 0,
                    'status' => 'pending',
                ],
            ],
        ];

        $content = $this->transferPayment($this->payment['id'], $transfers);
        $this->assertNotNull($content);
        $this->assertArraySelectiveEquals($expected, $content);
        $this->assertCount(1, $content['items']);

        $transferResponse = $content['items'][0];
        $transferId = $transferResponse['id'];

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
                        "amount" => "8000",
                        "base_amount" => "8000",
                        "merchant_payable_amount" => "8000",
                        "merchant_balance_amount" => "8000",
                        "tax" => "0",
                        "transfer_commission" => "0",
                        "merchant_receivable_amount" => "0",
                    ],
                    "additional_params"=>["entry_type"=>"debit", "credit_accounting" => "postpaid"]
                ],
                [
                    "merchant_id"=>"10000000000001",
                    "currency"=>"INR",
                    "money_params" => [
                        "amount" => "8000",
                        "base_amount" => "8000",
                        "merchant_payable_amount" => "8000",
                        "merchant_balance_amount" => "8000",
                    ],
                    "additional_params"=>["entry_type"=>"credit"]
                ]
            ],
        ];

        // fetch transfer journal payload from ledger_outbox
        $ledgerOutboxEntity = $this->getDbLastEntity('ledger_outbox');
        $this->assertNotNull($ledgerOutboxEntity);
        $this->assertEquals( sprintf('%s-transfer_processed', $transferId),$ledgerOutboxEntity['payload_name']);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);
        $actualLedgerOutboxEntry = json_decode($payload, true);
        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($transferId, $actualLedgerOutboxEntry['transactor_id']);
        $this->assertNotNull($actualLedgerOutboxEntry['idempotency_key']);
    }

    public function testPaymentTransferReverseShadowOutboxPushWithAmountCreditsHigherPriorityOverFeeCreditsAndPostpaid()
    {
        $this->assertNotNull($this->payment);

        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow']);

        $this->fixtures->base->editEntity('merchant', '10000000000000', ['fee_model' => 'postpaid']);

        $this->initialiseLedger(1000000, 1000000, 1000000);

        $this->assertEquals(0, $this->getAccountBalance('10000000000001'));
        $this->fixtures->merchant->addFeatures([ 'pg_ledger_reverse_shadow'], '10000000000001');

        $oldMarketBalance = $this->getAccountBalance('10000000000000');
        $this->assertGreaterThanOrEqual($this->payment['amount'],$oldMarketBalance);

        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 50000,
            'currency'=> 'INR',
        ];

        $expected = [
            'count' => 1,
            'items' => [
                [
                    'source'          => $this->payment['id'],
                    'recipient'       => 'acc_10000000000001',
                    'amount'          => 50000,
                    'amount_reversed' => 0,
                    'status' => 'pending',
                ],
            ],
        ];

        $content = $this->transferPayment($this->payment['id'], $transfers);
        $this->assertNotNull($content);
        $this->assertArraySelectiveEquals($expected, $content);
        $this->assertCount(1, $content['items']);

        $transferResponse = $content['items'][0];
        $transferId = $transferResponse['id'];

        // payment txn exists
        $paymentTxn = $this->getDbLastEntity('transaction');
        $this->assertEquals('payment', $paymentTxn['type']);
        $this->assertEquals($this->payment['id'], sprintf('pay_%s',$paymentTxn['entity_id']));

        // transfer entity exists
        $transfer = $this->getDbLastEntity('transfer');
        $this->assertNotNull($transfer);
        $this->assertEquals($transferId, sprintf('trf_%s',$transfer['id']));

        // dummy payment entity exists
        $transferPayment = $this->getDbEntity('payment', ['transfer_id' => $transferId]);
        $this->assertNull($transferPayment);

        // transfer txn and dummy payment txn not created
        $this->assertNull($transfer['transaction_id']);
        $this->assertNull($transferPayment['transaction_id']);
        $transferTxn = $this->getDbEntity('transaction', ['type' => 'transfer', 'entity_id' => $transferId]);
        $this->assertNull($transferTxn);

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
                        "razorpay_rewards"        => "50000",
                        "amount_credits"          => "50000"
                    ],
                    "additional_params"=>["entry_type"=>"debit", "credit_accounting" => "amount_credits_redemption"]
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
        $this->assertEquals( sprintf('%s-transfer_processed', $transferId),$ledgerOutboxEntity['payload_name']);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);
        $actualLedgerOutboxEntry = json_decode($payload, true);
        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($transferId, $actualLedgerOutboxEntry['transactor_id']);
        $this->assertNotNull($actualLedgerOutboxEntry['idempotency_key']);
    }

    public function testPaymentTransferReverseShadowOutboxPushWithFeeCreditsHigherPriorityOverPostpaid()
    {
        $this->assertNotNull($this->payment);

        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow']);

        $this->fixtures->base->editEntity('merchant', '10000000000000', ['fee_model' => 'postpaid']);

        $this->initialiseLedger(1000000, 1000000, 0);

        $this->assertEquals(0, $this->getAccountBalance('10000000000001'));
        $this->fixtures->merchant->addFeatures([ 'pg_ledger_reverse_shadow'], '10000000000001');

        $oldMarketBalance = $this->getAccountBalance('10000000000000');
        $this->assertGreaterThanOrEqual($this->payment['amount'],$oldMarketBalance);

        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 8000,
            'currency'=> 'INR',
        ];

        $expected = [
            'count' => 1,
            'items' => [
                [
                    'source'          => $this->payment['id'],
                    'recipient'       => 'acc_10000000000001',
                    'amount'          => 8000,
                    'amount_reversed' => 0,
                    'status' => 'pending',
                ],
            ],
        ];

        $content = $this->transferPayment($this->payment['id'], $transfers);
        $this->assertNotNull($content);
        $this->assertArraySelectiveEquals($expected, $content);
        $this->assertCount(1, $content['items']);

        $transferResponse = $content['items'][0];
        $transferId = $transferResponse['id'];

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
                        "amount" => "8000",
                        "base_amount" => "8000",
                        "merchant_payable_amount" => "8000",
                        "merchant_balance_amount" => "8000",
                        "tax" => "0",
                        "transfer_commission" => "0",
                        "fee_credits" => "0",
                    ],
                    "additional_params"=>["entry_type"=>"debit", "credit_accounting" => "fee_credits"]
                ],
                [
                    "merchant_id"=>"10000000000001",
                    "currency"=>"INR",
                    "money_params" => [
                        "amount" => "8000",
                        "base_amount" => "8000",
                        "merchant_payable_amount" => "8000",
                        "merchant_balance_amount" => "8000",
                    ],
                    "additional_params"=>["entry_type"=>"credit"]
                ]
            ],
        ];

        // fetch transfer journal payload from ledger_outbox
        $ledgerOutboxEntity = $this->getDbLastEntity('ledger_outbox');
        $this->assertNotNull($ledgerOutboxEntity);
        $this->assertEquals( sprintf('%s-transfer_processed', $transferId),$ledgerOutboxEntity['payload_name']);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);
        $actualLedgerOutboxEntry = json_decode($payload, true);
        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($transferId, $actualLedgerOutboxEntry['transactor_id']);
        $this->assertNotNull($actualLedgerOutboxEntry['idempotency_key']);

    }

    public function testPaymentTransferReverseShadowOutboxPushWithNegativeBalanceLimitEnabled()
    {
        $this->assertNotNull($this->payment);

        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow']);

        $this->fixtures->create('balance_config',
            [
                'id'                            => '100yz000yz00yz',
                'balance_id'                    => '10000000000000',
                'type'                          => 'primary',
                'negative_transaction_flows'   => ['transfer'],
                'negative_limit_auto'           => 500000,
                'negative_limit_manual'         => 500000
            ]
        );

        $this->initialiseLedger(1000000, 1000000, 0);

        $this->assertEquals(0, $this->getAccountBalance('10000000000001'));
        $this->fixtures->merchant->addFeatures([ 'pg_ledger_reverse_shadow'], '10000000000001');

        $oldMarketBalance = $this->getAccountBalance('10000000000000');
        $this->assertGreaterThanOrEqual($this->payment['amount'],$oldMarketBalance);

        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 8000,
            'currency'=> 'INR',
        ];

        $expected = [
            'count' => 1,
            'items' => [
                [
                    'source'          => $this->payment['id'],
                    'recipient'       => 'acc_10000000000001',
                    'amount'          => 8000,
                    'amount_reversed' => 0,
                    'status' => 'pending',
                ],
            ],
        ];

        $content = $this->transferPayment($this->payment['id'], $transfers);
        $this->assertNotNull($content);
        $this->assertArraySelectiveEquals($expected, $content);
        $this->assertCount(1, $content['items']);

        $transferResponse = $content['items'][0];
        $transferId = $transferResponse['id'];

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
                        "amount" => "8000",
                        "base_amount" => "8000",
                        "merchant_payable_amount" => "8000",
                        "merchant_balance_amount" => "8000",
                        "tax" => "0",
                        "transfer_commission" => "0",
                        "fee_credits" => "0",
                        "merchant_balance_limit" =>  "500000",
                    ],
                    "additional_params"=>["entry_type"=>"debit", "credit_accounting" => "fee_credits"]
                ],
                [
                    "merchant_id"=>"10000000000001",
                    "currency"=>"INR",
                    "money_params" => [
                        "amount" => "8000",
                        "base_amount" => "8000",
                        "merchant_payable_amount" => "8000",
                        "merchant_balance_amount" => "8000",
                    ],
                    "additional_params"=>["entry_type"=>"credit"]
                ]
            ],
        ];

        // fetch transfer journal payload from ledger_outbox
        $ledgerOutboxEntity = $this->getDbLastEntity('ledger_outbox');
        $this->assertNotNull($ledgerOutboxEntity);
        $this->assertEquals( sprintf('%s-transfer_processed', $transferId),$ledgerOutboxEntity['payload_name']);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);
        $actualLedgerOutboxEntry = json_decode($payload, true);
        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($transferId, $actualLedgerOutboxEntry['transactor_id']);
        $this->assertNotNull($actualLedgerOutboxEntry['idempotency_key']);

    }

    public function testPaymentTransferReverseShadowOutboxPushFailureAmountGreaterThanCapturedFailure()
    {
        $this->assertNotNull($this->payment);

        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow']);

        $this->assertEquals(0, $this->getAccountBalance('10000000000001'));
        $this->fixtures->merchant->addFeatures([ 'pg_ledger_reverse_shadow'], '10000000000001');

        $oldMarketBalance = $this->getAccountBalance('10000000000000');
        $this->assertGreaterThanOrEqual($this->payment['amount'],$oldMarketBalance);

        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => $this->payment['amount'] + 1000,
            'currency'=> 'INR',
        ];

        $this->runRequestResponseFlow($this->testData[__FUNCTION__], function() use ($transfers)
        {
            $this->transferPayment($this->payment['id'], $transfers);
        });

        // payment txn exists
        $paymentTxn = $this->getDbLastEntity('transaction');
        $this->assertEquals('payment', $paymentTxn['type']);
        $this->assertEquals($this->payment['id'], sprintf('pay_%s',$paymentTxn['entity_id']));

        // transfer entity not created
        $transfer = $this->getDbLastEntity('transfer');
        $this->assertNull($transfer);

        //  transfer journal payload from ledger_outbox not created
        $ledgerOutboxEntity = $this->getDbLastEntity('ledger_outbox');
        $this->assertNull($ledgerOutboxEntity);

    }

    public function testPaymentTransferReverseShadowOutboxPushFailureFetchAccountsFailed()
    {
        $this->assertNotNull($this->payment);

        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow']);

        $this->app['config']->set('applications.ledger.enabled', true);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $mockLedger->shouldReceive('fetchAccountsByEntitiesAndMerchantID')
            ->times(1)
            ->andThrow(new \Exception("invalid argument"),
                [
                    "code"  => "invalid_argument",
                    "msg"   => "validation_failure: record_already_exist: BAD_REQUEST_RECORD_ALREADY_EXIST"
                ]
                , 400
            );

        $this->assertEquals(0, $this->getAccountBalance('10000000000001'));
        $this->fixtures->merchant->addFeatures([ 'pg_ledger_reverse_shadow'], '10000000000001');

        $oldMarketBalance = $this->getAccountBalance('10000000000000');
        $this->assertGreaterThanOrEqual($this->payment['amount'],$oldMarketBalance);

        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 10000,
            'currency'=> 'INR',
        ];

        try {
            $this->transferPayment($this->payment['id'], $transfers);
        }
        catch (\Exception $e)
        {
            $this->assertNotNull($e);

            // payment txn exists
            $paymentTxn = $this->getDbLastEntity('transaction');
            $this->assertEquals('payment', $paymentTxn['type']);
            $this->assertEquals($this->payment['id'], sprintf('pay_%s',$paymentTxn['entity_id']));

            // transfer entity not created
            $transfer = $this->getDbLastEntity('transfer');
            $this->assertNull($transfer);

            //  transfer journal payload from ledger_outbox not created
            $ledgerOutboxEntity = $this->getDbLastEntity('ledger_outbox');
            $this->assertNull($ledgerOutboxEntity);
        }
    }

    public function testPaymentTransferReverseShadowOutboxPushWhenTransferPaymentAlreadyExists()
    {
        $this->initialiseLedger(1000000, 0, 0);

        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow']);
        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow'], 10000000000001);

        $transferId = "AnyRandomID123";

        $paymentId = Payment\Entity::verifyIdAndSilentlyStripSign($this->payment['id']);

        $dummyTransferData = [
            'id'                 => $transferId,
            'source_id'          => $paymentId,
            'source_type'        => "payment",
            'status'             => "pending",
            'settlement_status'  => NULL,
            'to_id'              => 10000000000001,
            'to_type'            => "merchant",
            'amount'             => 50000,
            'currency'           => "INR",
            'amount_reversed'    => 0,
            'created_at'         => Carbon::now()->addHours(-5)->getTimestamp(),
            'updated_at'         => Carbon::now()->addHours(-4)->getTimestamp(),
            'processed_at'       => Carbon::now()->addHours(-4)->getTimestamp(),
        ];

        $this->fixtures->transfer->create($dummyTransferData);

        // create dummy payment
        $this->fixtures->payment->create(
            [
                'id'          => 'dummyN3uSlFkHT',
                'merchant_id' => '10000000000001',
                'transfer_id' => 'AnyRandomID123',
                'amount'      => 50000,
                'currency'    => 'INR',
                'method'      => 'transfer',
                'status'      => 'captured',
                'captured_at' => Carbon::now(Timezone::IST)->getTimestamp(),
                'fee'         => 0,
                'tax'         => 0,
            ]
        );

        // transfer entity exists
        $transfer = $this->getDbLastEntity('transfer');
        $this->assertNotNull($transfer);
        $this->assertEquals($transferId, $transfer['id']);

        // process transfer, dummy payment already exists
        (new TransferProcess('test', $this->payment['id'], 'payment'))->handle();

        // only 1 dummy payment entity exists
        $transferPayments = $this->getDbEntities('payment', ['transfer_id' => $transferId]);
        $this->assertCount(1, $transferPayments);
        $transferPayment = $transferPayments[0];
        $this->assertNotNull($transferPayment);
        $this->assertEquals('transfer', $transferPayment['method']);
        $this->assertEquals($transferId, $transferPayment['transfer_id']);

        // transfer txn and dummy payment txn not created
        $this->assertNull($transfer['transaction_id']);
        $this->assertNull($transferPayment['transaction_id']);
        $transferTxn = $this->getDbEntity('transaction', ['type' => 'transfer', 'entity_id' => $transferId]);
        $this->assertNull($transferTxn);
        $transferPaymentTxn = $this->getDbEntity('transaction', ['type' => 'payment', 'entity_id' => sprintf('pay_%s',$transferPayment['id'])]);
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
        $this->assertEquals( sprintf('%s-transfer_processed', 'trf_' . $transferId),$ledgerOutboxEntity['payload_name']);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);
        $actualLedgerOutboxEntry = json_decode($payload, true);
        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals('trf_' . $transferId, $actualLedgerOutboxEntry['transactor_id']);
        $this->assertNotNull($actualLedgerOutboxEntry['idempotency_key']);
    }

    /* ACK worker test cases */

    private function getPaymentTransferJournalResponsePayload($transferId, $debitJournalId, $creditJournalId, $sourceMID, $destnMID, $amountVal = 0, $commissionVal = 0, $taxVal = 0)
    {
        $amount = sprintf('%d', $amountVal);
        $commission = sprintf('%d', $commissionVal);
        $tax = sprintf('%d', $taxVal);
        return [
            "journals" => [
                [
                    "id" => $creditJournalId,
                    "created_at" => 1686490069,
                    "updated_at" => 1686490069,
                    "amount" => $amount,
                    "base_amount" => $amount,
                    "currency" => "INR",
                    "tenant" => "PG",
                    "transactor_id" =>  $transferId,
                    "transactor_event" => 'transfer_processed',
                    "transaction_date" => 1686490069,
                    "ledger_entry" => [
                        [
                            "id" => "M0dgdv6bfXnKyD",
                            "created_at" => 1686490069,
                            "updated_at" => 1686490069,
                            "merchant_id" => $destnMID,
                            "journal_id" => $creditJournalId,
                            "account_id" => "JjpZUD9PmJeNPk",
                            "amount" => $amount,
                            "base_amount" => $amount,
                            "type" => "credit",
                            "currency" => "INR",
                            "balance" => "1697591272.000000",
                            "balance_updated" => true,
                            "account_entities" => [
                                "account_type" => [
                                    "payable"
                                ],
                                "fund_account_type" => [
                                    "merchant_balance"
                                ]
                            ],
                            "notes" => [
                                "ledger_config_id"	=> "LJaX3wRdGtrNqc",
                                "payment_id"	=> "pay_ONnhOfz1UPaAXf"

                            ]
                        ],
                        [
                            "id" => "M0dgdv6cUeToBw",
                            "created_at" => 1686490069,
                            "updated_at" => 1686490069,
                            "merchant_id" =>  $destnMID,
                            "journal_id" => $creditJournalId,
                            "account_id" => "LycvlyvdXjtmUL",
                            "amount" => $amount,
                            "base_amount" => $amount,
                            "type" => "debit",
                            "currency" => "INR",
                            "account_entities" => [
                                "account_type" => [
                                    "payable"
                                ],
                                "fund_account_type" => [
                                    "merchant_va_merchant"
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    "id" => $debitJournalId,
                    "created_at" => 1686490069,
                    "updated_at" => 1686490069,
                    "amount" => $amount,
                    "base_amount" => $amount,
                    "currency" => "INR",
                    "tenant" => "PG",
                    "transactor_id" =>  $transferId,
                    "transactor_event" => 'transfer_processed',
                    "transaction_date" => 1686490069,
                    "ledger_entry" => [
                        [
                            "id" => "M0dgdv6cUeToBw",
                            "created_at" => 1686490069,
                            "updated_at" => 1686490069,
                            "merchant_id" =>  $sourceMID,
                            "journal_id" => $creditJournalId,
                            "account_id" => "LycvlyvdXjtmUL",
                            "amount" => $amount,
                            "base_amount" => $amount,
                            "type" => "credit",
                            "currency" => "INR",
                            "account_entities" => [
                                "account_type" => [
                                    "payable"
                                ],
                                "fund_account_type" => [
                                    "merchant_va_merchant"
                                ]
                            ]
                        ],
                        [
                            "id" => "M0dgdvV4wWeXaO",
                            "created_at" => 1686490069,
                            "updated_at" => 1686490069,
                            "merchant_id" => $sourceMID,
                            "journal_id" => $debitJournalId,
                            "account_id" => "JjpZUBB7rH14Is",
                            "amount" => $tax,
                            "base_amount" => $tax,
                            "type" => "credit",
                            "currency" => "INR",
                            "balance" => "115976.000000",
                            "balance_updated" => true,
                            "account_entities" => [
                                "account_type" => [
                                    "payable"
                                ],
                                "fund_account_type" => [
                                    "rzp_gst"
                                ]
                            ]
                        ],
                        [
                            "id" => "M0dgdvV4wWeXaO",
                            "created_at" => 1686490069,
                            "updated_at" => 1686490069,
                            "merchant_id" => $sourceMID,
                            "journal_id" => $debitJournalId,
                            "account_id" => "JjpZUBB7rH14Is",
                            "amount" => $commission,
                            "base_amount" => $commission,
                            "type" => "credit",
                            "currency" => "INR",
                            "balance" => "115976.000000",
                            "balance_updated" => true,
                            "account_entities" => [
                                "account_type" => [
                                    "cash"
                                ],
                                "fund_account_type" => [
                                    "rzp_transfer_fee"
                                ]
                            ]
                        ],
                        [
                            "id" => "M0dgdvV5sSk8f9",
                            "created_at" => 1686490069,
                            "updated_at" => 1686490069,
                            "merchant_id" =>  $sourceMID,
                            "journal_id" => $debitJournalId,
                            "account_id" => "LycvlyvdXjtmUL",
                            "amount" => $amount,
                            "base_amount" => $amount,
                            "type" => "debit",
                            "currency" => "INR",
                            "account_entities" => [
                                "account_type" => [
                                    "payable"
                                ],
                                "fund_account_type" => [
                                    "merchant_balance"
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    private function getDebitJournalForTransfer($transferId, $debitJournalId, $creditJournalId, $sourceMID, $destnMID, $amountVal = 0)
    {
        $amount = sprintf('%d', $amountVal);

        return [
            "journals" => [
                "id"               => $debitJournalId,
                "created_at"       => 1686490069,
                "updated_at"       => 1686490069,
                "amount"           => $amount,
                "base_amount"      => $amount,
                "currency"         => "INR",
                "tenant"           => "PG",
                "transactor_id"    => $transferId,
                "transactor_event" => 'transfer_processed',
                "transaction_date" => 1686490069,
                "ledger_entry"     => [
                    [
                        "id"               => "M0dgdvV4wWeXaO",
                        "created_at"       => 1686490069,
                        "updated_at"       => 1686490069,
                        "merchant_id"      => $sourceMID,
                        "journal_id"       => $debitJournalId,
                        "account_id"       => "JjpZUBB7rH14Is",
                        "amount"           => "0",
                        "base_amount"      => "0",
                        "type"             => "credit",
                        "currency"         => "INR",
                        "balance"          => "115976.000000",
                        "balance_updated"  => true,
                        "account_entities" => [
                            "account_type"      => [
                                "payable"
                            ],
                            "fund_account_type" => [
                                "rzp_gst"
                            ]
                        ]
                    ],
                    [
                        "id"               => "M0dgdvV4wWeXaO",
                        "created_at"       => 1686490069,
                        "updated_at"       => 1686490069,
                        "merchant_id"      => $sourceMID,
                        "journal_id"       => $debitJournalId,
                        "account_id"       => "JjpZUBB7rH14Is",
                        "amount"           => "0",
                        "base_amount"      => "0",
                        "type"             => "credit",
                        "currency"         => "INR",
                        "balance"          => "115976.000000",
                        "balance_updated"  => true,
                        "account_entities" => [
                            "account_type"      => [
                                "cash"
                            ],
                            "fund_account_type" => [
                                "rzp_transfer_fee"
                            ]
                        ]
                    ],
                    [
                        "id"               => "M0dgdvV5sSk8f9",
                        "created_at"       => 1686490069,
                        "updated_at"       => 1686490069,
                        "merchant_id"      => $sourceMID,
                        "journal_id"       => $debitJournalId,
                        "account_id"       => "LycvlyvdXjtmUL",
                        "amount"           => $amount,
                        "base_amount"      => $amount,
                        "type"             => "debit",
                        "currency"         => "INR",
                        "account_entities" => [
                            "account_type"      => [
                                "payable"
                            ],
                            "fund_account_type" => [
                                "merchant_balance"
                            ]
                        ]
                    ]
                ]
            ]];
    }

    private function getCreditJournalForTransfer($transferId, $debitJournalId, $creditJournalId, $sourceMID, $destnMID, $amountVal = 0)
    {
        $amount = sprintf('%d', $amountVal);

        return [
            "journals" => [
                "id"               => $creditJournalId,
                "created_at"       => 1686490069,
                "updated_at"       => 1686490069,
                "amount"           => $amount,
                "base_amount"      => $amount,
                "currency"         => "INR",
                "tenant"           => "PG",
                "transactor_id"    => $transferId,
                "transactor_event" => 'transfer_processed',
                "transaction_date" => 1686490069,
                "ledger_entry"     => [
                    [
                        "id"               => "M0dgdv6bfXnKyD",
                        "created_at"       => 1686490069,
                        "updated_at"       => 1686490069,
                        "merchant_id"      => $destnMID,
                        "journal_id"       => $creditJournalId,
                        "account_id"       => "JjpZUD9PmJeNPk",
                        "amount"           => $amount,
                        "base_amount"      => $amount,
                        "type"             => "credit",
                        "currency"         => "INR",
                        "balance"          => "1697591272.000000",
                        "balance_updated"  => true,
                        "account_entities" => [
                            "account_type"      => [
                                "payable"
                            ],
                            "fund_account_type" => [
                                "merchant_balance"
                            ]
                        ]
                    ],
                    [
                        "id"               => "M0dgdv6cUeToBw",
                        "created_at"       => 1686490069,
                        "updated_at"       => 1686490069,
                        "merchant_id"      => $destnMID,
                        "journal_id"       => $creditJournalId,
                        "account_id"       => "LycvlyvdXjtmUL",
                        "amount"           => $amount,
                        "base_amount"      => $amount,
                        "type"             => "debit",
                        "currency"         => "INR",
                        "account_entities" => [
                            "account_type"      => [
                                "payable"
                            ],
                            "fund_account_type" => [
                                "merchant_va_merchant"
                            ]
                        ]
                    ]
                ]
            ]];
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

    public function testFullPaymentTransferReverseShadowKafkaAckSuccess()
    {
        $sourceMID = '10000000000000';
        $destnMID = '10000000000001';

        $this->assertNotNull($this->payment);

        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow']);
        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow'], $destnMID);

        $oldDestnMarketBalance = $this->getAccountBalance($destnMID);
        $this->assertEquals(0, $oldDestnMarketBalance);

        $oldSourceMarketBalance = $this->getAccountBalance($sourceMID);
        $this->assertGreaterThanOrEqual($this->payment['amount'],$oldSourceMarketBalance);

        $this->initialiseLedger(1000000, 0, 0);

        // create transfer
        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 50000,
            'currency'=> 'INR',
        ];

        $content = $this->transferPayment($this->payment['id'], $transfers);

        $this->assertNotNull($content);

        $this->assertEquals($transfers[0]["amount"], $content['items'][0]["amount"]);

        $publicTransferId = $content['items'][0]['id'];

        $transferId =  str_replace('trf_', '', $publicTransferId);

        $transfer = $this->getDbEntity('transfer', ['id'=>$transferId]);
        $this->assertNotNull($transfer, 'transfer entity does nit exist');
        $this->assertNull($transfer['transaction_id'], 'transfer txn created in sync');

        $transferPayment = $this->getDbEntity('payment',['transfer_id' => $transferId ] );
        $this->assertNull($transferPayment, 'dummy payment entity does not exist');

        $debitJID = 'LsqR14zUg9dbDB' ;
        $creditJID = 'LsqR157oYgCrCR';

        $journal = $this->getPaymentTransferJournalResponsePayload($publicTransferId, $debitJID, $creditJID, $sourceMID, $destnMID, $transfers[0]['amount']);

        $kafkaEventPayload = $this->getKafkaEventPayload($journal);

        // run test
        (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_PG_LEDGER_ACKNOWLEDGMENTS, $kafkaEventPayload, 'test');

        // fetch transfer again to check if txn id associated
        $transfer = $this->getDbEntity('transfer',  ['id' => $transferId]);
        $this->assertNotNull($transfer, 'transfer not found');
        $this->assertNotNull($transfer['transaction_id'], 'debit transaction not associated with transfer');
        $this->assertEquals('processed', $transfer['status'], 'transfer status not marked processed');
        $this->assertEquals('pending', $transfer['settlement_status'], 'transfer settlement status not marked processed');
        $this->assertEquals($debitJID, $transfer['transaction_id'], 'transfer txn_id not equal to debit journal_id');

        // fetch source_payment again to check if amount_transferred updated
        $sourcePayment = $this->getDbEntity('payment', ['id' => str_replace('pay_', '', $this->payment['id'])]);
        $newTransferPaymentEntity = $this->getLastEntity('transfer_payment', true);
        $this->assertNotNull($sourcePayment, 'source payment not found');
        $this->assertEquals($transfer['amount'], $newTransferPaymentEntity['amount_transferred'], 'amount_transferred incorrect in source_payment ');

        // fetch transfer payment again to check if txn id associated
        $transferPayment = $this->getDbEntity('payment',['transfer_id' => $transferId ] );
        $this->assertNotNull($transferPayment, 'dummy payment entity does not exist');
        $this->assertEquals('transfer', $transferPayment['method']);
        $this->assertNotNull($transferPayment['transaction_id'], 'transfer txn and transfer_payment txn should not be created');
        $this->assertEquals('captured', $transferPayment['status'], 'transfer_payment not captured');
        $this->assertEquals($creditJID, $transferPayment['transaction_id'], 'transfer_payment txn_id not equal to credit journal_id');
        $this->assertSame(0, $transferPayment['fee']);
        $this->assertSame(0, $transferPayment['tax']);
        $this->assertSame(0, $transferPayment['mdr']);

        // fetch transfer txn
        $transferTxn = $this->getDbEntity('transaction', ['type' => 'transfer', 'entity_id' => $transferId]);
        $this->assertNotNull($transferTxn, 'transfer_txn not found');
        $this->assertEquals($debitJID, $transferTxn['id'], 'transfer_txn_id does not match debit journalId');
        $this->assertNotNull($transferTxn['balance_id'], ' balance not updated in transfer_txn');
        $this->assertNotNull($transferTxn['debit'], 'amount not debited from transfer Txn');

        $debitAMount = $transferTxn['debit'];

        // fetch transfer_payment txn
        $transferPaymentTxn = $this->getDbEntity('transaction', ['type' => 'payment', 'entity_id' => $transferPayment['id']]);
        $this->assertNotNull($transferPaymentTxn, 'transfer_payment_txn not found');
        $this->assertEquals($creditJID, $transferPaymentTxn['id'], 'transfer_payment_txn_id does not match credit journal_id');
        $this->assertNotNull($transferPaymentTxn['balance_id'], 'balance not updated in transfer_payment_txn');
        $this->assertNotNull($transferPaymentTxn['credit'], 'amount not credited from transfer_payment_txn');
        $this->assertEquals($debitAMount, $transferPaymentTxn['credit'], 'debit amount not equal to credit amount');

        // fetch  outbox entry
        $ledgerOutboxEntities = $this->getTrashedDbEntities('ledger_outbox', ['payload_name' => $publicTransferId.'-'.'transfer_processed']);
        $this->assertCount(1,$ledgerOutboxEntities, ' ledger_outbox entry for transfer_processed event not found');
        $this->assertEquals( $ledgerOutboxEntities[0]['is_deleted'], 1, 'outbox entry not soft deleted');
        $this->assertNotNull( $ledgerOutboxEntities[0]['deleted_at'], 'outbox entry not soft deleted');

        // check new source balance
        $newSourceMarketBalance = $this->getAccountBalance($sourceMID);
        $this->assertEquals($oldSourceMarketBalance - $transfer->getAmount(), $newSourceMarketBalance, 'source balance not deeducted');

        // check new destn balance
        $newDestnMarketBalance = $this->getAccountBalance($destnMID);
        $this->assertEquals($oldDestnMarketBalance + $transfer->getAmount(), $newDestnMarketBalance, 'destn balance not deeducted');

        return $transferId;
    }
    public function testPaymentIdFromJournalInFullPaymentTransferReverseShadowKafkaAckSuccess()
    {
        $transferPaymentId = 'ONnhOfz1UPaAXf';
        $transferId = $this->testFullPaymentTransferReverseShadowKafkaAckSuccess();
        $transferPayment = $this->getDbEntity('payment',['transfer_id' => $transferId ] );
        $this->assertNotNull($transferPayment, 'dummy payment entity does not exist');
        $this->assertEquals('transfer', $transferPayment['method']);
        $this->assertNotNull($transferPayment['transaction_id'], 'transfer txn and transfer_payment txn should not be created');
        $this->assertEquals('captured', $transferPayment['status'], 'transfer_payment not captured');
        $this->assertEquals($transferPayment['id'],$transferPaymentId,'transfer_payment id not equal to payment id in credit journal');
    }

    public function testGetFeeTaxFromTransferJournal()
    {
        $debitJournal = [
            "id" => 'LsqR14zUg9dbDB',
            "created_at" => 1686490069,
            "updated_at" => 1686490069,
            "amount" => '10000',
            "base_amount" => '10000',
            "currency" => "INR",
            "tenant" => "PG",
            "transactor_id" =>  'trf_TRFR157oYgCrCR',
            "transactor_event" => 'transfer_processed',
            "transaction_date" => 1686490069,
            "ledger_entry" => [
                [
                    "id" => "M0dgdv6cUeToBw",
                    "created_at" => 1686490069,
                    "updated_at" => 1686490069,
                    "merchant_id" =>  '10000000000000',
                    "journal_id" => 'LsqR14zUg9dbDB',
                    "account_id" => "LycvlyvdXjtmUL",
                    "amount" => '9000',
                    "base_amount" => '9000',
                    "type" => "credit",
                    "currency" => "INR",
                    "account_entities" => [
                        "account_type" => [
                            "payable"
                        ],
                        "fund_account_type" => [
                            "merchant_va_merchant"
                        ]
                    ]
                ],
                [
                    "id" => "M0dgdvV4wWeXaO",
                    "created_at" => 1686490069,
                    "updated_at" => 1686490069,
                    "merchant_id" =>  '10000000000000',
                    "journal_id" => 'LsqR14zUg9dbDB',
                    "account_id" => "JjpZUBB7rH14Is",
                    "amount" => "100",
                    "base_amount" => "100",
                    "type" => "credit",
                    "currency" => "INR",
                    "balance" => "115976.000000",
                    "balance_updated" => true,
                    "account_entities" => [
                        "account_type" => [
                            "payable"
                        ],
                        "fund_account_type" => [
                            "rzp_gst"
                        ]
                    ]
                ],
                [
                    "id" => "M0dgdvV4wWeXaO",
                    "created_at" => 1686490069,
                    "updated_at" => 1686490069,
                    "merchant_id" =>  '10000000000000',
                    "journal_id" => 'LsqR14zUg9dbDB',
                    "account_id" => "JjpZUBB7rH14Is",
                    "amount" => "900",
                    "base_amount" => "900",
                    "type" => "credit",
                    "currency" => "INR",
                    "balance" => "115976.000000",
                    "balance_updated" => true,
                    "account_entities" => [
                        "account_type" => [
                            "cash"
                        ],
                        "fund_account_type" => [
                            "rzp_transfer_fee"
                        ]
                    ]
                ],
                [
                    "id" => "M0dgdvV5sSk8f9",
                    "created_at" => 1686490069,
                    "updated_at" => 1686490069,
                    "merchant_id" =>  '10000000000000',
                    "journal_id" => 'LsqR14zUg9dbDB',
                    "account_id" => "LycvlyvdXjtmUL",
                    "amount" => '10000',
                    "base_amount" => '10000',
                    "type" => "debit",
                    "currency" => "INR",
                    "account_entities" => [
                        "account_type" => [
                            "payable"
                        ],
                        "fund_account_type" => [
                            "merchant_balance"
                        ]
                    ]
                ]
            ]
        ];
        [$fees, $tax, $isAmountCreditsUsed] = $this->getFeeAndTaxFromJournal($debitJournal,"rzp_transfer_fee","rzp_gst");
        $this->assertEquals(100, $tax);
        $this->assertEquals(1000, $fees);
        $this->assertFalse($isAmountCreditsUsed);

    }

    public function testPartialPaymentTransferReverseShadowKafkaAckSuccess()
    {
        $sourceMID = '10000000000000';
        $destnMID = '10000000000001';

        $this->assertNotNull($this->payment);

        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow']);
        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow'], $destnMID);

        $oldDestnMarketBalance = $this->getAccountBalance($destnMID);
        $this->assertEquals(0, $oldDestnMarketBalance);

        $oldSourceMarketBalance = $this->getAccountBalance($sourceMID);
        $this->assertGreaterThanOrEqual($this->payment['amount'],$oldSourceMarketBalance);

        $this->initialiseLedger(1000000, 0, 0);

        // create transfer
        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 10000,
            'currency'=> 'INR',
        ];

        $content = $this->transferPayment($this->payment['id'], $transfers);

        $this->assertNotNull($content);

        $this->assertEquals($transfers[0]["amount"], $content['items'][0]["amount"]);

        $publicTransferId = $content['items'][0]['id'];

        $transferId =  str_replace('trf_', '', $publicTransferId);

        $transfer = $this->getDbEntity('transfer', ['id'=>$transferId]);
        $this->assertNotNull($transfer, 'transfer entity does nit exist');
        $this->assertNull($transfer['transaction_id'], 'transfer txn created in sync');

        $transferPayment = $this->getDbEntity('payment',['transfer_id' => $transferId ] );
        $this->assertNull($transferPayment, 'dummy payment entity does not exist');

        $debitJID = 'LsqR14zUg9dbDB' ;
        $creditJID = 'LsqR157oYgCrCR';

        $journal = $this->getPaymentTransferJournalResponsePayload($publicTransferId, $debitJID, $creditJID, $sourceMID, $destnMID, $transfers[0]['amount']);

        $kafkaEventPayload = $this->getKafkaEventPayload($journal);

        // run test
        (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_PG_LEDGER_ACKNOWLEDGMENTS, $kafkaEventPayload, 'test');

        // fetch transfer again to check if txn id associated
        $transfer = $this->getDbEntity('transfer',  ['id' => $transferId]);
        $this->assertNotNull($transfer, 'transfer not found');
        $this->assertNotNull($transfer['transaction_id'], 'debit transaction not associated with transfer');
        $this->assertEquals('processed', $transfer['status'], 'transfer status not marked processed');
        $this->assertEquals('pending', $transfer['settlement_status'], 'transfer settlement status not marked processed');
        $this->assertEquals($debitJID, $transfer['transaction_id'], 'transfer txn_id not equal to debit journal_id');

        // fetch source_payment again to check if amount_transferred updated
        $sourcePayment = $this->getDbEntity('payment', ['id' => str_replace('pay_', '', $this->payment['id'])]);
        $newTransferPaymentEntity = $this->getLastEntity('transfer_payment', true);

        $this->assertNotNull($sourcePayment, 'source payment not found');
        $this->assertEquals($transfer['amount'], $newTransferPaymentEntity['amount_transferred'], 'amount_transferred incorrect in source_payment ');

        // fetch transfer payment again to check if txn id associated
        $transferPayment = $this->getDbEntity('payment', ['transfer_id' => $transferId]);
        $this->assertNotNull($transferPayment, 'transfer_payment not found');
        $this->assertEquals('captured', $transferPayment['status'], 'transfer_payment not captured');
        $this->assertEquals($creditJID, $transferPayment['transaction_id'], 'transfer_payment txn_id not equal to credit journal_id');
        $this->assertSame(0, $transferPayment['fee']);
        $this->assertSame(0, $transferPayment['tax']);
        $this->assertSame(0, $transferPayment['mdr']);

        // fetch transfer txn
        $transferTxn = $this->getDbEntity('transaction', ['type' => 'transfer', 'entity_id' => $transferId]);
        $this->assertNotNull($transferTxn, 'transfer_txn not found');
        $this->assertEquals($debitJID, $transferTxn['id'], 'transfer_txn_id does not match debit journalId');
        $this->assertNotNull($transferTxn['balance_id'], ' balance not updated in transfer_txn');
        $this->assertNotNull($transferTxn['debit'], 'amount not debited from transfer Txn');

        $debitAMount = $transferTxn['debit'];

        // fetch transfer_payment txn
        $transferPaymentTxn = $this->getDbEntity('transaction', ['type' => 'payment', 'entity_id' => $transferPayment['id']]);
        $this->assertNotNull($transferPaymentTxn, 'transfer_payment_txn not found');
        $this->assertEquals($creditJID, $transferPaymentTxn['id'], 'transfer_payment_txn_id does not match credit journal_id');
        $this->assertNotNull($transferPaymentTxn['balance_id'], 'balance not updated in transfer_payment_txn');
        $this->assertNotNull($transferPaymentTxn['credit'], 'amount not credited from transfer_payment_txn');
        $this->assertEquals($debitAMount, $transferPaymentTxn['credit'], 'debit amount not equal to credit amount');

        // fetch  outbox entry
        $ledgerOutboxEntities = $this->getTrashedDbEntities('ledger_outbox', ['payload_name' => $publicTransferId.'-'.'transfer_processed']);
        $this->assertCount(1,$ledgerOutboxEntities, ' ledger_outbox entry for transfer_processed event not found');
        $this->assertEquals( $ledgerOutboxEntities[0]['is_deleted'], 1, 'outbox entry not soft deleted');
        $this->assertNotNull( $ledgerOutboxEntities[0]['deleted_at'], 'outbox entry not soft deleted');

        // check new source balance
        $newSourceMarketBalance = $this->getAccountBalance($sourceMID);
        $this->assertEquals($oldSourceMarketBalance - $transfer->getAmount(), $newSourceMarketBalance, 'source balance not deeducted');

        // check new destn balance
        $newDestnMarketBalance = $this->getAccountBalance($destnMID);
        $this->assertEquals($oldDestnMarketBalance + $transfer->getAmount(), $newDestnMarketBalance, 'destn balance not deeducted');

    }

    public function testAPITxnAlreadyPresentForPaymentTransferKafkaAckSuccess()
    {
        $sourceMID = '10000000000000';
        $destnMID = '10000000000001';

        $this->assertNotNull($this->payment);

        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow']);
        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow'], $destnMID);

        $oldDestnMarketBalance = $this->getAccountBalance($destnMID);
        $this->assertEquals(0, $oldDestnMarketBalance);

        $oldSourceMarketBalance = $this->getAccountBalance($sourceMID);
        $this->assertGreaterThanOrEqual($this->payment['amount'],$oldSourceMarketBalance);

        $this->initialiseLedger(1000000, 0, 0);

        // create transfer
        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 10000,
            'currency'=> 'INR',
        ];

        $content = $this->transferPayment($this->payment['id'], $transfers);

        $publicTransferId = $content['items'][0]['id'];

        $transferId =  str_replace('trf_', '', $publicTransferId);

        $debitJID = 'LsqR14zUg9dbDB' ;
        $creditJID = 'LsqR157oYgCrCR';

        $journal = $this->getPaymentTransferJournalResponsePayload($publicTransferId, $debitJID, $creditJID, $sourceMID, $destnMID, $transfers[0]['amount']);

        $kafkaEventPayload = $this->getKafkaEventPayload($journal);

        $this->fixtures->create('payment', [
            'transfer_id' => $transferId,
            'amount' => 10000,
            'status' => 'captured',
            'currency'=> 'INR',
        ]);
        $transferPayment = $this->getDbEntity('payment',['transfer_id' => $transferId ] );

        $sourcePaymentId = str_replace('pay_', '', $this->payment['id']);

        $this->fixtures->edit('payment', $sourcePaymentId, [
            'amount_transferred' => $transfers[0]['amount'],
        ]);

        $this->fixtures->create('transaction', [
            'id' => $debitJID,
            'entity_id' => $transferId,
            'merchant_id' => $sourceMID,
            'type' => 'transfer',
            'settled' => 1,
            'debit' => $transfers[0]['amount'],
            'balance_id' => 'TCTRhsU4aiY0t1'
        ]);

        $this->fixtures->create('transaction', [
            'id' => $creditJID,
            'entity_id' => $transferPayment['id'],
            'merchant_id' => $destnMID,
            'type' => 'payment',
            'settled' => 1,
            'debit' => $transfers[0]['amount'],
            'balance_id' => 'TCTRhsU4aiY0t1'
        ]);

        // create transaction fixtures to test txn already exists scenario
        $this->fixtures->edit('transfer',$transferId, [
            'status' => 'processed',
            'transaction_id' => $debitJID
        ]);

        $this->fixtures->edit('payment',$transferPayment['id'], [
            'status' => 'captured',
            'transaction_id' => $creditJID
        ]);


        $transfer = $this->getDbEntity('transfer',  ['id' => $transferId]);
        $this->assertEquals('processed', $transfer['status'], 'transfer status not marked processed');
        $this->assertEquals($debitJID, $transfer['transaction_id'], 'transfer txn_id not equal to debit journal_id');

        // fetch transfer payment again to check if txn id associated
        $transferPayment = $this->getDbEntity('payment',['transfer_id' => $transferId ] );
        $this->assertNotNull($transferPayment, 'transfer_payment not found');
        $this->assertEquals('captured', $transferPayment['status'], 'transfer_payment not captured');
        $this->assertEquals($creditJID, $transferPayment['transaction_id'], 'transfer_payment txn_id not equal to credit journal_id');

        // run test
        (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_PG_LEDGER_ACKNOWLEDGMENTS, $kafkaEventPayload, 'test');

        // fetch source_payment again to check if amount_transferred updated
        $sourcePayment = $this->getDbEntity('payment', ['id' => $sourcePaymentId]);
        $this->assertNotNull($sourcePayment, 'source payment not found');
        $this->assertEquals($transfer['amount'], $sourcePayment['amount_transferred'], 'amount_transferred incorrect in source_payment ');

        // check new source balance - should not get updated
        $finalSourceMarketBalance = $this->getAccountBalance($sourceMID);
        $this->assertEquals($oldSourceMarketBalance, $finalSourceMarketBalance, 'source balance deducted');

        // check new destn balance - should not get updated
        $finalDestnMarketBalance = $this->getAccountBalance($destnMID);
        $this->assertEquals( $oldDestnMarketBalance, $finalDestnMarketBalance, 'destn balance deducted');

    }

    public function testKafkaNonRetryableFailureValidationFailureForPaymentTransferProcessedEvent()
    {
        $this->assertNotNull($this->payment);

        $sourceMID = '10000000000000';
        $destnMID = '10000000000001';

        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow']);
        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow'], $destnMID);

        $oldDestnMarketBalance = $this->getAccountBalance($destnMID);
        $this->assertEquals(0, $oldDestnMarketBalance);

        $oldSourceMarketBalance = $this->getAccountBalance($sourceMID);
        $this->assertGreaterThanOrEqual($this->payment['amount'],$oldSourceMarketBalance);

        $this->initialiseLedger(1000000, 0, 0, 1);

        // create transfer payload
        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 10000,
            'currency'=> 'INR',
        ];

        //create transfer for payment
        $content = $this->transferPayment($this->payment['id'], $transfers);

        $publicTransferId = $content['items'][0]['id'];

        $transferId =  str_replace('trf_', '', $publicTransferId);

        $kafkaEventPayload = $this->getKafkaEventPayload(
            null,
            [
                "transactor_event" => "transfer_processed",
                "transactor_id"     => $publicTransferId
            ],
            "validation_failure: validation_failure: BAD_REQUEST_VALIDATION_FAILURE",
        );

        // run test
        (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_PG_LEDGER_ACKNOWLEDGMENTS, $kafkaEventPayload, 'test');

        $transfer = $this->getDbEntity('transfer', ['id'=>$transferId]);
        $this->assertNotNull($transfer, 'transfer entity does nit exist');
        $this->assertNull($transfer['transaction_id'], 'transfer txn created in sync');
        $this->assertNotEquals('processed', $transfer['status'], 'transfer status marked processed');

        // fetch source_payment again to check if amount_transferred updated
        $sourcePayment = $this->getDbEntity('payment', ['id' => str_replace('pay_', '', $this->payment['id'])]);
        $this->assertNotNull($sourcePayment, 'source payment not found');
        $this->assertEquals(0, $sourcePayment['amount_transferred'], 'amount_transferred incorrect in source_payment ');

        $transferPayment = $this->getDbEntity('payment',['transfer_id' => $transferId ] );
        $this->assertNull($transferPayment, 'dummy payment entity exist');

        $ledgerOutboxEntity = $this->getTrashedDbEntity('ledger_outbox', ['payload_name' => $publicTransferId.'-transfer_processed']);
        $this->assertEquals(1, $ledgerOutboxEntity['is_deleted'], 'outbox entry not soft deleted');
        $this->assertNotNull($ledgerOutboxEntity['deleted_at'], 'outbox entry not soft deleted');

        // check new source balance
        $newSourceMarketBalance = $this->getAccountBalance($sourceMID);
        $this->assertEquals($oldSourceMarketBalance, $newSourceMarketBalance, 'source balance  deducted');

        // check new destn balance
        $newDestnMarketBalance = $this->getAccountBalance($destnMID);
        $this->assertEquals($oldDestnMarketBalance, $newDestnMarketBalance, 'destn balance  deducted');
    }

    public function testKafkaNonRetryableFailureLedgerBuilderFailureForPaymentTransferProcessedEvent()
    {
        $this->assertNotNull($this->payment);

        $sourceMID = '10000000000000';
        $destnMID = '10000000000001';

        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow']);
        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow'], $destnMID);

        $oldDestnMarketBalance = $this->getAccountBalance($destnMID);
        $this->assertEquals(0, $oldDestnMarketBalance);

        $oldSourceMarketBalance = $this->getAccountBalance($sourceMID);
        $this->assertGreaterThanOrEqual($this->payment['amount'],$oldSourceMarketBalance);

        $this->initialiseLedger(1000000, 0, 0, 1);

        // create transfer payload
        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 10000,
            'currency'=> 'INR',
        ];

        //create transfer for payment
        $content = $this->transferPayment($this->payment['id'], $transfers);

        $publicTransferId = $content['items'][0]['id'];

        $transferId =  str_replace('trf_', '', $publicTransferId);

        $kafkaEventPayload = $this->getKafkaEventPayload(
            null,
            [
                "transactor_event" => "transfer_processed",
                "transactor_id"     => $publicTransferId
            ],
            "ledger_builder_failure: No parameter 'commission' found." ,
        );

        // run test
        (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_PG_LEDGER_ACKNOWLEDGMENTS, $kafkaEventPayload, 'test');

        $transfer = $this->getDbEntity('transfer', ['id'=>$transferId]);
        $this->assertNotNull($transfer, 'transfer entity does nit exist');
        $this->assertNull($transfer['transaction_id'], 'transfer txn created in sync');
        $this->assertNotEquals('processed', $transfer['status'], 'transfer status marked processed');

        // fetch source_payment again to check if amount_transferred updated
        $sourcePayment = $this->getDbEntity('payment', ['id' => str_replace('pay_', '', $this->payment['id'])]);
        $this->assertNotNull($sourcePayment, 'source payment not found');
        $this->assertEquals(0, $sourcePayment['amount_transferred'], 'amount_transferred incorrect in source_payment ');

        $transferPayment = $this->getDbEntity('payment',['transfer_id' => $transferId ] );
        $this->assertNull($transferPayment, 'dummy payment entity  exist');

        $ledgerOutboxEntity = $this->getTrashedDbEntity('ledger_outbox', ['payload_name' => $publicTransferId.'-transfer_processed']);
        $this->assertEquals(1, $ledgerOutboxEntity['is_deleted'], 'outbox entry not soft deleted');
        $this->assertNotNull($ledgerOutboxEntity['deleted_at'], 'outbox entry not soft deleted');

        // check new source balance
        $newSourceMarketBalance = $this->getAccountBalance($sourceMID);
        $this->assertEquals($oldSourceMarketBalance, $newSourceMarketBalance, 'source balance not deducted');

        // check new destn balance
        $newDestnMarketBalance = $this->getAccountBalance($destnMID);
        $this->assertEquals($oldDestnMarketBalance, $newDestnMarketBalance, 'destn balance not deducted');
    }

    public function testKafkaNonRetryableFailureRecordAlreadyExistForPaymentTransferProcessedEvent()
    {
        $this->assertNotNull($this->payment);

        $sourceMID = '10000000000000';
        $destnMID = '10000000000001';

        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow']);
        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow'], $destnMID);

        $oldDestnMarketBalance = $this->getAccountBalance($destnMID);
        $this->assertEquals(0, $oldDestnMarketBalance);

        $oldSourceMarketBalance = $this->getAccountBalance($sourceMID);
        $this->assertGreaterThanOrEqual($this->payment['amount'],$oldSourceMarketBalance);

        $mockLedger = $this->initialiseLedger(1000000, 0, 0);

        // create transfer payload
        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 10000,
            'currency'=> 'INR',
        ];

        //create transfer for payment
        $content = $this->transferPayment($this->payment['id'], $transfers);

        $publicTransferId = $content['items'][0]['id'];

        $transferId =  str_replace('trf_', '', $publicTransferId);

        $kafkaEventPayload = $this->getKafkaEventPayload(
            null,
            [
                "transactor_event" => "transfer_processed",
                "transactor_id"     => $publicTransferId
            ],
            "validation_failure: record_already_exist: BAD_REQUEST_RECORD_ALREADY_EXIST"
        );

        $debitJID = 'LsqR14zUg9dbDB' ;
        $creditJID = 'LsqR157oYgCrCR';

        $mockLedger->shouldReceive('fetchByTransactor')
            ->times(1)
            ->andReturn([
                    "body" =>  $this->getPaymentTransferJournalResponsePayload($publicTransferId, $debitJID, $creditJID, $sourceMID, $destnMID, $transfers[0]['amount'])
                ]
            );

        // run test
        (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_PG_LEDGER_ACKNOWLEDGMENTS, $kafkaEventPayload, 'test');

        // fetch transfer again to check if txn id associated
        $transfer = $this->getDbEntity('transfer',  ['id' => $transferId]);
        $this->assertNotNull($transfer, 'transfer not found');
        $this->assertNull($transfer['transaction_id'], 'transfer txn associaited not found');
        $this->assertEquals('pending', $transfer['status'], 'transfer status not marked pending');

        // fetch source_payment again to check if amount_transferred updated
        $sourcePayment = $this->getDbEntity('payment', ['id' => str_replace('pay_', '', $this->payment['id'])]);
        $newTransferPaymentEntity = $this->getLastEntity('transfer_payment', true);

        $this->assertNotNull($sourcePayment, 'source payment not found');
        $this->assertNotEquals($transfer['amount'], $newTransferPaymentEntity['amount_transferred'], 'amount_transferred incorrect in source_payment ');

        // fetch transfer payment again to check if txn id associated
        $transferPayment = $this->getDbEntity('payment',['transfer_id' => $transferId ] );
        $this->assertNull($transferPayment, 'transfer_payment  found for prnding transfer');

        // fetch transfer txn
        $transferTxn = $this->getDbEntity('transaction', ['type' => 'transfer', 'entity_id' => $transferId]);
        $this->assertNull($transferTxn, 'transfer_txn not found');


        // fetch transfer_payment txn
        $transferPaymentTxn = $this->getDbEntity('transaction', ['type' => 'payment', 'entity_id' => $transferPayment['id']]);
        $this->assertNull($transferPaymentTxn, 'transfer_payment_txn not found');

        // fetch  outbox entry
        $ledgerOutboxEntities = $this->getDbEntities('ledger_outbox', ['payload_name' => $publicTransferId.'-'.'transfer_processed']);
        $this->assertCount(1,$ledgerOutboxEntities, ' ledger_outbox entry for transfer_processed event not found');
        $this->assertEquals( $ledgerOutboxEntities[0]['is_deleted'], 0, 'outbox entry not soft deleted');
        $this->assertNull( $ledgerOutboxEntities[0]['deleted_at'], 'outbox entry not soft deleted');

        // check new source balance
        $newSourceMarketBalance = $this->getAccountBalance($sourceMID);
        $this->assertEquals($oldSourceMarketBalance, $newSourceMarketBalance, 'source balance not deeducted');

        // check new destn balance
        $newDestnMarketBalance = $this->getAccountBalance($destnMID);
        $this->assertEquals($oldDestnMarketBalance, $newDestnMarketBalance, 'destn balance not deeducted');

    }

    public function testKafkaRetryableFailuresForPaymentTransferProcessedEvent()
    {
        $this->assertNotNull($this->payment);

        $sourceMID = '10000000000000';
        $destnMID = '10000000000001';

        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow']);
        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow'], $destnMID);

        $oldDestnMarketBalance = $this->getAccountBalance($destnMID);
        $this->assertEquals(0, $oldDestnMarketBalance);

        $oldSourceMarketBalance = $this->getAccountBalance($sourceMID);
        $this->assertGreaterThanOrEqual($this->payment['amount'],$oldSourceMarketBalance);

        $this->initialiseLedger(1000000, 0, 0, 4);

        $retryableErrorMessages = [
            "insufficient_balance_failure: BAD_REQUEST_INSUFFICIENT_BALANCE",
            "account_discovery_failure: ACCOUNT_DISCOVERY_ACCOUNT_NOT_FOUND",
            "account_discovery_failure: ACCOUNT_DISCOVERY_MULTIPLE_ACCOUNTS_FOUND",
            "mutex_failure: resource already acquired",
        ];

        foreach ($retryableErrorMessages as $errorMessage)
        {
            // create transfer payload
            $transfers[0] = [
                'account' => 'acc_10000000000001',
                'amount'  => 10000,
                'currency'=> 'INR',
            ];

            //create transfer for payment
            $content = $this->transferPayment($this->payment['id'], $transfers);

            $publicTransferId = $content['items'][0]['id'];

            $transferId =  str_replace('trf_', '', $publicTransferId);

            $kafkaEventPayload = $this->getKafkaEventPayload(
                null,
                [
                    "transactor_event" => "transfer_processed",
                    "transactor_id"     => $publicTransferId
                ],
                $errorMessage
            );

            // run test
            (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_PG_LEDGER_ACKNOWLEDGMENTS, $kafkaEventPayload, 'test');

            $transfer = $this->getDbEntity('transfer', ['id'=>$transferId]);
            $this->assertNotNull($transfer, 'transfer entity does nit exist');
            $this->assertNull($transfer['transaction_id'], 'transfer txn created in sync');
            $this->assertNotEquals('processed', $transfer['status'], 'transfer status marked processed');

            // fetch source_payment again to check if amount_transferred updated
            $sourcePayment = $this->getDbEntity('payment', ['id' => str_replace('pay_', '', $this->payment['id'])]);
            $this->assertNotNull($sourcePayment, 'source payment not found');
            $this->assertEquals(0, $sourcePayment['amount_transferred'], 'amount_transferred incorrect in source_payment ');

            $transferPayment = $this->getDbEntity('payment',['transfer_id' => $transferId ] );
            $this->assertNull($transferPayment, 'dummy payment entity does not exist');

            $ledgerOutboxEntity = $this->getTrashedDbEntity('ledger_outbox', ['payload_name' => $publicTransferId.'-transfer_processed']);
            $this->assertEquals(0, $ledgerOutboxEntity['is_deleted'], 'outbox entry  soft deleted');
            $this->assertNull($ledgerOutboxEntity['deleted_at'], 'outbox entry  soft deleted');

            // check new source balance
            $newSourceMarketBalance = $this->getAccountBalance($sourceMID);
            $this->assertEquals($oldSourceMarketBalance, $newSourceMarketBalance, 'source balance not deducted');

            // check new destn balance
            $newDestnMarketBalance = $this->getAccountBalance($destnMID);
            $this->assertEquals($oldDestnMarketBalance, $newDestnMarketBalance, 'destn balance not deducted');
        }
    }

    public function testKafkaRetryableFailureRecordAlreadyExistForPaymentTransferProcessedEvent()
    {
        $this->assertNotNull($this->payment);

        $sourceMID = '10000000000000';
        $destnMID = '10000000000001';

        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow']);
        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow'], $destnMID);

        $oldDestnMarketBalance = $this->getAccountBalance($destnMID);
        $this->assertEquals(0, $oldDestnMarketBalance);

        $oldSourceMarketBalance = $this->getAccountBalance($sourceMID);
        $this->assertGreaterThanOrEqual($this->payment['amount'],$oldSourceMarketBalance);

        $mockLedger = $this->initialiseLedger(1000000, 0, 0);

        // create transfer payload
        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 10000,
            'currency'=> 'INR',
        ];

        //create transfer for payment
        $content = $this->transferPayment($this->payment['id'], $transfers);

        $publicTransferId = $content['items'][0]['id'];

        $transferId =  str_replace('trf_', '', $publicTransferId);

        $kafkaEventPayload = $this->getKafkaEventPayload(
            null,
            [
                "transactor_event" => "transfer_processed",
                "transactor_id"     => $publicTransferId
            ],
            "validation_failure: record_already_exist: BAD_REQUEST_RECORD_ALREADY_EXIST"
        );

        $mockLedger->shouldReceive('fetchByTransactor')
            ->times(1)
            ->andReturn([
                    "body" =>  null
                ]
            );

        // run test
        (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_PG_LEDGER_ACKNOWLEDGMENTS, $kafkaEventPayload, 'test');

        $transfer = $this->getDbEntity('transfer', ['id'=>$transferId]);
        $this->assertNotNull($transfer, 'transfer entity does nit exist');
        $this->assertNull($transfer['transaction_id'], 'transfer txn created in sync');
        $this->assertNotEquals('processed', $transfer['status'], 'transfer status marked processed');

        // fetch source_payment again to check if amount_transferred updated
        $sourcePayment = $this->getDbEntity('payment', ['id' => str_replace('pay_', '', $this->payment['id'])]);
        $this->assertNotNull($sourcePayment, 'source payment not found');
        $this->assertEquals(0, $sourcePayment['amount_transferred'], 'amount_transferred incorrect in source_payment ');

        $transferPayment = $this->getDbEntity('payment',['transfer_id' => $transferId ] );
        $this->assertNull($transferPayment, 'dummy payment entity exist');

        $ledgerOutboxEntity = $this->getTrashedDbEntity('ledger_outbox', ['payload_name' => $publicTransferId.'-transfer_processed']);
        $this->assertEquals(0, $ledgerOutboxEntity['is_deleted'], 'outbox entry  soft deleted');
        $this->assertNull($ledgerOutboxEntity['deleted_at'], 'outbox entry  soft deleted');

        // check new source balance
        $newSourceMarketBalance = $this->getAccountBalance($sourceMID);
        $this->assertEquals($oldSourceMarketBalance, $newSourceMarketBalance, 'source balance not deducted');

        // check new destn balance
        $newDestnMarketBalance = $this->getAccountBalance($destnMID);
        $this->assertEquals($oldDestnMarketBalance, $newDestnMarketBalance, 'destn balance not deducted');
    }

    /* CRON test cases */

    public function testReverseShadowCronRetrySuccessForPaymentTransferProcessedEvent()
    {
        $this->assertNotNull($this->payment);

        $sourceMID = '10000000000000';
        $destnMID = '10000000000001';

        $this->assertNotNull($this->payment);

        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow']);
        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow'], $destnMID);

        $oldDestnMarketBalance = $this->getAccountBalance($destnMID);
        $this->assertEquals(0, $oldDestnMarketBalance);

        $oldSourceMarketBalance = $this->getAccountBalance($sourceMID);
        $this->assertGreaterThanOrEqual($this->payment['amount'],$oldSourceMarketBalance);

        $mockLedger = $this->initialiseLedger(1000000, 0, 0);

        // create transfer
        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 10000,
            'currency'=> 'INR',
        ];

        $content = $this->transferPayment($this->payment['id'], $transfers);

        $publicTransferId = $content['items'][0]['id'];

        $transferId =  str_replace('trf_', '', $publicTransferId);

        $transferPayment = $this->getDbEntity('payment',['transfer_id' => $transferId ] );
        $this->assertNull($transferPayment, 'dummy payment entity exist');

        $ledgerOutboxEntry = $this->getDbEntity('ledger_outbox',  ['payload_name' => $publicTransferId.'-transfer_processed']);
        $this->assertNotNull( $ledgerOutboxEntry);

        // setting outbox entry's created_at to an earlier timestamp so that cron fetches it
        $createdAtTimestamp = (int)((millitime()-3600000)/1000);
        $this->fixtures->edit('ledger_outbox', $ledgerOutboxEntry['id'], ['created_at' => $createdAtTimestamp]);

        $debitJID = 'LsqR14zUg9dbDB' ;
        $creditJID = 'LsqR157oYgCrCR';

        $journal = $this->getPaymentTransferJournalResponsePayload($publicTransferId, $debitJID, $creditJID, $sourceMID, $destnMID, $transfers[0]['amount']);

        $mockLedger->shouldReceive('createBulkJournal')
            ->times(1)
            ->andReturnValues([
                [
                    'code' => 200,
                    'body' => $journal,
                ],
            ]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/ledger_outbox/retry?type=transfer';
        $this->ba->cronAuth();
        $this->runRequestResponseFlow($testData);

        // fetch transfer again to check if txn id associated
        $transfer = $this->getDbEntity('transfer',  ['id' => $transferId]);
        $this->assertNotNull($transfer, 'transfer not found');
        $this->assertNotNull($transfer['transaction_id'], 'debit transaction not associated with transfer');
        $this->assertEquals('processed', $transfer['status'], 'transfer status not marked processed');
        $this->assertEquals('pending', $transfer['settlement_status'], 'transfer settlement status not marked processed');
        $this->assertEquals($debitJID, $transfer['transaction_id'], 'transfer txn_id not equal to debit journal_id');

        // fetch source_payment again to check if amount_transferred updated
        $sourcePayment = $this->getDbEntity('payment', ['id' => str_replace('pay_', '', $this->payment['id'])]);
        $newTransferPaymentEntity = $this->getLastEntity('transfer_payment', true);

        $this->assertNotNull($sourcePayment, 'source payment not found');
        $this->assertEquals($transfer['amount'], $newTransferPaymentEntity['amount_transferred'], 'amount_transferred incorrect in source_payment ');

        // fetch transfer payment again to check if txn id associated
        $transferPayment = $this->getDbEntity('payment', ['transfer_id' => $transferId]);
        $this->assertNotNull($transferPayment, 'transfer_payment not found');
        $this->assertEquals('captured', $transferPayment['status'], 'transfer_payment not captured');
        $this->assertEquals($creditJID, $transferPayment['transaction_id'], 'transfer_payment txn_id not equal to credit journal_id');

        // fetch transfer txn
        $transferTxn = $this->getDbEntity('transaction', ['type' => 'transfer', 'entity_id' => $transferId]);
        $this->assertNotNull($transferTxn, 'transfer_txn not found');
        $this->assertEquals($debitJID, $transferTxn['id'], 'transfer_txn_id does not match debit journalId');
        $this->assertNotNull($transferTxn['balance_id'], ' balance not updated in transfer_txn');
        $this->assertNotNull($transferTxn['debit'], 'amount not debited from transfer Txn');

        $debitAMount = $transferTxn['debit'];

        // fetch transfer_payment txn
        $transferPaymentTxn = $this->getDbEntity('transaction', ['type' => 'payment', 'entity_id' => $transferPayment['id']]);
        $this->assertNotNull($transferPaymentTxn, 'transfer_payment_txn not found');
        $this->assertEquals($creditJID, $transferPaymentTxn['id'], 'transfer_payment_txn_id does not match credit journal_id');
        $this->assertNotNull($transferPaymentTxn['balance_id'], 'balance not updated in transfer_payment_txn');
        $this->assertNotNull($transferPaymentTxn['credit'], 'amount not credited from transfer_payment_txn');
        $this->assertEquals($debitAMount, $transferPaymentTxn['credit'], 'debit amount not equal to credit amount');

        // fetch  outbox entry
        $ledgerOutboxEntities = $this->getTrashedDbEntities('ledger_outbox', ['payload_name' => $publicTransferId.'-'.'transfer_processed']);
        $this->assertCount(1,$ledgerOutboxEntities, ' ledger_outbox entry for transfer_processed event not found');
        $this->assertEquals( $ledgerOutboxEntities[0]['is_deleted'], 1, 'outbox entry not soft deleted');
        $this->assertNotNull( $ledgerOutboxEntities[0]['deleted_at'], 'outbox entry not soft deleted');

        // check new source balance
        $newSourceMarketBalance = $this->getAccountBalance($sourceMID);
        $this->assertEquals($oldSourceMarketBalance - $transfer->getAmount(), $newSourceMarketBalance, 'source balance not deeducted');

        // check new destn balance
        $newDestnMarketBalance = $this->getAccountBalance($destnMID);
        $this->assertEquals($oldDestnMarketBalance + $transfer->getAmount(), $newDestnMarketBalance, 'destn balance not deeducted');

    }

    public function testReverseShadowCronRetryRetryableFailureForPaymentTransferProcessedEvent()
    {
        $this->assertNotNull($this->payment);

        $sourceMID = '10000000000000';
        $destnMID = '10000000000001';

        $this->assertNotNull($this->payment);

        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow']);
        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow'], $destnMID);

        $oldDestnMarketBalance = $this->getAccountBalance($destnMID);
        $this->assertEquals(0, $oldDestnMarketBalance);

        $oldSourceMarketBalance = $this->getAccountBalance($sourceMID);
        $this->assertGreaterThanOrEqual($this->payment['amount'],$oldSourceMarketBalance);

        $mockLedger = $this->initialiseLedger(1000000, 0, 0);

        // create transfer
        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 10000,
            'currency'=> 'INR',
        ];

        $content = $this->transferPayment($this->payment['id'], $transfers);

        $publicTransferId = $content['items'][0]['id'];

        $transferId =  str_replace('trf_', '', $publicTransferId);

        $transferPayment = $this->getDbEntity('payment',['transfer_id' => $transferId ] );
        $this->assertNull($transferPayment, 'dummy payment entity exist');

        $ledgerOutboxEntry = $this->getDbEntity('ledger_outbox',  ['payload_name' => $publicTransferId.'-transfer_processed']);
        $this->assertNotNull( $ledgerOutboxEntry);

        // setting outbox entry's created_at to an earlier timestamp so that cron fetches it
        $createdAtTimestamp = (int)((millitime()-3600000)/1000);
        $this->fixtures->edit('ledger_outbox', $ledgerOutboxEntry['id'], ['created_at' => $createdAtTimestamp]);

        $debitJID = 'LsqR14zUg9dbDB' ;
        $creditJID = 'LsqR157oYgCrCR';

        $mockLedger->shouldReceive('createBulkJournal')
            ->once()
            ->andThrow(new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                [],
                "insufficient_balance_failure: BAD_REQUEST_INSUFFICIENT_BALANCE"
            ));


        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/ledger_outbox/retry?type=transfer';
        $this->ba->cronAuth();
        $this->runRequestResponseFlow($testData);

        // fetch transfer again to check if txn id associated
        $transfer = $this->getDbEntity('transfer',  ['id' => $transferId]);
        $this->assertNotNull($transfer, 'transfer not found');
        $this->assertNull($transfer['transaction_id'], 'debit transaction not associated with transfer');
        $this->assertNotEquals('processed', $transfer['status'], 'transfer status not marked processed');

        // fetch source_payment again to check if amount_transferred updated
        $sourcePayment = $this->getDbEntity('payment', ['id' => str_replace('pay_', '', $this->payment['id'])]);
        $this->assertNotNull($sourcePayment, 'source payment not found');
        $this->assertEquals(0, $sourcePayment['amount_transferred'], 'amount_transferred incorrect in source_payment ');

        // fetch transfer payment again to check if txn id associated
        $transferPayment = $this->getDbEntity('payment', ['transfer_id' => $transferId]);
        $this->assertNull($transferPayment, 'transfer_payment not found');

        // fetch  outbox entry
        $ledgerOutboxEntities = $this->getDbEntities('ledger_outbox', ['payload_name' => $publicTransferId.'-'.'transfer_processed']);
        $this->assertCount(1,$ledgerOutboxEntities, ' ledger_outbox entry for transfer_processed event not found');
        $this->assertEquals( $ledgerOutboxEntities[0]['retry_count'], 1, 'outbox entry retry count not updated');
        $this->assertEquals( $ledgerOutboxEntities[0]['is_deleted'], 0, 'outbox entry soft deleted');
        $this->assertNull( $ledgerOutboxEntities[0]['deleted_at'], 'outbox entry soft deleted');

        // check new source balance
        $newSourceMarketBalance = $this->getAccountBalance($sourceMID);
        $this->assertEquals($oldSourceMarketBalance, $newSourceMarketBalance, 'source balance not deeducted');

        // check new destn balance
        $newDestnMarketBalance = $this->getAccountBalance($destnMID);
        $this->assertEquals($oldDestnMarketBalance, $newDestnMarketBalance, 'destn balance not deeducted');

    }

    public function testReverseShadowCronRetryTransferFailsDueToInsufficientBalanceAfterMaxRetry()
    {
        $this->assertNotNull($this->payment);

        $sourceMID = '10000000000000';
        $destnMID = '10000000000001';

        $this->assertNotNull($this->payment);

        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow']);
        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow'], $destnMID);

        $oldDestnMarketBalance = $this->getAccountBalance($destnMID);
        $this->assertEquals(0, $oldDestnMarketBalance);

        $oldSourceMarketBalance = $this->getAccountBalance($sourceMID);
        $this->assertGreaterThanOrEqual($this->payment['amount'],$oldSourceMarketBalance);

        $mockLedger = $this->initialiseLedger(1000000, 0, 0);

        // create transfer
        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 10000,
            'currency'=> 'INR',
        ];

        $content = $this->transferPayment($this->payment['id'], $transfers);

        $publicTransferId = $content['items'][0]['id'];

        $transferId =  str_replace('trf_', '', $publicTransferId);

        $transferPayment = $this->getDbEntity('payment', ['transfer_id' => $transferId]);
        $this->assertNull($transferPayment, 'transfer_payment  found');

        $ledgerOutboxEntry = $this->getDbEntity('ledger_outbox',  ['payload_name' => $publicTransferId.'-transfer_processed']);
        $this->assertNotNull( $ledgerOutboxEntry);

        // setting outbox entry's:
        //      - created_at to an earlier timestamp so that cron fetches it
        //      - retry_count to max retry count - 1
        $createdAtTimestamp = (int)((millitime()-3600000)/1000);
        $this->fixtures->edit('ledger_outbox', $ledgerOutboxEntry['id'], [
            'created_at' => $createdAtTimestamp,
            'retry_count' => ReverseShadow\Constants::MAX_RETRY_COUNT_CRON - 1
        ]);

        $debitJID = 'LsqR14zUg9dbDB' ;
        $creditJID = 'LsqR157oYgCrCR';

        $mockLedger->shouldReceive('createBulkJournal')
            ->once()
            ->andThrow(new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                [],
                "insufficient_balance_failure: BAD_REQUEST_INSUFFICIENT_BALANCE"
            ));


        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/ledger_outbox/retry?type=transfer';
        $this->ba->cronAuth();
        $this->runRequestResponseFlow($testData);

        // fetch transfer again to validate status as failed
        $transfer = $this->getDbEntity('transfer',  ['id' => $transferId]);
        $this->assertNotNull($transfer, 'transfer not found');
        $this->assertEquals('failed', $transfer['status'], 'transfer status not marked failed');

        // fetch transfer payment to check if txn id associated
        $transferPayment = $this->getDbEntity('payment', ['transfer_id' => $transferId]);
        $this->assertNull($transferPayment, 'transfer_payment  found');

        // fetch  outbox entry
        $ledgerOutboxEntities = $this->getDbEntities('ledger_outbox', ['payload_name' => $publicTransferId.'-'.'transfer_processed']);
        $this->assertCount(0 ,$ledgerOutboxEntities, ' ledger_outbox entry for transfer_processed event found');

        // check new source balance
        $newSourceMarketBalance = $this->getAccountBalance($sourceMID);
        $this->assertEquals($oldSourceMarketBalance, $newSourceMarketBalance, 'source balance not deducted');

        // check new destn balance
        $newDestnMarketBalance = $this->getAccountBalance($destnMID);
        $this->assertEquals($oldDestnMarketBalance, $newDestnMarketBalance, 'destn balance not deducted');
    }

    public function testPendingTransferCronProcessingAfterReverseShadowProcessingIsInitiatedAndMerchantOffboarded()
    {
        $this->assertNotNull($this->payment);

        $sourceMID = '10000000000000';
        $destnMID = '10000000000001';

        $this->assertNotNull($this->payment);

        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow']);
        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow'], $destnMID);

        $oldDestnMarketBalance = $this->getAccountBalance($destnMID);
        $this->assertEquals(0, $oldDestnMarketBalance);

        $oldSourceMarketBalance = $this->getAccountBalance($sourceMID);
        $this->assertGreaterThanOrEqual($this->payment['amount'],$oldSourceMarketBalance);

        $mockLedger = $this->initialiseLedger(1000000, 0, 0);

        // create transfer
        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 10000,
            'currency'=> 'INR',
        ];

        $content = $this->transferPayment($this->payment['id'], $transfers);

        $publicTransferId = $content['items'][0]['id'];

        $transferId =  str_replace('trf_', '', $publicTransferId);

        $ledgerOutboxEntry = $this->getDbEntity('ledger_outbox',  ['payload_name' => $publicTransferId.'-transfer_processed']);
        $this->assertNotNull( $ledgerOutboxEntry);

        $transferPayments = $this->getDbEntities('payment', ['transfer_id' => $transferId]);
        $this->assertCount(0, $transferPayments);

        $debitJID = 'LsqR14zUg9dbDB' ;
        $creditJID = 'LsqR157oYgCrCR';

        // offboard merchant from reverse shadow
        $this->fixtures->merchant->removeFeatures(['pg_ledger_reverse_shadow']);

        $this->fixtures->edit('transfer', $transferId, [
            'created_at' => Carbon::now()->subMinutes(10)->getTimestamp(),
            'updated_at' => Carbon::now()->subMinutes(10)->getTimestamp(),
        ]);

        // process transfer via pending transfer cron
        $this->ba->cronAuth();
        $request  = [
            'method'    => 'POST',
            'url'       => '/payment_transfers/process_pending?minutes=1',
            'content'   => [

            ],
        ];
        $response = $this->makeRequestAndGetContent($request);
        $this->assertNotNull($response);

        $transfer = $this->getDbEntity('transfer',  ['id' => $transferId]);
        $this->assertNotNull($transfer, 'transfer not found');
        $this->assertEquals('processed', $transfer['status'], 'transfer status not marked processed');

        $transferPayments = $this->getDbEntities('payment', ['transfer_id' => $transferId]);
        $this->assertCount(1, $transferPayments);
    }

    public function testReverseShadowCronRetryNonRetryableFailureForPaymentTransferProcessedEvent()
    {
        $this->assertNotNull($this->payment);

        $sourceMID = '10000000000000';
        $destnMID = '10000000000001';

        $this->assertNotNull($this->payment);

        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow']);
        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow'], $destnMID);

        $oldDestnMarketBalance = $this->getAccountBalance($destnMID);
        $this->assertEquals(0, $oldDestnMarketBalance);

        $oldSourceMarketBalance = $this->getAccountBalance($sourceMID);
        $this->assertGreaterThanOrEqual($this->payment['amount'],$oldSourceMarketBalance);

        $mockLedger = $this->initialiseLedger(1000000, 0, 0);

        // create transfer
        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 10000,
            'currency'=> 'INR',
        ];

        $content = $this->transferPayment($this->payment['id'], $transfers);

        $publicTransferId = $content['items'][0]['id'];

        $transferId =  str_replace('trf_', '', $publicTransferId);

        $transferPayment = $this->getDbEntity('payment', ['transfer_id' => $transferId]);
        $this->assertNull($transferPayment, 'transfer_payment  found');

        $ledgerOutboxEntry = $this->getDbEntity('ledger_outbox',  ['payload_name' => $publicTransferId.'-transfer_processed']);
        $this->assertNotNull( $ledgerOutboxEntry);

        // setting outbox entry's created_at to an earlier timestamp so that cron fetches it
        $createdAtTimestamp = (int)((millitime()-3600000)/1000);
        $this->fixtures->edit('ledger_outbox', $ledgerOutboxEntry['id'], ['created_at' => $createdAtTimestamp]);

        $debitJID = 'LsqR14zUg9dbDB' ;
        $creditJID = 'LsqR157oYgCrCR';

        $journal = $this->getPaymentTransferJournalResponsePayload($publicTransferId, $debitJID, $creditJID, $sourceMID, $destnMID, $transfers[0]['amount']);

        $mockLedger->shouldReceive('createBulkJournal')
            ->once()
            ->andThrow(new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                [],
                "validation_failure: validation_failure: BAD_REQUEST_VALIDATION_FAILURE"
            ));


        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/ledger_outbox/retry?type=transfer';
        $this->ba->cronAuth();
        $this->runRequestResponseFlow($testData);

        // fetch transfer again to check if txn id associated
        $transfer = $this->getDbEntity('transfer',  ['id' => $transferId]);
        $this->assertNotNull($transfer, 'transfer not found');
        $this->assertNull($transfer['transaction_id'], 'debit transaction not associated with transfer');
        $this->assertNotEquals('processed', $transfer['status'], 'transfer status not marked processed');

        // fetch source_payment again to check if amount_transferred updated
        $sourcePayment = $this->getDbEntity('payment', ['id' => str_replace('pay_', '', $this->payment['id'])]);
        $this->assertNotNull($sourcePayment, 'source payment not found');
        $this->assertEquals(0, $sourcePayment['amount_transferred'], 'amount_transferred incorrect in source_payment ');

        // fetch transfer payment again to check if txn id associated
        $transferPayment = $this->getDbEntity('payment', ['transfer_id' => $transferId]);
        $this->assertNull($transferPayment, 'transfer_payment not found');

        // fetch  outbox entry
        $ledgerOutboxEntities = $this->getTrashedDbEntities('ledger_outbox', ['payload_name' => $publicTransferId.'-'.'transfer_processed']);
        $this->assertCount(1,$ledgerOutboxEntities, ' ledger_outbox entry for transfer_processed event not found');
        $this->assertEquals( $ledgerOutboxEntities[0]['retry_count'], 1, 'outbox entry retry count not updated');
        $this->assertEquals( $ledgerOutboxEntities[0]['is_deleted'], 1, 'outbox entry not soft deleted');
        $this->assertNotNull( $ledgerOutboxEntities[0]['deleted_at'], 'outbox entry not soft deleted');

        // check new source balance
        $newSourceMarketBalance = $this->getAccountBalance($sourceMID);
        $this->assertEquals($oldSourceMarketBalance, $newSourceMarketBalance, 'source balance not deeducted');

        // check new destn balance
        $newDestnMarketBalance = $this->getAccountBalance($destnMID);
        $this->assertEquals($oldDestnMarketBalance, $newDestnMarketBalance, 'destn balance not deeducted');

    }

    public function testCronCreateMissingTransactionForTransfers()
    {
        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow']);

        $order = $this->fixtures->order->create(['receipt' => 'check123', 'bank' => 'ICICI', 'account_number' => '0040304030403040', 'amount' => '50000', 'status' => 'paid']);

        $this->fixtures->edit('payment', $this->payment['id'], ['order_id' => $order['id']]);

        $dummyTransferData = [
            'id'                 => "AnyRandomID123",
            'source_id'          => $order['id'],
            'source_type'        => "order",
            'status'             => "processed",
            'settlement_status'  => NULL,
            'to_id'              => 10000000000001,
            'to_type'            => "merchant",
            'amount'             => 50000,
            'currency'           => "INR",
            'amount_reversed'    => 0,
            'created_at'         => Carbon::now()->addHours(-5)->getTimestamp(),
            'updated_at'         => Carbon::now()->addHours(-4)->getTimestamp(),
            'processed_at'       => Carbon::now()->addHours(-4)->getTimestamp(),
        ];

        $this->fixtures->transfer->create($dummyTransferData);

        $this->fixtures->payment->create(
            [
                'id'          => 'dummyN3uSlFkHT',
                'merchant_id' => '10000000000001',
                'transfer_id' => 'AnyRandomID123',
                'amount'      => 50000,
                'currency'    => 'INR',
                'method'      => 'transfer',
                'status'      => 'captured',
                'captured_at' => Carbon::now(Timezone::IST)->getTimestamp(),
                'fee'         => 0,
                'tax'         => 0,
            ]
        );

        $transfer = $this->getLastEntity('transfer', true);

        $this->assertEquals('processed', $transfer['status']);
        $this->assertNull($transfer['transaction_id']);

        $data = $this->testData[__FUNCTION__];
        $this->ba->cronAuth();
        $mockLedger = \Mockery::mock(Ledger::class)->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $sourceMID = '10000000000000';
        $debitJID = 'LsqR14zUg9dbDB' ;
        $creditJID = 'LsqR157oYgCrCR';
        $debitJournal = $this->getDebitJournalForTransfer($dummyTransferData['id'], $debitJID, $creditJID, $sourceMID, $dummyTransferData['to_id'], $dummyTransferData['amount']);
        $creditJournal = $this->getCreditJournalForTransfer($dummyTransferData['id'], $debitJID, $creditJID, $sourceMID, $dummyTransferData['to_id'], $dummyTransferData['amount']);

        $mockLedger->expects('fetchByTransactor')
            ->times(1)
            ->andReturns($debitJournal);

        $mockLedger->expects('fetchByTransactor')
            ->times(1)
            ->andReturns($creditJournal);

        $transferIds = $this->runRequestResponseFlow($data);

        $transfer = $this->getDbEntity('transfer', ['id' => "AnyRandomID123"]);
        $dummyPayment = $this->getDbEntity('payment', ['transfer_id' => $transfer['id']]);
        $dummyPaymentTxn = $this->getDbEntity('transaction', ['type' => 'payment', 'entity_id' => $dummyPayment['id']]);

        $this->assertEquals($transfer['transaction_id'], $debitJID);
        $this->assertEquals($dummyTransferData['id'], $transferIds[0]);
        $this->assertEquals($dummyPayment['transaction_id'], $creditJID);
        $this->assertEquals($dummyPaymentTxn['id'], $dummyPayment['transaction_id']);
    }

    /* Partial feature check */

    public function testFeatureEnabledForParentButNotChildTransferReverseShadowOutboxPushFailure()
    {
        $this->assertNotNull($this->payment);

        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow']);

        $this->assertEquals(0, $this->getAccountBalance('10000000000001'));

        $oldMarketBalance = $this->getAccountBalance('10000000000000');
        $this->assertGreaterThanOrEqual($this->payment['amount'], $oldMarketBalance);

        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount' => 50000,
            'currency' => 'INR',
        ];

        $expected = [
            'count' => 1,
            'items' => [
                [
                    'source' => $this->payment['id'],
                    'recipient' => 'acc_10000000000001',
                    'amount' => 50000,
                    'amount_reversed' => 0,
                    'status' => 'pending',
                ],
            ],
        ];

        $content = $this->transferPayment($this->payment['id'], $transfers);
        $this->assertNotNull($content);
        $this->assertArraySelectiveEquals($expected, $content);
        $this->assertCount(1, $content['items']);

        $transferResponse = $content['items'][0];
        $transferId = $transferResponse['id'];

        // transfer entity exists
        $transfer = $this->getDbLastEntity('transfer');
        $this->assertNotNull($transfer);
        $this->assertEquals($transferId, sprintf('trf_%s', $transfer['id']));

        // dummy payment entity exists
        $transferPayment = $this->getDbEntity('payment', ['transfer_id' => $transfer['id']]);
        $this->assertNotNull($transferPayment);
        $this->assertEquals('transfer', $transferPayment['method']);

        // transfer txn and dummy payment txn  created in non reverse shadow
        $this->assertNotNull($transfer['transaction_id']);
        $this->assertNotNull($transferPayment['transaction_id']);
        $transferTxn = $this->getDbEntityById('transaction', $transfer['transaction_id']);
        $this->assertNotNull($transferTxn);

        $transferPaymentTxn = $this->getDbEntityById('transaction', $transferPayment['transaction_id']);
        $this->assertNotNull($transferPaymentTxn);

        // fetch transfer journal payload from ledger_outbox
        $ledgerOutboxEntity = $this->getDbLastEntity('ledger_outbox');
        $this->assertNull($ledgerOutboxEntity);
    }

    public function testReverseShadowCronRetryForPaymentTransferProcessedEventFailureWrongRoute()
    {
        $this->assertNotNull($this->payment);

        $sourceMID = '10000000000000';
        $destnMID = '10000000000001';

        $this->assertNotNull($this->payment);

        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow']);
        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow'], $destnMID);

        $oldDestnMarketBalance = $this->getAccountBalance($destnMID);
        $this->assertEquals(0, $oldDestnMarketBalance);

        $oldSourceMarketBalance = $this->getAccountBalance($sourceMID);
        $this->assertGreaterThanOrEqual($this->payment['amount'],$oldSourceMarketBalance);

        $mockLedger = $this->initialiseLedger(1000000, 0, 0);

        // create transfer
        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 10000,
            'currency'=> 'INR',
        ];

        $content = $this->transferPayment($this->payment['id'], $transfers);

        $publicTransferId = $content['items'][0]['id'];

        $transferId =  str_replace('trf_', '', $publicTransferId);

        $transferPayment = $this->getDbEntity('payment',['transfer_id' => $transferId ] );
        $this->assertNull($transferPayment, 'dummy payment entity exist');

        $ledgerOutboxEntry = $this->getDbEntity('ledger_outbox',  ['payload_name' => $publicTransferId.'-transfer_processed']);
        $this->assertNotNull( $ledgerOutboxEntry);

        // setting outbox entry's created_at to an earlier timestamp so that cron fetches it
        $createdAtTimestamp = (int)((millitime()-3600000)/1000);
        $this->fixtures->edit('ledger_outbox', $ledgerOutboxEntry['id'], ['created_at' => $createdAtTimestamp]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/ledger_outbox/retry';
        $this->ba->cronAuth();
        $this->runRequestResponseFlow($testData);

    }

    public function testPaymentTransferNotProcessedByPendingTransferCronInReverseShadowOutbox()
    {
        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow']);
        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow'], 10000000000001);

        $transferId = "AnyRandomID123";

        $paymentId = Payment\Entity::verifyIdAndSilentlyStripSign($this->payment['id']);

        $dummyTransferData = [
            'id'                 => $transferId,
            'source_id'          => $paymentId,
            'source_type'        => "payment",
            'status'             => "pending",
            'settlement_status'  => NULL,
            'to_id'              => 10000000000001,
            'to_type'            => "merchant",
            'amount'             => 50000,
            'currency'           => "INR",
            'amount_reversed'    => 0,
            'created_at'         => Carbon::now()->addHours(-5)->getTimestamp(),
            'updated_at'         => Carbon::now()->addHours(-4)->getTimestamp(),
            'processed_at'       => Carbon::now()->addHours(-4)->getTimestamp(),
        ];

        $this->fixtures->transfer->create($dummyTransferData);

        // transfer entity exists
        $transfer = $this->getDbLastEntity('transfer');
        $this->assertNotNull($transfer);
        $this->assertEquals($transferId, $transfer['id']);
        $this->assertEquals('pending', $transfer['status']);

        $transferPayment = $this->getDbEntity('payment',['transfer_id' => $transferId ] );
        $this->assertNull($transferPayment, 'dummy payment entity exist');

        // process transfer via pending transfer cron
        $this->ba->cronAuth();
        $request  = [
            'method'    => 'POST',
            'url'       => '/payment_transfers/process_pending?minutes=1',
            'content'   => [

            ],
        ];
        $response = $this->makeRequestAndGetContent($request);
        $this->assertNotNull($response);


        // fetch transfer journal payload from ledger_outbox
        $ledgerOutboxEntity = $this->getDbLastEntity('ledger_outbox');
        $this->assertNull($ledgerOutboxEntity);

        // check transfer is not processed
        $transfer = $this->getDbLastEntity('transfer');
        $this->assertEquals($transferId, $transfer['id']);
        $this->assertEquals('pending', $transfer['status']);

        $transferPayment = $this->getDbEntity('payment',['transfer_id' => $transferId ] );
        $this->assertNull($transferPayment, 'dummy payment entity exist');
    }

    public function testTransferProcessingFailsInReverseShadowIfTransferAmountGreaterThanUntransferredAmount()
    {
        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow']);
        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow'], 10000000000001);

        $transferId = "AnyRandomID123";

        $paymentId = Payment\Entity::verifyIdAndSilentlyStripSign($this->payment['id']);

        $dummyTransferData = [
            'id'                 => $transferId,
            'source_id'          => $paymentId,
            'source_type'        => "payment",
            'status'             => "pending",
            'settlement_status'  => NULL,
            'to_id'              => 10000000000001,
            'to_type'            => "merchant",
            'amount'             => 500000,
            'currency'           => "INR",
            'amount_reversed'    => 0,
            'created_at'         => Carbon::now()->addHours(-5)->getTimestamp(),
            'updated_at'         => Carbon::now()->addHours(-4)->getTimestamp(),
            'processed_at'       => Carbon::now()->addHours(-4)->getTimestamp(),
        ];

        $this->fixtures->transfer->create($dummyTransferData);

        // process transfer
        (new TransferProcess('test', $this->payment['id'], 'payment'))->handle();

        $transferPayment = $this->getDbEntity('payment',['transfer_id' => $transferId ] );
        $this->assertNull($transferPayment, 'dummy payment entity exist');

        // fetch transfer journal payload from ledger_outbox
        $ledgerOutboxEntity = $this->getDbLastEntity('ledger_outbox');
        $this->assertNull($ledgerOutboxEntity);

        // check transfer is not failed
        $transfer = $this->getDbLastEntity('transfer');
        $this->assertEquals($transferId, $transfer['id']);
        $this->assertEquals('failed', $transfer['status']);
        $this->assertEquals('BAD_REQUEST_ERROR', $transfer['error_code']);
        $this->assertEquals('Transfer amount should be less than or equal to amount not transferred yet', $transfer['message']);
        $this->assertNull($transfer['transaction_id']);
    }

    public function testTransferProcessingNotReInitiatedWhenTransferIsFailedInReverseShadow()
    {
        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow']);
        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow'], 10000000000001);

        $transferId = "AnyRandomID123";

        $paymentId = Payment\Entity::verifyIdAndSilentlyStripSign($this->payment['id']);

        $dummyTransferData = [
            'id'                 => $transferId,
            'source_id'          => $paymentId,
            'source_type'        => "payment",
            'status'             => "failed",
            'attempts'           => 1,
            'settlement_status'  => NULL,
            'to_id'              => 10000000000001,
            'to_type'            => "merchant",
            'amount'             => 50000,
            'currency'           => "INR",
            'amount_reversed'    => 0,
            'created_at'         => Carbon::now()->addHours(-5)->getTimestamp(),
            'updated_at'         => Carbon::now()->addHours(-4)->getTimestamp(),
            'processed_at'       => Carbon::now()->addHours(-4)->getTimestamp(),
        ];

        $this->fixtures->transfer->create($dummyTransferData);

        // process transfer
        (new TransferProcess('test', $this->payment['id'], 'payment'))->handle();

        $transferPayment = $this->getDbEntity('payment',['transfer_id' => $transferId ] );
        $this->assertNull($transferPayment, 'dummy payment entity exist');

        // fetch transfer journal payload from ledger_outbox
        $ledgerOutboxEntity = $this->getDbLastEntity('ledger_outbox');
        $this->assertNull($ledgerOutboxEntity);

        // check transfer is failed and attempts not incremented
        $transfer = $this->getDbLastEntity('transfer');
        $this->assertEquals($transferId, $transfer['id']);
        $this->assertEquals('failed', $transfer['status']);
        $this->assertEquals(1, $transfer['attempts']);
    }

    public function testEarlyDispatchOfTxnsInReverseShadowUsingLedgerJournal()
    {
        $sourceMID = '10000000000000';
        $destnMID = '10000000000001';

        $this->assertNotNull($this->payment);

        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow']);
        $this->fixtures->merchant->addFeatures(['marketplace', 'pg_ledger_reverse_shadow'], $destnMID);

        $oldDestnMarketBalance = $this->getAccountBalance($destnMID);
        $this->assertEquals(0, $oldDestnMarketBalance);

        $oldSourceMarketBalance = $this->getAccountBalance($sourceMID);
        $this->assertGreaterThanOrEqual($this->payment['amount'],$oldSourceMarketBalance);

        $this->initialiseLedger(1000000, 0, 0);

        // create transfer
        $transfers[0] = [
            'account' => 'acc_10000000000001',
            'amount'  => 50000,
            'currency'=> 'INR',
        ];

        $content = $this->transferPayment($this->payment['id'], $transfers);

        $this->assertNotNull($content);

        $this->assertEquals($transfers[0]["amount"], $content['items'][0]["amount"]);

        $publicTransferId = $content['items'][0]['id'];

        $transferId =  str_replace('trf_', '', $publicTransferId);

        $transfer = $this->getDbEntity('transfer', ['id'=>$transferId]);
        $this->assertNotNull($transfer, 'transfer entity does nit exist');
        $this->assertNull($transfer['transaction_id'], 'transfer txn created in sync');

        // create dummy payment
        $this->fixtures->payment->create(
            [
                'id'          => 'dummyN3uSlFkHT',
                'merchant_id' => '10000000000001',
                'transfer_id' => $transferId,
                'amount'      => 50000,
                'currency'    => 'INR',
                'method'      => 'transfer',
                'status'      => 'captured',
                'captured_at' => Carbon::now(Timezone::IST)->getTimestamp(),
                'fee'         => 0,
                'tax'         => 0,
            ]
        );

        $debitTxnPayload = [
            'id'                    => 'LsqR14zUg9dbDB',
            'merchant_id'           => '10000000000000',
            'source_id'             => $transferId,
            'source_type'           => 'transfer',
            'balance_type'          => 'PRIMARY',
            'currency'              => 'INR',
            'credit'                => 0,
            'debit'                 => 50000,
            'fee'                   => 0,
            'tax'                   => 0,
            'settled_by'            => 'Razorpay',
            'on_hold'               => null,
            'on_hold_reason'        => '',
            'meta'                  => [
                'source_type'    => 'payment',
                'source_id'      => explode('_', $this->payment['id'])[1],
                'source_method'  => 'card',
                'source_settled' => false,
                'international'  => false,
            ]
        ];

        $creditTxnPayload = [
            'id'                    => 'LsqR157oYgCrCR',
            'merchant_id'           => '10000000000001',
            'source_id'             => 'dummyN3uSlFkHT',
            'source_type'           => 'payment',
            'balance_type'          => 'PRIMARY',
            'currency'              => 'INR',
            'credit'                => 50000,
            'debit'                 => 0,
            'fee'                   => 0,
            'tax'                   => 0,
            'settled_by'            => 'Razorpay',
            'on_hold'               => false,
            'on_hold_reason'        => '',
            'meta'                  => [
                'method'        => 'transfer',
                'origin_method' => 'card',
                'international' => false,
            ]
        ];

        $this->mockSns($debitTxnPayload, $creditTxnPayload);

        $debitJID = 'LsqR14zUg9dbDB' ;
        $creditJID = 'LsqR157oYgCrCR';

        $journal = $this->getPaymentTransferJournalResponsePayload($publicTransferId, $debitJID, $creditJID, $sourceMID, $destnMID, $transfers[0]['amount']);

        $kafkaEventPayload = $this->getKafkaEventPayload($journal);

        // run test
        (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_PG_LEDGER_ACKNOWLEDGMENTS, $kafkaEventPayload, 'test');

        // fetch transfer again to check if txn id associated
        $transfer = $this->getDbEntity('transfer',  ['id' => $transferId]);
        $this->assertNotNull($transfer, 'transfer not found');
        $this->assertNotNull($transfer['transaction_id'], 'debit transaction not associated with transfer');
        $this->assertEquals('processed', $transfer['status'], 'transfer status not marked processed');
        $this->assertEquals('pending', $transfer['settlement_status'], 'transfer settlement status not marked processed');
        $this->assertEquals($debitJID, $transfer['transaction_id'], 'transfer txn_id not equal to debit journal_id');

        // fetch source_payment again to check if amount_transferred updated
        $sourcePayment = $this->getDbEntity('payment', ['id' => str_replace('pay_', '', $this->payment['id'])]);
        $newTransferPaymentEntity = $this->getLastEntity('transfer_payment', true);
        $this->assertNotNull($sourcePayment, 'source payment not found');
        $this->assertEquals($transfer['amount'], $newTransferPaymentEntity['amount_transferred'], 'amount_transferred incorrect in source_payment ');

        // fetch transfer payment again to check if txn id associated
        $transferPayment = $this->getDbEntity('payment', ['id' => 'dummyN3uSlFkHT']);
        $this->assertNotNull($transferPayment, 'transfer_payment not found');
        $this->assertEquals('captured', $transferPayment['status'], 'transfer_payment not captured');
        $this->assertEquals($creditJID, $transferPayment['transaction_id'], 'transfer_payment txn_id not equal to credit journal_id');

        // fetch transfer txn
        $transferTxn = $this->getDbEntity('transaction', ['type' => 'transfer', 'entity_id' => $transferId]);
        $this->assertNotNull($transferTxn, 'transfer_txn not found');
        $this->assertEquals($debitJID, $transferTxn['id'], 'transfer_txn_id does not match debit journalId');
        $this->assertNotNull($transferTxn['balance_id'], ' balance not updated in transfer_txn');
        $this->assertNotNull($transferTxn['debit'], 'amount not debited from transfer Txn');
        $this->assertEquals($transfer['amount'], $transferTxn['amount'], 'amount not matching with transfer amount');

        $debitAMount = $transferTxn['debit'];

        // fetch transfer_payment txn
        $transferPaymentTxn = $this->getDbEntity('transaction', ['type' => 'payment', 'entity_id' => $transferPayment['id']]);
        $this->assertNotNull($transferPaymentTxn, 'transfer_payment_txn not found');
        $this->assertEquals($creditJID, $transferPaymentTxn['id'], 'transfer_payment_txn_id does not match credit journal_id');
        $this->assertNotNull($transferPaymentTxn['balance_id'], 'balance not updated in transfer_payment_txn');
        $this->assertNotNull($transferPaymentTxn['credit'], 'amount not credited from transfer_payment_txn');
        $this->assertEquals($debitAMount, $transferPaymentTxn['credit'], 'debit amount not equal to credit amount');

        // fetch  outbox entry
        $ledgerOutboxEntities = $this->getTrashedDbEntities('ledger_outbox', ['payload_name' => $publicTransferId.'-'.'transfer_processed']);
        $this->assertCount(1,$ledgerOutboxEntities, ' ledger_outbox entry for transfer_processed event not found');
        $this->assertEquals( $ledgerOutboxEntities[0]['is_deleted'], 1, 'outbox entry not soft deleted');
        $this->assertNotNull( $ledgerOutboxEntities[0]['deleted_at'], 'outbox entry not soft deleted');

        // check new source balance
        $newSourceMarketBalance = $this->getAccountBalance($sourceMID);
        $this->assertEquals($oldSourceMarketBalance - $transfer->getAmount(), $newSourceMarketBalance, 'source balance not deeducted');

        // check new destn balance
        $newDestnMarketBalance = $this->getAccountBalance($destnMID);
        $this->assertEquals($oldDestnMarketBalance + $transfer->getAmount(), $newDestnMarketBalance, 'destn balance not deeducted');
    }
}
