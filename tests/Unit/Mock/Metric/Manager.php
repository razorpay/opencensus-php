<?php

namespace RZP\Tests\Unit\Mock\Metric;

use Razorpay\Metrics;
use Razorpay\Metrics\Drivers;

class Manager extends Metrics\Manager
{
    public $mockedDrivers = [];

    /**
     * We can tell Manager which Driver to Mock.
     *
     * @param $driver
     * @return $this
     */
    public function mockDriver($driver)
    {
        $this->mockedDrivers[$driver] = true;

        return $this;
    }

    /**
     * If called driver is found in the list of mocked drivers,
     * we will return mocked driver otherwise actual driver.
     *
     * @param $driver
     * @return Drivers\Driver
     */
    protected function createDriver($driver): Drivers\Driver
    {
        if ((isset($this->mockedDrivers[$driver]) === true))
        {
            return new Driver($driver);
        }

        parent::createDriver($driver);
    }
}
