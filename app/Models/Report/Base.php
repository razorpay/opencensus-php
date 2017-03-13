<?php

namespace RZP\Models\Report;

use Carbon\Carbon;

use RZP\Models\Base\Core;

class Base extends Core
{
    protected static q$rules = [
        'year'  =>  'required|digits:4',
        'month' =>  'required|digits_between:1,2',
        'day'   =>  'sometimes|digits_between:1,2',
        'count' =>  'sometimes|integer|min:1',
        'skip'  =>  'sometimes|integer|min:0',
    ];

    protected function getTimestamps($input): array
    {
        $year = (int) $input['year'];

        $from = $to = null;

        // If day is set, `from` and `to` are of that day start and end only.
        // If day is not set, month should be set. `from` and `to` will be
        // the first day and the last day of the month.
        if (isset($input['day']))
        {
            $day = (int) $input['day'];
            $month = (int) $input['month'];

            $date = Carbon::today('Asia/Kolkata')
                          ->month($month)
                          ->day($day)
                          ->year($year)
                          ->startOfDay();

            $from = $date->timestamp;
            $to = $date->addDay()->timestamp - 1;
        }
        else if (isset($input['month']))
        {
            $month = (int) $input['month'];

            assertTrue($month > 0);
            assertTrue($month <= 12);

            $from = Carbon::today('Asia/Kolkata')
                                  ->month($month)
                                  ->year($year)
                                  ->startOfMonth()
                                  ->timestamp;

            $to   = Carbon::today('Asia/Kolkata')
                                  ->month($month)
                                  ->year($year)
                                  ->endOfMonth()
                                  ->timestamp;
        }

        return [$from, $to];
    }
}
