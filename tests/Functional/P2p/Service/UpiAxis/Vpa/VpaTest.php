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
        $this->assertSame($response[Entity::SUGGESTIONS], array_map(
            function($item)
            {
                return explode('@', $item)[0];
            }, $suggestions));
    }

    public function testAssignBankAccount()
    {
        $bankAccount = $this->fixtures->createBankAccount([
            'gateway_data' => [
                'referenceId' => 'SomeReferenceId'
            ]
        ]);

        $vpaId = $this->fixtures->vpa->getPublicId();

        $helper = $this->getVpaHelper();

        $helper->withSchemaValidated();

        $helper->assignBankAccount($vpaId, $bankAccount->getPublicId());

        $this->assertSame($bankAccount->getId(), $this->fixtures->vpa->reload()->getBankAccountId());
    }

    public function testDeleteVpa()
    {
        $vpa = $this->fixtures->createVpa([
            'default' => false,
        ]);

        $helper = $this->getVpaHelper();

        $helper->withSchemaValidated();

        $helper->deleteVpa($vpa->getPublicId());

        $this->assertTrue($vpa->refresh()->trashed());
    }

    public function testSetDefault()
    {
        $default = $this->fixtures->vpa;

        $vpa = $this->fixtures->createVpa([
            'default' => false,
        ]);

        $this->assertTrue($default->isDefault());

        $helper = $this->getVpaHelper();

        $helper->withSchemaValidated();

        $response = $helper->setDefault($vpa->getPublicId());

        $this->assertTrue($response['default']);

        $this->assertTrue($vpa->refresh()->isDefault());

        $this->assertFalse($default->refresh()->isDefault());
    }
}
