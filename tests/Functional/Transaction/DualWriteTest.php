<?php

use  \RZP\Tests\Functional\TestCase;
use \RZP\Models\Transaction\DualWriteCore;

class DualWriteTest extends TestCase
{
    use \RZP\Tests\Functional\RequestResponseFlowTrait;
    public function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/DualWriteTestData.php';

        parent::setUp();
    }

    public function testCustomerTransferWalletAPILedgerPositive()
    {
        $transfer = $this->testData[__FUNCTION__]['transfer'];

        $this->fixtures->create('transfer', $transfer);

        $this->startTest();
    }

    public function testCustomerTransferWalletAPILedgerPostpaid()
    {
        $this->fixtures->edit('merchant', '10000000000000', [
            'fee_model' => 'postpaid'
        ]);

        $transfer = $this->testData[__FUNCTION__]['transfer'];

        $this->fixtures->create('transfer', $transfer);

        $this->startTest();
    }

    public function testCustomerTransferWalletAPILedgerFeeCredits()
    {
        $this->fixtures->create('credits', [
            'type'        => 'fee',
            'value'       => 17,
            'merchant_id' => '10000000000000',
        ]);

        $transfer = $this->testData[__FUNCTION__]['transfer'];

        $this->fixtures->create('transfer', $transfer);

        $this->startTest();
    }

    public function testCustomerTransferWalletAPILedgerFeeCreditsNegative()
    {
        $this->fixtures->create('credits', [
            'type'        => 'fee',
            'value'       => 10,
            'merchant_id' => '10000000000000',
        ]);

        $transfer = $this->testData[__FUNCTION__]['transfer'];

        $this->fixtures->create('transfer', $transfer);

        $this->startTest();
    }

    public function testCustomerTransferWalletAPILedgerAmountCredits()
    {
        $this->fixtures->create('credits', [
            'type'        => 'amount',
            'value'       => 10000,
            'merchant_id' => '10000000000000',
        ]);

        $transfer = $this->testData[__FUNCTION__]['transfer'];

        $this->fixtures->create('transfer', $transfer);

        $this->startTest();
    }

    public function testCustomerTransferWalletAPILedgerAmountCreditsNegative()
    {
        $this->fixtures->create('credits', [
            'type'        => 'amount',
            'value'       => 1000,
            'merchant_id' => '10000000000000',
        ]);

        $transfer = $this->testData[__FUNCTION__]['transfer'];

        $this->fixtures->create('transfer', $transfer);

        $this->startTest();
    }
    public function startTest($testDataToReplace = [])
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $name = $trace[1]['function'];

        $testData = $this->testData[$name]['payload'];

        $this->replaceValuesRecursively($testData, $testDataToReplace);

        try
        {
            $dualWriteCore = new DualWriteCore();

            $reflection = new \ReflectionClass($dualWriteCore);

            $method = $reflection->getMethod('createCustomerWalletAPILedger');

            $actual = $method->invokeArgs($dualWriteCore, [$testData]);
        }
        catch (\RZP\Exception\BaseException $e)
        {
            $this->processAndAssertException($e, $this->testData[$name]['exception']);
        }

        $expected = $this->testData[$name]['expected'];

        if (isset($expected))
        {
            $this->assertArraySelectiveEquals($expected, $actual->toArray());
        }
    }
}
