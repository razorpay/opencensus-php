<?php

namespace RZP\Tests\P2p\Service\BankAccount;

use RZP\Tests\P2p\Service\TestCase;

class BankAccountTest extends TestCase
{
    public function testFetchBanks()
    {
        $helper = $this->getBankAccountHelper();

        $helper->withSchemaValidated();

        $helper->fetchBanks();
    }

    public function testFetch()
    {
        $helper = $this->getBankAccountHelper();

        $helper->withSchemaValidated();

        $helper->fetch();
    }

    public function testRetrieve()
    {
        $ifsc = 'ACME000001';

        $helper = $this->getBankAccountHelper();

        $helper->withSchemaValidated();

        $helper->retrieve($ifsc);
    }

    public function testSetUpiPin()
    {
        $bankId = 'ba_AtIZbXUOTDp1ND';

        $helper = $this->getBankAccountHelper();

        $helper->withSchemaValidated();

        $helper->setUpiPin($bankId);
    }

    public function testInitiateSetUpiPin()
    {
        $bankId = 'ba_AtIZbXUOTDp1ND';

        $helper = $this->getBankAccountHelper();

        $helper->withSchemaValidated();

        $helper->initiateSetUpiPin($bankId);
    }

    public function testInitiateFetchBalance()
    {
        $bankId = 'ba_AtIZbXUOTDp1ND';

        $helper = $this->getBankAccountHelper();

        $helper->withSchemaValidated();

        $helper->initiateFetchBalance($bankId);
    }

    public function testFetchBalance()
    {
        $bankId = 'ba_AtIZbXUOTDp1ND';

        $helper = $this->getBankAccountHelper();

        $helper->withSchemaValidated();

        $helper->fetchBalance($bankId);
    }
}
