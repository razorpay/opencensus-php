<?php

namespace RZP\Tests\P2p\Service\UpiSharp\Vpa;

use RZP\Tests\P2p\Service\UpiSharp\TestCase;

class VpaTest extends TestCase
{
    public function testFetchHandles()
    {
        $helper = $this->getVpaHelper();

        $helper->withSchemaValidated();

        $handles = $helper->fetchHandles();

        $this->assertCollection($handles, 2);
    }

    public function testCreateVpa()
    {
        $helper = $this->getVpaHelper();

        $helper->withSchemaValidated();

        $helper->createVpa();
    }

    public function testFetchVpa()
    {
        $vpaId = $this->fixtures->vpa->getPublicId();

        $helper = $this->getVpaHelper();

        $helper->withSchemaValidated();

        $helper->fetchVpa($vpaId);
    }

    public function testFetchAllVpa()
    {
        $helper = $this->getVpaHelper();

        $helper->withSchemaValidated();

        $helper->fetchAllVpa();
    }

    public function testAssignBankAccount()
    {
        $this->fixtures->vpa->setBankAccountId('NA')->save();

        $vpaId = $this->fixtures->vpa->getPublicId();

        $bankAccountId = $this->fixtures->bank_account->getPublicId();

        $helper = $this->getVpaHelper();

        $helper->withSchemaValidated();

        $helper->assignBankAccount($vpaId, $bankAccountId);

        $this->assertSame($this->fixtures->bank_account->getId(),
                          $this->fixtures->vpa->reload()->getBankAccountId());
    }

    public function testCheckAvailability()
    {
        $helper = $this->getVpaHelper();

        $helper->withSchemaValidated();

        $helper->checkAvailability();
    }

    public function testDeleteVpa()
    {
        $vpaId = $this->fixtures->vpa->getPublicId();

        $helper = $this->getVpaHelper();

        $helper->withSchemaValidated();

        $helper->deleteVpa($vpaId);
    }
}
