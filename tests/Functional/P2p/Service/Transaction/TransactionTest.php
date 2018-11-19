<?php

namespace RZP\Tests\P2p\Service\Transaction;

use RZP\Tests\P2p\Service\TestCase;

class TransactionTest extends TestCase
{
    public function testInitiatePay()
    {
        $helper = $this->getTransactionHelper();

        $helper->withSchemaValidated();

        $helper->initiatePay();
    }

    public function testInitiateCollect()
    {
        $helper = $this->getTransactionHelper();

        $helper->withSchemaValidated();

        $helper->initiateCollect();
    }

    public function testInitiateAuthorize()
    {
        $cTxnId = 'ctxn_12345abcde6789';

        $helper = $this->getTransactionHelper();

        $helper->withSchemaValidated();

        $helper->initiateAuthorize($cTxnId);
    }

    public function testAuthorizeTransaction()
    {
        $cTxnId = 'ctxn_12345abcde6789';

        $helper = $this->getTransactionHelper();

        $helper->withSchemaValidated();

        $helper->authorizeTransaction($cTxnId);
    }

    public function testRejectTransaction()
    {
        $cTxnId = 'ctxn_12345abcde6789';

        $helper = $this->getTransactionHelper();

        $helper->withSchemaValidated();

        $helper->rejectTransaction($cTxnId);
    }

    public function testFetchAll()
    {
        $helper = $this->getTransactionHelper();

        $helper->withSchemaValidated();

        $helper->fetchAll();
    }

    public function testFetch()
    {
        $cTxnId = 'ctxn_12345abcde6789';

        $helper = $this->getTransactionHelper();

        $helper->withSchemaValidated();

        $helper->fetch($cTxnId);
    }
}
