<?php

namespace RZP\Tests\P2p\Service\UpiAxis\Vpa;

use RZP\Models\P2p\Vpa\Entity;
use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Tests\P2p\Service\UpiAxis\TestCase;

class VpaTest extends TestCase
{
    public function testFetchHandles()
    {
        $helper = $this->getVpaHelper();

        $helper->withSchemaValidated();

        $handles = $helper->fetchHandles();

        $this->assertCollection($handles, 4);
    }

    public function testInitiateCreateVpa()
    {
        $helper = $this->getVpaHelper();

        $helper->withSchemaValidated();

        $helper->intiateCreateVpa();
    }

    public function testCreateVpa()
    {
        $helper = $this->getVpaHelper();

        $request = $helper->intiateCreateVpa();

        $content = $this->handleSdkRequest($request);

        $helper->withSchemaValidated();

        $request = $helper->createVpa($request['callback'], $content);

        $content = $this->handleSdkRequest($request);

        $helper->createVpa($request['callback'], $content);
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
}
