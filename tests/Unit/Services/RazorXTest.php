<?php

namespace RZP\Tests\Unit\Services;

use RZP\Tests\TestCase;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class RazorXTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        parent::setUp();

        $this->razorX = $this->getMockBuilder(RazorXClient::class)
                             ->setConstructorArgs([$this->app])
                             ->setMethods(['sendRequest'])
                             ->getMock();
    }

    public function testGetTreatment()
    {
        $route = 'evaluate';

        $requestParams = [
            'id'           => '10000000000000',
            'feature_flag' => 'reportsV3',
            'environment'  => 'testing',
            'mode'         => 'test',
        ];

        $this->razorX->expects($this->once())
                     ->method('sendRequest')
                     ->with($route, 'GET', $requestParams)
                     ->willReturn('control');


        $variant = $this->razorX->getTreatment('10000000000000', 'reportsV3', 'test');

        $this->assertEquals('control', $variant);
    }
}
