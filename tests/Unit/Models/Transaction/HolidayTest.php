<?php

namespace Tests\Unit\Models\Transaction;

use Carbon\Carbon;
use Mockery;
use Models\Settlement\Holidays;

use Models\Transaction;
use ReflectionClass;
use Tests\TestCase;

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
