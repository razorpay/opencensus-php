<?php

namespace RZP\Tests\P2p\Service\UpiAxis\BankAccount;

use RZP\Tests\P2p\Service\Base;
use RZP\Models\P2p\BankAccount;
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

        $this->assertCollection($bankAccounts, 2);
        // This is to assert that existing bank accounts are getting updates
        $this->assertUpiPinSet(false, $bankAccounts['items'][1]);

        $content['sdk']['accounts'][1]['branchName'] = 'Test Branch Name';
        $content['sdk']['accounts'][1]['mpinSet'] = 'true';
        $content['sdk']['accounts'][1]['referenceId'] = 'UpadatingThis';

        $uniqueId = $content['sdk']['accounts'][1]['bankAccountUniqueId'];

        unset($content['sdk']['accounts'][0]);

        $bankAccounts = $helper->retrieve($request['callback'], $content);

        $this->assertCollection($bankAccounts, 1);
        $this->assertUpiPinSet(true, $bankAccounts['items'][0]);

        $bankAccount = $this->getDbBankAccountById($bankAccounts['items'][0]['id']);
        $this->assertSame('UpadatingThis', $bankAccount->getGatewayData()['referenceId']);
        $this->assertSame($uniqueId, $bankAccount->getGatewayData()['id']);
        $this->assertSame($uniqueId, $bankAccount->getGatewayData()['bankAccountUniqueId']);

        $this->assertArrayHasKey('type', $bankAccount);
        $this->assertSame(BankAccount\Type::SAVINGS, $bankAccount->getType());
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

    public function testSetUpiPinTokenExpiry()
    {
        $this->setDeviceTokenExpiryValidation(true);

        $this->fixtures->deviceToken(self::DEVICE_1)->generateRefreshedAt()->saveOrFail();

        $helper = $this->getBankAccountHelper();

        $helper->withSchemaValidated();

        $helper->initiateSetUpiPin($this->fixtures->bank_account->getPublicId());
    }

    public function testFetchBalanceTokenExpiry()
    {
        $this->setDeviceTokenExpiryValidation(true);

        $this->fixtures->deviceToken(self::DEVICE_1)->generateRefreshedAt()->saveOrFail();

        $helper = $this->getBankAccountHelper();

        $helper->withSchemaValidated();

        $helper->initiateFetchBalance($this->fixtures->bank_account->getPublicId());
    }
}
