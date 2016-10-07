<?php

namespace RZP\Tests\Functional\FeeBreakup;

use DB;
use Mockery;
use Carbon\Carbon;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Pricing\FeeBreakup as FeeBreakup;
use RZP\Models\Pricing\FeeBreakup\Type as FeeBreakupType;
use RZP\Models\Pricing\FeeBreakup\Name as FeeBreakupName;


class TransactionMigrationTest extends TestCase
{
    use PaymentTrait;

    protected $month = null;
    protected $year = null;
    protected $url = null;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/TransactionMigrationTestData.php';

        parent::setUp();

        $now = Carbon::now();
        $this->month = $now->month;
        $this->year = $now->year;

        $this->url = '/transactions/migrate?' .'month=' .$this->month .'&year=' .$this->year;
    }

    public function testMigration()
    {
        $payment = $this->fixtures->create('payment:captured');

        $transaction = $payment->transaction;

        $this->ba->appAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = $this->url;

        $content = $this->makeRequestAndGetContent($testData['request']);

        $this->assertContent($content, $transaction->getPublicId(), $transaction->getFee(), $transaction->getServiceTax());
    }

    // Captured At: 31st May 2015 ST = 12.36
    public function testMigrationWithST1()
    {
        $payment = $this->fixtures->create('payment:captured');

        $transaction = $payment->transaction;

        $this->fixtures->base->editEntity('payment', $payment['id'], ['captured_at' => 1433096000]);

        $this->fixtures->base->editEntity('transaction', $transaction['id'], ['fee' => 22472]);

        $this->fixtures->base->editEntity('transaction', $transaction['id'], ['service_tax' => 2472]);

        $transaction = $this->getLastEntity('transaction', true);

        $this->ba->appAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = $this->url;

        $content = $this->makeRequestAndGetContent($testData['request']);

        $this->assertContent($content, $transaction['id'], $transaction['fee'], $transaction['service_tax']);
    }

    // Captured At: 1st June 2015 ST = 14
    public function testMigrationWithST2()
    {
        $payment = $this->fixtures->create('payment:captured');

        $transaction = $payment->transaction;

        $this->fixtures->base->editEntity('payment', $payment['id'], ['captured_at' => 1433099000]);

        $this->fixtures->base->editEntity('transaction', $transaction['id'], ['fee' => 22800]);

        $this->fixtures->base->editEntity('transaction', $transaction['id'], ['service_tax' => 2800]);

        $transaction = $this->getLastEntity('transaction', true);

        $this->ba->appAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = $this->url;

        $content = $this->makeRequestAndGetContent($testData['request']);

        $this->assertContent($content, $transaction['id'], $transaction['fee'], $transaction['service_tax']);
    }

    // Captured At: 15th Nov 2015 ST = 14, SB = 0.5
    public function testMigrationWithSTSB()
    {
        $payment = $this->fixtures->create('payment:captured');

        $transaction = $payment->transaction;

        $this->fixtures->base->editEntity('payment', $payment['id'], ['captured_at' => 1447525900]);

        $this->fixtures->base->editEntity('transaction', $transaction['id'], ['fee' => 22900]);

        $this->fixtures->base->editEntity('transaction', $transaction['id'], ['service_tax' => 2900]);

        $transaction = $this->getLastEntity('transaction', true);

        $this->ba->appAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = $this->url;

        $content = $this->makeRequestAndGetContent($testData['request']);

        $this->assertContent($content, $transaction['id'], $transaction['fee'], $transaction['service_tax']);
    }

    // Captured At: 1st June 2016 ST = 14, SB = 0.5, KK = 0.5
    public function testMigrationWithSTSBKK()
    {
        $payment = $this->fixtures->create('payment:captured');

        $transaction = $payment->transaction;

        $this->fixtures->base->editEntity('payment', $payment['id'], ['captured_at' => 1464719500]);

        $this->fixtures->base->editEntity('transaction', $transaction['id'], ['fee' => 23000]);

        $this->fixtures->base->editEntity('transaction', $transaction['id'], ['service_tax' => 3000]);

        $transaction = $this->getLastEntity('transaction', true);

        $this->ba->appAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = $this->url;

        $content = $this->makeRequestAndGetContent($testData['request']);

        $this->assertContent($content, $transaction['id'], $transaction['fee'], $transaction['service_tax']);
    }

    public function testMigrationWithTaxMistmatch()
    {
        $payment = $this->fixtures->create('payment:captured');

        $transaction = $payment->transaction;

        $this->fixtures->base->editEntity('transaction', $transaction['id'], ['fee' => 22000]);

        $this->fixtures->base->editEntity('transaction', $transaction['id'], ['service_tax' => 2000]);

        $transaction = $this->getLastEntity('transaction', true);

        $this->ba->appAuth();

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = $this->url;

        $this->startTest();
    }

    public function testMigrationWithFeeMistmatch()
    {
        $payment = $this->fixtures->create('payment:captured');

        $transaction = $payment->transaction;

        $this->fixtures->base->editEntity('transaction', $transaction['id'], ['fee' => 22000]);

        $this->fixtures->base->editEntity('transaction', $transaction['id'], ['service_tax' => 3000]);

        $transaction = $this->getLastEntity('transaction', true);

        $this->ba->appAuth();

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = $this->url;

        $this->startTest();
    }

    protected function assertContent($content, $txnId, $fee, $serviceTax)
    {
        $feesCollection = $content[$txnId];

        $feesSplit = $feesCollection['items'];

        list($rzpFee, $taxes) = $this->getFeesSplit($feesSplit);

        $this->assertEquals($rzpFee + $taxes, $fee);

        $this->assertEquals($taxes, $serviceTax);
    }

    protected function getFeesSplit($feesSplit)
    {
        $rzpFee = 0;
        $taxes = 0;

        foreach ($feesSplit as $feeSplit)
        {
            if ($feeSplit['name'] === FeeBreakupName::RZP)
            {
                $rzpFee += $feeSplit['amount'];
            }
            else
            {
                $taxes += $feeSplit['amount'];
            }
        }
        return [$rzpFee, $taxes];
    }
}
