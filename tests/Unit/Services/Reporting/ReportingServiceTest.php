<?php

namespace RZP\Tests\Unit\Services\Reporting;

use RZP\Tests\TestCase;

class ReportingServiceTest extends TestCase
{
    /**
     * Method to successfully test auth headers
     */
    public function testReportingAuthHeaders()
    {
        $this->app['rzp.mode'] = 'test';

        $reporting = new \RZP\Services\Reporting($this->app);

        $auth = $this->getMethod('getAuthHeaders');

        $authHeaders = $auth->invokeArgs($reporting, []);

        assert(count($authHeaders) === 2);
        $this->assertEquals($authHeaders[0], 'rzp_live');
    }

    protected function getMethod($name)
    {
        $class = new \ReflectionClass('\RZP\Services\Reporting');

        $method = $class->getMethod($name);

        $method->setAccessible(true);

        return $method;
    }
}
