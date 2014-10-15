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

    public function testDefaultGateway()
    {
        $config = require app_path().'/config/gateway.php';
        $this->assertEquals('hdfc', $config['default']);
    }

    public function testSlackPretendTrue()
    {
        $config = require app_path().'/config/slack.php';
        $this->assertEquals(false, $config['pretend']);
    }
}