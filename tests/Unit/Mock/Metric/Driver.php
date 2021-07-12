<?php

namespace RZP\Tests\Unit\Mock\Metric;

use RZP\Exception\LogicException;
use Razorpay\Metrics\Drivers\Mock;

class Driver extends Mock
{
    // Metric Types
    const COUNT         = "count";
    const HISTOGRAM     = "histogram";

    public $calledCount;

    public $calledHistogram;

    public function count(string $metric, int $times = 1, array $dimensions = [])
    {
        $this->calledCount[$metric][] = $dimensions;
    }

    public function histogram(string $metric, float $value, array $dimensions = [])
    {
        $this->calledHistogram[$metric][] =  $dimensions;
    }

    public function metric(string $metric, string $metricType = self::COUNT): array
    {
        // if the metric of count type then return it's dimensions from calledCount
        // if the metric of histogram type then return it's dimensions from calledHistogram
        // default -> throw Logic exception
        switch ($metricType)
        {
            case self::COUNT:
                return $this->calledCount[$metric];

            case self::HISTOGRAM:
                return $this->calledHistogram[$metric];

            default:
                throw new LogicException("Incorrect Metric Type");
        }
    }
}
