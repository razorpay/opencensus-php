<?php

namespace RZP\Models\FundTransfer\Batch;

use RZP\Models\Base;
use RZP\Models\Settlement;
use Carbon\Carbon;

class Service extends Base\Service
{
    public function calculatePreviousBatchFundTransferFees()
    {
        $result = $this->repo->transaction(function()
        {
            return $this->calculatePreviousBatchFundTransferFeesCore();
        });

        return $result;
    }

    protected function calculatePreviousBatchFundTransferFeesCore()
    {
        $batchFundTransfers = $this->repo->batch_fund_transfer->getIfFeesIsNull();

        $totalFees = 0;
        $totalSetlCount = 0;

        foreach ($batchFundTransfers as $batch)
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

    public function computeBatchFundTransferTax()
    {
        return $this->repo->transaction(function ()
        {
            $batchFundTransfers = $this->repo->batch_fund_transfer->getIfTaxIsNullOrZero();

            $setlRepo = $this->repo->settlement;

            $totalTax = 0;
            $totalSetlCount = 0;

            foreach ($batchFundTransfers as $batch)
            {
                $timestamp = $batch->getCreatedAt();

                $date = Carbon::createFromTimestamp($timestamp, 'Asia/Kolkata');
                $date->startOfDay();

                $from = $date->timestamp;
                $to = $date->addDay()->timestamp;

                $settlements = $setlRepo->fetch(['from' => $from, 'to' => $to]);

                $batchTax = 0;

                assertTrue($batch->getTotalCount() === $settlements->count());

                foreach ($settlements as $setl)
                {
                    $batchTax += $setl->getServiceTax();
                }

                $batch->setServiceTax($batchTax);

                $batch->setTax($batchTax);

                $this->repo->saveOrFail($batch);

                $totalTax += $batchTax;

                $totalSetlCount += $settlements->count();
            }

            return [
                'total_tax'                  => $totalTax,
                'total_batch_setl_count'     => $totalSetlCount,
                'total_batch_fund_transfers' => $batchFundTransfers->count(),
            ];
        });
    }
}
