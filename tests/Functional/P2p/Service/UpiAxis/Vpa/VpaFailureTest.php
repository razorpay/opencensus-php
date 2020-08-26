<?php

namespace RZP\Tests\P2p\Service\UpiAxis\Vpa;

use RZP\Models\P2p\Client;
use RZP\Models\P2p\Client\Config;
use RZP\Tests\P2p\Service\UpiAxis\TestCase;

class VpaFailureTest extends TestCase
{
    public function testInitiateVpaAvailabilityWithInvalidUsername()
    {
        $helper = $this->getVpaHelper();

        $this->withFailureResponse($helper, function($error)
        {
            $this->assertArraySubset([
                'code'        => 'BAD_REQUEST_ERROR',
                'description' => 'The username format is invalid.'
            ], $error);
        });

        $response = $helper->initiateCheckVpaAvailable([
            'username' => 'ab'
        ]);
    }

    public function testVpaCreateWithAlreadyTaken()
    {
        $helper = $this->getVpaHelper();

        $this->withFailureResponse($helper, function($error)
        {
            $this->assertArraySubset([
                'code'        => 'BAD_REQUEST_ERROR',
                'description' => 'Duplicate VPA address, try a different username',
                'action'      => 'initiateCheckAvailability'
            ], $error);
        });

        $request = $helper->intiateCreateVpa([
            'username' => strtolower($this->fixtures->vpa(self::DEVICE_2)->getUsername()), // alc02custvpa03
        ]);
    }

    public function testInitiateCreateVpaWithDifferentPhonenumber()
    {
        $helper = $this->getVpaHelper();

        $this->withFailureResponse($helper, function($error)
        {
            $this->assertArraySubset([
                'code'        => 'BAD_REQUEST_ERROR',
                'description' => 'VPA not available, try a different username',
                'action'      => 'initiateCheckAvailability'
            ], $error);
        });

        $helper->intiateCreateVpa([
            'username' => '9999999999',
        ]);
    }

    public function testMaxVpaLimitReachedForCustomer()
    {
        $helper = $this->getVpaHelper();

        // Creating 4 more VPAs for the customer
        $this->fixtures->createVpa([]);
        $this->fixtures->createVpa([]);
        $this->fixtures->createVpa([]);
        $vpa = $this->fixtures->createVpa([]);

        $vpas = $helper->fetchAllVpa();

        // We are only allowing 3 max vpas
        $this->assertCount(5, $vpas['items']);

        $this->withFailureResponse($helper, function($error)
        {
            $this->assertArraySubset([
                'code'        => 'BAD_REQUEST_ERROR',
                'description' => 'Maximum VPA allowed per customer limit reached',
            ], $error);
        });

        $helper->initiateCheckVpaAvailable();

        // Even if the VPA is deleted
        $vpa->delete();

        $helper->initiateCheckVpaAvailable();

        // Also for addition
        $helper->intiateCreateVpa();
    }

    public function testMaxVpaLimitWithChangedMaxVpa()
    {
        $merchantId = $this->fixtures->merchant->getId();

        $client = $this->fixtures->handle->client(Client\Type::MERCHANT, $merchantId);

        $client->setConfig([Config::MAX_VPA => 2])->save();

        $helper = $this->getVpaHelper();

        $this->fixtures->createVpa([]);

        $vpas = $helper->fetchAllVpa();

        $this->assertCount(2, $vpas['items']);

        $this->withFailureResponse($helper, function($error)
        {
            $this->assertArraySubset([
                'code'        => 'BAD_REQUEST_ERROR',
                'description' => 'Maximum VPA allowed per customer limit reached',
            ], $error);
        });

        $helper->initiateCheckVpaAvailable();
    }
}
