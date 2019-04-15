<?php

namespace RZP\Tests\P2p\Service\UpiAxis\BankAccount;

use RZP\Tests\P2p\Service\Base;
use RZP\Models\P2p\BankAccount\Entity;
use RZP\Tests\P2p\Service\UpiAxis\TestCase;

class BankAccountTest extends TestCase
{
    public function testFetchBanks()
    {
        $helper = $this->getBankAccountHelper();

        $helper->withSchemaValidated();

        $banks = $helper->fetchBanks();

        $this->assertCollection($banks, 3);
    }

    public function testInitiateRetrieve()
    {
        $id = 'bank_' . Base\Constants::ARZP_AXIS;

        $helper = $this->getBankAccountHelper();

        $helper->withSchemaValidated();

        $helper->initiateRetrieve($id);
    }

    public function testRetrieve()
    {
        $id = 'bank_' . Base\Constants::ARZP_AXIS;

        $helper = $this->getBankAccountHelper();

        $request = $helper->initiateRetrieve($id);

        $content = $this->handleSdkRequest($request);

        $helper->withSchemaValidated();

        $bankAccounts = $helper->retrieve($request['callback'], $content);

        $this->assertCollection($bankAccounts, 3);

        // This is to assert that existing bank accounts are getting updates
        $this->assertUpiPinSet(false, $bankAccounts['items'][2]);

        $content['sdk']['accounts'][1]['branchName'] = 'Test Branch Name';
        $content['sdk']['accounts'][1]['mpinSet'] = 'true';

        $bankAccounts = $helper->retrieve($request['callback'], $content);

        $this->assertUpiPinSet(true, $bankAccounts['items'][2]);
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
        $id = $this->fixtures->bank_account->getPublicId();

        $helper = $this->getBankAccountHelper();

        $helper->withSchemaValidated();

        $helper->fetch($id);
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
        $bankAccount = $this->fixtures->bank_account;

        $bankAccount->setCredsUpiPin(false)->saveOrFail();

        $this->assertUpiPinSet(false, $bankAccount->toArrayPublic());

        $helper = $this->getBankAccountHelper();

        $request = $helper->initiateSetUpiPin($bankAccount->getPublicId());

        $content = $this->handleSdkRequest($request);

        $helper->withSchemaValidated();

        $bankAccount = $helper->setUpiPin($request['callback'], $content);

        $this->assertUpiPinSet(true, $bankAccount);
    }

    public function testChangeUpiPin()
    {
        $bankAccount = $this->fixtures->bank_account;

        $helper = $this->getBankAccountHelper();

        $request = $helper->initiateSetUpiPin($bankAccount->getPublicId(), [
            Entity::ACTION  => 'change',
            Entity::CARD    => null,
        ]);

        $content = $this->handleSdkRequest($request);

        $helper->withSchemaValidated();

        $bankAccount = $helper->setUpiPin($request['callback'], $content);

        $this->assertUpiPinSet(true, $bankAccount);
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

        $request = $helper->initiateFetchBalance($bankAccountId);

        $content = $this->handleSdkRequest($request);

        $helper->withSchemaValidated();

        $response = $helper->fetchBalance($request['callback'], $content);

        $this->assertSame(220690, $response[Entity::BALANCE]);
    }
}
