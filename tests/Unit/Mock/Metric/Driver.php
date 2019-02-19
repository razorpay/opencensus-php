<?php

namespace RZP\Tests\Unit\Mock\Metric;

use Razorpay\Metrics\Drivers\Mock;

class Driver extends Mock
{
    public $calledCount;

    public function count(string $metric, int $times = 1, array $dimensions = [])
    {
        $this->calledCount[$metric][] = $dimensions;
    }

    public function metric(string $metric): array
    {
        return ($this->calledCount[$metric] ?? []);
    }
}
