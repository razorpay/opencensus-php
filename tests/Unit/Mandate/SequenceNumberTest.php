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

        //Commenting these tests as the supported frequency are only monthly and as_presented

        /****************** Daily *******************/
        /*   $testcases['daily_base_case'] = [
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
        */

        /***************** Weekly *******************/

      /*  $testcases['weekly_base_case'] = [
            Frequency::WEEKLY,
            Carbon::createFromDate(2021, 1, 4),
            Carbon::createFromDate(2021, 1, 8),
            1,
        ];

        $testcases['weekly_happy_flow'] = [
            Frequency::WEEKLY,
            Carbon::createFromDate(2021, 1, 4),
            Carbon::createFromDate(2021, 1, 10),
            1,
        ];

        $testcases['weeky_happy_flow_2'] = [
            Frequency::WEEKLY,
            Carbon::createFromDate(2021, 1, 4),
            Carbon::createFromDate(2021, 1, 12),
            2,
        ];

        $testcases['weekly_start_date_as_sunday'] = [
            Frequency::WEEKLY,
            Carbon::createFromDate(2021, 1, 3),
            Carbon::createFromDate(2021, 1, 4),
            2,
        ];

        $testcases['weekly_start_date_next_year_overlap'] = [
            Frequency::WEEKLY,
            Carbon::createFromDate(2020, 12, 27),
            Carbon::createFromDate(2021, 1, 4),
            3,
        ];

        $testcases['weekly_leap_year_feb_happy_flow'] = [
            Frequency::WEEKLY,
            Carbon::createFromDate(2020, 2, 29),
            Carbon::createFromDate(2020, 3, 1),
            1,
        ];

        $testcases['weekly_leap_year_feb_happy_flow_2'] = [
            Frequency::WEEKLY,
            Carbon::createFromDate(2020, 2, 29),
            Carbon::createFromDate(2020, 3, 3),
            2,
        ];*/

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

        /******************  Bimonthly *****************/

      /*  $testcases['bimonthy_same_cycle'] = [
            Frequency::BIMONTHLY,
            Carbon::createFromDate(2021, 8, 8),
            Carbon::createFromDate(2021, 8, 10),
            1,
        ];

        $testcases['bimonthly_next_cycle'] = [
            Frequency::BIMONTHLY,
            Carbon::createFromDate(2021, 8, 8),
            Carbon::createFromDate(2021, 9, 30),
            2,
        ];

        $testcases['bimonthy_same_dates'] = [
            Frequency::BIMONTHLY,
            Carbon::createFromDate(2021, 8, 8),
            Carbon::createFromDate(2021, 8, 8),
            1,
        ];

        $testcases['bimonthly_next_cycle_last_month'] = [
            Frequency::BIMONTHLY,
            Carbon::createFromDate(2021, 8, 8),
            Carbon::createFromDate(2021, 10, 1),
            2,
        ];

        $testcases['bimonthly_start_date_2nd_cycle_end_date_5th_cycle'] = [
            Frequency::BIMONTHLY,
            Carbon::createFromDate(2021, 3, 31),
            Carbon::createFromDate(2021, 9, 1),
            4,
        ];

        $testcases['bimonthly_year_gap'] = [
            Frequency::BIMONTHLY,
            Carbon::createFromDate(2021, 2, 1),
            Carbon::createFromDate(2023, 9, 1),
            17,
        ];

        $testcases['bimonthly_start_date_as_last_day_of_cycle_end_date_as_start_of_new_cycle'] = [
            Frequency::BIMONTHLY,
            Carbon::createFromDate(2021, 8, 31),
            Carbon::createFromDate(2021, 10, 1),
            2,
        ];

        $testcases['bimonthly_start_date_as_end_of_year_end_date_as_next_year'] = [
            Frequency::BIMONTHLY,
            Carbon::createFromDate(2020, 12, 31),
            Carbon::createFromDate(2021, 12, 1),
            7,
        ];
        $testcases['bimonthly_start_date_as_end_of_year_end_date_as_start_of_next_year'] = [
            Frequency::BIMONTHLY,
            Carbon::createFromDate(2020, 12, 31),
            Carbon::createFromDate(2021, 1, 1),
            2,
        ];

        $testcases['bimonthly_start_date_feb_leap_year'] = [
            Frequency::BIMONTHLY,
            Carbon::createFromDate(2020, 2, 29),
            Carbon::createFromDate(2020, 7, 31),
            4,
        ];
        */
        /******************  Quarterly *****************/

      /*  $testcases['quarterly_same_dates'] = [
            Frequency::QUARTERLY,
            Carbon::createFromDate(2021, 8, 8),
            Carbon::createFromDate(2021, 8, 8),
            1,
        ];

        $testcases['quarterly_base_case_same_year_first_quarter'] = [
            Frequency::QUARTERLY,
            Carbon::createFromDate(2021, 1, 1),
            Carbon::createFromDate(2021, 3, 1),
            1,
        ];

        $testcases['quarterly_base_case_same_year_second_quarter'] = [
            Frequency::QUARTERLY,
            Carbon::createFromDate(2021, 1, 1),
            Carbon::createFromDate(2021, 5, 1),
            2,
        ];
        $testcases['quarterly_base_case_same_year_third_quarter'] = [
            Frequency::QUARTERLY,
            Carbon::createFromDate(2021, 1, 1),
            Carbon::createFromDate(2021, 8, 1),
            3,
        ];
        $testcases['quarterly_base_case_same_year_fourth_quarter'] = [
            Frequency::QUARTERLY,
            Carbon::createFromDate(2021, 1, 1),
            Carbon::createFromDate(2021, 10, 1),
            4,
        ];

        $testcases['quarterly_end_date_next_year'] = [
            Frequency::QUARTERLY,
            Carbon::createFromDate(2021, 1, 1),
            Carbon::createFromDate(2022, 4, 1),
            6,
        ];

        $testcases['quarterly_start_date_end_of_quarter_end_date_start_of_next_quarter'] = [
            Frequency::QUARTERLY,
            Carbon::createFromDate(2021, 3, 31),
            Carbon::createFromDate(2021, 4, 1),
            2,
        ];

        $testcases['quarterly_start_date_end_of_year_end_date_start_of_year'] = [
            Frequency::QUARTERLY,
            Carbon::createFromDate(2021, 12, 31),
            Carbon::createFromDate(2022, 1, 1),
            2,
        ];

        $testcases['quarterly_year_gap'] = [
            Frequency::QUARTERLY,
            Carbon::createFromDate(2020, 10, 8),
            Carbon::createFromDate(2025, 12, 31),
            21,
        ];

        $testcases['quarterly_start_date_feb_leap_year'] = [
            Frequency::QUARTERLY,
            Carbon::createFromDate(2020, 2, 29),
            Carbon::createFromDate(2020, 7, 31),
            3,
        ];

        $testcases['quarterly_start_date_as_last_day_of_cycle'] = [
            Frequency::QUARTERLY,
            Carbon::createFromDate(2021, 8, 31),
            Carbon::createFromDate(2021, 11, 1),
            2,
        ];
        */
        /******************  Half-Yearly *****************/
        /*
        $testcases['halfyearly_same_dates'] = [
            Frequency::HALF_YEARLY,
            Carbon::createFromDate(2021, 8, 8),
            Carbon::createFromDate(2021, 8, 8),
            1,
        ];

        $testcases['halfyearly_start_date_end_date_in_same_year_first_cycle'] = [
            Frequency::HALF_YEARLY,
            Carbon::createFromDate(2021, 1, 1),
            Carbon::createFromDate(2021, 5, 1),
            1,
        ];

        $testcases['halfyearly_start_date_end_date_in_second_cycle'] = [
            Frequency::HALF_YEARLY,
            Carbon::createFromDate(2021, 8, 30),
            Carbon::createFromDate(2021, 12, 1),
            1,
        ];

        $testcases['halfyearly_end_date_same_year_second_cycle'] = [
            Frequency::HALF_YEARLY,
            Carbon::createFromDate(2021, 1, 1),
            Carbon::createFromDate(2021, 8, 1),
            2,
        ];

        $testcases['halfyearly_end_date_next_year_second_cycle'] = [
            Frequency::HALF_YEARLY,
            Carbon::createFromDate(2021, 3, 1),
            Carbon::createFromDate(2022, 11, 1),
            4,
        ];

        $testcases['halfyearly_start_date_second_cycle_end_date_next_year_second_cycle'] = [
            Frequency::HALF_YEARLY,
            Carbon::createFromDate(2021, 10, 31),
            Carbon::createFromDate(2022, 8, 1),
            3,
        ];

        $testcases['halfyearly_end_date_same_month_next_year'] = [
            Frequency::HALF_YEARLY,
            Carbon::createFromDate(2021, 1, 1),
            Carbon::createFromDate(2022, 1, 1),
            3,
        ];

        $testcases['halfyearly_more_than_1_year_gap'] = [
            Frequency::HALF_YEARLY,
            Carbon::createFromDate(2021, 6, 1),
            Carbon::createFromDate(2024, 7, 1),
            8,
        ];

        $testcases['halfyearly_start_date_feb_leap_year'] = [
            Frequency::HALF_YEARLY,
            Carbon::createFromDate(2020, 2, 29),
            Carbon::createFromDate(2022, 1, 31),
            5,
        ];

        $testcases['halfyearly_start_date_end_of_june_end_date_start_of_july'] = [
            Frequency::HALF_YEARLY,
            Carbon::createFromDate(2021, 6, 30),
            Carbon::createFromDate(2021, 7, 1),
            2,
        ];

        $testcases['halfyearly_start_date_as_end_of_year_end_date_as_start_of_next_year'] = [
            Frequency::HALF_YEARLY,
            Carbon::createFromDate(2021, 12, 31),
            Carbon::createFromDate(2022, 01, 1),
            2,
        ];
        */
        /****************** Yearly *****************/
        /*
        $testcases['yearly_same_year'] = [
            Frequency::YEARLY,
            Carbon::createFromDate(2021, 8, 1),
            Carbon::createFromDate(2021, 10, 1),
            1,
        ];

        $testcases['yearly_next_year'] = [
            Frequency::YEARLY,
            Carbon::createFromDate(2021, 8, 1),
            Carbon::createFromDate(2022, 8, 1),
            2,
        ];

        $testcases['early_start_date_as_end_of_year_end_date_as_start_of_next_year'] = [
            Frequency::YEARLY,
            Carbon::createFromDate(2021, 12, 31),
            Carbon::createFromDate(2022, 1, 1),
            2,
        ];

        $testcases['yearly_year_gap'] = [
            Frequency::YEARLY,
            Carbon::createFromDate(2020, 12, 1),
            Carbon::createFromDate(2030, 05, 1),
            11,
        ];

        $testcases['yearly_start_date_feb_leap_year'] = [
            Frequency::YEARLY,
            Carbon::createFromDate(2020, 2, 29),
            Carbon::createFromDate(2025, 2, 28),
            6,
        ];
        */
        /************** sanity testcases **********/

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
            Frequency::MONTHLY,
            null,
            null,
            1,
        ];

        $testcases['mixed_date_formats'] = [
            Frequency::MONTHLY,
            1607373614,
            Carbon::createFromFormat('Y-m-d H:i:s' , '2020-12-9 00:00:00'),
            1,
        ];

        // frequencies as_presented should always return 1 as these will be implemented in future
        $testcases['frequency_as_presented_must_always_return_1'] = [
            Frequency::AS_PRESENTED,
            Carbon::createFromDate(2020, 1, 9),
            Carbon::createFromDate(2020, 10, 20),
            1,
        ];

        /********************** Edge cases ********************/
        // Time dependent edge cases to check when start time is 23:59:59PM and end time is 00:00:01 AM

       /* $testcases['daily_start_date_23:59PM_to_end_date_12:01AM'] = [
            Frequency::DAILY,
            Carbon::createFromFormat('Y-m-d H:i:s' , '2021-01-1 23:59:59'),
            Carbon::createFromFormat('Y-m-d H:i:s' , '2021-01-2 00:00:01'),
            2,
        ];

        $testcases['weekly_sunday_23:59PM_to_monday_12:01AM'] = [
            Frequency::WEEKLY,
            Carbon::createFromFormat('Y-m-d H:i:s' , '2021-01-3 23:59:59'),
            Carbon::createFromFormat('Y-m-d H:i:s' , '2021-01-4 00:00:01'),
            2,
        ];*/

        $testcases['monthly_monthEnd_23:59PM_to_next_monthStart_12:01AM'] = [
            Frequency::MONTHLY,
            Carbon::createFromFormat('Y-m-d H:i:s' , '2020-12-31 23:59:59'),
            Carbon::createFromFormat('Y-m-d H:i:s' , '2021-1-1 00:00:01'),
            2,
        ];

        $testcases['yearly_yearEnd_23:59PM_to_next_yearStart_12:01AM'] = [
            Frequency::MONTHLY,
            Carbon::createFromFormat('Y-m-d H:i:s' , '2020-12-31 23:59:59'),
            Carbon::createFromFormat('Y-m-d H:i:s' , '2021-1-1 00:00:01'),
            2,
        ];

        /********************** Unsupported Frequencies *******************/
        //Frequencies other than as_presnted and monthly are not supported, hence they should return null

        $testcases['unsupported_daily'] = [
            Frequency::DAILY,
            Carbon::createFromDate(2020, 12, 9),
            Carbon::createFromDate(2020, 12, 9),
            null,
        ];

        $testcases['unsupported_weekly'] = [
            Frequency::WEEKLY,
            Carbon::createFromDate(2020, 12, 9),
            Carbon::createFromDate(2020, 12, 20),
            null,
        ];

        $testcases['unsupported_bimonthly'] = [
            Frequency::BIMONTHLY,
            Carbon::createFromDate(2020, 8, 9),
            Carbon::createFromDate(2020, 12, 9),
            null,
        ];

        $testcases['unsupported_quarterly'] = [
            Frequency::QUARTERLY,
            Carbon::createFromDate(2020, 2, 9),
            Carbon::createFromDate(2020, 12, 9),
            null,
        ];

        $testcases['unsupported_half_yearly'] = [
            Frequency::HALF_YEARLY,
            Carbon::createFromDate(2020, 12, 9),
            Carbon::createFromDate(2021, 04, 9),
            null,
        ];

        $testcases['unsupported_yearly'] = [
            Frequency::YEARLY,
            Carbon::createFromDate(2020, 12, 9),
            Carbon::createFromDate(2024, 12, 9),
            null,
        ];

        return $testcases;
    }
}
