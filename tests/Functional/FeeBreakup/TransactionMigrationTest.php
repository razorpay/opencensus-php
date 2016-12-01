<?php

namespace RZP\Tests\Functional\FeeBreakup;

use DB;
use Mockery;
use Carbon\Carbon;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Transaction\FeeBreakup as FeeBreakup;
use RZP\Models\Transaction\FeeBreakup\Name as FeeBreakupName;

class TransactionMigrationTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/TransactionMigrationTestData.php';

        parent::setUp();
    }

    public function testMigration()
    {
        $payment = $this->fixtures->create('payment:captured');

        $transaction = $payment->transaction;

        $this->ba->appAuth();

        $testData = $this->testData[__FUNCTION__];

        $content = $this->makeRequestAndGetContent($testData['request']);

        $this->assertEquals($content['migrated'][0], $transaction->getPublicId());

        $this->assertEquals($content['migrated'][0], $transaction->getPublicId());
    }

    // Captured At: 31st May 2015 ST = 12.36
    public function testMigrationWithST1()
    {
        $payment = $this->fixtures->create('payment:captured');

        $transaction = $payment->transaction;

        $this->fixtures->base->editEntity('payment', $payment['id'], ['captured_at' => 1433096000]);

        $this->fixtures->base->editEntity('payment', $payment['id'], ['authorized_at' => 1433096000]);

        $this->fixtures->base->editEntity('transaction', $transaction['id'], ['fee' => 22472]);

        $this->fixtures->base->editEntity('transaction', $transaction['id'], ['service_tax' => 2472]);

        $transaction = $this->getLastEntity('transaction', true);

        $this->ba->appAuth();

        $testData = $this->testData[__FUNCTION__];

        $content = $this->makeRequestAndGetContent($testData['request']);

        $this->assertEquals($content['migrated'][0], $transaction['id']);
    }

    // Captured At: 1st June 2015 ST = 14
    public function testMigrationWithST2()
    {
        $payment = $this->fixtures->create('payment:captured');

        $transaction = $payment->transaction;

        $this->fixtures->base->editEntity('payment', $payment['id'], ['captured_at' => 1433099000]);

        $this->fixtures->base->editEntity('payment', $payment['id'], ['authorized_at' => 1433099000]);

        $this->fixtures->base->editEntity('transaction', $transaction['id'], ['fee' => 22800]);

        $this->fixtures->base->editEntity('transaction', $transaction['id'], ['service_tax' => 2800]);

        $transaction = $this->getLastEntity('transaction', true);

        $this->ba->appAuth();

        $testData = $this->testData[__FUNCTION__];

        $content = $this->makeRequestAndGetContent($testData['request']);

        $this->assertEquals($content['migrated'][0], $transaction['id']);
    }

    // Captured At: 15th Nov 2015 ST = 14, SB = 0.5
    public function testMigrationWithSTSB()
    {
        $payment = $this->fixtures->create('payment:captured');

        $transaction = $payment->transaction;

        $this->fixtures->base->editEntity('payment', $payment['id'], ['captured_at' => 1447525900]);

        $this->fixtures->base->editEntity('payment', $payment['id'], ['authorized_at' => 1447525900]);

        $this->fixtures->base->editEntity('transaction', $transaction['id'], ['fee' => 22900]);

        $this->fixtures->base->editEntity('transaction', $transaction['id'], ['service_tax' => 2900]);

        $transaction = $this->getLastEntity('transaction', true);

        $this->ba->appAuth();

        $testData = $this->testData[__FUNCTION__];

        $content = $this->makeRequestAndGetContent($testData['request']);

        $this->assertEquals($content['migrated'][0], $transaction['id']);
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

        $content = $this->makeRequestAndGetContent($testData['request']);

        $this->assertEquals($content['migrated'][0], $transaction['id']);
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

        $this->startTest();
    }
}
