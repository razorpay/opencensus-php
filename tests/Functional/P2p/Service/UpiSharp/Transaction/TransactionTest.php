<?php

namespace RZP\Tests\P2p\Service\UpiSharp\Transaction;

use RZP\Tests\P2p\Service\UpiSharp\TestCase;
use RZP\Tests\P2p\Service\Base\Traits\TransactionTrait;

class TransactionTest extends TestCase
{
    use TransactionTrait;

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

        $response = $helper->initiateCollect();

        $transaction = $this->fixtures->getDbLastTransaction();

        $this->assertSame($this->fixtures->vpa(self::DEVICE_2)->getId(), $transaction->payer->getId());
        $this->assertSame($this->fixtures->vpa(self::DEVICE_1)->getId(), $transaction->payee->getId());
    }

    public function testInitiateAuthorize()
    {
        $helper = $this->getTransactionHelper();

        $helper->initiatePay();

        $transaction = $this->fixtures->getDbLastTransaction();

        $helper->withSchemaValidated();

        $coproto = $helper->initiateAuthorize($transaction->getPublicId());
    }

    public function testAuthorizeTransaction()
    {
        $helper = $this->getTransactionHelper();

        $request = $helper->initiatePay();

        $helper->withSchemaValidated();

        $response = $helper->authorizeTransaction($request['callback']);
    }

    public function testInitiateRejectTransaction()
    {
        $helper = $this->getTransactionHelper();

        $transaction = $this->createCollectIncomingTransaction();

        $helper->withSchemaValidated();

        $helper->initiateReject($transaction->getPublicId());
    }

    public function testRejectTransaction()
    {
        $helper = $this->getTransactionHelper();

        $transaction = $this->createCollectIncomingTransaction();

        $request = $helper->initiateReject($transaction->getPublicId());

        $helper->withSchemaValidated();

        $response = $helper->rejectTransaction($request['callback']);
    }

    public function testFetchAll()
    {
        $helper = $this->getTransactionHelper();

        $helper->initiatePay();

        $helper->withSchemaValidated();

        $helper->fetchAll();
    }

    public function testFetch()
    {
        $helper = $this->getTransactionHelper();

        $helper->initiatePay();

        $transaction = $this->fixtures->getDbLastTransaction();

        $helper->withSchemaValidated();

        $helper->fetch($transaction->getPublicId());
    }
}
