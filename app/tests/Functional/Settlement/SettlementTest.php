<?php

namespace Tests\Functional\Settlement;

use Tests\Functional\TestCase;

class SettlementTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->ba->publicAuth();
    }

    public function testDummy()
    {
        // apparently you need to have a test per test file!
    }

    public function testSettlement()
    {
        $pricing = $this->fixtures->create('pricing:standard_plan');

        $merchants = $this->fixtures->times(3)->create('merchant:with_balance_terminals_standard_pricing');

        $merchantPayments = [];

        $i = 0;

        foreach ($merchants as $merchant)
        {
            $payments = $this->fixtures->times(2)->create(
                'payment:captured',
                ['merchant_id' => $merchant->getId(),
                 'amount' => '10000']);

            foreach ($payments as $payment)
            {
                $txn = $payment->transaction;

                $this->assertEquals(2248, $txn->fee);
                $this->assertEquals(7752, $txn->credit);
                $this->assertEquals(0, $txn->debit);
            }

            $this->assertEquals(15504, $merchant->balance->getBalance());

            $merchantPayments[] = $payments;
        }

        $attrs = ['payment' => $merchantPayments[0][0]];

        $refund = $this->fixtures->create('refund:from_payment', $attrs);

        $this->assertEquals(10000, $refund->transaction->debit);

        $this->assertEquals(5504, $merchants[0]->balance->reload()->getBalance());
        $this->assertEquals(5504, $merchants[0]->balance->reload()->getBalance());
    }

    protected function startTest($testDataToReplace = array())
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        $this->replaceValuesRecursively($testData, $testDataToReplace);

        return $this->runRequestResponseFlow($testData);
    }
}