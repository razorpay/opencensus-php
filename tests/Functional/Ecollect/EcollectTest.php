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
        $this->startTest();
    }

    public function testEcollectValidateRazorp()
    {
        $this->startTest();
    }

    public function testEcollectValidateDuplicateUtr()
    {
        Redis::shouldReceive('get')
            ->once()
            ->andReturnUsing(function ()
            {
                return 'dummy_duplicate_data';
            });

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
}
