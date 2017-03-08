<?php

namespace RZP\Models\FundTransfer\Batch;

use RZP\Models\Base;
use RZP\Models\Settlement;
use Carbon\Carbon;

class Service extends Base\Service
{
    public function calculatePreviousBatchSettlementFees()
    {
        $result = $this->repo->transaction(function()
        {
            return $this->calculatePreviousBatchSettlementFeesCore();
        });

        return $result;
    }

    protected function calculatePreviousBatchSettlementFeesCore()
    {
        $batchSettlements = $this->repo->batch_settlement->getIfFeesIsNull();

        $totalFees = 0;
        $totalSetlCount = 0;

        foreach ($batchSettlements as $batch)
        {
            $timestamp = $batch->getCreatedAt();

            $date = Carbon::createFromTimestamp($timestamp, 'Asia/Kolkata');

            $date = $date->toDateString() . ' 00:00:00';

            $date = Carbon::createFromFormat('Y-m-d H:i:s', $date, 'Asia/Kolkata');
            $from = $date->timestamp;
            $to = $date->addDay()->timestamp;

            $settlements = $this->repo->settlement->fetch(
                ['from' => $from, 'to' => $to]);

            $batchFees = 0;

            assertTrue($batch->getTotalCountAttribute() === $settlements->count());

            foreach ($settlements as $setl)
            {
                $batchFees += $setl->getFees();
            }

            $batch->setFees($batchFees);

            $this->repo->saveOrFail($batch);

            $totalFees += $batchFees;
            $totalSetlCount += $settlements->count();
        }

        return ['total_fees' => $totalFees, 'total_setl_count' => $totalSetlCount];
    }

    public function computeBatchSettlementServiceTax()
    {
        return $this->repo->transaction(function ()
        {
            $batchSettlements = $this->repo->batch_settlement->getIfServiceTaxIsNullOrZero();

            $setlRepo = $this->repo->settlement;

            $totalServiceTax = 0;
            $totalSetlCount = 0;

            foreach ($batchSettlements as $batch)
            {
                $timestamp = $batch->getCreatedAt();

                $date = Carbon::createFromTimestamp($timestamp, 'Asia/Kolkata');
                $date->startOfDay();

                $from = $date->timestamp;
                $to = $date->addDay()->timestamp;

                $settlements = $setlRepo->fetch(['from' => $from, 'to' => $to]);

                $batchServiceTax = 0;

                assertTrue($batch->getTotalCount() === $settlements->count());

                foreach ($settlements as $setl)
                {
                    $batchServiceTax += $setl->getServiceTax();
                }

                $batch->setServiceTax($batchServiceTax);

                $this->repo->saveOrFail($batch);

                $totalServiceTax += $batchServiceTax;

                $totalSetlCount += $settlements->count();
            }

            return [
                'total_service_tax'         => $totalServiceTax,
                'total_batch_setl_count'    => $totalSetlCount,
                'total_batch_settlements'   => $batchSettlements->count(),
            ];
        });
    }
}
