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

    public function testInitiateVpaAvailability()
    {
        $helper = $this->getVpaHelper();

        $helper->withSchemaValidated();

        $helper->initiateCheckVpaAvailable();
    }

    public function testCheckVpaAvailability()
    {
        $helper = $this->getVpaHelper();

        $request = $helper->initiateCheckVpaAvailable();

        $content = $this->handleSdkRequest($request);

        $helper->withSchemaValidated();

        $response = $helper->checkAvailability($request['callback'], $content);
    }

    public function testCheckVpaAvailabilityWithSuggestions()
    {
        $helper = $this->getVpaHelper();

        $request = $helper->initiateCheckVpaAvailable();

        $suggestions = [
            'sample1@razoraxis',
            'sample2@razoraxis',
            'sample3@razoraxis'
        ];

        $this->mockSdkContentFunction(function(& $content) use ($suggestions)
        {
            $content['available'] = false;

            $content['vpaSuggestions'] = $suggestions;
        });

        $content = $this->handleSdkRequest($request);

        $helper->withSchemaValidated();

        $response = $helper->checkAvailability($request['callback'], $content);

        $this->assertArrayHasKey(Entity::SUGGESTIONS, $response);
        $this->assertSame($response[Entity::SUGGESTIONS], $suggestions);
    }
}
