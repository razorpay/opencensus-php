<?php

namespace RZP\Tests\Unit\Gateway;

use App;
use RZP\Tests\Functional\TestCase;
use Illuminate\Support\Facades\Redis;
use RZP\Exception\ServerErrorException;
use RZP\Tests\Unit\Gateway\BaseGatewayTest;
use RZP\Models\Gateway\Terminal\GatewayProcessor\Worldline;


class WorldlineTidGenerationTest extends TestCase
{
    protected $generator;

    protected $redis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new Worldline\TidGenerator();

        $this->redis = Redis::connection('mutex_redis')->client();

        $ranges = [
            [123800, 123899],
            [133800, 133899],
            [143800, 143899]
        ];

        $this->insertTidRangesIntoRedis($ranges);
    }

    private function insertTidRangesIntoRedis(array $ranges)
    {
        if (empty($this->redis->lrange($this->generator->redisTidKey, 0, -1)))
        {
            foreach ($ranges as $range)
            {
                $this->redis->rpush($this->generator->redisTidKey, json_encode($range));
            }    
        }  
    }

    public function testGenerateTid()
    {
        $app = App::getFacadeRoot();

        for ($i = 0; $i < 100; $i++)
        {
            $app['config']->set('applications.redisdualwrite', ['redislab_cache_read' => true, 'skip_dual_write' => false ]);

            $generator = new Worldline\TidGenerator();

            $tid = $generator->generateTid();

            $this->assertEquals(123800 + $i, $tid);

            // If we switch to ElasticCache from RedisLabs in between, then functionality should work fine
            $app['config']->set('applications.redisdualwrite', ['redislab_cache_read' => false, 'skip_dual_write' => false ]);
            $i++;

            $generator = new Worldline\TidGenerator();

            $tid = $generator->generateTid();

            $this->assertEquals(123800 + $i, $tid);
        }

        // test range switch, instead of next sequence (1238900), next range should get picked (133800 - 133899)
        $tid = $generator->generateTid();

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

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
