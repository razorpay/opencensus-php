<?php

namespace RZP\Tests\P2p\Service\UpiSharp\Transaction;

use RZP\Tests\P2p\Service\UpiSharp\TestCase;

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

        $helper->initiateCollect();

        $transaction = $this->fixtures->getDbLastTransaction();

        $this->fixtures->switchDeviceSet(self::DEVICE_2);

        $helper->withSchemaValidated();

        $helper->initiateReject($transaction->getPublicId());
    }

    public function testRejectTransaction()
    {
        $helper = $this->getTransactionHelper();

        $helper->withSchemaValidated();

        $helper->initiateCollect();

        $transaction = $this->fixtures->getDbLastTransaction();

        $this->fixtures->switchDeviceSet(self::DEVICE_2);

        $request = $helper->initiateReject($transaction->getPublicId());

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
