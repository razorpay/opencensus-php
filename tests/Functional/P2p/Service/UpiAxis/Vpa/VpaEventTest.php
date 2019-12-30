<?php

namespace RZP\Tests\P2p\Service\UpiAxis\Vpa;

use RZP\Tests\P2p\Service\Base\Traits\EventsTrait;
use RZP\Tests\P2p\Service\UpiAxis\TestCase;

class VpaEventTest extends TestCase
{
    use EventsTrait;

    public function testVpaCreated()
    {
        $this->setEventsForMerchant();

        $helper = $this->getVpaHelper();

        $request = $helper->intiateCreateVpa();

        $content = $this->handleSdkRequest($request);

        $helper->withSchemaValidated();

        $request = $helper->createVpa($request['callback'], $content);

        $content = $this->handleSdkRequest($request);

        $helper->createVpa($request['callback'], $content);

        $this->assertWebhookContent(function($content)
        {
            $this->assertSame('customer.vpa.created', $content['event']);

            $this->assertArraySubset([
                'entity'    => 'vpa',
                'active'    => true,
                'default'   => false,
                'bank_account' => [
                    'id'     => 'ba_ALC01bankAc002',
                    'entity' => 'bank_account',

                ]
            ], $content['payload']);

        }, function ($headers)  {
            $this->assertNotNull($headers['X-Razorpay-Signature'][0]);
            $this->assertSame('www.example.com', $headers['Host'][0]);
        });
    }

    public function testVpaCreateFailed()
    {
        $this->setEventsForMerchant();

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

        $this->assertEmpty($this->mockedWebhookClient->getRequests());
    }
}


