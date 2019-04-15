<?php

namespace RZP\Tests\P2p\Service\UpiAxis\Vpa;

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
            'username' => 'a.b'
        ]);
    }
}
