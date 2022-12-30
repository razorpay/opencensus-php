<?php

namespace RZP\Tests\P2p\Service\UpiSharp\Client;

use Carbon\Carbon;
use RZP\Models\P2p\Client\Entity;
use RZP\Tests\P2p\Service\UpiSharp\TestCase;

class ClientTest extends TestCase
{
    public function testGetGatewayConfig()
    {
        $helper = $this->getClientHelper();

        $response = $helper->getGatewayConfig($this->gateway, []);

        $this->assertArraySubset([
         Entity::GATEWAY_CONFIG =>  [
             Entity::MERCHANT_ID            => "10000000000000",
         ]], $response);
    }
}
