<?php

namespace RZP\Tests\Functional\Adjustment;

use Mail;
use Queue;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\RuntimeException;
use RZP\Models\Adjustment\Status;
use RZP\Services\KafkaProducerClient;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Mail\Banking\YesbankLoadViaAdjustment;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;

class AdjustmentLedgerTest extends TestCase
{
    use DbEntityFetchTrait;
    use TestsWebhookEvents;
    use TestsBusinessBanking;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/AdjustmentLedgerTestData.php';

        parent::setUp();

        $this->createFixtures();

    }

    private function createFixtures()
    {
        $merchantId = '100abc000abc00';

        $this->fixtures->create('merchant', ['id' => $merchantId, 'email' => 'mahbubani.amit@gmail.com']);

        $admin = $this->ba->getAdmin();

        $this->fixtures->admin->edit($admin['id'], ['allow_all_merchants' => true]);

        $this->ba->adminProxyAuth($merchantId, 'rzp_test_'.$merchantId);
    }


    public function testManualAdjustmentCreateSuccess()
    {
        Mail::fake();

        $this->app['config']->set('applications.ledger.enabled', true);
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow'], '100abc000abc00');

        $this->fixtures->create(
            'balance',
            [
                'id'            => '100def000def00',
                'balance'       => 1000,
                'type'          => 'primary',
                'merchant_id'   => '100abc000abc00'
            ]
        );

        $this->ba->adminAuth();

        $expectedJournalPayload = [
            "merchant_id"           => "100abc000abc00",
            "currency"              => "INR",
            "transaction_date"      => "", // any
            "transactor_id"         => "", // any
            "transactor_event"      => "positive_adjustment",
            "money_params"          => [
                "merchant_balance_amount"   => "500",
                "base_amount"               => "500",
                "adjustment_amount"         => "500"
            ]
        ];

        $mockLedger->shouldReceive('createJournal')
        ->times(1)
        ->withArgs(function($journalPayload, $requestHeaders, $throwException) use ($expectedJournalPayload) {

            $this->assertArrayHasKey('transaction_date',$journalPayload);
            $this->assertArrayHasKey('transactor_id',$journalPayload);
            $this->assertEquals($expectedJournalPayload['currency'], $journalPayload['currency']);
            $this->assertEquals($expectedJournalPayload['merchant_id'], $journalPayload['merchant_id']);
            $this->assertEquals($expectedJournalPayload['transactor_event'], $journalPayload['transactor_event']);
            $this->assertEquals($expectedJournalPayload['money_params'], $journalPayload['money_params']);

            $this->assertArrayHasKey('idempotency-key', $requestHeaders);
            $this->assertEquals('reverse-shadow', $requestHeaders['Ledger-Integration-Mode']);
            $this->assertEquals('PG', $requestHeaders['ledger-tenant']);

            $this->assertTrue($throwException);

            return true;
        })
        ->andReturn([
            'code' => 200,
            'body' => [
                "id"=> "LN5BWCGvLdPu7T",
                "created_at"=> "1677853302",
                "updated_at"=> "1677853302",
                "amount"=> "500",
                "base_amount"=> "500",
                "currency"=> "INR",
                "tenant"=> "PG",
                "transactor_id"=> "adj_LN5BW4fDCb1Sn7",
                "transactor_event"=> "positive_adjustment",
                "transaction_date"=> "1677853302",
                "ledger_entry"=> [
                    [
                        "id"=> "LN5BWCaS5FKndm",
                        "created_at"=> "1677853302",
                        "updated_at"=> "1677853302",
                        "merchant_id"=> "100abc000abc00",
                        "journal_id"=> "LN5BWCGvLdPu7T",
                        "account_id"=> "JjpZUD9PmJeNPk",
                        "amount"=> "500",
                        "base_amount"=> "500",
                        "type"=> "credit",
                        "currency"=> "INR",
                        "balance"=> "531425.000000",
                        "balance_updated"=> true,
                        "account_entities"=> [
                            "account_type"=> [
                                "payable"
                            ],
                            "fund_account_type"=> [
                                "merchant_balance"
                            ]
                        ]
                    ],
                    [
                        "id"=> "LN5BWCaUJA3TfB",
                        "created_at"=> "1677853302",
                        "updated_at"=> "1677853302",
                        "merchant_id"=> "100abc000abc00",
                        "journal_id"=> "LN5BWCGvLdPu7T",
                        "account_id"=> "LN22CRUSOTIIBG",
                        "amount"=> "500",
                        "base_amount"=> "500",
                        "type"=> "debit",
                        "currency"=> "INR",
                        "balance"=> "997350.000000",
                        "balance_updated"=> true,
                        "account_entities"=> [
                            "account_type"=> [
                                "payable"
                            ],
                            "fund_account_type"=> [
                                "adjustment_payable"
                            ]
                        ]
                    ]
                ]
            ],
            ]);

        $response = $this->startTest();

        $adjId = $response['id'];

        $adjustment = $this->getDbEntityById('adjustment', $adjId);


        $this->assertNotNull($adjustment, 'adjustment should not be null');
        $this->assertEquals('100abc000abc00', $adjustment['merchant_id']);
        $this->assertEquals(500, $adjustment['amount']);
        $this->assertEquals(Status::PROCESSED, $adjustment['status']);
        $this->assertNull($adjustment['transaction_id'], 'transaction should be null');

        $balanceId = $adjustment['balance_id'];

        $balance = $this->getDbEntityById('balance', $balanceId);

        $this->assertNotNull($balance, 'balance should not be null');
        $this->assertEquals('primary', $balance['type']);
        $this->assertEquals('100abc000abc00', $balance['merchant_id']);
        // balance not updated as transaction not created yet
        $this->assertEquals(1000, $balance['balance']);

        $txn = $this->getDbLastEntity('transaction');
        // transaction is not created yet
        $this->assertNull($txn, 'transaction should be null');

    }

    public function testManualAdjustmentCreateNonRetryableError()
    {
        Mail::fake();

        $this->app['config']->set('applications.ledger.enabled', true);
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow'], '100abc000abc00');

        $this->fixtures->create(
            'balance',
            [
                'id'            => '100def000def00',
                'balance'       => 1000,
                'type'          => 'primary',
                'merchant_id'   => '100abc000abc00'
            ]
        );

        $this->ba->adminAuth();

        $expectedJournalPayload = [
            "merchant_id"           => "100abc000abc00",
            "currency"              => "INR",
            "transaction_date"      => "", // any
            "transactor_id"         => "", // any
            "transactor_event"      => "positive_adjustment",
            "money_params"          => [
                "merchant_balance_amount"   => "500",
                "base_amount"               => "500",
                "adjustment_amount"         => "500"
            ]
        ];

        $mockLedger->shouldReceive('createJournal')
            ->times(1)
            ->withArgs(function($journalPayload, $requestHeaders, $throwException) use ($expectedJournalPayload) {

                $this->assertArrayHasKey('transaction_date',$journalPayload);
                $this->assertArrayHasKey('transactor_id',$journalPayload);
                $this->assertEquals($expectedJournalPayload['currency'], $journalPayload['currency']);
                $this->assertEquals($expectedJournalPayload['merchant_id'], $journalPayload['merchant_id']);
                $this->assertEquals($expectedJournalPayload['transactor_event'], $journalPayload['transactor_event']);
                $this->assertEquals($expectedJournalPayload['money_params'], $journalPayload['money_params']);

                $this->assertArrayHasKey('idempotency-key', $requestHeaders);
                $this->assertEquals('reverse-shadow', $requestHeaders['Ledger-Integration-Mode']);
                $this->assertEquals('PG', $requestHeaders['ledger-tenant']);

                $this->assertTrue($throwException);

                return true;
            })
            ->andThrow(new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                    [],
                "record_already_exist: BAD_REQUEST_RECORD_ALREADY_EXISTS"
            ));

        try
        {
            $this->startTest();
        }
        catch(\Exception $e)
        {
            $this->assertNotNull($e);
            $adjustmentsCreated = $this->getDbEntities('adjustment');
            $this->assertEquals(0, count($adjustmentsCreated));
        }
    }

    public function testManualAdjustmentCreateRetryableError()
    {
        Mail::fake();

        $this->app['config']->set('applications.ledger.enabled', true);
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow'], '100abc000abc00');

        $this->fixtures->create(
            'balance',
            [
                'id'            => '100def000def00',
                'balance'       => 1000,
                'type'          => 'primary',
                'merchant_id'   => '100abc000abc00'
            ]
        );

        $this->ba->adminAuth();

        $expectedJournalPayload = [
            "merchant_id"           => "100abc000abc00",
            "currency"              => "INR",
            "transaction_date"      => "", // any
            "transactor_id"         => "", // any
            "transactor_event"      => "positive_adjustment",
            "money_params"          => [
                "merchant_balance_amount"   => "500",
                "base_amount"               => "500",
                "adjustment_amount"         => "500"
            ]
        ];

        $mockLedger->shouldReceive('createJournal')
            ->once()
            ->withArgs(function($journalPayload, $requestHeaders, $throwException) use ($expectedJournalPayload) {

                $this->assertArrayHasKey('transaction_date',$journalPayload);
                $this->assertArrayHasKey('transactor_id',$journalPayload);
                $this->assertEquals($expectedJournalPayload['currency'], $journalPayload['currency']);
                $this->assertEquals($expectedJournalPayload['merchant_id'], $journalPayload['merchant_id']);
                $this->assertEquals($expectedJournalPayload['transactor_event'], $journalPayload['transactor_event']);
                $this->assertEquals($expectedJournalPayload['money_params'], $journalPayload['money_params']);

                $this->assertArrayHasKey('idempotency-key', $requestHeaders);
                $this->assertEquals('reverse-shadow', $requestHeaders['Ledger-Integration-Mode']);
                $this->assertEquals('PG', $requestHeaders['ledger-tenant']);

                $this->assertTrue($throwException);

                return true;
            })
            ->andThrow(new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                [],
                "insufficient_balance_failure: BAD_REQUEST_INSUFFICIENT_BALANCE"
            ))
            ->shouldReceive('createJournal')
            ->once()
            ->withArgs(function($journalPayload, $requestHeaders, $throwException) use ($expectedJournalPayload) {

                $this->assertArrayHasKey('transaction_date',$journalPayload);
                $this->assertArrayHasKey('transactor_id',$journalPayload);
                $this->assertEquals($expectedJournalPayload['currency'], $journalPayload['currency']);
                $this->assertEquals($expectedJournalPayload['merchant_id'], $journalPayload['merchant_id']);
                $this->assertEquals($expectedJournalPayload['transactor_event'], $journalPayload['transactor_event']);
                $this->assertEquals($expectedJournalPayload['money_params'], $journalPayload['money_params']);

                $this->assertArrayHasKey('idempotency-key', $requestHeaders);
                $this->assertEquals('reverse-shadow', $requestHeaders['Ledger-Integration-Mode']);
                $this->assertEquals('PG', $requestHeaders['ledger-tenant']);

                $this->assertTrue($throwException);

                return true;
            })
            ->andReturn([
                'code' => 200,
                'body' => [
                    "id"=> "LN5BWCGvLdPu7T",
                    "created_at"=> "1677853302",
                    "updated_at"=> "1677853302",
                    "amount"=> "500",
                    "base_amount"=> "500",
                    "currency"=> "INR",
                    "tenant"=> "PG",
                    "transactor_id"=> "adj_LN5BW4fDCb1Sn7",
                    "transactor_event"=> "positive_adjustment",
                    "transaction_date"=> "1677853302",
                    "ledger_entry"=> [
                        [
                            "id"=> "LN5BWCaS5FKndm",
                            "created_at"=> "1677853302",
                            "updated_at"=> "1677853302",
                            "merchant_id"=> "100abc000abc00",
                            "journal_id"=> "LN5BWCGvLdPu7T",
                            "account_id"=> "JjpZUD9PmJeNPk",
                            "amount"=> "500",
                            "base_amount"=> "500",
                            "type"=> "credit",
                            "currency"=> "INR",
                            "balance"=> "531425.000000",
                            "balance_updated"=> true,
                            "account_entities"=> [
                                "account_type"=> [
                                    "payable"
                                ],
                                "fund_account_type"=> [
                                    "merchant_balance"
                                ]
                            ]
                        ],
                        [
                            "id"=> "LN5BWCaUJA3TfB",
                            "created_at"=> "1677853302",
                            "updated_at"=> "1677853302",
                            "merchant_id"=> "100abc000abc00",
                            "journal_id"=> "LN5BWCGvLdPu7T",
                            "account_id"=> "LN22CRUSOTIIBG",
                            "amount"=> "500",
                            "base_amount"=> "500",
                            "type"=> "debit",
                            "currency"=> "INR",
                            "balance"=> "997350.000000",
                            "balance_updated"=> true,
                            "account_entities"=> [
                                "account_type"=> [
                                    "payable"
                                ],
                                "fund_account_type"=> [
                                    "adjustment_payable"
                                ]
                            ]
                        ]
                    ]
                ],
            ]);

        $response = $this->startTest();

        $adjId = $response['id'];

        $adjustment = $this->getDbEntityById('adjustment', $adjId);

        $this->assertNotNull($adjustment, 'adjustment should not be null');
        $this->assertEquals('100abc000abc00', $adjustment['merchant_id']);
        $this->assertEquals(500, $adjustment['amount']);
        $this->assertEquals(Status::PROCESSED, $adjustment['status']);
        $this->assertNull($adjustment['transaction_id'], 'transaction should be null');

        $balanceId = $adjustment['balance_id'];

        $balance = $this->getDbEntityById('balance', $balanceId);

        $this->assertNotNull($balance, 'balance should not be null');
        $this->assertEquals('primary', $balance['type']);
        $this->assertEquals('100abc000abc00', $balance['merchant_id']);
        // balance not updated as transaction not created yet
        $this->assertEquals(1000, $balance['balance']);

        $txn = $this->getDbLastEntity('transaction');
        // transaction is not created yet
        $this->assertNull($txn, 'transaction should be null');

    }

    public function testAdjustmentTransactionCreate()
    {
        Mail::fake();
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow'], '100abc000abc00');

        $this->ba->pgRouterAuth();

        $this->fixtures->create(
            'balance',
            [
                'id'            => 'LN5BW4fDCb1Sn7',
                'balance'       => 1000,
                'type'          => 'primary',
                'merchant_id'   => '100abc000abc00'
            ]
        );

        $this->fixtures->create(
            'adjustment',
            [
                'id'            => 'LN1MS4fADj0Sn0',
                'merchant_id'   => '100abc000abc00',
                'balance_id'    => 'LN5BW4fDCb1Sn7',
                'entity_type'   => null,
                'entity_id'     => null,
                'amount'        => 500,
                'currency'      => 'INR',
                'description'   => 'add primary balance in reverse shadow',
                'status'        => 'processed',
                'transaction_id'=> null,
            ]
        );

        $adjustment = $this->getDbLastEntity('adjustment');

        $this->assertEquals('LN1MS4fADj0Sn0', $adjustment['id']);
        $this->assertEquals(Status::PROCESSED, $adjustment['status']);
        $this->assertNull($adjustment['transaction_id']); // before txn creation

        $transactionEntities = $this->getDbEntities('transaction');

        $this->assertEquals(0, count($transactionEntities));

        $response = $this->startTest();

        $transaction = $this->getDbEntityById('transaction', $response['transaction_id']);

        $this->assertNotNull($transaction, 'transaction should not be null');
        $this->assertEquals('LN5BWCGvLdPu7T', $transaction['id']);
        $this->assertEquals($adjustment['id'], $transaction['entity_id']);
        $this->assertEquals('adjustment', $transaction['type']);
        $this->assertEquals('100abc000abc00', $transaction['merchant_id']);
        $this->assertEquals(500, $transaction['amount']);
        $this->assertEquals('LN5BW4fDCb1Sn7', $transaction['balance_id']);
        $this->assertNotNull($transaction['posted_at']);

        $updatedAdjustment = $this->getDbLastEntity('adjustment');

        $this->assertEquals($transaction['entity_id'], $updatedAdjustment['id']);

        //should update txn_id in adjustment after txn creation
        $this->assertEquals($transaction['id'], $updatedAdjustment['transaction_id']);

        $balanceId = $updatedAdjustment['balance_id'];

        $balance = $this->getDbEntityById('balance', $balanceId);

        $this->assertNotNull($balance, 'balance should not be null');
        $this->assertEquals('primary', $balance['type']);
        $this->assertEquals('100abc000abc00', $balance['merchant_id']);

        //should update balance after txn creation
        $this->assertEquals(1500, $balance['balance']);
    }

}

