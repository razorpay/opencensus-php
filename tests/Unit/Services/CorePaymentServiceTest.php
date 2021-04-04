<?php

namespace RZP\Tests\Unit\Services;

use RZP\Tests\TestCase;
use RZP\Constants\Mode;
use RZP\Exception\ServerErrorException;

class CorePaymentServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // This is normally set in BasicAuth. Since this is a unit test and not
        // a functional one, there is no actual request being made, so basic
        // auth is not called and rzp.mode is never set. Setting manually here
        // because mode is required to decide the base URL in CPS initialization.
        $this->app['rzp.mode'] = Mode::TEST;

        $this->cps = $this->app['cps'];
    }

    public function testCpsFunctions()
    {
        $array = ['key' => 'value'];

        $response = $this->cps->action('gateway', 'auth', $array);

        $this->assertEquals($array, $response);

        $this->expectException(ServerErrorException::class);

        $this->expectExceptionMessage('timed out or something');

        $response = $this->cps->action('gateway', 'fail', $array);
    }
}
