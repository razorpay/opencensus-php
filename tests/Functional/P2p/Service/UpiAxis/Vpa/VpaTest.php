<?php

namespace RZP\Tests\P2p\Service\UpiAxis\Vpa;

use RZP\Models\P2p\Vpa\Entity;
use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Tests\P2p\Service\UpiAxis\TestCase;
use RZP\Tests\P2p\Service\Base\Traits\TransactionTrait;

class VpaTest extends TestCase
{
    use TransactionTrait;

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

    public function testInitiateCreateVpaWithPhonenumber()
    {
        $helper = $this->getVpaHelper();

        $response = $helper->intiateCreateVpa([
            'username' => substr($this->fixtures->device->getContact(), -10),
        ]);

        $this->assertSame('9988771111@razoraxis', $response['request']['content']['customerVpa']);
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

        $transaction = $this->createCollectIncomingTransaction([
            'payer_id' => $vpa->getId(),
        ]);

        $this->assertArraySubset([
            'status'        => 'requested',
            'payer_id'      => $vpa->getId(),
        ], $transaction->toArray());

        $helper = $this->getVpaHelper();

        $helper->withSchemaValidated();

        $helper->deleteVpa($vpa->getPublicId());

        $this->assertTrue($vpa->refresh()->trashed());

        // Pending collect transaction should be deleted
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
