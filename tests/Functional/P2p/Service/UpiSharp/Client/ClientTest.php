<?php

namespace RZP\Tests\P2p\Service\UpiSharp\Client;

use Carbon\Carbon;
use RZP\Models\P2p\Client\Entity;
use RZP\Gateway\P2p\Upi\Sharp\Fields;
use RZP\Tests\P2p\Service\UpiSharp\TestCase;

class ClientTest extends TestCase
{
    public function testGetGatewayConfig()
    {
        $helper = $this->getClientHelper();

        $response = $helper->getGatewayConfig($this->gateway, []);
        
        $this->assertArraySubset([
         Entity::GATEWAY_CONFIG =>  [
             Fields::MCC            => "7298",
         ]], $response);
    }
}
