<?php

namespace RZP\Tests\Unit;

use Config;

class ConfigTest extends \RZP\Tests\TestCase
{
    protected $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = Config::getFacadeRoot();
    }

    public function testHdfcConfigTimeout()
    {
        $this->assertEquals(60, \RZP\Gateway\Hdfc\Gateway::TIMEOUT);
    }
}
