<?php

namespace RZP\Tests\Unit\Services;

use RZP\Exception\ServerErrorException;
use RZP\Tests\TestCase;

class CorePaymentServiceTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->cps = $this->app['cps'];
    }

    public function testCpsFunctions()
    {
        $array = ['key' => 'value'];

        $response = $this->cps->action('auth', $array);

        $this->assertEquals($array, $response);

        $this->expectException(ServerErrorException::class);

        $this->expectExceptionMessage('timed out or something');

        $response = $this->cps->action('fail', $array);
    }
}
