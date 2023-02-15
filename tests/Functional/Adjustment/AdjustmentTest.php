<?php

namespace RZP\Tests\Functional\Adjustment;

use Mail;

use Queue;
use RZP\Jobs\Transactions;
use RZP\Models\Feature;
use RZP\Services\RazorXClient;
use RZP\Models\Adjustment\Status;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Transaction\Adjustment;
use RZP\Models\Merchant\Balance\Type;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Mail\Banking\YesbankLoadViaAdjustment;
use RZP\Tests\Functional\Fixtures\Entity\User;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Mail\Merchant\NegativeBalanceThresholdAlert;
use RZP\Models\Adjustment\Entity as AdjustmentEntity;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Models\Merchant\Balance\Entity as BalanceEntity;
use RZP\Mail\Merchant\ReserveBalanceActivate as ReserveBalanceActivateMail;

class AdjustmentTest extends TestCase
{
    use DbEntityFetchTrait;
    use TestsWebhookEvents;
    use TestsBusinessBanking;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/AdjustmentTestData.php';

        parent::setUp();

        $this->createFixtures();
    }

    public function testAddPrimaryBalance()
    {
        Mail::fake();

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

        $response = $this->startTest();

        $adjId = $response['id'];

        $txnId = $response['transaction_id'];

        $adjustment = $this->getDbEntityById('adjustment', $adjId);

        $balanceId = $adjustment['balance_id'];

        $balance = $this->getDbEntityById('balance', $balanceId);

        $transaction = $this->getDbEntityById('transaction', $txnId);

        $this->assertNotNull($adjustment, 'adjustment should not be null');

        $this->assertNotNull($balance, 'balance should not be null');

        $this->assertNotNull($transaction, 'transaction should not be null');

        $this->assertEquals($txnId, $adjustment['transaction_id']);
        $this->assertEquals('100abc000abc00', $adjustment['merchant_id']);
        $this->assertEquals(500000, $adjustment['amount']);
        $this->assertEquals(Status::PROCESSED, $adjustment['status']);

        $this->assertEquals('primary', $balance['type']);
        $this->assertEquals('100abc000abc00', $balance['merchant_id']);
        $this->assertEquals(501000, $balance['balance']);

        $this->assertEquals('adjustment', $transaction['type']);
        $this->assertEquals('100abc000abc00', $transaction['merchant_id']);
        $this->assertEquals(500000, $transaction['amount']);
        $this->assertEquals($balanceId, $transaction['balance_id']);

        $this->assertNotNull($transaction['posted_at']);
    }

    public function testAddPrimaryBalanceForMalaysianCurrency()
    {
        Mail::fake();

        $this->fixtures->merchant->edit('100abc000abc00', ['country_code' => 'MY']);
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

        $response = $this->startTest();

        $adjId = $response['id'];

        $txnId = $response['transaction_id'];

        $adjustment = $this->getDbEntityById('adjustment', $adjId);

        $balanceId = $adjustment['balance_id'];

        $balance = $this->getDbEntityById('balance', $balanceId);

        $transaction = $this->getDbEntityById('transaction', $txnId);

        $this->assertNotNull($adjustment, 'adjustment should not be null');

        $this->assertNotNull($balance, 'balance should not be null');

        $this->assertNotNull($transaction, 'transaction should not be null');

        $this->assertEquals($txnId, $adjustment['transaction_id']);
        $this->assertEquals('100abc000abc00', $adjustment['merchant_id']);
        $this->assertEquals(500000, $adjustment['amount']);
        $this->assertEquals(Status::PROCESSED, $adjustment['status']);

        $this->assertEquals('primary', $balance['type']);
        $this->assertEquals('100abc000abc00', $balance['merchant_id']);
        $this->assertEquals(501000, $balance['balance']);

        $this->assertEquals('adjustment', $transaction['type']);
        $this->assertEquals('100abc000abc00', $transaction['merchant_id']);
        $this->assertEquals(500000, $transaction['amount']);
        $this->assertEquals($balanceId, $transaction['balance_id']);

        $this->assertNotNull($transaction['posted_at']);
    }

    public function testAddPrimaryBalanceWhenLedgerReverseShadowEnabled()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $this->testData[__FUNCTION__] = $this->testData['testAddPrimaryBalance'];

        Mail::fake();

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

        $response = $this->startTest();

        $adjId = $response['id'];

        $txnId = $response['transaction_id'];

        $adjustment = $this->getDbEntityById('adjustment', $adjId);

        $balanceId = $adjustment['balance_id'];

        $balance = $this->getDbEntityById('balance', $balanceId);

        $transaction = $this->getDbEntityById('transaction', $txnId);

        $this->assertNotNull($adjustment, 'adjustment should not be null');

        $this->assertNotNull($balance, 'balance should not be null');

        $this->assertNotNull($transaction, 'transaction should not be null');

        $this->assertEquals($txnId, $adjustment['transaction_id']);
        $this->assertEquals('100abc000abc00', $adjustment['merchant_id']);
        $this->assertEquals(500000, $adjustment['amount']);
        $this->assertEquals(Status::PROCESSED, $adjustment['status']);

        $this->assertEquals('primary', $balance['type']);
        $this->assertEquals('100abc000abc00', $balance['merchant_id']);
        $this->assertEquals(501000, $balance['balance']);

        $this->assertEquals('adjustment', $transaction['type']);
        $this->assertEquals('100abc000abc00', $transaction['merchant_id']);
        $this->assertEquals(500000, $transaction['amount']);
        $this->assertEquals($balanceId, $transaction['balance_id']);

        $this->assertNotNull($transaction['posted_at']);

    }

    public function testCreateReservePrimaryBalance()
    {
        Mail::fake();

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn('on');

        $this->ba->adminAuth();

        $response = $this->startTest();

        $adjId = $response['id'];

        $txnId = $response['transaction_id'];

        $adjustment = $this->getDbEntityById('adjustment', $adjId);

        $balanceId = $adjustment['balance_id'];

        $balance = $this->getDbEntityById('balance', $balanceId);

        $transaction = $this->getDbEntityById('transaction', $txnId);

        $this->assertNotNull($adjustment, 'adjustment should not be null');

        $this->assertNotNull($balance, 'balance should not be null');

        $this->assertNotNull($transaction, 'transaction should not be null');

        $this->assertEquals($txnId, $adjustment['transaction_id']);
        $this->assertEquals('100abc000abc00', $adjustment['merchant_id']);
        $this->assertEquals(5000000, $adjustment['amount']);
        $this->assertEquals(Status::PROCESSED, $adjustment['status']);

        $this->assertEquals('reserve_primary', $balance['type']);
        $this->assertEquals('100abc000abc00', $balance['merchant_id']);
        $this->assertEquals(5000000, $balance['balance']);

        $this->assertEquals('adjustment', $transaction['type']);
        $this->assertEquals('100abc000abc00', $transaction['merchant_id']);
        $this->assertEquals(5000000, $transaction['amount']);
        $this->assertEquals($balanceId, $transaction['balance_id']);

        Mail::assertQueued(ReserveBalanceActivateMail::class, function ($mail)
        {
            $viewData = $mail->viewData;

            $this->assertEquals('50000 INR', $viewData['reserve_limit']);

            $this->assertEquals('emails.merchant.reserve_balance_activate_alert', $mail->view);

            return true;
        });
    }

    public function testCreateReservePrimaryBalanceRazorxControl()
    {
        Mail::fake();

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn('control');

        $this->ba->adminAuth();

        $response = $this->startTest();

        $adjId = $response['id'];

        $txnId = $response['transaction_id'];

        $adjustment = $this->getDbEntityById('adjustment', $adjId);

        $balanceId = $adjustment['balance_id'];

        $balance = $this->getDbEntityById('balance', $balanceId);

        $transaction = $this->getDbEntityById('transaction', $txnId);

        $this->assertNotNull($adjustment, 'adjustment should not be null');

        $this->assertNotNull($balance, 'balance should not be null');

        $this->assertNotNull($transaction, 'transaction should not be null');

        $this->assertEquals($txnId, $adjustment['transaction_id']);
        $this->assertEquals('100abc000abc00', $adjustment['merchant_id']);
        $this->assertEquals(5000000, $adjustment['amount']);
        $this->assertEquals(Status::PROCESSED, $adjustment['status']);

        $this->assertEquals('reserve_primary', $balance['type']);
        $this->assertEquals('100abc000abc00', $balance['merchant_id']);
        $this->assertEquals(5000000, $balance['balance']);

        $this->assertEquals('adjustment', $transaction['type']);
        $this->assertEquals('100abc000abc00', $transaction['merchant_id']);
        $this->assertEquals(5000000, $transaction['amount']);
        $this->assertEquals($balanceId, $transaction['balance_id']);

        Mail::assertQueued(ReserveBalanceActivateMail::class, function ($mail)
        {
            $viewData = $mail->viewData;

            $this->assertEquals('50000 INR', $viewData['reserve_limit']);

            $this->assertEquals('emails.merchant.reserve_balance_activate_alert', $mail->view);

            return true;
        });
    }

    public function testCreateReserveBankingBalance()
    {
        Mail::fake();

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn('on');

        $this->ba->adminAuth();

        $response = $this->startTest();

        $adjId = $response['id'];

        $txnId = $response['transaction_id'];

        $adjustment = $this->getDbEntityById('adjustment', $adjId);

        $balanceId = $adjustment['balance_id'];

        $balance = $this->getDbEntityById('balance', $balanceId);

        $transaction = $this->getDbEntityById('transaction', $txnId);

        $this->assertNotNull($adjustment, 'adjustment should not be null');

        $this->assertNotNull($balance, 'balance should not be null');

        $this->assertNotNull($transaction, 'transaction should not be null');

        $this->assertEquals($txnId, $adjustment['transaction_id']);
        $this->assertEquals('100abc000abc00', $adjustment['merchant_id']);
        $this->assertEquals(5000000, $adjustment['amount']);
        $this->assertEquals(Status::PROCESSED, $adjustment['status']);

        $this->assertEquals('reserve_banking', $balance['type']);
        $this->assertEquals('100abc000abc00', $balance['merchant_id']);
        $this->assertEquals(5000000, $balance['balance']);

        $this->assertEquals('adjustment', $transaction['type']);
        $this->assertEquals('100abc000abc00', $transaction['merchant_id']);
        $this->assertEquals(5000000, $transaction['amount']);
        $this->assertEquals($balanceId, $transaction['balance_id']);

        Mail::assertQueued(ReserveBalanceActivateMail::class, function ($mail)
        {
            $viewData = $mail->viewData;

            $this->assertEquals('50000 INR', $viewData['reserve_limit']);

            $this->assertEquals('emails.merchant.reserve_balance_activate_alert', $mail->view);

            return true;
        });
    }

    public function testCreateReserveBankingBalanceRazorxControl()
    {
        Mail::fake();

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn('control');

        $this->ba->adminAuth();

        $response = $this->startTest();

        $adjId = $response['id'];

        $txnId = $response['transaction_id'];

        $adjustment = $this->getDbEntityById('adjustment', $adjId);

        $balanceId = $adjustment['balance_id'];

        $balance = $this->getDbEntityById('balance', $balanceId);

        $transaction = $this->getDbEntityById('transaction', $txnId);

        $this->assertNotNull($adjustment, 'adjustment should not be null');

        $this->assertNotNull($balance, 'balance should not be null');

        $this->assertNotNull($transaction, 'transaction should not be null');

        $this->assertEquals($txnId, $adjustment['transaction_id']);
        $this->assertEquals('100abc000abc00', $adjustment['merchant_id']);
        $this->assertEquals(5000000, $adjustment['amount']);
        $this->assertEquals(Status::PROCESSED, $adjustment['status']);

        $this->assertEquals('reserve_banking', $balance['type']);
        $this->assertEquals('100abc000abc00', $balance['merchant_id']);
        $this->assertEquals(5000000, $balance['balance']);

        $this->assertEquals('adjustment', $transaction['type']);
        $this->assertEquals('100abc000abc00', $transaction['merchant_id']);
        $this->assertEquals(5000000, $transaction['amount']);
        $this->assertEquals($balanceId, $transaction['balance_id']);

        Mail::assertQueued(ReserveBalanceActivateMail::class, function ($mail)
        {
            $viewData = $mail->viewData;

            $this->assertEquals('50000 INR', $viewData['reserve_limit']);

            $this->assertEquals('emails.merchant.reserve_balance_activate_alert', $mail->view);

            return true;
        });
    }

    public function testAddReserveBalance()
    {
        Mail::fake();

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn('on');

        $this->createFixtures('100xyz000xyz00');

        $this->fixtures->create(
            'balance',
            [
                'id'            => '100def000def00',
                'balance'       => 500000,
                'type'          => 'reserve_primary',
                'merchant_id'   => '100xyz000xyz00'
            ]
        );

        $this->ba->adminAuth();

        $response = $this->startTest();

        $adjId = $response['id'];

        $txnId = $response['transaction_id'];

        $adjustment = $this->getDbEntityById('adjustment', $adjId);

        $balanceId = $adjustment['balance_id'];

        $balance = $this->getDbEntityById('balance', $balanceId);

        $transaction = $this->getDbEntityById('transaction', $txnId);

        $this->assertNotNull($adjustment, 'adjustment should not be null');

        $this->assertNotNull($balance, 'balance should not be null');

        $this->assertNotNull($transaction, 'transaction should not be null');

        $this->assertEquals($txnId, $adjustment['transaction_id']);
        $this->assertEquals('100xyz000xyz00', $adjustment['merchant_id']);
        $this->assertEquals(500000, $adjustment['amount']);
        $this->assertEquals(Status::PROCESSED, $adjustment['status']);

        $this->assertEquals('100def000def00', $balanceId);
        $this->assertEquals('reserve_primary', $balance['type']);
        $this->assertEquals('100xyz000xyz00', $balance['merchant_id']);
        $this->assertEquals(1000000, $balance['balance']);

        $this->assertEquals('adjustment', $transaction['type']);
        $this->assertEquals('100xyz000xyz00', $transaction['merchant_id']);
        $this->assertEquals(500000, $transaction['amount']);
        $this->assertEquals($balanceId, $transaction['balance_id']);

        Mail::assertNotQueued(ReserveBalanceActivateMail::class);
    }

    public function testCreateNegativeAdjustmentWithLowBalance()
    {
        Mail::fake();

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
                'negative_limit_auto'           => 5000,
                'negative_limit_manual'         => 5000
            ]
        );

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn('on');

        $this->ba->adminAuth();

        $this->startTest();

        $balance = $this->getDbEntity('balance', ['id' => '100def000def00']);

        $this->assertEquals(-4000, $balance['balance']);
        $adjustment = $this->getDbLastEntity('adjustment');
        $this->assertEquals(Status::PROCESSED, $adjustment->getStatus());

        Mail::assertQueued(NegativeBalanceThresholdAlert::class);
    }

    public function testCreateAdjustmentFromBatchRoute()
    {
        $this->fixtures->create('balance', [
                'id'            => '100def000def00',
                'balance'       => 10000,
                'type'          => 'primary',
                'merchant_id'   => '100abc000abc00'
            ]);

        $this->ba->batchAppAuth();

        $this->startTest();
    }

    private function createFixtures(string $id = null)
    {
        $merchantId = $id ?? '100abc000abc00';

        $this->fixtures->create('merchant', ['id' => $merchantId, 'email' => 'mahbubani.amit@gmail.com']);

        $admin = $this->ba->getAdmin();

        $this->fixtures->admin->edit($admin['id'], ['allow_all_merchants' => true]);

        $this->ba->adminProxyAuth($merchantId, 'rzp_test_'.$merchantId);
    }

    public function testTransactionCreatedWebhookAndLedgerSnsAndMailOnAdjustmentCreateForBankingBalance()
    {
        $ledgerSnsPayloadArray = [];

        $this->mockLedgerSns(1, $ledgerSnsPayloadArray);

        Mail::fake();

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment', 'getCachedTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->willReturn('on');

        $this->app->razorx->method('getCachedTreatment')
                          ->willReturn('off');

        $this->fixtures->create('balance',
                                [
                                    'type'           => 'banking',
                                    'account_type'   => 'shared',
                                    'account_number' => 'ABC123PQR',
                                    'merchant_id'    => '100abc000abc00',
                                    'balance'        => 30000
                                ]);

        $balance = $this->getDbLastEntity('balance');

        // Need to create a Banking Account since we send this data to ledger in ledger calls
        $bankingAccountAttributes = [
            'id'                    =>  'ABCde1234ABCde',
            'account_number'        =>  '2224440041626905',
            'balance_id'            =>  $balance['id'],
            'account_type'          =>  'nodal',
        ];

        $this->createBankingAccount($bankingAccountAttributes);

        // Create merchant user mapping
        $this->fixtures->on('live')->user->createUserMerchantMapping([
                                                                         'merchant_id' => '100abc000abc00',
                                                                         'user_id'     => User::MERCHANT_USER_ID,
                                                                         'product'     => 'banking',
                                                                         'role'        => 'owner',
                                                                     ], 'test');

        $this->app->forgetInstance('basicauth');

        $admin = $this->ba->getAdmin();

        $this->fixtures->on('test')->admin->edit($admin['id'], ['allow_all_merchants' => true]);

        $this->ba->adminAuth();

        $eventTestDataKey = 'testTransactionCreatedWebhookFiringAndMailOnAdjustmentCreateForBankingBalanceData';
        $this->expectWebhookEventWithContents('transaction.created', $eventTestDataKey);

        $response = $this->startTest();

        $adjId = $response['id'];

        $txnId = $response['transaction_id'];

        $adjustment = $this->getDbEntityById('adjustment', $adjId);

        $balanceId = $adjustment['balance_id'];

        $balance = $this->getDbEntityById('balance', $balanceId);

        $transaction = $this->getDbEntityById('transaction', $txnId);

        $this->assertNotNull($adjustment, 'adjustment should not be null');

        $this->assertNotNull($balance, 'balance should not be null');

        $this->assertNotNull($transaction, 'transaction should not be null');

        $this->assertEquals($txnId, $adjustment['transaction_id']);
        $this->assertEquals('100abc000abc00', $adjustment['merchant_id']);
        $this->assertEquals(250000, $adjustment['amount']);
        $this->assertEquals(Status::PROCESSED, $adjustment['status']);

        $this->assertEquals('banking', $balance['type']);
        $this->assertEquals('100abc000abc00', $balance['merchant_id']);
        $this->assertEquals(280000, $balance['balance']);

        $this->assertEquals('adjustment', $transaction['type']);
        $this->assertEquals('100abc000abc00', $transaction['merchant_id']);
        $this->assertEquals(250000, $transaction['amount']);
        $this->assertEquals($balanceId, $transaction['balance_id']);

        Mail::assertQueued(Adjustment::class, function($mail)
        {
            $viewData = $mail->viewData;

            $this->assertEquals('250000', $viewData['txn']['amount']); // raw amount
            $this->assertEquals('2,500.00', amount_format_IN($viewData['txn']['amount'])); // formatted amount

            $expectedData = [
                'source' => [
                    'description' => 'Account: ABC123, Bank: ICICI',
                    'amount'      => 250000
                ],
            ];

            $this->assertArraySelectiveEquals($expectedData, $viewData);

            $mailSubject = "[Test Mode] Your A/C ending with XXXXX3PQR has been credited by INR 2,500.00";

            $this->assertEquals($mailSubject, $mail->subject);

            $this->assertEquals('emails.transaction.adjustment', $mail->view);

            return true;
        });

        $adjustmentsCreated = $this->getDbEntities('adjustment');

        for ($index = 0; $index<count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['identifiers'] = json_decode($ledgerRequestPayload['identifiers'], true);
            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('test', $ledgerRequestPayload['mode']);
            $this->assertEquals($adjustmentsCreated[$index]->getPublicId(), $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('100abc000abc00', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals('0', $ledgerRequestPayload['commission']);
            $this->assertEquals('0', $ledgerRequestPayload['tax']);
            $this->assertEquals('positive_adjustment_processed', $ledgerRequestPayload['transactor_event']);
            $this->assertArrayNotHasKey('fee_accounting', $ledgerRequestPayload['additional_params']);
            $this->assertArrayNotHasKey('fts_fund_account_id', $ledgerRequestPayload['identifiers']);
            $this->assertArrayNotHasKey('fts_account_type', $ledgerRequestPayload['identifiers']);
        }
    }

    public function testTransactionCreatedWebhookAndLedgerSnsAndMailOnNegativeAdjustmentCreateForBankingBalance()
    {
        $ledgerSnsPayloadArray = [];

        $this->mockLedgerSns(1, $ledgerSnsPayloadArray);

        Mail::fake();

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment', 'getCachedTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->willReturn('on');

        $this->app->razorx->method('getCachedTreatment')
                          ->willReturn('off');

        $this->fixtures->create('balance',
                                [
                                    'type'           => 'banking',
                                    'account_type'   => 'shared',
                                    'account_number' => 'ABC123PQR',
                                    'merchant_id'    => '100abc000abc00',
                                    'balance'        => 280000
                                ]);

        $balance = $this->getDbLastEntity('balance');

        // Need to create a Banking Account since we send this data to ledger in ledger calls
        $bankingAccountAttributes = [
            'id'                    =>  'ABCde1234ABCde',
            'account_number'        =>  '2224440041626905',
            'balance_id'            =>  $balance['id'],
            'account_type'          =>  'nodal',
        ];

        $this->createBankingAccount($bankingAccountAttributes);

        // Create merchant user mapping
        $this->fixtures->on('live')->user->createUserMerchantMapping([
                                                                         'merchant_id' => '100abc000abc00',
                                                                         'user_id'     => User::MERCHANT_USER_ID,
                                                                         'product'     => 'banking',
                                                                         'role'        => 'owner',
                                                                     ], 'test');

        $this->app->forgetInstance('basicauth');

        $admin = $this->ba->getAdmin();

        $this->fixtures->on('test')->admin->edit($admin['id'], ['allow_all_merchants' => true]);

        $this->ba->adminAuth();

        $eventTestDataKey = 'testTransactionCreatedWebhookFiringAndMailOnNegativeAdjustmentCreateForBankingBalanceData';
        $this->expectWebhookEventWithContents('transaction.created', $eventTestDataKey);

        $response = $this->startTest();

        $adjId = $response['id'];

        $txnId = $response['transaction_id'];

        $adjustment = $this->getDbEntityById('adjustment', $adjId);

        $balanceId = $adjustment['balance_id'];

        $balance = $this->getDbEntityById('balance', $balanceId);

        $transaction = $this->getDbEntityById('transaction', $txnId);

        $this->assertNotNull($adjustment, 'adjustment should not be null');

        $this->assertNotNull($balance, 'balance should not be null');

        $this->assertNotNull($transaction, 'transaction should not be null');

        $this->assertEquals($txnId, $adjustment['transaction_id']);
        $this->assertEquals('100abc000abc00', $adjustment['merchant_id']);
        $this->assertEquals(-250000, $adjustment['amount']);
        $this->assertEquals(Status::PROCESSED, $adjustment['status']);

        $this->assertEquals('banking', $balance['type']);
        $this->assertEquals('100abc000abc00', $balance['merchant_id']);
        $this->assertEquals(30000, $balance['balance']);

        $this->assertEquals('adjustment', $transaction['type']);
        $this->assertEquals('100abc000abc00', $transaction['merchant_id']);
        $this->assertEquals(250000, $transaction['amount']);
        $this->assertEquals($balanceId, $transaction['balance_id']);

        Mail::assertQueued(Adjustment::class, function($mail)
        {
            $viewData = $mail->viewData;

            $this->assertEquals('250000', $viewData['txn']['amount']); // raw amount
            $this->assertEquals('2,500.00', amount_format_IN($viewData['txn']['amount'])); // formatted amount

            $expectedData = [
                'source' => [
                    'description' => 'Account: ABC123, Bank: ICICI',
                    'amount'      => -250000
                ],
            ];

            $this->assertArraySelectiveEquals($expectedData, $viewData);

            $mailSubject = "[Test Mode] Your A/C ending with XXXXX3PQR has been debited by INR 2,500.00";

            $this->assertEquals($mailSubject, $mail->subject);

            $this->assertEquals('emails.transaction.adjustment', $mail->view);

            return true;
        });

        $adjustmentsCreated = $this->getDbEntities('adjustment');

        for ($index = 0; $index<count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['identifiers'] = json_decode($ledgerRequestPayload['identifiers'], true);
            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('test', $ledgerRequestPayload['mode']);
            $this->assertEquals($adjustmentsCreated[$index]->getPublicId(), $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('100abc000abc00', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals('0', $ledgerRequestPayload['commission']);
            $this->assertEquals('0', $ledgerRequestPayload['tax']);
            $this->assertEquals('negative_adjustment_processed', $ledgerRequestPayload['transactor_event']);
            $this->assertArrayNotHasKey('fee_accounting', $ledgerRequestPayload['additional_params']);
            $this->assertArrayNotHasKey('fts_fund_account_id', $ledgerRequestPayload['identifiers']);
            $this->assertArrayNotHasKey('fts_account_type', $ledgerRequestPayload['identifiers']);
        }
    }

    public function testTransactionCreatedWebhookFiringAndMailOnAdjustmentCreateForBankingBalanceRazorxControl()
    {
        Mail::fake();

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment', 'getCachedTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->willReturn('control');

        $this->app->razorx->method('getCachedTreatment')
                          ->willReturn('off');

        $this->fixtures->create('balance',
                                [
                                    'type'           => 'banking',
                                    'account_type'   => 'shared',
                                    'account_number' => 'ABC123PQR',
                                    'merchant_id'    => '100abc000abc00',
                                    'balance'        => 30000
                                ]);

        // Create merchant user mapping
        $this->fixtures->on('live')->user->createUserMerchantMapping([
                                                                         'merchant_id' => '100abc000abc00',
                                                                         'user_id'     => User::MERCHANT_USER_ID,
                                                                         'product'     => 'banking',
                                                                         'role'        => 'owner',
                                                                     ], 'test');

        $this->app->forgetInstance('basicauth');

        $admin = $this->ba->getAdmin();

        $this->fixtures->on('test')->admin->edit($admin['id'], ['allow_all_merchants' => true]);

        $this->ba->adminAuth();

        $eventTestDataKey = 'testTransactionCreatedWebhookFiringAndMailOnAdjustmentCreateForBankingBalanceRazorxControlData';
        $this->expectWebhookEventWithContents('transaction.created', $eventTestDataKey);

        $response = $this->startTest();

        $adjId = $response['id'];

        $txnId = $response['transaction_id'];

        $adjustment = $this->getDbEntityById('adjustment', $adjId);

        $balanceId = $adjustment['balance_id'];

        $balance = $this->getDbEntityById('balance', $balanceId);

        $transaction = $this->getDbEntityById('transaction', $txnId);

        $this->assertNotNull($adjustment, 'adjustment should not be null');

        $this->assertNotNull($balance, 'balance should not be null');

        $this->assertNotNull($transaction, 'transaction should not be null');

        $this->assertEquals($txnId, $adjustment['transaction_id']);
        $this->assertEquals('100abc000abc00', $adjustment['merchant_id']);
        $this->assertEquals(250000, $adjustment['amount']);
        $this->assertEquals(Status::PROCESSED, $adjustment['status']);

        $this->assertEquals('banking', $balance['type']);
        $this->assertEquals('100abc000abc00', $balance['merchant_id']);
        $this->assertEquals(280000, $balance['balance']);

        $this->assertEquals('adjustment', $transaction['type']);
        $this->assertEquals('100abc000abc00', $transaction['merchant_id']);
        $this->assertEquals(250000, $transaction['amount']);
        $this->assertEquals($balanceId, $transaction['balance_id']);

        Mail::assertQueued(Adjustment::class, function($mail)
        {
            $viewData = $mail->viewData;

            $this->assertEquals('250000', $viewData['txn']['amount']); // raw amount
            $this->assertEquals('2,500.00', amount_format_IN($viewData['txn']['amount'])); // formatted amount

            $expectedData = [
                'source' => [
                    'description' => 'Account: ABC123, Bank: ICICI',
                    'amount'      => 250000
                ],
            ];

            $this->assertArraySelectiveEquals($expectedData, $viewData);

            $mailSubject = "[Test Mode] Your A/C ending with XXXXX3PQR has been credited by INR 2,500.00";

            $this->assertEquals($mailSubject, $mail->subject);

            $this->assertEquals('emails.transaction.adjustment', $mail->view);

            return true;
        });
    }

    public function testTransactionCreatedWebhookFiringAndMailOnNegativeAdjustmentCreateForBankingBalanceRazorxControl()
    {
        Mail::fake();

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment', 'getCachedTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->willReturn('control');

        $this->app->razorx->method('getCachedTreatment')
                          ->willReturn('off');

        $this->fixtures->create('balance',
                                [
                                    'type'           => 'banking',
                                    'account_type'   => 'shared',
                                    'account_number' => 'ABC123PQR',
                                    'merchant_id'    => '100abc000abc00',
                                    'balance'        => 280000
                                ]);

        // Create merchant user mapping
        $this->fixtures->on('live')->user->createUserMerchantMapping([
                                                                         'merchant_id' => '100abc000abc00',
                                                                         'user_id'     => User::MERCHANT_USER_ID,
                                                                         'product'     => 'banking',
                                                                         'role'        => 'owner',
                                                                     ], 'test');

        $this->app->forgetInstance('basicauth');

        $admin = $this->ba->getAdmin();

        $this->fixtures->on('test')->admin->edit($admin['id'], ['allow_all_merchants' => true]);

        $this->ba->adminAuth();

        $eventTestDataKey = 'testTransactionCreatedWebhookFiringAndMailOnNegativeAdjustmentCreateForBankingBalanceRazorxControlData';
        $this->expectWebhookEventWithContents('transaction.created', $eventTestDataKey);

        $response = $this->startTest();

        $adjId = $response['id'];

        $txnId = $response['transaction_id'];

        $adjustment = $this->getDbEntityById('adjustment', $adjId);

        $balanceId = $adjustment['balance_id'];

        $balance = $this->getDbEntityById('balance', $balanceId);

        $transaction = $this->getDbEntityById('transaction', $txnId);

        $this->assertNotNull($adjustment, 'adjustment should not be null');

        $this->assertNotNull($balance, 'balance should not be null');

        $this->assertNotNull($transaction, 'transaction should not be null');

        $this->assertEquals($txnId, $adjustment['transaction_id']);
        $this->assertEquals('100abc000abc00', $adjustment['merchant_id']);
        $this->assertEquals(-250000, $adjustment['amount']);
        $this->assertEquals(Status::PROCESSED, $adjustment['status']);

        $this->assertEquals('banking', $balance['type']);
        $this->assertEquals('100abc000abc00', $balance['merchant_id']);
        $this->assertEquals(30000, $balance['balance']);

        $this->assertEquals('adjustment', $transaction['type']);
        $this->assertEquals('100abc000abc00', $transaction['merchant_id']);
        $this->assertEquals(250000, $transaction['amount']);
        $this->assertEquals($balanceId, $transaction['balance_id']);

        Mail::assertQueued(Adjustment::class, function($mail)
        {
            $viewData = $mail->viewData;

            $this->assertEquals('250000', $viewData['txn']['amount']); // raw amount
            $this->assertEquals('2,500.00', amount_format_IN($viewData['txn']['amount'])); // formatted amount

            $expectedData = [
                'source' => [
                    'description' => 'Account: ABC123, Bank: ICICI',
                    'amount'      => -250000
                ],
            ];

            $this->assertArraySelectiveEquals($expectedData, $viewData);

            $mailSubject = "[Test Mode] Your A/C ending with XXXXX3PQR has been debited by INR 2,500.00";

            $this->assertEquals($mailSubject, $mail->subject);

            $this->assertEquals('emails.transaction.adjustment', $mail->view);

            return true;
        });
    }

    public function testLedgerSnsForPositiveAdjustmentCreationOnLiveMode()
    {
        // No Ledger SNS call because the feature isn't enabled
        $this->mockLedgerSns(0);

        $countOfAdjustmentsBeforeTest = count($this->getDbEntities('adjustment', [], 'live'));

        $this->fixtures->on('live')->create('balance',
                                            [
                                                'type'           => 'banking',
                                                'account_type'   => 'shared',
                                                'account_number' => 'ABC123PQR',
                                                'merchant_id'    => '10000000000000',
                                                'balance'        => 280000
                                            ]);

        $this->ba->adminAuth('live');

        $this->startTest();

        $countOfAdjustmentsAfterTest = count($this->getDbEntities('adjustment', [], 'live'));

        $this->assertEquals($countOfAdjustmentsAfterTest, $countOfAdjustmentsBeforeTest+1);
    }


    public function testLedgerSnsForPositiveAdjustmentCreationOnLiveModeWhenLedgerFeatureEnabled()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_JOURNAL_WRITES]);

        $ledgerSnsPayloadArray = [];

        $this->mockLedgerSns(1, $ledgerSnsPayloadArray);

        $countOfAdjustmentsBeforeTest = count($this->getDbEntities('adjustment', [], 'live'));

        $this->fixtures->on('live')->create('balance',
                                            [
                                                'type'           => 'banking',
                                                'account_type'   => 'shared',
                                                'account_number' => 'ABC123PQR',
                                                'merchant_id'    => '10000000000000',
                                                'balance'        => 280000
                                            ]);

        $balance = $this->getDbLastEntity('balance', 'live');

        // Need to create a Banking Account since we send this data to ledger in ledger calls
        $bankingAccountAttributes = [
            'id'                    =>  'ABCde1234ABCde',
            'account_number'        =>  '2224440041626905',
            'balance_id'            =>  $balance['id'],
            'account_type'          =>  'nodal',
        ];

        $this->createBankingAccount($bankingAccountAttributes, 'live');

        $this->ba->adminAuth('live');

        $this->testData[__FUNCTION__] = $this->testData['testLedgerSnsForPositiveAdjustmentCreationOnLiveMode'];

        $this->startTest();

        $adjustmentsCreated = $this->getDbEntities('adjustment', [], 'live');

        $countOfAdjustmentsAfterTest = count($adjustmentsCreated);

        $this->assertEquals($countOfAdjustmentsAfterTest, $countOfAdjustmentsBeforeTest+1);

        for ($index = 0; $index<count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['identifiers'] = json_decode($ledgerRequestPayload['identifiers'], true);
            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('live', $ledgerRequestPayload['mode']);
            $this->assertEquals($adjustmentsCreated[$index]->getPublicId(), $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals('0', $ledgerRequestPayload['commission']);
            $this->assertEquals('0', $ledgerRequestPayload['tax']);
            $this->assertEquals('positive_adjustment_processed', $ledgerRequestPayload['transactor_event']);
            $this->assertArrayNotHasKey('fee_accounting', $ledgerRequestPayload['additional_params']);
            $this->assertArrayNotHasKey('fts_fund_account_id', $ledgerRequestPayload['identifiers']);
            $this->assertArrayNotHasKey('fts_account_type', $ledgerRequestPayload['identifiers']);
        }
    }

    public function testForPositiveAdjustmentCreationOnLiveModeWhenLedgerReverseShadowEnabled()
    {
        $this->app['config']->set('applications.ledger.enabled', false);

        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $countOfAdjustmentsBeforeTest = count($this->getDbEntities('adjustment', [], 'live'));

        $this->fixtures->on('live')->create('balance',
            [
                'type'           => 'banking',
                'account_type'   => 'shared',
                'account_number' => 'ABC123PQR',
                'merchant_id'    => '10000000000000',
                'balance'        => 280000
            ]);

        $balance = $this->getDbLastEntity('balance', 'live');

        // Need to create a Banking Account since we send this data to ledger in ledger calls
        $bankingAccountAttributes = [
            'id'                    =>  'ABCde1234ABCde',
            'account_number'        =>  '2224440041626905',
            'balance_id'            =>  $balance['id'],
            'account_type'          =>  'nodal',
        ];

        $this->createBankingAccount($bankingAccountAttributes, 'live');

        $this->ba->adminAuth('live');

        $this->testData[__FUNCTION__] = $this->testData['testLedgerSnsForPositiveAdjustmentCreationOnLiveMode'];

        $this->startTest();

        $adjustmentsCreated = $this->getDbEntities('adjustment', [], 'live');

        $countOfAdjustmentsAfterTest = count($adjustmentsCreated);

        $this->assertEquals($countOfAdjustmentsAfterTest, $countOfAdjustmentsBeforeTest+1);

        $newAdjustments = $this->getDbLastEntity('adjustment', 'live');

        $this->assertEquals(Status::PROCESSED, $newAdjustments['status']);

        $this->assertNull($newAdjustments['transaction_id']);
    }

    public function testForPositiveAdjustmentCreationOnLiveModeWhenLedgerReverseShadowSyncFailureAndAsyncSuccess()
    {
        $this->app['config']->set('applications.ledger.enabled', true);

        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        // forcing async retry after all sync retry failures
        $mockLedger->shouldReceive('createJournal')
            ->times(4)
            ->andThrow(new \WpOrg\Requests\Exception(
                'Unexpected response code received from Ledger service.',
                null,
                [
                    'status_code'   => 500,
                    'response_body' => [
                        'code' => 'invalid_argument',
                        'msg' => 'unknown',
                    ],
                ]
            ));

        $mockLedger->shouldReceive('fetchByTransactor')
            ->times(1)
            ->andReturn([
                "body" => [
                    "id"                => "sampleJournlID",
                    "created_at"        => "1623848289",
                    "updated_at"        => "1632368730",
                    "amount"            => "130.000000",
                    "base_amount"       => "130.000000",
                    "currency"          => "INR",
                    "tenant"            => "X",
                    "transactor_id"     => "adj_IwHCToefEWVgph",
                    "transactor_event"  => "positive_adjustment_processed",
                    "transaction_date"  => "1611132045",
                    "ledger_entry" => [
                        [
                            "id"          => "HNjsypHNXdSiei",
                            "created_at"  => "1623848289",
                            "updated_at"  => "1623848289",
                            "merchant_id" => "HN59oOIDACOXt3",
                            "journal_id"  => "sampleJournlID",
                            "account_id"  => "GoRNyEuu9Hl0OZ",
                            "amount"      => "130.000000",
                            "base_amount" => "130.000000",
                            "type"        => "debit",
                            "currency"    => "INR",
                            "balance"     => ""
                        ],
                        [
                            "id"          => "HNjsypHPOUlxDR",
                            "created_at"  => "1623848289",
                            "updated_at"  => "1623848289",
                            "merchant_id" => "HN59oOIDACOXt3",
                            "journal_id"  => "sampleJournlID",
                            "account_id"  => "HN5AGgmKu0ki13",
                            "amount"      => "130.000000",
                            "base_amount" => "130.000000",
                            "type"        => "credit",
                            "currency"    => "INR",
                            "balance"     => "",
                            'account_entities' => [
                                'account_type'       => ['payable'],
                                'fund_account_type'  => ['merchant_va'],
                            ],
                        ]
                    ]
                ]
            ]);

        $countOfAdjustmentsBeforeTest = count($this->getDbEntities('adjustment', [], 'live'));

        $this->fixtures->on('live')->create('balance',
            [
                'type'           => 'banking',
                'account_type'   => 'shared',
                'account_number' => 'ABC123PQR',
                'merchant_id'    => '10000000000000',
                'balance'        => 280000
            ]);

        $balance = $this->getDbLastEntity('balance', 'live');

        // Need to create a Banking Account since we send this data to ledger in ledger calls
        $bankingAccountAttributes = [
            'id'                    =>  'ABCde1234ABCde',
            'account_number'        =>  '2224440041626905',
            'balance_id'            =>  $balance['id'],
            'account_type'          =>  'nodal',
        ];

        $this->createBankingAccount($bankingAccountAttributes, 'live');

        $this->ba->adminAuth('live');

        $this->testData[__FUNCTION__] = $this->testData['testLedgerSnsForPositiveAdjustmentCreationOnLiveMode'];

        $this->startTest();

        $adjustmentsCreated = $this->getDbEntities('adjustment', [], 'live');

        $countOfAdjustmentsAfterTest = count($adjustmentsCreated);

        $this->assertEquals($countOfAdjustmentsAfterTest, $countOfAdjustmentsBeforeTest+1);

        $newAdjustments = $this->getDbLastEntity('adjustment', 'live');
        $newAdjustmentsTxn = $this->getDbLastEntity('transaction', 'live');

        // assert api adjustment
        $this->assertEquals(Status::PROCESSED, $newAdjustments['status']);
        $this->assertEquals(250000, $newAdjustments['amount']);

    }

    public function testForPositiveAdjustmentCreationOnLiveModeWhenLedgerReverseShadowSyncAsyncFailure()
    {
        $this->app['config']->set('applications.ledger.enabled', true);

        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        // forcing async retry after all sync retry failures
        $mockLedger->shouldReceive('createJournal')
            ->times(4)
            ->andThrow(new \WpOrg\Requests\Exception(
                'Unexpected response code received from Ledger service.',
                null,
                [
                    'status_code'   => 500,
                    'response_body' => [
                        'code' => 'invalid_argument',
                        'msg' => 'unknown',
                    ],
                ]
            ));

        $mockLedger->shouldReceive('fetchByTransactor')
            ->times(1)
            ->andThrow(new \WpOrg\Requests\Exception(
                'Unexpected response code received from Ledger service.',
                null,
                [
                    'status_code'   => 500,
                    'response_body' => [
                        'code' => 'invalid_argument',
                        'msg' => 'unknown',
                    ],
                ]
            ));

        $countOfAdjustmentsBeforeTest = count($this->getDbEntities('adjustment', [], 'live'));

        $this->fixtures->on('live')->create('balance',
            [
                'type'           => 'banking',
                'account_type'   => 'shared',
                'account_number' => 'ABC123PQR',
                'merchant_id'    => '10000000000000',
                'balance'        => 280000
            ]);

        $balance = $this->getDbLastEntity('balance', 'live');

        // Need to create a Banking Account since we send this data to ledger in ledger calls
        $bankingAccountAttributes = [
            'id'                    =>  'ABCde1234ABCde',
            'account_number'        =>  '2224440041626905',
            'balance_id'            =>  $balance['id'],
            'account_type'          =>  'nodal',
        ];

        $this->createBankingAccount($bankingAccountAttributes, 'live');

        $this->ba->adminAuth('live');

        $this->testData[__FUNCTION__] = $this->testData['testLedgerSnsForPositiveAdjustmentCreationOnLiveMode'];

        $this->startTest();

        $adjustmentsCreated = $this->getDbEntities('adjustment', [], 'live');

        $countOfAdjustmentsAfterTest = count($adjustmentsCreated);

        $this->assertEquals($countOfAdjustmentsAfterTest, $countOfAdjustmentsBeforeTest+1);

        $newAdjustments = $this->getDbLastEntity('adjustment', 'live');

        // assert api adjustment
        $this->assertEquals(Status::CREATED, $newAdjustments['status']);
        $this->assertNull($newAdjustments['transaction_id']);
        $this->assertEquals(250000, $newAdjustments['amount']);
    }

    public function testForPositiveAdjustmentCreationOnLiveModeWhenLedgerReverseShadowStatusCheckNoRecord()
    {
        $this->app['config']->set('applications.ledger.enabled', true);

        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        // forcing async retry after all sync retry failures
        $mockLedger->shouldReceive('createJournal')
            ->times(5)
            ->andThrow(new \WpOrg\Requests\Exception(
                'Unexpected response code received from Ledger service.',
                null,
                [
                    'status_code'   => 500,
                    'response_body' => [
                        'code' => 'invalid_argument',
                        'msg' => 'unknown',
                    ],
                ]
            ));

        $mockLedger->shouldReceive('fetchByTransactor')
            ->times(1)
            ->andThrow(new \RZP\Exception\RuntimeException(
                'Unexpected response code received from Ledger service.',
                [
                    'status_code'   => 500,
                    'response_body' => [
                        'code' => 'invalid_argument',
                        'msg' => 'record_not_found',
                    ],
                ]
            ));

        $countOfAdjustmentsBeforeTest = count($this->getDbEntities('adjustment', [], 'live'));

        $this->fixtures->on('live')->create('balance',
            [
                'type'           => 'banking',
                'account_type'   => 'shared',
                'account_number' => 'ABC123PQR',
                'merchant_id'    => '10000000000000',
                'balance'        => 280000
            ]);

        $balance = $this->getDbLastEntity('balance', 'live');

        // Need to create a Banking Account since we send this data to ledger in ledger calls
        $bankingAccountAttributes = [
            'id'                    =>  'ABCde1234ABCde',
            'account_number'        =>  '2224440041626905',
            'balance_id'            =>  $balance['id'],
            'account_type'          =>  'nodal',
        ];

        $this->createBankingAccount($bankingAccountAttributes, 'live');

        $this->ba->adminAuth('live');

        $this->testData[__FUNCTION__] = $this->testData['testLedgerSnsForPositiveAdjustmentCreationOnLiveMode'];

        $this->startTest();

        $adjustmentsCreated = $this->getDbEntities('adjustment', [], 'live');

        $countOfAdjustmentsAfterTest = count($adjustmentsCreated);

        $this->assertEquals($countOfAdjustmentsAfterTest, $countOfAdjustmentsBeforeTest+1);

        $newAdjustments = $this->getDbLastEntity('adjustment', 'live');

        // assert api adjustment
        $this->assertEquals(Status::CREATED, $newAdjustments['status']);
        $this->assertNull($newAdjustments['transaction_id']);
        $this->assertEquals(250000, $newAdjustments['amount']);
    }

    public function testForPositiveAdjustmentCreationOnLiveModeWhenLedgerReverseShadowPostStatusCheckSuccess()
    {
        $this->app['config']->set('applications.ledger.enabled', true);

        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        // ledge response
        $ledgerSuccessResponse = [
            "body" => [
                "id"                => "sampleJournlID",
                "created_at"        => "1623848289",
                "updated_at"        => "1632368730",
                "amount"            => "130.000000",
                "base_amount"       => "130.000000",
                "currency"          => "INR",
                "tenant"            => "X",
                "transactor_id"     => "adj_SamplePayoutId4",
                "transactor_event"  => "positive_adjustment_processed",
                "transaction_date"  => "1611132045",
                "ledger_entry" => [
                    [
                        "id"          => "HNjsypHNXdSiei",
                        "created_at"  => "1623848289",
                        "updated_at"  => "1623848289",
                        "merchant_id" => "HN59oOIDACOXt3",
                        "journal_id"  => "sampleJournlID",
                        "account_id"  => "GoRNyEuu9Hl0OZ",
                        "amount"      => "130.000000",
                        "base_amount" => "130.000000",
                        "type"        => "debit",
                        "currency"    => "INR",
                        "balance"     => "",
                        'account_entities' => [
                            'account_type'       => ['payable'],
                            'fund_account_type'  => ['merchant_va'],
                        ],
                    ],
                    [
                        "id"          => "HNjsypHPOUlxDR",
                        "created_at"  => "1623848289",
                        "updated_at"  => "1623848289",
                        "merchant_id" => "HN59oOIDACOXt3",
                        "journal_id"  => "sampleJournlID",
                        "account_id"  => "HN5AGgmKu0ki13",
                        "amount"      => "130.000000",
                        "base_amount" => "130.000000",
                        "type"        => "credit",
                        "currency"    => "INR",
                        "balance"     => ""
                    ]
                ]
            ]
        ];

        // forcing async retry after all sync retry failures
        $mockLedger->shouldReceive('createJournal')
            ->times(5)
            ->andReturnUsing(
                function () use($ledgerSuccessResponse) {
                    static $counter = 0;
                    switch ($counter++) {
                        // 4th call is made from async job, which should succeed for this test
                        case 4:
                            return $ledgerSuccessResponse;
                            break;
                        default:
                            // 0th-3rd call is made while sync retries, which should fail for this test
                            throw new \WpOrg\Requests\Exception(
                                'Unexpected response code received from Ledger service.',
                                null,
                                [
                                    'status_code'   => 500,
                                    'response_body' => [
                                        'code' => 'invalid_argument',
                                        'msg' => 'unknown',
                                    ],
                                ]
                            );
                            break;
                    }
                }
            );

        $mockLedger->shouldReceive('fetchByTransactor')
            ->times(1)
            ->andThrow(new \RZP\Exception\RuntimeException(
                'Unexpected response code received from Ledger service.',
                [
                    'status_code'   => 500,
                    'response_body' => [
                        'code' => 'invalid_argument',
                        'msg' => 'record_not_found',
                    ],
                ]
            ));

        $countOfAdjustmentsBeforeTest = count($this->getDbEntities('adjustment', [], 'live'));

        $this->fixtures->on('live')->create('balance',
            [
                'type'           => 'banking',
                'account_type'   => 'shared',
                'account_number' => 'ABC123PQR',
                'merchant_id'    => '10000000000000',
                'balance'        => 280000
            ]);

        $balance = $this->getDbLastEntity('balance', 'live');

        // Need to create a Banking Account since we send this data to ledger in ledger calls
        $bankingAccountAttributes = [
            'id'                    =>  'ABCde1234ABCde',
            'account_number'        =>  '2224440041626905',
            'balance_id'            =>  $balance['id'],
            'account_type'          =>  'nodal',
        ];

        $this->createBankingAccount($bankingAccountAttributes, 'live');

        $this->ba->adminAuth('live');

        $this->testData[__FUNCTION__] = $this->testData['testLedgerSnsForPositiveAdjustmentCreationOnLiveMode'];

        $this->startTest();

        $adjustmentsCreated = $this->getDbEntities('adjustment', [], 'live');

        $countOfAdjustmentsAfterTest = count($adjustmentsCreated);

        $this->assertEquals($countOfAdjustmentsAfterTest, $countOfAdjustmentsBeforeTest+1);

        $newAdjustments = $this->getDbLastEntity('adjustment', 'live');
        $newAdjustmentsTxn = $this->getDbLastEntity('transaction', 'live');

        // assert api adjustment
        $this->assertEquals(Status::PROCESSED, $newAdjustments['status']);
        $this->assertEquals(250000, $newAdjustments['amount']);

    }

    public function testLedgerSnsForNegativeAdjustmentCreationOnLiveMode()
    {
        // No Ledger SNS call because the feature isn't enabled
        $this->mockLedgerSns(0);

        $countOfAdjustmentsBeforeTest = count($this->getDbEntities('adjustment', [], 'live'));

        $this->fixtures->on('live')->create('balance',
                                            [
                                                'type'           => 'banking',
                                                'account_type'   => 'shared',
                                                'account_number' => 'ABC123PQR',
                                                'merchant_id'    => '10000000000000',
                                                'balance'        => 280000
                                            ]);

        $this->ba->adminAuth('live');

        $this->startTest();

        $countOfAdjustmentsAfterTest = count($this->getDbEntities('adjustment', [], 'live'));

        $newAdjustments = $this->getDbLastEntity('adjustment', 'live');

        $this->assertEquals(Status::PROCESSED, $newAdjustments['status']);

        $this->assertEquals($countOfAdjustmentsAfterTest, $countOfAdjustmentsBeforeTest+1);
    }


    public function testLedgerSnsForNegativeAdjustmentCreationOnLiveModeWhenLedgerFeatureEnabled()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_JOURNAL_WRITES]);

        $ledgerSnsPayloadArray = [];

        $this->mockLedgerSns(1, $ledgerSnsPayloadArray);

        $countOfAdjustmentsBeforeTest = count($this->getDbEntities('adjustment', [], 'live'));

        $this->fixtures->on('live')->create('balance',
                                            [
                                                'type'           => 'banking',
                                                'account_type'   => 'shared',
                                                'account_number' => 'ABC123PQR',
                                                'merchant_id'    => '10000000000000',
                                                'balance'        => 280000
                                            ]);

        $balance = $this->getDbLastEntity('balance', 'live');

        // Need to create a Banking Account since we send this data to ledger in ledger calls
        $bankingAccountAttributes = [
            'id'                    =>  'ABCde1234ABCde',
            'account_number'        =>  '2224440041626905',
            'balance_id'            =>  $balance['id'],
            'account_type'          =>  'nodal',
        ];

        $this->createBankingAccount($bankingAccountAttributes, 'live');

        $this->ba->adminAuth('live');

        $this->testData[__FUNCTION__] = $this->testData['testLedgerSnsForNegativeAdjustmentCreationOnLiveMode'];

        $this->startTest();

        $adjustmentsCreated = $this->getDbEntities('adjustment', [], 'live');

        $countOfAdjustmentsAfterTest = count($adjustmentsCreated);

        $newAdjustments = $this->getDbLastEntity('adjustment', 'live');

        $this->assertEquals(Status::PROCESSED, $newAdjustments['status']);

        $this->assertEquals($countOfAdjustmentsAfterTest, $countOfAdjustmentsBeforeTest+1);

        for ($index = 0; $index<count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['identifiers'] = json_decode($ledgerRequestPayload['identifiers'], true);
            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('live', $ledgerRequestPayload['mode']);
            $this->assertEquals($adjustmentsCreated[$index]->getPublicId(), $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals('0', $ledgerRequestPayload['commission']);
            $this->assertEquals('0', $ledgerRequestPayload['tax']);
            $this->assertEquals('negative_adjustment_processed', $ledgerRequestPayload['transactor_event']);
            $this->assertArrayNotHasKey('fee_accounting', $ledgerRequestPayload['additional_params']);
            $this->assertArrayNotHasKey('fts_fund_account_id', $ledgerRequestPayload['identifiers']);
            $this->assertArrayNotHasKey('fts_account_type', $ledgerRequestPayload['identifiers']);
        }
    }

    public function testForNegativeAdjustmentCreationOnLiveModeWhenLedgerReverseShadowEnabled()
    {
        $this->app['config']->set('applications.ledger.enabled', false);

        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $countOfAdjustmentsBeforeTest = count($this->getDbEntities('adjustment', [], 'live'));

        $this->fixtures->on('live')->create('balance',
            [
                'type'           => 'banking',
                'account_type'   => 'shared',
                'account_number' => 'ABC123PQR',
                'merchant_id'    => '10000000000000',
                'balance'        => 280000
            ]);

        $balance = $this->getDbLastEntity('balance', 'live');

        // Need to create a Banking Account since we send this data to ledger in ledger calls
        $bankingAccountAttributes = [
            'id'                    =>  'ABCde1234ABCde',
            'account_number'        =>  '2224440041626905',
            'balance_id'            =>  $balance['id'],
            'account_type'          =>  'nodal',
        ];

        $this->createBankingAccount($bankingAccountAttributes, 'live');

        $this->ba->adminAuth('live');

        $this->testData[__FUNCTION__] = $this->testData['testLedgerSnsForNegativeAdjustmentCreationOnLiveMode'];

        $this->startTest();

        $adjustmentsCreated = $this->getDbEntities('adjustment', [], 'live');

        $countOfAdjustmentsAfterTest = count($adjustmentsCreated);

        $this->assertEquals($countOfAdjustmentsAfterTest, $countOfAdjustmentsBeforeTest+1);

        $newAdjustments = $this->getDbLastEntity('adjustment', 'live');

        $this->assertEquals(Status::PROCESSED, $newAdjustments['status']);

        $this->assertNull($newAdjustments['transaction_id']);
    }

    public function testForNegativeAdjustmentCreationOnLiveModeWhenLedgerReverseShadowSyncFailureAndAsyncSuccess()
    {
        $this->app['config']->set('applications.ledger.enabled', true);

        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        // forcing async retry after all sync retry failures
        $mockLedger->shouldReceive('createJournal')
            ->times(4)
            ->andThrow(new \WpOrg\Requests\Exception(
                'Unexpected response code received from Ledger service.',
                null,
                [
                    'status_code'   => 500,
                    'response_body' => [
                        'code' => 'invalid_argument',
                        'msg' => 'unknown',
                    ],
                ]
            ));

        $mockLedger->shouldReceive('fetchByTransactor')
            ->times(1)
            ->andReturn([
                "body" => [
                    "id"                => "sampleJournlID",
                    "created_at"        => "1623848289",
                    "updated_at"        => "1632368730",
                    "amount"            => "130.000000",
                    "base_amount"       => "130.000000",
                    "currency"          => "INR",
                    "tenant"            => "X",
                    "transactor_id"     => "adj_IwHCToefEWVgph",
                    "transactor_event"  => "negative_adjustment_processed",
                    "transaction_date"  => "1611132045",
                    "ledger_entry" => [
                        [
                            "id"          => "HNjsypHNXdSiei",
                            "created_at"  => "1623848289",
                            "updated_at"  => "1623848289",
                            "merchant_id" => "HN59oOIDACOXt3",
                            "journal_id"  => "sampleJournlID",
                            "account_id"  => "GoRNyEuu9Hl0OZ",
                            "amount"      => "130.000000",
                            "base_amount" => "130.000000",
                            "type"        => "credit",
                            "currency"    => "INR",
                            "balance"     => ""
                        ],
                        [
                            "id"          => "HNjsypHPOUlxDR",
                            "created_at"  => "1623848289",
                            "updated_at"  => "1623848289",
                            "merchant_id" => "HN59oOIDACOXt3",
                            "journal_id"  => "sampleJournlID",
                            "account_id"  => "HN5AGgmKu0ki13",
                            "amount"      => "130.000000",
                            "base_amount" => "130.000000",
                            "type"        => "debit",
                            "currency"    => "INR",
                            "balance"     => "",
                            'account_entities' => [
                                'account_type'       => ['payable'],
                                'fund_account_type'  => ['merchant_va'],
                            ],
                        ]
                    ]
                ]
            ]);

        $countOfAdjustmentsBeforeTest = count($this->getDbEntities('adjustment', [], 'live'));

        $this->fixtures->on('live')->create('balance',
            [
                'type'           => 'banking',
                'account_type'   => 'shared',
                'account_number' => 'ABC123PQR',
                'merchant_id'    => '10000000000000',
                'balance'        => 280000
            ]);

        $balance = $this->getDbLastEntity('balance', 'live');

        // Need to create a Banking Account since we send this data to ledger in ledger calls
        $bankingAccountAttributes = [
            'id'                    =>  'ABCde1234ABCde',
            'account_number'        =>  '2224440041626905',
            'balance_id'            =>  $balance['id'],
            'account_type'          =>  'nodal',
        ];

        $this->createBankingAccount($bankingAccountAttributes, 'live');

        $this->ba->adminAuth('live');

        $this->testData[__FUNCTION__] = $this->testData['testLedgerSnsForNegativeAdjustmentCreationOnLiveMode'];

        $this->startTest();

        $adjustmentsCreated = $this->getDbEntities('adjustment', [], 'live');

        $countOfAdjustmentsAfterTest = count($adjustmentsCreated);

        $this->assertEquals($countOfAdjustmentsAfterTest, $countOfAdjustmentsBeforeTest+1);

        $newAdjustments = $this->getDbLastEntity('adjustment', 'live');
        $newAdjustmentsTxn = $this->getDbLastEntity('transaction', 'live');

        // assert api adjustment
        $this->assertEquals(Status::PROCESSED, $newAdjustments['status']);
        $this->assertEquals(-250000, $newAdjustments['amount']);

    }

    public function testForNegativeAdjustmentCreationOnLiveModeWhenLedgerReverseShadowSyncAsyncFailure()
    {
        $this->app['config']->set('applications.ledger.enabled', true);

        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        // forcing async retry after all sync retry failures
        $mockLedger->shouldReceive('createJournal')
            ->times(4)
            ->andThrow(new \WpOrg\Requests\Exception(
                'Unexpected response code received from Ledger service.',
                null,
                [
                    'status_code'   => 500,
                    'response_body' => [
                        'code' => 'invalid_argument',
                        'msg' => 'unknown',
                    ],
                ]
            ));

        $mockLedger->shouldReceive('fetchByTransactor')
            ->times(1)
            ->andThrow(new \WpOrg\Requests\Exception(
                'Unexpected response code received from Ledger service.',
                null,
                [
                    'status_code'   => 500,
                    'response_body' => [
                        'code' => 'invalid_argument',
                        'msg' => 'unknown',
                    ],
                ]
            ));

        $countOfAdjustmentsBeforeTest = count($this->getDbEntities('adjustment', [], 'live'));

        $this->fixtures->on('live')->create('balance',
            [
                'type'           => 'banking',
                'account_type'   => 'shared',
                'account_number' => 'ABC123PQR',
                'merchant_id'    => '10000000000000',
                'balance'        => 280000
            ]);

        $balance = $this->getDbLastEntity('balance', 'live');

        // Need to create a Banking Account since we send this data to ledger in ledger calls
        $bankingAccountAttributes = [
            'id'                    =>  'ABCde1234ABCde',
            'account_number'        =>  '2224440041626905',
            'balance_id'            =>  $balance['id'],
            'account_type'          =>  'nodal',
        ];

        $this->createBankingAccount($bankingAccountAttributes, 'live');

        $this->ba->adminAuth('live');

        $this->testData[__FUNCTION__] = $this->testData['testLedgerSnsForNegativeAdjustmentCreationOnLiveMode'];

        $this->startTest();

        $adjustmentsCreated = $this->getDbEntities('adjustment', [], 'live');

        $countOfAdjustmentsAfterTest = count($adjustmentsCreated);

        $this->assertEquals($countOfAdjustmentsAfterTest, $countOfAdjustmentsBeforeTest+1);

        $newAdjustments = $this->getDbLastEntity('adjustment', 'live');

        // assert api adjustment
        $this->assertEquals(Status::CREATED, $newAdjustments['status']);
        $this->assertNull($newAdjustments['transaction_id']);
        $this->assertEquals(-250000, $newAdjustments['amount']);
    }

    public function testForNegativeAdjustmentCreationOnLiveModeWhenLedgerReverseShadowStatusCheckNoRecord()
    {
        $this->app['config']->set('applications.ledger.enabled', true);

        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        // forcing async retry after all sync retry failures
        $mockLedger->shouldReceive('createJournal')
            ->times(4)
            ->andThrow(new \WpOrg\Requests\Exception(
                'Unexpected response code received from Ledger service.',
                null,
                [
                    'status_code'   => 500,
                    'response_body' => [
                        'code' => 'invalid_argument',
                        'msg' => 'unknown',
                    ],
                ]
            ));

        $mockLedger->shouldReceive('fetchByTransactor')
            ->times(1)
            ->andThrow(new \RZP\Exception\RuntimeException(
                'Unexpected response code received from Ledger service.',
                [
                    'status_code'   => 500,
                    'response_body' => [
                        'code' => 'invalid_argument',
                        'msg' => 'record_not_found',
                    ],
                ]
            ));

        $countOfAdjustmentsBeforeTest = count($this->getDbEntities('adjustment', [], 'live'));

        $this->fixtures->on('live')->create('balance',
            [
                'type'           => 'banking',
                'account_type'   => 'shared',
                'account_number' => 'ABC123PQR',
                'merchant_id'    => '10000000000000',
                'balance'        => 280000
            ]);

        $balance = $this->getDbLastEntity('balance', 'live');

        // Need to create a Banking Account since we send this data to ledger in ledger calls
        $bankingAccountAttributes = [
            'id'                    =>  'ABCde1234ABCde',
            'account_number'        =>  '2224440041626905',
            'balance_id'            =>  $balance['id'],
            'account_type'          =>  'nodal',
        ];

        $this->createBankingAccount($bankingAccountAttributes, 'live');

        $this->ba->adminAuth('live');

        $this->testData[__FUNCTION__] = $this->testData['testLedgerSnsForNegativeAdjustmentCreationOnLiveMode'];

        $this->startTest();

        $adjustmentsCreated = $this->getDbEntities('adjustment', [], 'live');

        $countOfAdjustmentsAfterTest = count($adjustmentsCreated);

        $this->assertEquals($countOfAdjustmentsAfterTest, $countOfAdjustmentsBeforeTest+1);

        $newAdjustments = $this->getDbLastEntity('adjustment', 'live');

        // assert api adjustment
        $this->assertEquals(Status::FAILED, $newAdjustments['status']);
        $this->assertNull($newAdjustments['transaction_id']);
        $this->assertEquals(-250000, $newAdjustments['amount']);
    }

    public function testAddAdjustmentOnCapitalBalance()
    {
        Mail::fake();

        $balancefixture = $this->fixtures->create('balance', [
            BalanceEntity::MERCHANT_ID => '100abc000abc00',
            BalanceEntity::TYPE        => Type::PRINCIPAL,
            BalanceEntity::BALANCE     => 100000,
        ]);

        $this->testData[__FUNCTION__]['request']['content']['balance_id'] = $balancefixture['id'];

        $this->ba->adminAuth();

        $response = $this->startTest();

        $adjId = $response['id'];

        $txnId = $response['transaction_id'];

        $adjustment = $this->getDbEntityById('adjustment', $adjId);

        $balanceId = $adjustment['balance_id'];

        $balance = $this->getDbEntityById('balance', $balanceId);

        $transaction = $this->getDbEntityById('transaction', $txnId);

        $this->assertNotNull($adjustment, 'adjustment should not be null');

        $this->assertNotNull($balance, 'balance should not be null');

        $this->assertNotNull($transaction, 'transaction should not be null');

        $this->assertEquals($txnId, $adjustment['transaction_id']);
        $this->assertEquals('100abc000abc00', $adjustment['merchant_id']);
        $this->assertEquals(500000, $adjustment['amount']);
        $this->assertEquals(Status::PROCESSED, $adjustment['status']);

        $this->assertEquals(Type::PRINCIPAL, $balance['type']);
        $this->assertEquals('100abc000abc00', $balance['merchant_id']);
        $this->assertEquals(600000, $balance['balance']);

        $this->assertEquals('adjustment', $transaction['type']);
        $this->assertEquals('100abc000abc00', $transaction['merchant_id']);
        $this->assertEquals(500000, $transaction['amount']);
        $this->assertEquals($balanceId, $transaction['balance_id']);

        $this->assertNotNull($transaction['posted_at']);
    }

    public function testAdjustmentBetweenSubBalance()
    {
        Mail::fake();

        $this->fixtures->create(
            'balance',
            [
                'id'           => 'xbalancesource',
                'balance'      => 10000,
                'type'         => 'banking',
                'account_type' => 'shared',
                'merchant_id'  => '10000000000000'
            ]
        );

        $this->fixtures->create(
            'balance',
            [
                'id'           => 'xbalancedestin',
                'balance'      => 10000,
                'type'         => 'banking',
                'account_type' => 'shared',
                'merchant_id'  => '10000000000000'
            ]
        );

        $this->ba->adminAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/sub_balance/adjustment',
            'content'   => [
                "amount"                 => "1000",
                "type"                   => "banking",
                "currency"               => "INR",
                "description"            => "kurama is dead",
                "merchant_id"            => "10000000000000",
                "source_balance_id"      => "xbalancesource",
                "destination_balance_id" => "xbalancedestin"
            ],
        ];

        $response = $this->makeRequestAndGetContent($request);

        /** @var AdjustmentEntity $sourceAdjustment */
        $sourceAdjustment = $this->getDbEntityById('adjustment', $response['source_adjustment']['id']);
        $destinationAdjustment = $this->getDbEntityById('adjustment', $response['destination_adjustment']['id']);

        /** @var BalanceEntity $sourceBalance */
        $sourceBalance = $this->getDbEntityById('balance', 'xbalancesource');

        /** @var BalanceEntity $destinationBalance */
        $destinationBalance = $this->getDbEntityById('balance', 'xbalancedestin');

        $this->assertEquals(9000, $sourceBalance->getBalance());
        $this->assertEquals(11000, $destinationBalance->getBalance());

        $this->assertEquals(-1000, $sourceAdjustment->getAmount());
        $this->assertEquals(1000, $destinationAdjustment->getAmount());
        $this->assertEquals(Status::PROCESSED, $sourceAdjustment->getStatus());
        $this->assertEquals(Status::PROCESSED, $destinationAdjustment->getStatus());

        $this->assertEquals($sourceAdjustment->transaction->getDebit(), $destinationAdjustment->transaction->getCredit());
        $this->assertEquals(0, $sourceAdjustment->transaction->getCredit());
        $this->assertEquals(0, $destinationAdjustment->transaction->getDebit());

        $this->assertEquals('xbalancesource' ,$sourceAdjustment->getBalanceId());
        $this->assertEquals('xbalancedestin' ,$destinationAdjustment->getBalanceId());
        $this->assertEquals('xbalancesource', $sourceAdjustment->transaction->getBalanceId());
        $this->assertEquals('xbalancedestin', $destinationAdjustment->transaction->getBalanceId());
    }
}
