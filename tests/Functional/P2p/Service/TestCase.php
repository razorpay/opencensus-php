<?php

namespace RZP\Tests\P2p\Service;

use RZP\Tests\Functional;

class TestCase extends Functional\TestCase
{
    /**
     * @var $fixtures Base\Fixtures\Fixtures
     */
    protected $fixtures;

    public function setUp()
    {
        parent::setUp();

        $this->fixtures = new Base\Fixtures\Fixtures();
    }

    protected function getCustomerHelper(): Base\CustomerHelper
    {
        return new Base\CustomerHelper($this->fixtures);
    }

    protected function getDeviceHelper(): Base\DeviceHelper
    {
        return new Base\DeviceHelper($this->fixtures);
    }
}
