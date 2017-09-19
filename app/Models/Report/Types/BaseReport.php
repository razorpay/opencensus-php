<?php

namespace RZP\Models\Report\Types;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Models\Base;
use RZP\Base\RuntimeManager;

class BaseReport extends Base\Core
{
    protected static $rules = [
        'year'  => 'required|digits:4',
        'month' => 'required|digits_between:1,2',
        'day'   => 'sometimes|digits_between:1,2',
        'count' => 'sometimes|integer|min:1',
        'skip'  => 'sometimes|integer|min:0',
    ];

    /**
     * This is done because once the process is pushed to queue,
     * we do not have merchant set, and hence,
     * while creating report & file entity, we are unable to set merchant
     *
     * It is used inside ReportsJob before starting `generateReport()`
     *
     * @param $merchant Merchant\Entity
     * @return void
     */
    public function setMerchant(string $merchantId = null)
    {
        if ($this->merchant === null)
        {
            $this->merchant = $this->repo->merchant->findOrFail($merchantId);
        }
    }

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

            $date = Carbon::createFromDate($year, $month, $day, Timezone::IST)
                          ->startOfDay();

            $from = $date->getTimestamp();
            $to = $date->addDay()->getTimestamp() - 1;
        }
        else if (isset($input['month']))
        {
            $month = (int) $input['month'];

            assertTrue($month > 0);
            assertTrue($month <= 12);

            $from = Carbon::createFromDate($year, $month, 1, Timezone::IST)
                          ->startOfDay()
                          ->getTimestamp();

            $to = Carbon::createFromDate($year, $month, 1, Timezone::IST)
                        ->endOfMonth()
                        ->getTimestamp();
        }

        return [$from, $to];
    }

    /**
     * 1. increase system limits
     * 2. Sets timezone
     */
    protected function setDefaults()
    {
        $this->increaseAllowedSystemLimits();

        date_default_timezone_set(Timezone::IST);
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('1024M');
        RuntimeManager::setTimeLimit(501);
    }
}
