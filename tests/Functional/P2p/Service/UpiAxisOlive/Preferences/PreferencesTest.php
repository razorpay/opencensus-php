<?php

namespace RZP\Tests\P2p\Service\UpiAxisOlive\Device;

use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Tests\P2p\Service\UpiAxisOlive\TestCase;
use RZP\Tests\P2p\Service\Base\Traits\EventsTrait;
use RZP\Tests\P2p\Service\Base\Traits\MetricsTrait;
use RZP\Tests\P2p\Service\Base\Traits\TransactionTrait;

class PreferencesTest extends TestCase
{
    use EventsTrait;
    use MetricsTrait;
    use TransactionTrait;
    use TestsWebhookEvents;

    public function testGetGatewayPreferences()
    {
        $helper = $this->getPreferencesHelper();

        $helper->withSchemaValidated();

        $response = $helper->getGatewayPreferences($this->gateway, []);

        $this->assertArrayHasKey('customer', $response);

        $this->assertArrayHasKey('gateways', $response);
    }
}
