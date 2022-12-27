<?php

namespace RZP\Tests\P2p\Service\UpiAxisOlive\Client;

use RZP\Models\P2p\Client\Entity;
use RZP\Gateway\P2p\Upi\AxisOlive\Fields;
use RZP\Tests\P2p\Service\UpiAxisOlive\TestCase;

class ClientTest extends TestCase
{
    public function testGetGatewayConfig()
    {
        $helper = $this->getClientHelper();

        $helper->withSchemaValidated();

        $response = $helper->getGatewayConfig($this->gateway, []);

        $this->assertArraySubset([
                     Entity::GATEWAY_CONFIG =>[
                         Fields::MERCHANT_ID            => "RAZORPAYAGG",
                         Fields::MERCHANT_CHANNEL_ID    => "OLIVEAPP",
                         Fields::SUB_MERCHANT_ID        => "OLIVE",
                         Fields::MCC_CODE               => "7299",
                     ]
                 ], $response);
    }
}
