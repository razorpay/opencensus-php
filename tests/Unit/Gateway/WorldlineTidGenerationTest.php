<?php

namespace RZP\Tests\Unit\Gateway;

use RZP\Tests\Functional\TestCase;
use Illuminate\Support\Facades\Redis;
use RZP\Exception\ServerErrorException;
use RZP\Tests\Unit\Gateway\BaseGatewayTest;
use RZP\Models\Gateway\Terminal\GatewayProcessor\Worldline;


class WorldlineTidGenerationTest extends TestCase
{
    protected $generator;

    protected $redis;

    public function setUp()
    {
        parent::setUp();

        $this->generator = new Worldline\TidGenerator();

        $this->redis = Redis::connection()->client();

        $ranges = [
            [123800, 123899],
            [133800, 133899],
            [143800, 143899]
        ];

        $this->insertTidRangesIntoRedis($ranges);
    }

    private function insertTidRangesIntoRedis(array $ranges)
    {
        foreach ($ranges as $range)
        {
            $this->redis->rpush($this->generator->redisTidKey, json_encode($range));
        }
    }

    public function testGenerateTid()
    {
        for ($i = 0; $i < 100; $i++)
        {
            $tid = $this->generator->generateTid();

            $this->assertEquals(123800 + $i, $tid);
        }
    }

    public function testGenerateTidRangeSwitch()
    {
        $this->testGenerateTid();

        $tid = $this->generator->generateTid();

        $this->assertEquals(133800, $tid);
    }

    // public function testGenerateTidExhausted()
    // {
    //     for ($i = 0; $i < 300; $i++)
    //     {
    //         $this->generator->generateTid();
    //     }

    //     $this->expectException(ServerErrorException::class);

    //     $this->generator->generateTid();
    // }

    public function tearDown()
    {
        $this->redis->flushall();

        parent::tearDown();
    }
}
