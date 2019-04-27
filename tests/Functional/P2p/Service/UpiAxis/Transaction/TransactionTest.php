<?php

namespace RZP\Tests\P2p\Service\UpiAxis\Transaction;

use RZP\Tests\P2p\Service\UpiAxis\TestCase;

class TransactionTest extends TestCase
{
    public function testInitiatePay()
    {
        $helper = $this->getTransactionHelper();

        $helper->withSchemaValidated();

        $coproto = $helper->initiatePay();

        $transaction = $this->fixtures->getDbLastTransaction();

        $this->assertSame($this->fixtures->vpa(self::DEVICE_1)->getId(), $transaction->payer->getId());
        $this->assertSame($this->fixtures->vpa(self::DEVICE_2)->getId(), $transaction->payee->getId());
    }

    public function testInitiateCollect()
    {
        $helper = $this->getTransactionHelper();

        $helper->withSchemaValidated();

        $coproto = $helper->initiateCollect();

        $transaction = $this->fixtures->getDbLastTransaction();

        $this->assertSame($this->fixtures->vpa(self::DEVICE_1)->getId(), $transaction->payee->getId());
        $this->assertSame($this->fixtures->vpa(self::DEVICE_2)->getId(), $transaction->payer->getId());
    }

    public function testPayAuthorize()
    {
        $helper = $this->getTransactionHelper();

        $coproto = $helper->initiatePay();

        $content = $this->handleSdkRequest($coproto);

        $helper->withSchemaValidated();

        $response = $helper->authorizeTransaction($coproto['callback'], $content);

        $transaction = $this->fixtures->getDbLastTransaction();

        $this->assertTrue($transaction->isCompleted());
    }

    public function testCollectAuthorize()
    {
        $helper = $this->getTransactionHelper();

        $coproto = $helper->initiateCollect();

        $content = $this->handleSdkRequest($coproto);

        $helper->withSchemaValidated();

        $response = $helper->authorizeTransaction($coproto['callback'], $content);

        $transaction = $this->fixtures->getDbLastTransaction();

        $this->assertTrue($transaction->isProcessing());
    }
}
