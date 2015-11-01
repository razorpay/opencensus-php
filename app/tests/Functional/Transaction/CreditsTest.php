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

        $this->fixtures->merchant->addCredits('100000', '10000000000000');
        $this->fixtures->merchant->addCreditsToNodalAccount('100000');
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_billdesk_terminal');
    }

    /**
     * When payment is not authorized on payment network gateway
     */
    public function testCredits()
    {
        $this->doAuthAndCapturePayment();

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals(0, $txn['fee']);

        $balance = $this->getEntityById('balance', '10000000000000', true);
        $this->assertEquals(1050000, $balance['balance']);
        $this->assertEquals(50000, $balance['credits']);

        $nodalBalance = $this->getNodalAccountBalance();
        $this->assertEquals(1050000, $nodalBalance['balance']);
        $this->assertEquals(50000, $nodalBalance['credits']);
    }

    /**
     * When payment authorized on payment network gateway
     */
    public function testCredits2()
    {
        $payment = $this->getDefaultNetbankingPaymentArray();
        $this->doAuthPayment($payment);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals(0, $txn['fee']);

        $balance = $this->getEntityById('balance', '10000000000000', true);
        $this->assertEquals(1000000, $balance['balance']);
        $this->assertEquals(50000, $balance['credits']);

        $nodalBalance = $this->getNodalAccountBalance();
        $this->assertEquals(1050000, $nodalBalance['balance']);
        $this->assertEquals(50000, $nodalBalance['credits']);
    }
}
