<?php

namespace RZP\Models\FeeRecovery;

use RZP\Models\Payout;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

class Status
{
    /**
     * Status is 'unrecovered' when:
     *      1. Fee recovery hasn't been initiated yet
     *      2. Fee recovery attempt failed
     */
    const UNRECOVERED           = 'unrecovered';

    /**
     * Status is 'recovered' when:
     *      1. Fee Recovery attempt succeeded
     */
    const RECOVERED             = 'recovered';

    /**
     * Status is 'processing' when:
     *      1. Fee Recovery attempt has been initiated (corresponding payout in queued/initiated state)
     */
    const PROCESSING            = 'processing';

    /**
     * Status is 'manually recovered' when:
     *      1. Fee Recovery failed three times and after that OPs manually recovered the fees
     *         and created a new entry using an admin route to maintain data sanity
     */
    const MANUALLY_RECOVERED    = 'manually_recovered';

    /**
     * Status is 'failed' when:
     *      1. The retry cron picks up an 'unrecovered' fee_recovery entity and re-initiates recovery.
     *         In such a case, the unrecovered entity gets updated to failed. (There can only be one 'unrecovered'
     *         entry in the fee_recovery table corresponding to a certain source_entity)
     */
    const FAILED                = 'failed';

    const VALID_STATUSES = [
        self::UNRECOVERED,
        self::RECOVERED,
        self::PROCESSING,
        self::MANUALLY_RECOVERED,
        self::FAILED,
    ];

    const STATE_MACHINE = [
        null                        => [
            // During Payout initiation/Reversal creation
            self::UNRECOVERED,
        ],
        self::UNRECOVERED           => [
            // Fee Recovery Payout created (Queued/Initiated)
            self::PROCESSING,
            // Once Retry Cron has added a duplicate entry in fee_recovery, original is set to failed
            self::FAILED,
            // If fees has been manually recovered after 3 failed attempts
            self::MANUALLY_RECOVERED,
        ],
        self::PROCESSING            => [
            // Fee Recovery Payout failed
            self::UNRECOVERED,
            // Fee Recovery Payout successful
            self::RECOVERED,
        ],
        // Final state
        self::RECOVERED             => [],
        // Final state
        self::FAILED                => [],
        // Final state
        self::MANUALLY_RECOVERED    => [],
    ];

    /**
     * This mapping is used to find the status of fee_recovery based on the
     * status of the corresponding fee_recovery payout
     */
    const PAYOUT_STATUS_TO_FEE_RECOVERY_STATUS_MAPPING = [
        // If the fee_recovery payout gets 'processed', all previous fee_recovery entries corresponding to this
        // fee_recovery payout get marked as recovered
        Payout\Status::PROCESSED    => self::RECOVERED,

        // If the fee_recovery payout gets 'failed', all previous fee_recovery entries corresponding to this
        // fee_recovery payout get marked as unrecovered
        Payout\Status::FAILED       => self::UNRECOVERED,

        // If the fee_recovery payout gets 'reversed', all previous fee_recovery entries corresponding to this
        // fee_recovery payout get marked as unrecovered
        Payout\Status::REVERSED     => self::UNRECOVERED,
    ];

    // Function used by admin fetch
    public static function getAll()
    {
        return self::VALID_STATUSES;
    }

    /**
     * @param string $status
     *
     * @throws BadRequestException
     */
    public static function validateStatus(string $status)
    {
        if (in_array($status, self::VALID_STATUSES, true) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_FEE_RECOVERY_INVALID_STATUS,
                null,
                [
                    Entity::STATUS => $status
                ]
            );
        }
    }

    /**
     * @param string      $nextStatus
     * @param string|null $currentStatus
     *
     * @throws BadRequestException
     */
    public static function validateStatusUpdate(string $nextStatus,
                                                string $currentStatus = null)
    {
        self::validateStatus($nextStatus);

        $allowedTransitions = self::STATE_MACHINE[$currentStatus];

        if (in_array($nextStatus, $allowedTransitions, true) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_FEE_RECOVERY_INVALID_STATUS_TRANSITION,
                null,
                [
                    Entity::CURRENT_STATUS  => $currentStatus,
                    Entity::NEXT_STATUS     => $nextStatus
                ]
            );
        }
    }

    /**
     * This function is used to find the status of fee_recovery based on the status of corresponding fee_recovery payout
     *
     * @param string $payoutStatus
     *
     * @return string
     */
    public static function getFeeRecoveryStatusFromPayoutStatus(string $payoutStatus): string
    {
        return self::PAYOUT_STATUS_TO_FEE_RECOVERY_STATUS_MAPPING[$payoutStatus];
    }
}
