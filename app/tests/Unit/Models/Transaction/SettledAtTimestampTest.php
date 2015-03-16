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
        $class = new ReflectionClass('Models\Transaction\Core');
        $method = $class->getMethod('getSettledAtTimestamp');
        $method->setAccessible(true);

        $core = new Transaction\Core;

        // Mapping of Payment day to Settlement day
        // Number refers to day of week
        $map = [
            0 => 4,
            1 => 4,
            2 => 5,
            3 => 1,
            4 => 2,
            5 => 3,
            6 => 4,
        ];

        foreach ($map as $key => $value)
        {
            $capturedAt = Carbon::today('Asia/Kolkata');
            $day = (int) $capturedAt->format('w');
            $addDays = $key - $day;
            $capturedAt->addDays($addDays);

            $addDays = 3;
            $day = (int) $capturedAt->format('w');

            $settledAt = $method->invokeArgs($core, array($capturedAt->timestamp, $addDays));

            $day = (int) Carbon::createFromTimestamp($settledAt, 'Asia/Kolkata')->format('w');

            $this->assertEquals($value, $day);
        }
    }
}