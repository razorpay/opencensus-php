<?php

namespace RZP\Tests\Functional\Helpers;

use Redis;

trait MocksRedisTrait
{
    protected function setupRedisMockWithOptions(array $override = [])
    {
        // We can override default options with array_merge
        $options = array_merge([
            'hGetAll' => [],
            'set'     => true,
            'get'     => 1,
            'hSet'    => null,
            'incr'    => 401,
            'hGet'    => null,
            'setex'   => null,
            'expire'  => null,
        ], $override);

        $redisMock = $this->getMockBuilder(Redis::class)
            ->setMethods(array_keys($options))
            ->getMock();

        Redis::shouldReceive('connection')
            ->andReturn($redisMock);

        foreach ($options as $key => $value)
        {
            $redisMock->method($key)->willReturn($value);
        }

        return $redisMock;
    }
}
