<?php

namespace RZP\Models\Payout\DualWrite;

use App;

use RZP\Models\Payout\Entity;
use RZP\Models\Payout\Status;

class PayoutLogs extends Base
{
    public function dualWritePSPayoutLogs(Entity & $payout)
    {
        $payoutLogsData = $this->getPayoutLogsDataFromPayoutService($payout->getId());

        foreach ($payoutLogsData as $status => $timestamp)
        {
            if (in_array($status, Status::$timestampedStatuses, true) === false)
            {
                continue;
            }

            $timestampKey = $status . '_at';

            if ($status === Status::CREATED)
            {
                $timestampKey = Entity::INITIATED_AT;
            }

            if ($status === Status::INITIATED)
            {
                $timestampKey = Entity::TRANSFERRED_AT;
            }

            $payout->setAttribute($timestampKey, $timestamp);
        }

        $this->repo->payout->saveOrFail($payout);
    }

    protected function getPayoutLogsDataFromPayoutService(string $payoutId)
    {
        $payoutServicePayoutLogs = $this->repo->payout->getPayoutServicePayoutLogs($payoutId);

        if (count($payoutServicePayoutLogs) === 0)
        {
            return [];
        }

        $payoutLogsData = [];

        foreach ($payoutServicePayoutLogs as $payoutServicePayoutLog)
        {
            $payoutLogsData[$payoutServicePayoutLog->to] = $payoutServicePayoutLog->created_at;
        }

        return $payoutLogsData;
    }
}
