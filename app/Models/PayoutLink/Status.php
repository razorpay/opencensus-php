<?php

namespace RZP\Models\PayoutLink;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Payout\Status as PayoutStatus;

class Status
{
    // There is no Failed state, because in case of failures the customer can retry the Payout from his side
    const ISSUED     = 'issued';
    const PROCESSING = 'processing';
    const ATTEMPTED  = 'attempted';
    const PROCESSED  = 'processed';
    const CANCELLED  = 'cancelled';

    const VALID_PROCESSING_START_STATUSES = [
        self::ISSUED,
        self::ATTEMPTED
    ];

    const VALID_STATUSES = [
        self::ISSUED,
        self::PROCESSING,
        self::ATTEMPTED,
        self::PROCESSED,
        self::CANCELLED
    ];

    const STATUS_TO_WEBHOOK_EVENT = [
        self::ISSUED     => 'api.payout_link.issued',
        self::PROCESSING => 'api.payout_link.processing',
        self::ATTEMPTED  => 'api.payout_link.attempted',
        self::PROCESSED  => 'api.payout_link.processed',
        self::CANCELLED  => 'api.payout_link.cancelled',
    ];

    const PAYOUT_TO_PAYOUT_LINK_STATUSES = [
        PayoutStatus::FAILED     => self::ATTEMPTED,
        PayoutStatus::REVERSED   => self::ATTEMPTED,
        PayoutStatus::REJECTED   => self::ATTEMPTED,
        PayoutStatus::CREATED    => self::PROCESSING,
        PayoutStatus::INITIATED  => self::PROCESSING,
        PayoutStatus::PROCESSING => self::PROCESSING,
        PayoutStatus::QUEUED     => self::PROCESSING,
        PayoutStatus::PENDING    => self::PROCESSING,
        PayoutStatus::PROCESSED  => self::PROCESSED,
        PayoutStatus::CANCELLED  => self::ATTEMPTED,
    ];

    const INTERNAL_TO_PUBLIC_STATUS = [
        self::ATTEMPTED => self::ISSUED
    ];

    /**
     * Valid state transitions.
     */
    const STATE_MACHINE = [
        null             => [
            self::ISSUED
        ],
        self::ISSUED     => [
            self::PROCESSING,
            self::CANCELLED
        ],
        self::ATTEMPTED  => [
            self::PROCESSING,
            self::CANCELLED
        ],
        self::PROCESSING => [
            self::ATTEMPTED, // in case payout fails
            self::PROCESSED,    // in case payout is successful,
            self::CANCELLED // in case the payout is cancelled
        ],
        self::PROCESSED  => [
            self::ATTEMPTED    // in case of a reversal
        ],
        self::CANCELLED  => [] // this is a final state
    ];

    // This is to handle the create entity flows, in which both the Status and ID
    // will be null to start with
    public static function validateStatusUpdate(string $nextStatus,
                                                string $currentStatus = null,
                                                string $payoutLinkId = null)
    {
        self::validate($nextStatus);

        $allowedNextStates = self::STATE_MACHINE[$currentStatus];

        if (in_array($nextStatus, $allowedNextStates, true) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_LINK_INVALID_STATUS_TRANSITION,
                null,
                [
                    'id'             => $payoutLinkId,
                    'current_status' => $currentStatus,
                    'next_status'    => $nextStatus
                ]
            );
        }
    }

    public static function validate($status)
    {
        if (in_array($status, self::VALID_STATUSES, true) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_LINK_INVALID_STATUS,
                null,
                [
                    'status' => $status
                ]
            );
        }
    }

    public static function getPublicStatusFromInternalStatus($internalStatus): string
    {
        return self::INTERNAL_TO_PUBLIC_STATUS[$internalStatus] ?? $internalStatus;
    }

    public static function payoutLinkInProcessableState($status)
    {
        return in_array($status, Status::VALID_PROCESSING_START_STATUSES, true);
    }

    public static function getWebhookEventCorrespondingToStatus(string $status)
    {
        if (array_key_exists($status, Status::STATUS_TO_WEBHOOK_EVENT) === false)
        {
            return null;
        }
        return Status::STATUS_TO_WEBHOOK_EVENT[$status];
    }
}
