<?php

namespace RZP\Tests\Functional\Dispute;

use DB;
use Mail;
use Cache;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Adjustment\Status;
use RZP\Models\Admin\Admin\Entity as AdminEntity;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\TestCase;
use Functional\Dispute\DisputeTrait;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class DisputeLedgerTest extends TestCase
{
    use DisputeTrait;
    use DbEntityFetchTrait;
    use TestsWebhookEvents;

    protected $druidMock;

    protected $salesforceMock;

    const SECONDS_IN_DAY = 24 * 60 * 60;

    protected $payment = null;

    protected $merchant = null;

    protected $repo = null;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/DisputeTestLedgerData.php';

        parent::setUp();

        $this->ba->adminAuth();

    }


    public function testDisputeDeductAtOnsetCreateSuccess()
    {
        $this->app['config']->set('applications.ledger.enabled', true);
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $testData = $this->updateCreateTestData();

        $testData['response']['content']['payment_id'] = $this->payment->getPublicId();

        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $this->ba->adminAuth();

        $expectedJournalPayload = [
            "merchant_id"           => "10000000000000",
            "currency"              => "INR",
            "transaction_date"      => "", // any
            "transactor_id"         => "", // any
            "transactor_event"      => "razorpay_dispute_deduct",
            "money_params"          => [
                "merchant_balance_amount"       => "1000",
                "base_amount"                   => "1000",
                "gateway_dispute_payable_amount"=> "1000"
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
                    "id"=> "LO8XsENYT8eoLp",
                    "created_at"=> "1678083477",
                    "updated_at"=> "1678083477",
                    "amount"=> "1000",
                    "base_amount"=> "1000",
                    "currency"=> "INR",
                    "tenant"=> "PG",
                    "transactor_id"=> "disp_LO8XoBzYDF42Qf",
                    "transactor_event"=> "razorpay_dispute_deduct",
                    "transaction_date"=> "1678083475",
                    "ledger_entry"=> [
                        [
                            "id"=> "LO8XsEY2FgCgvg",
                            "created_at"=> "1678083477",
                            "updated_at"=> "1678083477",
                            "merchant_id"=> "10000000000000",
                            "journal_id"=> "LO8XsENYT8eoLp",
                            "account_id"=> "JjpZUD9PmJeNPk",
                            "amount"=> "1000",
                            "base_amount"=> "1000",
                            "type"=> "debit",
                            "currency"=> "INR",
                            "balance"=> "540189.000000",
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
                            "id"=> "LO8XsEY3C4sdPg",
                            "created_at"=> "1678083477",
                            "updated_at"=> "1678083477",
                            "merchant_id"=> "10000000000000",
                            "journal_id"=> "LO8XsENYT8eoLp",
                            "account_id"=> "LO89uSsPrzZHUe",
                            "amount"=> "1000",
                            "base_amount"=> "1000",
                            "type"=> "credit",
                            "currency"=> "INR",
                            "balance"=> "",
                            "balance_updated"=> false,
                            "account_entities"=> [
                                "account_type"=> [
                                    "payable"
                                ],
                                "fund_account_type"=> [
                                    "gateway_dispute"
                                ]
                            ]
                        ]
                    ]
                ],
            ]);

        $response = $this->startTest($testData);

        $this->assertNotNull($response, 'response should not be null');

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(true, $payment['disputed']);

        $dispute = $this->getLastEntity('dispute', true);

        $this->assertArraySelectiveEquals([
            'amount_deducted'   => 1000,
            'internal_status'   => 'open',
        ], $dispute);

        $this->assertEqualsWithDelta($dispute['created_at'] + self::SECONDS_IN_DAY * 10, $dispute['internal_respond_by'], 5);

        $adjustment = $this->getLastEntity('adjustment', true);

        $this->assertNotNull($adjustment, 'adjustment should not be null');
        $this->assertEquals('10000000000000', $adjustment['merchant_id']);
        $this->assertEquals(-1000, $adjustment['amount']);
        $this->assertEquals(Status::PROCESSED, $adjustment['status']);
        $this->assertNull($adjustment['transaction_id'], 'transaction should be null');
        $this->assertEquals($adjustment['entity_type'], 'dispute','entity_type should be dispute');
        $this->assertNotNull($adjustment['entity_id'], 'entity_id should not be null');

        $txn = $this->getDbEntity('transaction', ['type'=>'adjustment']);
        // transaction is not created yet
        $this->assertNull($txn, 'transaction should be null');
    }

    protected function updateCreateTestData(string $paymentId = null): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $name = $trace[1]['function'];

        if (isset($paymentId) === false)
        {
            $this->payment = $this->fixtures->create('payment:captured');

            $paymentId = $this->payment->getPublicId();
        }

        $reason = $this->fixtures->create('dispute_reason');

        $testData = &$this->testData[$name];

        $testData['request']['url'] = '/payments/' . $paymentId . '/disputes';

        $testData['request']['content']['reason_id'] = $reason['id'];

        return $testData;
    }

    public function testDisputeDeductAtOnsetAdjustmentTransactionCreate()
    {
        Mail::fake();
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $this->ba->pgRouterAuth();

        $oldBalance = $this->fixtures->create(
            'balance',
            [
                'id'            => 'LN5BW4fDCb1Sn7',
                'balance'       => 5000,
                'type'          => 'primary',
                'merchant_id'   => '10000000000000'
            ]
        );

        $this->fixtures->create(
            'adjustment',
            [
                'id'            => 'LOkELUdz5YqBn2',
                'merchant_id'   => '10000000000000',
                'balance_id'    => 'LN5BW4fDCb1Sn7',
                'entity_type'   => 'dispute',
                'entity_id'     => 'LOkELLuV3ps5vW',
                'amount'        => -1000,
                'currency'      => 'INR',
                'description'   => 'add negative adjustment',
                'status'        => 'processed',
                'transaction_id'=> null,
            ]
        );

        $adjustment = $this->getDbLastEntity('adjustment');

        $this->assertEquals('LOkELUdz5YqBn2', $adjustment['id']);
        $this->assertEquals(Status::PROCESSED, $adjustment['status']);
        $this->assertNull($adjustment['transaction_id']); // before txn creation

        $transactionEntities = $this->getDbEntities('transaction');

        $this->assertEquals(0, count($transactionEntities));

        $response = $this->startTest();

        $this->assertNotNull($response, 'response should not be null');

        $transaction = $this->getDbEntityById('transaction', $response['transaction_id']);

        $this->assertNotNull($transaction, 'transaction should not be null');
        $this->assertEquals('LN5BWCGvLdPu7T', $transaction['id']);
        $this->assertEquals($adjustment['id'], $transaction['entity_id']);
        $this->assertEquals('adjustment', $transaction['type']);
        $this->assertEquals('10000000000000', $transaction['merchant_id']);
        $this->assertEquals(1000, $transaction['amount']);
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
        $this->assertEquals('10000000000000', $newBalance['merchant_id']);

        //should update balance after txn creation
        $this->assertEquals($updatedAdjustment['amount'], $newBalance['balance']-$oldBalance['balance']);
    }

    public function testDisputeDeductAtOnsetCreateFailureWithLedgerNonRetryableError()
    {
        $this->app['config']->set('applications.ledger.enabled', true);
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $testData = $this->updateCreateTestData();

        $testData['response']['content']['payment_id'] = $this->payment->getPublicId();

        $payment = $this->getLastEntity('payment', true);

        $this->assertNotNull($payment);

        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $this->ba->adminAuth();

        $expectedJournalPayload = [
            "merchant_id"           => "10000000000000",
            "currency"              => "INR",
            "transaction_date"      => "", // any
            "transactor_id"         => "", // any
            "transactor_event"      => "razorpay_dispute_deduct",
            "money_params"          => [
                "merchant_balance_amount"       => "1000",
                "base_amount"                   => "1000",
                "gateway_dispute_payable_amount"=> "1000"
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
            $this->startTest($testData);
        }
        catch(\Exception $e)
        {
            $this->assertNotNull($e);

            $adjustmentsCreated = $this->getDbEntities('adjustment');

            $this->assertEquals(0, count($adjustmentsCreated), 'adjustment should rollback');

            $disputesCreated = $this->getDbEntities('dispute');

            $this->assertEquals(0, count($disputesCreated), 'dispute should rollback');

            $payment = $this->getLastEntity('payment', true);

            $this->assertEquals(false, $payment['disputed'], 'payment should not be disputed');
        }
    }

    public function testDisputeDeductAtOnsetCreateFailureWithLedgerRetryableError()
    {
        $this->app['config']->set('applications.ledger.enabled', true);
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $testData = $this->updateCreateTestData();

        $testData['response']['content']['payment_id'] = $this->payment->getPublicId();

        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $this->ba->adminAuth();

        $expectedJournalPayload = [
            "merchant_id"           => "10000000000000",
            "currency"              => "INR",
            "transaction_date"      => "", // any
            "transactor_id"         => "", // any
            "transactor_event"      => "razorpay_dispute_deduct",
            "money_params"          => [
                "merchant_balance_amount"       => "1000",
                "base_amount"                   => "1000",
                "gateway_dispute_payable_amount"=> "1000"
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
                    "id"=> "LO8XsENYT8eoLp",
                    "created_at"=> "1678083477",
                    "updated_at"=> "1678083477",
                    "amount"=> "1000",
                    "base_amount"=> "1000",
                    "currency"=> "INR",
                    "tenant"=> "PG",
                    "transactor_id"=> "disp_LO8XoBzYDF42Qf",
                    "transactor_event"=> "razorpay_dispute_deduct",
                    "transaction_date"=> "1678083475",
                    "ledger_entry"=> [
                        [
                            "id"=> "LO8XsEY2FgCgvg",
                            "created_at"=> "1678083477",
                            "updated_at"=> "1678083477",
                            "merchant_id"=> "10000000000000",
                            "journal_id"=> "LO8XsENYT8eoLp",
                            "account_id"=> "JjpZUD9PmJeNPk",
                            "amount"=> "1000",
                            "base_amount"=> "1000",
                            "type"=> "debit",
                            "currency"=> "INR",
                            "balance"=> "540189.000000",
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
                            "id"=> "LO8XsEY3C4sdPg",
                            "created_at"=> "1678083477",
                            "updated_at"=> "1678083477",
                            "merchant_id"=> "10000000000000",
                            "journal_id"=> "LO8XsENYT8eoLp",
                            "account_id"=> "LO89uSsPrzZHUe",
                            "amount"=> "1000",
                            "base_amount"=> "1000",
                            "type"=> "credit",
                            "currency"=> "INR",
                            "balance"=> "",
                            "balance_updated"=> false,
                            "account_entities"=> [
                                "account_type"=> [
                                    "payable"
                                ],
                                "fund_account_type"=> [
                                    "gateway_dispute"
                                ]
                            ]
                        ]
                    ]
                ],
            ]);

        $response = $this->startTest($testData);

        $this->assertNotNull($response, 'response should not be null');

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(true, $payment['disputed']);

        $dispute = $this->getLastEntity('dispute', true);

        $this->assertArraySelectiveEquals([
            'amount_deducted'   => 1000,
            'internal_status'   => 'open',
        ], $dispute);

        $this->assertEqualsWithDelta($dispute['created_at'] + self::SECONDS_IN_DAY * 10, $dispute['internal_respond_by'], 5);

        $adjustment = $this->getLastEntity('adjustment', true);

        $this->assertNotNull($adjustment, 'adjustment should not be null');
        $this->assertEquals('10000000000000', $adjustment['merchant_id']);
        $this->assertEquals(-1000, $adjustment['amount']);
        $this->assertEquals(Status::PROCESSED, $adjustment['status']);
        $this->assertNull($adjustment['transaction_id'], 'transaction should be null');
        $this->assertEquals($adjustment['entity_type'], 'dispute','entity_type should be dispute');
        $this->assertNotNull($adjustment['entity_id'], 'entity_id should not be null');

        $txn = $this->getDbEntity('transaction', ['type'=>'adjustment']);
        // transaction is not created yet
        $this->assertNull($txn, 'transaction should be null');
    }

    protected function updateEditTestData(array $attributes = []): array
    {
        $this->fixtures->edit(AdminEntity::ADMIN, Org::SUPER_ADMIN, [AdminEntity::ALLOW_ALL_MERCHANTS => 1]);

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $name = $trace[1]['function'];

        $dispute = $this->fixtures->create('dispute', $attributes);

        $this->merchant = $dispute->merchant;

        $this->ba->adminProxyAuth($this->merchant->getId(), 'rzp_test_' . $this->merchant->getId());

        $testData = &$this->testData[$name];

        $testData['request']['url'] = '/disputes/' . $dispute->getPublicId();

        return $testData;
    }

    public function testDisputeWonReversalSuccess()
    {
        $this->app['config']->set('applications.ledger.enabled', true);
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $input = [
            'amount'                => 10100,
            'deduct_at_onset'       => 1,
        ];

        $testdata = $this->updateEditTestData($input);

        $oldMerchantBalance = $this->getDbEntityById('balance', $this->merchant['id'], true)['balance'];

        $this->ba->adminProxyAuth();

        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $expectedJournalPayload = [
            "merchant_id"           => "10000000000000",
            "currency"              => "INR",
            "transaction_date"      => "", // any
            "transactor_id"         => "", // disp_<id>
            "transactor_event"      => "razorpay_dispute_reversal",
            "money_params"          => [
                "merchant_balance_amount"       => "10100",
                "base_amount"                   => "10100",
                "gateway_dispute_payable_amount"=> "10100"
            ]
        ];

        $mockLedger->shouldReceive('createJournal')
            ->times(1)
            ->withArgs(function($journalPayload, $requestHeaders, $throwException) use ($expectedJournalPayload) {

                $this->assertArrayHasKey('transaction_date',$journalPayload);
                $this->assertArrayHasKey('transactor_id',$journalPayload);
                $this->assertEquals('disp', substr($journalPayload['transactor_id'],0,4));
                $this->assertEquals($expectedJournalPayload['currency'], $journalPayload['currency']);
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
                    "id"=> "LO8XsENYT8eoLp",
                    "created_at"=> "1678083477",
                    "updated_at"=> "1678083477",
                    "amount"=> "10100",
                    "base_amount"=> "10100",
                    "currency"=> "INR",
                    "tenant"=> "PG",
                    "transactor_id"=> "disp_LO8XoBzYDF42Qf",
                    "transactor_event"=> "razorpay_dispute_reversal",
                    "transaction_date"=> "1678083475",
                    "ledger_entry"=> [
                        [
                            "id"=> "LO8XsEY2FgCgvg",
                            "created_at"=> "1678083477",
                            "updated_at"=> "1678083477",
                            "merchant_id"=> "10000000000000",
                            "journal_id"=> "LO8XsENYT8eoLp",
                            "account_id"=> "JjpZUD9PmJeNPk",
                            "amount"=> "10100",
                            "base_amount"=> "10100",
                            "type"=> "credit",
                            "currency"=> "INR",
                            "balance"=> "540189.000000",
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
                            "id"=> "LO8XsEY3C4sdPg",
                            "created_at"=> "1678083477",
                            "updated_at"=> "1678083477",
                            "merchant_id"=> "10000000000000",
                            "journal_id"=> "LO8XsENYT8eoLp",
                            "account_id"=> "LO89uSsPrzZHUe",
                            "amount"=> "10100",
                            "base_amount"=> "10100",
                            "type"=> "debit",
                            "currency"=> "INR",
                            "balance"=> "",
                            "balance_updated"=> false,
                            "account_entities"=> [
                                "account_type"=> [
                                    "payable"
                                ],
                                "fund_account_type"=> [
                                    "gateway_dispute"
                                ]
                            ]
                        ]
                    ]
                ],
            ]);

        $response = $this->runRequestResponseFlow($testdata);

        $this->assertNotNull($response, 'response should not be null');

        $adjustment = $this->getDbLastEntity('adjustment');

        $dispute = $this->getDbLastEntity('dispute');

        $txn = $this->getDbEntity('transaction',['type'=> 'adjustment']);

        $newMerchantBalance = $this->getDbEntityById('balance', $dispute['merchant_id'])['balance'];

        $this->assertEquals($dispute['id'], substr($response['id'], 5));
        $this->assertEquals($testdata['request']['content']['status'], $response['status']);
        $this->assertEquals( $dispute['internal_status'], 'won');
        $this->assertEquals( $dispute['amount_reversed'], 10100);

        $this->assertNotNull($adjustment, 'adjustment should not be null');
        $this->assertEquals('10000000000000', $adjustment['merchant_id']);
        $this->assertEquals($adjustment['amount'], 10100);
        $this->assertEquals($adjustment['entity_type'], 'dispute','entity_type should be dispute');
        $this->assertEquals($adjustment['entity_id'], $dispute['id']);
        $this->assertNull($adjustment['transaction_id'], 'transaction should be null');
        $this->assertEquals(Status::PROCESSED, $adjustment['status']);

        $this->assertEquals(0, ($newMerchantBalance - $oldMerchantBalance), 'balance should not be updated until txn is created in async');

        $this->assertNotEquals($adjustment['id'], $txn['entity_id'],'transaction should not be created for current adjustment');

    }

    public function testDisputeWonAdjustmentTransactionCreateSuccess()
    {
        Mail::fake();
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $this->ba->pgRouterAuth();

        $oldBalance = $this->fixtures->create(
            'balance',
            [
                'id'            => 'LN5BW4fDCb1Sn7',
                'balance'       => 50000,
                'type'          => 'primary',
                'merchant_id'   => '10000000000000'
            ]
        );

        $this->fixtures->create(
            'adjustment',
            [
                'id'            => 'LOkELUdz5YqBn2',
                'merchant_id'   => '10000000000000',
                'balance_id'    => 'LN5BW4fDCb1Sn7',
                'entity_type'   => 'dispute',
                'entity_id'     => 'LOkELLuV3ps5vW',
                'amount'        => 10100,
                'currency'      => 'INR',
                'description'   => 'Credit to reverse a previous dispute debit',
                'status'        => 'processed',
                'transaction_id'=> null,
            ]
        );

        $adjustment = $this->getDbLastEntity('adjustment');

        $this->assertNull($adjustment['transaction_id']); // before txn creation

        $transactionEntities = $this->getDbEntities('transaction');

        $this->assertEquals(0, count($transactionEntities));

        $response = $this->startTest();

        $this->assertNotNull($response, 'response should not be null');

        $transaction = $this->getDbEntityById('transaction', $response['transaction_id']);

        $this->assertNotNull($transaction, 'transaction should not be null');
        $this->assertEquals('LN5BWCGvLdPu7T', $transaction['id']);
        $this->assertEquals($adjustment['id'], $transaction['entity_id']);
        $this->assertEquals('adjustment', $transaction['type']);
        $this->assertEquals('10000000000000', $transaction['merchant_id']);
        $this->assertEquals(10100, $transaction['amount']);
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
        $this->assertEquals('10000000000000', $newBalance['merchant_id']);

        //should update balance after txn creation
        $this->assertEquals($updatedAdjustment['amount'], $newBalance['balance']-$oldBalance['balance']);
    }

    public function testDisputeWonAdjustmentTransactionCreateFailureWithInvalidRequest()
    {
        Mail::fake();
        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $this->ba->pgRouterAuth();

         $this->fixtures->create(
            'balance',
            [
                'id'            => 'LN5BW4fDCb1Sn7',
                'balance'       => 50000,
                'type'          => 'primary',
                'merchant_id'   => '10000000000000'
            ]
        );

        $this->fixtures->create(
            'adjustment',
            [
                'id'            => 'LOkELUdz5YqBn2',
                'merchant_id'   => '10000000000000',
                'balance_id'    => 'LN5BW4fDCb1Sn7',
                'entity_type'   => 'dispute',
                'entity_id'     => 'LOkELLuV3ps5vW',
                'amount'        => 10100,
                'currency'      => 'INR',
                'description'   => 'Credit to reverse a previous dispute debit',
                'status'        => 'processed',
                'transaction_id'=> null,
            ]
        );

        try
        {
            $this->startTest();
        }
        catch(\Exception $e)
        {
            $this->assertNotNull($e);

            $txn = $this->getDbLastEntity('transaction');

            $this->assertEquals('Both id and transaction_id are required.', $e->getMessage());

            $this->assertEquals('BAD_REQUEST_VALIDATION_FAILURE', $e->getCode());

            $this->assertNull($txn, 'transaction should not be created');
        }

    }

    public function testDisputeWonReversalSuccessWithLedgerRetryableError()
    {
        $this->app['config']->set('applications.ledger.enabled', true);
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $input = [
            'amount'                => 10100,
            'deduct_at_onset'       => 1,
        ];

        $testdata = $this->updateEditTestData($input);

        $oldMerchantBalance = $this->getDbEntityById('balance', $this->merchant['id'], true)['balance'];

        $this->ba->adminProxyAuth();

        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $expectedJournalPayload = [
            "merchant_id"           => "10000000000000",
            "currency"              => "INR",
            "transaction_date"      => "", // any
            "transactor_id"         => "", // disp_<id>
            "transactor_event"      => "razorpay_dispute_reversal",
            "money_params"          => [
                "merchant_balance_amount"       => "10100",
                "base_amount"                   => "10100",
                "gateway_dispute_payable_amount"=> "10100"
            ]
        ];

        $mockLedger->shouldReceive('createJournal')
            ->once()
            ->withArgs(function($journalPayload, $requestHeaders, $throwException) use ($expectedJournalPayload) {

                $this->assertArrayHasKey('transaction_date',$journalPayload);
                $this->assertArrayHasKey('transactor_id',$journalPayload);
                $this->assertEquals('disp', substr($journalPayload['transactor_id'],0,4));
                $this->assertEquals($expectedJournalPayload['currency'], $journalPayload['currency']);
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
                $this->assertEquals('disp', substr($journalPayload['transactor_id'],0,4));
                $this->assertEquals($expectedJournalPayload['currency'], $journalPayload['currency']);
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
                    "id"=> "LO8XsENYT8eoLp",
                    "created_at"=> "1678083477",
                    "updated_at"=> "1678083477",
                    "amount"=> "10100",
                    "base_amount"=> "10100",
                    "currency"=> "INR",
                    "tenant"=> "PG",
                    "transactor_id"=> "disp_LO8XoBzYDF42Qf",
                    "transactor_event"=> "razorpay_dispute_reversal",
                    "transaction_date"=> "1678083475",
                    "ledger_entry"=> [
                        [
                            "id"=> "LO8XsEY2FgCgvg",
                            "created_at"=> "1678083477",
                            "updated_at"=> "1678083477",
                            "merchant_id"=> "10000000000000",
                            "journal_id"=> "LO8XsENYT8eoLp",
                            "account_id"=> "JjpZUD9PmJeNPk",
                            "amount"=> "10100",
                            "base_amount"=> "10100",
                            "type"=> "credit",
                            "currency"=> "INR",
                            "balance"=> "540189.000000",
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
                            "id"=> "LO8XsEY3C4sdPg",
                            "created_at"=> "1678083477",
                            "updated_at"=> "1678083477",
                            "merchant_id"=> "10000000000000",
                            "journal_id"=> "LO8XsENYT8eoLp",
                            "account_id"=> "LO89uSsPrzZHUe",
                            "amount"=> "10100",
                            "base_amount"=> "10100",
                            "type"=> "debit",
                            "currency"=> "INR",
                            "balance"=> "",
                            "balance_updated"=> false,
                            "account_entities"=> [
                                "account_type"=> [
                                    "payable"
                                ],
                                "fund_account_type"=> [
                                    "gateway_dispute"
                                ]
                            ]
                        ]
                    ]
                ],
            ]);

        $response = $this->runRequestResponseFlow($testdata);

        $this->assertNotNull($response, 'response should not be null');

        $adjustment = $this->getDbLastEntity('adjustment');

        $dispute = $this->getDbLastEntity('dispute');

        $txn = $this->getDbEntity('transaction',['type'=> 'adjustment']);

        $newMerchantBalance = $this->getDbEntityById('balance', $dispute['merchant_id'])['balance'];

        $this->assertEquals($dispute['id'], substr($response['id'], 5));
        $this->assertEquals($testdata['request']['content']['status'], $response['status']);
        $this->assertEquals( $dispute['internal_status'], 'won');
        $this->assertEquals( $dispute['amount_reversed'], 10100);

        $this->assertNotNull($adjustment, 'adjustment should not be null');
        $this->assertEquals('10000000000000', $adjustment['merchant_id']);
        $this->assertEquals($adjustment['amount'], 10100);
        $this->assertEquals($adjustment['entity_type'], 'dispute','entity_type should be dispute');
        $this->assertEquals($adjustment['entity_id'], $dispute['id']);
        $this->assertNull($adjustment['transaction_id'], 'transaction should be null');
        $this->assertEquals(Status::PROCESSED, $adjustment['status']);

        $this->assertEquals(0, ($newMerchantBalance - $oldMerchantBalance), 'balance should not be updated until txn is created in async');

        $this->assertNotEquals($adjustment['id'], $txn['entity_id'],'transaction should not be created for current adjustment');

    }

    public function testDisputeWonReversalFailureWithLedgerNonRetryableError()
    {
        $this->app['config']->set('applications.ledger.enabled', true);
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $input = [
            'amount'                => 10100,
            'deduct_at_onset'       => 1,
        ];

        $testdata = $this->updateEditTestData($input);

        $this->getDbEntityById('balance', $this->merchant['id'], true)['balance'];

        $this->ba->adminProxyAuth();

        $this->fixtures->merchant->addFeatures(['pg_ledger_reverse_shadow']);

        $expectedJournalPayload = [
            "merchant_id"           => "10000000000000",
            "currency"              => "INR",
            "transaction_date"      => "", // any
            "transactor_id"         => "", // disp_<id>
            "transactor_event"      => "razorpay_dispute_reversal",
            "money_params"          => [
                "merchant_balance_amount"       => "10100",
                "base_amount"                   => "10100",
                "gateway_dispute_payable_amount"=> "10100"
            ]
        ];

        $mockLedger->shouldReceive('createJournal')
            ->times(1)
            ->withArgs(function($journalPayload, $requestHeaders, $throwException) use ($expectedJournalPayload) {

                $this->assertArrayHasKey('transaction_date',$journalPayload);
                $this->assertArrayHasKey('transactor_id',$journalPayload);
                $this->assertEquals('disp', substr($journalPayload['transactor_id'],0,4));
                $this->assertEquals($expectedJournalPayload['currency'], $journalPayload['currency']);
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
            $this->runRequestResponseFlow($testdata);
        }
        catch(\Exception $e)
        {
            $this->assertNotNull($e);

            $dispute = $this->getDbLastEntity('dispute');

            $this->assertNotEquals( $dispute['internal_status'], 'won');
            $this->assertEquals( $dispute['amount_reversed'], 0);

        }
    }

}
