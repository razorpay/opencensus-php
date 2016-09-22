<?php

namespace RZP\Tests\Functional\Payout;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class PayoutTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/PayoutTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->fixtures->merchant->editFeatures('payout');
    }

    public function testCreatePayout()
    {
        $this->ba->privateAuth();

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertEquals('txn_' . $payout['transaction_id'], $txn['id']);
    }

    public function testCreatePayoutInsufficientBalance()
    {
        return $this->startTest();
    }

    public function testGetPayouts()
    {
        $payout = $this->testCreatePayout();

        $payout = $this->testCreatePayout();

        $this->ba->privateAuth();

        $payouts = $this->startTest();

        $this->assertEquals($payouts['entity'], 'collection');

        $this->assertEquals($payouts['count'], 2);

        $this->assertNotEquals($payouts['items'], null);
    }

    public function testGetPayout()
    {
        $this->testCreatePayout();

        $payout = $this->getLastEntity('payout', false);

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['url'] = '/payouts/'. $payout['id'];

        $payout2 = $this->startTest();

        $this->assertEquals($payout, $payout2);
    }
}