<?php

namespace RZP\Tests\P2p\Service\Transaction;

use RZP\Tests\P2p\Service\TestCase;
use RZP\Tests\P2p\Service\Base\Fixtures\Fixtures;

class TransactionTest extends TestCase
{
    public function testInitiatePay()
    {
        $helper = $this->getTransactionHelper();

        $helper->withSchemaValidated();

        $coproto = $helper->initiatePay();

        $transaction = $this->fixtures->getDbLastTransaction();

        $this->assertSame(Fixtures::CUSTOMER_1_VPA_1, $transaction->payer->getId());
        $this->assertSame(Fixtures::CUSTOMER_2_VPA_1, $transaction->payee->getId());
    }

    public function testInitiateCollect()
    {
        $helper = $this->getTransactionHelper();

        $helper->withSchemaValidated();

        $response = $helper->initiateCollect();

        $transaction = $this->fixtures->getDbLastTransaction();

        $this->assertSame(Fixtures::CUSTOMER_2_VPA_1, $transaction->payer->getId());
        $this->assertSame(Fixtures::CUSTOMER_1_VPA_1, $transaction->payee->getId());
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

        $helper->initiatePay();

        $transaction = $this->fixtures->getDbLastTransaction();

        $helper->withSchemaValidated();

        $response = $helper->authorizeTransaction($transaction->getPublicId());
    }

    public function testRejectTransaction()
    {
        $helper = $this->getTransactionHelper();

        $helper->withSchemaValidated();

        $response = $helper->initiateCollect();

        $transaction = $this->fixtures->getDbLastTransaction();

        $this->fixtures->switchDevice(Fixtures::DEVICE_2);

        $response = $helper->rejectTransaction($transaction->getPublicId());
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
