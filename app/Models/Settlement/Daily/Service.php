<?php

namespace RZP\Models\Settlement\Daily;

use RZP\Models\Base;
use RZP\Models\Gateway;
use RZP\Models\Settlement;
use RZP\Models\Settlement\Daily;
use Carbon\Carbon;

class Service extends Base\Service
{
    public function fetch($id)
    {
        Daily\Entity::verifyIdAndStripSign($id);

        $setl = (new Daily\Repository)->findOrFailPublic($id);

        return $setl->toArrayPublic();
    }

    public function fetchMultiple($input)
    {
        $settlements = (new Daily\Repository)->fetch($input);

        return $settlements->toArrayPublic();
    }

    public function calculatePreviousDailySettlementFees()
    {
        $repo = new Daily\Repository;

        $result = $repo->transaction(function() use ($repo)
                {
                    return $this->calculatePreviousDailySettlementFeesCore($repo);
                });

        return $result;
    }

    protected function calculatePreviousDailySettlementFeesCore($repo)
    {
        $dailySettlements = $repo->getIfFeesIsNull();

        $setlRepo = new Settlement\Repository;

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

            $settlements = $setlRepo->fetch(
                ['from' => $from, 'to' => $to]);

            $dailyFees = 0;

            assert($daily->getSettlementCountAttribute() === $settlements->count());

            foreach ($settlements as $setl)
            {
                $dailyFees += $setl->getFees();
            }

            $daily->setFees($dailyFees);

            $repo->saveOrFail($daily);

            $totalFees += $dailyFees;
            $totalSetlCount += $settlements->count();
        }

        return ['total_fees' => $totalFees, 'total_setl_count' => $totalSetlCount];
    }

    public function computeDailySettlementServiceTax()
    {
        $repo = new Daily\Repository;

        return $repo->transaction(function () use($repo)
        {
            $dailySettlements = $repo->getIfServiceTaxIsNullOrZero();

            $setlRepo = new Settlement\Repository;

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

                assert($daily->getSettlementCount() === $settlements->count());

                foreach ($settlements as $setl)
                {
                    $dailyServiceTax += $setl->getServiceTax();
                }

                $daily->setServiceTax($dailyServiceTax);

                $repo->saveOrFail($daily);

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