<?php

namespace Functional\Refund;

use DB;
use Mail;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Admin\Admin\Entity as AdminEntity;
use RZP\Models\Merchant;
use RZP\Models\Payment\Method;
use RZP\Models\Merchant\Account;
use RZP\Services\KafkaMessageProcessor;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Helpers\Org\CustomBrandingTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Refund\Speed as RefundSpeed;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Payment\Refund\Status as RefundStatus;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\RazorxTrait;

class RefundLedgerTest extends TestCase
{

    use PaymentTrait;
    use DbEntityFetchTrait;
    use CustomBrandingTrait;
    use RazorxTrait;

    protected $payment = null;

    protected $sharedTerminal;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/RefundTestData.php';

        parent::setUp();

        $this->payment = $this->fixtures->create('payment:captured');

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_upi_mindgate_terminal');

        $this->fixtures->merchant->enableMethod(Account::TEST_ACCOUNT, Method::UPI);

        $this->ba->privateAuth();
    }

    public function testNormalRefundReverseShadow()
    {
        Mail::fake();

        $this->app['config']->set('applications.ledger.enabled', true);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $mockLedger->shouldReceive('createJournal')
            ->times(1)
            ->andReturn([
                    "body" => $this->getJournalCreateSuccessResponse()
                ]
            );

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->merchant->addFeatures('pg_ledger_reverse_shadow');

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            return $content;
        });

        $refund = $this->refundPayment($payment['id']);

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $this->assertGreaterThan(time() - 30, $refund['created_at']);

        $refund = $this->getLastEntity('refund', true);
        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertEquals(RefundSpeed::NORMAL, $refund['speed_processed']);
    }

    public function testNormalRefundReverseShadowFailure()
    {
        Mail::fake();

        $this->app['config']->set('applications.ledger.enabled', true);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $mockLedger->shouldReceive('createJournal')
            ->times(1)
            ->andThrow(new \Exception("invalid argument"), $this->getJournalCreateFailureResponse(), 400);

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->merchant->addFeatures('pg_ledger_reverse_shadow');

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            return $content;
        });

        try {
            $this->refundPayment($payment['id']);
        }
        catch (\Exception $e)
        {
            $this->assertNotNull($e);
            $this->assertEquals("LEDGER_JOURNAL_CREATE_ERROR", $e->getMessage());
        }
    }

    public function testInstantRefundReverseShadow()
    {
        Mail::fake();

        $this->app['config']->set('applications.ledger.enabled', true);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $mockLedger->shouldReceive('createJournal')
            ->times(1)
            ->andReturn([
                    "body" => $this->getJournalCreateSuccessResponse()
                ]
            );

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->merchant->addFeatures('pg_ledger_reverse_shadow');

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            return $content;
        });

        $this->fixtures->pricing->createInstantRefundsDefaultPricingplan();

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        $this->fixtures->pricing->createInstantRefundsModeLevelPricingPlan();

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $refund = $this->refundPayment(
            $payment['id'],
            3471,
            [
                'speed'    => 'optimum',
                'is_fta'   => true,
                'fta_data' => [
                    'card_transfer' => [
                        'card_id' => $payment['card_id']
                    ]
                ]
            ]
        );

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $this->assertGreaterThan(time() - 30, $refund['created_at']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertEquals(RefundSpeed::INSTANT, $refund['speed_processed']);
    }

    public function testNormalRefundReverseShadowDeductFromCredits()
    {
        Mail::fake();

        $this->fixtures->merchant->editRefundCredits('60000', '10000000000000');

        $this->fixtures->merchant->edit('10000000000000', ['refund_source' => 'credits']);

        $this->app['config']->set('applications.ledger.enabled', true);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $mockLedger->shouldReceive('createJournal')
            ->times(1)
            ->andReturn([
                    "body" => $this->getJournalCreateSuccessResponse()
                ]
            );

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->merchant->addFeatures('pg_ledger_reverse_shadow');

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            return $content;
        });

        $refund = $this->refundPayment($payment['id']);

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $this->assertGreaterThan(time() - 30, $refund['created_at']);

        $refund = $this->getLastEntity('refund', true);
        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertEquals(RefundSpeed::NORMAL, $refund['speed_processed']);
    }

    public function testInstantRefundReverseShadowDeductFromCredits()
    {
        Mail::fake();

        $this->fixtures->merchant->editRefundCredits('60000', '10000000000000');

        $this->fixtures->merchant->edit('10000000000000', ['refund_source' => 'credits']);

        $this->app['config']->set('applications.ledger.enabled', true);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $mockLedger->shouldReceive('createJournal')
            ->times(1)
            ->andReturn([
                    "body" => $this->getJournalCreateSuccessResponse()
                ]
            );

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->merchant->addFeatures('pg_ledger_reverse_shadow');

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            return $content;
        });

        $this->fixtures->pricing->createInstantRefundsDefaultPricingplan();

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        $this->fixtures->pricing->createInstantRefundsModeLevelPricingPlan();

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $refund = $this->refundPayment(
            $payment['id'],
            3471,
            [
                'speed'    => 'optimum',
                'is_fta'   => true,
                'fta_data' => [
                    'card_transfer' => [
                        'card_id' => $payment['card_id']
                    ]
                ]
            ]
        );

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $this->assertGreaterThan(time() - 30, $refund['created_at']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertEquals(RefundSpeed::INSTANT, $refund['speed_processed']);
    }

    public function testInstantRefundReverseShadowDeductFromCreditsPostpaid()
    {
        Mail::fake();

        $this->fixtures->merchant->setFeeModel(Merchant\FeeModel::POSTPAID);
        $this->fixtures->merchant->editRefundCredits('60000', '10000000000000');

        $this->fixtures->merchant->edit('10000000000000', ['refund_source' => 'credits']);

        $this->app['config']->set('applications.ledger.enabled', true);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $mockLedger->shouldReceive('createJournal')
            ->times(1)
            ->andReturn([
                    "body" => $this->getJournalCreateSuccessResponse()
                ]
            );

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->merchant->addFeatures('pg_ledger_reverse_shadow');

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            return $content;
        });

        $this->fixtures->pricing->createInstantRefundsDefaultPricingplan();

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        $this->fixtures->pricing->createInstantRefundsModeLevelPricingPlan();

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $refund = $this->refundPayment(
            $payment['id'],
            3471,
            [
                'speed'    => 'optimum',
                'is_fta'   => true,
                'fta_data' => [
                    'card_transfer' => [
                        'card_id' => $payment['card_id']
                    ]
                ]
            ]
        );

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $this->assertGreaterThan(time() - 30, $refund['created_at']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertEquals(RefundSpeed::INSTANT, $refund['speed_processed']);
    }

    public function testInstantRefundReverseShadowDeductFromBalancePostpaid()
    {
        Mail::fake();

        $this->fixtures->merchant->setFeeModel(Merchant\FeeModel::POSTPAID);

        $this->app['config']->set('applications.ledger.enabled', true);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $mockLedger->shouldReceive('createJournal')
            ->times(1)
            ->andReturn([
                    "body" => $this->getJournalCreateSuccessResponse()
                ]
            );

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->merchant->addFeatures('pg_ledger_reverse_shadow');

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            return $content;
        });

        $this->fixtures->pricing->createInstantRefundsDefaultPricingplan();

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        $this->fixtures->pricing->createInstantRefundsModeLevelPricingPlan();

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $refund = $this->refundPayment(
            $payment['id'],
            3471,
            [
                'speed'    => 'optimum',
                'is_fta'   => true,
                'fta_data' => [
                    'card_transfer' => [
                        'card_id' => $payment['card_id']
                    ]
                ]
            ]
        );

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $this->assertGreaterThan(time() - 30, $refund['created_at']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertEquals(RefundSpeed::INSTANT, $refund['speed_processed']);
    }

    public function testDSWithRefundInstantSpeed()
    {
        Mail::fake();

        $this->app['config']->set('applications.ledger.enabled', true);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $mockLedger->shouldReceive('createJournal')
            ->times(1)
            ->andReturn([
                    "body" => $this->getJournalCreateSuccessResponse()
                ]
            );

        $payment = $this->defaultAuthPayment();
        $this->capturePayment($payment['id'], $payment['amount']);

        $payment = $this->getLastEntity('payment', true);

        $this->fixtures->terminal->edit($payment['terminal_id'], [
            'type' => [
                'direct_settlement_with_refund' => '1',
                'recurring_3ds' => '1'
            ],
        ]);

        $this->fixtures->merchant->addFeatures('pg_ledger_reverse_shadow');

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            return $content;
        });

        $this->fixtures->pricing->createInstantRefundsDefaultPricingplan();

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        $this->fixtures->pricing->createInstantRefundsModeLevelPricingPlan();

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $refund = $this->refundPayment(
            $payment['id'],
            3471,
            [
                'speed'    => 'optimum',
                'is_fta'   => true,
                'fta_data' => [
                    'card_transfer' => [
                        'card_id' => $payment['card_id']
                    ]
                ]
            ]
        );

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $this->assertGreaterThan(time() - 30, $refund['created_at']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertEquals(RefundSpeed::INSTANT, $refund['speed_processed']);
    }

    public function getJournalCreateFailureResponse(): array
    {
        return [
                "code"  => "invalid_argument",
                "msg"   => "validation_failure: record_already_exist: BAD_REQUEST_RECORD_ALREADY_EXIST"
        ];
    }

    public function getJournalCreateSuccessResponse(): array
    {
        return [
            [
                "id" => "LP6jffEeZejP3v",
                "created_at" => "1678295443",
                "updated_at" => "1678295443",
                "amount" => "100",
                "base_amount" => "100",
                "currency" => "INR",
                "tenant" => "PG",
                "transactor_id" => "rfnd_LP6jdIRBWV1RCS",
                "transactor_event" => "refund_processed",
                "transaction_date" => "0",
                "ledger_entry" => [
                    [
                        "id" => "LP6jffKNJZ7A4O",
                        "created_at" => "1678295443",
                        "updated_at" => "1678295443",
                        "merchant_id" => "JVoa37lqQ0hMMv",
                        "journal_id" => "LP6jffEeZejP3v",
                        "account_id" => "JjpZUD9PmJeNPk",
                        "amount" => "100",
                        "base_amount" => "100",
                        "type" => "debit",
                        "currency" => "INR",
                        "balance" => "539189.000000",
                        "balance_updated" => true,
                        "account_entities" =>
                            [
                                "account_type" => ["payable"],
                                "fund_account_type" => ["merchant_balance"]
                            ]
                    ],
                    [
                        "id" => "LP6jffKOUMWtSE",
                        "created_at" => "1678295443",
                        "updated_at" => "1678295443",
                        "merchant_id" => "JVoa37lqQ0hMMv",
                        "journal_id" => "LP6jffEeZejP3v",
                        "account_id" => "Iu15tvUXWI1loc",
                        "amount" => "100",
                        "base_amount" => "100",
                        "type" => "credit",
                        "currency" => "INR",
                        "balance" => "",
                        "balance_updated" => false,
                        "account_entities" =>
                            [
                                "account_type" => ["payable"],
                                "fund_account_type" => ["gateway_refund"],
                                "gateway" => ["sharp"],
                                "terminal_id" => ["sharp"]
                            ]
                    ]
                ]
            ]
        ];
    }

    //Reversal Test cases

    //creates payment and refund entity for normal reversal testcases
    //adds pg_ledger_reverse_shadow flag to test merchant
    public function createRefundForReversalRefundShadow($mockLedger, $amount = 50000)
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->merchant->addFeatures('pg_ledger_reverse_shadow');

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            return $content;
        });

        $mockLedger->shouldReceive('createJournal')
            ->times(1)
            ->andReturn([
                    "body" => $this->getJournalCreateSuccessResponse()
                ]
            );

        $this->refundPayment($payment['id'], $amount);
    }

    public function testNormalRefundReversalReverseShadow()
    {
        $this->app['config']->set('applications.ledger.enabled', true);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $this->createRefundForReversalRefundShadow($mockLedger);

        $refund = $this->getLastEntity('refund', true);

        $this->assertGreaterThan(time() - 30, $refund['created_at']);
        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));
        $this->assertEquals('processed', $refund['status']);
        $this->assertNotNull($refund['transaction_id']);
        $this->assertNotNull($refund['balance_id']);
        $this->assertEquals(RefundSpeed::NORMAL, $refund['speed_processed']);
        $this->assertEquals(RefundSpeed::NORMAL, $refund['speed_decisioned']);

        $mockLedger->shouldReceive('fetchByTransactor')
            ->times(1)
            ->withArgs(function ($journalPayload, $requestHeaders, $throwException) use ($refund) {

                $this->assertEquals($refund['id'], $journalPayload['transactor_id']);
                $this->assertEquals('refund_processed', $journalPayload['transactor_event']);

                $this->assertArrayHasKey('idempotency-key', $requestHeaders);
                $this->assertEquals('PG', $requestHeaders['ledger-tenant']);

                $this->assertTrue($throwException);

                return true;
            })
            ->andReturn([
                "body" => $this->getJournalCreateSuccessResponse()
            ]);

        // create reversal
        $this->scroogeUpdateRefundStatus($refund, 'failed_event');

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertEquals(RefundStatus::REVERSED, $refund['status']);

        $reversal = $this->getLastEntity('reversal', true);

        $this->assertEquals($reversal['entity_type'], 'refund');
        $this->assertEquals('rfnd_' . $reversal['entity_id'], $refund['id']);
        $this->assertEquals($refund['balance_id'], $reversal['balance_id']);
        $this->assertNotNull(50000, $reversal['amount']);
        $this->assertNull($reversal['transaction_id'], 'reversal txn should not be created in sync');

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" => "10000000000000",
            "currency" => "INR",
            "transactor_event" => "refund_reversed",
            "money_params" => [
                "reversed_amount" => "50000",
                "merchant_balance_amount" => "50000",
            ],
            "ledger_integration_mode" => "reverse-shadow",
            "identifiers" => [
                "gateway" => "hdfc"
            ],
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($reversal['id'], $actualLedgerOutboxEntry['transactor_id']);
        $this->assertNotNull($actualLedgerOutboxEntry['idempotency_key']);

    }

    public function testNormalRefundReversalReverseShadowForZeroDebitAmount()
    {
        $this->app['config']->set('applications.ledger.enabled', true);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $this->createRefundForReversalRefundShadow($mockLedger);

        $refund = $this->getLastEntity('refund', true);

        $this->assertGreaterThan(time() - 30, $refund['created_at']);
        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));
        $this->assertEquals('processed', $refund['status']);
        $this->assertNotNull($refund['transaction_id']);
        $this->assertNotNull($refund['balance_id']);
        $this->assertEquals(RefundSpeed::NORMAL, $refund['speed_processed']);
        $this->assertEquals(RefundSpeed::NORMAL, $refund['speed_decisioned']);

        $mockLedger->shouldReceive('fetchByTransactor')
            ->times(1)
            ->withArgs(function ($journalPayload, $requestHeaders, $throwException) use ($refund) {

                $this->assertEquals($refund['id'], $journalPayload['transactor_id']);
                $this->assertEquals('refund_processed', $journalPayload['transactor_event']);

                $this->assertArrayHasKey('idempotency-key', $requestHeaders);
                $this->assertEquals('PG', $requestHeaders['ledger-tenant']);

                $this->assertTrue($throwException);

                return true;
            })
            ->andReturn([
                "body" => [
                    "id" => "LP6jffEeZejP3v",
                    "created_at" => "1678295443",
                    "updated_at" => "1678295443",
                    "amount" => "0",
                    "base_amount" => "0",
                    "currency" => "INR",
                    "tenant" => "PG",
                    "transactor_id" => "rfnd_LP6jdIRBWV1RCS",
                    "transactor_event" => "refund_processed",
                    "transaction_date" => "0",
                    "ledger_entry" => [
                        [
                            "id" => "LP6jffKNJZ7A4O",
                            "created_at" => "1678295443",
                            "updated_at" => "1678295443",
                            "merchant_id" => "JVoa37lqQ0hMMv",
                            "journal_id" => "LP6jffEeZejP3v",
                            "account_id" => "JjpZUD9PmJeNPk",
                            "amount" => "0",
                            "base_amount" => "0",
                            "type" => "debit",
                            "currency" => "INR",
                            "balance" => "539189.000000",
                            "balance_updated" => true,
                            "account_entities" =>
                                [
                                    "account_type" => ["payable"],
                                    "fund_account_type" => ["merchant_balance"]
                                ]
                        ],
                        [
                            "id" => "LP6jffKOUMWtSE",
                            "created_at" => "1678295443",
                            "updated_at" => "1678295443",
                            "merchant_id" => "JVoa37lqQ0hMMv",
                            "journal_id" => "LP6jffEeZejP3v",
                            "account_id" => "Iu15tvUXWI1loc",
                            "amount" => "0",
                            "base_amount" => "0",
                            "type" => "credit",
                            "currency" => "INR",
                            "balance" => "",
                            "balance_updated" => false,
                            "account_entities" =>
                                [
                                    "account_type" => ["payable"],
                                    "fund_account_type" => ["gateway_refund"],
                                    "gateway" => ["sharp"],
                                    "terminal_id" => ["sharp"]
                                ]
                        ]
                    ]
                ]
            ]);
        // create reversal
        $this->scroogeUpdateRefundStatus($refund, 'failed_event');

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(RefundStatus::PROCESSED, $refund['status']);

        $reversal = $this->getLastEntity('reversal', true);
        $this->assertNull($reversal);
    }

    public function testNormalRefundReversalWithCreditsReverseShadow()
    {
        Mail::fake();

        $this->fixtures->merchant->editRefundCredits('60000', '10000000000000');
        $this->fixtures->merchant->edit('10000000000000', ['refund_source' => 'credits']);

        $oldMerchantBalance = $this->getDbEntityById('balance', '10000000000000');
        $this->assertEquals(60000, $oldMerchantBalance['refund_credits']);

        $this->app['config']->set('applications.ledger.enabled', true);
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $this->createRefundForReversalRefundShadow($mockLedger);

        $refund = $this->getLastEntity('refund', true);

        $this->assertGreaterThan(time() - 30, $refund['created_at']);
        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));
        $this->assertEquals('processed', $refund['status']);
        $this->assertEquals(50000, $refund['amount']);
        $this->assertNotNull($refund['transaction_id']);
        $this->assertNotNull($refund['balance_id']);
        $this->assertEquals(RefundSpeed::NORMAL, $refund['speed_processed']);
        $this->assertEquals(RefundSpeed::NORMAL, $refund['speed_decisioned']);

        $mockLedger->shouldReceive('fetchByTransactor')
            ->times(1)
            ->withArgs(function ($journalPayload, $requestHeaders, $throwException) use ($refund) {

                $this->assertEquals($refund['id'], $journalPayload['transactor_id']);
                $this->assertEquals('refund_processed', $journalPayload['transactor_event']);

                $this->assertArrayHasKey('idempotency-key', $requestHeaders);
                $this->assertEquals('PG', $requestHeaders['ledger-tenant']);

                $this->assertTrue($throwException);

                return true;
            })
            ->andReturn([
                'code' => 200,
                "body" => [
                    "id" => "LP6jffEeZejP3v",
                    "created_at" => "1678295443",
                    "updated_at" => "1678295443",
                    "amount" => "50000",
                    "base_amount" => "50000",
                    "currency" => "INR",
                    "tenant" => "PG",
                    "transactor_id" => $refund['id'],
                    "transactor_event" => "refund_processed",
                    "transaction_date" => "0",
                    "ledger_entry" => [
                        [
                            "id" => "LP6jffKNJZ7A4O",
                            "created_at" => "1678295443",
                            "updated_at" => "1678295443",
                            "merchant_id" => "10000000000000",
                            "journal_id" => "LP6jffEeZejP3v",
                            "account_id" => "JjpZUD9PmJeNPk",
                            "amount" => "50000",
                            "base_amount" => "50000",
                            "type" => "debit",
                            "currency" => "INR",
                            "balance" => "10000",
                            "balance_updated" => true,
                            "account_entities" =>
                                [
                                    "account_type" => ["payable"],
                                    "fund_account_type" => ["merchant_refund_credits"]
                                ]
                        ],
                        [
                            "id" => "LP6jffKOUMWtSE",
                            "created_at" => "1678295443",
                            "updated_at" => "1678295443",
                            "merchant_id" => "10000000000000",
                            "journal_id" => "LP6jffEeZejP3v",
                            "account_id" => "Iu15tvUXWI1loc",
                            "amount" => "50000",
                            "base_amount" => "50000",
                            "type" => "credit",
                            "currency" => "INR",
                            "balance" => "0",
                            "balance_updated" => false,
                            "account_entities" =>
                                [
                                    "account_type" => ["payable"],
                                    "fund_account_type" => ["gateway_refund"],
                                    "gateway" => ["sharp"],
                                    "terminal_id" => ["sharp"]
                                ]
                        ]
                    ]
                ]
            ]);

        $this->scroogeUpdateRefundStatus($refund, 'failed_event');

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertEquals(RefundStatus::REVERSED, $refund['status']);

        $reversal = $this->getLastEntity('reversal', true);

        $this->assertEquals($reversal['entity_type'], 'refund');
        $this->assertEquals('rfnd_' . $reversal['entity_id'], $refund['id']);
        $this->assertEquals($refund['balance_id'], $reversal['balance_id']);
        $this->assertNotNull(50000, $reversal['amount']);
        $this->assertNull($reversal['transaction_id'], 'reversal txn should not be created in sync');

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" => "10000000000000",
            "currency" => "INR",
            "transactor_event" => "refund_reversed",
            "money_params" => [
                "reversed_amount" => "50000",
                "refund_credits" => "50000",
            ],
            "additional_params" => [
                'reverse_refund_accounting' => 'refund_reversed_credits'
            ],
            "identifiers" => [
                "gateway" => "hdfc"
            ],
            "ledger_integration_mode" => "reverse-shadow",
            "tenant" => "PG",
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($reversal['id'], $actualLedgerOutboxEntry['transactor_id']);
        $this->assertNotNull($actualLedgerOutboxEntry['idempotency_key']);

        $newMerchantBalance = $this->getDbEntityById('balance', '10000000000000');

        $this->assertEquals($oldMerchantBalance['refund_credits'] - $reversal['amount'], $newMerchantBalance['refund_credits']);
    }

    public function testInstantRefundReversalReverseShadow()
    {
        Mail::fake();

        $this->app['config']->set('applications.ledger.enabled', true);
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $mockLedger->shouldReceive('createJournal')
            ->times(1)
            ->andReturn([
                    "body" => $this->getJournalCreateSuccessResponse()
                ]
            );

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->merchant->addFeatures('pg_ledger_reverse_shadow');

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (&$content, $action = null) {
            if ($action === 'verify') {
                $content['result'] = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2'] = '';
                $content['udf5'] = 'TrackID';
            }

            return $content;
        });

        $this->fixtures->pricing->createInstantRefundsDefaultPricingplan();

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        $this->fixtures->pricing->createInstantRefundsModeLevelPricingPlan();

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $response = $this->refundPayment(
            $payment['id'],
            3471,
            [
                'speed' => 'optimum',
                'is_fta' => true,
                'fta_data' => [
                    'card_transfer' => [
                        'card_id' => $payment['card_id']
                    ]
                ]
            ]
        );

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals($response['id'], $refund['id']);
        $this->assertEquals(RefundStatus::PROCESSED, $refund['status']);
        $this->assertNotNull($refund['transaction_id']);
        $this->assertNotNull($refund['balance_id']);
        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertEquals(RefundSpeed::INSTANT, $refund['speed_processed']);
        $this->assertEquals(RefundSpeed::OPTIMUM, $refund['speed_decisioned']);

        $mockLedger->shouldReceive('fetchByTransactor')
            ->times(1)
            ->withArgs(function ($journalPayload, $requestHeaders, $throwException) use ($refund) {

                $this->assertEquals($refund['id'], $journalPayload['transactor_id']);
                $this->assertEquals('refund_processed', $journalPayload['transactor_event']);

                $this->assertArrayHasKey('idempotency-key', $requestHeaders);
                $this->assertEquals('PG', $requestHeaders['ledger-tenant']);

                $this->assertTrue($throwException);

                return true;
            })
            ->andReturn([
                'code' => 200,
                "body" => [
                    "id" => "LP6jffEeZejP3v",
                    "created_at" => "1678295443",
                    "updated_at" => "1678295443",
                    "amount" => "3471",
                    "base_amount" => "3471",
                    "currency" => "INR",
                    "tenant" => "PG",
                    "transactor_id" => $refund['id'],
                    "transactor_event" => "refund_processed",
                    "transaction_date" => "0",
                    "ledger_entry" => [
                        [
                            "id" => "LP6jffKNJZ7A4O",
                            "created_at" => "1678295443",
                            "updated_at" => "1678295443",
                            "merchant_id" => "10000000000000",
                            "journal_id" => "LP6jffEeZejP3v",
                            "account_id" => "JjpZUD9PmJeNPk",
                            "amount" => "4179",
                            "base_amount" => "4179",
                            "type" => "debit",
                            "currency" => "INR",
                            "balance" => "10000",
                            "balance_updated" => true,
                            "account_entities" =>
                                [
                                    "account_type" => ["payable"],
                                    "fund_account_type" => ["merchant_balance"]
                                ]
                        ],
                        [
                            "id" => "LP6jffKNJZ7A41",
                            "created_at" => "1678295443",
                            "updated_at" => "1678295443",
                            "merchant_id" => "10000000000000",
                            "journal_id" => "LP6jffEeZejP3v",
                            "account_id" => "Iu15tvUXWI1loc",
                            "amount" => "3471",
                            "base_amount" => "3471",
                            "type" => "credit",
                            "currency" => "INR",
                            "balance" => "0",
                            "balance_updated" => false,
                            "account_entities" =>
                                [
                                    "account_type" => ["payable"],
                                    "fund_account_type" => ["customer_refund"],
                                ]
                        ],
                        [
                            "id" => "LP6jffKNJZ7A42",
                            "created_at" => "1678295443",
                            "updated_at" => "1678295443",
                            "merchant_id" => "10000000000000",
                            "journal_id" => "LP6jffEeZejP3v",
                            "account_id" => "Iu15tvUXWI1loc",
                            "amount" => "708",
                            "base_amount" => "708",
                            "type" => "credit",
                            "currency" => "INR",
                            "balance" => "0",
                            "balance_updated" => false,
                            "account_entities" =>
                                [
                                    "account_type" => ["receivable"],
                                    "fund_account_type" => ["rzp_commission"],
                                ]
                        ],
                        [
                            "id" => "LP6jffKNJZ7A43",
                            "created_at" => "1678295443",
                            "updated_at" => "1678295443",
                            "merchant_id" => "10000000000000",
                            "journal_id" => "LP6jffEeZejP3v",
                            "account_id" => "Iu15tvUXWI1loc",
                            "amount" => "108",
                            "base_amount" => "108",
                            "type" => "credit",
                            "currency" => "INR",
                            "balance" => "0",
                            "balance_updated" => false,
                            "account_entities" =>
                                [
                                    "account_type" => ["payable"],
                                    "fund_account_type" => ["rzp_gst"],
                                ]
                        ]
                    ]
                ]
            ]);

        // create reversal
        $this->scroogeUpdateRefundStatus($refund, 'failed_event');

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(RefundStatus::REVERSED, $refund['status']);

        $reversal = $this->getLastEntity('reversal', true);

        $this->assertEquals($reversal['entity_type'], 'refund');
        $this->assertEquals('rfnd_' . $reversal['entity_id'], $refund['id']);
        $this->assertEquals($refund['balance_id'], $reversal['balance_id']);
        $this->assertNotNull(3471, $reversal['amount']);
        $this->assertNull($reversal['transaction_id'], 'reversal txn should not be created in sync');

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" => "10000000000000",
            "currency" => "INR",
            "transactor_event" => "refund_reversed",
            "money_params" => [
                "reversed_amount" => "3471",
                "merchant_balance_amount" => "4179",
                "commission" => "600",
                "tax" => "108"
            ],
            "additional_params" => [
                'reverse_refund_accounting' => 'instant_refund_reversed'
            ],
            "ledger_integration_mode" => "reverse-shadow",
            "identifiers" => [
                "gateway" => "hdfc"
            ],
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($reversal['id'], $actualLedgerOutboxEntry['transactor_id']);
        $this->assertNotNull($actualLedgerOutboxEntry['idempotency_key']);
    }

    public function testInstantRefundReversalWithCreditsReverseShadow()
    {
        Mail::fake();

        $this->fixtures->merchant->editRefundCredits('60000', '10000000000000');
        $this->fixtures->merchant->edit('10000000000000', ['refund_source' => 'credits']);

        $oldMerchantBalance = $this->getDbEntityById('balance', '10000000000000');
        $this->assertEquals(60000, $oldMerchantBalance['refund_credits']);

        $this->app['config']->set('applications.ledger.enabled', true);
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $mockLedger->shouldReceive('createJournal')
            ->times(1)
            ->andReturn([
                    "body" => $this->getJournalCreateSuccessResponse()
                ]
            );

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->merchant->addFeatures('pg_ledger_reverse_shadow');

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (&$content, $action = null) {
            if ($action === 'verify') {
                $content['result'] = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2'] = '';
                $content['udf5'] = 'TrackID';
            }

            return $content;
        });

        $this->fixtures->pricing->createInstantRefundsDefaultPricingplan();

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        $this->fixtures->pricing->createInstantRefundsModeLevelPricingPlan();

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $response = $this->refundPayment(
            $payment['id'],
            3471,
            [
                'speed' => 'optimum',
                'is_fta' => true,
                'fta_data' => [
                    'card_transfer' => [
                        'card_id' => $payment['card_id']
                    ]
                ]
            ]
        );

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals($response['id'], $refund['id']);
        $this->assertEquals(RefundStatus::PROCESSED, $refund['status']);
        $this->assertNotNull($refund['transaction_id']);
        $this->assertNotNull($refund['balance_id']);
        $this->assertEquals(RefundSpeed::INSTANT, $refund['speed_processed']);
        $this->assertEquals(RefundSpeed::OPTIMUM, $refund['speed_decisioned']);

        $mockLedger->shouldReceive('fetchByTransactor')
            ->times(1)
            ->withArgs(function ($journalPayload, $requestHeaders, $throwException) use ($refund) {

                $this->assertEquals($refund['id'], $journalPayload['transactor_id']);
                $this->assertEquals('refund_processed', $journalPayload['transactor_event']);

                $this->assertArrayHasKey('idempotency-key', $requestHeaders);
                $this->assertEquals('PG', $requestHeaders['ledger-tenant']);

                $this->assertTrue($throwException);

                return true;
            })
            ->andReturn([
                'code' => 200,
                "body" => [
                    "id" => "LP6jffEeZejP3v",
                    "created_at" => "1678295443",
                    "updated_at" => "1678295443",
                    "amount" => "3471",
                    "base_amount" => "3471",
                    "currency" => "INR",
                    "tenant" => "PG",
                    "transactor_id" => $refund['id'],
                    "transactor_event" => "refund_processed",
                    "transaction_date" => "0",
                    "ledger_entry" => [
                        [
                            "id" => "LP6jffKNJZ7A4O",
                            "created_at" => "1678295443",
                            "updated_at" => "1678295443",
                            "merchant_id" => "10000000000000",
                            "journal_id" => "LP6jffEeZejP3v",
                            "account_id" => "JjpZUD9PmJeNPk",
                            "amount" => "4179",
                            "base_amount" => "4179",
                            "type" => "debit",
                            "currency" => "INR",
                            "balance" => "10000",
                            "balance_updated" => true,
                            "account_entities" =>
                                [
                                    "account_type" => ["payable"],
                                    "fund_account_type" => ["merchant_refund_credits"]
                                ]
                        ],
                        [
                            "id" => "LP6jffKNJZ7A41",
                            "created_at" => "1678295443",
                            "updated_at" => "1678295443",
                            "merchant_id" => "10000000000000",
                            "journal_id" => "LP6jffEeZejP3v",
                            "account_id" => "Iu15tvUXWI1loc",
                            "amount" => "3471",
                            "base_amount" => "3471",
                            "type" => "credit",
                            "currency" => "INR",
                            "balance" => "0",
                            "balance_updated" => false,
                            "account_entities" =>
                                [
                                    "account_type" => ["payable"],
                                    "fund_account_type" => ["customer_refund"],
                                ]
                        ],
                        [
                            "id" => "LP6jffKNJZ7A42",
                            "created_at" => "1678295443",
                            "updated_at" => "1678295443",
                            "merchant_id" => "10000000000000",
                            "journal_id" => "LP6jffEeZejP3v",
                            "account_id" => "Iu15tvUXWI1loc",
                            "amount" => "708",
                            "base_amount" => "708",
                            "type" => "credit",
                            "currency" => "INR",
                            "balance" => "0",
                            "balance_updated" => false,
                            "account_entities" =>
                                [
                                    "account_type" => ["receivable"],
                                    "fund_account_type" => ["rzp_commission"],
                                ]
                        ],
                        [
                            "id" => "LP6jffKNJZ7A43",
                            "created_at" => "1678295443",
                            "updated_at" => "1678295443",
                            "merchant_id" => "10000000000000",
                            "journal_id" => "LP6jffEeZejP3v",
                            "account_id" => "Iu15tvUXWI1loc",
                            "amount" => "108",
                            "base_amount" => "108",
                            "type" => "credit",
                            "currency" => "INR",
                            "balance" => "0",
                            "balance_updated" => false,
                            "account_entities" =>
                                [
                                    "account_type" => ["payable"],
                                    "fund_account_type" => ["rzp_gst"],
                                ]
                        ]
                    ]
                ]
            ]);

        // create reversal
        $this->scroogeUpdateRefundStatus($refund, 'failed_event');

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(RefundStatus::REVERSED, $refund['status']);

        $reversal = $this->getLastEntity('reversal', true);

        $this->assertEquals($reversal['entity_type'], 'refund');
        $this->assertEquals('rfnd_' . $reversal['entity_id'], $refund['id']);
        $this->assertEquals($refund['balance_id'], $reversal['balance_id']);
        $this->assertNotNull(3471, $reversal['amount']);
        $this->assertNull($reversal['transaction_id'], 'reversal txn should not be created in sync');

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" => "10000000000000",
            "currency" => "INR",
            "transactor_event" => "refund_reversed",
            "money_params" => [
                "reversed_amount" => "3471",
                "refund_credits" => "4179",
                "commission" => "600",
                "tax" => "108"
            ],
            "additional_params" => [
                'reverse_refund_accounting' => 'instant_refund_reversed_credits'
            ],
            "ledger_integration_mode" => "reverse-shadow",
            "identifiers" => [
                "gateway" => "hdfc"
            ],
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($reversal['id'], $actualLedgerOutboxEntry['transactor_id']);
        $this->assertNotNull($actualLedgerOutboxEntry['idempotency_key']);

        $newMerchantBalance = $this->getDbEntityById('balance', '10000000000000');

        $this->assertEquals($oldMerchantBalance['refund_credits'] - 4179, $newMerchantBalance['refund_credits']);
    }

    public function testInstantRefundFeeOnlyReversalReverseShadow()
    {
        Mail::fake();

        $this->app['config']->set('applications.ledger.enabled', true);
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $mockLedger->shouldReceive('createJournal')
            ->times(1)
            ->andReturn([
                    "body" => $this->getJournalCreateSuccessResponse()
                ]
            );

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->merchant->addFeatures('pg_ledger_reverse_shadow');

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (&$content, $action = null) {
            if ($action === 'verify') {
                $content['result'] = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2'] = '';
                $content['udf5'] = 'TrackID';
            }

            return $content;
        });

        $this->fixtures->pricing->createInstantRefundsDefaultPricingplan();

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        $this->fixtures->pricing->createInstantRefundsModeLevelPricingPlan();

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $response = $this->refundPayment(
            $payment['id'],
            3471,
            [
                'speed' => 'optimum',
                'is_fta' => true,
                'fta_data' => [
                    'card_transfer' => [
                        'card_id' => $payment['card_id']
                    ]
                ]
            ]
        );

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals($response['id'], $refund['id']);
        $this->assertEquals(RefundStatus::PROCESSED, $refund['status']);
        $this->assertNotNull($refund['transaction_id']);
        $this->assertNotNull($refund['balance_id']);
        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertEquals(RefundSpeed::INSTANT, $refund['speed_processed']);
        $this->assertEquals(RefundSpeed::OPTIMUM, $refund['speed_decisioned']);

        $mockLedger->shouldReceive('fetchByTransactor')
            ->times(1)
            ->withArgs(function ($journalPayload, $requestHeaders, $throwException) use ($refund) {

                $this->assertEquals($refund['id'], $journalPayload['transactor_id']);
                $this->assertEquals('refund_processed', $journalPayload['transactor_event']);

                $this->assertArrayHasKey('idempotency-key', $requestHeaders);
                $this->assertEquals('PG', $requestHeaders['ledger-tenant']);

                $this->assertTrue($throwException);

                return true;
            })
            ->andReturn([
                'code' => 200,
                "body" => [
                    "id" => "LP6jffEeZejP3v",
                    "created_at" => "1678295443",
                    "updated_at" => "1678295443",
                    "amount" => "3471",
                    "base_amount" => "3471",
                    "currency" => "INR",
                    "tenant" => "PG",
                    "transactor_id" => $refund['id'],
                    "transactor_event" => "refund_processed",
                    "transaction_date" => "0",
                    "ledger_entry" => [
                        [
                            "id" => "LP6jffKNJZ7A4O",
                            "created_at" => "1678295443",
                            "updated_at" => "1678295443",
                            "merchant_id" => "10000000000000",
                            "journal_id" => "LP6jffEeZejP3v",
                            "account_id" => "JjpZUD9PmJeNPk",
                            "amount" => "4179",
                            "base_amount" => "4179",
                            "type" => "debit",
                            "currency" => "INR",
                            "balance" => "10000",
                            "balance_updated" => true,
                            "account_entities" =>
                                [
                                    "account_type" => ["payable"],
                                    "fund_account_type" => ["merchant_balance"]
                                ]
                        ],
                        [
                            "id" => "LP6jffKNJZ7A41",
                            "created_at" => "1678295443",
                            "updated_at" => "1678295443",
                            "merchant_id" => "10000000000000",
                            "journal_id" => "LP6jffEeZejP3v",
                            "account_id" => "Iu15tvUXWI1loc",
                            "amount" => "3471",
                            "base_amount" => "3471",
                            "type" => "credit",
                            "currency" => "INR",
                            "balance" => "0",
                            "balance_updated" => false,
                            "account_entities" =>
                                [
                                    "account_type" => ["payable"],
                                    "fund_account_type" => ["customer_refund"],
                                ]
                        ],
                        [
                            "id" => "LP6jffKNJZ7A42",
                            "created_at" => "1678295443",
                            "updated_at" => "1678295443",
                            "merchant_id" => "10000000000000",
                            "journal_id" => "LP6jffEeZejP3v",
                            "account_id" => "Iu15tvUXWI1loc",
                            "amount" => "708",
                            "base_amount" => "708",
                            "type" => "credit",
                            "currency" => "INR",
                            "balance" => "0",
                            "balance_updated" => false,
                            "account_entities" =>
                                [
                                    "account_type" => ["receivable"],
                                    "fund_account_type" => ["rzp_commission"],
                                ]
                        ],
                        [
                            "id" => "LP6jffKNJZ7A43",
                            "created_at" => "1678295443",
                            "updated_at" => "1678295443",
                            "merchant_id" => "10000000000000",
                            "journal_id" => "LP6jffEeZejP3v",
                            "account_id" => "Iu15tvUXWI1loc",
                            "amount" => "108",
                            "base_amount" => "108",
                            "type" => "credit",
                            "currency" => "INR",
                            "balance" => "0",
                            "balance_updated" => false,
                            "account_entities" =>
                                [
                                    "account_type" => ["payable"],
                                    "fund_account_type" => ["rzp_gst"],
                                ]
                        ]
                    ]
                ]
            ]);

        // create reversal
        $this->scroogeUpdateRefundStatus($refund, 'fee_only_reversal_event'); //

        $refund = $this->getLastEntity('refund', true);

        $this->assertNotEquals(RefundStatus::REVERSED, $refund['status']);

        $reversal = $this->getLastEntity('reversal', true);

        $this->assertEquals($reversal['entity_type'], 'refund');
        $this->assertEquals('rfnd_' . $reversal['entity_id'], $refund['id']);
        $this->assertEquals($refund['balance_id'], $reversal['balance_id']);
        $this->assertNotNull(0, $reversal['amount']);
        $this->assertNull($reversal['transaction_id'], 'reversal txn should not be created in sync');

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" => "10000000000000",
            "currency" => "INR",
            "transactor_event" => "refund_reversed",
            "money_params" => [
                "reversed_amount" => "0",
                "merchant_balance_amount" => "708",
                "commission" => "600",
                "tax" => "108"
            ],
            "additional_params" => [
                'reverse_refund_accounting' => 'instant_refund_reversed'
            ],
            "ledger_integration_mode" => "reverse-shadow",
            "identifiers" => [
                "gateway" => "hdfc"
            ],
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($reversal['id'], $actualLedgerOutboxEntry['transactor_id']);
        $this->assertNotNull($actualLedgerOutboxEntry['idempotency_key']);
    }

    public function testInstantRefundFeeOnlyReversalWithCreditsReverseShadow()
    {
        Mail::fake();

        $this->fixtures->merchant->editRefundCredits('60000', '10000000000000');
        $this->fixtures->merchant->edit('10000000000000', ['refund_source' => 'credits']);

        $oldMerchantBalance = $this->getDbEntityById('balance', '10000000000000');
        $this->assertEquals(60000, $oldMerchantBalance['refund_credits']);

        $this->app['config']->set('applications.ledger.enabled', true);
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $mockLedger->shouldReceive('createJournal')
            ->times(1)
            ->andReturn([
                    "body" => $this->getJournalCreateSuccessResponse()
                ]
            );

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->merchant->addFeatures('pg_ledger_reverse_shadow');

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (&$content, $action = null) {
            if ($action === 'verify') {
                $content['result'] = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2'] = '';
                $content['udf5'] = 'TrackID';
            }

            return $content;
        });

        $this->fixtures->pricing->createInstantRefundsDefaultPricingplan();

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        $this->fixtures->pricing->createInstantRefundsModeLevelPricingPlan();

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $response = $this->refundPayment(
            $payment['id'],
            3471,
            [
                'speed' => 'optimum',
                'is_fta' => true,
                'fta_data' => [
                    'card_transfer' => [
                        'card_id' => $payment['card_id']
                    ]
                ]
            ]
        );

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals($response['id'], $refund['id']);
        $this->assertEquals(RefundStatus::PROCESSED, $refund['status']);
        $this->assertNotNull($refund['transaction_id']);
        $this->assertNotNull($refund['balance_id']);
        $this->assertEquals(RefundSpeed::INSTANT, $refund['speed_processed']);
        $this->assertEquals(RefundSpeed::OPTIMUM, $refund['speed_decisioned']);

        $mockLedger->shouldReceive('fetchByTransactor')
            ->times(1)
            ->withArgs(function ($journalPayload, $requestHeaders, $throwException) use ($refund) {

                $this->assertEquals($refund['id'], $journalPayload['transactor_id']);
                $this->assertEquals('refund_processed', $journalPayload['transactor_event']);

                $this->assertArrayHasKey('idempotency-key', $requestHeaders);
                $this->assertEquals('PG', $requestHeaders['ledger-tenant']);

                $this->assertTrue($throwException);

                return true;
            })
            ->andReturn([
                'code' => 200,
                "body" => [
                    "id" => "LP6jffEeZejP3v",
                    "created_at" => "1678295443",
                    "updated_at" => "1678295443",
                    "amount" => "3471",
                    "base_amount" => "3471",
                    "currency" => "INR",
                    "tenant" => "PG",
                    "transactor_id" => $refund['id'],
                    "transactor_event" => "refund_processed",
                    "transaction_date" => "0",
                    "ledger_entry" => [
                        [
                            "id" => "LP6jffKNJZ7A4O",
                            "created_at" => "1678295443",
                            "updated_at" => "1678295443",
                            "merchant_id" => "10000000000000",
                            "journal_id" => "LP6jffEeZejP3v",
                            "account_id" => "JjpZUD9PmJeNPk",
                            "amount" => "4179",
                            "base_amount" => "4179",
                            "type" => "debit",
                            "currency" => "INR",
                            "balance" => "10000",
                            "balance_updated" => true,
                            "account_entities" =>
                                [
                                    "account_type" => ["payable"],
                                    "fund_account_type" => ["merchant_refund_credits"]
                                ]
                        ],
                        [
                            "id" => "LP6jffKNJZ7A41",
                            "created_at" => "1678295443",
                            "updated_at" => "1678295443",
                            "merchant_id" => "10000000000000",
                            "journal_id" => "LP6jffEeZejP3v",
                            "account_id" => "Iu15tvUXWI1loc",
                            "amount" => "3471",
                            "base_amount" => "3471",
                            "type" => "credit",
                            "currency" => "INR",
                            "balance" => "0",
                            "balance_updated" => false,
                            "account_entities" =>
                                [
                                    "account_type" => ["payable"],
                                    "fund_account_type" => ["customer_refund"],
                                ]
                        ],
                        [
                            "id" => "LP6jffKNJZ7A42",
                            "created_at" => "1678295443",
                            "updated_at" => "1678295443",
                            "merchant_id" => "10000000000000",
                            "journal_id" => "LP6jffEeZejP3v",
                            "account_id" => "Iu15tvUXWI1loc",
                            "amount" => "708",
                            "base_amount" => "708",
                            "type" => "credit",
                            "currency" => "INR",
                            "balance" => "0",
                            "balance_updated" => false,
                            "account_entities" =>
                                [
                                    "account_type" => ["receivable"],
                                    "fund_account_type" => ["rzp_commission"],
                                ]
                        ],
                        [
                            "id" => "LP6jffKNJZ7A43",
                            "created_at" => "1678295443",
                            "updated_at" => "1678295443",
                            "merchant_id" => "10000000000000",
                            "journal_id" => "LP6jffEeZejP3v",
                            "account_id" => "Iu15tvUXWI1loc",
                            "amount" => "108",
                            "base_amount" => "108",
                            "type" => "credit",
                            "currency" => "INR",
                            "balance" => "0",
                            "balance_updated" => false,
                            "account_entities" =>
                                [
                                    "account_type" => ["payable"],
                                    "fund_account_type" => ["rzp_gst"],
                                ]
                        ]
                    ]
                ]
            ]);

        // create reversal
        $this->scroogeUpdateRefundStatus($refund, 'fee_only_reversal_event');

        $refund = $this->getLastEntity('refund', true);

        $this->assertNotEquals(RefundStatus::REVERSED, $refund['status']);
        $this->assertEquals(0, $refund['fee']);
        $this->assertEquals(0, $refund['tax']);

        $reversal = $this->getLastEntity('reversal', true);

        $this->assertEquals($reversal['entity_type'], 'refund');
        $this->assertEquals('rfnd_' . $reversal['entity_id'], $refund['id']);
        $this->assertEquals($refund['balance_id'], $reversal['balance_id']);
        $this->assertNotNull(0, $reversal['amount']);
        $this->assertNull($reversal['transaction_id'], 'reversal txn should not be created in sync');

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" => "10000000000000",
            "currency" => "INR",
            "transactor_event" => "refund_reversed",
            "money_params" => [
                "reversed_amount" => "0",
                "refund_credits" => "708",
                "commission" => "600",
                "tax" => "108"
            ],
            "additional_params" => [
                'reverse_refund_accounting' => 'instant_refund_reversed_credits'
            ],
            "ledger_integration_mode" => "reverse-shadow",
            "identifiers" => [
                "gateway" => "hdfc"
            ],
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($reversal['id'], $actualLedgerOutboxEntry['transactor_id']);
        $this->assertNotNull($actualLedgerOutboxEntry['idempotency_key']);

        $newMerchantBalance = $this->getDbEntityById('balance', '10000000000000');

        //reversal's refund credits will not be deducted from balance until reversal txn is created
        $this->assertEquals($oldMerchantBalance['refund_credits'] - 4179, $newMerchantBalance['refund_credits']);
    }

    public function testNormalRefundReversalReverseShadowFailure()
    {
        $this->app['config']->set('applications.ledger.enabled', true);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $this->createRefundForReversalRefundShadow($mockLedger);

        $refund = $this->getLastEntity('refund', true);

        $this->assertGreaterThan(time() - 30, $refund['created_at']);
        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));
        $this->assertEquals('processed', $refund['status']);
        $this->assertNotNull($refund['transaction_id']);
        $this->assertNotNull($refund['balance_id']);
        $this->assertEquals(RefundSpeed::NORMAL, $refund['speed_processed']);
        $this->assertEquals(RefundSpeed::NORMAL, $refund['speed_decisioned']);

        $mockLedger->shouldReceive('fetchByTransactor')
            ->times(1)
            ->withArgs(function ($journalPayload, $requestHeaders, $throwException) use ($refund) {

                $this->assertEquals($refund['id'], $journalPayload['transactor_id']);
                $this->assertEquals('refund_processed', $journalPayload['transactor_event']);

                $this->assertArrayHasKey('idempotency-key', $requestHeaders);
                $this->assertEquals('PG', $requestHeaders['ledger-tenant']);

                $this->assertTrue($throwException);

                return true;
            })
            ->andThrow(new Exception\RuntimeException(
                'Unexpected response code received from Ledger service.',
                [
                    'status_code' => 500,
                    'response_body' => [
                        'code' => 'invalid_argument',
                        'msg' => 'record_not_found',
                    ],
                ]
            ));

        try {
            // create reversal
            $this->scroogeUpdateRefundStatus($refund, 'failed_event');
        } catch (\Exception $e) {
            $this->assertNotNull($e);
            $reversal = $this->getLastEntity('reversal', true);
            $this->assertNull($reversal);
        }
    }

    public function getReversalJournalCreateSuccessResponse($reversalId): array
    {
        return [
            "id" => "LLpAtAQhlnVeHg",
            "created_at" => 1677578580,
            "updated_at" => 1677578580,
            "amount" => "100",
            "base_amount" => "100",
            "currency" => "INR",
            "tenant" => "PG",
            "transactor_id" => $reversalId,
            "transactor_event" => "refund_reversed",
            "transaction_date" => 1677578532,
            "ledger_entry" => [
                [
                    "id" => "LLpAtAYRsTzogL",
                    "created_at" => 1677578580,
                    "updated_at" => 1677578580,
                    "merchant_id" => "10000000000000",
                    "journal_id" => "LLpAtAQhlnVeHg",
                    "account_id" => "JjpZUD9PmJeNPk",
                    "amount" => "50000",
                    "base_amount" => "50000",
                    "type" => "credit",
                    "currency" => "INR",
                    "balance" => "480675.000000",
                    "balance_updated" => true,
                    "account_entities" => [
                        "account_type" => [
                            "payable"
                        ],
                        "fund_account_type" => [
                            "merchant_balance"
                        ]
                    ]
                ],
                [
                    "id" => "LLpAtAYSkg5NfT",
                    "created_at" => 1677578580,
                    "updated_at" => 1677578580,
                    "merchant_id" => "10000000000000",
                    "journal_id" => "LLpAtAQhlnVeHg",
                    "account_id" => "Iu15tvUXWI1loc",
                    "amount" => "50000",
                    "base_amount" => "50000",
                    "type" => "debit",
                    "currency" => "INR",
                    "account_entities" => [
                        "account_type" => [
                            "payable"
                        ],
                        "fund_account_type" => [
                            "gateway_refund"
                        ],
                        "gateway" => [
                            "sharp"
                        ],
                        "terminal_id" => [
                            "sharp"
                        ]
                    ]
                ]
            ]
        ];
    }

    public function testKafkaAckSuccessForNormalRefundReversalEvent()
    {
        $this->app['config']->set('applications.ledger.enabled', true);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $oldMerchantBalance = $this->getDbEntityById('balance', '10000000000000');

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->merchant->addFeatures('pg_ledger_reverse_shadow');

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (&$content, $action = null) {
            if ($action === 'verify') {
                $content['result'] = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2'] = '';
                $content['udf5'] = 'TrackID';
            }

            return $content;
        });

        $mockLedger->shouldReceive('createJournal')
            ->times(1)
            ->andReturn([
                    "body" => $this->getJournalCreateSuccessResponse()
                ]
            );

        // create refund
        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('processed', $refund['status']);
        $this->assertNotNull($refund['transaction_id']);

        $mockLedger->shouldReceive('fetchByTransactor')
            ->times(1)
            ->withArgs(function ($journalPayload, $requestHeaders, $throwException) use ($refund) {

                $this->assertEquals($refund['id'], $journalPayload['transactor_id']);
                $this->assertEquals('refund_processed', $journalPayload['transactor_event']);

                $this->assertArrayHasKey('idempotency-key', $requestHeaders);
                $this->assertEquals('PG', $requestHeaders['ledger-tenant']);

                $this->assertTrue($throwException);

                return true;
            })
            ->andReturn([
                "body" => $this->getJournalCreateSuccessResponse()
            ]);

        // create reversal
        $this->scroogeUpdateRefundStatus($refund, 'failed_event');

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(RefundStatus::REVERSED, $refund['status']);

        $reversal = $this->getLastEntity('reversal', true);

        $reversalId = $reversal['id'];

        $this->assertEquals('rfnd_' . $reversal['entity_id'], $refund['id']);
        $this->assertNotNull(50000, $reversal['amount']);
        $this->assertNull($reversal['transaction_id'], 'reversal txn should not be created in sync');

        $ledgerOutboxEntity = $this->getDbEntity('ledger_outbox', ['payload_name' => $reversalId . '-' . 'refund_reversed']);
        $this->assertEquals($ledgerOutboxEntity['is_deleted'], 0, 'outbox entry should not be soft deleted');

        $journal = $this->getReversalJournalCreateSuccessResponse($reversalId);

        $journalId = $journal['id'];

        $kafkaEventPayload = $this->getKafkaEventPayload($journal);

        (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_PG_LEDGER_ACKNOWLEDGMENTS, $kafkaEventPayload, 'test');

        $txn = $this->getDbEntity('transaction', ["type" => "reversal"]);

        $reversal = $this->getLastEntity('reversal', true);

        $ledgerOutboxEntity = $this->getTrashedDbEntity('ledger_outbox', ['payload_name' => $reversalId . '-' . 'refund_reversed']);

        $this->assertEquals($reversalId, 'rvrsl_' . $txn['entity_id']);
        $this->assertEquals($journalId, $txn['id']);
        $this->assertEquals($journal['ledger_entry'][0]['amount'], $txn['amount']);
        $this->assertEquals($journal['ledger_entry'][1]['amount'], $txn['amount'] - $txn['fee'] - $txn['tax']);
        $this->assertEquals($ledgerOutboxEntity['is_deleted'], 1, 'outbox entry not soft deleted');
        $this->assertNotNull($ledgerOutboxEntity['deleted_at'], 'outbox entry not soft deleted');
        $this->assertNotNull($reversal['transaction_id'], 'reversal txn should not be null');

    }

    private function getKafkaEventPayload($journal, $request = null, $msg = "")
    {
        $kafkaPayload = [
            "request" => $request,
            "response" => $journal,
            "error_response" => [
                "msg" => $msg
            ]
        ];

        $serializedPayload = base64_encode(json_encode($kafkaPayload));

        return [
            "before" => null,
            "after" => [
                "id" => "LLJMDzemcsroDp",
                "payload_serialized" => $serializedPayload,
                "created_at" => 1677466532,
                "updated_at" => 1677466532
            ],
            "source" => [
                "version" => "2.1.1.Final",
                "connector" => "postgresql",
                "name" => "internal_db_stage_ledger_payments_test_outbox",
                "ts_ms" => 1677466532793,
                "snapshot" => "false",
                "db" => "stage_ledger_pg_test",
                "sequence" => "[\"60869735408\",\"60869737336\"]",
                "schema" => "public",
                "table" => "outbox_jobs_api_default",
                "txId" => 242246764,
                "lsn" => 60869737336,
                "xmin" => null
            ],
            "op" => "c",
            "ts_ms" => 1677466533213,
            "transaction" => null,
            "_record_source" => "debezium_postgres"
        ];
    }

    public function testKafkAckSuccessInstantRefundReversalWithCreditsReverseShadow()
    {
        Mail::fake();

        $this->fixtures->merchant->editRefundCredits('60000', '10000000000000');
        $this->fixtures->merchant->edit('10000000000000', ['refund_source' => 'credits']);

        $oldMerchantBalance = $this->getDbEntityById('balance', '10000000000000');
        $this->assertEquals(60000, $oldMerchantBalance['refund_credits']);

        $this->app['config']->set('applications.ledger.enabled', true);
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $mockLedger->shouldReceive('createJournal')
            ->times(1)
            ->andReturn([
                    "body" => $this->getJournalCreateSuccessResponse()
                ]
            );

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->merchant->addFeatures('pg_ledger_reverse_shadow');

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (&$content, $action = null) {
            if ($action === 'verify') {
                $content['result'] = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2'] = '';
                $content['udf5'] = 'TrackID';
            }

            return $content;
        });

        $this->fixtures->pricing->createInstantRefundsDefaultPricingplan();

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        $this->fixtures->pricing->createInstantRefundsModeLevelPricingPlan();

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $response = $this->refundPayment(
            $payment['id'],
            3471,
            [
                'speed' => 'optimum',
                'is_fta' => true,
                'fta_data' => [
                    'card_transfer' => [
                        'card_id' => $payment['card_id']
                    ]
                ]
            ]
        );

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(RefundStatus::PROCESSED, $refund['status']);
        $this->assertNotNull($refund['transaction_id']);
        $this->assertEquals(RefundSpeed::INSTANT, $refund['speed_processed']);
        $this->assertEquals(RefundSpeed::OPTIMUM, $refund['speed_decisioned']);

        $mockLedger->shouldReceive('fetchByTransactor')
            ->times(1)
            ->withArgs(function ($journalPayload, $requestHeaders, $throwException) use ($refund) {

                $this->assertEquals($refund['id'], $journalPayload['transactor_id']);
                $this->assertEquals('refund_processed', $journalPayload['transactor_event']);

                $this->assertArrayHasKey('idempotency-key', $requestHeaders);
                $this->assertEquals('PG', $requestHeaders['ledger-tenant']);

                $this->assertTrue($throwException);

                return true;
            })
            ->andReturn([
                'code' => 200,
                "body" => [
                    "id" => "LP6jffEeZejP3v",
                    "created_at" => "1678295443",
                    "updated_at" => "1678295443",
                    "amount" => "3471",
                    "base_amount" => "3471",
                    "currency" => "INR",
                    "tenant" => "PG",
                    "transactor_id" => $refund['id'],
                    "transactor_event" => "refund_processed",
                    "transaction_date" => "0",
                    "ledger_entry" => [
                        [
                            "id" => "LP6jffKNJZ7A4O",
                            "created_at" => "1678295443",
                            "updated_at" => "1678295443",
                            "merchant_id" => "10000000000000",
                            "journal_id" => "LP6jffEeZejP3v",
                            "account_id" => "JjpZUD9PmJeNPk",
                            "amount" => "4179",
                            "base_amount" => "4179",
                            "type" => "debit",
                            "currency" => "INR",
                            "balance" => "10000",
                            "balance_updated" => true,
                            "account_entities" =>
                                [
                                    "account_type" => ["payable"],
                                    "fund_account_type" => ["merchant_refund_credits"]
                                ]
                        ],
                        [
                            "id" => "LP6jffKNJZ7A41",
                            "created_at" => "1678295443",
                            "updated_at" => "1678295443",
                            "merchant_id" => "10000000000000",
                            "journal_id" => "LP6jffEeZejP3v",
                            "account_id" => "Iu15tvUXWI1loc",
                            "amount" => "3471",
                            "base_amount" => "3471",
                            "type" => "credit",
                            "currency" => "INR",
                            "balance" => "0",
                            "balance_updated" => false,
                            "account_entities" =>
                                [
                                    "account_type" => ["payable"],
                                    "fund_account_type" => ["customer_refund"],
                                ]
                        ],
                        [
                            "id" => "LP6jffKNJZ7A42",
                            "created_at" => "1678295443",
                            "updated_at" => "1678295443",
                            "merchant_id" => "10000000000000",
                            "journal_id" => "LP6jffEeZejP3v",
                            "account_id" => "Iu15tvUXWI1loc",
                            "amount" => "708",
                            "base_amount" => "708",
                            "type" => "credit",
                            "currency" => "INR",
                            "balance" => "0",
                            "balance_updated" => false,
                            "account_entities" =>
                                [
                                    "account_type" => ["receivable"],
                                    "fund_account_type" => ["rzp_commission"],
                                ]
                        ],
                        [
                            "id" => "LP6jffKNJZ7A43",
                            "created_at" => "1678295443",
                            "updated_at" => "1678295443",
                            "merchant_id" => "10000000000000",
                            "journal_id" => "LP6jffEeZejP3v",
                            "account_id" => "Iu15tvUXWI1loc",
                            "amount" => "108",
                            "base_amount" => "108",
                            "type" => "credit",
                            "currency" => "INR",
                            "balance" => "0",
                            "balance_updated" => false,
                            "account_entities" =>
                                [
                                    "account_type" => ["payable"],
                                    "fund_account_type" => ["rzp_gst"],
                                ]
                        ]
                    ]
                ]
            ]);

        // create reversal
        $this->scroogeUpdateRefundStatus($refund, 'failed_event');

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(RefundStatus::REVERSED, $refund['status']);

        $reversal = $this->getLastEntity('reversal', true);
        $reversalId = $reversal['id'];

        $this->assertEquals('rfnd_' . $reversal['entity_id'], $refund['id']);
        $this->assertNull($reversal['transaction_id'], 'reversal txn should not be created in sync');

        $ledgerOutboxEntity = $this->getDbEntity('ledger_outbox', ['payload_name' => $reversalId . '-' . 'refund_reversed']);

        $this->assertEquals($ledgerOutboxEntity['is_deleted'], 0, 'outbox entry should not be soft deleted');

        $journal = $this->getInstantReversalJournalCreateSuccessResponse($reversalId);

        $journalId = $journal['id'];

        $kafkaEventPayload = $this->getKafkaEventPayload($journal);

        (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_PG_LEDGER_ACKNOWLEDGMENTS, $kafkaEventPayload, 'test');

        $txn = $this->getDbEntity('transaction', ["type" => "reversal"]);
        $val= json_encode($txn);
        echo "\nnew txn = $val \n";

        $ledgerOutboxEntity = $this->getTrashedDbEntity('ledger_outbox', ['payload_name' => $reversalId . '-' . 'refund_reversed']);

        $this->assertEquals($reversalId, 'rvrsl_' . $txn['entity_id']);
        $this->assertEquals($journalId, $txn['id']);
        $this->assertEquals($journal['ledger_entry'][0]['amount'], $txn['amount']);
        $this->assertEquals(-$journal['ledger_entry'][1]['amount'], $txn['fee'] - $txn['tax']);
        $this->assertEquals(-$journal['ledger_entry'][2]['amount'], $txn['tax']);
        $this->assertEquals($journal['ledger_entry'][3]['amount'], $txn['amount'] + ((-1)*$txn['fee'])); //  fee = commission+tax
        $this->assertEquals($ledgerOutboxEntity['is_deleted'], 1, 'outbox entry not soft deleted');
        $this->assertNotNull($ledgerOutboxEntity['deleted_at'], 'outbox entry not soft deleted');

        $newMerchantBalance = $this->getDbEntityById('balance', '10000000000000');
        $val= json_encode($newMerchantBalance);
        echo "\nnew bal = $val \n";
        $val= json_encode($newMerchantBalance);
        echo "\nnew bal = $val\n";
    }

    public function getInstantReversalJournalCreateSuccessResponse($reversalId): array
    {
        return [
            "id" => "LLpAtAQhlnVeHg",
            "created_at" => 1677578580,
            "updated_at" => 1677578580,
            "amount" => "100",
            "base_amount" => "100",
            "currency" => "INR",
            "tenant" => "PG",
            "transactor_id" => $reversalId,
            "transactor_event" => "refund_reversed",
            "transaction_date" => 1677578532,
            "ledger_entry" => [
                [
                    "id" => "LLpAtAYSkg1NfT",
                    "created_at" => 1677578580,
                    "updated_at" => 1677578580,
                    "merchant_id" => "10000000000000",
                    "journal_id" => "LLpAtAQhlnVeHg",
                    "account_id" => "Iu15tvUXWI1loc",
                    "amount" => "3471",
                    "base_amount" => "3471",
                    "type" => "debit",
                    "currency" => "INR",
                    "account_entities" => [
                        "account_type" => [
                            "payable"
                        ],
                        "fund_account_type" => [
                            "customer_refund"
                        ],
                    ]
                ],
                [
                    "id" => "LLpAtAYSkg2NfT",
                    "created_at" => 1677578580,
                    "updated_at" => 1677578580,
                    "merchant_id" => "10000000000000",
                    "journal_id" => "LLpAtAQhlnVeHg",
                    "account_id" => "Iu15tvUXWI1loc",
                    "amount" => "600",
                    "base_amount" => "600",
                    "type" => "debit",
                    "currency" => "INR",
                    "account_entities" => [
                        "account_type" => [
                            "receivable"
                        ],
                        "fund_account_type" => [
                            "rzp_commission"
                        ],
                    ]
                ],
                [
                    "id" => "LLpAtAYSkg3NfT",
                    "created_at" => 1677578580,
                    "updated_at" => 1677578580,
                    "merchant_id" => "10000000000000",
                    "journal_id" => "LLpAtAQhlnVeHg",
                    "account_id" => "Iu15tvUXWI1loc",
                    "amount" => "108",
                    "base_amount" => "108",
                    "type" => "debit",
                    "currency" => "INR",
                    "account_entities" => [
                        "account_type" => [
                            "payable"
                        ],
                        "fund_account_type" => [
                            "rzp_gst"
                        ],
                    ]
                ],
                [
                    "id" => "LLpAtAYSkg5NfT",
                    "created_at" => 1677578580,
                    "updated_at" => 1677578580,
                    "merchant_id" => "10000000000000",
                    "journal_id" => "LLpAtAQhlnVeHg",
                    "account_id" => "Iu15tvUXWI1loc",
                    "amount" => "4179",
                    "base_amount" => "4179",
                    "type" => "credit",
                    "currency" => "INR",
                    "account_entities" => [
                        "account_type" => [
                            "payable"
                        ],
                        "fund_account_type" => [
                            "merchant_refund_credits"
                        ],
                    ]
                ]
            ]
        ];
    }

    public function getNormalReversalWithCreditsJournalSuccessResponse($reversalId): array
    {
        return [
            "id" => "LLpAtAQhlnVeHg",
            "created_at" => 1677578580,
            "updated_at" => 1677578580,
            "amount" => "100",
            "base_amount" => "100",
            "currency" => "INR",
            "tenant" => "PG",
            "transactor_id" => $reversalId,
            "transactor_event" => "refund_reversed",
            "transaction_date" => 1677578532,
            "ledger_entry" => [
                [
                    "id" => "LLpAtAYRsTzogL",
                    "created_at" => 1677578580,
                    "updated_at" => 1677578580,
                    "merchant_id" => "10000000000000",
                    "journal_id" => "LLpAtAQhlnVeHg",
                    "account_id" => "JjpZUD9PmJeNPk",
                    "amount" => "50000",
                    "base_amount" => "50000",
                    "type" => "credit",
                    "currency" => "INR",
                    "balance" => "480675.000000",
                    "balance_updated" => true,
                    "account_entities" => [
                        "account_type" => [
                            "payable"
                        ],
                        "fund_account_type" => [
                            "merchant_refund_credits"
                        ]
                    ]
                ],
                [
                    "id" => "LLpAtAYSkg5NfT",
                    "created_at" => 1677578580,
                    "updated_at" => 1677578580,
                    "merchant_id" => "10000000000000",
                    "journal_id" => "LLpAtAQhlnVeHg",
                    "account_id" => "Iu15tvUXWI1loc",
                    "amount" => "50000",
                    "base_amount" => "50000",
                    "type" => "debit",
                    "currency" => "INR",
                    "account_entities" => [
                        "account_type" => [
                            "payable"
                        ],
                        "fund_account_type" => [
                            "gateway_refund"
                        ],
                        "gateway" => [
                            "sharp"
                        ],
                        "terminal_id" => [
                            "sharp"
                        ]
                    ]
                ]
            ]
        ];
    }

    public function testCronRetrySuccessForNormalRefundReversalWithCredits()
    {
        Mail::fake();

        $this->fixtures->merchant->editRefundCredits('60000', '10000000000000');
        $this->fixtures->merchant->edit('10000000000000', ['refund_source' => 'credits']);

        $oldMerchantBalance = $this->getDbEntityById('balance', '10000000000000');

        $this->app['config']->set('applications.ledger.enabled', true);
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $this->createRefundForReversalRefundShadow($mockLedger);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('processed', $refund['status']);
        $this->assertNotNull($refund['transaction_id']);

        $mockLedger->shouldReceive('fetchByTransactor')
            ->times(1)
            ->andReturn([
                'code' => 200,
                "body" => [
                    "id" => "LP6jffEeZejP3v",
                    "created_at" => "1678295443",
                    "updated_at" => "1678295443",
                    "amount" => "50000",
                    "base_amount" => "50000",
                    "currency" => "INR",
                    "tenant" => "PG",
                    "transactor_id" => $refund['id'],
                    "transactor_event" => "refund_processed",
                    "transaction_date" => "0",
                    "ledger_entry" => [
                        [
                            "id" => "LP6jffKNJZ7A4O",
                            "created_at" => "1678295443",
                            "updated_at" => "1678295443",
                            "merchant_id" => "10000000000000",
                            "journal_id" => "LP6jffEeZejP3v",
                            "account_id" => "JjpZUD9PmJeNPk",
                            "amount" => "50000",
                            "base_amount" => "50000",
                            "type" => "debit",
                            "currency" => "INR",
                            "balance" => "10000",
                            "balance_updated" => true,
                            "account_entities" =>
                                [
                                    "account_type" => ["payable"],
                                    "fund_account_type" => ["merchant_refund_credits"]
                                ]
                        ],
                        [
                            "id" => "LP6jffKOUMWtSE",
                            "created_at" => "1678295443",
                            "updated_at" => "1678295443",
                            "merchant_id" => "10000000000000",
                            "journal_id" => "LP6jffEeZejP3v",
                            "account_id" => "Iu15tvUXWI1loc",
                            "amount" => "50000",
                            "base_amount" => "50000",
                            "type" => "credit",
                            "currency" => "INR",
                            "balance" => "0",
                            "balance_updated" => false,
                            "account_entities" =>
                                [
                                    "account_type" => ["payable"],
                                    "fund_account_type" => ["gateway_refund"],
                                    "gateway" => ["sharp"],
                                    "terminal_id" => ["sharp"]
                                ]
                        ]
                    ]
                ]
            ]);

        $afterRefundMerchantBalance = $this->getDbEntityById('balance', '10000000000000');
        $this->assertEquals($oldMerchantBalance['refund_credits'] - $refund['amount'], $afterRefundMerchantBalance['refund_credits']);

        $this->scroogeUpdateRefundStatus($refund, 'failed_event');

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(RefundStatus::REVERSED, $refund['status']);

        $reversal = $this->getLastEntity('reversal', true);

        $this->assertNotNull(50000, $reversal['amount']);
        $this->assertNull($reversal['transaction_id'], 'reversal txn should not be created in sync');

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        // setting outbox entry's created_at to an earlier timestamp so that cron fetches it
        $createdAtTimestamp = (int)((millitime()-3600000)/1000);
        $this->fixtures->edit('ledger_outbox', $ledgerOutboxEntity['id'], ['created_at' => $createdAtTimestamp]);

        $this->assertNotNull($ledgerOutboxEntity);

        $journalResponse = $this->getNormalReversalWithCreditsJournalSuccessResponse($reversal['id']);

        $mockLedger->shouldReceive('createJournal')
            ->times(1)
            ->andReturn(
                [
                    'code' => 200,
                    'body' => $journalResponse,
                ],
            );

        $this->ba->cronAuth();

        $this->startTest();

        $txn = $this->getDbEntity('transaction', ["type" => "reversal"]);

        $newMerchantBalance = $this->getDbEntityById('balance', '10000000000000');
        $this->assertEquals($afterRefundMerchantBalance['refund_credits'] + $reversal['amount'], $newMerchantBalance['refund_credits']);

        $ledgerOutboxEntity = $this->getTrashedDbEntity('ledger_outbox', ['payload_name' => $reversal['id'] . '-refund_reversed']);

        $this->assertNotNull($ledgerOutboxEntity);
        $this->assertNotNull($txn);

        $this->assertEquals($reversal['id'], 'rvrsl_'.$txn['entity_id']);
        $this->assertEquals($journalResponse['id'], $txn['id']);
        $this->assertEquals($journalResponse['ledger_entry'][0]['amount'], $txn['amount']);
        $this->assertEquals($journalResponse['ledger_entry'][1]['amount'], $txn['amount']);

        $this->assertEquals(1, $ledgerOutboxEntity['is_deleted'], 'outbox entry not soft deleted');
        $this->assertEquals(1, $ledgerOutboxEntity['retry_count']);
        $this->assertNotNull($ledgerOutboxEntity['deleted_at'], 'outbox entry not soft deleted');
    }

    public function testCronRetryForNormalRefundReversalWithCreditsUpdateRetryCount()
    {
        Mail::fake();

        $this->fixtures->merchant->editRefundCredits('60000', '10000000000000');
        $this->fixtures->merchant->edit('10000000000000', ['refund_source' => 'credits']);

        $this->app['config']->set('applications.ledger.enabled', true);
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $this->createRefundForReversalRefundShadow($mockLedger);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('processed', $refund['status']);
        $this->assertNotNull($refund['transaction_id']);

        $mockLedger->shouldReceive('fetchByTransactor')
            ->times(1)
            ->andReturn([
                'code' => 200,
                "body" => [
                    "id" => "LP6jffEeZejP3v",
                    "created_at" => "1678295443",
                    "updated_at" => "1678295443",
                    "amount" => "50000",
                    "base_amount" => "50000",
                    "currency" => "INR",
                    "tenant" => "PG",
                    "transactor_id" => $refund['id'],
                    "transactor_event" => "refund_processed",
                    "transaction_date" => "0",
                    "ledger_entry" => [
                        [
                            "id" => "LP6jffKNJZ7A4O",
                            "created_at" => "1678295443",
                            "updated_at" => "1678295443",
                            "merchant_id" => "10000000000000",
                            "journal_id" => "LP6jffEeZejP3v",
                            "account_id" => "JjpZUD9PmJeNPk",
                            "amount" => "50000",
                            "base_amount" => "50000",
                            "type" => "debit",
                            "currency" => "INR",
                            "balance" => "10000",
                            "balance_updated" => true,
                            "account_entities" =>
                                [
                                    "account_type" => ["payable"],
                                    "fund_account_type" => ["merchant_refund_credits"]
                                ]
                        ],
                        [
                            "id" => "LP6jffKOUMWtSE",
                            "created_at" => "1678295443",
                            "updated_at" => "1678295443",
                            "merchant_id" => "10000000000000",
                            "journal_id" => "LP6jffEeZejP3v",
                            "account_id" => "Iu15tvUXWI1loc",
                            "amount" => "50000",
                            "base_amount" => "50000",
                            "type" => "credit",
                            "currency" => "INR",
                            "balance" => "0",
                            "balance_updated" => false,
                            "account_entities" =>
                                [
                                    "account_type" => ["payable"],
                                    "fund_account_type" => ["gateway_refund"],
                                    "gateway" => ["sharp"],
                                    "terminal_id" => ["sharp"]
                                ]
                        ]
                    ]
                ]
            ]);

        $this->scroogeUpdateRefundStatus($refund, 'failed_event');

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(RefundStatus::REVERSED, $refund['status']);

        $reversal = $this->getLastEntity('reversal', true);

        $this->assertNotNull(50000, $reversal['amount']);
        $this->assertNull($reversal['transaction_id'], 'reversal txn should not be created in sync');

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        // setting outbox entry's created_at to an earlier timestamp so that cron fetches it
        $createdAtTimestamp = (int)((millitime()-3600000)/1000);
        $this->fixtures->edit('ledger_outbox', $ledgerOutboxEntity['id'], ['created_at' => $createdAtTimestamp]);

        $this->assertNotNull($ledgerOutboxEntity);

        $journalResponse = $this->getNormalReversalWithCreditsJournalSuccessResponse($reversal['id']);

        $mockLedger->shouldReceive('createJournal')
            ->once()
            ->andThrow(new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                [],
                "insufficient_balance_failure: BAD_REQUEST_INSUFFICIENT_BALANCE"
            ));

        $this->ba->cronAuth();

        $this->startTest();

        $txn = $this->getDbEntity('transaction', ["type" => "reversal"]);

        $ledgerOutboxEntity = $this->getDbEntity('ledger_outbox', ['payload_name' => $ledgerOutboxEntity['payload_name']]);

        $this->assertNotNull($ledgerOutboxEntity);
        $this->assertNull($txn);

        $this->assertEquals(0, $ledgerOutboxEntity['is_deleted'], 'outbox entry should not be soft deleted');
        $this->assertEquals(1, $ledgerOutboxEntity['retry_count']);
        $this->assertNull($ledgerOutboxEntity['deleted_at'], 'outbox entry should not be soft deleted');
    }

    public function testCronRetryFailureForNormalRefundReversalWithCreditsWithDuplicateError()
    {
        Mail::fake();

        $this->fixtures->merchant->editRefundCredits('60000', '10000000000000');
        $this->fixtures->merchant->edit('10000000000000', ['refund_source' => 'credits']);

        $this->app['config']->set('applications.ledger.enabled', true);
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $this->createRefundForReversalRefundShadow($mockLedger);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('processed', $refund['status']);
        $this->assertNotNull($refund['transaction_id']);

        $mockLedger->shouldReceive('fetchByTransactor')
            ->times(1)
            ->andReturn([
                'code' => 200,
                "body" => [
                    "id" => "LP6jffEeZejP3v",
                    "created_at" => "1678295443",
                    "updated_at" => "1678295443",
                    "amount" => "50000",
                    "base_amount" => "50000",
                    "currency" => "INR",
                    "tenant" => "PG",
                    "transactor_id" => $refund['id'],
                    "transactor_event" => "refund_processed",
                    "transaction_date" => "0",
                    "ledger_entry" => [
                        [
                            "id" => "LP6jffKNJZ7A4O",
                            "created_at" => "1678295443",
                            "updated_at" => "1678295443",
                            "merchant_id" => "10000000000000",
                            "journal_id" => "LP6jffEeZejP3v",
                            "account_id" => "JjpZUD9PmJeNPk",
                            "amount" => "50000",
                            "base_amount" => "50000",
                            "type" => "debit",
                            "currency" => "INR",
                            "balance" => "10000",
                            "balance_updated" => true,
                            "account_entities" =>
                                [
                                    "account_type" => ["payable"],
                                    "fund_account_type" => ["merchant_refund_credits"]
                                ]
                        ],
                        [
                            "id" => "LP6jffKOUMWtSE",
                            "created_at" => "1678295443",
                            "updated_at" => "1678295443",
                            "merchant_id" => "10000000000000",
                            "journal_id" => "LP6jffEeZejP3v",
                            "account_id" => "Iu15tvUXWI1loc",
                            "amount" => "50000",
                            "base_amount" => "50000",
                            "type" => "credit",
                            "currency" => "INR",
                            "balance" => "0",
                            "balance_updated" => false,
                            "account_entities" =>
                                [
                                    "account_type" => ["payable"],
                                    "fund_account_type" => ["gateway_refund"],
                                    "gateway" => ["sharp"],
                                    "terminal_id" => ["sharp"]
                                ]
                        ]
                    ]
                ]
            ]);

        $this->scroogeUpdateRefundStatus($refund, 'failed_event');

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(RefundStatus::REVERSED, $refund['status']);

        $reversal = $this->getLastEntity('reversal', true);

        $this->assertNotNull(50000, $reversal['amount']);
        $this->assertNull($reversal['transaction_id'], 'reversal txn should not be created in sync');

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        // setting outbox entry's created_at to an earlier timestamp so that cron fetches it
        $createdAtTimestamp = (int)((millitime()-3600000)/1000);
        $this->fixtures->edit('ledger_outbox', $ledgerOutboxEntity['id'], ['created_at' => $createdAtTimestamp]);

        $this->assertNotNull($ledgerOutboxEntity);

        $journalResponse = $this->getNormalReversalWithCreditsJournalSuccessResponse($reversal['id']);

        $mockLedger->shouldReceive('createJournal')
            ->once()
            ->andThrow(new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                [],
                "record_already_exist: BAD_REQUEST_RECORD_ALREADY_EXISTS"
            ));

        $this->ba->cronAuth();

        $this->startTest();

        $txn = $this->getDbEntity('transaction', ["type" => "reversal"]);

        $ledgerOutboxEntity = $this->getTrashedDbEntity('ledger_outbox', ['payload_name' => $reversal['id'] . '-refund_reversed']);

        $this->assertNotNull($ledgerOutboxEntity);
        $this->assertNull($txn);

        $this->assertEquals(1, $ledgerOutboxEntity['is_deleted'], 'outbox entry should be soft deleted');
        $this->assertEquals(1, $ledgerOutboxEntity['retry_count']);
        $this->assertNotNull($ledgerOutboxEntity['deleted_at'], 'outbox entry should be soft deleted');

    }

    public function testCronRetryFailureForNormalRefundReversalWithCreditsWithExhaustedRetries()
    {
        Mail::fake();

        $this->fixtures->merchant->editRefundCredits('60000', '10000000000000');
        $this->fixtures->merchant->edit('10000000000000', ['refund_source' => 'credits']);

        $this->app['config']->set('applications.ledger.enabled', true);
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $this->createRefundForReversalRefundShadow($mockLedger);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('processed', $refund['status']);
        $this->assertNotNull($refund['transaction_id']);

        $mockLedger->shouldReceive('fetchByTransactor')
            ->times(1)
            ->andReturn([
                'code' => 200,
                "body" => [
                    "id" => "LP6jffEeZejP3v",
                    "created_at" => "1678295443",
                    "updated_at" => "1678295443",
                    "amount" => "50000",
                    "base_amount" => "50000",
                    "currency" => "INR",
                    "tenant" => "PG",
                    "transactor_id" => $refund['id'],
                    "transactor_event" => "refund_processed",
                    "transaction_date" => "0",
                    "ledger_entry" => [
                        [
                            "id" => "LP6jffKNJZ7A4O",
                            "created_at" => "1678295443",
                            "updated_at" => "1678295443",
                            "merchant_id" => "10000000000000",
                            "journal_id" => "LP6jffEeZejP3v",
                            "account_id" => "JjpZUD9PmJeNPk",
                            "amount" => "50000",
                            "base_amount" => "50000",
                            "type" => "debit",
                            "currency" => "INR",
                            "balance" => "10000",
                            "balance_updated" => true,
                            "account_entities" =>
                                [
                                    "account_type" => ["payable"],
                                    "fund_account_type" => ["merchant_refund_credits"]
                                ]
                        ],
                        [
                            "id" => "LP6jffKOUMWtSE",
                            "created_at" => "1678295443",
                            "updated_at" => "1678295443",
                            "merchant_id" => "10000000000000",
                            "journal_id" => "LP6jffEeZejP3v",
                            "account_id" => "Iu15tvUXWI1loc",
                            "amount" => "50000",
                            "base_amount" => "50000",
                            "type" => "credit",
                            "currency" => "INR",
                            "balance" => "0",
                            "balance_updated" => false,
                            "account_entities" =>
                                [
                                    "account_type" => ["payable"],
                                    "fund_account_type" => ["gateway_refund"],
                                    "gateway" => ["sharp"],
                                    "terminal_id" => ["sharp"]
                                ]
                        ]
                    ]
                ]
            ]);

        $this->scroogeUpdateRefundStatus($refund, 'failed_event');

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(RefundStatus::REVERSED, $refund['status']);

        $reversal = $this->getLastEntity('reversal', true);

        $this->assertNotNull(50000, $reversal['amount']);
        $this->assertNull($reversal['transaction_id'], 'reversal txn should not be created in sync');

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        // setting outbox entry's created_at to an earlier timestamp so that cron fetches it
        $createdAtTimestamp = (int)((millitime()-3600000)/1000);
        $this->fixtures->edit('ledger_outbox', $ledgerOutboxEntity['id'], ['created_at' => $createdAtTimestamp, "retry_count" => 9]);

        $this->assertNotNull($ledgerOutboxEntity);

        $journalResponse = $this->getNormalReversalWithCreditsJournalSuccessResponse($reversal['id']);

        $mockLedger->shouldReceive('createJournal')
            ->times(1)
            ->andThrow(new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                [],
                "insufficient_balance_failure: BAD_REQUEST_INSUFFICIENT_BALANCE"
            ));

        $this->ba->cronAuth();

        $this->startTest();

        $txn = $this->getDbEntity('transaction', ["type" => "reversal"]);

        $ledgerOutboxEntity = $this->getTrashedDbEntity('ledger_outbox', ['payload_name' => $reversal['id'] . '-refund_reversed']);

        $this->assertNotNull($ledgerOutboxEntity);
        $this->assertNull($txn);

        $this->assertEquals(1, $ledgerOutboxEntity['is_deleted'], 'outbox entry should be soft deleted');
        $this->assertEquals(10, $ledgerOutboxEntity['retry_count']);
        $this->assertNotNull($ledgerOutboxEntity['deleted_at'], 'outbox entry should be soft deleted');
    }

    // Virtual Refund Reversal test cases

    public function testVirtualRefundReversalFaliureWithInvalidInput()
    {
        $this->ba->scroogeAuth();

        $response = $this->startTest();

        $this->assertNotNull($response['error']);
        $this->assertEquals('The gateway field is required.', $response['error']['description']);
    }

    public function testVirtualRefundReversalFaliureWithReverseShadowNotEnabled()
    {
        $this->ba->scroogeAuth();

        $response = $this->startTest();

        $reversal = $this->getLastEntity('reversal', true);

        $this->assertNotNull($response['error']);
        $this->assertNull($reversal);
    }

    protected function updateTestDataForVirtualRefundReversal($paymentId, $refundId): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $name = $trace[1]['function'];

        $testData = &$this->testData[$name];

        $testData['request']['content'] = [
            'journal_id'               => 'LReSY2s4HTYIOx',
            'payment_id'               => $paymentId,
            'refund_id'                => $refundId,
            'merchant_id'              => '10000000000000',
            'speed_decisioned'         => 'normal',
            'base_amount'              => '10000',
            'fee'                      => '0',
            'tax'                      => '0',
            'fee_only_reversal'        =>  0,
            'currency'                 => 'INR',
            'created_at'               => '1678851765',
            'gateway'                  => 'hdfc',
        ];

        return $testData;
    }

    public function testVirtualRefundReversalFaliureWithDuplicateReversal()
    {
        $this->app['config']->set('applications.ledger.enabled', true);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $this->createRefundForReversalRefundShadow($mockLedger);

        $refund = $this->getLastEntity('refund', true);

        $mockLedger->shouldReceive('fetchByTransactor')
            ->times(1)
            ->andReturn([
                "body" => $this->getJournalCreateSuccessResponse()
            ]);

        // create reversal
        $this->scroogeUpdateRefundStatus($refund, 'failed_event');

        $reversal = $this->getLastEntity('reversal', true);

        $paymentId = str_replace('pay_', '',$refund['payment_id']);
        $refundId = str_replace('rfnd_', '',$refund['id']);
        $reversalId = str_replace('rvrsl_', '',$reversal['id']);

        $testData = $this->updateTestDataForVirtualRefundReversal($paymentId, $refundId);

        $testData['response']['content'] = [
            "reversal_id"               => $reversalId,
            "refund_id"                 => $refundId,
            "is_duplicate"              => true
        ];

        $this->ba->scroogeAuth();

        $response = $this->startTest($testData);

        $this->assertEquals($refundId, $response['refund_id']);
        $this->assertEquals($reversalId, $response['reversal_id']);
        $this->assertTrue($response['is_duplicate']);
    }

    public function testVirtualRefundReversalSuccess()
    {
        $this->app['config']->set('applications.ledger.enabled', true);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $paymentId = str_replace('pay_', '',$payment['id']);
        $refundId = 'LRei1RkDqc1WBd';

        $testData = $this->updateTestDataForVirtualRefundReversal($paymentId, $refundId);

        $this->fixtures->merchant->addFeatures('pg_ledger_reverse_shadow');

        $mockLedger->shouldReceive('fetchByTransactor')
            ->times(1)
            ->withArgs(function ($journalPayload, $requestHeaders, $throwException) use ($refundId) {

                $this->assertEquals('rfnd_'.$refundId, $journalPayload['transactor_id']);
                $this->assertEquals('refund_processed', $journalPayload['transactor_event']);

                $this->assertArrayHasKey('idempotency-key', $requestHeaders);
                $this->assertEquals('PG', $requestHeaders['ledger-tenant']);

                $this->assertTrue($throwException);

                return true;
            })
            ->andReturn([
                'code' => 200,
                "body" => $this->getJournalCreateSuccessResponse()
            ]);

        $this->ba->scroogeAuth();

        $response = $this->startTest($testData);

        $refund = $this->getLastEntity('refund', true);

        $reversal = $this->getLastEntity('reversal', true);

        $this->assertEquals($refundId, $response['refund_id']);
        $this->assertEquals($reversal['id'], 'rvrsl_' . $response['reversal_id']);
        $this->assertFalse($response['is_duplicate']);

        $this->assertEquals($reversal['entity_type'], 'refund');
        $this->assertEquals($reversal['entity_id'], $refundId);
        $this->assertEquals(10000, $reversal['amount']);
        $this->assertNull($reversal['transaction_id'], 'reversal txn should not be created in sync');
        $this->assertNull($refund, 'refund should not be created');

        $ledgerOutboxEntity = $this->getLastEntity('ledger_outbox', true);

        $payload = base64_decode($ledgerOutboxEntity['payload_serialized']);

        $actualLedgerOutboxEntry = json_decode($payload, true);

        $expectedLedgerOutboxEntry = [
            "merchant_id" => "10000000000000",
            "currency" => "INR",
            "transactor_event" => "refund_reversed",
            "money_params" => [
                "reversed_amount" => "10000",
                "merchant_balance_amount" => "10000",
            ],
            "ledger_integration_mode" => "reverse-shadow",
            "identifiers" => [
                "gateway" => "hdfc"
            ],
            "tenant" => "PG"
        ];

        $this->assertArraySubset($expectedLedgerOutboxEntry, $actualLedgerOutboxEntry);
        $this->assertEquals($reversal['id'], $actualLedgerOutboxEntry['transactor_id']);
        $this->assertNotNull($actualLedgerOutboxEntry['idempotency_key']);
        $this->assertEquals(0,$ledgerOutboxEntity['is_deleted']);
    }

    public function testKafkaAckSuccessForVirtualRefundReversalEvent()
    {
        $this->app['config']->set('applications.ledger.enabled', true);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $paymentId = str_replace('pay_', '',$payment['id']);
        $refundId = 'LRei1RkDqc1WBd';

        $testData = $this->updateTestDataForVirtualRefundReversal($paymentId, $refundId);

        $this->fixtures->merchant->addFeatures('pg_ledger_reverse_shadow');

        $mockLedger->shouldReceive('fetchByTransactor')
            ->times(1)
            ->withArgs(function ($journalPayload, $requestHeaders, $throwException) use ($refundId) {

                $this->assertEquals('rfnd_'.$refundId, $journalPayload['transactor_id']);
                $this->assertEquals('refund_processed', $journalPayload['transactor_event']);

                $this->assertArrayHasKey('idempotency-key', $requestHeaders);
                $this->assertEquals('PG', $requestHeaders['ledger-tenant']);

                $this->assertTrue($throwException);

                return true;
            })
            ->andReturn([
                'code' => 200,
                "body" => [
                    "id" => "LP6jffEeZejP3v",
                    "created_at" => "1678295443",
                    "updated_at" => "1678295443",
                    "amount" => "3471",
                    "base_amount" => "3471",
                    "currency" => "INR",
                    "tenant" => "PG",
                    "transactor_id" => 'rfnd_'.$refundId,
                    "transactor_event" => "refund_processed",
                    "transaction_date" => "0",
                    "ledger_entry" => [
                        [
                            "id" => "LP6jffKNJZ7A4O",
                            "created_at" => "1678295443",
                            "updated_at" => "1678295443",
                            "merchant_id" => "10000000000000",
                            "journal_id" => "LP6jffEeZejP3v",
                            "account_id" => "JjpZUD9PmJeNPk",
                            "amount" => "4179",
                            "base_amount" => "4179",
                            "type" => "debit",
                            "currency" => "INR",
                            "balance" => "10000",
                            "balance_updated" => true,
                            "account_entities" =>
                                [
                                    "account_type" => ["payable"],
                                    "fund_account_type" => ["merchant_balance"]
                                ]
                        ],
                        [
                            "id" => "LP6jffKNJZ7A41",
                            "created_at" => "1678295443",
                            "updated_at" => "1678295443",
                            "merchant_id" => "10000000000000",
                            "journal_id" => "LP6jffEeZejP3v",
                            "account_id" => "Iu15tvUXWI1loc",
                            "amount" => "3471",
                            "base_amount" => "3471",
                            "type" => "credit",
                            "currency" => "INR",
                            "balance" => "0",
                            "balance_updated" => false,
                            "account_entities" =>
                                [
                                    "account_type" => ["payable"],
                                    "fund_account_type" => ["customer_refund"],
                                ]
                        ],
                        [
                            "id" => "LP6jffKNJZ7A42",
                            "created_at" => "1678295443",
                            "updated_at" => "1678295443",
                            "merchant_id" => "10000000000000",
                            "journal_id" => "LP6jffEeZejP3v",
                            "account_id" => "Iu15tvUXWI1loc",
                            "amount" => "708",
                            "base_amount" => "708",
                            "type" => "credit",
                            "currency" => "INR",
                            "balance" => "0",
                            "balance_updated" => false,
                            "account_entities" =>
                                [
                                    "account_type" => ["receivable"],
                                    "fund_account_type" => ["rzp_commission"],
                                ]
                        ],
                        [
                            "id" => "LP6jffKNJZ7A43",
                            "created_at" => "1678295443",
                            "updated_at" => "1678295443",
                            "merchant_id" => "10000000000000",
                            "journal_id" => "LP6jffEeZejP3v",
                            "account_id" => "Iu15tvUXWI1loc",
                            "amount" => "108",
                            "base_amount" => "108",
                            "type" => "credit",
                            "currency" => "INR",
                            "balance" => "0",
                            "balance_updated" => false,
                            "account_entities" =>
                                [
                                    "account_type" => ["payable"],
                                    "fund_account_type" => ["rzp_gst"],
                                ]
                        ]
                    ]
                ]
            ]);

        $this->ba->scroogeAuth();

        //creates virtual refund reversal
        $response = $this->startTest($testData);

        $refund = $this->getLastEntity('refund', true);

        $reversal = $this->getLastEntity('reversal', true);

        $this->assertEquals($refundId, $response['refund_id']);
        $this->assertEquals($reversal['id'], 'rvrsl_' . $response['reversal_id']);
        $this->assertFalse($response['is_duplicate']);

        $this->assertNull($refund);
        $this->assertNull($reversal['transaction_id'], 'reversal txn should not be created in sync');

        $ledgerOutboxEntity = $this->getDbEntity('ledger_outbox', ['payload_name' => $reversal['id'] . '-' . 'refund_reversed']);
        $this->assertEquals($ledgerOutboxEntity['is_deleted'], 0, 'outbox entry should not be soft deleted');

        $journal = $this->getReversalJournalCreateSuccessResponse($reversal['id']);

        $kafkaEventPayload = $this->getKafkaEventPayload($journal);

        (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_PG_LEDGER_ACKNOWLEDGMENTS, $kafkaEventPayload, 'test');

        //Note: txn is not created for virtual refund entities. As this reversal is created for a virtual refund,
        // txn should not be created for such rveersals as well.
        $txn = $this->getDbEntity('transaction', ["type" => "reversal"]);

        $ledgerOutboxEntity = $this->getTrashedDbEntity('ledger_outbox', ['payload_name' => $reversal['id'] . '-' . 'refund_reversed']);

        // Note: As request is via worker and refund transaction is null, an exception is throw and we do not soft-delete the outbox entry
        // so that Reversal txn creation in this case can be retried from cron.
        $this->assertNull($txn);
        $this->assertEquals($ledgerOutboxEntity['is_deleted'], 0, 'outbox entry should be soft deleted');
        $this->assertNull($ledgerOutboxEntity['deleted_at'], 'outbox entry should not be soft deleted');
    }

}
