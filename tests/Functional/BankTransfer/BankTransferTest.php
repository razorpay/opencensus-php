<?php

namespace RZP\Tests\Functional\BankTransfer;

use Redis;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class BankTransferTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/BankTransferTestData.php';

        parent::setUp();

        $this->ba->appAuth();
    }

    public function testBankTransferValidate()
    {
        $this->mockRedis();

        $this->startTest();
    }

    public function testBankTransferValidateRazorp()
    {
        $this->mockRedis();

        $this->startTest();
    }

    public function testBankTransferValidateDuplicateUtr()
    {
        $this->mockRedisGet('dummy_duplicate_data');

        $this->startTest();
    }

    public function testBankTransferValidateFalse()
    {
        $this->startTest();
    }

    public function testBankTransferValidateFailure()
    {
        $this->startTest();
    }

    public function testBankTransferPay()
    {
        $this->startTest();
    }

    public function testBankTransferPayFailure()
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
