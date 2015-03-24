<?php

namespace Tests\Unit\Models\Transaction;

use Carbon\Carbon;
use Mockery;
use Models\Transaction;
use ReflectionClass;
use Tests\TestCase;

class SettledAtTimestampTest extends TestCase
{
    public function testSettledAtTimestampForTxn()
    {
        // Mapping of Payment day to Settlement day
        // Number refers to day of week

        $map3 = [
            0 => 4,
            1 => 4,
            2 => 5,
            3 => 1,
            4 => 2,
            5 => 3,
            6 => 4,
        ];

        $map1 = [
            0 => 2,
            1 => 2,
            2 => 3,
            3 => 4,
            4 => 5,
            5 => 1,
            6 => 1,
        ];

        $this->runSettledAtFunc($map3, 3);
//        $this->runSettledAtFunc($map1, 1);
    }

    protected function runSettledAtFunc($map, $addDays)
    {
        $class = new ReflectionClass('Models\Transaction\Core');
        $method = $class->getMethod('getSettledAtTimestamp');
        $method->setAccessible(true);

        $core = new Transaction\Core;

        foreach ($map as $key => $value)
        {
            $capturedAt = Carbon::today('Asia/Kolkata');
            $day = (int) $capturedAt->format('w');
            $capturedAddDays = $key - $day;
            $capturedAt->addDays($capturedAddDays);

            $day = (int) $capturedAt->format('w');

            $settledAt = $method->invokeArgs($core, array($capturedAt->timestamp, $addDays));

            $day = (int) Carbon::createFromTimestamp($settledAt, 'Asia/Kolkata')->format('w');

            $this->assertEquals($value, $day);
        }
    }
}