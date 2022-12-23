<?php

namespace RZP\Tests\P2p\Service\UpiAxisOlive\Client;

use RZP\Exception\RuntimeException;
use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Tests\P2p\Service\UpiAxisOlive\TestCase;
use RZP\Tests\P2p\Service\Base\Traits\EventsTrait;
use RZP\Tests\P2p\Service\Base\Traits\MetricsTrait;
use RZP\Tests\P2p\Service\Base\Traits\TransactionTrait;

class ClientTest extends TestCase
{
    public function testGetGatewayConfig()
    {
        $helper = $this->getClientHelper();

        $helper->withSchemaValidated();

        $this->expectException(RuntimeException::class);

        $helper->getGatewayConfig($this->gateway, []);
    }
}
