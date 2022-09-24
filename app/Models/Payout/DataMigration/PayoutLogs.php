<?php

namespace RZP\Models\Payout\DataMigration;

use RZP\Models\Payout\Entity;
use RZP\Models\Payout\Status;

class PayoutLogs
{
    const PAYOUT_ID    = 'payout_id';
    const EVENT        = 'event';
    const FROM         = 'from';
    const TO           = 'to';
    const TRIGGERED_BY = 'triggered_by';
    const SYSTEM       = 'system';

    const PAYOUT_STATUS_IN_RELATIVE_ORDER = [
        Status::PENDING,
        Status::REJECTED,
        Status::SCHEDULED,
        Status::BATCH_SUBMITTED,
        Status::CREATE_REQUEST_SUBMITTED,
        Status::ON_HOLD,
        Status::QUEUED,
        Status::CANCELLED,
        Status::CREATED,
        Status::INITIATED,
        Status::PROCESSED,
        Status::FAILED,
        Status::REVERSED,
    ];

    // For a given payout we will generate corresponding payout_logs to be stored in payout service.
    // We have relative ordering of payout statuses, we traverse through this order to generate the
    // payout_logs to maintain id order.
    public function getPayoutServicePayoutLogsForApiPayout(Entity $payout)
    {
        $payoutLogs = [];

        $previousStatus = null;

        foreach (self::PAYOUT_STATUS_IN_RELATIVE_ORDER as $status)
        {
            if ($payout->getStatusEnterTimeStamp($status) !== null)
            {
                if ($previousStatus === null)
                {
                    if (($status === Status::PENDING) or
                        ($status === Status::CREATE_REQUEST_SUBMITTED))
                    {
                        $previousStatus = $status;

                        continue;
                    }

                    if ($status !== Status::CREATE_REQUEST_SUBMITTED)
                    {
                        $previousStatus = Status::CREATE_REQUEST_SUBMITTED;
                    }
                }

                $payoutLogs[] = $this->createPayoutLogEntry($payout, $status, $previousStatus);

                $previousStatus = $status;
            }
        }

        return $payoutLogs;
    }

    // create payout_logs entry for a payout for a given status and previous status.
    protected function createPayoutLogEntry(Entity $payout, string $status, string $previousStatus)
    {
        return [
            Entity::ID         => Entity::generateUniqueId(),
            self::PAYOUT_ID    => $payout->getId(),
            self::EVENT        => $status,
            self::FROM         => $previousStatus,
            self::TO           => $status,
            Entity::MODE       => self::SYSTEM,
            self::TRIGGERED_BY => self::SYSTEM,
            Entity::CREATED_AT => $payout->getStatusEnterTimeStamp($status),
            Entity::UPDATED_AT => $payout->getStatusEnterTimeStamp($status),
        ];
    }
}
