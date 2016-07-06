<?php

namespace RZP\Tests\Unit\Models\Transaction;

use Carbon\Carbon;
use Mockery;
use RZP\Models\Settlement\Holidays;

use RZP\Models\Transaction;
use ReflectionClass;
use RZP\Tests\TestCase;

class HolidayTest extends TestCase
{
    public function testHolidayTimestamp()
    {
        $now = Carbon::now('Asia/Kolkata');

        $nextWorkingDay = Holidays::getNextWorkingDay($now);

        assert($nextWorkingDay->hour === 0);
        assert($nextWorkingDay->minute === 0);
        assert($nextWorkingDay->second === 0);
    }

}
