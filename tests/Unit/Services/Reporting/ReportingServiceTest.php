<?php

namespace RZP\Tests\Unit\Services\Reporting;

use RZP\Tests\TestCase;
use RZP\Constants\Mode;

class ReportingServiceTest extends TestCase
{
    /**
     * Method to successfully test auth headers
     */
    public function testReportingAuthHeaders()
    {
        // In unit tests this is not set(otherwise it gets set via ba middleware)
        $this->app['rzp.mode'] = Mode::TEST;

        $reporting = new \RZP\Services\Reporting();

        $auth = $this->getMethod('getAuthHeaders');

        $authHeaders = $auth->invokeArgs($reporting, []);

        assert(count($authHeaders) === 2);
        $this->assertEquals($authHeaders[0], 'api');
    }

    /**
     * @param  string $name
     *
     * @return mixed
     */
    protected function getMethod(string $name)
    {
        $class = new \ReflectionClass('\RZP\Services\Reporting');

        $method = $class->getMethod($name);

        $method->setAccessible(true);

        return $method;
    }
}
