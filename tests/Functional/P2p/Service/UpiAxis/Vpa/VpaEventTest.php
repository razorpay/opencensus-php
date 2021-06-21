<?php

namespace RZP\Tests\P2p\Service\UpiAxis\Vpa;

use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Tests\P2p\Service\Base\Traits\EventsTrait;
use RZP\Tests\P2p\Service\UpiAxis\TestCase;

class VpaEventTest extends TestCase
{
    use EventsTrait;
    use TestsWebhookEvents;

    public function testVpaCreated()
    {
        $this->expectWebhookEvent(
            'customer.vpa.created',
            function (array $event)
            {
                $this->assertArraySubset([
                    'entity'    => 'vpa',
                    'active'    => true,
                    'default'   => false,
                    'bank_account' => [
                        'id'          => 'ba_ALC01bankAc002',
                        'entity'      => 'bank_account',

                    ],
                    'device'       => [
                        'id'          => 'device_ALC01device001',
                        'entity'      => 'device',
                        'customer_id' => 'cust_ArzpLocalCust1',
                        'contact'     => '919988771111',
                        'auth_token'  => 'XXXXXXXXXXe001',
                    ],
                ], $event['payload']);
            }
        );

        $helper = $this->getVpaHelper();

        $request = $helper->intiateCreateVpa();

        $content = $this->handleSdkRequest($request);

        $helper->withSchemaValidated();

        $request = $helper->createVpa($request['callback'], $content);

        $content = $this->handleSdkRequest($request);

        $helper->createVpa($request['callback'], $content);
    }

    public function testVpaCreateFailed()
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

        $this->dontExpectAnyWebhookEvent();
    }

    public function testVpaDeleted()
    {
        $this->expectWebhookEvent(
            'customer.vpa.deleted',
            function (array $event)
            {
                $this->assertArraySubset([
                    'entity'    => 'vpa',
                    'active'    => true,
                    'default'   => false,
                    'device'    => [
                        'id'            => 'device_ALC01device001',
                        'entity'        => 'device',
                        'auth_token'    => 'XXXXXXXXXXe001',
                    ],
                ], $event['payload']);
            }
        );

        $vpa = $this->fixtures->createVpa([
            'default' => false,
        ]);

        $helper = $this->getVpaHelper();

        $helper->withSchemaValidated();

        $helper->deleteVpa($vpa->getPublicId());

        $this->assertTrue($vpa->refresh()->trashed());
    }
}


