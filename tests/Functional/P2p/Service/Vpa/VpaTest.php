<?php

namespace RZP\Tests\P2p\Service\Vpa;

use RZP\Tests\P2p\Service\TestCase;

class VpaTest extends TestCase
{
    public function testCreateVpa()
    {
        $helper = $this->getVpaHelper();

        $helper->withSchemaValidated();

        $helper->createVpa();
    }

    public function testAssignBankAccount()
    {
        $vpaId = 'vpa_AagzIzN8Hgp3wU';

        $bankId = 'ba_9cWHVXVPkAZZQZ';

        $helper = $this->getVpaHelper();

        $helper->withSchemaValidated();

        $helper->assignBankAccount($vpaId, $bankId);
    }

    public function testCheckAvailability()
    {
        $helper = $this->getVpaHelper();

        $helper->withSchemaValidated();

        $helper->checkAvailability();
    }

    public function testDeleteVpa()
    {
        $vpaId = 'vpa_AagzIzN8Hgp3wU';

        $helper = $this->getVpaHelper();

        $helper->withSchemaValidated();

        $helper->deleteVpa($vpaId);
    }

    public function testFetchVpa()
    {
        $vpaId = 'vpa_AagzIzN8Hgp3wU';

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
}
