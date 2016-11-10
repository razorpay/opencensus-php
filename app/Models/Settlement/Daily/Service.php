<?php

namespace RZP\Models\Settlement\Daily;

use RZP\Models\Base;
use RZP\Models\Settlement;
use RZP\Models\Settlement\Daily;
use Carbon\Carbon;

class Service extends Base\Service
{
    public function fetch($id)
    {
        $setl = $this->repo->daily_settlement->findByPublicId($id);

        return $setl->toArrayPublic();
    }

    public function fetchMultiple($input)
    {
        $settlements = $this->repo->daily_settlement->fetch($input);

        return $settlements->toArrayPublic();
    }

    public function calculatePreviousDailySettlementFees()
    {
        $result = $this->repo->transaction(function()
                {
                    return $this->calculatePreviousDailySettlementFeesCore();
                });

        return $result;
    }

    protected function calculatePreviousDailySettlementFeesCore()
    {
        $dailySettlements = $this->repo->daily_settlement->getIfFeesIsNull();

        $totalFees = 0;
        $totalSetlCount = 0;

        foreach ($dailySettlements as $daily)
        {
            $timestamp = $daily->getCreatedAt();

            $date = Carbon::createFromTimestamp($timestamp, 'Asia/Kolkata');

            $date = $date->toDateString() . ' 00:00:00';

            $date = Carbon::createFromFormat('Y-m-d H:i:s', $date, 'Asia/Kolkata');
            $from = $date->timestamp;
            $to = $date->addDay()->timestamp;

            $settlements = $this->repo->settlement->fetch(
                ['from' => $from, 'to' => $to]);

            $dailyFees = 0;

            assertTrue($daily->getSettlementCountAttribute() === $settlements->count());

            foreach ($settlements as $setl)
            {
                $dailyFees += $setl->getFees();
            }

            $daily->setFees($dailyFees);

            $this->repo->saveOrFail($daily);

            $totalFees += $dailyFees;
            $totalSetlCount += $settlements->count();
        }

        return ['total_fees' => $totalFees, 'total_setl_count' => $totalSetlCount];
    }

    public function computeDailySettlementServiceTax()
    {
        return $this->repo->transaction(function ()
        {
            $dailySettlements = $this->repo->daily_settlement->getIfServiceTaxIsNullOrZero();

            $setlRepo = $this->repo->settlement;

            $totalServiceTax = 0;
            $totalSetlCount = 0;

            foreach ($dailySettlements as $daily)
            {
                $timestamp = $daily->getCreatedAt();

                $date = Carbon::createFromTimestamp($timestamp, 'Asia/Kolkata');
                $date->startOfDay();

                $from = $date->timestamp;
                $to = $date->addDay()->timestamp;

                $settlements = $setlRepo->fetch(['from' => $from, 'to' => $to]);

                $dailyServiceTax = 0;

                assertTrue($daily->getSettlementCount() === $settlements->count());

                foreach ($settlements as $setl)
                {
                    $dailyServiceTax += $setl->getServiceTax();
                }

                $daily->setServiceTax($dailyServiceTax);

                $this->repo->saveOrFail($daily);

                $totalServiceTax += $dailyServiceTax;

                $totalSetlCount += $settlements->count();
            }

            return [
                'total_service_tax'         => $totalServiceTax,
                'total_daily_setl_count'    => $totalSetlCount,
                'total_daily_settlements'   => $dailySettlements->count(),
            ];
        });
    }
}
