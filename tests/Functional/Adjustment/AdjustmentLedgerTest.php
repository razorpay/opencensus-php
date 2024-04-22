<?php

namespace RZP\Tests\Functional\Adjustment;

use Mail;
use Queue;
use Mockery;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Mail\Merchant\NegativeBalanceThresholdAlert;
use RZP\Models\Adjustment\Status;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\MocksRazorx;
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
    use MocksRazorx;

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

        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow', 'new_settlement_service'], '100abc000abc00');

        $this->mockRazorxTreatmentV2(RazorxTreatment::EARLY_DISPATCH_OF_TXNS_FOR_SETTLEMENTS_USING_JOURNAL_ADJUSTMENTS, 'on');

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
                "adjustment_amount"         => "500",
                "merchant_balance_limit"    => "0"
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

        $this->fixtures->merchant->addFeatures([['pg_ledger_reverse_shadow', 'new_settlement_service']], '100abc000abc00');

        $this->mockRazorxTreatmentV2(RazorxTreatment::EARLY_DISPATCH_OF_TXNS_FOR_SETTLEMENTS_USING_JOURNAL_ADJUSTMENTS, 'on');

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
                "adjustment_amount"         => "500",
                "merchant_balance_limit"    => "0"
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
                "adjustment_amount"         => "500",
                "merchant_balance_limit"    => "0"
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
        $this->fixtures->merchant->addFeatures([['pg_ledger_reverse_shadow', 'new_settlement_service']], '100abc000abc00');

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

    public function testDuplicateAdjustmentTransactionCreate()
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

        $oldBalance = $this->getDbEntityById('balance', 'LN5BW4fDCb1Sn7');
        $this->assertEquals(1000, $oldBalance['balance']);

        $adjAmount = 500;

        $this->fixtures->create(
            'adjustment',
            [
                'id'            => 'LN1MS4fADj0Sn0',
                'merchant_id'   => '100abc000abc00',
                'balance_id'    => 'LN5BW4fDCb1Sn7',
                'entity_type'   => null,
                'entity_id'     => null,
                'amount'        => $adjAmount,
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
        $this->assertEquals($adjAmount, $transaction['amount']);
        $this->assertEquals('LN5BW4fDCb1Sn7', $transaction['balance_id']);
        $this->assertNotNull($transaction['posted_at']);

        $updatedAdjustment = $this->getDbLastEntity('adjustment');

        $this->assertEquals($transaction['entity_id'], $updatedAdjustment['id']);

        //should update txn_id in adjustment after txn creation
        $this->assertEquals($transaction['id'], $updatedAdjustment['transaction_id']);

        $balanceId = $updatedAdjustment['balance_id'];

        $newBalance = $this->getDbEntityById('balance', $balanceId);

        $this->assertNotNull($newBalance, 'balance should not be null');
        $this->assertEquals('primary', $newBalance['type']);
        $this->assertEquals('100abc000abc00', $newBalance['merchant_id']);

        //should update balance after txn creation
        $this->assertEquals($oldBalance['balance']+$adjAmount, $newBalance['balance']);

        // makes duplicate request
        $response = $this->startTest();

        $newBalance2 = $this->getDbEntityById('balance', $balanceId);

        $updatedAdjustment = $this->getDbLastEntity('adjustment');

        $this->assertEquals($transaction['id'], $updatedAdjustment['transaction_id']);

        // should not update balance again
        $this->assertEquals($newBalance['balance'], $newBalance2['balance']);
    }

    public function testNegativeAdjustmentCreateWithNegativeLimit()
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

        $this->fixtures->create('balance_config',
            [
                'id'                            => '100yz000yz00yz',
                'balance_id'                    => '100def000def00',
                'type'                          => 'primary',
                'negative_transaction_flows'   => ['adjustment'],
                'negative_limit_auto'           => 6000,
                'negative_limit_manual'         => 6000
            ]
        );

        $this->ba->adminAuth();

        $expectedJournalPayload = [
            "merchant_id"           => "100abc000abc00",
            "currency"              => "INR",
            "transaction_date"      => "", // any
            "transactor_id"         => "", // any
            "transactor_event"      => "negative_adjustment",
            "money_params"          => [
                "merchant_balance_amount"   => "5000",
                "base_amount"               => "5000",
                "adjustment_amount"         => "5000",
                "merchant_balance_limit"    => "6000"
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
                    "amount"=> "5000",
                    "base_amount"=> "5000",
                    "currency"=> "INR",
                    "tenant"=> "PG",
                    "transactor_id"=> "adj_LN5BW4fDCb1Sn7",
                    "transactor_event"=> "negative_adjustment",
                    "transaction_date"=> "1677853302",
                    "ledger_entry"=> [
                        [
                            "id"=> "LN5BWCaS5FKndm",
                            "created_at"=> "1677853302",
                            "updated_at"=> "1677853302",
                            "merchant_id"=> "100abc000abc00",
                            "journal_id"=> "LN5BWCGvLdPu7T",
                            "account_id"=> "JjpZUD9PmJeNPk",
                            "amount"=> "5000",
                            "base_amount"=> "5000",
                            "type"=> "credit",
                            "currency"=> "INR",
                            "balance"=> "-1000.000000",
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
                            "amount"=> "5000",
                            "base_amount"=> "5000",
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
        $this->assertEquals(-5000, $adjustment['amount']);
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

    public function testNegativeAdjustmentTransactionCreateWithLowBalanceInReverseShadow()
    {
        Mail::fake();
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow'], '100abc000abc00');

        $this->ba->pgRouterAuth();

        $oldBalanceAmount = 1000;

        $this->fixtures->create(
            'balance',
            [
                'id'            => '100def000def00',
                'balance'       => $oldBalanceAmount,
                'type'          => 'primary',
                'merchant_id'   => '100abc000abc00'
            ]
        );

        $this->fixtures->create('balance_config',
            [
                'id'                            => '100yz000yz00yz',
                'balance_id'                    => '100def000def00',
                'type'                          => 'primary',
                'negative_transaction_flows'   => ['adjustment'],
                'negative_limit_auto'           => 6000,
                'negative_limit_manual'         => 6000
            ]
        );

        $this->fixtures->create(
            'adjustment',
            [
                'id'            => 'LN1MS4fADj0Sn0',
                'merchant_id'   => '100abc000abc00',
                'balance_id'    => '100def000def00',
                'entity_type'   => null,
                'entity_id'     => null,
                'amount'        => -5000,
                'currency'      => 'INR',
                'description'   => 'deduct primary balance in reverse shadow',
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
        $this->assertEquals(abs($adjustment->getAmount()), $transaction['amount']);
        $this->assertEquals(abs($adjustment->getAmount()), $transaction['debit']);
        $this->assertEquals('100def000def00', $transaction['balance_id']);
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
        $this->assertEquals($oldBalanceAmount + $updatedAdjustment->getAmount(), $balance['balance']);
    }

    public function testMissingAdjustmentTransactionsCreateCron()
    {
        Mail::fake();
        $this->app['config']->set('applications.ledger.enabled', true);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $createdAtTimestamp = (int)((millitime()-32400000)/1000);

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
                'created_at'    => $createdAtTimestamp
            ]
        );

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

        $journal = $this->getJournal();

        $mockLedger->shouldReceive('fetchByTransactor')
            ->times(1)
            ->andReturn([
                    "body" => $journal
                ]
            );

        $this->ba->cronAuth();

        $adj = $this->getDbLastEntity('adjustment');

        $this->assertNotNull($adj);

        $txn = $this->getDbLastEntity('transaction');

        $this->assertNull($txn);

        $this->startTest();

        $txn = $this->getDbLastEntity('transaction');

        $this->assertNotNull($txn);
    }

    public function testReserveBalanceNegativeAdjustmentCreateSuccess()
    {
        Mail::fake();

        $this->app['config']->set('applications.ledger.enabled', true);
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow'], '100abc000abc00');

        $this->fixtures->create(
            'balance',
            [
                'id'            => '100efg000efg00',
                'balance'       => 1000,
                'type'          => 'reserve_primary',
                'merchant_id'   => '100abc000abc00'
            ]
        );

        $this->ba->adminAuth();

        $expectedJournalPayload = [
            "merchant_id"           => "100abc000abc00",
            "currency"              => "INR",
            "transaction_date"      => "", // any
            "transactor_id"         => "", // any
            "transactor_event"      => "negative_adjustment",
            "money_params"          => [
                "reserve_balance_amount"   => "500",
                "base_amount"               => "500",
                "adjustment_amount"         => "500",
                "merchant_balance_limit"    => "0"
            ],
            "additional_params"     => [
                "balance_type"  => "reserve_balance"
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
                    "transactor_event"=> "negative_adjustment",
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
                            "type"=> "debit",
                            "currency"=> "INR",
                            "balance"=> "500.000000",
                            "balance_updated"=> true,
                            "account_entities"=> [
                                "account_type"=> [
                                    "payable"
                                ],
                                "fund_account_type"=> [
                                    "merchant_reserve_balance"
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
                            "type"=> "credit",
                            "currency"=> "INR",
                            "balance"=> "1000.000000",
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
        $this->assertEquals(-500, $adjustment['amount']);
        $this->assertEquals(Status::PROCESSED, $adjustment['status']);
        $this->assertNull($adjustment['transaction_id'], 'transaction should be null');

        $balanceId = $adjustment['balance_id'];
        $this->assertEquals('100efg000efg00', $balanceId);

        $balance = $this->getDbEntityById('balance', $balanceId);

        $this->assertNotNull($balance, 'balance should not be null');
        $this->assertEquals('reserve_primary', $balance['type']);
        $this->assertEquals('100abc000abc00', $balance['merchant_id']);
        // balance not updated as transaction not created yet
        $this->assertEquals(1000, $balance['balance']);

        $txn = $this->getDbLastEntity('transaction');
        // transaction is not created yet
        $this->assertNull($txn, 'transaction should be null');

        // create transaction for adjustement

        $request = [
            'url' => '/adjustments/transaction_create',
            'method' => 'POST',
            'content' => [
                'id'        =>  str_replace("adj_", "", $adjId),
                'transaction_id' =>  'LN5BWCGvLdPu7T',
            ]
        ];

        $this->app['config']->set('applications.ledger.enabled', true);

        $this->ba->pgRouterAuth();

        $response = $this->makeRequestAndGetContent($request);
        $this->assertNotNull($response);

        $balance = $this->getDbEntityById('balance', $balanceId);

        $this->assertNotNull($balance, 'balance should not be null');
        $this->assertEquals('reserve_primary', $balance['type']);
        $this->assertEquals('100abc000abc00', $balance['merchant_id']);
        // balance  debited with negative adj amount as transaction should be created
        $this->assertEquals(500, $balance['balance']);

        $txn = $this->getDbEntityById('transaction', 'LN5BWCGvLdPu7T');
        // transaction is not created yet
        $this->assertNotNull($txn, 'transaction should not be null');
        $this->assertEquals(str_replace("adj_", "", $adjId), $txn['entity_id']);
        $this->assertEquals(500, $txn['debit']);
        $this->assertEquals('adjustment', $txn['type']);
        $this->assertEquals('100abc000abc00', $txn['merchant_id']);
        $this->assertEquals(500, $txn['amount']);
        $this->assertEquals($balanceId, $txn['balance_id']);

    }

    public function testCommissionBalanceAdjustmentTransactionCreateSuccessInReverseShadow()
    {
        Mail::fake();

        $this->app['config']->set('applications.ledger.enabled', true);
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow'], '100abc000abc00');

        $this->fixtures->create(
            'balance',
            [
                'id'            => '100efg000efg00',
                'balance'       => 1000,
                'type'          => 'commission',
                'merchant_id'   => '100abc000abc00'
            ]
        );

        $this->ba->adminAuth();

        $response = $this->startTest();

        $adjId = $response['id'];

        $adjustment = $this->getDbEntityById('adjustment', $adjId);

        $this->assertNotNull($adjustment, 'adjustment should not be null');
        $this->assertEquals('100abc000abc00', $adjustment['merchant_id']);
        $this->assertEquals(-500, $adjustment['amount']);
        $this->assertEquals(Status::PROCESSED, $adjustment['status']);
        $this->assertNotNull($adjustment['transaction_id'], 'transaction should not be null');

        $balanceId = $adjustment['balance_id'];
        $this->assertEquals('100efg000efg00', $balanceId);

        $balance = $this->getDbEntityById('balance', $balanceId);

        $this->assertNotNull($balance, 'balance should not be null');
        $this->assertEquals('commission', $balance['type']);
        $this->assertEquals('100abc000abc00', $balance['merchant_id']);
        // balance  updated as transaction not created yet
        $this->assertEquals(500, $balance['balance']);

        $txn = $this->getDbLastEntity('transaction');
        $this->assertNotNull($txn, 'transaction should not be null');
        $this->assertNotNull($txn, 'transaction should not be null');
        $this->assertEquals(str_replace("adj_", "", $adjId), $txn['entity_id']);
        $this->assertEquals(500, $txn['debit']);
        $this->assertEquals('adjustment', $txn['type']);
        $this->assertEquals('100abc000abc00', $txn['merchant_id']);
        $this->assertEquals(500, $txn['amount']);
        $this->assertEquals($balanceId, $txn['balance_id']);

    }

    private function getJournal()
    {

        return [
            "id"=> "LLJMDzXXytv87X",
            "created_at"=> 1677466532,
            "updated_at"=> 1677466532,
            "amount"=> "100",
            "base_amount"=> "100",
            "currency"=> "INR",
            "tenant"=> "PG",
            "transactor_id"=> "adj_LLJMDzXXyjd7UI",
            "transactor_event"=> "positive_adjustment",
            "transaction_date"=> 1677466530,
            "ledger_entry"=> [
                [
                    "id"=> "LLJMDzXXyjC93B",
                    "created_at"=> 1677466532,
                    "updated_at"=> 1677466532,
                    "merchant_id"=> "10000000000000",
                    "journal_id"=> "LLJMDzRZGnZhGU",
                    "account_id"=> "Jk3pWyD5WaSSPP",
                    "amount"=> "100",
                    "base_amount"=> "100",
                    "type"=> "credit",
                    "currency"=> "INR",
                    "balance"=> "100.000000",
                    "balance_updated"=> true,
                    "account_entities"=> [
                        "account_type"=> [
                            "receivable"
                        ],
                        "fund_account_type"=> [
                            "merchant_balance"
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
                    "amount"=> "100",
                    "base_amount"=> "100",
                    "type"=> "debit",
                    "currency"=> "INR",
                    "balance"=> "100.000000",
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
        ];
    }

    public function testManualAdjustmentCreateSuccessWithEarlyDispatch()
    {
        Mail::fake();

        $this->app['config']->set('applications.ledger.enabled', true);
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow', 'new_settlement_service'], '100abc000abc00');

        $this->mockRazorxTreatmentV2(RazorxTreatment::EARLY_DISPATCH_OF_TXNS_FOR_SETTLEMENTS_USING_JOURNAL_ADJUSTMENTS, 'on');

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
                "adjustment_amount"         => "500",
                "merchant_balance_limit"    => "0"
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

        $creditTxnPayload =  [
            'id' =>  'LN5BWCGvLdPu7T',
            'merchant_id' =>  '100abc000abc00',
            'source_id' =>  'LN5BW4fDCb1Sn7',
            'source_type' =>  'adjustment',
            'balance_type' => 'PRIMARY',
            'currency' => 'INR',
            'credit' =>  500,
            'debit' =>  0,
            'fee' =>  0,
            'tax' =>  0,
            'settled_by' => 'Razorpay',
            'on_hold' => null,
            'on_hold_reason' => '',
            'meta' => null,
        ];

        $this->mockSns($creditTxnPayload);

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


    public function testAdjustmentTransactionCreateWithEarlyDispatch()
    {
        Mail::fake();
        $this->fixtures->merchant->addFeatures([['pg_ledger_reverse_shadow', 'new_settlement_service']], '100abc000abc00');

        $this->mockRazorxTreatmentV2(RazorxTreatment::EARLY_DISPATCH_OF_TXNS_FOR_SETTLEMENTS_USING_JOURNAL_ADJUSTMENTS, 'on');

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

    protected function mockSns($creditTxnPayload)
    {
        $sns = Mockery::mock('RZP\Services\Aws\Sns');

        $this->app->instance('sns', $sns);

        $sns->shouldReceive('publish')
            ->times(1)
            ->with(Mockery::type('string'), Mockery::type('string'))
            ->andReturnUsing(function ($input) use ($creditTxnPayload)
            {
                $json_decoded_input = json_decode($input, true);

                if ($json_decoded_input['id'] === $creditTxnPayload['id'])
                {
                    $this->assertEquals($creditTxnPayload['merchant_id'], $json_decoded_input['merchant_id']);
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

}

