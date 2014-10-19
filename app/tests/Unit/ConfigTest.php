<?php

namespace Tests\Unit;

use Config;

class ConfigTest extends \Tests\TestCase
{
    protected $config;

    public function setUp()
    {
        parent::setUp();

        $this->config = Config::getFacadeRoot();
    }

    public function testHdfcConfigTimeout()
    {
        $this->assertEquals(30, \Gateway\Hdfc\Config::TIMEOUT);
    }

    public function testGatewayConfig()
    {
        $config = require app_path().'/config/gateway.php';

        $gateways = ['hdfc', 'atom'];

        $configGateways = $config['available'];

        $this->assertEquals(0, count(array_diff($configGateways, $gateways)));

        $this->assertEquals($config['mock_hdfc'], false);
        $this->assertEquals($config['mock_atom'], false);
    }

    public function testSlackPretendTrue()
    {
        $config = require app_path().'/config/slack.php';
        $this->assertEquals(false, $config['pretend']);
    }
}