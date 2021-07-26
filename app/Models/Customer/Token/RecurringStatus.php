<?php

namespace RZP\Models\Customer\Token;

use RZP\Error\ErrorCode;
use RZP\Exception\InvalidArgumentException;

class RecurringStatus
{
    /**
     * This status indicates that we are waiting for the bank's response to update the status
     */
    const INITIATED         = 'initiated';

    /**
     * This status indicates that this token has been confirmed for future recurring payments
     */
    const CONFIRMED         = 'confirmed';

    /**
     * This status indicates that this token has been rejected for future recurring payments
     */
    const REJECTED          = 'rejected';

    /**
     * This status indicates that recurring is not applicable for this token. This is basically
     * for all non-recurring tokens.
     */
    const NOT_APPLICABLE    = 'not_applicable';

    const PAID              = 'paid';

    /**
     * This status indicates that this token has been paused for future recurring payments
     */
    const PAUSED         = 'paused';

    /**
     * This status indicates that this token has been cancel for future recurring payments
     */
    const CANCELLED      = 'cancelled';

    /**
    * These apps are notified when token status changes
    */
    const appsToNotifyTokenStatus  = [ 'subscription', ];

    public static $webhookStatuses = [
        self::CONFIRMED,
        self::REJECTED,
        self::PAUSED,
        self::CANCELLED,
    ];

    protected static $finalStatuses = [
        self::CONFIRMED,
        self::REJECTED,
    ];

    protected static $timestampedStatuses = [
        self::CONFIRMED,
        self::REJECTED,
    ];

    public static function isRecurringStatusValid($recurringStatus): bool
    {
        return (defined(__CLASS__ . '::' . strtoupper($recurringStatus)));
    }

    public static function validateRecurringStatus($recurringStatus)
    {
        if (self::isRecurringStatusValid($recurringStatus) === false)
        {
            throw new InvalidArgumentException(
                'Invalid recurring type',
                [
                    'field'            => Entity::RECURRING_STATUS,
                    'recurring_status' => $recurringStatus
                ]);
        }
    }

    public static function isFinalStatus($recurringStatus): bool
    {
        return (in_array($recurringStatus, self::$finalStatuses, true) === true);
    }

    public static function isTokenStatusConfirmed($recurringStatus): bool
    {
        return ($recurringStatus === self::CONFIRMED);
    }

    public static function isTimestampedStatus($recurringStatus): bool
    {
        return (in_array($recurringStatus, self::$timestampedStatuses, true) === true);
    }

    public static function isWebhookStatus($recurringStatus): bool
    {
        return (in_array($recurringStatus, self::$webhookStatuses, true) === true);
    }
}
