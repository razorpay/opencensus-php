<?php

namespace RZP\Tests\Functional\Adjustment;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

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

    public function testCreateReserveBalanceInvalidAmount()
    {
        $this->startTest();
    }

    public function testCreateReservePrimaryBalance()
    {
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

    }

    public function testCreateReserveBankingBalance()
    {
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
    }

    public function testAddReserveBalance()
    {
        $this->createFixtures('100xyz000xyz00');

        $this->fixtures->create(
            'balance',
            [
                'id'            => '100def000def00',
                'balance'       => 5000000,
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
        $this->assertEquals(5000000, $adjustment['amount']);

        $this->assertEquals('100def000def00', $balanceId);
        $this->assertEquals('reserve_primary', $balance['type']);
        $this->assertEquals('100xyz000xyz00', $balance['merchant_id']);
        $this->assertEquals(10000000, $balance['balance']);

        $this->assertEquals('adjustment', $transaction['type']);
        $this->assertEquals('100xyz000xyz00', $transaction['merchant_id']);
        $this->assertEquals(5000000, $transaction['amount']);
        $this->assertEquals($balanceId, $transaction['balance_id']);
    }

    private function createFixtures(string $id = null)
    {
        $merchantId = $id ?? '100abc000abc00';

        $this->fixtures->create('merchant', ['id' => $merchantId]);

        $admin = $this->ba->getAdmin();

        $this->fixtures->admin->edit($admin['id'], ['allow_all_merchants' => true]);

        $this->ba->adminProxyAuth($merchantId, 'rzp_test_'.$merchantId);
    }
}
