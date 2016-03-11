<?php

namespace Models\Base;

use Constants\Entity as E;
use EE\Exception;

class Report extends Service
{
    protected $allowed = array(
        E::ORDER,
        E::REFUND,
        E::PAYMENT,
        E::SETTLEMENT,
        E::TRANSACTION,
    );

    public function getReport($input, $entity)
    {
        if (in_array($entity, $this->allowed) === false)
        {
            Exception\BadRequestValidationFailureException(
                'Cannot get report for the given entity');
        }

        $this->increaseAllowedSystemLimits();

        $merchantId = $this->merchant->getId();

        [$from, $to] = $this->getTimestamps($input);

        $repo = E::getPublicEntityRepository($entity);

        $entities = (new $repo)->fetchEntitiesForReport($merchantId, $from, $to);

        date_default_timezone_set('Asia/Kolkata');

        return $entities->toArrayReport();

        $this->trace->debug(
            'MISC_TRACE_CODE',
            [
                'from'          => $from,
                'to'            => $to,
                'merchantId'    => $merchantId
            ]);


        return $reportTxns;
    }

    protected function getTimestamps($input)
    {
        $year = (int) $input['year'];

        if (isset($input['day']))
        {
            $day = (int) $input['day'];
            $month = (int) $input['month'];

            $date = Carbon::today('Asia/Kolkata')
                          ->day($day)
                          ->month($month)
                          ->year($year)
                          ->startOfDay();

            $from = $date->timestamp;
            $to = $date->addDay()->timestamp - 1;
        }
        else if (isset($input['month']))
        {
            $month = (int) $input['month'];

            assert($month > 0);
            assert($month <= 12);

            $startOfMonth = Carbon::today('Asia/Kolkata')
                                  ->month($month)
                                  ->startOfMonth()
                                  ->year($year)
                                  ->timestamp;

            $endMonth = $month + 1;

            if ($endMonth === 13)
            {
                $endMonth = 1;
                $year++;
            }

            $endOfMonth = Carbon::today('Asia/Kolkata')
                                ->month($endMonth)
                                ->startOfMonth()
                                ->year($year)
                                ->timestamp;

            $from = $startOfMonth;
            $to = $endOfMonth;
        }

        return [$from, $to];
    }

    protected function increaseAllowedSystemLimits()
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(300);
    }
}