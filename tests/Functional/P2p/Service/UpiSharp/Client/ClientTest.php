<?php

namespace RZP\Tests\P2p\Service\UpiSharp\Device;

use Carbon\Carbon;
use phpseclib\Crypt\AES;
use RZP\Models\P2p\Device;
use RZP\Exception\RuntimeException;
use RZP\Models\P2p\Device\DeviceToken;
use RZP\Http\Controllers\P2p\Requests;
use RZP\Tests\P2p\Service\Base\P2pRequest;
use RZP\Tests\P2p\Service\Base\Scenario;
use RZP\Gateway\P2p\Upi\Sharp\DeviceGateway;
use RZP\Tests\P2p\Service\UpiSharp\TestCase;
use RZP\Tests\P2p\Service\Base\Fixtures\Fixtures;

class ClientTest extends TestCase
{
    public function testGetGatewayConfig()
    {
        $helper = $this->getClientHelper();

        $helper->withSchemaValidated();

        $response = $helper->getGatewayConfig($this->gateway, []);
    }
}
