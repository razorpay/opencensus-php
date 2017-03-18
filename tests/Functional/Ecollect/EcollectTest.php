<?php

namespace RZP\Tests\Functional\Ecollect;

use Redis;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class EcollectTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/EcollectTestData.php';

        parent::setUp();

        $this->ba->appAuth();
    }

    public function testEcollectValidate()
    {
        $this->mockRedis();

        $this->startTest();
    }

    public function testEcollectValidateRazorp()
    {
        $this->mockRedis();

        $this->startTest();
    }

    public function testEcollectValidateDuplicateUtr()
    {
        $this->mockRedisGet('dummy_duplicate_data');

        $this->startTest();
    }

    public function testEcollectValidateFalse()
    {
        $this->startTest();
    }

    public function testEcollectValidateFailure()
    {
        $this->startTest();
    }

    public function testEcollectPay()
    {
        $this->startTest();
    }

    public function testEcollectPayFailure()
    {
        $this->startTest();
    }

    protected function mockRedis()
    {
        $this->mockRedisSet();

        $this->mockRedisGet();
    }

    protected function mockRedisSet()
    {
        Redis::shouldReceive('set')
            ->once();
    }

    protected function mockRedisGet($data = null)
    {
        Redis::shouldReceive('get')
            ->once()
            ->andReturnUsing(function () use ($data) {
                return $data;
            });
    }
}
