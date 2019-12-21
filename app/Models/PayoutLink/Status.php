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
    const PAID       = 'paid';
    const CANCELLED  = 'cancelled';

    const VALID_STATUSES = [
        self::ISSUED,
        self::PROCESSING,
        self::PAID,
        self::CANCELLED
    ];

    const PAYOUT_TO_PAYOUT_LINK_STATUSES = [
        PayoutStatus::PROCESSING => self::PROCESSING,
        PayoutStatus::CANCELLED  => self::ISSUED,
        PayoutStatus::FAILED     => self::ISSUED,
        PayoutStatus::REVERSED   => self::ISSUED,
        PayoutStatus::CREATED    => self::PROCESSING,
        PayoutStatus::INITIATED  => self::PROCESSING,
        PayoutStatus::PROCESSED  => self::PAID,
        PayoutStatus::QUEUED     => self::PROCESSING,
    ];

    /**
     * Valid state transitions.
     */
    const STATE_MACHINE = [
        null => [
            self::ISSUED
        ],
        self::ISSUED => [
            self::PROCESSING,
            self::CANCELLED
        ],
        self::PROCESSING => [
            self::ISSUED, // in case payout fails
            self::PAID    // in case payout is successful,
        ],
        self::PAID => [
            self::ISSUED    // in case of a reversal
        ],
        self::CANCELLED => [] // this is a final state
    ];

    // This is to handle the create entity flows, in which both the Status and ID
    // will be null to start with
    public static function validateStatusUpdate(string $nextStatus,
                                                string $currentStatus = null,
                                                string $payoutLinkId = null)
    {
        $context = [
            'id'             => $payoutLinkId,
            'current_status' => $currentStatus,
            'next_status'    => $nextStatus
        ];

        if (in_array($nextStatus, self::VALID_STATUSES) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_LINK_INVALID_STATUS,
                null,
                $context
            );
        }

        $allowedNextStates = self::STATE_MACHINE[$currentStatus];

        if (in_array($nextStatus, $allowedNextStates) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_LINK_INVALID_STATUS_TRANSITION,
                null,
                $context
            );
        }
    }
}
