<?php

namespace RZP\Tests\Functional\Adjustment;

use Mail;

use RZP\Mail\Merchant\NegativeBalanceAlert;
use RZP\Mail\Merchant\NegativeBalanceThresholdAlert;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Banking\YesbankLoadViaAdjustment;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Mail\Merchant\ReserveBalanceActivate as ReserveBalanceActivateMail;

class AdjustmentTest extends TestCase
{
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;

    public function setUp()
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

        $this->assertEquals('primary', $balance['type']);
        $this->assertEquals('100abc000abc00', $balance['merchant_id']);
        $this->assertEquals(501000, $balance['balance']);

        $this->assertEquals('adjustment', $transaction['type']);
        $this->assertEquals('100abc000abc00', $transaction['merchant_id']);
        $this->assertEquals(500000, $transaction['amount']);
        $this->assertEquals($balanceId, $transaction['balance_id']);
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

    public function testSendYesbankLoadSuccessfulEmail()
    {
        Mail::fake();

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn('on');

        $this->fixtures->create('balance',
                                [
                                    'type'           => 'banking',
                                    'account_type'   => 'shared',
                                    'account_number' => 'ABC123PQR',
                                    'merchant_id'    => '100abc000abc00',
                                    'balance'        => 30000
                                ]);

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

        $this->assertEquals('banking', $balance['type']);
        $this->assertEquals('100abc000abc00', $balance['merchant_id']);
        $this->assertEquals(280000, $balance['balance']);

        $this->assertEquals('adjustment', $transaction['type']);
        $this->assertEquals('100abc000abc00', $transaction['merchant_id']);
        $this->assertEquals(250000, $transaction['amount']);
        $this->assertEquals($balanceId, $transaction['balance_id']);

        Mail::assertQueued(YesbankLoadViaAdjustment::class, function($mail)
        {
            $viewData = $mail->viewData;

            $this->assertEquals('250000', $viewData['amount']); // raw amount
            $this->assertEquals('2,500.00', amount_format_IN($viewData['amount'])); // formatted amount

            $expectedData = [
                'adjustment_description' => 'Account: ABC123, Bank: ICICI',
                'account_number'         => 'ABC123PQR',
            ];

            $this->assertArraySelectiveEquals($expectedData, $viewData);

            $this->assertEquals('emails.banking.yesbank_load_via_adjustment', $mail->view);

            return true;
        });
    }

    public function testSendYesbankLoadSuccessfulEmailRazorxControl()
    {
        Mail::fake();

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn('control');

        $this->fixtures->create('balance',
            [
                'type'           => 'banking',
                'account_type'   => 'shared',
                'account_number' => 'ABC123PQR',
                'merchant_id'    => '100abc000abc00',
                'balance'        => 30000
            ]);

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

        $this->assertEquals('banking', $balance['type']);
        $this->assertEquals('100abc000abc00', $balance['merchant_id']);
        $this->assertEquals(280000, $balance['balance']);

        $this->assertEquals('adjustment', $transaction['type']);
        $this->assertEquals('100abc000abc00', $transaction['merchant_id']);
        $this->assertEquals(250000, $transaction['amount']);
        $this->assertEquals($balanceId, $transaction['balance_id']);

        Mail::assertQueued(YesbankLoadViaAdjustment::class, function($mail)
        {
            $viewData = $mail->viewData;

            $this->assertEquals('250000', $viewData['amount']); // raw amount
            $this->assertEquals('2,500.00', amount_format_IN($viewData['amount'])); // formatted amount

            $expectedData = [
                'adjustment_description' => 'Account: ABC123, Bank: ICICI',
                'account_number'         => 'ABC123PQR',
            ];

            $this->assertArraySelectiveEquals($expectedData, $viewData);

            $this->assertEquals('emails.banking.yesbank_load_via_adjustment', $mail->view);

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

    public function testAddReserveBalanceInvalidMaxLimit()
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
                'balance'       => 4500000,
                'type'          => 'reserve_primary',
                'merchant_id'   => '100xyz000xyz00'
            ]
        );

        $this->startTest();
    }

    public function testAddReserveBalanceInvalidMaxLimitRazorxControl()
    {
        Mail::fake();

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn('control');

        $this->createFixtures('100xyz000xyz00');

        $this->fixtures->create(
            'balance',
            [
                'id'            => '100def000def00',
                'balance'       => 4500000,
                'type'          => 'reserve_primary',
                'merchant_id'   => '100xyz000xyz00'
            ]
        );

        $this->startTest();
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

        $this->startTest();

        $balance = $this->getDbEntity('balance', ['id' => '100def000def00']);

        $this->assertEquals(-4000, $balance['balance']);

        Mail::assertQueued(NegativeBalanceThresholdAlert::class);
    }

    public function testCreateNegativeAdjustmentWithLowBalanceRazorxControl()
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
            ->willReturn('control');

        $this->startTest();

        $balance = $this->getDbEntity('balance', ['id' => '100def000def00']);

        $this->assertEquals(1000, $balance['balance']);
    }

    public function testCreateReserveBalanceInvalidAmount()
    {
        $this->startTest();
    }

    public function testCreateAdjustmentFromBatchRoute()
    {
        $this->fixtures->create('balance', [
                'id'            => '100def000def00',
                'balance'       => 10000,
                'type'          => 'primary',
                'merchant_id'   => '100abc000abc00'
            ]);

        $this->ba->batchAuth();

        $this->startTest();
    }

    private function createFixtures(string $id = null)
    {
        $merchantId = $id ?? '100abc000abc00';

        $this->fixtures->create('merchant', ['id' => $merchantId, 'email' => 'mahbubani.amit@gmail.com']);

//        $this->fixtures->create('balance', ['merchant_id' => $merchantId]);

        $admin = $this->ba->getAdmin();

        $this->fixtures->admin->edit($admin['id'], ['allow_all_merchants' => true]);

        $this->ba->adminProxyAuth($merchantId, 'rzp_test_'.$merchantId);
    }
}
