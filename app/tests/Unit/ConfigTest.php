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
}