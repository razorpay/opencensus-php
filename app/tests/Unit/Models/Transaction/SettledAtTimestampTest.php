<?php

namespace Tests\Unit\Models\Transaction;

use Carbon\Carbon;
use Mockery;
use Models\Transaction;
use ReflectionClass;
use Tests\TestCase;

class SettledAtTimestampTest extends TestCase
{
    public function testSettledAtTimestampForNonWorkingSaturdayTxn()
    {
        // Mapping of Payment day to Settlement day
        // Number refers to day of week

        $workingSaturdayWeek = false;

        $map1 = [
            0 => 1,
            1 => 2,
            2 => 3,
            3 => 4,
            4 => 5,
            5 => 8,
            6 => 8,
        ];

        $map2 = [
            0 => 2,
            1 => 3,
            2 => 4,
            3 => 5,
            4 => 8,
            5 => 9,
            6 => 9,
        ];

        $map3 = [
            0 => 3,
            1 => 4,
            2 => 5,
            3 => 8,
            4 => 9,
            5 => 10,
            6 => 10,
        ];

        $map4 = [
            0 => 4,
            1 => 5,
            2 => 8,
            3 => 9,
            4 => 10,
            5 => 11,
            6 => 11,
        ];

        $this->runSettledAtFunc($map1, 1, $workingSaturdayWeek);
        $this->runSettledAtFunc($map2, 2, $workingSaturdayWeek);
        $this->runSettledAtFunc($map3, 3, $workingSaturdayWeek);
        $this->runSettledAtFunc($map4, 4, $workingSaturdayWeek);
    }

    public function testSettledAtTimestampForWorkingSaturdayTxn()
    {
        // Mapping of Payment day to Settlement day
        // Number refers to day of week
        // This is a working saturday test

        $workingSaturdayWeek = true;

        $map1 = [
            0 => 1,
            1 => 2,
            2 => 3,
            3 => 4,
            4 => 5,
            5 => 6,
            6 => 8,
        ];

        $map2 = [
            0 => 2,
            1 => 3,
            2 => 4,
            3 => 5,
            4 => 6,
            5 => 8,
            6 => 9,
        ];

        $map3 = [
            0 => 3,
            1 => 4,
            2 => 5,
            3 => 6,
            4 => 8,
            5 => 9,
            6 => 10,
        ];

        $map4 = [
            0 => 4,
            1 => 5,
            2 => 6,
            3 => 8,
            4 => 9,
            5 => 10,
            6 => 11,
        ];

        $this->runSettledAtFunc($map1, 1, $workingSaturdayWeek);
        $this->runSettledAtFunc($map2, 2, $workingSaturdayWeek);
        $this->runSettledAtFunc($map3, 3, $workingSaturdayWeek);
        $this->runSettledAtFunc($map4, 4, $workingSaturdayWeek);
    }



    protected function runSettledAtFunc($map, $addDays, $workingSaturdayWeek)
    {
        $class = new ReflectionClass('Models\Transaction\Core');
        $method = $class->getMethod('calculateSettledAtTimestamp');
        $method->setAccessible(true);

        $core = new Transaction\Core;

        foreach ($map as $key => $value)
        {
            $capturedAt = Carbon::now('Asia/Kolkata');

            if ($workingSaturdayWeek === true)
            {
                $capturedAt = Carbon::parse('first saturday of this month','Asia/Kolkata');
            }
            else
            {
                $capturedAt = Carbon::parse('second saturday of this month','Asia/Kolkata');
            }

            $day = (int) $capturedAt->format('w');
            $capturedAddDays = $key - $day;
            $capturedAt->addDays($capturedAddDays);

            $day = (int) $capturedAt->format('w');

            $settledAt = $method->invokeArgs($core, array($capturedAt->timestamp, $addDays));
            $settledAt = Carbon::createFromTimestamp($settledAt, 'Asia/Kolkata');

            $diff = $settledAt->diffInDays($capturedAt);
            $day  += $diff;

            $this->assertEquals($value, $day);
        }
    }
}
