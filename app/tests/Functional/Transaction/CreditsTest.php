<?php

namespace Tests\Functional\Transaction;

use Carbon\Carbon;
use Tests\Functional\TestCase;
use Tests\Functional\Helpers\Payment\PaymentTrait;

class CreditsTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/TransactionData.php';

        parent::setUp();

        $this->ba->proxyAuth();

        $this->fixtures->merchant->addCredits('1000000', '10000000000000');
    }

    public function testCredits()
    {
        $this->doAuthAndCapturePayment();

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals(0, $txn['fee']);

        $balance = $this->getEntityById('balance', '10000000000000', true);
        $this->assertEquals(1050000, $balance['balance']);
        $this->assertEquals(950000, $balance['credits']);
    }
}
