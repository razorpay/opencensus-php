<?php

namespace RZP\Tests\P2p\Service\UpiSharp\BankAccount;

use RZP\Tests\P2p\Service\Base;
use RZP\Models\P2p\BankAccount\Entity;
use RZP\Tests\P2p\Service\UpiSharp\TestCase;

class BankAccountTest extends TestCase
{
    public function testFetchBanks()
    {
        $helper = $this->getBankAccountHelper();

        $helper->withSchemaValidated();

        $banks = $helper->fetchBanks();

        $this->assertCollection($banks, 3);
    }

    public function testRetrieve()
    {
        $id = 'bank_' . Base\Constants::ARZP;

        $helper = $this->getBankAccountHelper();

        $helper->withSchemaValidated();

        $helper->retrieve($id);
    }

    public function testFetchAll()
    {
        $helper = $this->getBankAccountHelper();

        $helper->withSchemaValidated();

        $bankAccounts = $helper->fetchAll();

        $this->assertCollection($bankAccounts, 1);
    }

    public function testFetch()
    {
        $bankAccountId = $this->fixtures->bank_account->getPublicId();

        $helper = $this->getBankAccountHelper();

        $helper->withSchemaValidated();

        $bankAccount = $helper->fetch($bankAccountId);

        $this->assertSame($bankAccount[Entity::ID], $bankAccountId);
    }

    public function testInitiateSetUpiPin()
    {
        $bankAccountId = $this->fixtures->bank_account->getPublicId();

        $helper = $this->getBankAccountHelper();

        $helper->withSchemaValidated();

        $helper->initiateSetUpiPin($bankAccountId);
    }

    public function testSetUpiPin()
    {
        $bankAccountId = $this->fixtures->bank_account->getPublicId();

        $helper = $this->getBankAccountHelper();

        $helper->withSchemaValidated();

        $helper->setUpiPin($bankAccountId);
    }

    public function testInitiateFetchBalance()
    {
        $bankAccountId = $this->fixtures->bank_account->getPublicId();

        $helper = $this->getBankAccountHelper();

        $helper->withSchemaValidated();

        $helper->initiateFetchBalance($bankAccountId);
    }

    public function testFetchBalance()
    {
        $bankAccountId = $this->fixtures->bank_account->getPublicId();

        $helper = $this->getBankAccountHelper();

        $helper->withSchemaValidated();

        $helper->fetchBalance($bankAccountId);
    }
}
