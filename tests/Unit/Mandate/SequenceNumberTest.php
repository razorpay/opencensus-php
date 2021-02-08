<?php

namespace Unit\Mandate;

use Carbon\Carbon;
use RZP\Tests\TestCase;
use RZP\Models\UpiMandate\Frequency;
use RZP\Models\UpiMandate\SequenceNumber;

class SequenceNumberTest extends TestCase
{
    /**
     * @dataProvider functionGenerateSeqNumber
     */
    public function testGenerateSeqNumberDaily($frequency, $fromDate, $toDate, $expected)
    {
        $sequenceNumber = (new SequenceNumber($fromDate, $toDate))->generate($frequency);

        $this->assertEquals($expected, $sequenceNumber);
    }

    public function functionGenerateSeqNumber()
    {
        $testcases = [];

        /****************** Daily *******************/

        $testcases['daily_base_case'] = [
            Frequency::DAILY,
            Carbon::createFromDate(2020, 1, 2),
            Carbon::createFromDate(2020, 1, 2),
            1,
        ];

        $testcases['daily_happy_flow'] = [
            Frequency::DAILY,
            Carbon::createFromDate(2020, 1, 2),
            Carbon::createFromDate(2020, 1, 9),
            8,
        ];

        $testcases['daily_leap_year_february'] = [
            Frequency::DAILY,
            Carbon::createFromDate(2016, 2, 27),
            Carbon::createFromDate(2016, 3, 1),
            4,
        ];

        $testcases['daily_non_leap_year_february'] = [
            Frequency::DAILY,
            Carbon::createFromDate(2015, 2, 27),
            Carbon::createFromDate(2015, 3, 1),
            3,
        ];

        /****************** Monthly *******************/

        $testcases['monthly_base_case'] = [
            Frequency::MONTHLY,
            Carbon::createFromDate(2025, 8, 8),
            Carbon::createFromDate(2025, 8, 10),
            1,
        ];

        $testcases['monthly_start_date_lesser_than_current_date'] = [
            Frequency::MONTHLY,
            Carbon::createFromDate(2020, 2, 12),
            Carbon::createFromDate(2020, 3, 17),
            2,
        ];

        $testcases['monthly_start_date_greater_than_current_date'] = [
            Frequency::MONTHLY,
            Carbon::createFromDate(2020, 2, 12),
            Carbon::createFromDate(2020, 3, 4),
            2,
        ];

        $testcases['monthly_large_year_gap'] = [
            Frequency::MONTHLY,
            Carbon::createFromDate(2019, 1, 2),
            Carbon::createFromDate(2021, 3, 4),
            27,
        ];

        $testcases['monthly_end_of_month_to_beginning_of_month'] = [
            Frequency::MONTHLY,
            Carbon::createFromDate(2020, 1, 31),
            Carbon::createFromDate(2020, 2, 1),
            2,
        ];

        $testcases['monthly_leap_year_february'] = [
            Frequency::MONTHLY,
            Carbon::createFromDate(2020, 2, 29),
            Carbon::createFromDate(2021, 3, 28),
            14,
        ];

        $testcases['monthly_leap_year_to_leap_year'] = [
            Frequency::MONTHLY,
            Carbon::createFromDate(2016, 2, 29),
            Carbon::createFromDate(2020, 2, 29),
            49,
        ];

        /************** other sanity testcases **********/

        $testcases['invalid_freq'] = [
            'some_frequency',
            Carbon::createFromDate(2020, 12, 9),
            Carbon::createFromDate(2020, 12, 9),
            null,
        ];

        $testcases['null_param'] = [
            null,
            null,
            null,
            null,
        ];

        $testcases['null_frequency_param'] = [
            null,
            Carbon::createFromDate(2020, 12, 9),
            Carbon::createFromDate(2020, 12, 9),
            null,
        ];

        $testcases['null_date_params'] = [
            Frequency::DAILY,
            null,
            null,
            1,
        ];

        $testcases['mixed_date_formats'] = [
            Frequency::DAILY,
            1607373614,
            Carbon::create(2020, 12, 9, 0,0,0),
            2,
        ];

        /********* valid frequencies other than daily and monthly should always return 1 after current refactoring changes *************/

        $testcases['frequency_yearly_must_always_return_1'] = [
            Frequency::YEARLY,
            Carbon::createFromDate(2020, 12, 9),
            Carbon::createFromDate(2025, 12, 9),
            1,
        ];

        $testcases['frequency_bimonthly_must_always_return_1'] = [
            Frequency::BIMONTHLY,
            Carbon::createFromDate(2020, 1, 1),
            Carbon::createFromDate(2020, 10, 10),
            1,
        ];

        $testcases['frequency_quaterly_must_always_return_1'] = [
            Frequency::QUARTERLY,
            Carbon::createFromDate(2020, 1, 1),
            Carbon::createFromDate(2020, 12, 31),
            1,
        ];

        $testcases['frequency_as_presented_must_always_return_1'] = [
            Frequency::AS_PRESENTED,
            Carbon::createFromDate(2020, 1, 9),
            Carbon::createFromDate(2020, 10, 20),
            1,
        ];

        $testcases['frequency_weekly_must_always_return_1'] = [
            Frequency::WEEKLY,
            Carbon::createFromDate(2020, 8, 4),
            Carbon::createFromDate(2020, 8, 31),
            1,
        ];

        $testcases['frequency_half_yearly_must_always_return_1'] = [
            Frequency::HALF_YEARLY,
            Carbon::createFromDate(2020, 1, 1),
            Carbon::createFromDate(2021, 1, 1),
            1,
        ];

        return $testcases;
    }
}
