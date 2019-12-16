<?php

namespace RZP\Models\PayoutLink;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

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

    /**
     * Valid state transitions.
     */
    const STATE_MACHINE = [
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

    // Note: '?' before the type hint, means that a NULL is also allowed instead of a string.
    // This is to handle the create entity flows, in which both the Status and ID will be null to start with
    static function validateStatusUpdate(?string $currentStatus, string $nextStatus, string $payoutLinkId = null)
    {
        // This will happen only in case of create, when there is no status set
        // We have to validate that the starting state is ISSUED and nothing else
        if ((empty($currentStatus) === true) and
            ($nextStatus === self::ISSUED))
        {
            return;
        }

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
