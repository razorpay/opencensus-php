<?php

namespace RZP\Models\Payout\DualWrite;

use App;

use RZP\Models\Payout\Entity;
use RZP\Models\Payout\Status;

class PayoutLogs extends Base
{
    public function dualWritePSPayoutLogs(Entity & $payout)
    {
        list($payoutLogsData, $initialStatus) = $this->getPayoutLogsDataFromPayoutService($payout->getId());

        foreach ($payoutLogsData as $status => $timestamp)
        {
            $this->setTimestampForStatusInPayout($payout, $status, $timestamp);
        }

        if (empty($initialStatus) == false)
        {
            $this->setTimestampForStatusInPayout($payout, $initialStatus, $payout->getCreatedAt());
        }

        if (empty($payoutLogsData) === true)
        {
            $this->setTimestampForStatusInPayout($payout, $payout->getStatus(), $payout->getCreatedAt());
        }
    }

    protected function setTimestampForStatusInPayout(Entity & $payout, string $status, $timestamp)
    {
        if ((in_array($status, Status::$timestampedStatuses, true) === false) and
            (in_array($status, Status::$timestampedStatuses2, true) === false))
        {
            return;
        }

        $timestampKey = $status . '_at';

        if (in_array($status, Status::$timestampedStatuses2, true) === true)
        {
            $timestampKey = $status . '_on';
        }

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

    protected function getPayoutLogsDataFromPayoutService(string $payoutId)
    {
        $payoutServicePayoutLogs = $this->repo->payout->getPayoutServicePayoutLogs($payoutId);

        $payoutLogsData = [];

        $initialStatus = null;

        if (count($payoutServicePayoutLogs) === 0)
        {
            return [$payoutLogsData, $initialStatus];
        }

        foreach ($payoutServicePayoutLogs as $payoutServicePayoutLog)
        {
            if (empty($initialStatus) === true)
            {
                $initialStatus = $payoutServicePayoutLog->from;
            }

            $payoutLogsData[$payoutServicePayoutLog->to] = $payoutServicePayoutLog->created_at;
        }

        return [$payoutLogsData, $initialStatus];
    }
}
